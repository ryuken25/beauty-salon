<?php
namespace App\Models;
class TelegramActionTokenModel extends BaseAppModel { protected $table = 'telegram_action_tokens'; protected $useTimestamps = false; protected $allowedFields = ['booking_id','action','token','expires_at','used_at','created_at']; }
