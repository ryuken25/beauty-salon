<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SalonSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Login accounts — admin & pemilik only. Pelanggan tidak punya akun.
        $this->db->table('users')->insertBatch([
            ['email' => 'owner@swbeautysalon.local', 'password_hash' => password_hash('Password123!', PASSWORD_BCRYPT), 'nama' => 'Ni Wayan Sutrisna Wati', 'role' => 'pemilik', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'admin@swbeautysalon.local', 'password_hash' => password_hash('Password123!', PASSWORD_BCRYPT), 'nama' => 'Admin Salon', 'role' => 'admin', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Stylist tim salon (full CRUD via /owner/stylist).
        $this->db->table('stylists')->insertBatch([
            ['nama' => 'Ni Wayan Sutrisna Wati', 'spesialisasi' => 'Hair & Make Up', 'jam_kerja_mulai' => '08:00:00', 'jam_kerja_selesai' => '19:00:00', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Kadek Ayu Lestari',      'spesialisasi' => 'Nail & Lashes',  'jam_kerja_mulai' => '09:00:00', 'jam_kerja_selesai' => '17:00:00', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Putu Indah Pertiwi',     'spesialisasi' => 'Facial & Body',  'jam_kerja_mulai' => '10:00:00', 'jam_kerja_selesai' => '18:00:00', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Layanan SW Beauty Salon — durasi harus kelipatan 30 menit (fixed time slot).
        // Harga estimasi range Tabanan; pemilik bisa adjust via /admin/layanan.
        $layanan = [
            // — Nails & Body —
            ['nama' => 'Nail Art',                  'kategori' => 'Nails',          'deskripsi' => 'Dekorasi kuku artistik (30–90 menit).',                'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-palette'],
            ['nama' => 'Manicure / Pedicure',       'kategori' => 'Nails',          'deskripsi' => 'Perawatan tangan & kuku elegan.',                      'durasi_menit' => 30,  'harga' => 80000,  'ikon' => 'bi-hand-index'],
            ['nama' => 'Callus Removal',            'kategori' => 'Nails',          'deskripsi' => 'Pengangkatan kapalan & kulit mati pada kaki.',         'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-droplet-half'],

            // — Lashes & Brow —
            ['nama' => 'Eyelash Extension',         'kategori' => 'Lashes',         'deskripsi' => 'Pemasangan bulu mata premium (30–60 menit).',          'durasi_menit' => 60,  'harga' => 200000, 'ikon' => 'bi-eye'],
            ['nama' => 'Shaping Alis',              'kategori' => 'Brow',           'deskripsi' => 'Pembentukan alis natural (kurang lebih 5 menit).',     'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-eye-fill'],
            ['nama' => 'Sulam Alis',                'kategori' => 'Sulam',          'deskripsi' => 'Sulam alis natural teknik premium (±3 jam).',          'durasi_menit' => 180, 'harga' => 450000, 'ikon' => 'bi-pen'],
            ['nama' => 'Sulam Bibir',               'kategori' => 'Sulam',          'deskripsi' => 'Sulam bibir premium (±2 jam).',                        'durasi_menit' => 120, 'harga' => 400000, 'ikon' => 'bi-emoji-kiss'],

            // — Body Treatment —
            ['nama' => 'IPL Treatment',             'kategori' => 'Body',           'deskripsi' => 'Perawatan IPL kulit.',                                 'durasi_menit' => 30,  'harga' => 250000, 'ikon' => 'bi-lightning'],
            ['nama' => 'Waxing + Detox Underarm',   'kategori' => 'Body',           'deskripsi' => 'Waxing ketiak + detox (1 jam).',                       'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-droplet'],
            ['nama' => 'Wax Kaki / Tangan',         'kategori' => 'Body',           'deskripsi' => 'Hair removal kaki atau tangan (30 menit).',            'durasi_menit' => 30,  'harga' => 80000,  'ikon' => 'bi-droplet'],

            // — Face —
            ['nama' => 'Facial',                    'kategori' => 'Facial',         'deskripsi' => 'Perawatan wajah signature (30–60 menit).',             'durasi_menit' => 60,  'harga' => 180000, 'ikon' => 'bi-flower1'],

            // — Hair —
            ['nama' => 'Keramas',                   'kategori' => 'Hair',           'deskripsi' => 'Cuci rambut + pijat kepala (±30 menit).',              'durasi_menit' => 30,  'harga' => 35000,  'ikon' => 'bi-droplet-half'],
            ['nama' => 'Masker Bilas',              'kategori' => 'Hair',           'deskripsi' => 'Masker rambut bilas (±30 menit).',                     'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-droplet'],
            ['nama' => 'Catok / Styling',           'kategori' => 'Hair',           'deskripsi' => 'Catok / styling rambut (±30 menit).',                  'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-magic'],
            ['nama' => 'Masker Steam',              'kategori' => 'Hair',           'deskripsi' => 'Masker rambut + steam (1 jam).',                       'durasi_menit' => 60,  'harga' => 80000,  'ikon' => 'bi-cloud'],
            ['nama' => 'Creambath',                 'kategori' => 'Hair',           'deskripsi' => 'Creambath relax (90 menit).',                          'durasi_menit' => 90,  'harga' => 100000, 'ikon' => 'bi-stars'],
            ['nama' => 'Hair Spa',                  'kategori' => 'Hair',           'deskripsi' => 'Hair spa premium (90 menit).',                         'durasi_menit' => 90,  'harga' => 150000, 'ikon' => 'bi-gem'],
            ['nama' => 'Smoothing',                 'kategori' => 'Hair',           'deskripsi' => 'Smoothing rambut (3–6 jam).',                          'durasi_menit' => 240, 'harga' => 600000, 'ikon' => 'bi-scissors'],
            ['nama' => 'Blow Permanent',            'kategori' => 'Hair',           'deskripsi' => 'Blow permanent (4–6 jam).',                            'durasi_menit' => 300, 'harga' => 700000, 'ikon' => 'bi-wind'],
            ['nama' => 'Treatment Anti Ketombe',    'kategori' => 'Hair',           'deskripsi' => 'Treatment anti ketombe (90 menit).',                   'durasi_menit' => 90,  'harga' => 150000, 'ikon' => 'bi-shield-check'],
            ['nama' => 'Treatment Rambut Rontok',   'kategori' => 'Hair',           'deskripsi' => 'Treatment rambut rontok (1 jam).',                     'durasi_menit' => 60,  'harga' => 150000, 'ikon' => 'bi-shield'],
            ['nama' => 'Hair Filler Keratin',       'kategori' => 'Hair',           'deskripsi' => 'Hair filler keratin (±2 jam).',                        'durasi_menit' => 120, 'harga' => 450000, 'ikon' => 'bi-droplet-fill'],
            ['nama' => 'Hair Color',                'kategori' => 'Hair',           'deskripsi' => 'Pewarnaan rambut sesuai konsultasi (1–4 jam).',         'durasi_menit' => 120, 'harga' => 350000, 'ikon' => 'bi-palette-fill'],

            // — Make Up —
            ['nama' => 'Make Up',                   'kategori' => 'Make Up',        'deskripsi' => 'Make up profesional untuk acara spesial (±90 menit).', 'durasi_menit' => 90,  'harga' => 300000, 'ikon' => 'bi-stars'],
        ];

        $rows = [];
        foreach ($layanan as $l) {
            $rows[] = $l + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->db->table('layanan')->insertBatch($rows);

        $defaults = [
            'nama_salon' => 'SW Beauty Salon',
            'alamat_salon' => 'Batunya, Kec. Baturiti, Kabupaten Tabanan, Bali 82191',
            'telp_salon' => '+62 878-6218-3074',
            'nomor_hp_owner' => '6287862183074',
            'jam_buka' => '08:00',
            'jam_tutup' => '19:00',
            'slot_durasi_menit' => '30',
            'range_hari_booking' => '7',
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
