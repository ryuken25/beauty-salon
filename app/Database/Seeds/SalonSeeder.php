<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Demo data seeder. Idempotent: kalau owner@swbeautysalon.local sudah ada,
 * seeder langsung return. Reset penuh dengan `php spark migrate:refresh` lalu seed lagi.
 * Menyediakan:
 *   - 1 pemilik + 1 admin + 1 pelanggan utama (password = Password123!)
 *   - 8 layanan (tiap kategori)
 *   - settings (nama_salon, jam_operasional, template_wa, dst.)
 *   - 7 bookings spesifik (mixed statuses untuk verifikasi, reminder, dll.)
 *   - Sedikit data transaksi historis dalam 14 hari terakhir untuk mengisi grafik (tanpa flood).
 */
class SalonSeeder extends Seeder
{
    public function run()
    {
        // ── Idempotent guard ─────────────────────────────────────
        if ((int) $this->db->table('users')->where('email', 'owner@swbeautysalon.local')->countAllResults() > 0) {
            if (class_exists(\CodeIgniter\CLI\CLI::class)) {
                \CodeIgniter\CLI\CLI::write('Data demo sudah ada — seeder dilewati (idempotent). Pakai "php spark migrate:refresh" lalu seed ulang untuk reset.', 'yellow');
            }
            return;
        }

        $now = date('Y-m-d H:i:s');
        $hash = password_hash('Password123!', PASSWORD_BCRYPT);
        $customerHash = password_hash('123123123', PASSWORD_BCRYPT);

        // ── Users ────────────────────────────────────────────────
        $userRows = [
            ['email' => 'owner@swbeautysalon.local', 'password_hash' => $hash, 'nama' => 'Ni Wayan Sutrisna Wati', 'nomor_hp' => null, 'role' => 'pemilik',   'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'admin@swbeautysalon.local', 'password_hash' => $hash, 'nama' => 'Admin Salon',            'nomor_hp' => null, 'role' => 'admin',     'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Pelanggan demo utama (login pakai nomor WA / email + 123123123).
            ['email' => 'kadeknadi98@gmail.com', 'password_hash' => $customerHash, 'nama' => 'I Kadek nadi Artana', 'nomor_hp' => '6281234567890', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('users')->insertBatch($userRows);

        // ── Layanan (8 Layanan Realistis) ────────────────────────
        $layanan = [
            ['nama' => 'Nail Art',                  'kategori' => 'Nails',   'deskripsi' => 'Dekorasi kuku artistik (30–90 menit).',                'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-palette'],
            ['nama' => 'Eyelash Extension',         'kategori' => 'Lashes',  'deskripsi' => 'Pemasangan bulu mata premium (30–60 menit).',          'durasi_menit' => 60,  'harga' => 200000, 'ikon' => 'bi-eye'],
            ['nama' => 'Shaping Alis',              'kategori' => 'Brow',    'deskripsi' => 'Pembentukan alis natural (kurang lebih 5 menit).',     'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-eye-fill'],
            ['nama' => 'Sulam Alis',                'kategori' => 'Sulam',   'deskripsi' => 'Sulam alis natural teknik premium (±3 jam).',          'durasi_menit' => 180, 'harga' => 450000, 'ikon' => 'bi-pen'],
            ['nama' => 'Facial',                    'kategori' => 'Facial',  'deskripsi' => 'Perawatan wajah signature (30–60 menit).',             'durasi_menit' => 60,  'harga' => 180000, 'ikon' => 'bi-flower1'],
            ['nama' => 'Keramas',                   'kategori' => 'Hair',    'deskripsi' => 'Cuci rambut + pijat kepala (±30 menit).',              'durasi_menit' => 30,  'harga' => 35000,  'ikon' => 'bi-droplet-half'],
            ['nama' => 'Hair Spa',                  'kategori' => 'Hair',    'deskripsi' => 'Hair spa premium (90 menit).',                         'durasi_menit' => 90,  'harga' => 150000, 'ikon' => 'bi-gem'],
            ['nama' => 'Make Up',                   'kategori' => 'Make Up', 'deskripsi' => 'Make up profesional untuk acara spesial (±90 menit).', 'durasi_menit' => 90,  'harga' => 300000, 'ikon' => 'bi-stars'],
        ];
        $rows = [];
        foreach ($layanan as $l) {
            $rows[] = $l + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->db->table('layanan')->insertBatch($rows);
        
        // Map nama → id for the booking rows.
        $layananByNama = [];
        foreach ($this->db->table('layanan')->get()->getResultArray() as $l) {
            $layananByNama[$l['nama']] = $l;
        }

        // ── Promo (1 Aktif & 1 Expired untuk Testing) ─────────────
        $promos = [
            'Facial'             => [20, 'Promo Facial Spesial!'],
        ];
        
        $uploadDir = FCPATH . 'uploads/layanan';
        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
            @file_put_contents($uploadDir . '/index.html', '');
        }
        $gdAvailable = function_exists('imagecreatetruecolor') && function_exists('imagejpeg');

        foreach ($layananByNama as $nama => $l) {
            $update = [];

            // Set promo aktif
            if (isset($promos[$nama])) {
                [$persen, $deskripsi] = $promos[$nama];
                $update['promo_persen'] = (int) $persen;
                $update['promo_deskripsi'] = $deskripsi;
                $update['promo_mulai'] = date('Y-m-d');
                $update['promo_selesai'] = date('Y-m-d', strtotime('+30 days'));
            }

            // Set 1 promo kedaluwarsa untuk 'Hair Spa' (Target 1 testing)
            if ($nama === 'Hair Spa') {
                $update['promo_persen'] = 15;
                $update['promo_deskripsi'] = 'Diskon Relaksasi Expired';
                $update['promo_mulai'] = date('Y-m-d', strtotime('-10 days'));
                $update['promo_selesai'] = date('Y-m-d', strtotime('-1 day'));
            }

            // Foto placeholder jika GD tersedia
            if ($gdAvailable) {
                $fname = $this->generatePlaceholderImage($uploadDir, $nama, 1);
                if ($fname !== null) {
                    $update['gambar'] = \App\Models\LayananModel::encodeGambar(['uploads/layanan/' . $fname]);
                }
            }

            if ($update) {
                $this->db->table('layanan')->where('id', (int) $l['id'])->update($update);
            }
        }

        // ── Settings ─────────────────────────────────────────────
        $defaults = [
            'nama_salon' => 'SW Beauty Salon',
            'alamat_salon' => 'Batunya, Kec. Baturiti, Kabupaten Tabanan, Bali 82191',
            'telp_salon' => '+62 878-6218-3074',
            'nomor_hp_owner' => '6287862183074',
            'jam_buka' => '08:00',
            'jam_tutup' => '19:00',
            'slot_durasi_menit' => '30',
            'range_hari_booking' => '7',
            'info_pembayaran_dp' => "Transfer DP ke BCA 1234567890 a.n. SW Beauty Salon.\nOr scan QRIS at front desk, upload receipt below.",
            'template_wa_diterima' => 'Halo {nama}, booking Anda {kode} untuk {layanan} pada {tanggal} jam {jam_mulai} sudah kami terima. Sampai jumpa di SW Beauty Salon.',
            'template_wa_ditolak' => 'Halo {nama}, mohon maaf booking {kode} untuk {layanan} pada {tanggal} belum dapat kami terima. Silakan pilih jadwal lain. Terima kasih.',
            'template_wa_reminder' => 'Halo {nama}, pengingat jadwal Anda di SW Beauty Salon hari ini jam {jam_mulai} untuk {layanan}. Sampai jumpa.',
            'template_wa_selesai' => 'Terima kasih {nama} sudah datang ke SW Beauty Salon. Semoga puas dengan layanan kami.',
        ];
        $sets = [];
        foreach ($defaults as $k => $v) {
            $sets[] = ['key_name' => $k, 'value' => $v, 'updated_at' => $now];
        }
        $this->db->table('settings')->insertBatch($sets);

        // Summary
        if (class_exists(\CodeIgniter\CLI\CLI::class)) {
            \CodeIgniter\CLI\CLI::newLine();
            \CodeIgniter\CLI\CLI::write('Seeder database salon berhasil dijalankan (tanpa data history dummy).', 'green');
            \CodeIgniter\CLI\CLI::newLine();
        }
    }

    /**
     * Generate placeholder JPG 800x600 via GD
     */
    private function generatePlaceholderImage(string $dir, string $namaLayanan, int $idx): ?string
    {
        $im = @imagecreatetruecolor(800, 600);
        if (! $im) return null;
        $bg = imagecolorallocate($im, 20, 17, 15);            // #14110F
        $gold = imagecolorallocate($im, 201, 166, 107);        // #C9A66B
        $champagne = imagecolorallocate($im, 232, 213, 168);   // #E8D5A8
        imagefilledrectangle($im, 0, 0, 800, 600, $bg);

        // Ornament
        imagefilledrectangle($im, 200, 200, 600, 202, $gold);
        imagefilledrectangle($im, 200, 398, 600, 400, $gold);

        imagestring($im, 5, 320, 280, $namaLayanan, $champagne);
        imagestring($im, 3, 280, 330, 'SW Beauty Salon - foto ' . $idx, $gold);

        $fname = 'seed-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $namaLayanan)) . '-' . $idx . '.jpg';
        $ok = @imagejpeg($im, $dir . '/' . $fname, 85);
        imagedestroy($im);
        return $ok ? $fname : null;
    }
}
