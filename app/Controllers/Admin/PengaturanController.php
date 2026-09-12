<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\UserModel;

class PengaturanController extends BaseController
{
    private array $editableKeys = [
        'nama_salon', 'alamat_salon', 'telp_salon', 'nomor_hp_owner', 'wa_admin',
        'jam_buka', 'jam_tutup', 'range_hari_booking',
        'info_pembayaran_dp',
        'template_wa_diterima', 'template_wa_ditolak', 'template_wa_reminder', 'template_wa_selesai',
    ];

    public function index()
    {
        $set = new SettingModel();
        if ($this->request->getMethod() === 'POST') {
            // Aturan ditulis sebagai array, bukan string ber-pipe, karena regex
            // nomor WA memuat karakter "|" yang akan memecah string aturan.
            if ($this->request->getPost('wa_admin') !== null
                && ! $this->validateData(
                    ['wa_admin' => (string) $this->request->getPost('wa_admin')],
                    ['wa_admin' => ['permit_empty', 'regex_match[/^(\+?62|0)8[0-9]{7,13}$/]']]
                )
            ) {
                return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp admin tidak valid. Contoh: 081338109102 atau 6281338109102.');
            }

            foreach ($this->editableKeys as $k) {
                $raw = $this->request->getPost($k);
                // Halaman ini punya beberapa form terpisah per tab. Key yang tidak
                // ikut terkirim jangan ditimpa string kosong.
                if ($raw === null) continue;
                $v = (string) $raw;
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
