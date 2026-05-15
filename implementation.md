# IMPLEMENTATION_PLAN.md — SW Beauty Salon

> ⚠️ **OBSOLETE SECTIONS (per 2026-05-15):** Telegram Bot integration telah dihapus seluruhnya dari aplikasi (controller, service, command, migration cleanup, route `/telegram/webhook`, command `php spark telegram:poll`, dan settings `telegram_*`). Semua bagian di bawah yang mention `TelegramNotifier`, `TelegramPoll`, inline-button verification, dan webhook Telegram sudah **tidak berlaku** — verifikasi booking sekarang hanya lewat dashboard `/admin/booking`. Dokumen ini ditinggalkan sebagai history plan; rujuk `README.md` + `docs/ERD.md` untuk state terbaru.

> **Cara pakai:** clone repo, buka Claude Code di root project, kasih satu perintah: *"Baca file IMPLEMENTATION_PLAN.md di root project terus eksekusi sesuai urutan di Section 12. Sebelum mulai tiap section, show me your plan dulu — jangan langsung coding."* Selesai.

---

## 0. Compliance Check — Wajib Patuhi Proposal SEMPRO

Project ini diturunkan dari proposal Tugas Akhir SEMPRO **"Sistem Informasi Penjadwalan dan Pelayanan pada SW Beauty Salon dengan Pendekatan Fixed Time Slot"** (NIM 220030114, I Made Nadi Artana). Implementasi **TIDAK BOLEH** melebihi ruang lingkup dan batasan yang udah di-defend.

### Yang WAJIB ada (core scope per proposal)
- [x] Pendekatan **fixed time slot 30 menit** sebagai inti — durasi layanan dikonversi jadi jumlah slot berurutan yang harus tersedia.
- [x] **Validasi deterministik** (slot kosong/terisi), prinsip first-come-first-served, tanpa algoritma optimasi/AI/ML/forecasting.
- [x] **Pencegahan double booking** via transaksi DB.
- [x] **Role-based access** untuk Admin, dan Pemilik (Pelanggan tanpa login — lihat Section 1).
- [x] **CRUD layanan** (nama, kategori, durasi, harga) oleh Pemilik.
- [x] **CRUD stylist** + pengaturan hari/jam kerja oleh Pemilik.
- [x] **Booking online** + **walk-in/offline input** oleh Admin/Pemilik.
- [x] **Pembatalan booking** oleh pelanggan (selama status masih bisa dibatalkan) dan oleh Admin/Pemilik.
- [x] **Status booking:** pending_verification, accepted, rejected, cancelled, completed.
- [x] **Transaksi otomatis** tercatat saat booking diubah ke `completed`.
- [x] **Notifikasi Telegram Bot** ke Pemilik untuk booking baru + inline button verifikasi/tolak.
- [x] **WhatsApp manual** via template + tombol Salin + buka `wa.me` + tandai "WA Sudah Dikirim". TANPA WhatsApp Cloud API / Twilio / Meta Graph API / Fonnte / layanan berbayar apapun.
- [x] **Dashboard deskriptif** untuk Pemilik: rekap pendapatan harian/mingguan/bulanan, grafik tren sederhana, layanan terpopuler.
- [x] **Stack:** PHP 8 + CodeIgniter 4 + MySQL + Bootstrap 5 (CDN) + Chart.js (CDN). MVC. XAMPP-friendly. Pengembangan metode Waterfall.
- [x] **Pengujian:** Black Box Testing + System Usability Scale (SUS) sesuai Bab III Section 3.6 dan Section 2.18-2.19.

### Yang DILARANG (batasan proposal Section 1.5 nomor 4)
- ❌ Payment gateway integration (Midtrans, Xendit, Doku, dll).
- ❌ Marketplace, POS eksternal, CRM eksternal, accounting eksternal.
- ❌ Machine learning, AI recommendation, forecasting, optimasi penjadwalan otomatis.
- ❌ Stok produk, perhitungan biaya operasional detail, laporan laba rugi, neraca, arus kas.
- ❌ Loyalty program, membership berbayar, modul pemasaran lanjutan.
- ❌ Multi-cabang / multi-branch / sinkronisasi antar lokasi.
- ❌ Pengujian load test ratusan user concurrent.
- ❌ Dependency frontend berat (React, Vue, Alpine, htmx, Tailwind, Sass) — hanya Bootstrap 5 + vanilla JS.

### Yang BERUBAH dari spec sebelumnya (revisi user, di-scope-down dari proposal)
- 🔄 **Customer TIDAK punya akun/login.** Booking dilakukan anonim dengan input nama + nomor HP. Lookup status booking via halaman publik dengan input nomor HP + kode booking. Konsekuensi: tabel `users` hanya untuk Admin + Pemilik. (Catatan: ini lebih sempit dari proposal Section 1.5 nomor 2 yang menyebut pelanggan bisa register/login. Smaller scope OK untuk thesis — pastikan dosbing tau.)
- 🔄 **Pemilihan stylist disembunyikan di UI customer.** Walaupun proposal Section 1.5 nomor 2 mention "Pelanggan dapat memilih stylist", praktiknya stylist aktif efektif = 1 (Pemilik). Stylist auto-assigned ke default. Tabel stylist tetap ada di DB sesuai proposal. (Pemilik tetap bisa kelola stylist dari panel admin.)
- 🔄 **Date picker:** hanya hari ini + 7 hari ke depan. Tanggal lewat = disabled.
- 🔄 **Time slot UI:** grid ala booking kursi bioskop (lihat Section 6 untuk spec detail).
- 🔄 **Jam operasional:** 08:00 – 19:00 (22 slot 30-menit dari 08:00 sampai 18:30 sebagai start time terakhir untuk service 30 menit; valid start time terakhir untuk service > 30 menit dihitung mundur dari 19:00).
- 🔄 **Navbar publik:** TIDAK ada link "Admin". Admin login via URL `/admin` (atau `/admin/login`) yang tidak dipublikasikan di navbar.

---

## 1. Scope Adjustment — Detail Perubahan

### 1.1 Customer flow tanpa login

| Aksi | Cara |
|---|---|
| Browse layanan | Halaman publik `/layanan` |
| Booking baru | Halaman publik `/booking`, isi nama + HP + pilih layanan + tanggal + jam |
| Cek status booking | Halaman publik `/cek-booking`, input nomor HP → list booking dengan nomor HP itu |
| Lihat detail satu booking | `/booking/{kode_booking}` — public route, tapi butuh kombinasi nomor HP yang match |
| Batalkan booking | Dari halaman detail booking, klik tombol "Batalkan" (selama status masih `pending_verification` atau `accepted` ≥ 2 jam sebelum slot mulai) |

**Anti-abuse:** rate limit per IP (max 5 booking per IP per hari, max 20 lookup per IP per jam). Pakai CI4 throttler built-in.

### 1.2 Stylist auto-assign

- Tabel `stylists` tetap ada dengan kolom default (kompatibel dengan proposal).
- Saat customer booking, sistem auto-pick stylist dengan `is_default = 1` dan aktif di hari/jam tersebut.
- Pemilik di-set `is_default = 1` saat seed.
- Pemilik bisa tambah/edit/hapus stylist dari admin panel. Kalau Pemilik tambah stylist baru, Pemilik bisa toggle field "tampilkan ke customer" untuk masa depan (default off).

### 1.3 Admin URL khusus

- Navbar publik HANYA punya link: Beranda · Layanan · Booking saya · Cek booking.
- Admin login page: `https://swbeautysalon.local/admin` atau `/admin/login` — TIDAK dipublikasikan di navbar manapun.
- Setelah login, admin di-redirect ke `/admin/dashboard`.
- Logout balik ke `/` (landing publik).

---

## 2. Tech Stack & Lingkungan

| Aspek | Pilihan |
|---|---|
| Backend | PHP 8.1+, CodeIgniter 4.5+ |
| Database | MySQL 8 / MariaDB 10.6+ |
| Frontend | Bootstrap 5 (CDN), Bootstrap Icons (CDN), Chart.js (CDN), vanilla JS |
| Custom CSS | `public/assets/css/salon-theme.css` (file tunggal) |
| Build tool | TIDAK ADA (no Vite/Webpack/Sass) |
| Server lokal | XAMPP (Apache + MySQL) |
| Method | Waterfall (Analisis → Desain → Implementasi → Pengujian → Pemeliharaan) |
| Telegram | Bot API native via cURL/Guzzle (long polling + webhook opsional) |
| WhatsApp | Manual link `wa.me/<no>?text=<encoded>` — TANPA API berbayar |

