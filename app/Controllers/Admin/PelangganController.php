<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

/**
 * Admin-side customer-account management. Two write operations only:
 *  - updateNama: rename a customer account.
 *  - resetPassword: set a new password hash (this is the only reset path
 *    — the public /lupa-password page has no self-reset).
 *
 * nomor_hp is intentionally NOT updatable: it is the login identifier and
 * the unique key. Any attempt to POST it is ignored server-side.
 */
class PelangganController extends BaseController
{
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        $db = db_connect();
        $builder = $db->table('users u')
            ->select('u.id, u.nama, u.nomor_hp, u.is_active, u.created_at, COUNT(b.id) AS total_booking, MAX(b.tanggal) AS terakhir')
            ->join('bookings b', 'b.user_id = u.id', 'left')
            ->where('u.role', 'pelanggan')
            ->groupBy('u.id')
            ->orderBy('u.nama');

        if ($q !== '') {
            $builder->groupStart()
                ->like('u.nama', $q)
                ->orLike('u.nomor_hp', $q)
                ->groupEnd();
        }

        return view('admin/pelanggan/index', [
            'rows' => $builder->limit(200)->get()->getResultArray(),
            'q' => $q,
        ]);
    }

    public function updateNama(int $id)
    {
        $model = new UserModel();
        $row = $model->where('role', 'pelanggan')->find($id);
        if (! $row) {
            return redirect()->to('/admin/pelanggan')->with('error', 'Pelanggan tidak ditemukan.');
        }
        if (! $this->validate(['nama' => 'required|min_length[3]|max_length[100]'])) {
            return redirect()->to('/admin/pelanggan')->with('error', implode(' ', $this->validator->getErrors()));
        }
        // Whitelist — nomor_hp explicitly excluded so a forged POST can't change it.
        $model->update($id, ['nama' => trim((string) $this->request->getPost('nama'))]);
        return redirect()->to('/admin/pelanggan')->with('success', 'Nama pelanggan diperbarui.');
    }

    public function resetPassword(int $id)
    {
        $model = new UserModel();
        $row = $model->where('role', 'pelanggan')->find($id);
        if (! $row) {
            return redirect()->to('/admin/pelanggan')->with('error', 'Pelanggan tidak ditemukan.');
        }
        if (! $this->validate(['new_password' => 'required|min_length[8]'])) {
            return redirect()->to('/admin/pelanggan')->with('error', implode(' ', $this->validator->getErrors()));
        }
        $model->update($id, [
            'password_hash' => password_hash((string) $this->request->getPost('new_password'), PASSWORD_BCRYPT),
        ]);
        return redirect()->to('/admin/pelanggan')->with('success', 'Password pelanggan ' . esc($row['nama']) . ' direset.');
    }
}
