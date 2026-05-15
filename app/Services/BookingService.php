<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\LayananModel;
use App\Models\StylistModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use RuntimeException;

class BookingService
{
    private SlotService $slotService;

    public function __construct()
    {
        $this->slotService = new SlotService();
    }

    public function create(array $data): array
    {
        foreach (['nama_pelanggan', 'nomor_hp_pelanggan', 'layanan_id', 'tanggal', 'slot_mulai'] as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Data booking belum lengkap.');
            }
        }
        $stylistId = isset($data['stylist_id']) && $data['stylist_id'] ? (int) $data['stylist_id'] : null;
        if (! $stylistId) {
            $stylist = (new StylistModel())->defaultActive();
            if (! $stylist) {
                throw new RuntimeException('Belum ada stylist default. Hubungi pemilik.');
            }
            $stylistId = (int) $stylist['id'];
        }

        $phone = $this->normalizePhone((string) $data['nomor_hp_pelanggan']);
        $layanan = (new LayananModel())->where('is_active', 1)->find((int) $data['layanan_id']);
        if (! $layanan) {
            throw new RuntimeException('Layanan tidak valid.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $validation = $this->slotService->validateBookingSlot((int) $data['layanan_id'], $stylistId, $data['tanggal'], $data['slot_mulai']);
            $sumber = ($data['sumber'] ?? 'online') === 'walkin' ? 'walkin' : 'online';
            $statusInitial = $sumber === 'walkin' ? ($data['initial_status'] ?? 'accepted') : 'pending_verification';
            $kode = $this->generateKodeBooking();
            $now = date('Y-m-d H:i:s');

            $bookingRow = [
                'kode_booking' => $kode,
                'user_id' => isset($data['user_id']) && $data['user_id'] ? (int) $data['user_id'] : null,
                'nama_pelanggan' => trim((string) $data['nama_pelanggan']),
                'nomor_hp_pelanggan' => $phone,
                'layanan_id' => (int) $layanan['id'],
                'stylist_id' => $stylistId,
                'tanggal' => $data['tanggal'],
                'slot_mulai' => $validation['slot_mulai'] . ':00',
                'slot_selesai' => $validation['slot_selesai'] . ':00',
                'jumlah_slot' => $validation['jumlah_slot'],
                'harga_layanan' => (int) $layanan['harga'],
                'status' => $statusInitial,
                'sumber' => $sumber,
                'catatan' => $data['catatan'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($statusInitial === 'accepted') {
                $bookingRow['verified_via'] = $data['verified_via'] ?? 'walkin';
                $bookingRow['verified_at'] = $now;
            }

            $db->table('bookings')->insert($bookingRow);
            $bookingId = (int) $db->insertID();

            foreach ($validation['slots_needed'] as $slot) {
                $db->table('booking_slots')->insert([
                    'booking_id' => $bookingId,
                    'stylist_id' => $stylistId,
                    'tanggal' => $data['tanggal'],
                    'slot_waktu' => $slot . ':00',
                    'status' => 'held',
                    'created_at' => $now,
                ]);
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Slot sudah terisi, silakan pilih waktu lain.');
            }
            $db->transCommit();

            $actor = $data['actor'] ?? ($sumber === 'walkin' ? 'admin' : 'pelanggan');
            $actorRole = $data['actor_role'] ?? ($sumber === 'walkin' ? 'admin' : 'pelanggan');
            $this->logEvent($bookingId, 'created', $actor, $actorRole, [
                'kode' => $kode,
                'layanan_id' => (int) $layanan['id'],
                'tanggal' => $data['tanggal'],
                'slot_mulai' => $validation['slot_mulai'],
                'slot_selesai' => $validation['slot_selesai'],
            ]);

            $row = (new BookingModel())->detail($bookingId);
            return $row ?: ['id' => $bookingId, 'kode_booking' => $kode];
        } catch (DatabaseException|RuntimeException $e) {
            $db->transRollback();
            if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                throw new RuntimeException('Slot sudah terisi, silakan pilih waktu lain.');
            }
            throw $e;
        }
    }

    public function verify(int $bookingId, ?int $userId = null): void
    {
        $this->transitionPending($bookingId, 'accepted', $userId, null);
    }

    public function reject(int $bookingId, ?int $userId = null, ?string $reason = null): void
    {
        $this->transitionPending($bookingId, 'rejected', $userId, $reason);
    }

    public function cancel(int $bookingId, string $by, ?int $userId = null): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->require($bookingId);
        if (! in_array($booking['status'], ['pending_verification', 'accepted'], true)) {
            $db->transRollback();
            throw new RuntimeException('Booking tidak dapat dibatalkan karena status sudah final.');
        }
        if ($by === 'pelanggan') {
            $slotTs = strtotime("{$booking['tanggal']} {$booking['slot_mulai']}");
            if ($slotTs - time() < 2 * 3600) {
                $db->transRollback();
                throw new RuntimeException('Pembatalan dari pelanggan harus dilakukan minimal 2 jam sebelum jam booking.');
            }
        }
        $cancelledBy = $by === 'pelanggan' ? 'pelanggan' : ('dashboard:' . ($userId ?? 'unknown'));
        $db->table('bookings')->where('id', $bookingId)->whereIn('status', ['pending_verification', 'accepted'])->update([
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $cancelledBy,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking sudah berubah status.');
        }
        $db->table('booking_slots')->where('booking_id', $bookingId)->delete();
        $db->transCommit();

        $this->logEvent($bookingId, 'cancelled', $cancelledBy, $by === 'pelanggan' ? 'pelanggan' : 'admin', []);
    }

    public function complete(int $bookingId, ?int $userId, string $metodeBayar = 'cash', ?string $catatan = null): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->require($bookingId);
        if ($booking['status'] !== 'accepted') {
            $db->transRollback();
            throw new RuntimeException('Hanya booking diterima yang dapat ditandai selesai.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('bookings')->where('id', $bookingId)->where('status', 'accepted')->update([
            'status' => 'completed',
            'completed_at' => $now,
            'updated_at' => $now,
        ]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking sudah berubah status dan tidak dapat ditandai selesai.');
        }
        $existing = $db->table('transaksi')->where('booking_id', $bookingId)->countAllResults();
        if ($existing === 0) {
            $db->table('transaksi')->insert([
                'booking_id' => $bookingId,
                'nominal' => (int) $booking['harga_layanan'],
                'metode_bayar' => $metodeBayar,
                'tanggal_transaksi' => $now,
                'catatan' => $catatan,
                'created_at' => $now,
            ]);
        }
        $db->transCommit();

        $this->logEvent($bookingId, 'completed', 'dashboard:' . ($userId ?? 'unknown'), 'admin', [
            'metode_bayar' => $metodeBayar,
            'nominal' => (int) $booking['harga_layanan'],
        ]);
    }

