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
            ['email' => null, 'password_hash' => $hash, 'nama' => 'I Made Winayagatar',  'nomor_hp' => '6281338109102', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => null, 'password_hash' => $hash, 'nama' => 'Putu Ayu Pramesti',   'nomor_hp' => '6281234567001', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => null, 'password_hash' => $hash, 'nama' => 'Kadek Sri Wahyuni',   'nomor_hp' => '6281234567002', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => null, 'password_hash' => $hash, 'nama' => 'Ni Komang Aristina',  'nomor_hp' => '6281234567003', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['email' => null, 'password_hash' => $hash, 'nama' => 'Made Bayu Sentana',   'nomor_hp' => '6281234567004', 'role' => 'pelanggan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
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

        $bookings = [
            // 1. Pending — tomorrow, manicure 30min
            ['user' => '6281338109102', 'layanan' => 'Manicure / Pedicure', 'tanggal' => $tomorrow,   'mulai' => '10:00:00', 'selesai' => '10:30:00', 'slot' => 1, 'status' => 'pending_verification', 'pay' => 'dp_uploaded', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 2. Accepted — tomorrow, facial 60min
            ['user' => '6281234567001', 'layanan' => 'Facial',              'tanggal' => $tomorrow,   'mulai' => '13:00:00', 'selesai' => '14:00:00', 'slot' => 2, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 2],
            // 3. Accepted — day after, hair spa 90min
            ['user' => '6281234567002', 'layanan' => 'Hair Spa',            'tanggal' => $dayAfter,   'mulai' => '15:00:00', 'selesai' => '16:30:00', 'slot' => 3, 'status' => 'accepted',             'pay' => 'dp_verified', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
            // 4. Completed — last week
            ['user' => '6281234567003', 'layanan' => 'Make Up',             'tanggal' => $oneWeekAgo, 'mulai' => '08:00:00', 'selesai' => '09:30:00', 'slot' => 3, 'status' => 'completed',            'pay' => 'dp_verified', 'proof' => null, 'seq' => 1],
            // 5. Cancelled — today (history)
            ['user' => '6281234567004', 'layanan' => 'Keramas',             'tanggal' => $today,      'mulai' => '11:00:00', 'selesai' => '11:30:00', 'slot' => 1, 'status' => 'cancelled',            'pay' => 'unpaid',      'proof' => null, 'seq' => 1],
            // 6. Pending kemarin — kandidat auto-cancel (FASE 10 demo)
            ['user' => '6281234567001', 'layanan' => 'Catok / Styling',     'tanggal' => $yesterday,  'mulai' => '12:00:00', 'selesai' => '12:30:00', 'slot' => 1, 'status' => 'pending_verification', 'pay' => 'dp_uploaded', 'proof' => 'uploads/dp/sample.jpg', 'seq' => 1],
        ];

        foreach ($bookings as $b) {
            $l = $layananByNama[$b['layanan']];
            $harga = (int) $l['harga'];
            $kodeStr = $kode($b['tanggal'], $b['seq']);
            $this->db->table('bookings')->insert([
                'kode_booking' => $kodeStr,
                'user_id' => $pelangganIds[$b['user']] ?? null,
                'nama_pelanggan' => $this->namaForPhone($b['user']),
                'nomor_hp_pelanggan' => $b['user'],
                'email_pelanggan' => null,
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
                    'tanggal_transaksi' => $now,
                    'catatan' => 'Seeded sample transaction.',
                    'created_at' => $now,
                ]);
            }
        }
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
