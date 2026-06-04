<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Guard for customer-only areas (/booking, /pelanggan/*). Not-logged-in is
 * sent to the customer login form with a flash that explains why; a logged-in
 * staff account is bounced to its own dashboard instead of being silently
 * granted access.
 */
class CustomerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('is_logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan login untuk booking.');
        }
        $role = session('user_role');
        if ($role !== 'pelanggan') {
            if ($role === 'pemilik') {
                return redirect()->to('/admin/dashboard')->with('error', 'Halaman ini untuk pelanggan.');
            }
            if ($role === 'admin') {
                return redirect()->to('/admin/dashboard')->with('error', 'Halaman ini untuk pelanggan.');
            }
            return redirect()->to('/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