CDN yang dipakai (taruh di layout master):
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,500&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
```

---

## 3. Routes & Access Control

### 3.1 Public routes (no auth)

| Method | Route | Controller@method | Tujuan |
|---|---|---|---|
| GET | `/` | `Home::index` | Landing page |
| GET | `/layanan` | `Home::layanan` | Daftar layanan |
| GET | `/booking` | `Booking::form` | Form booking baru |
| POST | `/booking` | `Booking::store` | Submit booking |
| GET | `/booking/sukses/{kode}` | `Booking::sukses` | Halaman sukses + tombol WhatsApp ke owner |
| GET | `/cek-booking` | `Booking::cekForm` | Form input nomor HP |
| POST | `/cek-booking` | `Booking::cekProses` | Hasil lookup |
| GET | `/booking/{kode}` | `Booking::detail` | Detail satu booking (validasi via nomor HP di query) |
| POST | `/booking/{kode}/batal` | `Booking::batal` | Batalkan booking |
| GET | `/api/slots` | `Api::slots?tanggal=YYYY-MM-DD&layanan_id=X` | AJAX endpoint untuk grid slot |

### 3.2 Admin routes (auth required, role: admin atau pemilik)

| Method | Route | Tujuan |
|---|---|---|
| GET | `/admin` atau `/admin/login` | Login form (NO NAVBAR LINK) |
| POST | `/admin/login` | Process login |
| GET | `/admin/logout` | Logout |
| GET | `/admin/dashboard` | Dashboard admin (terbatas) atau pemilik (full) |
| GET | `/admin/booking` | List semua booking + filter |
| GET | `/admin/booking/{id}` | Detail booking |
| POST | `/admin/booking/{id}/verify` | Set status `accepted` |
| POST | `/admin/booking/{id}/reject` | Set status `rejected` |
| POST | `/admin/booking/{id}/complete` | Set status `completed` + auto-create transaksi |
| POST | `/admin/booking/{id}/cancel` | Set status `cancelled` |
| POST | `/admin/booking/{id}/wa-sent` | Tandai WA template udah dikirim manual |
| GET | `/admin/booking/walkin` | Form walk-in booking |
| POST | `/admin/booking/walkin` | Submit walk-in |
| GET | `/admin/booking/jadwal` | View jadwal harian (timeline) |

### 3.3 Pemilik-only routes (role: pemilik)

| Method | Route | Tujuan |
|---|---|---|
| GET, POST | `/admin/layanan` | CRUD layanan |
| GET, POST | `/admin/layanan/{id}/edit` | Edit layanan |
| POST | `/admin/layanan/{id}/delete` | Hapus layanan |
| GET, POST | `/admin/stylist` | CRUD stylist + jadwal kerja |
| GET | `/admin/transaksi` | List transaksi otomatis |
| GET | `/admin/pengaturan` | Settings (Telegram, WhatsApp nomor owner, info salon) |
| POST | `/telegram/webhook` | Webhook Telegram (optional HTTPS only) |

### 3.4 Auth filter

Bikin `app/Filters/AdminFilter.php`:
- Cek session `is_logged_in` dan `user_role`.
- Kalau gak ada → redirect ke `/admin/login`.
- Apply di routes group `admin` via `$routes->group('admin', ['filter' => 'admin'], ...)`.

Pemilik-only routes pakai filter tambahan `PemilikFilter` yang cek `user_role === 'pemilik'`.

---

## 4. Database Schema

Semua tabel pakai prefix kosong, charset `utf8mb4_unicode_ci`. Bikin migration per tabel di `app/Database/Migrations/`.

### 4.1 Tabel `users` (admin + pemilik saja, tidak ada customer)

```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
email           VARCHAR(150) UNIQUE NOT NULL
password_hash   VARCHAR(255) NOT NULL
nama            VARCHAR(100) NOT NULL
role            ENUM('admin','pemilik') NOT NULL
is_active       TINYINT(1) DEFAULT 1
created_at      DATETIME
updated_at      DATETIME
```

### 4.2 Tabel `stylists`

```sql
id              BIGINT UNSIGNED PK
nama            VARCHAR(100) NOT NULL
nomor_hp        VARCHAR(20)
peran           VARCHAR(50)            -- "Owner & Stylist", "Junior Stylist", dll
is_default      TINYINT(1) DEFAULT 0   -- yang auto-assign ke booking customer
is_active       TINYINT(1) DEFAULT 1
created_at      DATETIME
updated_at      DATETIME
```

Constraint: max 1 baris dengan `is_default = 1 AND is_active = 1` (validasi aplikasi-level).

### 4.3 Tabel `stylist_schedules` (jam kerja per hari)

```sql
id              BIGINT UNSIGNED PK
stylist_id      BIGINT UNSIGNED FK -> stylists.id
hari            ENUM('senin','selasa','rabu','kamis','jumat','sabtu','minggu')
jam_mulai       TIME            -- contoh 08:00
jam_selesai     TIME            -- contoh 19:00
is_libur        TINYINT(1) DEFAULT 0
created_at      DATETIME
updated_at      DATETIME

UNIQUE KEY (stylist_id, hari)
```

### 4.4 Tabel `layanan` (services)

```sql
id              BIGINT UNSIGNED PK
nama            VARCHAR(100) NOT NULL
kategori        VARCHAR(50)          -- "Hair", "Facial", "Nails", dll
deskripsi       TEXT
durasi_menit    SMALLINT UNSIGNED    -- harus kelipatan 30 (validasi aplikasi)
harga           INT UNSIGNED         -- Rupiah, no desimal
ikon            VARCHAR(50)          -- nama Bootstrap Icon, contoh "bi-scissors"
is_active       TINYINT(1) DEFAULT 1
created_at      DATETIME
updated_at      DATETIME
```

### 4.5 Tabel `bookings`

```sql
id                      BIGINT UNSIGNED PK
kode_booking            VARCHAR(20) UNIQUE       -- format "BK-YYYYMMDD-XXX"
nama_pelanggan          VARCHAR(100) NOT NULL
nomor_hp_pelanggan      VARCHAR(20) NOT NULL     -- format 62xxx
layanan_id              BIGINT UNSIGNED FK -> layanan.id
stylist_id              BIGINT UNSIGNED FK -> stylists.id
tanggal                 DATE NOT NULL
slot_mulai              TIME NOT NULL            -- "08:00", "08:30", dll
slot_selesai            TIME NOT NULL            -- dihitung dari slot_mulai + layanan.durasi_menit
jumlah_slot             SMALLINT                 -- durasi_menit / 30
status                  ENUM('pending_verification','accepted','rejected','cancelled','completed') DEFAULT 'pending_verification'
sumber                  ENUM('online','walkin') DEFAULT 'online'
catatan                 TEXT
wa_sent                 TINYINT(1) DEFAULT 0     -- ditandai admin setelah kirim WA manual
verified_via            VARCHAR(50) NULL         -- "telegram:<chat_id>" atau "dashboard:<user_id>"
verified_at             DATETIME NULL
completed_at            DATETIME NULL
cancelled_at            DATETIME NULL
cancelled_by            VARCHAR(50) NULL         -- "pelanggan", "admin:<user_id>", "telegram:<chat_id>"
created_at              DATETIME
updated_at              DATETIME

INDEX (tanggal, slot_mulai)
INDEX (status)
INDEX (nomor_hp_pelanggan)
```

### 4.6 Tabel `booking_slots` (slot occupancy explicit)

Tabel ini OPSIONAL — bisa di-derive dari `bookings` saja. Tapi adanya tabel ini bikin query availability lebih cepat dan auditable.

```sql
id              BIGINT UNSIGNED PK
booking_id      BIGINT UNSIGNED FK -> bookings.id
stylist_id      BIGINT UNSIGNED FK -> stylists.id
tanggal         DATE NOT NULL
slot_waktu      TIME NOT NULL          -- contoh "08:00", "08:30"
status          ENUM('held','released') DEFAULT 'held'
created_at      DATETIME

UNIQUE KEY uniq_slot (stylist_id, tanggal, slot_waktu, status)   -- hanya 1 "held" per slot per stylist
```

Saat booking dibuat dengan status pending_verification/accepted: insert 1 row per 30-menit slot dengan status `held`.
Saat booking jadi rejected/cancelled: update slot ke `released` (atau hapus row).
Saat completed: slot tetap held (sebagai history).

### 4.7 Tabel `transaksi`

```sql
id                  BIGINT UNSIGNED PK
booking_id          BIGINT UNSIGNED UNIQUE FK -> bookings.id
nominal             INT UNSIGNED                 -- copy dari layanan.harga saat completed
metode_bayar        VARCHAR(30) DEFAULT 'cash'   -- "cash" / "transfer" / "qris" (manual entry, no gateway)
tanggal_transaksi   DATETIME NOT NULL            -- = bookings.completed_at
catatan             TEXT
created_at          DATETIME
```

### 4.8 Tabel `settings` (key-value)

```sql
id          BIGINT UNSIGNED PK
key_name    VARCHAR(50) UNIQUE
value       TEXT
updated_at  DATETIME
```

Initial keys yang di-seed:
- `nama_salon` = "SW Beauty Salon"
- `alamat_salon` = "Batunya, Kec. Baturiti, Kabupaten Tabanan, Bali 82191"
- `nomor_hp_owner` = "6287862183074" (format internasional buat link wa.me)
- `jam_buka` = "08:00"
- `jam_tutup` = "19:00"
- `slot_durasi_menit` = "30"
- `range_hari_booking` = "7" (hari ke depan maksimal)
- `telegram_bot_token` = ""
- `telegram_allowed_chat_ids` = ""
- `template_wa_diterima` = "Halo {nama}, booking Anda {kode} untuk {layanan} pada {tanggal} jam {jam_mulai} sudah kami terima. Sampai jumpa di SW Beauty Salon."
- `template_wa_ditolak` = "..."
- `template_wa_reminder` = "..."

### 4.9 Seeder

`SalonSeeder` bikin:
- 1 user pemilik (`owner@swbeautysalon.local` / `Password123!`)
- 1 user admin (`admin@swbeautysalon.local` / `Password123!`)
- 1 stylist (Ni Wayan Sutrisna Wati, is_default=1)
- Schedule stylist senin-minggu 08:00-19:00
- Sample layanan: Hair Treatment (90m, 250k), Facial Premium (60m, 180k), Hair Color (120m, 350k), Manicure (60m, 120k), Make Up (90m, 300k), Diamond Tooth (30m, 150k), Nails (60m, 100k), Sulam Alis (180m, 450k)
- Settings default

---

## 5. Per-Page Implementation

### 5.1 `/` — Landing publik

Layout: `templates/layout_public.php` (navbar publik).

Komponen:
- **Hero** background onyx (#0A0A0A), padding 60px vertikal, center-aligned.
  - Ornament rule (gold line + sparkle icon + gold line)
  - Display wordmark "SW Beauty Salon" serif 32px letter-spacing 6px
  - Italic tagline "Reserve · Refine · Revel"
  - Body subtitle: "Perawatan kecantikan kelas atas dengan sentuhan personal di Tabanan"
  - CTA primary "Booking Sekarang" → `/booking`
  - Bottom ornament rule
- **Layanan unggulan section** (3 service card grid):
  - Query 3 layanan dengan harga termurah atau yang ditandai featured (kalau gak ada flag, ambil 3 layanan pertama is_active=1).
  - Card: ikon hero (bg cream), nama serif, italic subtitle, durasi + harga, klik → `/layanan`.
- **Why us section** (3 kolom): ikon clock "Booking fleksibel", ikon award "Stylist tersertifikasi", ikon heart "Layanan personal". Each: ikon 24px gold + h3 + italic Playfair subtitle.
- **Footer** background onyx, center: pin icon + alamat, phone icon + nomor HP, copyright italic.

Public navbar:
```
[SW Beauty Salon (wordmark)]       Beranda · Layanan · Booking saya · Cek booking      [Booking Sekarang button]
```

TIDAK ADA link "Admin" di navbar.

### 5.2 `/layanan` — Daftar layanan publik

- Page header: H1 "Layanan kami" + italic tagline + ornament rule.
- Filter chips category: Semua · Hair · Facial · Nails · Make Up · Diamond Tooth · Sulam (vanilla JS filter, no AJAX).
- Grid 3 kolom desktop, 2 kolom tablet, 1 kolom mobile.
- Tiap card service: ikon hero (cream bg, gold icon 32px), nama, italic kategori, deskripsi singkat (truncate 80 char), durasi + harga, tombol "Booking" → `/booking?layanan_id={id}`.

### 5.3 `/booking` — Form booking baru (HALAMAN KRUSIAL)

Layout 2 step di satu page (bukan multi-page form):

**Block 1: Data pelanggan**
```
[H1: Booking Layanan]
[Tagline: Pilih layanan, tanggal, dan jam yang nyaman untuk Anda]
[Ornament rule]

