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

        // Query completed transactions
        $completed = $db->table('transaksi t')
            ->select('t.id, t.nominal, t.base_price, t.additional_price, t.dp_paid, t.sisa_bayar, t.metode_bayar, t.tanggal_transaksi AS tanggal, t.catatan, b.id AS booking_id, b.kode_booking, b.nama_pelanggan, l.nama AS nama_layanan, "pelunasan" AS tipe')
            ->join('bookings b', 'b.id = t.booking_id')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('DATE(t.tanggal_transaksi) >=', $start)
            ->where('DATE(t.tanggal_transaksi) <=', $end)
            ->get()->getResultArray();

        // Query bookings with verified DP
        $dps = $db->table('bookings b')
            ->select('NULL AS id, b.dp_amount AS nominal, b.final_service_price AS base_price, 0 AS additional_price, b.dp_amount AS dp_paid, b.remaining_payment AS sisa_bayar, "transfer" AS metode_bayar, b.dp_verified_at AS tanggal, b.catatan, b.id AS booking_id, b.kode_booking, b.nama_pelanggan, l.nama AS nama_layanan, "dp" AS tipe')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('b.payment_status', 'dp_verified')
            ->where('b.dp_verified_at IS NOT NULL')
            ->where('DATE(b.dp_verified_at) >=', $start)
            ->where('DATE(b.dp_verified_at) <=', $end)
            ->get()->getResultArray();

        // Merge and sort by date descending
        $rows = array_merge($completed, $dps);
        usort($rows, function ($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });

        // Calculate metrics based on actual cash flow:
        // - For DP payment: dp_paid (dp_amount)
        // - For Pelunasan: sisa_bayar (remaining payment)
        $total = 0;
        foreach ($rows as $r) {
            if ($r['tipe'] === 'dp') {
                $total += (int) $r['dp_paid'];
            } else {
                $total += (int) $r['sisa_bayar'];
            }
        }
        $count = count($rows);
        $avg = $count > 0 ? (int) round($total / $count) : 0;

        return view('admin/transaksi/index', [
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'total' => $total,
            'count' => $count,
            'avg' => $avg
        ]);
    }

    public function nota(int $id)
    {
        $db = db_connect();
        $transaksi = $db->table('transaksi t')
            ->select('t.id, t.nominal, t.base_price, t.additional_price, t.dp_paid, t.sisa_bayar, t.metode_bayar, t.tanggal_transaksi, t.catatan, b.kode_booking, b.nama_pelanggan, b.nomor_hp_pelanggan, b.email_pelanggan, b.verified_via, b.dp_verified_at, l.nama AS nama_layanan')
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

    public function resendEmail(int $id)
    {
        $db = db_connect();
        $transaksi = $db->table('transaksi')->where('id', $id)->get()->getRowArray();
        if (! $transaksi) {
            return redirect()->back()->with('error', 'Transaksi tidak ditemukan.');
        }
        $booking = (new \App\Models\BookingModel())->detail((int) $transaksi['booking_id']);
        if (! $booking) {
            return redirect()->back()->with('error', 'Booking tidak ditemukan.');
        }
        if (empty($booking['email_pelanggan'])) {
            return redirect()->back()->with('error', 'Pelanggan tidak memiliki alamat email.');
        }

        $sent = (new \App\Services\NotificationService())->sendFinalInvoiceEmail($booking, $transaksi);
        if ($sent) {
            return redirect()->back()->with('success', 'Email invoice final berhasil dikirim ulang.');
        }
        return redirect()->back()->with('error', 'Gagal mengirim email. Silakan cek konfigurasi email di .env.');
    }
}
