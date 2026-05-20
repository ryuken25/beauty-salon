<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class PelangganController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $q = trim((string) $this->request->getGet('q'));

        // Customers are not user accounts — they exist only as booking rows.
        // Aggregate each unique (nama, nomor_hp) pair into one customer record.
        $builder = $db->table('bookings')
            ->select('
                nama_pelanggan,
                nomor_hp_pelanggan,
                MAX(email_pelanggan) AS email_pelanggan,
                COUNT(*) AS total_booking,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS total_selesai,
                SUM(CASE WHEN status IN ("pending_verification","accepted") THEN 1 ELSE 0 END) AS aktif,
                MAX(tanggal) AS terakhir
            ')
            ->groupBy('nomor_hp_pelanggan, nama_pelanggan')
            ->orderBy('terakhir', 'DESC');

        if ($q !== '') {
            $builder->groupStart()
                ->like('nama_pelanggan', $q)
                ->orLike('nomor_hp_pelanggan', $q)
                ->groupEnd();
        }

        $rows = $builder->limit(200)->get()->getResultArray();

        return view('admin/pelanggan/index', ['rows' => $rows, 'q' => $q]);
    }
}
