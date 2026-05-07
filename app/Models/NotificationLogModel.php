<?php
namespace App\Models;
class NotificationLogModel extends BaseAppModel { protected $table = 'notification_logs'; protected $useTimestamps = false; protected $allowedFields = ['booking_id','channel','event_type','recipient','message','status','error_message','created_at']; }
