<?php
namespace App\Models;
class LayananModel extends BaseAppModel {
    protected $table = 'layanan';
    protected $allowedFields = ['nama','kategori','deskripsi','durasi_menit','harga','ikon','is_active'];
}
