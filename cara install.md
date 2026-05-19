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

Akan bikin semua tabel + isi data sample: 2 akun admin/pemilik, 24 layanan, setting awal salon.

---

## 7. Nyalakan server

```bash
php spark serve
```

Buka browser: **http://localhost:8080**

Selesai! 🎉

---

## 8. Akun login admin

| Role | Email | Password |
|---|---|---|
| **Pemilik** (akses penuh) | `owner@swbeautysalon.local` | `Password123!` |
| **Admin** (akses terbatas) | `admin@swbeautysalon.local` | `Password123!` |

Login di **http://localhost:8080/admin** (link ini sengaja **tidak ditampilkan di navbar publik** — admin harus tau URL-nya).

---

## 9. Pelanggan booking — gimana caranya?

Pelanggan **tidak perlu daftar akun**. Cukup:

1. Buka **http://localhost:8080/booking**
2. Isi nama + nomor WhatsApp
3. Pilih layanan, tanggal (max 7 hari ke depan), jam mulai
4. Submit → dapat kode booking + tombol WhatsApp ke owner

Untuk cek status booking nantinya:
- Buka **http://localhost:8080/cek-booking**
- Masukkan nomor WhatsApp yang dipakai saat booking
- Lihat detail + bisa batalkan (selama booking belum verifikasi atau ≥ 2 jam sebelum jam mulai)

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
