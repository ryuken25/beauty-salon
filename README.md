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

Pelanggan **tidak punya akun** — booking dilakukan langsung tanpa login.

## Fitur Utama

- Booking publik tanpa akun: nama + nomor WhatsApp (email opsional), pilih layanan, stylist, tanggal, dan slot mulai.
- Anti-spam ringan: honeypot field + batas booking per perangkat per hari (tanpa CAPTCHA).
- Kode booking unik `SW-YYYYMMDD-NNN` di halaman sukses, dengan tombol salin.
- Fixed time slot 30 menit dengan validasi ketersediaan slot berurutan.
- Cek status & batalkan booking sendiri di `/cek-booking` (kode + nomor HP) dengan modal konfirmasi.
- Dua area login: **Admin** (operasional — verifikasi/tolak/batal/selesai booking, walk-in, jadwal, data pelanggan) dan **Pemilik** (analitik — dashboard pendapatan, grafik, layanan terpopuler, transaksi, CRUD layanan & stylist, pengaturan).
- Manajemen stylist full CRUD dengan soft delete (riwayat booking tetap aman).
- Manajemen layanan full CRUD dengan soft delete.
- Transaksi otomatis dengan input biaya tambahan opsional + catatan saat booking diselesaikan, dengan opsi mode pencatatan manual.
- Input booking walk-in/offline oleh admin.
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

- `/` (beranda + CTA pesan)
- `/layanan`
- `/booking` (form booking publik)
- `/booking/sukses/{kode}`
- `/cek-booking` (cek status + batal booking)
- `/login`
- `/admin/dashboard`, `/admin/booking`, `/admin/booking/walkin`, `/admin/booking/jadwal`, `/admin/pelanggan`
- `/owner/dashboard`, `/owner/layanan`, `/owner/stylist`, `/owner/transaksi`, `/owner/pengaturan`

End-to-end test (Playwright, butuh `php spark serve`):

```bash
npm install
npx playwright install chromium
npx playwright test
```

## Dokumentasi

- Panduan install tambahan: `cara install.md`.
- Skenario Black Box Testing: `docs/BLACKBOX_TESTING.md`.
- Instrumen SUS: `docs/SUS.md`.
- Ringkasan ERD: `docs/ERD.md`.

## Catatan Keamanan Repo Public

- Jangan commit `.env` asli.
- Jangan commit folder `vendor/`.
- Gunakan `composer install` setelah clone untuk membuat folder `vendor/` lokal.
