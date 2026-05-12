<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class TransaksiController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $start = (string) ($this->request->getGet('start') ?: date('Y-m-01'));
        $end = (string) ($this->request->getGet('end') ?: date('Y-m-d'));
        $rows = $db->table('transaksi t')
            ->select('t.id, t.nominal, t.metode_bayar, t.tanggal_transaksi, t.catatan, b.kode_booking, b.nama_pelanggan, l.nama AS nama_layanan')
            ->join('bookings b', 'b.id = t.booking_id')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('DATE(t.tanggal_transaksi) >=', $start)
            ->where('DATE(t.tanggal_transaksi) <=', $end)
            ->orderBy('t.tanggal_transaksi', 'DESC')
            ->get()->getResultArray();
        $total = array_sum(array_column($rows, 'nominal'));
        $count = count($rows);
        $avg = $count > 0 ? (int) round($total / $count) : 0;
        return view('admin/transaksi/index', ['rows' => $rows, 'start' => $start, 'end' => $end, 'total' => $total, 'count' => $count, 'avg' => $avg]);
    }
}
