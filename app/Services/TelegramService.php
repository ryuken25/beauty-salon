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
        $isAdmin = in_array($chatId, $this->allowedChatIds, true);
        $isSuper = $chatId === $this->superAdminChatId();

        if ($text === '/start' || $text === '/id') {
            if ($isSuper) {
                $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "👑 <b>Super Admin</b>\nID: <code>{$chatId}</code>\n\nGunakan /help untuk daftar perintah."]);
            } elseif ($isAdmin) {
                $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "✅ <b>Admin SW Beauty Salon</b>\nID: <code>{$chatId}</code>\n\nGunakan /help untuk daftar perintah."]);
            } else {
                $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "ID: <code>{$chatId}</code>\nkamu bukan admin"]);
            }
            return;
        }

        if (str_starts_with($text, '/admin')) {
            $this->cmdAdmin($chatId, $text, $isSuper);
            return;
        }

        if (! $isAdmin) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "ID: <code>{$chatId}</code>\nkamu bukan admin"]);
            return;
        }

        if ($text === '/help') {
            $this->cmdHelp($chatId, $isSuper);
        } elseif ($text === '/pending') {
            $this->cmdPending($chatId);
        } elseif ($text === '/today') {
            $this->cmdToday($chatId);
        } elseif ($text === '/list' || str_starts_with($text, '/list')) {
            $this->cmdList($chatId, trim(substr($text, 5)));
        } elseif (str_starts_with($text, '/new')) {
            $this->cmdNew($chatId, trim(substr($text, 4)));
        } elseif ($text === '/stats') {
            $this->cmdStats($chatId);
        } elseif (str_starts_with($text, '/cancel')) {
            $this->cmdCancel($chatId, trim(substr($text, 7)));
        } else {
            $this->cmdHelp($chatId, $isSuper);
        }
    }

    private function superAdminChatId(): string
    {
        $row = $this->db->table('settings')->where('key_name', 'telegram_super_admin_chat_id')->get()->getRowArray();
        return (string) ($row['value'] ?? '');
    }

    private function cmdHelp(string $chatId, bool $isSuper): void
    {
        $lines = [
            '<b>Perintah Admin</b>',
            '/start atau /id — chat ID & status admin',
            '/help — daftar perintah',
            '/pending — booking menunggu verifikasi (dengan tombol)',
            '/today — jadwal hari ini',
            '/list [keyword] — daftar layanan aktif',
            '/new &lt;nominal&gt; &lt;nama paket&gt; — catat pesanan walk-in (langsung selesai + transaksi)',
            '/stats — ringkasan hari ini',
            '/cancel &lt;kode_booking&gt; — batalkan booking',
        ];
        if ($isSuper) {
            $lines[] = '';
            $lines[] = '<b>Super Admin</b>';
            $lines[] = '/admin list — daftar admin';
            $lines[] = '/admin add &lt;chat_id&gt; — tambah admin';
            $lines[] = '/admin remove &lt;chat_id&gt; — hapus admin';
        }
        $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => implode("\n", $lines)]);
    }

    private function cmdAdmin(string $chatId, string $text, bool $isSuper): void
    {
        if (! $isSuper) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Perintah /admin hanya untuk Super Admin.']);
            return;
        }
        $args = trim(substr($text, 6));
        if ($args === '' || $args === 'list') {
            $ids = $this->allowedChatIds ?: ['(kosong)'];
            $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "<b>Daftar admin:</b>\n<code>" . implode("\n", $ids) . '</code>']);
            return;
        }
        if (preg_match('/^add\s+(\d{3,})/', $args, $m)) {
            $newId = $m[1];
            if (in_array($newId, $this->allowedChatIds, true)) {
                $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Chat ID sudah terdaftar.']);
                return;
            }
            $this->allowedChatIds[] = $newId;
            $this->saveSetting('telegram_allowed_chat_ids', implode(',', $this->allowedChatIds));
            $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "✅ Admin baru ditambahkan: <code>{$newId}</code>"]);
            return;
        }
        if (preg_match('/^remove\s+(\d{3,})/', $args, $m)) {
            $rmId = $m[1];
            if ($rmId === $this->superAdminChatId()) {
                $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Tidak bisa menghapus Super Admin.']);
                return;
            }
            $this->allowedChatIds = array_values(array_filter($this->allowedChatIds, static fn($x) => $x !== $rmId));
            $this->saveSetting('telegram_allowed_chat_ids', implode(',', $this->allowedChatIds));
            $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "🗑 Admin dihapus: <code>{$rmId}</code>"]);
            return;
        }
        $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "Format:\n/admin list\n/admin add &lt;chat_id&gt;\n/admin remove &lt;chat_id&gt;"]);
    }

    private function cmdPending(string $chatId): void
    {
        $rows = $this->db->table('bookings')->where('status', 'pending_verification')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();
        if (! $rows) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Tidak ada booking pending.']);
            return;
        }
        $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Booking pending (' . count($rows) . '):']);
        foreach ($rows as $r) $this->sendBookingNotification((int) $r['id']);
    }

    private function cmdToday(string $chatId): void
    {
        $rows = $this->db->table('bookings b')->select('b.kode_booking, b.slot_mulai, b.slot_selesai, b.status, b.nama_pelanggan, l.nama AS nama_layanan')->join('layanan l', 'l.id = b.layanan_id')->where('b.tanggal', date('Y-m-d'))->whereIn('b.status', ['accepted', 'pending_verification', 'completed'])->orderBy('b.slot_mulai')->get()->getResultArray();
        if (! $rows) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Belum ada jadwal hari ini.']);
            return;
        }
        $msg = "<b>📅 Jadwal hari ini</b>\n";
        foreach ($rows as $r) {
            $st = ['pending_verification' => '⏳', 'accepted' => '✅', 'completed' => '🏁'][$r['status']] ?? '·';
            $msg .= $st . ' ' . substr($r['slot_mulai'], 0, 5) . '–' . substr($r['slot_selesai'], 0, 5) . " <code>{$r['kode_booking']}</code>\n   " . htmlspecialchars($r['nama_pelanggan'], ENT_QUOTES) . ' · ' . htmlspecialchars($r['nama_layanan'], ENT_QUOTES) . "\n";
        }
        $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => $msg]);
    }

    private function cmdList(string $chatId, string $keyword): void
    {
        $q = $this->db->table('layanan')->where('is_active', 1)->orderBy('kategori')->orderBy('nama');
        if ($keyword !== '') $q->like('nama', $keyword);
        $rows = $q->get()->getResultArray();
        if (! $rows) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Tidak ada layanan' . ($keyword ? ' cocok dengan "' . $keyword . '"' : '') . '.']);
            return;
        }
        $msg = "<b>📋 Daftar Layanan</b>\n";
        $cat = '';
        foreach ($rows as $r) {
            if ($r['kategori'] !== $cat) {
                $cat = $r['kategori'];
                $msg .= "\n<b>" . htmlspecialchars($cat, ENT_QUOTES) . "</b>\n";
            }
            $msg .= '· ' . htmlspecialchars($r['nama'], ENT_QUOTES) . ' — ' . $r['durasi_menit'] . " menit — Rp " . number_format((int) $r['harga'], 0, ',', '.') . "\n";
        }
        $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => $msg]);
    }

    private function cmdNew(string $chatId, string $args): void
    {
        if (! preg_match('/^(\d{3,})\s+(.+)$/', $args, $m)) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "Format: <code>/new &lt;nominal&gt; &lt;nama paket&gt;</code>\nContoh: <code>/new 250000 Hair Treatment</code>"]);
            return;
        }
        $nominal = (int) $m[1];
        $namaPaket = trim($m[2]);
        $layanan = $this->db->table('layanan')->where('is_active', 1)->like('nama', $namaPaket)->orderBy('id', 'ASC')->limit(1)->get()->getRowArray();
        if (! $layanan) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "Paket \"{$namaPaket}\" tidak ditemukan. /list untuk lihat semua."]);
            return;
        }
        $stylist = $this->db->table('stylists')->where('is_active', 1)->where('is_default', 1)->get()->getRowArray()
            ?: $this->db->table('stylists')->where('is_active', 1)->orderBy('id')->get()->getRowArray();
        if (! $stylist) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => 'Belum ada stylist aktif.']);
            return;
        }
        $now = new \DateTimeImmutable();
        $start = $now->modify('-' . ((int) $now->format('i') % 30) . ' minutes')->setTime((int) $now->format('H'), (int) ($now->format('i') >= 30 ? 30 : 0));
        $jumlahSlot = max(1, (int) ($layanan['durasi_menit'] / 30));
        $end = $start->modify('+' . ($jumlahSlot * 30) . ' minutes');
        $kode = 'BK-' . $now->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $nowStr = $now->format('Y-m-d H:i:s');

        $this->db->table('bookings')->insert([
            'kode_booking' => $kode,
            'nama_pelanggan' => 'Walk-in (Telegram)',
            'nomor_hp_pelanggan' => $chatId,
            'layanan_id' => (int) $layanan['id'],
            'stylist_id' => (int) $stylist['id'],
            'tanggal' => $start->format('Y-m-d'),
            'slot_mulai' => $start->format('H:i:00'),
            'slot_selesai' => $end->format('H:i:00'),
            'jumlah_slot' => $jumlahSlot,
            'harga_layanan' => $nominal,
            'status' => 'completed',
            'sumber' => 'walkin',
            'catatan' => 'Dicatat via Telegram',
            'verified_via' => 'telegram:' . $chatId,
            'verified_at' => $nowStr,
            'completed_at' => $nowStr,
            'created_at' => $nowStr,
            'updated_at' => $nowStr,
        ]);
        $bookingId = (int) $this->db->insertID();
        $this->db->table('transaksi')->insert([
            'booking_id' => $bookingId,
            'nominal' => $nominal,
            'metode_bayar' => 'cash',
            'tanggal_transaksi' => $nowStr,
            'catatan' => 'Pesanan via Telegram /new',
            'created_at' => $nowStr,
        ]);
        $this->logBookingAudit($bookingId, 'created', 'telegram:' . $chatId, 'admin', ['via' => 'telegram_new', 'nominal' => $nominal]);
        $this->logBookingAudit($bookingId, 'completed', 'telegram:' . $chatId, 'admin', ['nominal' => $nominal]);

        $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' =>
            "✅ <b>Pesanan dicatat</b>\nKode: <code>{$kode}</code>\nPaket: " . htmlspecialchars($layanan['nama'], ENT_QUOTES) . "\nNominal: Rp " . number_format($nominal, 0, ',', '.') . "\nWaktu: " . $start->format('d/m/Y H:i')]);
    }

    private function cmdStats(string $chatId): void
    {
        $today = date('Y-m-d');
        $rev = (int) ($this->db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', $today)->get()->getRow()->nominal ?? 0);
        $cnt = $this->db->table('bookings')->where('tanggal', $today)->whereNotIn('status', ['rejected', 'cancelled'])->countAllResults();
        $done = $this->db->table('bookings')->where('tanggal', $today)->where('status', 'completed')->countAllResults();
        $pending = $this->db->table('bookings')->where('status', 'pending_verification')->countAllResults();
        $text = "<b>📊 Statistik hari ini</b>\n"
            . 'Tanggal: ' . date('d/m/Y') . "\n"
            . 'Pendapatan: Rp ' . number_format($rev, 0, ',', '.') . "\n"
            . "Booking aktif: {$cnt} (selesai {$done})\n"
            . "Pending verifikasi: {$pending}";
        $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => $text]);
    }

    private function cmdCancel(string $chatId, string $kode): void
    {
        $kode = strtoupper(trim($kode));
        if ($kode === '') {
            $this->api('sendMessage', ['chat_id' => $chatId, 'parse_mode' => 'HTML', 'text' => "Format: <code>/cancel &lt;kode_booking&gt;</code>"]);
            return;
        }
        $booking = $this->db->table('bookings')->where('kode_booking', $kode)->get()->getRowArray();
        if (! $booking) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "Booking {$kode} tidak ditemukan."]);
            return;
        }
        try {
            (new BookingService())->cancel((int) $booking['id'], 'admin', null);
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => "✅ Booking {$kode} dibatalkan."]);
        } catch (RuntimeException $e) {
            $this->api('sendMessage', ['chat_id' => $chatId, 'text' => $e->getMessage()]);
        }
    }

    private function saveSetting(string $key, string $value): void
    {
        $row = $this->db->table('settings')->where('key_name', $key)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        if ($row) $this->db->table('settings')->where('key_name', $key)->update(['value' => $value, 'updated_at' => $now]);
        else $this->db->table('settings')->insert(['key_name' => $key, 'value' => $value, 'updated_at' => $now]);
    }

    private function logBookingAudit(int $bookingId, string $eventType, string $actor, string $actorRole, array $payload = []): void
    {
        $this->db->table('booking_logs')->insert([
            'booking_id' => $bookingId,
            'event_type' => $eventType,
            'actor' => $actor,
            'actor_role' => $actorRole,
            'payload' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
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
