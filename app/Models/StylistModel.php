<?php
namespace App\Models;
class StylistModel extends BaseAppModel {
    protected $table = 'stylists';
    protected $allowedFields = ['nama','nomor_hp','peran','is_default','is_active'];

    public function defaultActive(): ?array
    {
        return $this->where('is_default', 1)->where('is_active', 1)->orderBy('id')->first() ?: null;
    }
}
