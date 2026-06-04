# SW Beauty Salon

Aplikasi web CodeIgniter 4 untuk booking layanan SW Beauty Salon dengan fixed time slot 30 menit, verifikasi booking admin/pemilik, template WhatsApp manual, transaksi otomatis saat layanan selesai, dan dashboard pendapatan.

Tampilan aplikasi memakai **dark editorial theme** (deep onyx + accent gold) — lihat `public/assets/css/salon-theme.css`. Bootstrap 5 dipakai via CDN.

## Tech Stack

- Backend: PHP 8 + CodeIgniter 4.
- Database: MySQL.
- Frontend: Bootstrap 5, CSS custom di `public/assets/css/salon-theme.css`, JavaScript sederhana.
- Grafik: Chart.js via CDN.
- WhatsApp: manual melalui template dan tautan `wa.me`, tanpa API pengiriman otomatis.

## Install Lokal

### Windows — auto setup (rekomendasi)

```bat
git clone https://github.com/ryuken25/beauty-salon.git
cd beauty-salon
setup-windows.bat
```

Skrip ini mengecek PHP/Composer/MySQL, install dependency, menyalin `.env`, membuat database `sw_beauty_salon`, menjalankan migrate + seed, lalu start server di `http://localhost:8080`. Versi PowerShell tersedia: `.\setup-windows.ps1`.

### Manual (Linux/macOS/Git Bash atau Windows tanpa setup script)

```bash
git clone https://github.com/ryuken25/beauty-salon.git
cd beauty-salon
composer install
cp .env.localhost .env             # Windows: copy .env.localhost .env
php spark migrate
php spark db:seed SalonSeeder
php spark serve
```

Atau pakai composer script: `composer fresh` menjalankan migrate + seed sekaligus.

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

| Role | Identitas Login | Password |
|---|---|---|
| Pemilik | email `owner@swbeautysalon.local` di `/admin/login` | `Password123!` |
| Admin | email `admin@swbeautysalon.local` di `/admin/login` | `Password123!` |
| Pelanggan | nomor WA `6281338109102` di `/login` (email akun: `winayagatar@gmail.com`) | `Password123!` |

Login terpisah: staff (admin/pemilik) pakai email di **`/admin/login`**; pelanggan pakai nomor WA di **`/login`**.

## Fitur Utama

- **Akun pelanggan** dengan registrasi (nama + nomor WA + **email** wajib + password) di `/register`. Lupa password tidak self-service — pelanggan menghubungi salon via WhatsApp, admin reset dari `/admin/pelanggan`.
- Booking **wajib login** sebagai pelanggan. Form mengisi nama + WA + email otomatis dari akun.
- **Notifikasi email otomatis** (Gmail SMTP, atas nama "SW Beauty Salon") di 3 momen: booking dibuat (menunggu verifikasi), admin verifikasi (dikonfirmasi), 30 menit sebelum sesi (pengingat). Best-effort — tanpa SMTP terkonfigurasi, booking tetap sukses.
- **Aturan DP**: harga ≤ Rp 50.000 → DP penuh; harga > Rp 50.000 → DP Rp 50.000. Pelanggan wajib upload bukti transfer/QRIS saat booking; admin verifikasi di halaman detail.
- **Awareness salon tutup**: kalau salon hampir/sudah tutup, halaman booking menampilkan banner dan langsung mengarahkan ke hari berikutnya.
- **Auto-cancel** booking pending yang sudah lewat jadwal — via `php spark bookings:auto-cancel` (jadwalkan Task Scheduler/cron tiap 5–10 menit) + lazy sweep di dashboard admin (1× per 5 menit).
- Kode booking unik `SW-YYYYMMDD-NNN`, tombol salin di halaman sukses.
- Fixed time slot 30 menit dengan validasi ketersediaan slot berurutan.
- Cek & batal booking publik di `/cek-booking` — cukup masukkan nomor WA, lihat semua booking di nomor itu. Pembatalan via halaman konfirmasi dedicated + lapor ke admin via WhatsApp.
- Dua tingkat akses panel: **Admin** (dashboard, booking, walk-in, jadwal, pelanggan, transaksi, pengaturan) dan **Pemilik** = superset Admin (tambah grup Manajerial: Laporan, Layanan).
- Manajemen layanan full CRUD (soft delete). Manajemen akun pelanggan: edit nama + reset password (nomor WA read-only — identitas login).
- Transaksi otomatis dengan input biaya tambahan opsional + catatan saat booking diselesaikan.
- Input booking walk-in oleh admin (tanpa DP — bayar di tempat).
- Template WhatsApp manual (Salin Pesan, Buka WhatsApp, Tandai sudah dikirim).

