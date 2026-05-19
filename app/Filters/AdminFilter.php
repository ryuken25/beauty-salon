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
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }
        $role = session('user_role');
        if (! in_array($role, ['admin', 'pemilik'], true)) {
            // Soft reject — keep the session, just bounce them to their own area.
            $target = $role === 'pelanggan' ? '/pelanggan/dashboard' : '/';
            return redirect()->to($target)->with('error', 'Area admin hanya untuk staff & pemilik.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
