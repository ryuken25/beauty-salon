<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\UserModel;

class PengaturanController extends BaseController
{
    private array $editableKeys = [
        'nama_salon', 'alamat_salon', 'telp_salon', 'nomor_hp_owner',
        'jam_buka', 'jam_tutup', 'range_hari_booking',
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
