<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SalonSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insertBatch([
            ['email' => 'owner@swbeautysalon.local', 'password_hash' => password_hash('Password123!', PASSWORD_BCRYPT), 'nama' => 'Ni Wayan Sutrisna Wati', 'role' => 'pemilik', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'admin@swbeautysalon.local', 'password_hash' => password_hash('Password123!', PASSWORD_BCRYPT), 'nama' => 'Admin Salon', 'role' => 'admin', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('stylists')->insert([
            'nama' => 'Ni Wayan Sutrisna Wati',
            'nomor_hp' => '6287862183074',
            'peran' => 'Owner & Stylist',
            'is_default' => 1,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $stylistId = (int) $this->db->insertID();

        $hari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $schedules = [];
        foreach ($hari as $h) {
            $schedules[] = ['stylist_id' => $stylistId, 'hari' => $h, 'jam_mulai' => '08:00:00', 'jam_selesai' => '19:00:00', 'is_libur' => 0, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->db->table('stylist_schedules')->insertBatch($schedules);

        $this->db->table('layanan')->insertBatch([
            ['nama' => 'Hair Treatment', 'kategori' => 'Hair', 'deskripsi' => 'Perawatan rambut premium untuk rambut sehat dan lembut.', 'durasi_menit' => 90, 'harga' => 250000, 'ikon' => 'bi-scissors', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Facial Premium', 'kategori' => 'Facial', 'deskripsi' => 'Perawatan wajah signature dengan produk premium.', 'durasi_menit' => 60, 'harga' => 180000, 'ikon' => 'bi-flower1', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Hair Color', 'kategori' => 'Hair', 'deskripsi' => 'Pewarnaan rambut bold & beautiful sesuai konsultasi.', 'durasi_menit' => 120, 'harga' => 350000, 'ikon' => 'bi-droplet', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Manicure', 'kategori' => 'Nails', 'deskripsi' => 'Perawatan tangan dan kuku elegan.', 'durasi_menit' => 60, 'harga' => 120000, 'ikon' => 'bi-hand-index', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Make Up', 'kategori' => 'Make Up', 'deskripsi' => 'Make up profesional untuk acara spesial.', 'durasi_menit' => 90, 'harga' => 300000, 'ikon' => 'bi-stars', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Diamond Tooth', 'kategori' => 'Aesthetic', 'deskripsi' => 'Pemasangan aksen diamond tooth kelas atas.', 'durasi_menit' => 30, 'harga' => 150000, 'ikon' => 'bi-gem', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Nails Art', 'kategori' => 'Nails', 'deskripsi' => 'Dekorasi kuku artistik.', 'durasi_menit' => 60, 'harga' => 100000, 'ikon' => 'bi-palette', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Sulam Alis', 'kategori' => 'Sulam', 'deskripsi' => 'Sulam alis natural dengan teknik premium.', 'durasi_menit' => 180, 'harga' => 450000, 'ikon' => 'bi-eye', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $defaults = [
            'nama_salon' => 'SW Beauty Salon',
            'alamat_salon' => 'Batunya, Kec. Baturiti, Kabupaten Tabanan, Bali 82191',
            'telp_salon' => '+62 878-6218-3074',
            'nomor_hp_owner' => '6287862183074',
            'jam_buka' => '08:00',
            'jam_tutup' => '19:00',
            'slot_durasi_menit' => '30',
            'range_hari_booking' => '7',
            'telegram_bot_token' => '8698187640:AAGlEgmSgv_5nlyPe-dZAzw3_nlFE64GZCo',
            'telegram_allowed_chat_ids' => '1553203064',
            'telegram_super_admin_chat_id' => '1553203064',
            'telegram_last_update_id' => '0',
            'template_wa_diterima' => "Halo {nama}, booking Anda {kode} untuk {layanan} pada {tanggal} jam {jam_mulai} sudah kami terima. Sampai jumpa di SW Beauty Salon.",
            'template_wa_ditolak' => "Halo {nama}, mohon maaf booking {kode} untuk {layanan} pada {tanggal} belum dapat kami terima. Silakan pilih jadwal lain. Terima kasih.",
            'template_wa_reminder' => "Halo {nama}, pengingat jadwal Anda di SW Beauty Salon hari ini jam {jam_mulai} untuk {layanan}. Sampai jumpa.",
            'template_wa_selesai' => "Terima kasih {nama} sudah datang ke SW Beauty Salon. Semoga puas dengan layanan kami.",
        ];
        $rows = [];
        foreach ($defaults as $k => $v) {
            $rows[] = ['key_name' => $k, 'value' => $v, 'updated_at' => $now];
        }
        $this->db->table('settings')->insertBatch($rows);
    }
}
