<?php
namespace App\Models;
class AppSettingModel extends BaseAppModel { protected $table = 'app_settings'; protected $allowedFields = ['setting_key','setting_value']; }
