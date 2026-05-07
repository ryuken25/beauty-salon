<?php
namespace App\Models;
class UserModel extends BaseAppModel { protected $table = 'users'; protected $allowedFields = ['name','email','phone','password_hash','role','is_active']; }
