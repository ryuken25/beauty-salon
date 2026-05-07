<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        if ($this->request->getMethod() === 'POST') {
            $email = trim((string) $this->request->getPost('email'));
            $password = (string) $this->request->getPost('password');
            $user = db_connect()->table('users')->where('email', $email)->where('is_active', 1)->get()->getRowArray();
            if ($user && password_verify($password, $user['password_hash'])) {
                session()->set(['user_id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]);
                return redirect()->to($user['role'] === 'customer' ? '/pelanggan' : '/admin')->with('success', 'Login berhasil.');
            }
            return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
        }
        return view('auth/login');
    }

    public function register()
    {
        if ($this->request->getMethod() === 'POST') {
            $rules = ['name' => 'required|min_length[3]', 'email' => 'required|valid_email|is_unique[users.email]', 'phone' => 'required|min_length[8]', 'password' => 'required|min_length[8]'];
            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            $db = db_connect();
            $db->transBegin();
            $now = date('Y-m-d H:i:s');
            $db->table('users')->insert(['name' => $this->request->getPost('name'), 'email' => $this->request->getPost('email'), 'phone' => $this->request->getPost('phone'), 'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT), 'role' => 'customer', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $userId = $db->insertID();
            $db->table('customers')->insert(['user_id' => $userId, 'name' => $this->request->getPost('name'), 'phone' => $this->request->getPost('phone'), 'address' => $this->request->getPost('address'), 'created_at' => $now, 'updated_at' => $now]);
            $db->transCommit();
            return redirect()->to('/login')->with('success', 'Registrasi berhasil. Silakan login.');
        }
        return view('auth/register');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('success', 'Anda telah logout.');
    }
}
