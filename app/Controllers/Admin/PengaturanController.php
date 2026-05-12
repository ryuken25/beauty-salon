<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\UserModel;
use App\Services\TelegramService;

class PengaturanController extends BaseController
{
    private array $editableKeys = [
        'nama_salon', 'alamat_salon', 'telp_salon', 'nomor_hp_owner',
        'jam_buka', 'jam_tutup', 'range_hari_booking',
        'telegram_bot_token', 'telegram_allowed_chat_ids',
        'template_wa_diterima', 'template_wa_ditolak', 'template_wa_reminder', 'template_wa_selesai',
    ];

    public function index()
    {
        $set = new SettingModel();
        if ($this->request->getMethod() === 'POST') {
            foreach ($this->editableKeys as $k) {
                $v = (string) $this->request->getPost($k);
                if (in_array($k, ['jam_buka', 'jam_tutup'], true) && $v !== '' && ! preg_match('/^\d{1,2}:\d{2}$/', $v)) continue;
                $set->setValue($k, $v);
            }
            return redirect()->to('/admin/pengaturan')->with('success', 'Pengaturan disimpan.');
        }
        $users = (new UserModel())->orderBy('role')->orderBy('nama')->find();
        return view('admin/pengaturan/index', ['s' => $set->all(), 'users' => $users]);
    }

    public function telegramTest()
    {
        $resp = (new TelegramService())->sendTestMessage();
        $msg = $resp['ok'] ? 'Test message terkirim.' : ('Gagal: ' . ($resp['reason'] ?? 'Cek log.'));
        return redirect()->to('/admin/pengaturan')->with($resp['ok'] ? 'success' : 'error', $msg);
    }

    public function gantiPassword()
    {
        $userId = (int) session('user_id');
        $current = (string) $this->request->getPost('current_password');
        $new = (string) $this->request->getPost('new_password');
        if (strlen($new) < 8) return redirect()->back()->with('error', 'Password baru minimal 8 karakter.');
        $user = (new UserModel())->find($userId);
        if (! $user || ! password_verify($current, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password lama salah.');
        }
        (new UserModel())->update($userId, ['password_hash' => password_hash($new, PASSWORD_BCRYPT)]);
        return redirect()->to('/admin/pengaturan')->with('success', 'Password diperbarui.');
    }
}
