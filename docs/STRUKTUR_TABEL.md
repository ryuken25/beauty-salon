# Struktur Tabel — SW Beauty Salon

Dokumen ini melengkapi [ERD.md](ERD.md) dengan rincian per kolom untuk dilampirkan
di laporan tugas akhir, sekaligus memuat hasil audit **AUTO_INCREMENT** pada seluruh
primary key.

Basis data: `sw_beauty_salon` (MySQL/MariaDB, engine InnoDB, charset `utf8mb4`).
Struktur di bawah adalah kondisi setelah seluruh migration dijalankan
(`php spark migrate`), dengan migration terakhir
`2026-06-19-100000_AddBookingPriceSnapshotAndDpVerifiedAt.php`.

---

## 1. Ringkasan Audit AUTO_INCREMENT

Audit dilakukan dua arah: membaca ulang definisi kolom di seluruh file
`app/Database/Migrations/`, lalu mencocokkannya dengan kondisi nyata di database
melalui `information_schema.COLUMNS`.

| Nama Tabel | Kolom PK | Tipe Data PK | Auto Increment | File Migration |
|---|---|---|---|---|
| `users` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `layanan` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `bookings` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `booking_slots` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `transaksi` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `settings` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `booking_logs` | `id` | BIGINT(20) UNSIGNED | **Ya** | `2026-05-12-100000_ResetAndCreateSalonSchema.php` |
| `migrations` | `id` | BIGINT(20) UNSIGNED | **Ya** | tabel internal CodeIgniter 4 (pencatat riwayat migrasi) |

**Kesimpulan audit: seluruh tabel sudah memenuhi syarat.** Setiap primary key
bernama `id`, bertipe `BIGINT UNSIGNED`, `NOT NULL`, ditandai `AUTO_INCREMENT`,
dan terdaftar sebagai `PRIMARY KEY`. Karena tidak ada temuan, **tidak ada migration
perbaikan yang dibuat** — menambah migration yang tidak mengubah apa pun hanya akan
mengotori riwayat skema.

Empat syarat yang diperiksa pada tiap definisi kolom di migration:

1. Tipe `INT` atau `BIGINT` — seluruh tabel memakai `BIGINT`.
2. `'unsigned' => true` — id tidak pernah bernilai negatif.
3. `'auto_increment' => true` — nilai id dihasilkan otomatis oleh database.
4. `$this->forge->addKey('id', true)` — parameter kedua `true` menandai kolom
   sebagai primary key. Ini setara dengan `addPrimaryKey('id')`; repositori ini
   konsisten memakai bentuk `addKey('id', true)`.

---

## 2. Struktur Tiap Tabel

Keterangan kolom **Null**: `Tidak` = wajib diisi (`NOT NULL`), `Ya` = boleh kosong.

### 2.1 Tabel `users`

Menyimpan seluruh akun yang dapat login: admin, pemilik, dan pelanggan.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | email | VARCHAR | 150 | Ya | Email login staff (admin/pemilik), UNIQUE. Kosong untuk pelanggan |
| 3 | password_hash | VARCHAR | 255 | Tidak | Hash password (bcrypt) |
| 4 | nama | VARCHAR | 100 | Tidak | Nama lengkap pengguna |
| 5 | nomor_hp | VARCHAR | 20 | Ya | Nomor WhatsApp pelanggan, UNIQUE. Dipakai sebagai identitas login pelanggan |
| 6 | role | ENUM | admin, pemilik, pelanggan | Tidak | Peran pengguna di sistem |
| 7 | is_active | TINYINT | 1 | Tidak | Status akun, 1 = aktif (default 1) |
| 8 | created_at | DATETIME | — | Ya | Waktu data dibuat |
| 9 | updated_at | DATETIME | — | Ya | Waktu data terakhir diubah |

### 2.2 Tabel `layanan`

