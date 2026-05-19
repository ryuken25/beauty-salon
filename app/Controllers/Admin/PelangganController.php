<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class PelangganController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $q = trim((string) $this->request->getGet('q'));

        // Aggregate from bookings: each unique (nama, nomor_hp) pair = one customer.
        // Registered pelanggan rows are linked via user_id; anonymous bookings carry
        // their own nama + HP.
        $builder = $db->table('bookings')
            ->select('
                MAX(user_id) AS user_id,
                nama_pelanggan,
                nomor_hp_pelanggan,
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
