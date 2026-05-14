<?php

namespace App\Controllers;

use App\Models\BookingModel;

class Pelanggan extends BaseController
{
    public function dashboard()
    {
        $userId = (int) session('user_id');
        $hp = (string) session('user_hp');
        $bookings = (new BookingModel())->findByUserOrHp($userId, $hp);
        return view('pelanggan/dashboard', [
            'bookings' => $bookings,
            'nama' => session('user_nama'),
        ]);
    }
}
