<?php

namespace App\Models;

class BookingLogModel extends BaseAppModel
{
    protected $table = 'booking_logs';
    protected $allowedFields = ['booking_id', 'event_type', 'actor', 'actor_role', 'payload', 'notes', 'created_at'];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    public function forBooking(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->findAll();
    }
}
