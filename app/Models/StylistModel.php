<?php
namespace App\Models;

class StylistModel extends BaseAppModel
{
    protected $table = 'stylists';
    protected $allowedFields = ['nama', 'foto', 'spesialisasi', 'jam_kerja_mulai', 'jam_kerja_selesai', 'is_active'];
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /** Active, non-deleted stylists for the public booking dropdown. */
    public function activeForBooking(): array
    {
        return $this->where('is_active', 1)->orderBy('nama')->findAll();
    }

    /** True when the stylist has at least one booking row (any status). */
    public function hasBookings(int $stylistId): bool
    {
        return db_connect()->table('bookings')->where('stylist_id', $stylistId)->countAllResults() > 0;
    }
}
