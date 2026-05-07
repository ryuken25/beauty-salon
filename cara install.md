
| Data | Isi awal |
|---|---|
| Akun Pemilik | `owner@swbeautysalon.local` / `Password123!` |
| Akun Admin | `admin@swbeautysalon.local` / `Password123!` |
| Akun Pelanggan | `pelanggan@example.com` / `Password123!` |
| Stylist | Ni Wayan Sutrisna Wati dan Stylist 2 Dummy |
| Jam kerja stylist | 08:00–19:00 untuk semua hari |
| Layanan salon | Hair Treatment, Coloring, Facial, Wax, Make Up, Nails, Sulam, Diamond Tooth |
| Pengaturan awal | Nomor WhatsApp salon dan placeholder konfigurasi Telegram |

## Kebutuhan aplikasi

Pastikan perangkat sudah memiliki:

1. XAMPP dengan Apache dan MySQL.
2. PHP versi 8.1 atau lebih baru.
3. Composer.
4. Browser seperti Chrome/Edge/Firefox.

## Langkah install

### 1. Ekstrak ZIP project

Ekstrak [`updated.zip`](updated.zip) ke folder XAMPP, contoh:

```text
C:\xampp\htdocs\kinknadi
```

Pastikan di dalam folder tersebut ada file seperti [`spark`](spark), [`composer.json`](composer.json), folder [`app`](app), dan folder [`public`](public).

### 2. Buka terminal di folder project

Masuk ke folder project:

```bat
cd C:\xampp\htdocs\kinknadi
```

### 3. Install dependency Composer

Jalankan:

```bat
composer install
```

Jika folder [`vendor`](vendor) sudah ada dari paket lain, tetap aman menjalankan perintah ini untuk memastikan dependency lengkap.

### 4. Buat file `.env`

Copy file contoh environment:

```bat
copy .env.example .env
```

Jika file [`env`](env) ingin dipakai sebagai acuan tambahan, tetap utamakan file `.env` untuk konfigurasi lokal.

### 5. Buat database MySQL

Nyalakan Apache dan MySQL dari XAMPP Control Panel.

Buka phpMyAdmin atau MySQL client, lalu buat database:

```sql
CREATE DATABASE sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Sesuaikan konfigurasi database di `.env`

Buka file `.env`, lalu isi konfigurasi database seperti berikut:

```env
CI_ENVIRONMENT = development

database.default.hostname = localhost
database.default.database = sw_beauty_salon
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Jika MySQL XAMPP kamu memakai password, isi bagian `database.default.password` sesuai password lokal.

### 7. Jalankan migration

Migration membuat tabel database:

```bat
php spark migrate
```

Jika berhasil, tabel seperti `users`, `customers`, `services`, `stylists`, `stylist_working_hours`, `bookings`, `booking_slots`, `transactions`, `notification_logs`, `telegram_action_tokens`, dan `app_settings` akan dibuat.

### 8. Jalankan seeder data awal

Seeder mengisi akun demo, stylist, jam kerja, layanan, dan pengaturan awal:

```bat
php spark db:seed SalonSeeder
```

Setelah langkah ini database sudah terisi data awal.

### 9. Jalankan aplikasi

Ada dua pilihan menjalankan aplikasi.

#### Opsi A: melalui server bawaan CodeIgniter

```bat
php spark serve
```

Buka browser:

```text
http://localhost:8080
```

#### Opsi B: melalui Apache XAMPP

Buka browser:

```text
http://localhost/kinknadi/public/
```

Jika folder project berbeda, sesuaikan URL dengan nama foldernya.

## Akun login awal

| Role | Email | Password | Keterangan |
|---|---|---|---|
| Pemilik | `owner@swbeautysalon.local` | `Password123!` | Akses dashboard, master layanan, stylist, transaksi, booking |
| Admin | `admin@swbeautysalon.local` | `Password123!` | Akses operasional booking, walk-in, batal, selesai |
| Pelanggan | `pelanggan@example.com` | `Password123!` | Akses booking layanan, riwayat, pembatalan |

## Setup Telegram Bot opsional

Telegram tidak wajib untuk aplikasi berjalan. Jika token atau chat ID kosong, sistem tidak crash dan booking tetap tersimpan.

Jika ingin mengaktifkan Telegram:

1. Buat bot di BotFather.
2. Isi `.env`:

```env
TELEGRAM_BOT_TOKEN = token_bot_anda
TELEGRAM_ALLOWED_CHAT_IDS = chat_id_pemilik
```

3. Untuk mode lokal tanpa HTTPS, jalankan polling:

```bat
php spark telegram:poll
```

4. Kirim `/start` ke bot untuk mengetahui chat ID.

## Cek aplikasi setelah install

Jalankan pengecekan syntax PHP:

```bat
php -r "foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.')) as $f) { if ($f->isFile() && strtolower($f->getExtension()) === 'php' && strpos($f->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) === false) { passthru('php -l ' . escapeshellarg($f->getPathname()), $code); if ($code !== 0) exit($code); } }"
```

Cek route CodeIgniter:

```bat
php spark routes
```

## Troubleshooting

| Masalah | Solusi |
|---|---|
| `composer` tidak dikenali | Install Composer dan buka ulang terminal |
| `php` tidak dikenali | Tambahkan path PHP XAMPP, contoh `C:\xampp\php`, ke environment variable PATH |
| Database connection error | Cek nama database, username, password, dan MySQL XAMPP sudah aktif |
| Halaman 404 di XAMPP | Pastikan URL mengarah ke folder [`public`](public), contoh `http://localhost/kinknadi/public/` |
| Migration gagal karena tabel sudah ada | Gunakan database kosong baru, atau reset database lokal sebelum migrate |
| Seeder gagal karena email duplicate | Database sudah pernah di-seed; gunakan database kosong atau hapus data lama |

## Catatan ruang lingkup

Aplikasi ini tidak memakai payment gateway, tidak mengirim WhatsApp otomatis, tidak memakai forecasting, machine learning, membership, stok produk, multi-cabang, POS eksternal, CRM, atau akuntansi formal. WhatsApp hanya template manual, sedangkan Telegram hanya untuk notifikasi/verifikasi booking sesuai proposal.
