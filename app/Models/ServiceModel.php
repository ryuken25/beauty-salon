<?php
namespace App\Models;
class ServiceModel extends BaseAppModel { protected $table = 'services'; protected $useSoftDeletes = true; protected $allowedFields = ['category','name','description','duration_minutes','price','is_active']; }
