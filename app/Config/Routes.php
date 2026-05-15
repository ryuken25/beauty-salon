<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');
$routes->get('layanan', 'Home::layanan');

$routes->match(['get', 'post'], 'booking', 'Booking::form');
$routes->get('booking/sukses/(:any)', 'Booking::sukses/$1');
$routes->get('booking/cek-saya', 'Booking::redirectCek');
$routes->match(['get', 'post'], 'cek-booking', 'Booking::cek');
$routes->get('booking/(:segment)', 'Booking::detail/$1');
$routes->post('booking/(:segment)/batal', 'Booking::batal/$1');

$routes->get('api/slots', 'Api::slots');

$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->match(['get', 'post'], 'register', 'Auth::register');
$routes->post('logout', 'Auth::logout');
$routes->match(['get', 'post'], 'admin/login', 'Auth::login');
$routes->get('admin', 'Auth::login');
$routes->post('admin/logout', 'Auth::logout');

$routes->group('pelanggan', ['filter' => 'pelanggan'], static function ($routes) {
    $routes->get('dashboard', 'Pelanggan::dashboard');
});

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
});

$routes->group('admin', ['filter' => 'pemilik'], static function ($routes) {
    $routes->get('layanan', 'Admin\LayananController::index');
    $routes->post('layanan', 'Admin\LayananController::store');
    $routes->post('layanan/(:num)/update', 'Admin\LayananController::update/$1');
    $routes->post('layanan/(:num)/delete', 'Admin\LayananController::delete/$1');

    $routes->get('stylist', 'Admin\StylistController::index');
    $routes->post('stylist', 'Admin\StylistController::store');
    $routes->post('stylist/(:num)/update', 'Admin\StylistController::update/$1');
    $routes->post('stylist/(:num)/delete', 'Admin\StylistController::delete/$1');
    $routes->match(['get', 'post'], 'stylist/(:num)/jadwal', 'Admin\StylistController::jadwal/$1');

    $routes->get('transaksi', 'Admin\TransaksiController::index');

    $routes->match(['get', 'post'], 'pengaturan', 'Admin\PengaturanController::index');
    $routes->post('pengaturan/ganti-password', 'Admin\PengaturanController::gantiPassword');
});
