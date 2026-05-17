# SW Beauty Salon

Aplikasi web CodeIgniter 4 untuk booking layanan SW Beauty Salon dengan fixed time slot 30 menit, verifikasi booking admin/pemilik, template WhatsApp manual, transaksi otomatis saat layanan selesai, dan dashboard pendapatan.

Tampilan aplikasi memakai **dark editorial theme** (deep onyx + accent gold) — lihat `public/assets/css/salon-theme.css`. Bootstrap 5 dipakai via CDN.

## Tech Stack

- Backend: PHP 8 + CodeIgniter 4.
- Database: MySQL.
- Frontend: Bootstrap 5, CSS custom di `public/assets/css/salon-theme.css`, JavaScript sederhana.
- Grafik: Chart.js via CDN.
- WhatsApp: manual melalui template dan tautan `wa.me`, tanpa API pengiriman otomatis.

## Clone dan Install Lokal

### Linux/macOS/Git Bash

```bash
git clone https://github.com/ryuken25/beauty-salon.git
cd beauty-salon
composer install
cp .env.localhost .env
php spark migrate
php spark db:seed SalonSeeder
php spark serve
```

### Windows Command Prompt

```bat
git clone https://github.com/ryuken25/beauty-salon.git
cd beauty-salon
composer install
copy .env.localhost .env
php spark migrate
php spark db:seed SalonSeeder
php spark serve
```

Akses aplikasi di:

```text
http://localhost:8080
```

## Buat Database

Jalankan SQL berikut melalui phpMyAdmin, MySQL CLI, atau tool database lain:

```sql
CREATE DATABASE sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Konfigurasi lokal siap pakai tersedia di `.env.localhost` dan `.env.example`:

```env
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'
app.indexPage = ''

database.default.hostname = localhost
database.default.database = sw_beauty_salon
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

Jika MySQL lokal memakai password, isi `database.default.password` di file `.env` masing-masing.

## Akun Demo

| Role | Email | Password |
|---|---|---|
| Pemilik | `owner@swbeautysalon.local` | `Password123!` |
| Admin | `admin@swbeautysalon.local` | `Password123!` |
| Pelanggan | `pelanggan@example.com` | `Password123!` |

## Fitur Utama

- Registrasi dan login pelanggan.
- Role-based access untuk pelanggan, admin, dan pemilik.
- Daftar layanan salon dengan kategori, deskripsi, durasi, harga, dan status aktif.
- Booking online dengan pemilihan layanan, stylist, tanggal, dan slot mulai.
- Fixed time slot 30 menit dengan validasi ketersediaan slot berurutan.
- Booking `pending_verification`, `accepted`, dan `completed` dianggap menahan slot.
- Admin/pemilik dapat menerima, menolak, membatalkan, dan menyelesaikan booking.
- Transaksi otomatis dengan input biaya tambahan opsional + catatan saat booking diselesaikan, dengan opsi mode pencatatan manual.
- Dashboard pendapatan harian, mingguan, bulanan, grafik pendapatan, status booking, dan layanan terpopuler.
- Input booking walk-in/offline.
- Manajemen layanan, stylist, jam kerja stylist, dan pengaturan dasar.
- Template WhatsApp manual: Salin Pesan, Buka WhatsApp, dan Tandai WA Sudah Dikirim.

## WhatsApp Manual

Aplikasi tidak mengirim WhatsApp otomatis dan tidak memakai WhatsApp Cloud API, Twilio, Meta Graph API, atau layanan berbayar. Sistem hanya membuat template pesan, menyediakan tombol salin, membuka tautan WhatsApp, dan mencatat jika admin menandai pesan sudah dikirim manual.

## Status Booking

| Internal | Label UI | Slot |
|---|---|---|
| `pending_verification` | Menunggu Verifikasi | Tertahan |
| `accepted` | Diterima / Terjadwal | Tertahan |
| `rejected` | Ditolak | Dilepas |
| `cancelled` | Batal | Dilepas |
| `completed` | Selesai | Histori tetap |

## Test Lokal

Jalankan pengecekan dependency, syntax PHP, dan routes:

```bash
composer install
php -r "foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.')) as $f) { if ($f->isFile() && strtolower($f->getExtension()) === 'php' && strpos($f->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) === false) { passthru('php -l ' . escapeshellarg($f->getPathname()), $code); if ($code !== 0) exit($code); } }"
php spark routes
```

Jika database lokal tersedia:

```bash
php spark migrate
php spark db:seed SalonSeeder
php spark serve
```

Halaman yang disarankan dicek manual:

- `/`
- `/layanan`
- `/login`
- `/register`
- `/pelanggan`
- `/pelanggan/booking/baru`
- `/pelanggan/booking`
- `/admin`
- `/admin/booking`
- `/admin/booking/walkin`
- `/admin/booking/jadwal`
- `/admin/layanan`
- `/admin/stylist`
- `/admin/transaksi`
- `/admin/pengaturan`

## Dokumentasi

- Panduan install tambahan: `cara install.md`.
- Skenario Black Box Testing: `docs/BLACKBOX_TESTING.md`.
- Instrumen SUS: `docs/SUS.md`.
- Ringkasan ERD: `docs/ERD.md`.

## Catatan Keamanan Repo Public

- Jangan commit `.env` asli.
- Jangan commit folder `vendor/`.
- Gunakan `composer install` setelah clone untuk membuat folder `vendor/` lokal.