    public function markWaSent(int $bookingId, ?int $userId): void
    {
        db_connect()->table('bookings')->where('id', $bookingId)->update(['wa_sent' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->logEvent($bookingId, 'wa_sent', 'dashboard:' . ($userId ?? 'unknown'), 'admin', []);
    }

    public function require(int $bookingId): array
    {
        $row = db_connect()->table('bookings')->where('id', $bookingId)->get()->getRowArray();
        if (! $row) {
            throw new RuntimeException('Booking tidak ditemukan.');
        }
        return $row;
    }

    public function normalizePhone(string $input): string
    {
        $clean = preg_replace('/\D+/', '', $input);
        if ($clean === '') return '';
        if (str_starts_with($clean, '0')) return '62' . substr($clean, 1);
        if (str_starts_with($clean, '62')) return $clean;
        if (str_starts_with($clean, '8')) return '62' . $clean;
        return $clean;
    }

    private function generateKodeBooking(): string
    {
        $date = date('Ymd');
        $count = db_connect()->table('bookings')->like('kode_booking', "BK-{$date}-", 'after')->countAllResults();
        $seq = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
        return "BK-{$date}-{$seq}";
    }

    private function transitionPending(int $bookingId, string $newStatus, ?int $userId, ?string $reason): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->require($bookingId);
        if ($booking['status'] !== 'pending_verification') {
            $db->transRollback();
            throw new RuntimeException('Booking ini sudah diproses.');
        }
        $now = date('Y-m-d H:i:s');
        $verifiedVia = 'dashboard:' . ($userId ?? 'unknown');
        $fields = [
            'status' => $newStatus,
            'verified_via' => $verifiedVia,
            'verified_at' => $now,
            'updated_at' => $now,
        ];
        if ($newStatus === 'rejected') {
            $fields['rejection_reason'] = $reason;
        }
        $db->table('bookings')->where('id', $bookingId)->where('status', 'pending_verification')->update($fields);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking ini sudah diproses.');
        }
        if ($newStatus === 'rejected') {
            $db->table('booking_slots')->where('booking_id', $bookingId)->delete();
        }
        $db->transCommit();

        $this->logEvent($bookingId, $newStatus === 'accepted' ? 'verified' : 'rejected', $verifiedVia, 'admin', [
            'reason' => $reason,
        ]);
    }

    public function logEvent(int $bookingId, string $eventType, string $actor, string $actorRole, array $payload = [], ?string $notes = null): void
    {
        try {
            db_connect()->table('booking_logs')->insert([
                'booking_id' => $bookingId,
                'event_type' => $eventType,
                'actor' => $actor,
                'actor_role' => $actorRole,
                'payload' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'booking_logs insert gagal #' . $bookingId . ': ' . $e->getMessage());
        }
    }
}
