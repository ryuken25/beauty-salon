<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $today = date('Y-m-d');

        $bookingHariIni = $db->table('bookings')
            ->where('tanggal', $today)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->countAllResults();
        $bookingHariIniSelesai = $db->table('bookings')
            ->where('tanggal', $today)
            ->where('status', 'completed')
            ->countAllResults();
        $pending = $db->table('bookings')
            ->where('status', 'pending_verification')
            ->countAllResults();
        $acceptedHariIni = $db->table('bookings')
            ->where('tanggal', $today)
            ->where('status', 'accepted')
            ->countAllResults();

        $bookingTerbaru = $db->table('bookings b')
            ->select('b.id, b.kode_booking, b.nama_pelanggan, b.slot_mulai, b.slot_selesai, b.status, l.nama AS nama_layanan')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->orderBy('b.created_at', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        return view('admin/dashboard', [
            'booking_hari_ini' => $bookingHariIni,
            'booking_hari_ini_selesai' => $bookingHariIniSelesai,
            'pending' => $pending,
            'accepted_hari_ini' => $acceptedHariIni,
            'booking_terbaru' => $bookingTerbaru,
        ]);
    }
}