Katalog layanan salon. Dikelola pemilik lewat `/owner/layanan`.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | nama | VARCHAR | 100 | Tidak | Nama layanan |
| 3 | kategori | VARCHAR | 50 | Ya | Pengelompokan layanan |
| 4 | deskripsi | TEXT | — | Ya | Penjelasan layanan |
| 5 | durasi_menit | SMALLINT UNSIGNED | 5 | Tidak | Durasi pengerjaan dalam menit, kelipatan 30 |
| 6 | harga | INT UNSIGNED | 10 | Tidak | Harga normal layanan dalam rupiah (default 0) |
| 7 | ikon | VARCHAR | 50 | Ya | Nama ikon Bootstrap Icons untuk kartu layanan |
| 8 | gambar | JSON | — | Ya | Galeri foto berupa array path relatif, indeks ke-0 menjadi sampul |
| 9 | promo_persen | TINYINT UNSIGNED | 3 | Ya | Besar diskon 0–100 persen. NULL atau 0 berarti tanpa promo |
| 10 | promo_mulai | DATE | — | Ya | Tanggal promo mulai berlaku. NULL = tanpa batas awal |
| 11 | promo_selesai | DATE | — | Ya | Tanggal promo berakhir. NULL = tanpa batas akhir |
| 12 | promo_deskripsi | VARCHAR | 255 | Ya | Teks keterangan promo |
| 13 | is_active | TINYINT | 1 | Tidak | Layanan tampil di katalog, 1 = aktif (default 1) |
| 14 | created_at | DATETIME | — | Ya | Waktu data dibuat |
| 15 | updated_at | DATETIME | — | Ya | Waktu data terakhir diubah |
| 16 | deleted_at | DATETIME | — | Ya | Penanda soft delete. NULL = belum dihapus |

### 2.3 Tabel `bookings`

Tabel inti pemesanan, baik dari pelanggan online maupun walk-in yang diinput admin.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | kode_booking | VARCHAR | 20 | Tidak | Kode unik booking, format `SW-YYYYMMDD-NNN`, UNIQUE |
| 3 | user_id | BIGINT UNSIGNED | 20 | Ya | Foreign Key ke tabel users. NULL untuk booking walk-in |
| 4 | nama_pelanggan | VARCHAR | 100 | Tidak | Nama lengkap pelanggan |
| 5 | nomor_hp_pelanggan | VARCHAR | 20 | Tidak | Nomor WhatsApp pelanggan |
| 6 | email_pelanggan | VARCHAR | 150 | Ya | Email pelanggan untuk pengiriman kode booking dan invoice |
| 7 | layanan_id | BIGINT UNSIGNED | 20 | Tidak | Foreign Key ke tabel layanan |
| 8 | tanggal | DATE | — | Tidak | Tanggal kunjungan |
| 9 | slot_mulai | TIME | — | Tidak | Jam mulai layanan |
| 10 | slot_selesai | TIME | — | Tidak | Jam selesai layanan |
| 11 | jumlah_slot | SMALLINT UNSIGNED | 5 | Tidak | Banyaknya slot 30 menit yang ditahan |
| 12 | harga_layanan | INT UNSIGNED | 10 | Tidak | Harga final setelah promo (default 0) |
| 13 | original_service_price | INT UNSIGNED | 10 | Tidak | Snapshot harga normal saat booking dibuat (default 0) |
| 14 | promo_id | INT UNSIGNED | 10 | Ya | Penanda promo yang dipakai saat booking |
| 15 | promo_name | VARCHAR | 150 | Ya | Snapshot nama promo |
| 16 | promo_discount_type | VARCHAR | 50 | Ya | Jenis potongan promo, misalnya persen |
| 17 | promo_discount_value | INT UNSIGNED | 10 | Tidak | Besar potongan dalam rupiah (default 0) |
| 18 | final_service_price | INT UNSIGNED | 10 | Tidak | Snapshot harga yang ditagihkan (default 0) |
| 19 | remaining_payment | INT UNSIGNED | 10 | Tidak | Sisa yang dibayar di salon setelah DP (default 0) |
| 20 | dp_amount | INT UNSIGNED | 10 | Tidak | Nominal uang muka (default 0) |
| 21 | dp_proof_path | VARCHAR | 255 | Ya | Path file bukti transfer DP |
| 22 | payment_status | ENUM | unpaid, dp_uploaded, dp_verified | Tidak | Status pembayaran DP (default unpaid) |
| 23 | dp_verified_at | DATETIME | — | Ya | Waktu DP diverifikasi admin |
| 24 | email_reminder_sent_at | DATETIME | — | Ya | Waktu email pengingat dikirim. NULL = belum dikirim |
| 25 | status | ENUM | pending_verification, accepted, rejected, cancelled, completed | Tidak | Status booking (default pending_verification) |
| 26 | sumber | ENUM | online, walkin | Tidak | Asal booking (default online) |
| 27 | catatan | TEXT | — | Ya | Catatan tambahan dari pelanggan atau admin |
| 28 | wa_sent | TINYINT | 1 | Tidak | Penanda pesan WhatsApp sudah dikirim admin (default 0) |
| 29 | verified_via | VARCHAR | 60 | Ya | Kanal verifikasi booking |
| 30 | verified_at | DATETIME | — | Ya | Waktu booking diverifikasi |
| 31 | completed_at | DATETIME | — | Ya | Waktu booking diselesaikan |
| 32 | cancelled_at | DATETIME | — | Ya | Waktu booking dibatalkan |
| 33 | cancelled_by | VARCHAR | 60 | Ya | Pihak yang membatalkan, pelanggan atau admin |
| 34 | cancellation_reason | TEXT | — | Ya | Alasan pembatalan |
| 35 | rejection_reason | TEXT | — | Ya | Alasan penolakan oleh admin |
| 36 | created_at | DATETIME | — | Ya | Waktu data dibuat |
| 37 | updated_at | DATETIME | — | Ya | Waktu data terakhir diubah |

