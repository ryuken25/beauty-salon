<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        if ($this->request->getMethod() === 'POST') {
            $email = trim((string) $this->request->getPost('email'));
            $password = (string) $this->request->getPost('password');
            $user = db_connect()->table('users')->where('email', $email)->where('is_active', 1)->whereIn('role', ['admin', 'owner'])->get()->getRowArray();
            if ($user && password_verify($password, $user['password_hash'])) {
                session()->set(['user_id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]);
                return redirect()->to('/admin')->with('success', 'Login berhasil.');
            }
            return redirect()->back()->withInput()->with('error', 'Email atau password admin/pemilik salah.');
        }
        return view('auth/login');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('success', 'Anda telah logout.');
    }
}
