<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('is_logged_in')) {
            return redirect()->to('/admin/login')->with('error', 'Silakan login terlebih dahulu.');
        }
        if (! in_array(session('user_role'), ['admin', 'pemilik'], true)) {
            session()->destroy();
            return redirect()->to('/admin/login')->with('error', 'Akses tidak diizinkan.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
