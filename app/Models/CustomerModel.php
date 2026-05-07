<?php
namespace App\Models;
class CustomerModel extends BaseAppModel { protected $table = 'customers'; protected $allowedFields = ['user_id','name','phone','address']; }
