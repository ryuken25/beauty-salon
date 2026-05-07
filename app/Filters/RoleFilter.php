<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowed = $arguments ?? [];
        if (! session('user_id')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }
        if ($allowed && ! in_array(session('role'), $allowed, true)) {
            return redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
