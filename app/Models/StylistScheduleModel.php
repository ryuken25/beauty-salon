<?php
namespace App\Models;
class StylistScheduleModel extends BaseAppModel {
    protected $table = 'stylist_schedules';
    protected $allowedFields = ['stylist_id','hari','jam_mulai','jam_selesai','is_libur'];

    public function forStylist(int $stylistId, string $hari): ?array
    {
        return $this->where('stylist_id', $stylistId)->where('hari', $hari)->first() ?: null;
    }
}