Nama lengkap *      [_____________]
Nomor HP (WA) *     [_____________]    helper text: "Contoh: 081234567890"
Catatan (opsional)  [_____________]
```

**Block 2: Pilih layanan**

Card grid 2 kolom (atau dropdown di mobile):
- Tiap kartu layanan menampilkan: ikon + nama + durasi badge + harga.
- Auto-select kalau ada query param `?layanan_id={id}`.
- Saat layanan dipilih, kartu di-border-tebal gold.

**Block 3: Pilih tanggal**

Horizontal date strip 8 kotak (hari ini + 7 hari ke depan):
```
[Hari ini]   [Sel 13]  [Rab 14]  [Kam 15]  [Jum 16]  [Sab 17]  [Min 18]  [Sen 19]
   12 Mei
```
Tiap kotak: hari (Sen, Sel, dll) + tanggal 2 digit + bulan abbreviated.
Hari ini diberi label "Hari ini" + warna gold.
Tanggal dipilih: bg gold, teks putih.
Tanggal lain: bg putih, border gold tipis.
Klik tanggal → fetch ulang grid slot via AJAX `/api/slots?tanggal=X&layanan_id=Y`.

**Block 4: Pilih jam mulai (CINEMA-STYLE GRID)**

Lihat Section 6 untuk spec lengkap. Grid 4 kolom (mobile) / 6 kolom (desktop), 22 slot dari 08:00 sampai 18:30.

**Block 5: Ringkasan + submit**

Fixed bottom bar (mobile) atau card di kanan (desktop):
```
Layanan      : Hair Treatment
Tanggal      : 12 Mei 2026
Jam          : 10:00 – 11:30 (90 menit)
Total        : Rp 250.000
                              [Konfirmasi Booking →]
```

Submit POST `/booking`:
1. Validasi server-side (lihat Section 7).
2. Generate `kode_booking` = "BK-" + date + "-" + 3-digit random.
3. Insert ke `bookings` dengan status `pending_verification`.
4. Insert N rows ke `booking_slots` (N = durasi_menit / 30).
5. Fire event → kirim Telegram notification ke owner.
6. Redirect ke `/booking/sukses/{kode}`.

### 5.4 `/booking/sukses/{kode}` — Halaman sukses

- Success icon ring 72px gold check.
- Ornament rule.
- H1 serif "Booking diterima!"
- Caption letter-spaced kode booking.
- Card detail booking (table-style key-value).
- Big button success green "Chat owner via WhatsApp" → `wa.me/{owner_no}?text={template_url_encoded}` template:
  ```
  Halo SW Beauty Salon, saya {nama} sudah melakukan booking:
  
  • Kode: {kode_booking}
  • Layanan: {nama_layanan}
  • Tanggal: {tanggal_format_indo}
  • Jam: {slot_mulai} – {slot_selesai}
  
  Mohon konfirmasi. Terima kasih.
  ```
- Info banner: "Admin sudah dikirimi notifikasi Telegram otomatis. Pesan WhatsApp ini opsional untuk konfirmasi langsung."
- Link "Kembali ke beranda" + "Cek status booking" → `/cek-booking`.

### 5.5 `/cek-booking` — Lookup status

**Form input:**
```
Cek status booking Anda

Nomor HP *  [______________]
                              [Cari]
