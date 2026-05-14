<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\BookingService;

class Auth extends BaseController
{
    public function login()
    {
        if (session('is_logged_in')) {
            return $this->redirectByRole(session('user_role'));
        }
        if ($this->request->getMethod() === 'POST') {
            $throttler = service('throttler');
            $key = 'login-' . md5($this->request->getIPAddress());
            if (! $throttler->check($key, 8, MINUTE * 15)) {
                return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.');
            }
            $email = trim((string) $this->request->getPost('email'));
            $password = (string) $this->request->getPost('password');
            $user = (new UserModel())->where('email', $email)->where('is_active', 1)->first();
            if ($user && password_verify($password, $user['password_hash'])) {
                session()->regenerate();
                session()->set([
                    'is_logged_in' => true,
                    'user_id' => $user['id'],
                    'user_nama' => $user['nama'],
                    'user_email' => $user['email'],
                    'user_hp' => $user['nomor_hp'] ?? '',
                    'user_role' => $user['role'],
                ]);
                return $this->redirectByRole($user['role']);
            }
            return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
        }
        return view('auth/login');
    }

    public function register()
    {
        if (session('is_logged_in')) {
            return $this->redirectByRole(session('user_role'));
        }
        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'nama' => 'required|min_length[3]|max_length[100]',
                'email' => 'required|valid_email|is_unique[users.email]',
                'nomor_hp' => 'required|regex_match[/^(\+?62|0)8[0-9]{7,12}$/]',
                'password' => 'required|min_length[6]',
                'password_confirm' => 'required|matches[password]',
            ];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            $nomorHp = (new BookingService())->normalizePhone((string) $this->request->getPost('nomor_hp'));
            $userModel = new UserModel();
            $userModel->insert([
                'email' => trim((string) $this->request->getPost('email')),
                'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'nama' => trim((string) $this->request->getPost('nama')),
                'nomor_hp' => $nomorHp,
                'role' => 'pelanggan',
                'is_active' => 1,
            ]);
            $userId = $userModel->getInsertID();
            $user = $userModel->find($userId);
            session()->regenerate();
            session()->set([
                'is_logged_in' => true,
                'user_id' => $user['id'],
                'user_nama' => $user['nama'],
                'user_email' => $user['email'],
                'user_hp' => $user['nomor_hp'] ?? '',
                'user_role' => 'pelanggan',
            ]);
            return redirect()->to('/pelanggan/dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
        }
        return view('auth/register');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('success', 'Anda telah logout.');
    }

    private function redirectByRole(?string $role)
    {
        if (in_array($role, ['admin', 'pemilik'], true)) {
            return redirect()->to('/admin/dashboard');
        }
        if ($role === 'pelanggan') {
            return redirect()->to('/pelanggan/dashboard');
        }
        return redirect()->to('/');
    }
}
