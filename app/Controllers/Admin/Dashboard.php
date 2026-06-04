<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\BookingService;

class Dashboard extends BaseController
{
    public function index()
    {
        $this->autoCancelSweep();
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

    /**
     * Best-effort cron-less sweep. Throttled to 1× per 5 minutes via
     * cache so a busy admin page doesn't run it on every click.
     * Production should still schedule `php spark bookings:auto-cancel`.
     */
    private function autoCancelSweep(): void
    {
        if (cache('auto_cancel_last_run')) return;
        cache()->save('auto_cancel_last_run', time(), 300);
        (new BookingService())->autoCancelExpired();
    }
}
