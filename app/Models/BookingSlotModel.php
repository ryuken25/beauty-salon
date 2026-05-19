<?php
namespace App\Models;
class BookingSlotModel extends BaseAppModel {
    protected $table = 'booking_slots';
    protected $allowedFields = ['booking_id','tanggal','slot_waktu','status'];
    protected $useTimestamps = false;

    public function heldOn(string $tanggal): array
    {
        return array_map(
            static fn($r) => substr((string) $r['slot_waktu'], 0, 5),
            $this->where('tanggal', $tanggal)->where('status', 'held')->findAll()
        );
    }
}
