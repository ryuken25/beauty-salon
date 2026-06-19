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
            ->select('t.id, t.nominal, t.base_price, t.additional_price, t.dp_paid, t.sisa_bayar, t.metode_bayar, t.tanggal_transaksi, t.catatan, b.kode_booking, b.nama_pelanggan, l.nama AS nama_layanan')
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

    public function nota(int $id)
    {
        $db = db_connect();
        $transaksi = $db->table('transaksi t')
            ->select('t.id, t.nominal, t.base_price, t.additional_price, t.dp_paid, t.sisa_bayar, t.metode_bayar, t.tanggal_transaksi, t.catatan, b.kode_booking, b.nama_pelanggan, b.nomor_hp_pelanggan, b.verified_via, l.nama AS nama_layanan')
            ->join('bookings b', 'b.id = t.booking_id')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('t.id', $id)
            ->get()->getRowArray();

        if (! $transaksi) {
            return redirect()->to('/admin/transaksi')->with('error', 'Transaksi tidak ditemukan.');
        }

        // Get cashier name
        $cashier = 'Admin';
        if (! empty($transaksi['verified_via'])) {
            if (str_starts_with($transaksi['verified_via'], 'dashboard:')) {
                $adminId = (int) substr($transaksi['verified_via'], 10);
                $admin = $db->table('users')->where('id', $adminId)->get()->getRowArray();
                if ($admin) {
                    $cashier = $admin['nama'];
                }
            } else {
                $cashier = $transaksi['verified_via'];
            }
        }

        return view('admin/transaksi/nota', [
            't' => $transaksi,
            'cashier' => $cashier,
        ]);
    }
}
