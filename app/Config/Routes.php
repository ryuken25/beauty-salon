<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Public ────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('layanan', 'Home::layanan');

$routes->match(['get', 'post'], 'booking', 'Booking::form');
$routes->get('booking/sukses/(:any)', 'Booking::sukses/$1');
$routes->get('booking/cek-saya', 'Booking::redirectCek');
$routes->match(['get', 'post'], 'cek-booking', 'Booking::cek');
$routes->get('booking/(:segment)', 'Booking::detail/$1');
$routes->post('booking/(:segment)/batal', 'Booking::batal/$1');

$routes->get('api/slots', 'Api::slots');

// ── Auth ──────────────────────────────────────────────────
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->match(['get', 'post'], 'register', 'Auth::register');
$routes->post('logout', 'Auth::logout');
// Back-compat: keep /admin/login + /admin pointing to the unified login.
$routes->match(['get', 'post'], 'admin/login', 'Auth::login');
$routes->get('admin', 'Auth::login');
$routes->post('admin/logout', 'Auth::logout');
// Pemilik shortcut to /owner via /owner GET if not logged in.
$routes->get('owner', static function () {
    return redirect()->to(session('user_role') === 'pemilik' ? '/owner/dashboard' : '/login');
});

// ── Pelanggan area ────────────────────────────────────────
$routes->group('pelanggan', ['filter' => 'pelanggan'], static function ($routes) {
    $routes->get('dashboard', 'Pelanggan::dashboard');
    $routes->post('booking/(:num)/cancel', 'Pelanggan::cancel/$1');
});

// ── Admin area (operational) ──────────────────────────────
// Allowed for admin AND pemilik (pemilik has read access to admin views).
$routes->group('admin', ['filter' => 'admin'], static function ($routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');

    $routes->get('booking', 'Admin\BookingController::index');
    $routes->get('booking/jadwal', 'Admin\BookingController::jadwal');
    $routes->match(['get', 'post'], 'booking/walkin', 'Admin\BookingController::walkin');
    $routes->get('booking/(:num)', 'Admin\BookingController::detail/$1');
    $routes->post('booking/(:num)/verify', 'Admin\BookingController::verify/$1');
    $routes->post('booking/(:num)/reject', 'Admin\BookingController::reject/$1');
    $routes->post('booking/(:num)/cancel', 'Admin\BookingController::cancel/$1');
    $routes->post('booking/(:num)/complete', 'Admin\BookingController::complete/$1');
    $routes->post('booking/(:num)/wa-sent', 'Admin\BookingController::waSent/$1');

    $routes->get('pelanggan', 'Admin\PelangganController::index');
});

// ── Owner area (analytical / managerial — pemilik only) ───
$routes->group('owner', ['filter' => 'owner'], static function ($routes) {
    $routes->get('dashboard', 'Owner\Dashboard::index');

    $routes->get('layanan', 'Owner\LayananController::index');
    $routes->post('layanan', 'Owner\LayananController::store');
    $routes->post('layanan/(:num)/update', 'Owner\LayananController::update/$1');
    $routes->post('layanan/(:num)/delete', 'Owner\LayananController::delete/$1');

    $routes->get('transaksi', 'Owner\TransaksiController::index');

    $routes->match(['get', 'post'], 'pengaturan', 'Owner\PengaturanController::index');
    $routes->post('pengaturan/ganti-password', 'Owner\PengaturanController::gantiPassword');
});
