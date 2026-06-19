<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Public ────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('layanan', 'Home::layanan');
$routes->get('layanan/(:num)', 'Home::detail/$1');
$routes->get('api/promos', 'Api::promos');
$routes->get('api/baru', 'Api::baru');

$routes->match(['GET', 'POST'], 'booking', 'Booking::form', ['filter' => 'customer']);
$routes->get('booking/sukses/(:any)', 'Booking::sukses/$1', ['filter' => 'customer']);
$routes->get('api/slots', 'Api::slots');

// ── Cek & batal booking (publik — customer tanpa akun) ────
$routes->match(['GET', 'POST'], 'cek-booking', 'CekBooking::index');
$routes->get('cek-booking/(:segment)/batal', 'CekBooking::konfirmasiBatal/$1');
$routes->post('cek-booking/(:segment)/batal', 'CekBooking::prosesBatal/$1');
$routes->get('cek-booking-sukses/(:segment)', 'CekBooking::suksesBatal/$1');

// ── Auth: satu halaman login untuk semua role (email ATAU nomor WA) ─
$routes->match(['GET', 'POST'], 'login', 'Auth::login');
$routes->match(['GET', 'POST'], 'register', 'Auth::register');
$routes->get('lupa-password', 'Auth::lupaPassword');
$routes->match(['GET', 'POST'], 'logout', 'Auth::logout');
// Kompatibilitas URL lama: /admin/login & /admin/logout → login terpadu.
$routes->match(['GET', 'POST'], 'admin/login', static fn () => redirect()->to('/login'));
$routes->match(['GET', 'POST'], 'admin/logout', 'Auth::logout');
$routes->get('admin', static fn () => redirect()->to('/admin/dashboard'));
// /owner shortcut → operational dashboard (owner = admin superset).
$routes->get('owner', static fn () => redirect()->to('/admin/dashboard'));

// ── Pelanggan area (akun pelanggan only) ──────────────────
$routes->group('pelanggan', ['filter' => 'customer'], static function ($routes) {
    $routes->get('dashboard', 'Pelanggan::dashboard');
    $routes->get('booking/(:segment)', 'Pelanggan::detail/$1');
    $routes->get('booking/(:segment)/batal', 'Pelanggan::konfirmasiBatal/$1');
    $routes->post('booking/(:segment)/batal', 'Pelanggan::prosesBatal/$1');
    $routes->get('booking/(:segment)/sukses', 'Pelanggan::suksesBatal/$1');
});

// ── Admin area — operasional (admin & pemilik) ────────────
$routes->group('admin', ['filter' => 'admin'], static function ($routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');

    $routes->get('booking', 'Admin\BookingController::index');
    $routes->get('booking/jadwal', 'Admin\BookingController::jadwal');
    $routes->match(['GET', 'POST'], 'booking/walkin', 'Admin\BookingController::walkin');
    $routes->get('booking/(:num)', 'Admin\BookingController::detail/$1');
    $routes->post('booking/(:num)/verify', 'Admin\BookingController::verify/$1');
    $routes->post('booking/(:num)/reject', 'Admin\BookingController::reject/$1');
    $routes->post('booking/(:num)/cancel', 'Admin\BookingController::cancel/$1');
    $routes->post('booking/(:num)/complete', 'Admin\BookingController::complete/$1');
    $routes->post('booking/(:num)/wa-sent', 'Admin\BookingController::waSent/$1');
    $routes->post('booking/(:num)/dp-verify', 'Admin\BookingController::dpVerify/$1');

    $routes->get('pelanggan', 'Admin\PelangganController::index');
    $routes->post('pelanggan/(:num)/update-nama', 'Admin\PelangganController::updateNama/$1');
    $routes->post('pelanggan/(:num)/reset-password', 'Admin\PelangganController::resetPassword/$1');
    $routes->get('transaksi', 'Admin\TransaksiController::index');
    $routes->get('transaksi/(:num)/nota', 'Admin\TransaksiController::nota/$1');
    $routes->match(['GET', 'POST'], 'pengaturan', 'Admin\PengaturanController::index');
    $routes->post('pengaturan/ganti-password', 'Admin\PengaturanController::gantiPassword');
});

// ── Owner area — manajerial (pemilik only) ────────────────
$routes->group('owner', ['filter' => 'owner'], static function ($routes) {
    $routes->get('laporan', 'Owner\LaporanController::index');
    $routes->get('laporan/revenue', 'Owner\LaporanController::revenueData');

    $routes->get('layanan', 'Owner\LayananController::index');
    $routes->post('layanan', 'Owner\LayananController::store');
    $routes->post('layanan/(:num)/update', 'Owner\LayananController::update/$1');
    $routes->post('layanan/(:num)/delete', 'Owner\LayananController::delete/$1');

});
