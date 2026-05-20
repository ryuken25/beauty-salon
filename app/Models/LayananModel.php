<?php
namespace App\Models;

class LayananModel extends BaseAppModel
{
    protected $table = 'layanan';
    protected $allowedFields = ['nama', 'kategori', 'deskripsi', 'durasi_menit', 'harga', 'ikon', 'is_active'];
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /** True when the layanan is referenced by at least one booking. */
    public function hasBookings(int $layananId): bool
    {
        return db_connect()->table('bookings')->where('layanan_id', $layananId)->countAllResults() > 0;
    }
}
