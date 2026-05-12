<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session('is_logged_in')) {
            return redirect()->to('/admin/dashboard');
        }
        if ($this->request->getMethod() === 'POST') {
            $throttler = service('throttler');
            $key = 'login-' . $this->request->getIPAddress();
            if (! $throttler->check($key, 5, MINUTE * 15)) {
                return redirect()->back()->with('error', 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.');
            }
            $email = trim((string) $this->request->getPost('email'));
            $password = (string) $this->request->getPost('password');
            $user = (new UserModel())->where('email', $email)->where('is_active', 1)->whereIn('role', ['admin', 'pemilik'])->first();
            if ($user && password_verify($password, $user['password_hash'])) {
                session()->regenerate();
                session()->set([
                    'is_logged_in' => true,
                    'user_id' => $user['id'],
                    'user_nama' => $user['nama'],
                    'user_email' => $user['email'],
                    'user_role' => $user['role'],
                ]);
                return redirect()->to('/admin/dashboard');
            }
            return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
        }
        return view('admin/auth/login');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('success', 'Anda telah logout.');
    }
}
