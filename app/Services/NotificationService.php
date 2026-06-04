<?php

namespace App\Services;

use App\Models\BookingLogModel;
use Config\Email as EmailConfig;
use Throwable;

/**
 * Email notifikasi otomatis (3 momen) via SMTP yang dikonfigurasi di .env
 * dengan prefix `email.` (mengikuti Config\Email).
 *
 * Best-effort policy:
 *  - Tidak melempar exception. Pengiriman dibungkus try/catch (\Throwable),
 *    log_message() saja kalau gagal. Booking/verify TIDAK boleh dibatalkan
 *    hanya karena SMTP error.
 *  - Skip diam-diam kalau service belum dikonfigurasi (SMTPHost / SMTPPass
 *    kosong) atau alamat tujuan kosong — supaya salon yang belum punya
 *    Gmail App Password tetap bisa pakai aplikasi.
 *  - Setiap send() yang sukses dicatat ke booking_logs (event_type =
 *    email_created / email_verified / email_reminder).
 *
 * WhatsApp tetap manual via WhatsAppTemplateService — jangan tambah otomasi
 * WA di sini.
 */
class NotificationService
{
    private bool $enabled;
    private EmailConfig $config;

    public function __construct()
    {
        $this->config = config(EmailConfig::class);
        $this->enabled = ($this->config->protocol ?? '') === 'smtp'
            && ! empty($this->config->SMTPHost)
            && ! empty($this->config->SMTPPass);
    }

    public function sendBookingCreated(array $b): bool
    {
        $subject = "[SW Beauty Salon] Booking {$b['kode_booking']} menunggu verifikasi";
        $sent = $this->send((string) ($b['email_pelanggan'] ?? ''), $subject, 'emails/booking_created', ['b' => $b]);
        if ($sent) $this->logEvent((int) $b['id'], 'email_created', $b['email_pelanggan']);
        return $sent;
    }

    public function sendBookingVerified(array $b): bool
    {
        $subject = "[SW Beauty Salon] Booking {$b['kode_booking']} sudah dikonfirmasi";
        $sent = $this->send((string) ($b['email_pelanggan'] ?? ''), $subject, 'emails/booking_verified', ['b' => $b]);
        if ($sent) $this->logEvent((int) $b['id'], 'email_verified', $b['email_pelanggan']);
        return $sent;
    }

    public function sendBookingReminder(array $b): bool
    {
        $jam = substr((string) $b['slot_mulai'], 0, 5);
        $subject = "[SW Beauty Salon] Pengingat: sesi Anda {$jam} hari ini";
        $sent = $this->send((string) ($b['email_pelanggan'] ?? ''), $subject, 'emails/booking_reminder', ['b' => $b]);
        if ($sent) $this->logEvent((int) $b['id'], 'email_reminder', $b['email_pelanggan']);
        return $sent;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private function send(string $to, string $subject, string $view, array $data): bool
    {
        if (! $this->enabled) {
            log_message('info', 'Email skip (SMTP disabled): ' . $subject);
            return false;
        }
        if ($to === '') {
            log_message('info', 'Email skip (no recipient): ' . $subject);
            return false;
        }
        try {
            $email = service('email', null, false);
            $email->clear(true);
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage(view($view, $data));
            $email->setMailType('html');
            return $email->send(false);
        } catch (Throwable $e) {
            log_message('error', 'Email gagal kirim: ' . $e->getMessage());
            return false;
        }
    }

    private function logEvent(int $bookingId, string $type, ?string $to): void
    {
        try {
            (new BookingLogModel())->insert([
                'booking_id' => $bookingId,
                'event_type' => $type,
                'actor' => 'system',
                'actor_role' => 'system',
                'payload' => json_encode(['to' => $to]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Booking log gagal: ' . $e->getMessage());
        }
    }
}
