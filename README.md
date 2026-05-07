# SW Beauty Salon

Aplikasi web CodeIgniter 4 untuk booking layanan SW Beauty Salon dengan fixed time slot 30 menit, verifikasi booking admin/pemilik, notifikasi Telegram opsional, template WhatsApp manual, transaksi otomatis saat layanan selesai, dan dashboard pendapatan.

Tampilan aplikasi menggunakan konsep **salon lokal yang rapi, hangat, elegan, dan terpercaya** dengan tema black-gold soft dan background cream/ivory. Tidak ada dependency frontend berat; Bootstrap 5 tetap dipakai via CDN.

## Tech Stack

- Backend: PHP 8 + CodeIgniter 4.
- Database: MySQL.
- Frontend: Bootstrap 5, CSS custom di `public/assets/css/salon-theme.css`, JavaScript sederhana.
- Grafik: Chart.js via CDN.
- Integrasi: Telegram Bot API opsional.
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

TELEGRAM_BOT_TOKEN = ''
TELEGRAM_ALLOWED_CHAT_IDS = ''
TELEGRAM_WEBHOOK_SECRET = ''
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
- Transaksi otomatis dibuat satu kali saat booking selesai.
- Dashboard pendapatan harian, mingguan, bulanan, grafik pendapatan, status booking, dan layanan terpopuler.
- Input booking walk-in/offline.
- Manajemen layanan, stylist, jam kerja stylist, dan pengaturan dasar.
- Template WhatsApp manual: Salin Pesan, Buka WhatsApp, dan Tandai WA Sudah Dikirim.
- Telegram long polling/webhook opsional untuk notifikasi dan aksi booking.

## Telegram Opsional

Telegram tidak wajib agar aplikasi berjalan. Jika token dan chat ID kosong, booking tetap tersimpan dan aplikasi tidak crash.

Isi `.env` lokal jika ingin mengaktifkan Telegram:

```env
TELEGRAM_BOT_TOKEN = 'isi_token_bot_anda'
TELEGRAM_ALLOWED_CHAT_IDS = '123456789,987654321'
TELEGRAM_WEBHOOK_SECRET = ''
```

Untuk lokal tanpa HTTPS:

```bash
php spark telegram:poll
```

Perintah bot:

- `/start`: menampilkan chat ID dan instruksi.
- `/pending`: menampilkan booking pending dengan tombol.
- `/today`: menampilkan jadwal hari ini.
- `/help`: daftar perintah.

Endpoint webhook opsional tersedia di `POST /telegram/webhook` jika aplikasi dipasang di server HTTPS.

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
- Jangan commit token Telegram atau secret asli.
- Jangan commit folder `vendor/`.
- Gunakan `composer install` setelah clone untuk membuat folder `vendor/` lokal.
