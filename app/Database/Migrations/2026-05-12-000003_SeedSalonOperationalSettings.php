<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedSalonOperationalSettings extends Migration
{
    public function up()
    {
        $db = $this->db;
        $now = date('Y-m-d H:i:s');
        $defaults = [
            'salon_open_time' => '09:00',
            'salon_close_time' => '21:00',
            'salon_whatsapp_owner' => '',
        ];
        foreach ($defaults as $key => $value) {
            $exists = $db->table('app_settings')->where('setting_key', $key)->countAllResults() > 0;
            if (! $exists) {
                $db->table('app_settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->table('app_settings')
            ->whereIn('setting_key', ['salon_open_time', 'salon_close_time', 'salon_whatsapp_owner'])
            ->delete();
    }
}
