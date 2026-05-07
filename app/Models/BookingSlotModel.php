<?php
namespace App\Models;
class BookingSlotModel extends BaseAppModel { protected $table = 'booking_slots'; protected $useTimestamps = false; protected $allowedFields = ['booking_id','stylist_id','slot_date','slot_start','slot_end','created_at']; }
