<?php
namespace App\Models;
class TransaksiModel extends BaseAppModel {
    protected $table = 'transaksi';
    protected $allowedFields = ['booking_id','nominal','base_price','additional_price','metode_bayar','tanggal_transaksi','catatan','dp_paid','sisa_bayar'];
    protected $useTimestamps = false;
}
