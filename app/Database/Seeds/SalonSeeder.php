<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Demo data seeder. Idempotent? No — it assumes a fresh schema (run after
 * migrate:refresh). Produces:
 *   - 1 pemilik + 1 admin + 5 pelanggan accounts (password = Password123!)
 *   - 24 layanan
 *   - settings (nama_salon, jam_*, info_pembayaran_dp, dst.)
 *   - 6 bookings with mixed statuses (incl. one yesterday-pending for the
 *     auto-cancel demo), held slots, and a transaksi row for the completed one.
 */
class SalonSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $hash = password_hash('Password123!', PASSWORD_BCRYPT);

        // ── Users ────────────────────────────────────────────────
        $userRows = [
            ['email' => 'owner@swbeautysalon.local', 'password_hash' => $hash, 'nama' => 'Ni Wayan Sutrisna Wati', 'nomor_hp' => null, 'role' => 'pemilik',   'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'admin@swbeautysalon.local', 'password_hash' => $hash, 'nama' => 'Admin Salon',            'nomor_hp' => null, 'role' => 'admin',     'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Pelanggan demo (login pakai nomor WA + Password123!).
            ['email' => 'winayagatar@gmail.com', 'password_hash' => $hash, 'nama' => 'I Made Winayagatar',  'nomor_hp' => '6281338109102', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'pramesti.demo@gmail.com', 'password_hash' => $hash, 'nama' => 'Putu Ayu Pramesti',   'nomor_hp' => '6281234567001', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'wahyuni.demo@gmail.com', 'password_hash' => $hash, 'nama' => 'Kadek Sri Wahyuni',   'nomor_hp' => '6281234567002', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'aristina.demo@gmail.com', 'password_hash' => $hash, 'nama' => 'Ni Komang Aristina',  'nomor_hp' => '6281234567003', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => 'bayu.demo@gmail.com', 'password_hash' => $hash, 'nama' => 'Made Bayu Sentana',   'nomor_hp' => '6281234567004', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('users')->insertBatch($userRows);
        $pelangganIds = [];
        foreach ($this->db->table('users')->where('role', 'pelanggan')->orderBy('id')->get()->getResultArray() as $u) {
            $pelangganIds[$u['nomor_hp']] = (int) $u['id'];
        }

        // ── Layanan ──────────────────────────────────────────────
        $layanan = [
            ['nama' => 'Nail Art',                  'kategori' => 'Nails',   'deskripsi' => 'Dekorasi kuku artistik (30–90 menit).',                'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-palette'],
            ['nama' => 'Manicure / Pedicure',       'kategori' => 'Nails',   'deskripsi' => 'Perawatan tangan & kuku elegan.',                      'durasi_menit' => 30,  'harga' => 80000,  'ikon' => 'bi-hand-index'],
            ['nama' => 'Callus Removal',            'kategori' => 'Nails',   'deskripsi' => 'Pengangkatan kapalan & kulit mati pada kaki.',         'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-droplet-half'],
            ['nama' => 'Eyelash Extension',         'kategori' => 'Lashes',  'deskripsi' => 'Pemasangan bulu mata premium (30–60 menit).',          'durasi_menit' => 60,  'harga' => 200000, 'ikon' => 'bi-eye'],
            ['nama' => 'Shaping Alis',              'kategori' => 'Brow',    'deskripsi' => 'Pembentukan alis natural (kurang lebih 5 menit).',     'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-eye-fill'],
            ['nama' => 'Sulam Alis',                'kategori' => 'Sulam',   'deskripsi' => 'Sulam alis natural teknik premium (±3 jam).',          'durasi_menit' => 180, 'harga' => 450000, 'ikon' => 'bi-pen'],
            ['nama' => 'Sulam Bibir',               'kategori' => 'Sulam',   'deskripsi' => 'Sulam bibir premium (±2 jam).',                        'durasi_menit' => 120, 'harga' => 400000, 'ikon' => 'bi-emoji-kiss'],
            ['nama' => 'IPL Treatment',             'kategori' => 'Body',    'deskripsi' => 'Perawatan IPL kulit.',                                 'durasi_menit' => 30,  'harga' => 250000, 'ikon' => 'bi-lightning'],
            ['nama' => 'Waxing + Detox Underarm',   'kategori' => 'Body',    'deskripsi' => 'Waxing ketiak + detox (1 jam).',                       'durasi_menit' => 60,  'harga' => 100000, 'ikon' => 'bi-droplet'],
            ['nama' => 'Wax Kaki / Tangan',         'kategori' => 'Body',    'deskripsi' => 'Hair removal kaki atau tangan (30 menit).',            'durasi_menit' => 30,  'harga' => 80000,  'ikon' => 'bi-droplet'],
            ['nama' => 'Facial',                    'kategori' => 'Facial',  'deskripsi' => 'Perawatan wajah signature (30–60 menit).',             'durasi_menit' => 60,  'harga' => 180000, 'ikon' => 'bi-flower1'],
            ['nama' => 'Keramas',                   'kategori' => 'Hair',    'deskripsi' => 'Cuci rambut + pijat kepala (±30 menit).',              'durasi_menit' => 30,  'harga' => 35000,  'ikon' => 'bi-droplet-half'],
            ['nama' => 'Masker Bilas',              'kategori' => 'Hair',    'deskripsi' => 'Masker rambut bilas (±30 menit).',                     'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-droplet'],
            ['nama' => 'Catok / Styling',           'kategori' => 'Hair',    'deskripsi' => 'Catok / styling rambut (±30 menit).',                  'durasi_menit' => 30,  'harga' => 50000,  'ikon' => 'bi-magic'],
            ['nama' => 'Masker Steam',              'kategori' => 'Hair',    'deskripsi' => 'Masker rambut + steam (1 jam).',                       'durasi_menit' => 60,  'harga' => 80000,  'ikon' => 'bi-cloud'],
            ['nama' => 'Creambath',                 'kategori' => 'Hair',    'deskripsi' => 'Creambath relax (90 menit).',                          'durasi_menit' => 90,  'harga' => 100000, 'ikon' => 'bi-stars'],
            ['nama' => 'Hair Spa',                  'kategori' => 'Hair',    'deskripsi' => 'Hair spa premium (90 menit).',                         'durasi_menit' => 90,  'harga' => 150000, 'ikon' => 'bi-gem'],
            ['nama' => 'Smoothing',                 'kategori' => 'Hair',    'deskripsi' => 'Smoothing rambut (3–6 jam).',                          'durasi_menit' => 240, 'harga' => 600000, 'ikon' => 'bi-scissors'],
            ['nama' => 'Blow Permanent',            'kategori' => 'Hair',    'deskripsi' => 'Blow permanent (4–6 jam).',                            'durasi_menit' => 300, 'harga' => 700000, 'ikon' => 'bi-wind'],
            ['nama' => 'Treatment Anti Ketombe',    'kategori' => 'Hair',    'deskripsi' => 'Treatment anti ketombe (90 menit).',                   'durasi_menit' => 90,  'harga' => 150000, 'ikon' => 'bi-shield-check'],
            ['nama' => 'Treatment Rambut Rontok',   'kategori' => 'Hair',    'deskripsi' => 'Treatment rambut rontok (1 jam).',                     'durasi_menit' => 60,  'harga' => 150000, 'ikon' => 'bi-shield'],
            ['nama' => 'Hair Filler Keratin',       'kategori' => 'Hair',    'deskripsi' => 'Hair filler keratin (±2 jam).',                        'durasi_menit' => 120, 'harga' => 450000, 'ikon' => 'bi-droplet-fill'],
            ['nama' => 'Hair Color',                'kategori' => 'Hair',    'deskripsi' => 'Pewarnaan rambut sesuai konsultasi (1–4 jam).',        'durasi_menit' => 120, 'harga' => 350000, 'ikon' => 'bi-palette-fill'],
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

        // ── Promo + galeri (kolom JSON, tanpa tabel baru) ────────
        $promos = [
            'Facial'             => [20, 'Promo bulan ini!'],
            'Hair Spa'           => [15, 'Spa relaks weekend'],
            'Creambath'          => [25, 'Spesial pelanggan baru'],
            'Make Up'            => [10, 'Diskon event'],
            'Nail Art'           => [30, 'Promo flash 7 hari'],
            'Manicure / Pedicure'=> [15, ''],
        ];
        $galleryFor = ['Facial', 'Hair Spa', 'Creambath', 'Make Up', 'Nail Art', 'Sulam Alis', 'Eyelash Extension'];
        $uploadDir = FCPATH . 'uploads/layanan';
        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
            @file_put_contents($uploadDir . '/index.html', '');
        }
        $gdAvailable = function_exists('imagecreatetruecolor') && function_exists('imagejpeg');

        $seedDir = FCPATH . 'uploads/layanan/seed';
        if (! is_dir($seedDir)) {
            @mkdir($seedDir, 0775, true);
        }
        $slugify = static fn (string $s) => trim(preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($s)), '-');

        foreach ($layananByNama as $nama => $l) {
            $update = [];

            // Promo — rentang default 30 hari ke depan supaya demo bisa lihat
            // "Berlaku 1 Jun – 30 Jun" di popup & detail.
            if (isset($promos[$nama])) {
                [$persen, $deskripsi] = $promos[$nama];
                $update['promo_persen'] = (int) $persen;
                $update['promo_deskripsi'] = $deskripsi !== '' ? $deskripsi : null;
                $update['promo_mulai'] = date('Y-m-d');
                $update['promo_selesai'] = date('Y-m-d', strtotime('+30 days'));
            }

            // Foto real dari uploads/layanan/seed/<slug>.jpg.
            // Gallery → s/d 3 foto, lainnya → 1 cover. Fallback placeholder
            // GD bila file tak ada (seeder tetap jalan walau foto belum di-download).
            $slug = $slugify($nama);
            $maxImg = in_array($nama, $galleryFor, true) ? 3 : 1;
            $paths = [];
            for ($i = 1; $i <= $maxImg; $i++) {
                $fname = $i === 1 ? "{$slug}.jpg" : "{$slug}-{$i}.jpg";
                if (is_file($seedDir . '/' . $fname)) {
                    $paths[] = 'uploads/layanan/seed/' . $fname;
                }
            }
            if (! $paths && $gdAvailable) {
                $fname = $this->generatePlaceholderImage($uploadDir, $nama, 1);
                if ($fname !== null) {
                    $paths[] = 'uploads/layanan/' . $fname;
                }
            }
            if ($paths) {
                $update['gambar'] = \App\Models\LayananModel::encodeGambar($paths);
            }

            if ($update) {
                $this->db->table('layanan')->where('id', (int) $l['id'])->update($update);
            }
        }
        if (! $gdAvailable) {
            log_message('warning', 'GD extension tidak tersedia — galeri placeholder dilewati.');
        }
        // Refresh map untuk dipakai hitung harga final di blok booking.
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
            'info_pembayaran_dp' => "Transfer DP ke BCA 1234567890 a.n. SW Beauty Salon.\nAtau scan QRIS yang ditempel di etalase salon, lalu upload bukti di bawah.",
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

        // ── Bookings (mixed statuses + DP rows) ──────────────────
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayAfter = date('Y-m-d', strtotime('+2 days'));
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));

        $dpFor = static fn (int $h): int => min($h, 50_000);
        $kode = static fn (string $date, int $seq): string => 'SW-' . str_replace('-', '', $date) . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        // Booking #7 (reminder fixture): accepted dengan slot_mulai +25 menit
        // dari NOW() MySQL → masuk window 30 menit (FASE 14C). Pakai DB clock
        // supaya konsisten dengan timezone server (PHP UTC vs MySQL lokal).
        $dbNow = $this->db->query('SELECT DATE_ADD(NOW(), INTERVAL 25 MINUTE) t')->getRowArray()['t'];
        $reminderTanggal = substr((string) $dbNow, 0, 10);
        $reminderMulai = substr((string) $dbNow, 11, 8);
        $reminderSelesai = date('H:i:s', strtotime((string) $dbNow) + 30 * 60);

        $bookings = [
            // 1. Pending — tomorrow, manicure 30min
            ['user' => '6281338109102', 'layanan' => 'Manicure / Pedicure', 'tanggal' => $tomorrow,        'mulai' => '10:00:00',     'selesai' => '10:30:00',     'slot' => 1, 'status' => 'pending_verification', 'pay' => 'dp_uploaded', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 2. Accepted — tomorrow, facial 60min
            ['user' => '6281234567001', 'layanan' => 'Facial',              'tanggal' => $tomorrow,        'mulai' => '13:00:00',     'selesai' => '14:00:00',     'slot' => 2, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 2],
            // 3. Accepted — day after, hair spa 90min
            ['user' => '6281234567002', 'layanan' => 'Hair Spa',            'tanggal' => $dayAfter,        'mulai' => '15:00:00',     'selesai' => '16:30:00',     'slot' => 3, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 4. Completed — last week
            ['user' => '6281234567003', 'layanan' => 'Make Up',             'tanggal' => $oneWeekAgo,      'mulai' => '08:00:00',     'selesai' => '09:30:00',     'slot' => 3, 'status' => 'completed',            'pay' => 'dp_verified', 'proof' => null, 'seq' => 1],
            // 5. Cancelled — today (history)
            ['user' => '6281234567004', 'layanan' => 'Keramas',             'tanggal' => $today,           'mulai' => '11:00:00',     'selesai' => '11:30:00',     'slot' => 1, 'status' => 'cancelled',            'pay' => 'unpaid',      'proof' => null, 'seq' => 1],
            // 6. Pending kemarin — kandidat auto-cancel (FASE 10 demo)
            ['user' => '6281234567001', 'layanan' => 'Catok / Styling',     'tanggal' => $yesterday,       'mulai' => '12:00:00',     'selesai' => '12:30:00',     'slot' => 1, 'status' => 'pending_verification', 'pay' => 'dp_uploaded', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 7. Accepted starting +25 min from NOW() — kandidat reminder (FASE 14C demo)
            ['user' => '6281338109102', 'layanan' => 'Keramas',             'tanggal' => $reminderTanggal, 'mulai' => $reminderMulai, 'selesai' => $reminderSelesai, 'slot' => 1, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 9],
        ];

        $emailForPhone = [
            '6281338109102' => 'winayagatar@gmail.com',
            '6281234567001' => 'pramesti.demo@gmail.com',
            '6281234567002' => 'wahyuni.demo@gmail.com',
            '6281234567003' => 'aristina.demo@gmail.com',
            '6281234567004' => 'bayu.demo@gmail.com',
        ];

        foreach ($bookings as $b) {
            $l = $layananByNama[$b['layanan']];
            $harga = \App\Models\LayananModel::hargaFinal($l);
            $kodeStr = $kode($b['tanggal'], $b['seq']);
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
                'dp_amount' => $dpFor($harga),
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

            // Hold slots for non-final statuses.
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

            // Transaksi for completed.
            if ($b['status'] === 'completed') {
                $this->db->table('transaksi')->insert([
                    'booking_id' => $bookingId,
                    'nominal' => $harga,
                    'base_price' => $harga,
                    'additional_price' => 0,
                    'metode_bayar' => 'cash',
                    'tanggal_transaksi' => $b['tanggal'] . ' 14:00:00',
                    'catatan' => 'Seeded sample transaction.',
                    'created_at' => $now,
                ]);
            }
        }

        // ── Multi-bulan filler: ~50 booking 3 bulan lalu s/d besok ─
        // Tujuan utama: data grafik laporan (mode bulan + prev/next, mode
        // hari per jam, mode minggu) ada isi. Pakai harga final (promo)
        // di harga_layanan/dp_amount/transaksi.nominal — konsisten dgn
        // BookingService::create.
        $phones = array_keys($pelangganIds);
        if ($phones) {
            $layananArr = array_values($layananByNama);
            $metode = ['cash', 'transfer', 'qris'];

            // Slot tracker per tanggal — anti double-booking.
            $usedSlotsByDate = [];
            $usedKodeSeqByDate = [];

            $insertFiller = function (string $tanggal, int $startMin, array $l, string $phone, string $status, ?string $metodeBayar = null) use (
                $kode, $emailForPhone, $now, &$usedSlotsByDate, &$usedKodeSeqByDate
            ): bool {
                $slotsNeeded = max(1, (int) ceil((int) $l['durasi_menit'] / 30));
                // Cek bentrok slot
                $used = $usedSlotsByDate[$tanggal] ?? [];
                for ($i = 0; $i < $slotsNeeded; $i++) {
                    $m = $startMin + 30 * $i;
                    if (in_array($m, $used, true)) return false;
                    if ($m + 30 > 19 * 60) return false; // jangan lewat tutup
                }
                // Tandai slot terpakai HANYA untuk status non-final
                if (in_array($status, ['pending_verification', 'accepted'], true)) {
                    for ($i = 0; $i < $slotsNeeded; $i++) {
                        $usedSlotsByDate[$tanggal][] = $startMin + 30 * $i;
                    }
                }

                $seq = ($usedKodeSeqByDate[$tanggal] ?? 100) + 1;
                $usedKodeSeqByDate[$tanggal] = $seq;
                $startTime = sprintf('%02d:%02d:00', intdiv($startMin, 60), $startMin % 60);
                $endMin = $startMin + ($slotsNeeded * 30);
                $endTime = sprintf('%02d:%02d:00', intdiv($endMin, 60), $endMin % 60);

                $harga = \App\Models\LayananModel::hargaFinal($l);
                $dp = min($harga, 50_000);
                $isFinal = in_array($status, ['rejected', 'cancelled', 'completed'], true);

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
                    'payment_status' => $status === 'completed' || $status === 'accepted' ? 'dp_verified' : 'unpaid',
                    'status' => $status,
                    'sumber' => 'online',
                    'catatan' => null,
                    'verified_via' => in_array($status, ['accepted', 'completed'], true) ? 'dashboard:seeder' : null,
                    'verified_at' => in_array($status, ['accepted', 'completed'], true) ? $tanggal . ' ' . $startTime : null,
                    'completed_at' => $status === 'completed' ? $tanggal . ' ' . $endTime : null,
                    'cancelled_at' => $status === 'cancelled' ? $tanggal . ' ' . $startTime : null,
                    'cancelled_by' => $status === 'cancelled' ? 'pelanggan' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Slot bentrok' : null,
                    'cancellation_reason' => $status === 'cancelled' ? 'Berhalangan hadir.' : null,
                    'created_at' => $tanggal . ' ' . $startTime,
                    'updated_at' => $now,
                ];
                $this->db->table('bookings')->insert($row);
                $bookingId = (int) $this->db->insertID();

                // Hold slots untuk non-final
                if (! $isFinal) {
                    for ($i = 0; $i < $slotsNeeded; $i++) {
                        $m = $startMin + 30 * $i;
                        $slotStr = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
                        $this->db->table('booking_slots')->insert([
                            'booking_id' => $bookingId,
                            'tanggal' => $tanggal,
                            'slot_waktu' => $slotStr,
                            'status' => 'held',
                            'created_at' => $now,
                        ]);
                    }
                }

                // Transaksi untuk completed
                if ($status === 'completed') {
                    $jamTrans = sprintf('%02d:%02d:00', max(9, intdiv($startMin, 60)) + (intdiv($startMin, 60) % 3), 0);
                    $this->db->table('transaksi')->insert([
                        'booking_id' => $bookingId,
                        'nominal' => $harga,
                        'base_price' => $harga,
                        'additional_price' => 0,
                        'metode_bayar' => $metodeBayar ?? 'cash',
                        'tanggal_transaksi' => $tanggal . ' ' . $jamTrans,
                        'catatan' => null,
                        'created_at' => $now,
                    ]);
                }
                return true;
            };

            // — Booking 3 bulan ke belakang (mayoritas completed) —
            $deterministic = 17;
            for ($daysAgo = 90; $daysAgo >= 2; $daysAgo--) {
                $tgl = date('Y-m-d', strtotime("-{$daysAgo} days"));
                // Skip Minggu kadang (buat variasi)
                if ((int) date('w', strtotime($tgl)) === 0 && ($daysAgo % 4) === 0) continue;
                // 0..2 booking per hari
                $jumlah = ($daysAgo * $deterministic) % 3;
                for ($k = 0; $k < $jumlah; $k++) {
                    $l = $layananArr[($daysAgo + $k * 7) % count($layananArr)];
                    $phone = $phones[($daysAgo + $k) % count($phones)];
                    // jam 09:00–17:30 tersebar
                    $startMin = (9 + (($daysAgo + $k * 3) % 9)) * 60;
                    // mayoritas completed (85%), sisanya cancelled/rejected
                    $r = ($daysAgo * 7 + $k) % 20;
                    $status = $r < 17 ? 'completed' : ($r < 19 ? 'cancelled' : 'rejected');
                    $insertFiller($tgl, $startMin, $l, $phone, $status, $metode[($daysAgo + $k) % 3]);
                }
            }

            // — Hari ini: 4–6 booking campur —
            $todayMix = [
                ['mulai' => 9 * 60,  'status' => 'completed'],
                ['mulai' => 10 * 60, 'status' => 'accepted'],
                ['mulai' => 13 * 60, 'status' => 'accepted'],
                ['mulai' => 14 * 60, 'status' => 'pending_verification'],
                ['mulai' => 16 * 60, 'status' => 'completed'],
            ];
            foreach ($todayMix as $i => $m) {
                $l = $layananArr[($i + 3) % count($layananArr)];
                $phone = $phones[$i % count($phones)];
                $insertFiller($today, $m['mulai'], $l, $phone, $m['status'], $metode[$i % 3]);
            }

            // — Besok: 3 pending + 2 accepted —
            $besokMix = [
                ['mulai' => 9 * 60,  'status' => 'pending_verification'],
                ['mulai' => 11 * 60, 'status' => 'accepted'],
                ['mulai' => 14 * 60, 'status' => 'pending_verification'],
                ['mulai' => 16 * 60, 'status' => 'accepted'],
                ['mulai' => 17 * 60, 'status' => 'pending_verification'],
            ];
            foreach ($besokMix as $i => $m) {
                $l = $layananArr[($i + 7) % count($layananArr)];
                $phone = $phones[($i + 1) % count($phones)];
                $insertFiller($tomorrow, $m['mulai'], $l, $phone, $m['status']);
            }
        }

        // ── Ringkasan kode booking untuk tester ──────────────────
        $summary = $this->db->table('bookings')->select('kode_booking, status, email_pelanggan')->orderBy('id', 'DESC')->limit(15)->get()->getResultArray();
        if (class_exists(\CodeIgniter\CLI\CLI::class)) {
            \CodeIgniter\CLI\CLI::newLine();
            \CodeIgniter\CLI\CLI::write('Booking terakhir (15) hasil seeder:', 'cyan');
            foreach ($summary as $row) {
                \CodeIgniter\CLI\CLI::write(sprintf('  %-22s %-22s %s', $row['kode_booking'], $row['status'], $row['email_pelanggan'] ?? '-'));
            }
            \CodeIgniter\CLI\CLI::newLine();
        }
    }

    /**
     * Generate placeholder JPG 800x600 (onyx bg + gold text) via GD untuk
     * layanan promo. Kembalikan nama file relatif, atau null kalau gagal.
     */
    private function generatePlaceholderImage(string $dir, string $namaLayanan, int $idx): ?string
    {
        $im = @imagecreatetruecolor(800, 600);
        if (! $im) return null;
        $bg = imagecolorallocate($im, 20, 17, 15);            // #14110F
        $gold = imagecolorallocate($im, 201, 166, 107);        // #C9A66B
        $champagne = imagecolorallocate($im, 232, 213, 168);   // #E8D5A8
        imagefilledrectangle($im, 0, 0, 800, 600, $bg);

        // Ornament rule horizontal
        imagefilledrectangle($im, 200, 200, 600, 202, $gold);
        imagefilledrectangle($im, 200, 398, 600, 400, $gold);

        $title = $namaLayanan;
        imagestring($im, 5, 320, 280, $title, $champagne);
        imagestring($im, 3, 280, 330, 'SW Beauty Salon - foto ' . $idx, $gold);

        $fname = 'seed-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $namaLayanan)) . '-' . $idx . '-' . substr(md5($namaLayanan . $idx), 0, 6) . '.jpg';
        $ok = @imagejpeg($im, $dir . '/' . $fname, 85);
        imagedestroy($im);
        return $ok ? $fname : null;
    }

    private function namaForPhone(string $phone): string
    {
        $map = [
            '6281338109102' => 'I Made Winayagatar',
            '6281234567001' => 'Putu Ayu Pramesti',
            '6281234567002' => 'Kadek Sri Wahyuni',
            '6281234567003' => 'Ni Komang Aristina',
            '6281234567004' => 'Made Bayu Sentana',
        ];
        return $map[$phone] ?? 'Pelanggan';
    }
}
