<?php
namespace App\Models;
class BookingModel extends BaseAppModel { protected $table = 'bookings'; protected $allowedFields = ['booking_code','customer_id','service_id','stylist_id','booking_date','start_time','end_time','slot_count','service_price','source','status','notes','rejection_reason','created_by','verified_by','verified_by_telegram_chat_id','verified_at','cancelled_by','cancelled_at','completed_by','completed_at','wa_notified_at','wa_notified_by']; }
