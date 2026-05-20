<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;

class LaporanController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $today = date('Y-m-d');

        $pendapatanHariIni = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', $today)->get()->getRow()->nominal ?? 0);
        $pendapatanKemarin = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', date('Y-m-d', strtotime('-1 day')))->get()->getRow()->nominal ?? 0);
        $trend = $pendapatanKemarin > 0 ? (int) round((($pendapatanHariIni - $pendapatanKemarin) / $pendapatanKemarin) * 100) : null;

        $pendapatanBulanIni = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi) >=', date('Y-m-01'))->get()->getRow()->nominal ?? 0);

        // 7-day revenue series for the chart
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

        // Status distribution this month
        $statusBreakdown = $db->table('bookings')
            ->select('status, COUNT(*) AS jumlah')
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->groupBy('status')
            ->get()
            ->getResultArray();
        $statusMap = ['pending_verification' => 0, 'accepted' => 0, 'completed' => 0, 'rejected' => 0, 'cancelled' => 0];
        foreach ($statusBreakdown as $r) {
            $statusMap[$r['status']] = (int) $r['jumlah'];
        }

        $topServices = $db->table('bookings b')
            ->select('l.nama AS nama, COUNT(*) AS jumlah')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('b.status', 'completed')
            ->where('b.completed_at >=', date('Y-m-01 00:00:00'))
            ->groupBy('l.id')
            ->orderBy('jumlah', 'DESC')
            ->limit(5)
            ->get()->getResultArray();
        $totalTop = array_sum(array_column($topServices, 'jumlah')) ?: 1;

        return view('owner/laporan', [
            'pendapatan_hari_ini' => $pendapatanHariIni,
            'pendapatan_bulan_ini' => $pendapatanBulanIni,
            'trend' => $trend,
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'total_minggu' => $totalMingguIni,
            'status_map' => $statusMap,
            'top_services' => $topServices,
            'total_top' => $totalTop,
        ]);
    }
}
