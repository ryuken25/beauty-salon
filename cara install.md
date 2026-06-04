# Cara Install & Pakai — SW Beauty Salon

Tutorial lengkap dari nol sampai aplikasi jalan di laptop kamu.

---

## 1. Yang harus disiapkan dulu

| Software | Versi | Cara dapat |
|---|---|---|
| **PHP** | 8.1 atau lebih baru | Sudah termasuk di XAMPP / Laragon / Herd |
| **MySQL / MariaDB** | MySQL 8 / MariaDB 10.6+ | Sudah termasuk di XAMPP / Laragon |
| **Composer** | terbaru | https://getcomposer.org/download/ |
| **Git** | terbaru | https://git-scm.com/downloads |
| **Browser** | modern (Chrome / Firefox / Edge) | apa aja yang baru |

> **Paling gampang di Windows:** install [Laragon](https://laragon.org/) (sekali install dapat PHP, MySQL, dan Apache).
> **Atau** install [XAMPP](https://www.apachefriends.org/download.html) — sama-sama OK.

Setelah install, **nyalakan MySQL** (di Laragon klik "Start All", di XAMPP buka Control Panel → Start "MySQL").

---

## 2. Clone repo dari GitHub

Buka **PowerShell** (Windows) atau **Terminal** (Mac/Linux). Masuk ke folder yang lo mau tarok project-nya (misalnya `C:\Project\` atau `~/Projects/`).

```bash
cd C:\Project
git clone https://github.com/ryuken25/beauty-salon.git
cd beauty-salon
```

---

## 3. Install dependency PHP

```bash
composer install
```

Tunggu sebentar, dia download semua library yang dibutuhkan ke folder `vendor/`.

---

## 4. Bikin file `.env`

Salin file `.env.localhost` jadi `.env`:

**Windows (PowerShell / CMD):**
```bash
copy .env.localhost .env
```

**Mac / Linux:**
```bash
cp .env.localhost .env
```

Buka file `.env` pakai text editor (VSCode / Notepad++). Cek bagian database — secara default sudah cocok untuk XAMPP/Laragon dengan user `root` tanpa password:

```env
database.default.hostname = localhost
database.default.database = sw_beauty_salon
database.default.username = root
database.default.password =
database.default.port = 3306
```

Kalau MySQL kamu pakai password root, isi di `database.default.password = `.

---

## 5. Bikin database

Buka **phpMyAdmin** (http://localhost/phpmyadmin) atau MySQL CLI, jalankan SQL ini:

```sql
CREATE DATABASE sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atau via CLI:

```bash
mysql -u root -e "CREATE DATABASE sw_beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## 6. Jalankan migrasi + seeder (isi data awal)

```bash
php spark migrate
php spark db:seed SalonSeeder
```

Akan bikin semua tabel + isi data sample: 1 pemilik + 1 admin + 5 pelanggan (semua password `Password123!`), 24 layanan, setting awal salon, dan 7 booking demo (campur status — termasuk 1 pending kemarin untuk uji auto-cancel & 1 accepted yang dimulai +25 menit dari sekarang untuk uji reminder email).

---

## 7. Nyalakan server

```bash
php spark serve
```

Buka browser: **http://localhost:8080**

Selesai! 🎉

---

## 8. Akun login

| Role | Login di | Identitas | Password |
|---|---|---|---|
| **Pemilik** (akses penuh) | `/admin/login` | email `owner@swbeautysalon.local` | `Password123!` |
| **Admin** (akses terbatas) | `/admin/login` | email `admin@swbeautysalon.local` | `Password123!` |
| **Pelanggan demo** | `/login` | nomor WA `6281338109102` (email akun: `winayagatar@gmail.com`) | `Password123!` |

Staff & pelanggan punya form login terpisah. Link `/admin/login` sengaja **tidak ditampilkan di navbar publik**.

---

## 9. Pelanggan booking — gimana caranya?

Pelanggan **wajib punya akun** (nama + nomor WA + email + password). Daftar dulu di **http://localhost:8080/register**. Lalu:

1. Login di **http://localhost:8080/login** (pakai nomor WA + password).
2. Buka **http://localhost:8080/booking** — nama, WA, dan email otomatis dari akun.
3. Pilih layanan, tanggal (max 7 hari ke depan), jam mulai.
4. Upload bukti transfer DP (PNG/JPG, max 2 MB). Aturan DP: harga ≤ Rp 50.000 → DP penuh; > Rp 50.000 → DP Rp 50.000.
5. Submit → dapat kode booking. Status awal `Menunggu Verifikasi` sampai admin verifikasi.

Pelanggan menerima **email otomatis** di 3 momen:
- Booking dibuat → "Menunggu verifikasi".
- Admin verifikasi → "Dikonfirmasi".
- ~30 menit sebelum sesi → "Pengingat".

Email butuh konfigurasi Gmail SMTP — lihat bagian **10. Notifikasi Email** di bawah. Tanpa SMTP terkonfigurasi, booking tetap jalan, hanya email yang nonaktif.

Cek status / batal booking tanpa login: buka **http://localhost:8080/cek-booking**, masukkan nomor WA → list semua booking nomor itu. Pembatalan dibolehkan selama booking belum verifikasi atau ≥ 2 jam sebelum jam mulai.

---

## 10. Notifikasi Email (Gmail SMTP)

Email otomatis dikirim atas nama "SW Beauty Salon" lewat Gmail SMTP. Setup:

1. Aktifkan **2-Step Verification** di akun Gmail salon ([myaccount.google.com/security](https://myaccount.google.com/security)).
2. Buat **App Password** (16 huruf, tanpa spasi) di [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).
3. Buka `.env`, isi:
   ```env
   email.SMTPUser  = 'akun-salon@gmail.com'
   email.SMTPPass  = 'xxxxxxxxxxxxxxxx'
   email.fromEmail = 'akun-salon@gmail.com'
   ```
   `fromEmail` **wajib sama** dengan `SMTPUser` — Gmail akan tolak kalau beda.
4. Restart `php spark serve` supaya `.env` ter-reload.

Tanpa `SMTPHost`/`SMTPPass` terisi, sistem skip email diam-diam (log info) — booking tetap sukses.

### Auto-cancel + reminder produksi

Schedule via **Task Scheduler (Windows)** atau **cron (Linux)** tiap 5 menit:

```bat
php spark bookings:auto-cancel
php spark bookings:send-reminders
```

Tanpa schedule, lazy sweep di `/admin/dashboard` & `/admin/booking` tetap menjalankan keduanya (max 1× per 5 menit) — cukup untuk dev/demo.

---

## 11. Cara pull update terbaru

Kalau ada update di GitHub:

```bash
cd C:\Project\beauty-salon
git pull
composer install
php spark migrate
php spark serve
```

`composer install` dijalankan kalau ada dependency baru, `php spark migrate` kalau ada perubahan struktur DB.

---

## 12. Troubleshooting

### ❌ "Unable to connect to the database"
- Pastikan MySQL nyala di XAMPP/Laragon.
- Cek `.env` ada di root folder project.
- Cek username/password MySQL di `.env` cocok dengan yang kamu set.

### ❌ "Database 'sw_beauty_salon' doesn't exist"
- Bikin dulu database-nya: `CREATE DATABASE sw_beauty_salon ...` (lihat step 5).

### ❌ "Class 'XXX' not found"
- Jalanin: `composer install` lagi.

### ❌ Port 8080 udah dipakai
- Pakai port lain: `php spark serve --port 8081`

### ❌ Migration error (tabel udah ada)
- Reset DB: `php spark migrate:refresh` (HATI-HATI: ini hapus semua data!).
- Lalu seed ulang: `php spark db:seed SalonSeeder`.

### ❌ Halaman blank / error 500
- Buka `writable/logs/log-YYYY-MM-DD.log` — error biasanya di sana.
- Cek `.env` → `CI_ENVIRONMENT = development` biar error message muncul jelas.

---

## 13. Mau deploy ke server beneran?

1. Upload semua file (kecuali `vendor/`, `.env`, `writable/cache/`, `writable/logs/`).
2. Di server: `composer install --no-dev --optimize-autoloader`.
3. Bikin `.env` di server, isi `CI_ENVIRONMENT = production` + database production.
4. Run `php spark migrate` + `php spark db:seed SalonSeeder`.
5. Point document root ke folder `public/` (bukan root project).

---

## File yang sering perlu disentuh

| File | Fungsi |
|---|---|
| `.env` | Konfigurasi database + environment |
| `app/Views/` | Tampilan halaman |
| `app/Controllers/` | Logika per halaman |
| `app/Services/` | Logika domain (booking, slot, WhatsApp template) |
| `public/assets/css/salon-theme.css` | Styling custom |
| `app/Database/Migrations/` | Schema database |
| `app/Database/Seeds/SalonSeeder.php` | Data awal |

---

*Reserve · Refine · Revel*
