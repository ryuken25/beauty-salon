<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if ($this->request->getMethod() === 'POST') {
            // Rate-limit FAILED attempts only — successful logins reset the
            // counter. A single typo won't lock the user out; a real burst
            // of bad passwords still hits the 8/15min ceiling.
            $failKey = 'login_fail_' . md5($this->request->getIPAddress());
            $fails = (int) (cache($failKey) ?? 0);
            if ($fails >= 8) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.');
            }
            $email = trim((string) $this->request->getPost('email'));
            $password = (string) $this->request->getPost('password');
            $user = (new UserModel())->where('email', $email)->where('is_active', 1)->first();
            if ($user && password_verify($password, $user['password_hash'])) {
                cache()->delete($failKey);
                // regenerate(true) drops prior session data while keeping the
                // session live — the supported way to do an in-place account
                // switch (admin ↔ pemilik) without an explicit logout.
                session()->regenerate(true);
                session()->set([
                    'is_logged_in' => true,
                    'user_id' => $user['id'],
                    'user_nama' => $user['nama'],
                    'user_email' => $user['email'],
                    'user_role' => $user['role'],
                ]);
                return $this->redirectByRole($user['role']);
            }
            cache()->save($failKey, $fails + 1, MINUTE * 15);
            return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
        }
        return view('auth/login');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda telah logout.');
    }

    private function redirectByRole(?string $role)
    {
        if ($role === 'pemilik') {
            return redirect()->to('/owner/dashboard');
        }
        if ($role === 'admin') {
            return redirect()->to('/admin/dashboard');
        }
        return redirect()->to('/');
    }
}
