<?php

namespace App\Controllers;

use App\Models\BookingModel;

class Pelanggan extends BaseController
{
    public function dashboard()
    {
        $userId = (int) session('user_id');
        $bookings = (new BookingModel())->findByUserId($userId);
        return view('pelanggan/dashboard', [
            'bookings' => $bookings,
            'nama' => session('user_nama'),
        ]);
    }
}