## Notifikasi Email (Gmail SMTP)

Email otomatis dikirim ke alamat akun pelanggan di tiga momen:

| Momen | Trigger | Subject |
|---|---|---|
| Booking dibuat | `BookingService::create()` setelah commit | `[SW Beauty Salon] Booking {kode} menunggu verifikasi` |
| Admin verifikasi | `BookingService::verify()` → `accepted` | `[SW Beauty Salon] Booking {kode} sudah dikonfirmasi` |
| Reminder 30 menit sebelum sesi | `bookings:send-reminders` (cron 5 menit) atau lazy sweep | `[SW Beauty Salon] Pengingat: sesi Anda {jam} hari ini` |

### Setup Gmail SMTP

1. Aktifkan **2-Step Verification** di akun Gmail salon ([myaccount.google.com/security](https://myaccount.google.com/security)).
2. Buat **App Password** (16 huruf) di [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).
3. Buka file `.env`, isi:
   ```dotenv
   email.SMTPUser  = 'akun-salon@gmail.com'
   email.SMTPPass  = 'xxxxxxxxxxxxxxxx'   # App Password tanpa spasi
   email.fromEmail = 'akun-salon@gmail.com'   # WAJIB sama dengan SMTPUser
   ```
4. (Opsional) `SMTPPort=465` + `SMTPCrypto=ssl` sebagai alternatif TLS-587 default.

Tanpa `SMTPHost`/`SMTPPass`, sistem skip pengiriman secara diam-diam dan log info — booking tetap sukses.

### Jadwal command produksi

Schedule via Windows Task Scheduler (atau cron di Linux) tiap **5 menit**:

```text
php spark bookings:auto-cancel       # batalkan pending yang lewat jadwal
php spark bookings:send-reminders    # email pengingat 30 menit sebelum sesi
```

Tanpa schedule, lazy sweep di `/admin/dashboard` & `/admin/booking` tetap menjalankan keduanya (max 1× per 5 menit) — cukup untuk dev/demo.

## WhatsApp Manual (bukan otomatis)

Aplikasi **tidak** mengirim WhatsApp otomatis dan tidak memakai WhatsApp Cloud API / Twilio / Meta Graph API / layanan berbayar lain. Yang otomatis adalah **email**. WhatsApp tetap manual: sistem menyediakan template pesan, tombol salin, tautan `wa.me`, dan flag "Tandai sudah dikirim" di detail booking admin.

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
- `/register`, `/login`, `/lupa-password` (auth pelanggan), `/admin/login` (auth staff)
- `/booking` (wajib login pelanggan; data nama/WA dari akun) → `/booking/sukses/{kode}`
- `/pelanggan/dashboard` (riwayat booking milik akun)
- `/cek-booking` (publik — nomor WA only) → `/cek-booking/{kode}/batal` → `/cek-booking-sukses/{kode}`
- Admin: `/admin/dashboard`, `/admin/booking`, `/admin/booking/walkin`, `/admin/booking/jadwal`, `/admin/pelanggan`, `/admin/transaksi`, `/admin/pengaturan`
- Pemilik (tambahan): `/owner/laporan`, `/owner/layanan`

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
