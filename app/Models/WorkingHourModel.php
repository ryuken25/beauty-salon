<?php
namespace App\Models;
class WorkingHourModel extends BaseAppModel { protected $table = 'stylist_working_hours'; protected $allowedFields = ['stylist_id','day_of_week','start_time','end_time','is_working']; }
