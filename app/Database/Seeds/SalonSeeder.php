<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SalonSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $users = [
            ['name' => 'Pemilik SW Beauty Salon', 'email' => 'owner@swbeautysalon.local', 'phone' => '6281230000001', 'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT), 'role' => 'owner', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Admin SW Beauty Salon', 'email' => 'admin@swbeautysalon.local', 'phone' => '6281230000002', 'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT), 'role' => 'admin', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pelanggan Dummy', 'email' => 'pelanggan@example.com', 'phone' => '6281230000003', 'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT), 'role' => 'customer', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('users')->insertBatch($users);
        $ownerId = $this->db->table('users')->where('email', 'owner@swbeautysalon.local')->get()->getRowArray()['id'];
        $customerUserId = $this->db->table('users')->where('email', 'pelanggan@example.com')->get()->getRowArray()['id'];
        $this->db->table('customers')->insert(['user_id' => $customerUserId, 'name' => 'Pelanggan Dummy', 'phone' => '6281230000003', 'address' => 'Denpasar', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('stylists')->insertBatch([
            ['user_id' => $ownerId, 'name' => 'Ni Wayan Sutrisna Wati', 'phone' => '6281230000001', 'is_owner' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Stylist 2 Dummy', 'phone' => '6281230000004', 'is_owner' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $stylists = $this->db->table('stylists')->get()->getResultArray();
        $hours = [];
        foreach ($stylists as $stylist) {
            for ($d = 0; $d <= 6; $d++) {
                $hours[] = ['stylist_id' => $stylist['id'], 'day_of_week' => $d, 'start_time' => '08:00:00', 'end_time' => '19:00:00', 'is_working' => 1, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        $this->db->table('stylist_working_hours')->insertBatch($hours);
        $this->db->table('services')->insertBatch([
            ['category' => 'Hair', 'name' => 'Hair Treatment', 'description' => 'Perawatan rambut agar sehat dan lembut.', 'duration_minutes' => 60, 'price' => 150000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Hair', 'name' => 'Coloring', 'description' => 'Pewarnaan rambut sesuai konsultasi.', 'duration_minutes' => 120, 'price' => 350000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Face', 'name' => 'Facial', 'description' => 'Perawatan wajah dasar.', 'duration_minutes' => 60, 'price' => 175000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Body', 'name' => 'Wax', 'description' => 'Layanan wax singkat.', 'duration_minutes' => 30, 'price' => 90000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Make Up', 'name' => 'Make Up', 'description' => 'Make up untuk acara.', 'duration_minutes' => 90, 'price' => 300000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Nails', 'name' => 'Nails', 'description' => 'Perawatan dan dekorasi kuku.', 'duration_minutes' => 60, 'price' => 125000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Sulam', 'name' => 'Sulam', 'description' => 'Layanan sulam kecantikan.', 'duration_minutes' => 120, 'price' => 450000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Aesthetic', 'name' => 'Diamond Tooth', 'description' => 'Pemasangan aksen diamond tooth.', 'duration_minutes' => 30, 'price' => 100000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $settings = [
            ['setting_key' => 'salon_whatsapp', 'setting_value' => '6281230000001', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'telegram_allowed_chat_ids', 'setting_value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['setting_key' => 'telegram_last_update_id', 'setting_value' => '0', 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('app_settings')->insertBatch($settings);
    }
}
