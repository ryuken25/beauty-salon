<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\SettingModel;
use App\Models\UserModel;
use RuntimeException;

class TelegramService
{
    private $db;
    private string $token;
    private array $allowedChatIds;

    public function __construct()
    {
        $this->db = db_connect();
        $set = new SettingModel();
        $this->token = (string) ($set->getValue('telegram_bot_token', '') ?: env('TELEGRAM_BOT_TOKEN') ?: '');
        $ids = (string) ($set->getValue('telegram_allowed_chat_ids', '') ?: env('TELEGRAM_ALLOWED_CHAT_IDS') ?: '');
        $this->allowedChatIds = array_values(array_filter(array_map('trim', explode(',', $ids))));
    }

    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->allowedChatIds !== [];
    }

    public function sendBookingNotification(int $bookingId): void
    {
        if (! $this->isConfigured()) {
            log_message('warning', 'Telegram belum dikonfigurasi (token / chat ID kosong).');
            return;
        }
        $booking = (new BookingModel())->detail($bookingId);
        if (! $booking || $booking['status'] !== 'pending_verification') return;

        $firstMessage = null;
        foreach ($this->allowedChatIds as $chatId) {
            $keyboard = ['inline_keyboard' => [[
                ['text' => '✅ Verifikasi', 'callback_data' => 'verify:' . $bookingId . ':' . $this->issueToken($bookingId, 'verify')],
                ['text' => '❌ Tolak', 'callback_data' => 'reject:' . $bookingId . ':' . $this->issueToken($bookingId, 'reject')],
            ], [
                ['text' => '🔗 Lihat di Dashboard', 'url' => site_url('admin/booking/' . $bookingId)],
            ]]];
            $resp = $this->api('sendMessage', [
                'chat_id' => $chatId,
                'text' => $this->formatBooking($booking, '🔔 BOOKING BARU'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard),
            ]);
            $ok = ($resp['ok'] ?? false) === true;
            if ($ok && $firstMessage === null && isset($resp['result']['message_id'])) {
                $firstMessage = ['chat_id' => (string) $chatId, 'message_id' => (int) $resp['result']['message_id']];
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

    public function onBookingVerified(int $bookingId, string $eventStatus, string $via, ?int $userId, ?string $telegramChatId, ?string $reason = null): void
    {
        if (! $this->isConfigured()) return;
        $booking = (new BookingModel())->detail($bookingId);
        if (! $booking) return;

        $statusLabel = $eventStatus === 'accepted' ? '✅ Sudah diverifikasi' : '❌ Ditolak';
        $sourceLabel = $via === 'telegram' ? 'via Telegram' : 'via Dashboard';
        $actorLabel = $via === 'telegram'
            ? ('chat ' . ($telegramChatId ?? '-'))
            : $this->userLabel($userId);
        $ts = date('d/m/Y H:i');
        $newText = $this->formatBooking($booking, $eventStatus === 'accepted' ? '✅ BOOKING DIVERIFIKASI' : '❌ BOOKING DITOLAK')
            . "\n\n<i>{$statusLabel} {$sourceLabel} oleh {$actorLabel} pada {$ts}</i>"
            . ($reason ? "\n<i>Alasan: " . htmlspecialchars($reason, ENT_QUOTES) . '</i>' : '');

        if (! empty($booking['telegram_message_id']) && ! empty($booking['telegram_message_chat_id'])) {
            $this->api('editMessageText', [
                'chat_id' => $booking['telegram_message_chat_id'],
                'message_id' => (int) $booking['telegram_message_id'],
                'text' => $newText,
                'parse_mode' => 'HTML',
            ]);
        }
        if ($via === 'dashboard') {
            $broadcast = "{$statusLabel} {$sourceLabel}\nKode: <code>{$booking['kode_booking']}</code>\nOleh: {$actorLabel}\nWaktu: {$ts}";
            foreach ($this->allowedChatIds as $chatId) {
                if (! empty($booking['telegram_message_chat_id']) && (string) $chatId === (string) $booking['telegram_message_chat_id']) continue;
                $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $broadcast, 'parse_mode' => 'HTML']);
            }
        }
    }

    public function onBookingCancelled(int $bookingId, string $cancelledBy): void
    {
        if (! $this->isConfigured()) return;
        $booking = (new BookingModel())->detail($bookingId);
        if (! $booking) return;
        $text = "🚫 BOOKING DIBATALKAN\nKode: <code>{$booking['kode_booking']}</code>\nOleh: " . htmlspecialchars($cancelledBy, ENT_QUOTES) . "\nWaktu: " . date('d/m/Y H:i');
        foreach ($this->allowedChatIds as $chatId) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML']);
        }
    }

    public function handleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }
        if (! isset($update['message'])) return;
        $chatId = (string) $update['message']['chat']['id'];
        $text = trim((string) ($update['message']['text'] ?? ''));
        if ($text === '/start') {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "Chat ID Anda: {$chatId}\nMasukkan ID ini di Pengaturan > Telegram untuk mengaktifkan notifikasi."]);
        } elseif ($text === '/pending') {
            if (! in_array($chatId, $this->allowedChatIds, true)) {
                $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Chat ID tidak diizinkan.']);
                return;
            }
            $rows = $this->db->table('bookings')->where('status', 'pending_verification')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();
            if (! $rows) {
                $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Tidak ada booking pending.']);
                return;
            }
            foreach ($rows as $r) $this->sendBookingNotification((int) $r['id']);
        } elseif ($text === '/today') {
            if (! in_array($chatId, $this->allowedChatIds, true)) return;
            $rows = $this->db->table('bookings b')->select('b.kode_booking, b.slot_mulai, b.slot_selesai, b.status, b.nama_pelanggan, l.nama AS nama_layanan')->join('layanan l', 'l.id = b.layanan_id')->where('b.tanggal', date('Y-m-d'))->whereIn('b.status', ['accepted', 'pending_verification', 'completed'])->orderBy('b.slot_mulai')->get()->getResultArray();
            $msg = "Jadwal hari ini:\n";
            foreach ($rows as $r) $msg .= substr($r['slot_mulai'], 0, 5) . '–' . substr($r['slot_selesai'], 0, 5) . " {$r['kode_booking']} {$r['nama_pelanggan']} ({$r['nama_layanan']}) [{$r['status']}]\n";
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $rows ? $msg : 'Belum ada jadwal hari ini.']);
        } else {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "Perintah:\n/start chat ID\n/pending booking pending\n/today jadwal hari ini"]);
        }
    }

    public function handleCallback(array $cb): void
    {
        $chatId = (string) $cb['message']['chat']['id'];
        if (! in_array($chatId, $this->allowedChatIds, true)) {
            $this->answer($cb['id'] ?? '', 'Chat ID tidak diizinkan.');
            return;
        }
        $parts = explode(':', (string) ($cb['data'] ?? ''));
        if (count($parts) !== 3) {
            $this->answer($cb['id'] ?? '', 'Data tidak valid.');
            return;
        }
        [$action, $bookingIdStr, $token] = $parts;
        if (! ctype_digit($bookingIdStr) || ! in_array($action, ['verify', 'reject'], true)) {
            $this->answer($cb['id'] ?? '', 'Aksi tidak dikenali.');
            return;
        }
        $bookingId = (int) $bookingIdStr;
        $tokenRow = $this->db->table('telegram_action_tokens')->where(['booking_id' => $bookingId, 'action' => $action, 'token' => $token, 'used_at' => null])->where('expires_at >=', date('Y-m-d H:i:s'))->get()->getRowArray();
        if (! $tokenRow) {
            $this->answer($cb['id'] ?? '', 'Token tidak valid atau kedaluwarsa.');
            return;
        }
        $this->db->table('telegram_action_tokens')->where('id', $tokenRow['id'])->where('used_at', null)->update(['used_at' => date('Y-m-d H:i:s')]);
        if ($this->db->affectedRows() !== 1) {
            $this->answer($cb['id'] ?? '', 'Aksi sudah diproses.');
            return;
        }
        $this->db->table('bookings')->where('id', $bookingId)->update([
            'telegram_message_chat_id' => $chatId,
            'telegram_message_id' => (int) ($cb['message']['message_id'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        try {
            $svc = new BookingService();
            if ($action === 'verify') {
                $svc->verify($bookingId, 'telegram', null, $chatId);
            } else {
                $svc->reject($bookingId, 'telegram', null, $chatId, 'Ditolak via Telegram');
            }
            $this->answer($cb['id'] ?? '', 'Aksi berhasil diproses.');
        } catch (RuntimeException $e) {
            $this->answer($cb['id'] ?? '', $e->getMessage());
        }
    }

    public function pollOnce(): int
    {
        if (! $this->token) return 0;
        $set = new SettingModel();
        $offset = (int) ($set->getValue('telegram_last_update_id', '0')) + 1;
        $resp = $this->api('getUpdates', ['offset' => $offset, 'timeout' => 25]);
        $count = 0;
        foreach (($resp['result'] ?? []) as $update) {
            $this->handleUpdate($update);
            $set->setValue('telegram_last_update_id', (string) $update['update_id']);
            $count++;
        }
        return $count;
    }

    public function sendTestMessage(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'reason' => 'Token atau chat ID belum diatur.'];
        }
        $ok = true;
        foreach ($this->allowedChatIds as $chatId) {
            $resp = $this->api('sendMessage', ['chat_id' => $chatId, 'text' => '✅ Test SW Beauty Salon — Telegram bot terkoneksi.']);
            $ok = $ok && (($resp['ok'] ?? false) === true);
        }
        return ['ok' => $ok];
    }

    private function api(string $method, array $payload): array
    {
        if (! $this->token) return [];
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 10]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            log_message('error', 'Telegram API gagal: ' . $err);
            return ['ok' => false];
        }
        curl_close($ch);
        return json_decode((string) $body, true) ?: [];
    }

    private function answer(string $id, string $text): void
    {
        if ($id === '') return;
        $this->api('answerCallbackQuery', ['callback_query_id' => $id, 'text' => $text]);
    }

    private function issueToken(int $bookingId, string $action): string
    {
        $token = bin2hex(random_bytes(5));
        $this->db->table('telegram_action_tokens')->insert([
            'booking_id' => $bookingId, 'action' => $action, 'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    private function formatBooking(array $b, string $title): string
    {
        return "<b>{$title}</b>\n\n"
            . "Kode: <code>{$b['kode_booking']}</code>\n"
            . "Nama: " . htmlspecialchars($b['nama_pelanggan'], ENT_QUOTES) . "\n"
            . "HP: <code>{$b['nomor_hp_pelanggan']}</code>\n"
            . "Layanan: " . htmlspecialchars($b['nama_layanan'], ENT_QUOTES) . "\n"
            . "Tanggal: " . date('d M Y', strtotime($b['tanggal'])) . "\n"
            . "Jam: " . substr($b['slot_mulai'], 0, 5) . ' – ' . substr($b['slot_selesai'], 0, 5) . "\n"
            . "Stylist: " . htmlspecialchars($b['nama_stylist'], ENT_QUOTES) . "\n"
            . "Harga: Rp " . number_format((int) $b['harga_layanan'], 0, ',', '.');
    }

    private function userLabel(?int $userId): string
    {
        if ($userId === null) return 'Sistem';
        $row = (new UserModel())->select('nama')->find($userId);
        return $row['nama'] ?? ('user#' . $userId);
    }
}
