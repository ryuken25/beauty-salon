<?php
namespace App\Models;
class StylistModel extends BaseAppModel { protected $table = 'stylists'; protected $useSoftDeletes = true; protected $allowedFields = ['user_id','name','phone','is_owner','is_active']; }
