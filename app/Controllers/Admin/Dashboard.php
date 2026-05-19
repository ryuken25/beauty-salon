<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $role = session('user_role');

        $pendapatanHariIni = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', $today)->get()->getRow()->nominal ?? 0);
        $pendapatanKemarin = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', date('Y-m-d', strtotime('-1 day')))->get()->getRow()->nominal ?? 0);
        $trend = $pendapatanKemarin > 0 ? (int) round((($pendapatanHariIni - $pendapatanKemarin) / $pendapatanKemarin) * 100) : null;

        $bookingHariIni = $db->table('bookings')->where('tanggal', $today)->whereNotIn('status', ['rejected', 'cancelled'])->countAllResults();
        $bookingHariIniSelesai = $db->table('bookings')->where('tanggal', $today)->where('status', 'completed')->countAllResults();
        $pending = $db->table('bookings')->where('status', 'pending_verification')->countAllResults();

        $chartLabels = [];
        $chartValues = [];
        $hariShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $sum = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', $d)->get()->getRow()->nominal ?? 0);
            $chartLabels[] = $hariShort[(int) date('w', strtotime($d))];
            $chartValues[] = $sum;
        }
        $totalMingguIni = array_sum($chartValues);

        $topServices = $db->table('bookings b')
            ->select('l.nama AS nama, COUNT(*) AS jumlah')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('b.status', 'completed')
            ->where('b.completed_at >=', date('Y-m-01 00:00:00'))
            ->groupBy('l.id')
            ->orderBy('jumlah', 'DESC')
            ->limit(4)
            ->get()->getResultArray();
        $totalTop = array_sum(array_column($topServices, 'jumlah')) ?: 1;

        $bookingTerbaru = $db->table('bookings b')
            ->select('b.id, b.kode_booking, b.nama_pelanggan, b.slot_mulai, b.slot_selesai, b.status, l.nama AS nama_layanan')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->orderBy('b.created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        return view('admin/dashboard', [
            'role' => $role,
            'pendapatan_hari_ini' => $pendapatanHariIni,
            'trend' => $trend,
            'booking_hari_ini' => $bookingHariIni,
            'booking_hari_ini_selesai' => $bookingHariIniSelesai,
            'pending' => $pending,
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'total_minggu' => $totalMingguIni,
            'top_services' => $topServices,
            'total_top' => $totalTop,
            'booking_terbaru' => $bookingTerbaru,
        ]);
    }
}
