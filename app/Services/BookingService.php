<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\LayananModel;
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

        $phone = $this->normalizePhone((string) $data['nomor_hp_pelanggan']);
        $layanan = (new LayananModel())->where('is_active', 1)->find((int) $data['layanan_id']);
        if (! $layanan) {
            throw new RuntimeException('Layanan tidak valid.');
        }

        $tanggalBooking = $data['tanggal'];

        // Hitung harga normal dan promo berdasarkan tanggal booking
        $hargaOriginal = (int) $layanan['harga'];
        $isPromoActive = LayananModel::isPromo($layanan, $tanggalBooking);
        $promoId = $isPromoActive ? (int) $layanan['id'] : null;
        $promoName = $isPromoActive ? ($layanan['promo_deskripsi'] ?: 'Promo ' . $layanan['nama']) : null;
        $promoDiscountType = $isPromoActive ? 'percentage' : null;
        $promoDiscountValue = $isPromoActive ? (int) $layanan['promo_persen'] : 0;
        $hargaFinal = LayananModel::hargaFinal($layanan, $tanggalBooking);

        // DP rule: harga ≤ 50.000 → DP = full; harga > 50.000 → DP = 50.000.
        $sumberRaw = ($data['sumber'] ?? 'online') === 'walkin' ? 'walkin' : 'online';
        $dpAmount = min($hargaFinal, 50_000);
        $remainingPayment = max(0, $hargaFinal - $dpAmount);
        $dpProof = $data['dp_proof_path'] ?? null;
        $paymentStatus = $sumberRaw === 'walkin' ? 'dp_verified' : ($dpProof ? 'dp_uploaded' : 'unpaid');

        $email = isset($data['email_pelanggan']) ? trim((string) $data['email_pelanggan']) : '';

        $db = db_connect();
        $db->transBegin();
        try {
            $validation = $this->slotService->validateBookingSlot((int) $data['layanan_id'], $data['tanggal'], $data['slot_mulai']);
            $sumber = ($data['sumber'] ?? 'online') === 'walkin' ? 'walkin' : 'online';
            $statusInitial = $sumber === 'walkin' ? ($data['initial_status'] ?? 'accepted') : 'pending_verification';
            $kode = $this->generateKodeBooking();
            $now = date('Y-m-d H:i:s');

            $bookingRow = [
                'kode_booking' => $kode,
                'user_id' => isset($data['user_id']) && $data['user_id'] ? (int) $data['user_id'] : null,
                'nama_pelanggan' => trim((string) $data['nama_pelanggan']),
                'nomor_hp_pelanggan' => $phone,
                'email_pelanggan' => $email !== '' ? $email : null,
                'layanan_id' => (int) $layanan['id'],
                'tanggal' => $data['tanggal'],
                'slot_mulai' => $validation['slot_mulai'] . ':00',
                'slot_selesai' => $validation['slot_selesai'] . ':00',
                'jumlah_slot' => $validation['jumlah_slot'],
                'harga_layanan' => $hargaFinal,
                'dp_amount' => $dpAmount,
                'dp_proof_path' => $dpProof,
                'payment_status' => $paymentStatus,
                'original_service_price' => $hargaOriginal,
                'promo_id' => $promoId,
                'promo_name' => $promoName,
                'promo_discount_type' => $promoDiscountType,
                'promo_discount_value' => $promoDiscountValue,
                'final_service_price' => $hargaFinal,
                'remaining_payment' => $remainingPayment,
                'status' => $statusInitial,
                'sumber' => $sumber,
                'catatan' => $data['catatan'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($statusInitial === 'accepted') {
                $bookingRow['verified_via'] = $data['verified_via'] ?? 'walkin';
                $bookingRow['verified_at'] = $now;
                if ($dpAmount > 0) {
                    $bookingRow['dp_verified_at'] = $now;
                }
            }

            $db->table('bookings')->insert($bookingRow);
            $bookingId = (int) $db->insertID();

            foreach ($validation['slots_needed'] as $slot) {
                $db->table('booking_slots')->insert([
                    'booking_id' => $bookingId,
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

            // FASE 14 hook A — email notifikasi sesuai status awal booking.
            // Best-effort: kegagalan SMTP tidak boleh membatalkan booking yang sudah commit.
            if ($row && ! empty($row['email_pelanggan'])) {
                try {
                    $notif = new NotificationService();
                    if ($statusInitial === 'accepted') {
                        if ((int) $row['dp_amount'] > 0) {
                            $notif->sendDpInvoiceEmail($row);
                        } else {
                            $notif->sendBookingVerified($row);
                        }
                    } else {
                        // Status pending_verification
                        $notif->sendBookingCreated($row);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Gagal mengirim email notifikasi booking baru: ' . $e->getMessage());
                }
            }

            return $row ?: ['id' => $bookingId, 'kode_booking' => $kode];
        } catch (DatabaseException|RuntimeException $e) {
            $db->transRollback();
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'duplicate')) {
                // Bedakan constraint yang kena: tabrakan kode_booking (balapan
                // pembuatan kode) BUKAN berarti slotnya terisi.
                if (str_contains($msg, 'kode_booking')) {
                    throw new RuntimeException('Sistem sedang sibuk, silakan tekan kirim sekali lagi.');
                }
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

    public function cancel(int $bookingId, string $by, ?int $userId = null, ?string $reason = null): void
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
        $cancelledBy = $by === 'pelanggan' ? 'pelanggan' : ($by === 'pemilik' ? ('owner:' . ($userId ?? 'unknown')) : ('dashboard:' . ($userId ?? 'unknown')));
        $cleanReason = $reason !== null ? trim($reason) : null;
        $cleanReason = ($cleanReason === '') ? null : $cleanReason;
        $update = [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $cleanReason,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $db->table('bookings')->where('id', $bookingId)->whereIn('status', ['pending_verification', 'accepted'])->update($update);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new RuntimeException('Booking sudah berubah status.');
        }
        $db->table('booking_slots')->where('booking_id', $bookingId)->delete();
        $db->transCommit();

        $actorRole = $by === 'pelanggan' ? 'pelanggan' : ($by === 'pemilik' ? 'pemilik' : 'admin');
        $this->logEvent($bookingId, 'cancelled', $cancelledBy, $actorRole, [
            'reason' => $cleanReason,
        ]);
    }

    /**
     * Auto-cancel bookings still pending_verification whose start time
     * has already passed. Called by the `bookings:auto-cancel` Spark
     * command and by lazy sweep in admin landing pages.
     *
     * @return int Number of bookings cancelled.
     */
    public function autoCancelExpired(): int
    {
        $db = db_connect();
        // Pakai NOW() langsung — PHP & MySQL bisa beda timezone.
        $expired = $db->query(
            "SELECT id FROM bookings WHERE status = 'pending_verification' AND CONCAT(tanggal, ' ', slot_mulai) < NOW()"
        )->getResultArray();
        $now = date('Y-m-d H:i:s');
        $count = 0;
        foreach ($expired as $row) {
            $bookingId = (int) $row['id'];
            $db->transBegin();
            $affected = $db->table('bookings')
                ->where('id', $bookingId)
                ->where('status', 'pending_verification')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => $now,
                    'cancelled_by' => 'system',
                    'cancellation_reason' => 'Dibatalkan otomatis: tidak dikonfirmasi admin sampai jadwal lewat.',
                    'updated_at' => $now,
                ]);
            if ($db->affectedRows() !== 1) {
                $db->transRollback();
                continue;
            }
            $db->table('booking_slots')->where('booking_id', $bookingId)->delete();
            $db->transCommit();
            $this->logEvent($bookingId, 'cancelled', 'system', 'system', [
                'reason' => 'expired_no_verification',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * FASE 14C — kirim email pengingat 30 menit sebelum sesi.
     * Kandidat: status='accepted' DAN email_pelanggan ada DAN
     * email_reminder_sent_at NULL DAN slot_mulai ada dalam (now, now+30min].
     *
     * Flag email_reminder_sent_at di-set walaupun NotificationService
     * disabled atau alamat kosong — supaya satu booking tidak terus
     * ikut polling setiap 5 menit selama 30 menit ke depan.
     *
     * @return int Jumlah baris yang sudah ditangani (terkirim atau di-skip).
     */
    public function sendDueReminders(): int
    {
        $db = db_connect();
        // Pakai NOW() langsung di SQL — PHP & MySQL bisa beda timezone
        // (PHP default UTC vs MySQL server lokal); kedua sisi window harus
        // datang dari clock yang sama.
        $candidates = $db->query(
            "SELECT b.id FROM bookings b
             WHERE b.status = 'accepted'
               AND b.email_pelanggan IS NOT NULL AND b.email_pelanggan <> ''
               AND b.email_reminder_sent_at IS NULL
               AND CONCAT(b.tanggal, ' ', b.slot_mulai) > NOW()
               AND CONCAT(b.tanggal, ' ', b.slot_mulai) <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)"
        )->getResultArray();
        if (empty($candidates)) return 0;

        $notif = new NotificationService();
        $model = new BookingModel();
        $count = 0;
        foreach ($candidates as $row) {
            $bookingId = (int) $row['id'];
            $detail = $model->detail($bookingId);
            if (! $detail) continue;
            try {
                $notif->sendBookingReminder($detail);
            } catch (\Throwable $e) {
                log_message('error', 'sendBookingReminder gagal: ' . $e->getMessage());
            }
            // Set flag apa pun hasilnya — supaya tidak terus diproses.
            $model->update($bookingId, ['email_reminder_sent_at' => date('Y-m-d H:i:s')]);
            $count++;
        }
        return $count;
    }

    public function complete(
        int $bookingId,
        ?int $userId,
        string $metodeBayar = 'cash',
        ?string $catatan = null,
        int $additionalPrice = 0,
        bool $manualMode = false,
        int $manualNominal = 0
    ): void {
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

        $dpTerverifikasi = ($booking['payment_status'] === 'dp_verified') ? (int) $booking['dp_amount'] : 0;
        if ($manualMode) {
            $totalLayananFinal = max(0, $manualNominal);
            $biayaTambahan = 0;
        } else {
            $totalLayananFinal = (int) ($booking['final_service_price'] ?: $booking['harga_layanan']);
            $biayaTambahan = max(0, $additionalPrice);
        }
        $subtotal = $totalLayananFinal + $biayaTambahan;
        $sisaPembayaran = max(0, $subtotal - $dpTerverifikasi);

        $existing = $db->table('transaksi')->where('booking_id', $bookingId)->countAllResults();
        if ($existing === 0) {
            $db->table('transaksi')->insert([
                'booking_id' => $bookingId,
                'nominal' => $subtotal,
                'base_price' => $totalLayananFinal,
                'additional_price' => $biayaTambahan,
                'dp_paid' => $dpTerverifikasi,
                'sisa_bayar' => $sisaPembayaran,
                'metode_bayar' => $metodeBayar,
                'tanggal_transaksi' => $now,
                'catatan' => $catatan,
                'created_at' => $now,
            ]);
        }

        $db->transCommit();

        $this->logEvent($bookingId, 'completed', 'dashboard:' . ($userId ?? 'unknown'), 'admin', [
            'manual' => $manualMode,
            'metode_bayar' => $metodeBayar,
            'base_price' => $totalLayananFinal,
            'additional_price' => $biayaTambahan,
            'nominal' => $subtotal,
        ]);

        // Kirim email invoice final (best-effort)
        $row = (new BookingModel())->detail($bookingId);
        $transaksi = $db->table('transaksi')->where('booking_id', $bookingId)->get()->getRowArray();
        if ($row && $transaksi && ! empty($row['email_pelanggan'])) {
            try {
                (new NotificationService())->sendFinalInvoiceEmail($row, $transaksi);
            } catch (\Throwable $e) {
                log_message('error', 'sendFinalInvoiceEmail gagal: ' . $e->getMessage());
            }
        }
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
        // Format: SW-YYYYMMDD-NNN. Sequence = MAX(suffix)+1, BUKAN COUNT+1:
        // seeder menomori kode per tanggal booking (101, 102, ...) sehingga
        // urutan harian punya celah — COUNT+1 menabrak kode yang sudah ada
        // (UNIQUE kode_booking) dan salah dilaporkan "Slot sudah terisi"
        // padahal slotnya kosong.
        $date = date('Ymd');
        $row = db_connect()->table('bookings')
            ->select("MAX(CAST(SUBSTRING_INDEX(kode_booking, '-', -1) AS UNSIGNED)) AS mx", false)
            ->groupStart()->like('kode_booking', "SW-{$date}-", 'after')->orLike('kode_booking', "BK-{$date}-", 'after')->groupEnd()
            ->get()->getRowArray();
        $seq = str_pad((string) (((int) ($row['mx'] ?? 0)) + 1), 3, '0', STR_PAD_LEFT);
        return "SW-{$date}-{$seq}";
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

        if ($newStatus === 'accepted') {
            // Check if DP proof is missing for online booking that requires DP
            if ($booking['sumber'] === 'online' && (int) $booking['dp_amount'] > 0) {
                if (empty($booking['dp_proof_path'])) {
                    $db->transRollback();
                    throw new RuntimeException('Verifikasi ditolak. Bukti pembayaran DP belum diunggah.');
                }
            }

            // Auto-verify DP
            if ((int) $booking['dp_amount'] > 0) {
                $fields['payment_status'] = 'dp_verified';
                $fields['dp_verified_at'] = $now;
            }
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

        if ($newStatus === 'accepted') {
            $row = (new BookingModel())->detail($bookingId);
            if ($row && ! empty($row['email_pelanggan'])) {
                try {
                    if ((int) $row['dp_amount'] > 0) {
                        (new NotificationService())->sendDpInvoiceEmail($row);
                    } else {
                        (new NotificationService())->sendBookingVerified($row);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Gagal mengirim email verifikasi/DP invoice: ' . $e->getMessage());
                }
            }
        }
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
