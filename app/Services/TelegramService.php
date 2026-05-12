<?php

namespace App\Services;

use RuntimeException;

class TelegramService
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
        $this->ensureConfigured();
    }

    public function sendBookingNotification(int $bookingId): void
    {
        $booking = $this->bookingDetail($bookingId);
        if (! $booking || $booking['status'] !== 'pending_verification') {
            return;
        }
        $chatIds = $this->allowedChatIds();
        if (! env('TELEGRAM_BOT_TOKEN') || ! $chatIds) {
            $this->log($bookingId, 'telegram_booking_new', '', 'Telegram belum dikonfigurasi.', 'pending');
            return;
        }
        $firstMessage = null;
        foreach ($chatIds as $chatId) {
            $keyboard = ['inline_keyboard' => [[
                ['text' => '✅ Verifikasi', 'callback_data' => 'acc:' . $bookingId . ':' . $this->token($bookingId, 'accept')],
                ['text' => '❌ Tolak', 'callback_data' => 'rej:' . $bookingId . ':' . $this->token($bookingId, 'reject')],
            ], [
                ['text' => '🔗 Lihat di Web', 'url' => site_url('admin/booking/' . $bookingId)],
            ]]];
            $response = $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $this->formatBooking($booking), 'reply_markup' => json_encode($keyboard)]);
            $ok = ($response['ok'] ?? false) === true;
            $this->log($bookingId, 'telegram_booking_new', $chatId, 'Notifikasi booking baru Telegram', $ok ? 'success' : 'failed');
            $this->logBookingAudit($bookingId, 'telegram_sent', 'system', 'system', ['chat_id' => $chatId, 'status' => $ok ? 'success' : 'failed']);
            if ($ok && $firstMessage === null && isset($response['result']['message_id'])) {
                $firstMessage = ['chat_id' => (string) $chatId, 'message_id' => (int) $response['result']['message_id']];
            }
        }
        if ($firstMessage) {
            $this->db->table('bookings')->where('id', $bookingId)->update([
                'telegram_message_chat_id' => $firstMessage['chat_id'],
                'telegram_message_id' => $firstMessage['message_id'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function sendStatusNotification(int $bookingId, string $eventLabel): void
    {
        $booking = $this->bookingDetail($bookingId);
        $chatIds = $this->allowedChatIds();
        if (! env('TELEGRAM_BOT_TOKEN') || ! $chatIds || ! $booking) {
            return;
        }
        $text = "Update Booking SW Beauty Salon\nStatus: {$eventLabel}\nKode Booking: {$booking['booking_code']}\nPelanggan: {$booking['customer_name']}\nLayanan: {$booking['service_name']}\nStylist: {$booking['stylist_name']}\nTanggal: {$booking['booking_date']}\nJam: " . substr($booking['start_time'], 0, 5) . '-' . substr($booking['end_time'], 0, 5);
        foreach ($chatIds as $chatId) {
            $response = $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $text]);
            $this->log($bookingId, 'telegram_status_' . strtolower($eventLabel), $chatId, 'Notifikasi status booking Telegram', ($response['ok'] ?? false) ? 'success' : 'failed');
        }
    }

    public function onBookingVerified(int $bookingId, string $eventType, string $via, ?int $userId, ?string $telegramChatId, ?string $reason = null): void
    {
        $booking = $this->bookingDetail($bookingId);
        if (! $booking) {
            return;
        }
        $statusLabel = $eventType === 'verified' ? '✅ Sudah diverifikasi' : '❌ Ditolak';
        $sourceLabel = $via === 'telegram' ? 'via Telegram' : 'via Dashboard';
        $actorLabel = $via === 'telegram'
            ? ('chat ' . ($telegramChatId ?? '-'))
            : ($this->userLabel($userId));
        $timestamp = date('d/m/Y H:i');
        $footer = "\n\n{$statusLabel} {$sourceLabel} oleh {$actorLabel} pada {$timestamp}";
        if ($eventType === 'rejected' && $reason) {
            $footer .= "\nAlasan: {$reason}";
        }
        $newText = $this->formatBooking($booking) . $footer;

        if (! empty($booking['telegram_message_id']) && ! empty($booking['telegram_message_chat_id'])) {
            $this->api('editMessageText', [
                'chat_id' => $booking['telegram_message_chat_id'],
                'message_id' => (int) $booking['telegram_message_id'],
                'text' => $newText,
            ]);
        }
        if ($via === 'dashboard') {
            $broadcast = "{$statusLabel} {$sourceLabel}\nKode Booking: {$booking['booking_code']}\nOleh: {$actorLabel}\nWaktu: {$timestamp}";
            foreach ($this->allowedChatIds() as $chatId) {
                if (! empty($booking['telegram_message_chat_id']) && (string) $chatId === (string) $booking['telegram_message_chat_id']) {
                    continue;
                }
                $response = $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $broadcast]);
                $this->log($bookingId, 'telegram_dashboard_sync', $chatId, $broadcast, ($response['ok'] ?? false) ? 'success' : 'failed');
            }
        }
    }

    public function handleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }
        if (! isset($update['message'])) {
            return;
        }
        $chatId = (string) $update['message']['chat']['id'];
        $text = trim($update['message']['text'] ?? '');
        if ($text === '/start') {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "Chat ID Anda: {$chatId}\nMasukkan ID ini ke TELEGRAM_ALLOWED_CHAT_IDS atau pengaturan sistem. Gunakan /help untuk bantuan."]);
        } elseif ($text === '/pending') {
            if (! in_array($chatId, $this->allowedChatIds(), true)) { $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Chat ID tidak diizinkan.']); return; }
            $rows = $this->pendingBookings();
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $rows ? 'Daftar booking pending:' : 'Tidak ada booking pending.']);
            foreach ($rows as $row) {
                $this->sendBookingNotification((int) $row['id']);
            }
        } elseif ($text === '/today') {
            if (! in_array($chatId, $this->allowedChatIds(), true)) { $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Chat ID tidak diizinkan.']); return; }
            $rows = $this->db->table('bookings b')->select('b.booking_code,b.start_time,b.end_time,b.status,c.name customer_name,s.name service_name,st.name stylist_name')->join('customers c','c.id=b.customer_id')->join('services s','s.id=b.service_id')->join('stylists st','st.id=b.stylist_id')->where('b.booking_date', date('Y-m-d'))->whereIn('b.status', ['pending_verification','accepted','completed'])->orderBy('b.start_time')->get()->getResultArray();
            $msg = "Jadwal hari ini:\n";
            foreach ($rows as $r) { $msg .= "{$r['booking_code']} {$r['start_time']}-{$r['end_time']} {$r['customer_name']} {$r['service_name']} ({$r['status']})\n"; }
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $rows ? $msg : 'Belum ada jadwal hari ini.']);
        } else {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "/help\n/start menampilkan chat ID\n/pending booking menunggu verifikasi\n/today jadwal hari ini"]);
        }
    }

    public function handleCallback(array $callback): void
    {
        $chatId = (string) $callback['message']['chat']['id'];
        if (! in_array($chatId, $this->allowedChatIds(), true)) {
            $this->answer($callback['id'], 'Chat ID tidak diizinkan.');
            return;
        }
        $parts = explode(':', $callback['data'] ?? '');
        if (count($parts) !== 3) {
            $this->answer($callback['id'], 'Data aksi tidak valid.');
            return;
        }
        [$shortAction, $bookingId, $token] = $parts;
        if (! ctype_digit((string) $bookingId) || ! in_array($shortAction, ['acc', 'rej'], true)) {
            $this->answer($callback['id'], 'Data aksi tidak valid.');
            return;
        }
        $action = $shortAction === 'acc' ? 'accept' : 'reject';
        $tokenRow = $this->db->table('telegram_action_tokens')->where(['booking_id' => $bookingId, 'action' => $action, 'token' => $token, 'used_at' => null])->where('expires_at >=', date('Y-m-d H:i:s'))->get()->getRowArray();
        if (! $tokenRow) {
            $this->answer($callback['id'], 'Token aksi tidak valid atau kedaluwarsa.');
            return;
        }
        $this->db->table('bookings')->where('id', (int) $bookingId)->update([
            'telegram_message_chat_id' => $chatId,
            'telegram_message_id' => (int) ($callback['message']['message_id'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        try {
            $bookingService = new BookingService();
            $now = date('Y-m-d H:i:s');
            $this->db->table('telegram_action_tokens')->where('id', $tokenRow['id'])->where('used_at', null)->update(['used_at' => $now]);
            if ($this->db->affectedRows() !== 1) {
                $this->answer($callback['id'], 'Aksi sudah pernah diproses.');
                return;
            }
            if ($action === 'accept') {
                $bookingService->accept((int) $bookingId, null, $chatId);
            } else {
                $bookingService->reject((int) $bookingId, null, 'Ditolak melalui Telegram', $chatId);
            }
            $this->answer($callback['id'], 'Aksi berhasil diproses.');
        } catch (RuntimeException $e) {
            $this->answer($callback['id'], $e->getMessage());
        }
    }

    public function pollOnce(): void
    {
        $offset = (int) ($this->setting('telegram_last_update_id') ?: 0) + 1;
        $updates = $this->api('getUpdates', ['offset' => $offset, 'timeout' => 25]);
        foreach (($updates['result'] ?? []) as $update) {
            $this->handleUpdate($update);
            $this->saveSetting('telegram_last_update_id', (string) $update['update_id']);
        }
    }

    private function ensureConfigured(): void
    {
        $env = strtolower((string) (defined('ENVIRONMENT') ? ENVIRONMENT : (env('CI_ENVIRONMENT') ?: 'development')));
        if ($env !== 'production') {
            if (! env('TELEGRAM_BOT_TOKEN')) {
                log_message('warning', 'TELEGRAM_BOT_TOKEN belum diisi. Notifikasi Telegram non-aktif (development).');
            }
            return;
        }
        if (! env('TELEGRAM_BOT_TOKEN') || ! $this->allowedChatIds()) {
            throw new RuntimeException('Konfigurasi Telegram wajib di environment production: isi TELEGRAM_BOT_TOKEN dan TELEGRAM_ALLOWED_CHAT_IDS.');
        }
    }

    private function api(string $method, array $payload): array
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (! $token) { return []; }
        $ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 30]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            log_message('error', 'Telegram API gagal: ' . $error);
            return ['ok' => false];
        }
        curl_close($ch);
        return json_decode((string) $body, true) ?: [];
    }

    private function answer(string $id, string $text): void { $this->api('answerCallbackQuery', ['callback_query_id' => $id, 'text' => $text, 'show_alert' => false]); }
    private function token(int $bookingId, string $action): string
    {
        $token = bin2hex(random_bytes(5));
        $this->db->table('telegram_action_tokens')->insert(['booking_id' => $bookingId, 'action' => $action, 'token' => $token, 'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days')), 'created_at' => date('Y-m-d H:i:s')]);
        return $token;
    }
    private function allowedChatIds(): array { $ids = env('TELEGRAM_ALLOWED_CHAT_IDS') ?: $this->setting('telegram_allowed_chat_ids'); return array_values(array_filter(array_map('trim', explode(',', (string) $ids)))); }
    private function setting(string $key): ?string { $row = $this->db->table('app_settings')->where('setting_key', $key)->get()->getRowArray(); return $row['setting_value'] ?? null; }
    private function saveSetting(string $key, string $value): void { $row = $this->db->table('app_settings')->where('setting_key', $key)->get()->getRowArray(); $row ? $this->db->table('app_settings')->where('setting_key', $key)->update(['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]) : $this->db->table('app_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]); }
    private function log(int $bookingId, string $event, string $recipient, string $message, string $status): void { $this->db->table('notification_logs')->insert(['booking_id' => $bookingId, 'channel' => 'telegram', 'event_type' => $event, 'recipient' => $recipient, 'message' => $message, 'status' => $status, 'created_at' => date('Y-m-d H:i:s')]); }
    private function logBookingAudit(int $bookingId, string $eventType, string $actor, string $actorRole, array $payload): void
    {
        try {
            $this->db->table('booking_logs')->insert([
                'booking_id' => $bookingId,
                'event_type' => $eventType,
                'actor' => $actor,
                'actor_role' => $actorRole,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menulis booking_logs (telegram) #' . $bookingId . ': ' . $e->getMessage());
        }
    }
    private function pendingBookings(): array { return $this->db->table('bookings')->where('status', 'pending_verification')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray(); }
    private function bookingDetail(int $bookingId): array { return $this->db->table('bookings b')->select('b.*,c.name customer_name,c.phone customer_phone,s.name service_name,s.duration_minutes,st.name stylist_name')->join('customers c','c.id=b.customer_id')->join('services s','s.id=b.service_id')->join('stylists st','st.id=b.stylist_id')->where('b.id', $bookingId)->get()->getRowArray() ?? []; }
    private function formatBooking(array $b): string { return "Booking Baru - Menunggu Verifikasi\nKode Booking: {$b['booking_code']}\nPelanggan: {$b['customer_name']}\nNo. WhatsApp: {$b['customer_phone']}\nLayanan: {$b['service_name']}\nStylist: {$b['stylist_name']}\nTanggal: {$b['booking_date']}\nJam: " . substr($b['start_time'],0,5) . '-' . substr($b['end_time'],0,5) . "\nDurasi: {$b['duration_minutes']} menit\nHarga: Rp" . number_format((float)$b['service_price'],0,',','.') . "\nSumber: " . ucfirst($b['source']); }
    private function userLabel(?int $userId): string
    {
        if ($userId === null) {
            return 'Sistem';
        }
        $row = $this->db->table('users')->select('name')->where('id', $userId)->get()->getRowArray();
        return $row['name'] ?? ('user#' . $userId);
    }
}
