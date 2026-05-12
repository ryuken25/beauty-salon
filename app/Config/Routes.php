<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('layanan', 'Home::services');
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->match(['get', 'post'], 'register', 'Auth::register');
$routes->post('logout', 'Auth::logout', ['filter' => 'auth']);
$routes->post('telegram/webhook', 'TelegramController::webhook');

$routes->group('pelanggan', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Customer\Dashboard::index', ['filter' => 'role:customer']);
    $routes->match(['get', 'post'], 'booking/baru', 'Customer\BookingController::create', ['filter' => 'role:customer']);
    $routes->get('booking/slots', 'Customer\BookingController::slots', ['filter' => 'role:customer']);
    $routes->get('booking', 'Customer\BookingController::history', ['filter' => 'role:customer']);
    $routes->get('booking/(:num)/sukses', 'Customer\BookingController::success/$1', ['filter' => 'role:customer']);
    $routes->get('booking/(:num)', 'Customer\BookingController::detail/$1', ['filter' => 'role:customer']);
    $routes->post('booking/(:num)/batal', 'Customer\BookingController::cancel/$1', ['filter' => 'role:customer']);
});

$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Admin\Dashboard::index', ['filter' => 'role:admin,owner']);
    $routes->get('booking', 'Admin\BookingController::index', ['filter' => 'role:admin,owner']);
    $routes->get('booking/pending', 'Admin\BookingController::pending', ['filter' => 'role:admin,owner']);
    $routes->get('booking/jadwal', 'Admin\BookingController::schedule', ['filter' => 'role:admin,owner']);
    $routes->match(['get', 'post'], 'booking/walkin', 'Admin\BookingController::walkin', ['filter' => 'role:admin,owner']);
    $routes->get('booking/slots', 'Admin\BookingController::slots', ['filter' => 'role:admin,owner']);
    $routes->get('booking/(:num)', 'Admin\BookingController::detail/$1', ['filter' => 'role:admin,owner']);
    $routes->post('booking/(:num)/terima', 'Admin\BookingController::accept/$1', ['filter' => 'role:admin,owner']);
    $routes->post('booking/(:num)/tolak', 'Admin\BookingController::reject/$1', ['filter' => 'role:admin,owner']);
    $routes->post('booking/(:num)/batal', 'Admin\BookingController::cancel/$1', ['filter' => 'role:admin,owner']);
    $routes->post('booking/(:num)/selesai', 'Admin\BookingController::complete/$1', ['filter' => 'role:admin,owner']);
    $routes->post('booking/(:num)/wa-sent', 'Admin\BookingController::markWaSent/$1', ['filter' => 'role:admin,owner']);
    $routes->get('transaksi', 'Admin\TransactionController::index', ['filter' => 'role:owner']);
    $routes->get('laporan', 'Admin\TransactionController::report', ['filter' => 'role:owner']);
    $routes->match(['get', 'post'], 'pengaturan', 'Admin\SettingsController::index', ['filter' => 'role:owner']);
    $routes->get('layanan', 'Admin\ServiceController::index', ['filter' => 'role:owner']);
    $routes->get('layanan/new', 'Admin\ServiceController::new', ['filter' => 'role:owner']);
    $routes->post('layanan', 'Admin\ServiceController::create', ['filter' => 'role:owner']);
    $routes->get('layanan/(:num)/edit', 'Admin\ServiceController::edit/$1', ['filter' => 'role:owner']);
    $routes->put('layanan/(:num)', 'Admin\ServiceController::update/$1', ['filter' => 'role:owner']);
    $routes->delete('layanan/(:num)', 'Admin\ServiceController::delete/$1', ['filter' => 'role:owner']);
    $routes->get('stylist', 'Admin\StylistController::index', ['filter' => 'role:owner']);
    $routes->get('stylist/new', 'Admin\StylistController::new', ['filter' => 'role:owner']);
    $routes->post('stylist', 'Admin\StylistController::create', ['filter' => 'role:owner']);
    $routes->get('stylist/(:num)/edit', 'Admin\StylistController::edit/$1', ['filter' => 'role:owner']);
    $routes->put('stylist/(:num)', 'Admin\StylistController::update/$1', ['filter' => 'role:owner']);
    $routes->delete('stylist/(:num)', 'Admin\StylistController::delete/$1', ['filter' => 'role:owner']);
    $routes->match(['get', 'post'], 'stylist/(:num)/jam-kerja', 'Admin\StylistController::hours/$1', ['filter' => 'role:owner']);
});