```

**Setelah submit (GET) dengan no_hp di query:**
- Tampilkan list semua booking dengan `nomor_hp_pelanggan = input` (max 20 latest).
- Tiap row: kode, layanan, tanggal+jam, badge status, tombol "Detail" → `/booking/{kode}?no_hp={hp}`.
- Empty state: "Tidak ada booking dengan nomor HP ini."

### 5.6 `/booking/{kode}` — Detail booking publik

- Validasi: query string harus include `?no_hp={hp}` yang match dengan booking. Kalau gak match → 403.
- Tampilkan detail lengkap booking + status + timeline (created → verified/rejected → wa_sent → completed/cancelled).
- Tombol aksi (kalau status memenuhi):
  - "Batalkan booking" (kalau status `pending_verification` atau `accepted` dan minimal 2 jam sebelum slot_mulai).
  - "Chat owner via WhatsApp" (link wa.me).
- POST `/booking/{kode}/batal` → set status `cancelled`, release slots, log `cancelled_by = pelanggan`, fire Telegram notif ke owner.

### 5.7 `/admin/login` — Admin login (NO NAVBAR LINK)

- Minimal page, layout `templates/layout_auth.php`.
- Centered card 400px:
  - Wordmark serif center.
  - Ornament rule.
  - H1 "Login admin".
  - Tagline italic "Akses panel pengelolaan salon".
  - Form: email + password.
  - btn-salon-primary full-width "Masuk".
  - Footer mini link "← Kembali ke beranda".
- POST validasi:
  - Cek user existence + bcrypt password verify.
  - Set session: `is_logged_in = true`, `user_id`, `user_role`, `user_nama`.
  - Redirect ke `/admin/dashboard`.
- Brute force protection: max 5 attempt per IP per 15 menit (CI4 throttler).

### 5.8 `/admin/dashboard` — Dashboard

Sidebar admin (collapsed icon-only ≤991px):
```
[Diamond logo brand]
[Dashboard] (active)
[Booking]
[Walk-in baru]
[Jadwal]
[Layanan]        (pemilik-only)
[Stylist]        (pemilik-only)
[Transaksi]      (pemilik-only)
[Pengaturan]     (pemilik-only)
─────────
[user nama + Logout]
```

Top bar:
- Left: H1 "Dashboard" + italic Playfair tanggal hari ini.
- Right: bell icon (badge count pending) + avatar user.

**Untuk role pemilik (full dashboard):**
- 4 metric cards grid:
  - Pendapatan hari ini (onyx bg gold text, featured) + trend vs kemarin.
  - Booking hari ini (white) + breakdown selesai/berjalan.
  - Pending verifikasi (white) + alert kalau > 0.
  - Stylist aktif (white).
- Row 60/40:
  - Card "Pendapatan 7 hari" bar chart Chart.js, gold fill.
  - Card "Layanan terpopuler" horizontal bar.
- Card "Booking terbaru" table-salon 5 row dengan kolom Pelanggan, Layanan, Jam, Status, Aksi.

**Untuk role admin (limited):**
- 2 metric cards: Booking hari ini, Pending verifikasi.
- Tidak ada chart pendapatan.
- Card "Booking terbaru" sama.

### 5.9 `/admin/booking` — List + filter

- Top bar: H1 "Daftar booking" + tagline + tombol "Walk-in baru" + "Export".
- Filter bar:
  - Status chips: Semua (active) · Pending · Diterima · Selesai · Ditolak · Batal.
  - Date range picker (default: hari ini).
  - Search input (cari nama/kode).
- Table-salon: ID, Pelanggan (nama + HP), Layanan, Tanggal & Jam, Status badge, Aksi (Detail + quick action per status).
- Pagination salon-styled.
- Row PENDING di-tonjolin dengan border-left amber 3px.

### 5.10 `/admin/booking/{id}` — Detail booking

- 2 kolom: kiri info booking + timeline, kanan aksi.
- Aksi tergantung status:
  - Pending: [Verifikasi] [Tolak]
  - Diterima: [Selesaikan] [Batal]
  - Selesai: [Lihat transaksi]
- Tombol WhatsApp template: copy template (sesuai status) + buka wa.me + tandai sudah kirim.
- Timeline section: list event dari `booking_logs` (kalau diimplementasikan) atau dari kolom-kolom timestamp di `bookings`.

### 5.11 `/admin/booking/walkin` — Walk-in booking

- Form ramping max 600px:
  - Nama pelanggan + No HP.
  - Pilih layanan (dropdown).
  - Pilih tanggal (date strip 8 hari sama dengan customer).
  - Pilih jam (cinema-style grid sama dengan customer).
  - Catatan opsional.
- Submit: Insert dengan status `accepted` langsung (gak perlu verifikasi karena dari admin), sumber=`walkin`, auto-create transaksi kalau langsung mau ditandai completed via toggle "Tandai selesai sekarang".

### 5.12 `/admin/booking/jadwal` — Schedule view

- Top bar: title + date navigator (← Hari ini →).
- Timeline view: rows = stylist (cuma 1 kalau default), columns = slot 30 menit dari 08:00 sampai 18:30.
- Slot kosong = putih border tipis, slot terisi = berwarna sesuai status booking (pending=amber, accepted=gold, completed=green soft).
- Click slot terisi → modal preview booking.

### 5.13 `/admin/layanan` — CRUD layanan (pemilik-only)

- Grid 3 kolom card layanan: ikon, nama, durasi, harga, toggle aktif/non-aktif, ikon edit, ikon trash.
- Tombol "+ Tambah layanan" → modal form:
  - Nama, kategori, durasi (dropdown kelipatan 30 menit), harga, deskripsi, ikon picker (subset Bootstrap Icons), is_active.

### 5.14 `/admin/stylist` — CRUD stylist + jadwal

- List stylist + tombol "+ Tambah stylist".
- Tiap stylist card: nama, peran italic, no HP, badge default, link "Atur jadwal" → modal pengaturan hari kerja (7 baris hari, jam_mulai, jam_selesai, toggle libur).

### 5.15 `/admin/transaksi`

- Filter date range + status booking.
- Top strip 3 metric: total pendapatan periode, jumlah transaksi, rata-rata per transaksi.
- Table-salon: tanggal, kode booking, pelanggan, layanan, nominal, metode bayar (dropdown editable kalau owner).
- Tombol export CSV (optional, basic).

### 5.16 `/admin/pengaturan` — Settings

Tab nav: Salon · Telegram · WhatsApp · Akun.

**Tab Salon:**
- Form: nama_salon, alamat, telp, jam_buka, jam_tutup, slot_durasi (read-only "30 menit" — sesuai novelty proposal), range_hari_booking.

**Tab Telegram:**
- Form: bot_token (password input), allowed_chat_ids (textarea, comma-separated).
- Status indicator: cek apakah bot reachable (klik tombol test → call getMe API).
- Tombol "Kirim test message" → send dummy ke chat IDs.

**Tab WhatsApp:**
- Form: nomor_hp_owner (format internasional).
- Preview template pesan: diterima, ditolak, reminder.
- Setting field editable untuk tiap template.

**Tab Akun:**
- Form ganti password user yang login.
- List akun (kalau pemilik) dengan tombol edit role/aktivkan-nonaktifkan.

---

## 6. Time Slot Picker — Cinema-Style Spec

Ini fitur paling kelihatan dan paling berpengaruh ke UX. Detail penuh:

### 6.1 Layout

Grid CSS 4 kolom di mobile, 6 kolom di tablet, 8 kolom di desktop:
```
[08:00] [08:30] [09:00] [09:30]
[10:00] [10:30] [11:00] [11:30]
[12:00] [12:30] [13:00] [13:30]
[14:00] [14:30] [15:00] [15:30]
[16:00] [16:30] [17:00] [17:30]
[18:00] [18:30]
```

Total: 22 slot dari 08:00 sampai 18:30 (last possible start time untuk service durasi 30 menit; service lebih panjang start time terakhir disesuaikan otomatis).

### 6.2 State per slot

```css
.slot {
  width: 100%;
  aspect-ratio: 2/1;             /* kotak rapi mudah dilihat */
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  user-select: none;
}

/* Tersedia (default) */
.slot--available {
  background: white;
  border: 1px solid var(--color-gold);
  color: var(--color-charcoal);
}
.slot--available:hover {
  background: var(--color-ivory-warm);
}

/* Terpilih sebagai jam mulai */
.slot--selected {
  background: var(--color-gold);
  border: 1px solid var(--color-gold);
  color: white;
}

/* Auto-held karena bagian dari durasi layanan yang dipilih */
.slot--held {
  background: var(--color-ivory-warm);
  border: 1px solid var(--color-gold);
  color: var(--color-gold-dark);
  cursor: default;
}

/* Sudah dibooked orang lain */
.slot--booked {
  background: #d4cfc0;
  border: 1px solid #b8b0a0;
  color: #6e6657;
  text-decoration: line-through;
  cursor: not-allowed;
  position: relative;
}
.slot--booked::after {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 4px,
    rgba(0,0,0,0.05) 4px,
    rgba(0,0,0,0.05) 8px
  );
  pointer-events: none;
}

/* Sudah lewat (kalau date = hari ini) */
.slot--past {
  background: #ede8dd;
  border: 1px solid #d4cfc0;
  color: #b0a89a;
  cursor: not-allowed;
  text-decoration: line-through;
}

/* Tidak cukup ruang untuk durasi (mis. pilih 18:30 untuk 90 menit) */
.slot--insufficient {
  background: white;
  border: 1px solid #d4cfc0;
  color: #b0a89a;
  cursor: not-allowed;
}
```

### 6.3 Legenda (tampilkan di atas grid)

```
■ Tersedia    ■ Dipilih    ■ Terisi (booked)    ■ Tidak tersedia
```

### 6.4 Behavior JS

```javascript
// State dipegang di JS sederhana
let selectedDate = todayISO();
let selectedLayananId = null;
let selectedSlot = null;
let bookedSlots = [];  // array of "HH:MM" yang sudah di-book
let allSlots = [];     // ['08:00', '08:30', ..., '18:30']

// Saat pilih tanggal atau layanan berubah:
async function refreshSlots() {
  if (!selectedDate || !selectedLayananId) return;
  const r = await fetch(`/api/slots?tanggal=${selectedDate}&layanan_id=${selectedLayananId}`);
  const data = await r.json();
  bookedSlots = data.booked;        // slot-slot yang sudah held di DB
  const layananDurasi = data.durasi_menit;
  const jumlahSlot = layananDurasi / 30;
  renderGrid(jumlahSlot);
}

function renderGrid(jumlahSlot) {
  const grid = document.getElementById('slot-grid');
  grid.innerHTML = '';
  
  allSlots.forEach(slot => {
    const div = document.createElement('div');
    div.classList.add('slot');
    div.textContent = slot;
    
    // Cek state
    if (isPast(selectedDate, slot)) {
      div.classList.add('slot--past');
    } else if (bookedSlots.includes(slot)) {
      div.classList.add('slot--booked');
    } else if (insufficientSpace(slot, jumlahSlot)) {
      div.classList.add('slot--insufficient');
    } else if (slot === selectedSlot) {
      div.classList.add('slot--selected');
    } else if (selectedSlot && isWithinHeldRange(slot, selectedSlot, jumlahSlot)) {
      div.classList.add('slot--held');
    } else {
      div.classList.add('slot--available');
      div.onclick = () => selectSlot(slot, jumlahSlot);
    }
    
    grid.appendChild(div);
  });
}

function insufficientSpace(startSlot, jumlahSlot) {
  // Cek apakah dari startSlot, ada N slot berurutan yang available (tidak booked)
  const startIdx = allSlots.indexOf(startSlot);
  if (startIdx + jumlahSlot > allSlots.length) return true;  // melebihi jam tutup
  for (let i = 1; i < jumlahSlot; i++) {
    if (bookedSlots.includes(allSlots[startIdx + i])) return true;
  }
  return false;
}

function isWithinHeldRange(slot, startSlot, jumlahSlot) {
  const startIdx = allSlots.indexOf(startSlot);
  const slotIdx = allSlots.indexOf(slot);
  return slotIdx > startIdx && slotIdx < startIdx + jumlahSlot;
}