### 2.4 Tabel `booking_slots`

Satu baris mewakili satu slot 30 menit yang ditahan sebuah booking. Tabel ini
menjadi acuan utama pencegahan jadwal bentrok.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | booking_id | BIGINT UNSIGNED | 20 | Tidak | Foreign Key ke tabel bookings |
| 3 | tanggal | DATE | — | Tidak | Tanggal slot ditahan |
| 4 | slot_waktu | TIME | — | Tidak | Jam mulai slot 30 menit |
| 5 | status | ENUM | held, released | Tidak | Status penahanan slot (default held) |
| 6 | created_at | DATETIME | — | Ya | Waktu data dibuat |

### 2.5 Tabel `transaksi`

Dibuat satu kali ketika booking berpindah ke status `completed`. Relasi satu-ke-satu
dengan `bookings`.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | booking_id | BIGINT UNSIGNED | 20 | Tidak | Foreign Key ke tabel bookings, UNIQUE |
| 3 | nominal | INT UNSIGNED | 10 | Tidak | Total yang dibayar pelanggan |
| 4 | base_price | INT UNSIGNED | 10 | Tidak | Harga layanan sebelum tambahan (default 0) |
| 5 | additional_price | INT UNSIGNED | 10 | Tidak | Biaya tambahan di luar harga layanan (default 0) |
| 6 | dp_paid | INT UNSIGNED | 10 | Tidak | Uang muka yang sudah dibayar (default 0) |
| 7 | sisa_bayar | INT UNSIGNED | 10 | Tidak | Sisa pelunasan di salon (default 0) |
| 8 | metode_bayar | VARCHAR | 30 | Tidak | Metode pembayaran (default cash) |
| 9 | tanggal_transaksi | DATETIME | — | Tidak | Waktu transaksi dicatat |
| 10 | catatan | TEXT | — | Ya | Catatan transaksi |
| 11 | created_at | DATETIME | — | Ya | Waktu data dibuat |

### 2.6 Tabel `settings`

Konfigurasi aplikasi bergaya key-value, diubah lewat `/admin/pengaturan`.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | key_name | VARCHAR | 60 | Tidak | Nama pengaturan, UNIQUE. Contoh `jam_buka`, `wa_admin` |
| 3 | value | TEXT | — | Ya | Nilai pengaturan |
| 4 | updated_at | DATETIME | — | Ya | Waktu pengaturan terakhir diubah |

### 2.7 Tabel `booking_logs`

Jejak audit perubahan status booking.

