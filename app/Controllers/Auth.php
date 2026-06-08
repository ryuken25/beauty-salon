<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\UserModel;
use App\Services\BookingService;

class Auth extends BaseController
{
    private const RATE_LIMIT = 8;
    private const RATE_WINDOW_SEC = 900; // 15 menit

    // ── Login terpadu: satu halaman untuk semua role ─────────────
    // Identifier bisa email (staff/pemilik) ATAU nomor WA (pelanggan).
    // Redirect otomatis sesuai role setelah login berhasil.
    public function login()
    {
        if (session('is_logged_in')) {
            return $this->redirectByRole(session('user_role'));
        }
        if ($this->request->getMethod() === 'POST') {
            $failKey = 'login_fail_' . md5($this->request->getIPAddress());
            $fails = (int) (cache($failKey) ?? 0);
            if ($fails >= self::RATE_LIMIT) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.');
            }
            $identifier = trim((string) $this->request->getPost('identifier'));
            $password = (string) $this->request->getPost('password');
            $user = $this->findUserByIdentifier($identifier);
            if ($user && password_verify($password, $user['password_hash'])) {
                cache()->delete($failKey);
                $this->issueSession($user);
                return $this->redirectByRole($user['role']);
            }
            cache()->save($failKey, $fails + 1, self::RATE_WINDOW_SEC);
            return redirect()->back()->withInput()->with('error', 'Email/Nomor WhatsApp atau password salah.');
        }
        return view('auth/login');
    }

    // Cari user aktif berdasarkan email (kalau mengandung '@') atau nomor WA.
    private function findUserByIdentifier(string $identifier): ?array
    {
        if ($identifier === '') {
            return null;
        }
        $model = new UserModel();
        if (str_contains($identifier, '@')) {
            return $model->where('email', strtolower($identifier))
                ->where('is_active', 1)
                ->first();
        }
        $phone = (new BookingService())->normalizePhone($identifier);
        if ($phone === '') {
            return null;
        }
        return $model->where('nomor_hp', $phone)
            ->where('is_active', 1)
            ->first();
    }

    private function redirectByRole(?string $role)
    {
        if (in_array($role, ['admin', 'pemilik'], true)) {
            return redirect()->to('/admin/dashboard');
        }
        return redirect()->to('/pelanggan/dashboard');
    }

    // ── Register pelanggan ───────────────────────────────────────
    public function register()
    {
        if (session('is_logged_in') && in_array(session('user_role'), ['admin', 'pemilik'], true)) {
            return redirect()->to('/admin/dashboard');
        }
        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'nama' => 'required|min_length[3]|max_length[100]',
                'email' => 'required|valid_email|max_length[150]|is_unique[users.email]',
                'nomor_hp' => 'required|regex_match[/^(\+?62|0)8[0-9]{7,12}$/]',
                'password' => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            $phone = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
            $userModel = new UserModel();
            if ($userModel->where('nomor_hp', $phone)->first()) {
                return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp sudah terdaftar. Silakan login.');
            }
            $email = strtolower(trim((string) $this->request->getPost('email')));
            $userModel->insert([
                'email' => $email,
                'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
                'nama' => trim((string) $this->request->getPost('nama')),
                'nomor_hp' => $phone,
                'role' => 'pelanggan',
                'is_active' => 1,
            ]);
            $user = $userModel->find($userModel->getInsertID());
            $this->issueSession($user);
            return redirect()->to('/pelanggan/dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
        }
        return view('auth/register');
    }

    // ── Lupa password — info page (tidak ada reset mandiri) ──────
    public function lupaPassword()
    {
        $owner = (new SettingModel())->getValue('nomor_hp_owner', '');
        $waLink = $owner ? 'https://wa.me/' . preg_replace('/\D+/', '', $owner) . '?text=' . rawurlencode(
            'Halo SW Beauty Salon, saya lupa password akun pelanggan. Mohon bantuannya untuk reset.'
        ) : '';
        return view('auth/lupa_password', ['wa_link' => $waLink]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda telah logout.');
    }

    private function issueSession(array $user): void
    {
        session()->regenerate(true);
        session()->set([
            'is_logged_in' => true,
            'user_id' => $user['id'],
            'user_nama' => $user['nama'],
            'user_email' => $user['email'] ?? null,
            'user_hp' => $user['nomor_hp'] ?? '',
            'user_role' => $user['role'],
        ]);
    }
}