function selectSlot(slot, jumlahSlot) {
  selectedSlot = slot;
  renderGrid(jumlahSlot);
  updateSummary();  // update ringkasan + enable submit button
}
```

### 6.5 Endpoint `/api/slots`

```php
// GET /api/slots?tanggal=2026-05-12&layanan_id=1
public function slots()
{
    $tanggal = $this->request->getGet('tanggal');
    $layananId = $this->request->getGet('layanan_id');
    
    // Validasi tanggal: hari ini sampai +7 hari
    $today = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+7 days'));
    if ($tanggal < $today || $tanggal > $maxDate) {
        return $this->response->setJSON(['error' => 'Tanggal di luar jangkauan'])->setStatusCode(400);
    }
    
    // Ambil layanan
    $layanan = (new LayananModel())->find($layananId);
    if (!$layanan || !$layanan['is_active']) {
        return $this->response->setJSON(['error' => 'Layanan tidak valid'])->setStatusCode(400);
    }
    
    // Ambil stylist default
    $stylist = (new StylistModel())->where('is_default', 1)->where('is_active', 1)->first();
    
    // Cek apakah stylist kerja di hari itu
    $hariStr = $this->getHariFromDate($tanggal);  // 'senin', 'selasa', dll
    $schedule = (new StylistScheduleModel())->where('stylist_id', $stylist['id'])->where('hari', $hariStr)->first();
    if (!$schedule || $schedule['is_libur']) {
        return $this->response->setJSON(['booked' => ['ALL'], 'durasi_menit' => $layanan['durasi_menit']]);
    }
    
    // Get held slots from booking_slots
    $heldSlots = (new BookingSlotModel())
        ->where('stylist_id', $stylist['id'])
        ->where('tanggal', $tanggal)
        ->where('status', 'held')
        ->findColumn('slot_waktu');
    
    return $this->response->setJSON([
        'booked' => array_map(fn($t) => substr($t, 0, 5), $heldSlots),  // "HH:MM"
        'durasi_menit' => $layanan['durasi_menit'],
        'jam_buka' => $schedule['jam_mulai'],
        'jam_tutup' => $schedule['jam_selesai'],
    ]);
}
```

---

## 7. Booking Validation Flow (Server-Side)

Saat POST `/booking`, server WAJIB lakukan validasi ini dalam DB transaction:

```php
public function store()
{
    $rules = [
        'nama_pelanggan' => 'required|min_length[3]|max_length[100]',
        'nomor_hp_pelanggan' => 'required|regex_match[/^(\+?62|0)8[0-9]{8,12}$/]',
        'layanan_id' => 'required|is_natural_no_zero',
        'tanggal' => 'required|valid_date',
        'slot_mulai' => 'required|regex_match[/^(0[8-9]|1[0-8]):(00|30)$/]',
    ];
    
    if (!$this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    
    $db = \Config\Database::connect();
    $db->transStart();
    
    // 1. Ambil layanan
    $layanan = (new LayananModel())->find($this->request->getPost('layanan_id'));
    if (!$layanan || !$layanan['is_active']) {
        $db->transRollback();
        return redirect()->back()->with('error', 'Layanan tidak valid');
    }
    
    $jumlahSlot = $layanan['durasi_menit'] / 30;
    
    // 2. Tentukan range tanggal valid
    $today = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+7 days'));
    $tanggal = $this->request->getPost('tanggal');
    if ($tanggal < $today || $tanggal > $maxDate) {
        $db->transRollback();
        return redirect()->back()->with('error', 'Tanggal di luar jangkauan booking');
    }
    
    // 3. Tentukan stylist default
    $stylist = (new StylistModel())->where('is_default', 1)->where('is_active', 1)->first();
    
    // 4. Hitung slot yang dibutuhkan
    $slotMulai = $this->request->getPost('slot_mulai');
    $slotsNeeded = [];
    $current = strtotime($slotMulai);
    for ($i = 0; $i < $jumlahSlot; $i++) {
        $slotsNeeded[] = date('H:i', $current);
        $current = strtotime('+30 minutes', $current);
    }
    $slotSelesai = date('H:i', $current);
    
    // Cek slot terakhir tidak melebihi jam tutup
    $jamTutup = (new SettingModel())->getValue('jam_tutup', '19:00');
    if (strtotime($slotSelesai) > strtotime($jamTutup)) {
        $db->transRollback();
        return redirect()->back()->with('error', 'Durasi layanan melebihi jam tutup');
    }
    
    // 5. Cek availability semua slot dengan SELECT FOR UPDATE (anti race condition)
    $existingSlots = $db->table('booking_slots')
        ->where('stylist_id', $stylist['id'])
        ->where('tanggal', $tanggal)
        ->whereIn('slot_waktu', $slotsNeeded)
        ->where('status', 'held')
        ->countAllResults();
    
    if ($existingSlots > 0) {
        $db->transRollback();
        return redirect()->back()->with('error', 'Slot waktu tidak tersedia. Silakan pilih jam lain.');
    }
    
    // 6. Generate kode booking
    $kodeBooking = $this->generateKodeBooking();  // "BK-20260512-001"
    
    // 7. Insert booking
    $bookingId = $db->table('bookings')->insert([
        'kode_booking' => $kodeBooking,
        'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
        'nomor_hp_pelanggan' => $this->normalizePhone($this->request->getPost('nomor_hp_pelanggan')),
        'layanan_id' => $layanan['id'],
        'stylist_id' => $stylist['id'],
        'tanggal' => $tanggal,
        'slot_mulai' => $slotMulai,
        'slot_selesai' => $slotSelesai,
        'jumlah_slot' => $jumlahSlot,
        'status' => 'pending_verification',
        'sumber' => 'online',
        'catatan' => $this->request->getPost('catatan'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ], true);
    
    $bookingId = $db->insertID();
    
    // 8. Insert booking_slots
    foreach ($slotsNeeded as $slot) {
        $db->table('booking_slots')->insert([
            'booking_id' => $bookingId,
            'stylist_id' => $stylist['id'],
            'tanggal' => $tanggal,
            'slot_waktu' => $slot,
            'status' => 'held',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    $db->transComplete();
    
    if ($db->transStatus() === false) {
        return redirect()->back()->with('error', 'Gagal menyimpan booking. Silakan coba lagi.');
    }
    
    // 9. Fire Telegram notification (queued, error tolerant)
    try {
        service('telegramNotifier')->kirimBookingBaru($bookingId);
    } catch (\Exception $e) {
        log_message('error', 'Telegram notif gagal: ' . $e->getMessage());
        // Don't fail the booking
    }
    
    return redirect()->to('/booking/sukses/' . $kodeBooking);
}
```

### 7.1 Format normalisasi nomor HP

```php
private function normalizePhone($input) {
    $clean = preg_replace('/[^0-9]/', '', $input);
    if (str_starts_with($clean, '0')) {
        return '62' . substr($clean, 1);
    }
    if (str_starts_with($clean, '62')) {
        return $clean;
    }
    if (str_starts_with($clean, '8')) {
        return '62' . $clean;
    }
    return $clean;
}
```

### 7.2 Generate kode booking

```php
private function generateKodeBooking() {
    $date = date('Ymd');
    $count = (new BookingModel())->where('kode_booking LIKE', "BK-{$date}-%")->countAllResults();
    $seq = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    return "BK-{$date}-{$seq}";
}
```

---

## 8. Telegram Bot Integration

### 8.1 Service `app/Libraries/TelegramNotifier.php`

```php
class TelegramNotifier
{
    private $token;
    private $allowedChatIds;
    
    public function __construct() {
        $setting = new SettingModel();
        $this->token = $setting->getValue('telegram_bot_token', '');
        $ids = $setting->getValue('telegram_allowed_chat_ids', '');
        $this->allowedChatIds = $ids ? explode(',', $ids) : [];
    }
    
    public function kirimBookingBaru(int $bookingId): void {
        if (!$this->token || !$this->allowedChatIds) return;  // soft fail
        
        $booking = (new BookingModel())->getWithRelations($bookingId);
        $text = $this->formatBookingMessage($booking, 'BOOKING BARU');
        
        $replyMarkup = [
            'inline_keyboard' => [[
                ['text' => '✅ Verifikasi', 'callback_data' => "verify:{$bookingId}"],
                ['text' => '❌ Tolak', 'callback_data' => "reject:{$bookingId}"]
            ]]
        ];
        
        foreach ($this->allowedChatIds as $chatId) {
            $this->sendMessage(trim($chatId), $text, $replyMarkup);
        }
    }
    
    public function kirimKonfirmasi(int $bookingId, string $action, string $by): void {
        // Kirim setelah aksi dari dashboard untuk sync
        ...
    }
    
    private function sendMessage(string $chatId, string $text, ?array $replyMarkup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup) $payload['reply_markup'] = json_encode($replyMarkup);
        
        // pakai cURL atau Guzzle
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp;
    }
    
    private function formatBookingMessage($booking, string $title): string {
        return "<b>🔔 {$title}</b>\n\n"
             . "Kode: <code>{$booking['kode_booking']}</code>\n"
             . "Nama: {$booking['nama_pelanggan']}\n"
             . "HP: {$booking['nomor_hp_pelanggan']}\n"
             . "Layanan: {$booking['nama_layanan']}\n"
             . "Tanggal: " . date('d M Y', strtotime($booking['tanggal'])) . "\n"
             . "Jam: {$booking['slot_mulai']} – {$booking['slot_selesai']}\n"
             . "Stylist: {$booking['nama_stylist']}";
    }
}
```

### 8.2 Long polling command

`app/Commands/TelegramPoll.php` — extends BaseCommand. Run via `php spark telegram:poll`. Loop infinite:
1. `getUpdates` dengan offset.
2. Parse callback_query.
3. Validasi chat_id ada di allowedChatIds.
4. Parse `callback_data` (`verify:123` atau `reject:123`).
5. Call `BookingService::verify($id, "telegram:{$chatId}")` atau `BookingService::reject(...)`.
6. `editMessageText` di Telegram untuk update tampilan jadi "✅ Sudah diverifikasi pada HH:MM" atau sejenisnya.
7. `answerCallbackQuery` untuk hilangkan loading.

### 8.3 Webhook (opsional, HTTPS only)

POST `/telegram/webhook` — same logic dengan polling tapi reactive. Secure dengan secret token di header.

---

## 9. WhatsApp Template Manual

### 9.1 Halaman detail booking admin

Tampilkan card "Pesan WhatsApp" dengan:
- Textarea preview template (auto-generated dari template setting + data booking).
- 3 tombol:
  - **Salin Pesan**: copy textarea ke clipboard via `navigator.clipboard.writeText`.
  - **Buka WhatsApp**: link `https://wa.me/{nomor_hp_pelanggan}?text={url_encoded_message}` target="_blank".
  - **Tandai WA Sudah Dikirim**: POST `/admin/booking/{id}/wa-sent`, set `wa_sent = 1`. Setelah ditandai, badge "WA terkirim ✓" muncul.

### 9.2 Template per status (di settings)

- `template_wa_diterima` (kalau status berubah ke accepted)
- `template_wa_ditolak` (kalau rejected)
- `template_wa_reminder` (1 jam sebelum slot mulai — gak ada cron auto, admin kirim manual)
- `template_wa_selesai` (kalau completed, ucapan terima kasih)

Variabel template:
- `{nama}`, `{kode}`, `{layanan}`, `{tanggal}`, `{jam_mulai}`, `{jam_selesai}`, `{nominal}`, `{nomor_owner}`

---

## 10. Dashboard Logic

### 10.1 Query metric harian (pemilik)

```php
// Pendapatan hari ini
$pendapatanHariIni = $db->table('transaksi')
    ->where('DATE(tanggal_transaksi)', date('Y-m-d'))
    ->selectSum('nominal')
    ->get()->getRow()->nominal ?? 0;

// Booking hari ini (total)
$bookingHariIni = $db->table('bookings')
    ->where('tanggal', date('Y-m-d'))
    ->whereNotIn('status', ['rejected', 'cancelled'])
    ->countAllResults();

// Pending verifikasi
$pending = $db->table('bookings')
    ->where('status', 'pending_verification')
    ->countAllResults();
```

### 10.2 Chart pendapatan 7 hari

```php
$last7days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $sum = $db->table('transaksi')
        ->where('DATE(tanggal_transaksi)', $d)
        ->selectSum('nominal')
        ->get()->getRow()->nominal ?? 0;
    $last7days[] = ['tanggal' => $d, 'nominal' => $sum];
}
```

Render dengan Chart.js bar chart.

### 10.3 Layanan terpopuler

```php
$topServices = $db->table('bookings')
    ->select('layanan.nama, COUNT(*) as jumlah, SUM(layanan.harga) as total_revenue')
    ->join('layanan', 'layanan.id = bookings.layanan_id')
    ->where('bookings.status', 'completed')
    ->where('MONTH(bookings.completed_at)', date('m'))
    ->where('YEAR(bookings.completed_at)', date('Y'))
    ->groupBy('layanan.id')
    ->orderBy('jumlah', 'DESC')
    ->limit(4)
    ->get()->getResultArray();
```

Render dengan horizontal progress bars (CSS, no chart needed).

---

## 11. Pengujian — Sesuai Bab III Proposal

### 11.1 Black Box Testing

Bikin file `docs/BLACKBOX_TESTING.md` dengan skenario uji per fitur. Format tiap skenario:

```
| ID | Skenario | Input | Output yang Diharapkan | Hasil |
|---|---|---|---|---|
| BB-01 | Customer book layanan dengan slot tersedia | nama="Putri", hp="081234", layanan=Hair Treatment, tanggal=besok, jam=10:00 | Booking tersimpan, kode_booking generated, redirect ke sukses page | Berhasil/Gagal |
| BB-02 | Customer book dengan jam yang sudah terisi | sama dengan BB-01 tapi jam=10:00 (sudah ada booking) | Error "Slot waktu tidak tersedia" | Berhasil/Gagal |
| BB-03 | Customer book dengan durasi melebihi jam tutup | layanan 120 menit, jam=18:00 | Error "Durasi layanan melebihi jam tutup" | Berhasil/Gagal |
| BB-04 | Customer book dengan tanggal lewat | tanggal=kemarin | Error "Tanggal di luar jangkauan booking" | Berhasil/Gagal |
... dst
```

Minimal 30-40 skenario covering:
- Booking customer (sukses, slot terisi, melebihi jam tutup, di luar range tanggal, format HP salah)
- Cek booking (HP yang ada booking, HP yang tidak ada)
- Pembatalan customer (status pending, accepted, sudah completed, sudah lewat 2 jam batas)
- Login admin (sukses, password salah, akun nonaktif)
- Verifikasi via dashboard
- Verifikasi via Telegram
- Walk-in booking
- Set status completed → cek transaksi otomatis terbentuk
- CRUD layanan, stylist
- Dashboard metric
- Pengaturan Telegram (test message)

### 11.2 System Usability Scale (SUS)

Bikin file `docs/SUS.md` dengan kuesioner 10 pernyataan standar Brooke (terjemahan Indonesia) + formula perhitungan + interpretasi grading sesuai Tabel 2.6 proposal.

10 pernyataan SUS standar (terjemahan Indonesia konsisten dengan tinjauan pustaka Section 2.19):

1. Saya berpikir akan menggunakan sistem ini lagi.
2. Saya merasa sistem ini rumit untuk digunakan.
3. Saya merasa sistem ini mudah digunakan.
4. Saya membutuhkan bantuan dari orang lain atau teknisi dalam menggunakan sistem ini.
5. Saya merasa fitur-fitur sistem ini berjalan dengan semestinya.
6. Saya merasa ada banyak hal yang tidak konsisten pada sistem ini.
7. Saya merasa orang lain akan memahami cara menggunakan sistem ini dengan cepat.
8. Saya merasa sistem ini membingungkan.
9. Saya merasa tidak ada hambatan dalam menggunakan sistem ini.
10. Saya perlu membiasakan diri terlebih dahulu sebelum menggunakan sistem ini.

Skala Likert 1-5. Perhitungan: pernyataan ganjil (n-1), pernyataan genap (5-n), jumlah × 2.5.

Target: minimal 5-10 responden. Hasil rata-rata interpretasi:
- ≥ 81: Excellent (A)
- 68-81: Good (B)
- 68: Okay (C)
- 51-57: Poor (D)
- < 51: Worst (F)

---

## 12. Implementation Order (untuk Claude Code)

Eksekusi step by step. Sebelum tiap step, kasih plan dulu ke user untuk approval.

### Step 1: Setup foundation
- Update `composer.json` kalau perlu (mungkin nambah Guzzle untuk Telegram, optional).
- Create `app/Config/SalonSettings.php` untuk constants.
- Update `public/assets/css/salon-theme.css` dengan design tokens (lihat Section 13).
- Update layouts: `templates/layout_public.php`, `templates/layout_admin.php`, `templates/layout_auth.php`.
- Tambah font Google Fonts + Bootstrap Icons di layout master.

### Step 2: Migration database
- Bikin migration untuk semua tabel di Section 4.
- Run: `php spark migrate`.
- Test rollback: `php spark migrate:rollback`, lalu migrate lagi.

### Step 3: Seeder
- `SalonSeeder` dengan data sample sesuai Section 4.9.
- Run: `php spark db:seed SalonSeeder`.

### Step 4: Models
- `UserModel`, `StylistModel`, `StylistScheduleModel`, `LayananModel`, `BookingModel`, `BookingSlotModel`, `TransaksiModel`, `SettingModel`.
- Pakai entity class kalau perlu untuk relationship.

### Step 5: Public pages (customer flow)
- Implement `/`, `/layanan`, `/booking`, `/booking/sukses/{kode}`, `/cek-booking`, `/booking/{kode}`.
- Implement `/api/slots`.
- TEST end-to-end customer flow.

### Step 6: Admin auth + dashboard
- Implement `AdminFilter`, `PemilikFilter`.
- Implement `/admin/login`, session, logout.
- Implement `/admin/dashboard` (limited untuk admin, full untuk pemilik).

### Step 7: Admin booking management
- Implement `/admin/booking` (list + filter).
- Implement `/admin/booking/{id}` (detail + aksi).
- Implement walk-in `/admin/booking/walkin`.
- Implement `/admin/booking/jadwal` (timeline view).

### Step 8: Pemilik CRUD pages
- `/admin/layanan` CRUD.
- `/admin/stylist` CRUD + schedule.
- `/admin/transaksi`.
- `/admin/pengaturan` (4 tab).

### Step 9: Telegram integration
- `app/Libraries/TelegramNotifier.php`.
- `app/Commands/TelegramPoll.php`.
- Wire ke booking creation hook.
- Test dengan bot beneran.

### Step 10: WhatsApp template
- Update detail booking page admin dengan card WA template.
- Implement copy + open wa.me + mark sent.

### Step 11: Polish
- Empty states semua halaman.
- Loading states.
- Error pages 403/404.
- Toast/flash messages konsisten.

### Step 12: Documentation
- Update `README.md` dengan flow baru.
- Update `cara install.md`.
- Bikin `docs/BLACKBOX_TESTING.md` (Section 11.1).
- Bikin `docs/SUS.md` (Section 11.2).
- Bikin `docs/ERD.md` (visualisasi schema Section 4).

### Step 13: End-to-end manual test
- Run skenario di Section 11.1 manual.
- Fix bug.

---

## 13. Design Tokens & Komponen CSS

Paste di `public/assets/css/salon-theme.css`:

```css
:root {
  --color-ivory: #FAF6ED;
  --color-ivory-warm: #F0E4C8;
  --color-gold: #B8924A;
  --color-gold-dark: #856B2E;
  --color-gold-deep: #6F5524;
  --color-onyx: #0A0A0A;
  --color-charcoal: #1A1A1A;
  --color-cream-line: rgba(184, 146, 74, 0.33);
  
  --color-success: #25D366;
  --color-pending: #FFA500;
  --color-pending-bg: #FFE4B5;
  --color-danger: #C44545;
  --color-completed-bg: #DFF0D8;
  --color-completed-fg: #3C763D;
  
  --font-display: 'Playfair Display', 'Georgia', serif;
  --font-body: 'Inter', system-ui, sans-serif;
  
  --radius-sm: 6px;
  --radius-md: 9px;
  --radius-lg: 12px;
  --radius-pill: 999px;
}

body {
  background: var(--color-ivory);
  color: var(--color-charcoal);
  font-family: var(--font-body);
  font-weight: 400;
  line-height: 1.6;
}

/* Typography */
.display { font-family: var(--font-display); font-size: 2rem; font-weight: 500; letter-spacing: 6px; }
.h1 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 500; letter-spacing: 2px; color: var(--color-charcoal); }
.h2 { font-family: var(--font-display); font-size: 1.125rem; font-weight: 500; letter-spacing: 1px; }
.tagline { font-family: var(--font-display); font-style: italic; font-size: 0.8125rem; color: var(--color-gold-dark); letter-spacing: 1.5px; }
.caption { font-size: 0.75rem; color: var(--color-gold-dark); }
.label { font-size: 0.6875rem; font-weight: 500; letter-spacing: 1px; }

/* Buttons */
.btn-salon-primary {
  background: var(--color-onyx); color: var(--color-gold); border: none;
  border-radius: var(--radius-md); padding: 0.625rem 1.25rem;
  font-size: 0.8125rem; font-weight: 500; letter-spacing: 1.5px;
  cursor: pointer; transition: opacity 0.15s;
}
.btn-salon-primary:hover { opacity: 0.85; }

.btn-salon-secondary {
  background: white; color: var(--color-gold-dark); 
  border: 1px solid var(--color-gold); border-radius: var(--radius-md);
  padding: 0.5625rem 1.25rem; font-size: 0.8125rem; font-weight: 500;
}
.btn-salon-secondary:hover { background: var(--color-ivory-warm); }

.btn-salon-success {
  background: var(--color-success); color: white; border: none;
  border-radius: var(--radius-md); padding: 0.625rem 1.25rem;
  font-size: 0.8125rem; font-weight: 500;
  display: inline-flex; align-items: center; gap: 0.375rem;
}

.btn-salon-danger {
  background: transparent; color: var(--color-danger);
  border: 1px solid rgba(196, 69, 69, 0.4); border-radius: var(--radius-md);
  padding: 0.5625rem 1.125rem; font-size: 0.8125rem; font-weight: 500;
}

.btn-salon-ghost {
  background: transparent; color: var(--color-gold-dark); border: none;
  padding: 0.5rem 1rem; font-size: 0.8125rem; cursor: pointer;
}

/* Cards */
.card-salon {
  background: white; border: 0.5px solid var(--color-cream-line);
  border-radius: var(--radius-md); padding: 1rem;
}
.card-salon--active { border: 1.5px solid var(--color-gold); }

/* Form */
.form-salon-input {
  background: white; border: 0.5px solid var(--color-cream-line);
  border-radius: var(--radius-md); padding: 0.5625rem 0.75rem;
  font-size: 0.875rem; font-family: var(--font-body); width: 100%;
}
.form-salon-input:focus {
  outline: none; border-color: var(--color-gold);
}

/* Badge */
.badge-salon {
  display: inline-block; padding: 0.1875rem 0.625rem;
  border-radius: var(--radius-pill); font-size: 0.6875rem; font-weight: 500;
}
.badge-salon--pending { background: var(--color-pending-bg); color: var(--color-gold-dark); }
.badge-salon--accepted { background: rgba(184, 146, 74, 0.15); color: var(--color-gold-dark); }
.badge-salon--completed { background: var(--color-completed-bg); color: var(--color-completed-fg); }
.badge-salon--rejected { background: rgba(196, 69, 69, 0.12); color: var(--color-danger); }
.badge-salon--cancelled { background: rgba(0, 0, 0, 0.08); color: #666; }

/* Slot grid */
.slot-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin: 1rem 0;
}
@media (min-width: 768px) { .slot-grid { grid-template-columns: repeat(6, 1fr); } }
@media (min-width: 992px) { .slot-grid { grid-template-columns: repeat(8, 1fr); } }

.slot {
  width: 100%; aspect-ratio: 2/1; border-radius: 8px;
  font-size: 0.875rem; font-weight: 500;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; user-select: none;
}
.slot--available { background: white; border: 1px solid var(--color-gold); color: var(--color-charcoal); }
.slot--available:hover { background: var(--color-ivory-warm); }
.slot--selected { background: var(--color-gold); border: 1px solid var(--color-gold); color: white; }
.slot--held { background: var(--color-ivory-warm); border: 1px solid var(--color-gold); color: var(--color-gold-dark); cursor: default; }
.slot--booked {
  background: #d4cfc0; border: 1px solid #b8b0a0; color: #6e6657;
  text-decoration: line-through; cursor: not-allowed; position: relative;
}
.slot--booked::after {
  content: ''; position: absolute; inset: 0;
  background: repeating-linear-gradient(45deg, transparent, transparent 4px, rgba(0,0,0,0.05) 4px, rgba(0,0,0,0.05) 8px);
  pointer-events: none;
}
.slot--past, .slot--insufficient {
  background: #ede8dd; border: 1px solid #d4cfc0; color: #b0a89a;
  cursor: not-allowed; text-decoration: line-through;
}

/* Date strip */
.date-strip {
  display: flex; gap: 8px; overflow-x: auto;
  padding-bottom: 8px; scrollbar-width: thin;
}
.date-strip::-webkit-scrollbar { height: 4px; }
.date-strip-item {
  flex: 0 0 80px;
  background: white; border: 0.5px solid var(--color-cream-line);
  border-radius: var(--radius-md); padding: 0.625rem 0.5rem;
  text-align: center; cursor: pointer; transition: all 0.15s;
}
.date-strip-item:hover { background: var(--color-ivory-warm); }
.date-strip-item--selected { background: var(--color-gold); border-color: var(--color-gold); color: white; }
.date-strip-item--today { border-color: var(--color-gold); border-width: 1.5px; }
.date-strip-item__hari { font-size: 0.6875rem; color: var(--color-gold-dark); font-weight: 500; }
.date-strip-item__tanggal { font-family: var(--font-display); font-size: 1.25rem; font-weight: 500; }
.date-strip-item__bulan { font-size: 0.6875rem; color: var(--color-gold-dark); }
.date-strip-item--selected .date-strip-item__hari,
.date-strip-item--selected .date-strip-item__bulan { color: rgba(255,255,255,0.8); }

/* Ornament rule */
.ornament-rule {
  display: flex; align-items: center; justify-content: center;
  gap: 0.75rem; margin: 1.5rem 0;
}
.ornament-rule__line { flex: 0 0 50px; height: 0.5px; background: var(--color-gold); }
.ornament-rule__icon { color: var(--color-gold); font-size: 0.75rem; }

/* Empty state */
.empty-state {
  text-align: center; padding: 3rem 1rem;
}
.empty-state__icon { font-size: 2.5rem; color: var(--color-gold); opacity: 0.5; }

/* Public navbar */
.nav-salon {
  background: var(--color-ivory); border-bottom: 0.5px solid var(--color-cream-line);
  padding: 0.875rem 1.5rem;
  display: flex; justify-content: space-between; align-items: center;
}
.nav-salon__wordmark {
  font-family: var(--font-display); font-size: 1rem; font-weight: 500;
  letter-spacing: 4px; color: var(--color-charcoal);
}
.nav-salon__tagline {
  font-family: var(--font-display); font-style: italic; font-size: 0.75rem;
  color: var(--color-gold-dark); letter-spacing: 1px;
}

/* Admin sidebar */
.sidebar-admin {
  background: var(--color-onyx); width: 220px; padding: 1.5rem 0.75rem;
  display: flex; flex-direction: column; gap: 0.25rem;
  min-height: 100vh;
}
.sidebar-admin__item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.625rem 0.875rem; border-radius: var(--radius-sm);
  color: rgba(250, 246, 237, 0.6); text-decoration: none;
  font-size: 0.8125rem; transition: all 0.15s;
}
.sidebar-admin__item:hover { color: var(--color-gold); }
.sidebar-admin__item.active {
  background: rgba(184, 146, 74, 0.18); color: var(--color-gold);
}
@media (max-width: 991px) {
  .sidebar-admin { width: 64px; }
  .sidebar-admin__item span { display: none; }
}
```

---

## 14. Acceptance Criteria — Apa yang berarti "selesai"

✅ Customer (tanpa login) bisa buka `/booking`, isi nama+HP, pilih layanan, pilih tanggal (cuma 7 hari ke depan), pilih jam di grid cinema-style, submit → dapat kode booking + halaman sukses + tombol WhatsApp.

✅ Slot 30 menit yang dipilih + slot berurutan sesuai durasi layanan otomatis ter-held (visual jadi cream warm di grid).

✅ Slot yang sudah ada booking lain (status pending/accepted/completed) muncul sebagai "booked" (grey strikethrough) di grid, tidak bisa diklik.

✅ Slot yang udah lewat (hari ini, jam udah lewat) muncul sebagai "past" disabled.

✅ Submit booking dengan slot terisi → ditolak dengan error message.

✅ Submit booking dengan durasi yang exceed jam tutup → ditolak.

✅ Customer bisa cek status via `/cek-booking` input HP.

✅ Customer bisa batalkan booking lewat halaman detail, kalau status masih bisa.

✅ Telegram bot kirim notifikasi ke owner setiap booking baru, dengan inline button Verifikasi/Tolak.

✅ Owner klik tombol di Telegram → status booking berubah, message di-edit jadi konfirmasi.

✅ Admin login via `/admin` (TIDAK ada link di navbar publik), masuk dashboard.

✅ Admin/pemilik bisa kelola booking dari dashboard: verifikasi, tolak, selesaikan, batalkan.

✅ Saat booking diset ke `completed`, transaksi otomatis tercipta dengan nominal = harga layanan.

✅ Dashboard pemilik tampilin: pendapatan hari ini, chart 7 hari, layanan terpopuler, booking terbaru.

✅ Walk-in booking bisa diinput admin lewat `/admin/booking/walkin`.

✅ Pemilik bisa CRUD layanan dan stylist.

✅ Pengaturan Telegram + WhatsApp + info salon bisa diubah dari `/admin/pengaturan`.

✅ Tampilan konsisten dengan tema black-gold + cream/ivory di SEMUA halaman.

✅ Responsive: customer pages dipakai dari mobile (375px+) tanpa horizontal scroll.

✅ Black Box Testing scenarios di `docs/BLACKBOX_TESTING.md` 100% pass.

✅ SUS instrument siap di `docs/SUS.md`.

✅ Demo accounts berfungsi: owner@swbeautysalon.local / Password123! dan admin@swbeautysalon.local / Password123!.

✅ `php spark migrate:rollback` clean, `php spark migrate` clean tanpa error.

---

## 15. Catatan untuk Claude Code

1. **Jangan ubah file vendor/** atau composer dependencies kecuali bener-bener perlu.
2. **Selalu pakai prepared statements / query builder** untuk anti SQL injection.
3. **CSRF token** wajib di semua POST form (CI4 default, jangan disabled).
4. **Password hashing** pakai `password_hash($pass, PASSWORD_BCRYPT)`.
5. **Tanggal/jam format** di DB: ISO 8601 (`YYYY-MM-DD HH:MM:SS`). Format Indonesia untuk display: "12 Mei 2026, 10:00 WITA".
6. **Indonesian language** untuk SEMUA UI text, error messages, dan labels. JANGAN ada string English yang kelihatan customer.
7. **Jangan over-engineer**. Ini project skripsi UMKM, bukan startup unicorn. Code yang readable > code yang clever.
8. **Output sebelum coding**: tiap step, tampilkan plan (file yang mau dibuat/diubah, struktur method, query SQL utama). Tunggu user approve baru eksekusi.
9. **Test sebelum lanjut**. Habis tiap step, run aplikasi, klik manual untuk pastikan jalan. Kalau ada error, fix dulu sebelum step berikutnya.
10. **Commit message rapi**: format `feat(scope): description` (Conventional Commits).

---

---

# CLAUDE DESIGN PROMPT (opsional, di-paste ke claude.ai/design)

> Pakai prompt ini di Claude Design HANYA KALAU lo mau generate visual high-fidelity dulu sebelum implementasi. Token Claude Design boros, jadi mungkin lebih hemat langsung ke Claude Code dengan design tokens di Section 13. Kalau tetep mau pakai, paste prompt di bawah ini di chat awal Claude Design.

```
Saya ingin membuat design system dan mockup high-fidelity untuk "SW Beauty Salon", aplikasi web booking salon berbasis CodeIgniter 4 + Bootstrap 5 + MySQL untuk UMKM salon kecantikan di Tabanan, Bali.

KARAKTER BRAND
- Nama: SW Beauty Salon
- Tagline: "Reserve · Refine · Revel"
- Vibe: salon lokal upscale yang elegan, hangat, terpercaya. Bayangkan resepsionis Aman Resort Bali bertemu dengan boutique salon Eropa.
- Audience: wanita 25-45, Indonesian, pasar Tabanan-Denpasar.
- Mood: tenang, intim, considered. Bukan trendy, bukan corporate, bukan playful.

PALETTE WAJIB (hex exact)
- Ivory background: #FAF6ED
- Warm cream (held slot): #F0E4C8
- Antique gold accent: #B8924A
- Aged gold (text on cream): #856B2E
- Onyx (primary surface, text): #0A0A0A
- Charcoal body text: #1A1A1A
- WhatsApp green (only for WA CTA): #25D366
- Amber pending: #FFA500 with #FFE4B5 background
- Soft green completed: #DFF0D8 background, #3C763D text
- Muted red rejected: #C44545

TIPOGRAFI
- Display & wordmark: Playfair Display 500 weight, italic 500. Letter-spacing 4-6px untuk wordmark, 1-2px untuk heading.
- Body & UI: Inter 400 dan 500. HANYA 2 weight ini, jangan ada 600 atau 700.

JANGAN GUNAKAN
- Gradient (di mana pun).
- Drop shadow, glow, blur.
- Emoji atau ikon cartoon.
- Warna pink, ungu, atau "AI startup palette".
- Bold sans-serif headline (pakai serif untuk elegance).
- Glassmorphism, brutalism, neon, skeuomorphism.
- Rounded corner > 12px.

KOMPONEN YANG WAJIB ADA
1. Button: btn-salon-primary (onyx bg gold text), btn-salon-secondary (white outline gold), btn-salon-success (WhatsApp green), btn-salon-danger (red outline), btn-salon-ghost.
2. Card: card-salon (default), card-salon--active (1.5px gold border), card variants dengan border-left 3px untuk status pending/accepted/completed.
3. Form input: 0.5px gold-translucent border, focus state gold (BUKAN Bootstrap blue).
4. Status badge pill: pending (amber), accepted (gold), completed (green), rejected (red), cancelled (gray).
5. Cinema-style time slot grid (PALING PENTING — lihat detail di bawah).
6. Date strip horizontal 8 hari.
7. Ornamental rule: garis gold tipis + ikon (sparkles/diamond) di tengah.
8. Top navigation publik: wordmark kiri + tagline italic, nav links tengah, primary button kanan.
9. Admin sidebar onyx dengan icon-only mode di tablet.
10. Empty states: outline icon besar, serif title, italic hint.

TIME SLOT GRID (paling penting, ini novelty produk)
- 22 slot dari 08:00 sampai 18:30 (interval 30 menit).
- Grid 4 kolom di mobile, 6 di tablet, 8 di desktop.
- Setiap kotak proporsi 2:1 (lebar 2× tinggi), border-radius 8px, font 14px medium.
- 5 state visual jelas berbeda:
  * Tersedia: bg putih, border 1px gold, hover warm cream.
  * Dipilih (start time): bg gold solid, text putih.
  * Auto-held (bagian durasi layanan, antara start dan end): bg warm cream, text gold-dark, cursor default tidak clickable.
  * Sudah dibooked customer lain: bg abu-abu (#d4cfc0), border abu, text strikethrough, dengan repeating diagonal stripe overlay 45 derajat untuk tegas "tidak available".
  * Sudah lewat (untuk tanggal hari ini): bg cream pudar (#ede8dd), text gray strikethrough.
- Legend di atas grid: 4 kotak kecil dengan label "Tersedia / Dipilih / Terisi / Tidak tersedia".

HALAMAN YANG PERLU DIDESIGN (urutan prioritas)
1. Landing publik / (hero onyx + grid layanan + why us + footer).
2. Form booking /booking (paling kritis — punya semua: data customer, layanan picker, date strip, slot grid, ringkasan, submit).
3. Halaman sukses booking /booking/sukses (success icon, detail card, WhatsApp button, info Telegram terkirim).
4. Cek booking /cek-booking (form HP + hasil list).
5. Admin login /admin (centered card, NO publik navbar).
6. Admin dashboard /admin/dashboard (sidebar + metric cards + chart 7 hari + booking terbaru).
7. Admin booking management /admin/booking (filter chips + table dengan action buttons per status).
8. Admin pengaturan /admin/pengaturan (tabbed: Salon, Telegram, WhatsApp, Akun).

BAHASA UI
SEMUA copy dalam Bahasa Indonesia. Contoh:
- "Booking sekarang" bukan "Book now"
- "Pilih tanggal" bukan "Choose date"
- "Menunggu verifikasi" bukan "Pending verification"
- "Diterima" / "Ditolak" / "Selesai" / "Batal" untuk status
- "Layanan unggulan" / "Stylist tersertifikasi" / "Reservasi cepat dan mudah"

KONTEKS SPESIFIK
- Customer TIDAK punya akun atau login. Mereka booking dengan input nama + nomor HP saja, dan cek status pakai nomor HP + kode booking.
- Stylist picker DISEMBUNYIKAN dari customer flow (otomatis ke stylist default).
- Range tanggal booking: hari ini + 7 hari ke depan saja.
- Jam operasional salon: 08:00 - 19:00 (slot 30 menit per unit).
- Admin login URL tidak dipublikasikan di navbar — diakses via URL /admin saja.
- Workflow: customer book → status pending_verification → owner verifikasi via Telegram bot ATAU dashboard → status accepted → salon services → owner mark completed → transaksi auto-created.

DELIVERABLE PHASE 1
Generate design system dulu di canvas: token warna, type scale, semua button variants, semua card variants, semua badge variants, ornamental rule, dan satu mockup time slot grid lengkap dengan 5 state visual jelas dan legend di atasnya.

Setelah saya approve design system, lanjut generate halaman per halaman dari prioritas di atas, satu halaman per chat agar tidak boros token.
```

---

*End of plan. Total deliverable: 1 file untuk Claude Code (this) + 1 prompt untuk Claude Design (di atas).*