<?php
namespace App\Models;
class UserModel extends BaseAppModel {
    protected $table = 'users';
    protected $allowedFields = ['email','password_hash','nama','nomor_hp','role','is_active'];
}