| No | Nama Field | Tipe Data | Panjang | Null | Keterangan |
|---|---|---|---|---|---|
| 1 | id | BIGINT UNSIGNED | 20 | Tidak | Primary Key, Auto Increment |
| 2 | booking_id | BIGINT UNSIGNED | 20 | Tidak | Foreign Key ke tabel bookings |
| 3 | event_type | VARCHAR | 40 | Tidak | Jenis kejadian, misalnya created, verified, cancelled |
| 4 | actor | VARCHAR | 100 | Ya | Nama pelaku perubahan |
| 5 | actor_role | VARCHAR | 20 | Ya | Peran pelaku, misalnya admin atau pelanggan |
| 6 | payload | JSON | — | Ya | Data pendukung kejadian |
| 7 | notes | TEXT | — | Ya | Catatan bebas |
| 8 | created_at | DATETIME | — | Ya | Waktu kejadian dicatat |

---

## 3. Bukti Kode Migration

Potongan berikut diambil apa adanya dari
`app/Database/Migrations/2026-05-12-100000_ResetAndCreateSalonSchema.php`.

### 3.1 `users`

```php
$this->forge->addField([
    'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
    'email' => ['type' => 'VARCHAR', 'constraint' => 150],
    'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
    'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
    // ...
]);
$this->forge->addKey('id', true);   // parameter kedua true = PRIMARY KEY
$this->forge->addUniqueKey('email');
$this->forge->createTable('users', true);
```

### 3.2 `layanan`

```php
$this->forge->addField([
    'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
    'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
    'durasi_menit' => ['type' => 'SMALLINT', 'unsigned' => true],
    'harga' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
    // ...
]);
$this->forge->addKey('id', true);
$this->forge->createTable('layanan', true);
```

### 3.3 `bookings`

```php
$this->forge->addField([
    'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
    'kode_booking' => ['type' => 'VARCHAR', 'constraint' => 20],
    'nama_pelanggan' => ['type' => 'VARCHAR', 'constraint' => 100],
    'layanan_id' => ['type' => 'BIGINT', 'unsigned' => true],
    // ...
]);
$this->forge->addKey('id', true);
$this->forge->addUniqueKey('kode_booking');
$this->forge->addForeignKey('layanan_id', 'layanan', 'id', 'RESTRICT', 'CASCADE');
$this->forge->createTable('bookings', true);
```

Pola yang sama dipakai untuk `booking_slots`, `transaksi`, `settings`, dan
`booking_logs` pada file migration yang sama.

---

## 4. Lampiran: Verifikasi Langsung di Database

Diambil dari database `sw_beauty_salon` setelah `php spark migrate` dijalankan.

### 4.1 Rekap primary key seluruh tabel

```sql
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'sw_beauty_salon' AND COLUMN_KEY = 'PRI'
ORDER BY TABLE_NAME;
```

```
TABLE_NAME      COLUMN_NAME  COLUMN_TYPE           IS_NULLABLE  COLUMN_KEY  EXTRA
bookings        id           bigint(20) unsigned   NO           PRI         auto_increment
booking_logs    id           bigint(20) unsigned   NO           PRI         auto_increment
booking_slots   id           bigint(20) unsigned   NO           PRI         auto_increment
layanan         id           bigint(20) unsigned   NO           PRI         auto_increment
migrations      id           bigint(20) unsigned   NO           PRI         auto_increment
settings        id           bigint(20) unsigned   NO           PRI         auto_increment
transaksi       id           bigint(20) unsigned   NO           PRI         auto_increment
users           id           bigint(20) unsigned   NO           PRI         auto_increment
```

Kolom `EXTRA` bernilai `auto_increment` untuk seluruh tabel — inilah bukti tingkat
database bahwa nilai `id` dihasilkan otomatis dan tidak pernah diisi manual oleh
aplikasi.

### 4.2 `SHOW CREATE TABLE users`

```sql
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `role` enum('admin','pemilik','pelanggan') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `users_nomor_hp_unique` (`nomor_hp`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
```

### 4.3 `SHOW CREATE TABLE layanan`

