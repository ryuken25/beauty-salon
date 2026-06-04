<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\UserModel;
use App\Services\BookingService;

class Auth extends BaseController
{
    private const RATE_LIMIT = 8;
    private const RATE_WINDOW_SEC = 900; // 15 menit

    // ── Login admin / pemilik (email + password) ─────────────────
    public function login()
    {
        if ($this->request->getMethod() === 'POST') {
            $failKey = 'login_fail_' . md5($this->request->getIPAddress());
            $fails = (int) (cache($failKey) ?? 0);
            if ($fails >= self::RATE_LIMIT) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.');
            }
            $email = trim((string) $this->request->getPost('email'));
            $password = (string) $this->request->getPost('password');
            $user = (new UserModel())
                ->where('email', $email)
                ->where('is_active', 1)
                ->whereIn('role', ['admin', 'pemilik'])
                ->first();
            if ($user && password_verify($password, $user['password_hash'])) {
                cache()->delete($failKey);
                $this->issueSession($user);
                return redirect()->to('/admin/dashboard');
            }
            cache()->save($failKey, $fails + 1, self::RATE_WINDOW_SEC);
            return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
        }
        return view('auth/login');
    }

    // ── Login pelanggan (nomor WA + password) ────────────────────
    public function loginPelanggan()
    {
        if ($this->request->getMethod() === 'POST') {
            $failKey = 'login_fail_' . md5($this->request->getIPAddress());
            $fails = (int) (cache($failKey) ?? 0);
            if ($fails >= self::RATE_LIMIT) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.');
            }
            $phone = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
            $password = (string) $this->request->getPost('password');
            $user = $phone !== '' ? (new UserModel())->findPelangganByPhone($phone) : null;
            if ($user && password_verify($password, $user['password_hash'])) {
                cache()->delete($failKey);
                $this->issueSession($user);
                return redirect()->to('/pelanggan/dashboard');
            }
            cache()->save($failKey, $fails + 1, self::RATE_WINDOW_SEC);
            return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp atau password salah.');
        }
        return view('auth/login_pelanggan');
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
            $userModel->insert([
                'email' => null,
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
        $role = session('user_role');
        session()->destroy();
        $target = in_array($role, ['admin', 'pemilik'], true) ? '/admin/login' : '/login';
        return redirect()->to($target)->with('success', 'Anda telah logout.');
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
