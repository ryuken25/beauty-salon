<?php
namespace App\Models;
class TransaksiModel extends BaseAppModel {
    protected $table = 'transaksi';
    protected $allowedFields = ['booking_id','nominal','metode_bayar','tanggal_transaksi','catatan'];
    protected $useTimestamps = false;
}