```sql
CREATE TABLE `layanan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `durasi_menit` smallint(5) unsigned NOT NULL,
  `harga` int(10) unsigned NOT NULL DEFAULT 0,
  `ikon` varchar(50) DEFAULT NULL,
  `gambar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gambar`)),
  `promo_persen` tinyint(3) unsigned DEFAULT NULL,
  `promo_mulai` date DEFAULT NULL,
  `promo_selesai` date DEFAULT NULL,
  `promo_deskripsi` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
```

Catatan: di MariaDB, tipe `JSON` disimpan sebagai `longtext` dengan constraint
`json_valid()`. Secara fungsional tetap kolom JSON.

### 4.4 `SHOW CREATE TABLE bookings`

```sql
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_booking` varchar(20) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `nomor_hp_pelanggan` varchar(20) NOT NULL,
  `email_pelanggan` varchar(150) DEFAULT NULL,
  `layanan_id` bigint(20) unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `slot_mulai` time NOT NULL,
  `slot_selesai` time NOT NULL,
  `jumlah_slot` smallint(5) unsigned NOT NULL,
  `harga_layanan` int(10) unsigned NOT NULL DEFAULT 0,
  `original_service_price` int(10) unsigned NOT NULL DEFAULT 0,
  `promo_id` int(10) unsigned DEFAULT NULL,
  `promo_name` varchar(150) DEFAULT NULL,
  `promo_discount_type` varchar(50) DEFAULT NULL,
  `promo_discount_value` int(10) unsigned NOT NULL DEFAULT 0,
  `final_service_price` int(10) unsigned NOT NULL DEFAULT 0,
  `remaining_payment` int(10) unsigned NOT NULL DEFAULT 0,
  `dp_amount` int(10) unsigned NOT NULL DEFAULT 0,
  `dp_proof_path` varchar(255) DEFAULT NULL,
  `payment_status` enum('unpaid','dp_uploaded','dp_verified') NOT NULL DEFAULT 'unpaid',
  `dp_verified_at` datetime DEFAULT NULL,
  `email_reminder_sent_at` datetime DEFAULT NULL,
  `status` enum('pending_verification','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending_verification',
  `sumber` enum('online','walkin') NOT NULL DEFAULT 'online',
  `catatan` text DEFAULT NULL,
  `wa_sent` tinyint(1) NOT NULL DEFAULT 0,
  `verified_via` varchar(60) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(60) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_booking` (`kode_booking`),
  KEY `bookings_layanan_id_foreign` (`layanan_id`),
  KEY `tanggal_slot_mulai` (`tanggal`,`slot_mulai`),
  KEY `status` (`status`),
  KEY `nomor_hp_pelanggan` (`nomor_hp_pelanggan`),
  KEY `bookings_user_id_fk` (`user_id`),
  CONSTRAINT `bookings_layanan_id_foreign` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
```

### 4.5 Nilai counter AUTO_INCREMENT saat audit

```sql
SELECT TABLE_NAME, ENGINE, AUTO_INCREMENT, TABLE_ROWS
FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'sw_beauty_salon';
```

```
TABLE_NAME      ENGINE   AUTO_INCREMENT  TABLE_ROWS
bookings        InnoDB   4               2
booking_logs    InnoDB   12              6
booking_slots   InnoDB   7               4
layanan         InnoDB   9               8
migrations      InnoDB   100             13
settings        InnoDB   14              13
transaksi       InnoDB   4               2
users           InnoDB   4               3
```

Kolom `AUTO_INCREMENT` berisi nilai `id` berikutnya yang akan diberikan database.
Nilainya lebih besar dari jumlah baris karena counter tidak dipakai ulang setelah
sebuah baris dihapus — perilaku normal InnoDB, dan justru menjaga id tetap unik
sepanjang umur tabel.

---

## 5. Cara Memverifikasi Ulang

```bash
php spark migrate
mysql -u root -D sw_beauty_salon -e "SHOW CREATE TABLE bookings\G"
mysql -u root -D sw_beauty_salon -e "SELECT TABLE_NAME, COLUMN_NAME, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='sw_beauty_salon' AND COLUMN_KEY='PRI';"
```
