<?php
namespace App\Models;

class UserModel extends BaseAppModel
{
    protected $table = 'users';
    protected $allowedFields = ['email', 'password_hash', 'nama', 'nomor_hp', 'role', 'is_active'];

    /** Login pelanggan = nomor WA + password. */
    public function findPelangganByPhone(string $phone): ?array
    {
        return $this->where('role', 'pelanggan')
            ->where('nomor_hp', $phone)
            ->where('is_active', 1)
            ->first();
    }
}
