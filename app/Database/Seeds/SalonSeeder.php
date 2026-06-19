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

        // ── Users ────────────────────────────────────────────────
        $userRows = [
            ['email' => 'owner@swbeautysalon.local', 'password_hash' => $hash, 'nama' => 'Ni Wayan Sutrisna Wati', 'nomor_hp' => null, 'role' => 'pemilik',   'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'admin@swbeautysalon.local', 'password_hash' => $hash, 'nama' => 'Admin Salon',            'nomor_hp' => null, 'role' => 'admin',     'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Pelanggan demo utama (login pakai nomor WA + Password123!).
            ['email' => 'winayagatar@gmail.com', 'password_hash' => $hash, 'nama' => 'I Made Winayagatar',  'nomor_hp' => '6281338109102', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('users')->insertBatch($userRows);
        $pelangganIds = [];
        foreach ($this->db->table('users')->where('role', 'pelanggan')->orderBy('id')->get()->getResultArray() as $u) {
            $pelangganIds[$u['nomor_hp']] = (int) $u['id'];
        }

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

        // Refresh map
        $layananByNama = [];
        foreach ($this->db->table('layanan')->get()->getResultArray() as $l) {
            $layananByNama[$l['nama']] = $l;
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

        // ── Bookings & Transaksi Spesifik ────────────────────────
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayAfter = date('Y-m-d', strtotime('+2 days'));
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));

        $dpFor = static fn (int $h): int => min($h, 50_000);
        $kode = static fn (string $date, int $seq): string => 'SW-' . str_replace('-', '', $date) . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        $dbNow = $this->db->query('SELECT DATE_ADD(NOW(), INTERVAL 25 MINUTE) t')->getRowArray()['t'];
        $reminderTanggal = substr((string) $dbNow, 0, 10);
        $reminderMulai = substr((string) $dbNow, 11, 8);
        $reminderSelesai = date('H:i:s', strtotime((string) $dbNow) + 30 * 60);

        $bookings = [
            // 1. Pending — tomorrow, Keramas
            ['user' => '6281338109102', 'layanan' => 'Keramas',             'tanggal' => $tomorrow,        'mulai' => '10:00:00',     'selesai' => '10:30:00',     'slot' => 1, 'status' => 'pending_verification', 'pay' => 'dp_uploaded', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 2. Accepted — tomorrow, Facial
            ['user' => '6281338109102', 'layanan' => 'Facial',              'tanggal' => $tomorrow,        'mulai' => '13:00:00',     'selesai' => '14:00:00',     'slot' => 2, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 2],
            // 3. Accepted — day after, Hair Spa
            ['user' => '6281338109102', 'layanan' => 'Hair Spa',            'tanggal' => $dayAfter,        'mulai' => '15:00:00',     'selesai' => '16:30:00',     'slot' => 3, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 4. Completed — last week (Make Up)
            ['user' => '6281338109102', 'layanan' => 'Make Up',             'tanggal' => $oneWeekAgo,      'mulai' => '08:00:00',     'selesai' => '09:30:00',     'slot' => 3, 'status' => 'completed',            'pay' => 'dp_verified', 'proof' => null, 'seq' => 1],
            // 5. Cancelled — today
            ['user' => '6281338109102', 'layanan' => 'Keramas',             'tanggal' => $today,           'mulai' => '11:00:00',     'selesai' => '11:30:00',     'slot' => 1, 'status' => 'cancelled',            'pay' => 'unpaid',      'proof' => null, 'seq' => 1],
            // 6. Pending kemarin — kandidat auto-cancel
            ['user' => '6281338109102', 'layanan' => 'Shaping Alis',        'tanggal' => $yesterday,       'mulai' => '12:00:00',     'selesai' => '12:30:00',     'slot' => 1, 'status' => 'pending_verification', 'pay' => 'dp_uploaded', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 7. Accepted starting +25 min from NOW() — kandidat reminder
            ['user' => '6281338109102', 'layanan' => 'Keramas',             'tanggal' => $reminderTanggal, 'mulai' => $reminderMulai, 'selesai' => $reminderSelesai, 'slot' => 1, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 9],
        ];

        $emailForPhone = [
            '6281338109102' => 'winayagatar@gmail.com',
        ];

        foreach ($bookings as $b) {
            $l = $layananByNama[$b['layanan']];
            $harga = \App\Models\LayananModel::hargaFinal($l);
            $kodeStr = $kode($b['tanggal'], $b['seq']);
            $dpAmt = $dpFor($harga);
            
            $this->db->table('bookings')->insert([
                'kode_booking' => $kodeStr,
                'user_id' => $pelangganIds[$b['user']] ?? null,
                'nama_pelanggan' => $this->namaForPhone($b['user']),
                'nomor_hp_pelanggan' => $b['user'],
                'email_pelanggan' => $emailForPhone[$b['user']] ?? null,
                'layanan_id' => (int) $l['id'],
                'tanggal' => $b['tanggal'],
                'slot_mulai' => $b['mulai'],
                'slot_selesai' => $b['selesai'],
                'jumlah_slot' => $b['slot'],
                'harga_layanan' => $harga,
                'dp_amount' => $dpAmt,
                'dp_proof_path' => $b['proof'],
                'payment_status' => $b['pay'],
                'status' => $b['status'],
                'sumber' => 'online',
                'catatan' => null,
                'verified_via' => $b['status'] === 'accepted' || $b['status'] === 'completed' ? 'dashboard:seeder' : null,
                'verified_at' => $b['status'] === 'accepted' || $b['status'] === 'completed' ? $now : null,
                'completed_at' => $b['status'] === 'completed' ? $now : null,
                'cancelled_at' => $b['status'] === 'cancelled' ? $now : null,
                'cancelled_by' => $b['status'] === 'cancelled' ? 'pelanggan' : null,
                'cancellation_reason' => $b['status'] === 'cancelled' ? 'Ada urusan mendadak.' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $bookingId = (int) $this->db->insertID();

            // Hold slots for non-final
            if (in_array($b['status'], ['pending_verification', 'accepted'], true)) {
                $startMin = (int) substr((string) $b['mulai'], 0, 2) * 60 + (int) substr((string) $b['mulai'], 3, 2);
                for ($i = 0; $i < $b['slot']; $i++) {
                    $m = $startMin + 30 * $i;
                    $slotStr = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
                    $this->db->table('booking_slots')->insert([
                        'booking_id' => $bookingId,
                        'tanggal' => $b['tanggal'],
                        'slot_waktu' => $slotStr,
                        'status' => 'held',
                        'created_at' => $now,
                    ]);
                }
            }

            // Transaksi for completed
            if ($b['status'] === 'completed') {
                $this->db->table('transaksi')->insert([
                    'booking_id' => $bookingId,
                    'nominal' => $harga,
                    'base_price' => $harga,
                    'additional_price' => 0,
                    'dp_paid' => $dpAmt,
                    'sisa_bayar' => max(0, $harga - $dpAmt),
                    'metode_bayar' => 'cash',
                    'tanggal_transaksi' => $b['tanggal'] . ' 14:00:00',
                    'catatan' => 'Seeded sample transaction.',
                    'created_at' => $now,
                ]);
            }
        }

        // ── Ringkas Laporan Historis (Hanya ~5 bookings completed dalam 14 hari) ──
        $phones = array_keys($pelangganIds);
        if ($phones) {
            $layananArr = array_values($layananByNama);
            $metode = ['cash', 'transfer', 'qris'];

            $usedSlotsByDate = [];
            $usedKodeSeqByDate = [];

            $insertFiller = function (string $tanggal, int $startMin, array $l, string $phone, string $status, ?string $metodeBayar = null) use (
                $kode, $emailForPhone, $now, &$usedSlotsByDate, &$usedKodeSeqByDate, $dpFor
            ): bool {
                $slotsNeeded = max(1, (int) ceil((int) $l['durasi_menit'] / 30));
                
                $seq = ($usedKodeSeqByDate[$tanggal] ?? 100) + 1;
                $usedKodeSeqByDate[$tanggal] = $seq;
                $startTime = sprintf('%02d:%02d:00', intdiv($startMin, 60), $startMin % 60);
                $endMin = $startMin + ($slotsNeeded * 30);
                $endTime = sprintf('%02d:%02d:00', intdiv($endMin, 60), $endMin % 60);

                $harga = \App\Models\LayananModel::hargaFinal($l);
                $dp = $dpFor($harga);

                $row = [
                    'kode_booking' => $kode($tanggal, $seq),
                    'user_id' => null,
                    'nama_pelanggan' => $this->namaForPhone($phone),
                    'nomor_hp_pelanggan' => $phone,
                    'email_pelanggan' => $emailForPhone[$phone] ?? null,
                    'layanan_id' => (int) $l['id'],
                    'tanggal' => $tanggal,
                    'slot_mulai' => $startTime,
                    'slot_selesai' => $endTime,
                    'jumlah_slot' => $slotsNeeded,
                    'harga_layanan' => $harga,
                    'dp_amount' => $dp,
                    'dp_proof_path' => null,
                    'payment_status' => 'dp_verified',
                    'status' => $status,
                    'sumber' => 'online',
                    'catatan' => null,
                    'verified_via' => 'dashboard:seeder',
                    'verified_at' => $tanggal . ' ' . $startTime,
                    'completed_at' => $tanggal . ' ' . $endTime,
                    'created_at' => $tanggal . ' ' . $startTime,
                    'updated_at' => $now,
                ];
                $this->db->table('bookings')->insert($row);
                $bookingId = (int) $this->db->insertID();

                if ($status === 'completed') {
                    $jamTrans = sprintf('%02d:%02d:00', max(9, intdiv($startMin, 60)) + (intdiv($startMin, 60) % 3), 0);
                    $this->db->table('transaksi')->insert([
                        'booking_id' => $bookingId,
                        'nominal' => $harga,
                        'base_price' => $harga,
                        'additional_price' => 0,
                        'dp_paid' => $dp,
                        'sisa_bayar' => max(0, $harga - $dp),
                        'metode_bayar' => $metodeBayar ?? 'cash',
                        'tanggal_transaksi' => $tanggal . ' ' . $jamTrans,
                        'catatan' => null,
                        'created_at' => $now,
                    ]);
                }
                return true;
            };

            // Booking historis: 5 booking saja tersebar dalam 10 hari terakhir
            $fillDays = [2, 4, 6, 8, 10];
            foreach ($fillDays as $i => $daysAgo) {
                $tgl = date('Y-m-d', strtotime("-{$daysAgo} days"));
                $l = $layananArr[$i % count($layananArr)];
                $phone = $phones[0];
                $startMin = (9 + ($daysAgo % 5)) * 60; // 09:00 s/d 13:00
                $insertFiller($tgl, $startMin, $l, $phone, 'completed', $metode[$i % 3]);
            }
        }

        // Summary
        $summary = $this->db->table('bookings')->select('kode_booking, status, email_pelanggan')->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();
        if (class_exists(\CodeIgniter\CLI\CLI::class)) {
            \CodeIgniter\CLI\CLI::newLine();
            \CodeIgniter\CLI\CLI::write('Booking hasil seeder ringkas:', 'cyan');
            foreach ($summary as $row) {
                \CodeIgniter\CLI\CLI::write(sprintf('  %-22s %-22s %s', $row['kode_booking'], $row['status'], $row['email_pelanggan'] ?? '-'));
            }
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

    private function namaForPhone(string $phone): string
    {
        $map = [
            '6281338109102' => 'I Made Winayagatar',
        ];
        return $map[$phone] ?? 'Pelanggan';
    }
}
