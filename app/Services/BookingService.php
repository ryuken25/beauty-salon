<?php

namespace App\Services;

use CodeIgniter\Database\Exceptions\DatabaseException;
use RuntimeException;

class BookingService
{
    private SlotService $slotService;

    public function __construct()
    {
        $this->slotService = new SlotService();
    }

    public function createBooking(array $data): int
    {
        $db = db_connect();
        foreach (['customer_id', 'service_id', 'stylist_id', 'booking_date', 'start_time'] as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Data booking belum lengkap.');
            }
        }
        $db->transBegin();
        try {
            $validation = $this->slotService->validateSlot((int) $data['service_id'], (int) $data['stylist_id'], $data['booking_date'], $data['start_time']);
            $customer = $db->table('customers')->where('id', (int) $data['customer_id'])->get()->getRowArray();
            if (! $customer) {
                throw new RuntimeException('Data pelanggan tidak ditemukan.');
            }
            $code = 'SWB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $booking = [
                'booking_code' => $code,
                'customer_id' => (int) $data['customer_id'],
                'service_id' => (int) $data['service_id'],
                'stylist_id' => (int) $data['stylist_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $validation['start_time'],
                'end_time' => $validation['end_time'],
                'slot_count' => $validation['slot_count'],
                'service_price' => $validation['service']['price'],
                'source' => $data['source'] ?? 'online',
                'status' => 'pending_verification',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $db->table('bookings')->insert($booking);
            $bookingId = (int) $db->insertID();
            $start = strtotime($data['booking_date'] . ' ' . $validation['start_time']);
            for ($i = 0; $i < $validation['slot_count']; $i++) {
                $db->table('booking_slots')->insert([
                    'booking_id' => $bookingId,
                    'stylist_id' => (int) $data['stylist_id'],
                    'slot_date' => $data['booking_date'],
                    'slot_start' => date('H:i:00', $start + ($i * 1800)),
                    'slot_end' => date('H:i:00', $start + (($i + 1) * 1800)),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            if ($db->transStatus() === false) {
                throw new RuntimeException('Slot sudah terisi, silakan pilih waktu lain.');
            }
            $db->transCommit();
            try {
                (new TelegramService())->sendBookingNotification($bookingId);
            } catch (RuntimeException $e) {
                log_message('error', 'Gagal mengirim notifikasi Telegram booking #' . $bookingId . ': ' . $e->getMessage());
            }
            return $bookingId;
        } catch (DatabaseException|RuntimeException $e) {
            $db->transRollback();
            if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                throw new RuntimeException('Slot sudah terisi, silakan pilih waktu lain.');
            }
            throw $e;
        }
    }

    public function accept(int $bookingId, ?int $userId = null, ?string $telegramChatId = null): void
    {
        $this->changePending($bookingId, 'accepted', ['verified_by' => $userId, 'verified_by_telegram_chat_id' => $telegramChatId, 'verified_at' => date('Y-m-d H:i:s')]);
    }

    public function reject(int $bookingId, ?int $userId = null, ?string $reason = null, ?string $telegramChatId = null): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->requireBooking($bookingId);
        if ($booking['status'] !== 'pending_verification') {
            $db->transRollback();
            throw new RuntimeException('Booking ini sudah diproses.');
        }
        $db->table('bookings')->where('id', $bookingId)->where('status', 'pending_verification')->update(['status' => 'rejected', 'rejection_reason' => $reason, 'verified_by' => $userId, 'verified_by_telegram_chat_id' => $telegramChatId, 'verified_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking ini sudah diproses.');
        }
        $db->table('booking_slots')->where('booking_id', $bookingId)->delete();
        $db->transCommit();
    }

    public function cancel(int $bookingId, ?int $userId, bool $customerRule = false): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->requireBooking($bookingId);
        if (! in_array($booking['status'], ['pending_verification', 'accepted'], true)) {
            $db->transRollback();
            throw new RuntimeException('Booking tidak dapat dibatalkan karena status sudah final.');
        }
        if ($customerRule && strtotime($booking['booking_date'] . ' ' . $booking['start_time']) <= time()) {
            $db->transRollback();
            throw new RuntimeException('Booking yang sudah melewati jadwal tidak dapat dibatalkan pelanggan.');
        }
        $db->table('bookings')->where('id', $bookingId)->whereIn('status', ['pending_verification', 'accepted'])->update(['status' => 'cancelled', 'cancelled_by' => $userId, 'cancelled_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking tidak dapat dibatalkan karena status sudah berubah.');
        }
        $db->table('booking_slots')->where('booking_id', $bookingId)->delete();
        $db->transCommit();
        try {
            (new TelegramService())->sendStatusNotification($bookingId, 'BATAL');
        } catch (RuntimeException $e) {
            log_message('error', 'Gagal mengirim notifikasi Telegram pembatalan booking #' . $bookingId . ': ' . $e->getMessage());
        }
    }

    public function complete(int $bookingId, ?int $userId): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->requireBooking($bookingId);
        if ($booking['status'] !== 'accepted') {
            $db->transRollback();
            throw new RuntimeException('Hanya booking diterima yang dapat ditandai selesai.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('bookings')->where('id', $bookingId)->where('status', 'accepted')->update(['status' => 'completed', 'completed_by' => $userId, 'completed_at' => $now, 'updated_at' => $now]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking sudah berubah status dan tidak dapat ditandai selesai.');
        }
        $exists = $db->table('transactions')->where('booking_id', $bookingId)->countAllResults();
        if ($exists === 0) {
            $db->table('transactions')->insert(['booking_id' => $bookingId, 'transaction_code' => 'TRX-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))), 'amount' => $booking['service_price'], 'payment_method' => 'cash', 'transaction_date' => $now, 'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
        $db->transCommit();
    }

    private function changePending(int $bookingId, string $status, array $fields): void
    {
        $db = db_connect();
        $db->transBegin();
        $booking = $this->requireBooking($bookingId);
        if ($booking['status'] !== 'pending_verification') {
            $db->transRollback();
            throw new RuntimeException('Booking ini sudah diproses.');
        }
        $fields['status'] = $status;
        $fields['updated_at'] = date('Y-m-d H:i:s');
        $db->table('bookings')->where('id', $bookingId)->where('status', 'pending_verification')->update($fields);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking ini sudah diproses.');
        }
        $db->transCommit();
    }

    public function requireBooking(int $bookingId): array
    {
        $booking = db_connect()->table('bookings')->where('id', $bookingId)->get()->getRowArray();
        if (! $booking) {
            throw new RuntimeException('Booking tidak ditemukan.');
        }
        return $booking;
    }
}
