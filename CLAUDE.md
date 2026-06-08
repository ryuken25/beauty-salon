# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**SW Beauty Salon** — CodeIgniter 4 booking app for a salon in Tabanan, Bali. As of 2026-06-04: customers **must have accounts**. **Login terpadu di `/login` untuk semua role**: satu field "Email atau Nomor WhatsApp" + password — pelanggan pakai nomor WA, staff (admin/pemilik) pakai email; redirect otomatis sesuai role. `/admin/login` lama di-redirect ke `/login`. Stack: PHP 8.1+, MySQL/MariaDB, Bootstrap 5 + Bootstrap Icons + Chart.js via CDN, WhatsApp manual via `wa.me`. **No Telegram integration, no stylist.**

## Common Commands

```bash
composer install
cp .env.localhost .env          # Windows: copy .env.localhost .env
php spark migrate               # baseline reset migration
php spark migrate:rollback
php spark db:seed SalonSeeder
php spark serve                 # http://localhost:8080
php spark routes                # inspect routes
vendor/bin/phpunit              # run tests
```

PHP lint sweep:
```bash
php -r "foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.')) as $f) { if ($f->isFile() && strtolower($f->getExtension()) === 'php' && strpos($f->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) === false) { passthru('php -l ' . escapeshellarg($f->getPathname()), $code); if ($code !== 0) exit($code); } }"
```

Demo accounts (password `Password123!`) — semua login di `/login`:
- Pemilik: email `owner@swbeautysalon.local`
- Admin: email `admin@swbeautysalon.local`
- Pelanggan: nomor WA `6281338109102`

## Architecture

### Roles & access
- **Login terpadu** (`Auth::login`, view [auth/login.php](app/Views/auth/login.php)): satu form, field `identifier` = email ATAU nomor WA + `password`. Deteksi: mengandung `@` → cari by email; selain itu → `normalizePhone()` lalu cari by `nomor_hp`. Cocok → `issueSession()` → redirect by role (`pelanggan` → `/pelanggan/dashboard`; `admin`/`pemilik` → `/admin/dashboard`). Rate-limit 8 gagal / 15 menit / IP via cache `login_fail_*`. Sudah login lalu buka `/login` → auto-redirect ke dashboard sesuai role. `logout` selalu kembali ke `/login`. URL lama `/admin/login` & `/admin/logout` di-redirect demi kompatibilitas.
- **Pelanggan = customer accounts.** Register `/register` (nama + nomor WA + password). Reset password admin-only (`/admin/pelanggan`); `/lupa-password` is info-only.
- **Staff** (admin/pemilik) login dengan email lewat form `/login` yang sama.
- Booking (`/booking` + `/booking/sukses/{kode}`) sits behind the `customer` filter — guests bounce to `/login`. Pelanggan dashboard `/pelanggan/dashboard` is history-only (booking own + 'Cek/Batal' deep link).
- **Pemilik = Admin superset.** One unified [layouts/panel.php](app/Views/layouts/panel.php) with a role-aware sidebar. `/admin/*` (filter `admin` = admin + pemilik): dashboard, booking verify/reject/cancel/complete + DP-verify, walk-in, jadwal, pelanggan account management, transaksi, pengaturan. `/owner/*` (filter `owner` = pemilik only): laporan (analytics), layanan CRUD. Admin typing `/owner/*` gets bounced to `/admin/dashboard`.
- Public `/cek-booking` (no auth): **kode booking only** (dikirim ke pelanggan via email setelah booking sukses) → tampil read-only detail booking. Tombol "Batalkan" hanya muncul kalau session pelanggan aktif (redirect ke `/pelanggan/booking/{kode}/batal`). Walk-in tanpa akun dibatalkan oleh admin. Brute-force enumerasi kode dibatasi: 5 kegagalan / 15 menit / IP via cache `cek_fail_*`.
- Logged-in pelanggan punya halaman detail spesifik di `/pelanggan/booking/{kode}` (ownership via `user_id`, bukan via kode publik) — di sinilah flow batal pelanggan login berlangsung. Dashboard pelanggan link "Lihat detail" mengarah ke sini.
- Navbar publik (`layouts/public.php`) sadar login: guest → "Masuk" + "Pesan sekarang" (→ `/login`); pelanggan → dropdown profil (Dashboard / Booking baru / Logout); admin/pemilik → link "Panel Admin" + Logout.

### Fixed-slot domain (load-bearing)
- All times are 30-minute slots from `jam_buka` to `jam_tutup` (default 08:00–19:00, settable in `/admin/pengaturan`).
- A booking with `pending_verification`/`accepted`/`completed` holds N consecutive slots in `booking_slots` (status `held`). On `rejected`/`cancelled`, slot rows are deleted.
- Cinema-style picker: 5 visual states (available / selected / held / booked / past or insufficient). Frontend logic in [public/booking_form.php](app/Views/public/booking_form.php) and [admin/booking/walkin.php](app/Views/admin/booking/walkin.php) calls `/api/slots`.
- Server-side validation in [SlotService::validateBookingSlot](app/Services/SlotService.php) is the authoritative gate; never trust the JS-side.

### Service layer
- [BookingService](app/Services/BookingService.php) — full lifecycle (`create`, `verify`, `reject`, `cancel`, `complete`, `markWaSent`) + audit `logEvent()`.
- [SlotService](app/Services/SlotService.php) — availability + slot validation.
- [WhatsAppTemplateService](app/Services/WhatsAppTemplateService.php) — renders templates from settings, builds `wa.me` links. Manual-only — never integrate a paid WhatsApp API.

### Schema (Indonesian column names)
- `users` (roles admin/pemilik/pelanggan; `email` nullable; `nomor_hp` UNIQUE — MySQL allows many NULLs for staff rows), `layanan` (soft delete), `bookings` (`kode_booking` format `SW-YYYYMMDD-NNN`, `user_id` FK→users nullable for walk-in, `nama_pelanggan`, `nomor_hp_pelanggan`, `email_pelanggan` nullable, `layanan_id`, `slot_mulai`, `slot_selesai`, `jumlah_slot`, `harga_layanan`, `dp_amount`, `dp_proof_path`, `payment_status` ENUM('unpaid','dp_uploaded','dp_verified'), status, `cancellation_reason`, …), `booking_slots` (the column is `slot_waktu`, not `slot` — bites in seeders), `transaksi` (`booking_id`, `nominal`, `base_price`, `additional_price`, `metode_bayar`, `tanggal_transaksi`, `catatan` — no `kode_transaksi`), `settings` (key/value), `booking_logs`.
- Baseline: `2026-05-12-100000_ResetAndCreateSalonSchema.php`. Latest: `2026-06-04-100000_AuthPelangganDpRemoveStylist.php`. Add NEW dated migrations for any further schema change — **never** rely on `$db->getFieldNames()` inside a migration (CI4 caches it for the whole migrate run; probe `information_schema` instead).

### Booking status vocabulary
| Internal | UI label | Slot held? |
|---|---|---|
| `pending_verification` | Menunggu Verifikasi | yes |
| `accepted` | Diterima | yes |
| `rejected` | Ditolak | no |
| `cancelled` | Batal | no |
| `completed` | Selesai | historical |

Transactions in `transaksi` are created **once** on transition to `completed` (nominal = layanan.harga, payment method manual).

### Customer cancel rule
Dua jalur batal customer-side (logika sama: ≥ 2 jam sebelum `slot_mulai`, status ∈ {pending_verification, accepted}):
  - **Pelanggan login**: `/pelanggan/booking/{kode}/batal` (konfirmasi page) → POST → BookingService::cancel('pelanggan', user_id, reason). Ownership tervalidasi via session `user_id`.
  - **Publik dari /cek-booking**: hanya kalau user login sebagai pelanggan, jalur ini di-redirect ke jalur pertama (rekomendasi keamanan B). Walk-in (tanpa akun) hanya bisa dibatalkan admin.
Logic di [BookingService::cancel](app/Services/BookingService.php). Admin juga bisa cancel dari `/admin/booking/{id}`. **Halaman sukses-batal tidak memuat tombol WhatsApp** — pembatalan sudah otomatis tercatat di sistem; admin lihat di panel.

## Hard constraints

- **Stack lock:** PHP 8.1+, CodeIgniter 4, MySQL, Bootstrap 5, Chart.js. No React/Vue/Tailwind/Sass/build tools. Custom CSS lives in [public/assets/css/salon-theme.css](public/assets/css/salon-theme.css).
- **No paid WhatsApp API.** `wa.me` links + copy-to-clipboard templates only.
- **No payment gateway**, no ML/AI/forecasting, no multi-branch, no inventory.
- **Customer wajib login** untuk booking. `/register` aktif (nama + nomor WA + password). `/cek-booking` publik (nomor WA only) untuk lookup/cancel tanpa login.
- **DP rule**: harga ≤ Rp 50.000 → DP penuh; > 50.000 → DP Rp 50.000. Wajib upload bukti saat booking online. Walk-in (admin) tidak butuh DP.
- **Auto-cancel**: booking pending yang lewat jadwal dibatalkan via `php spark bookings:auto-cancel` (jadwalkan Task Scheduler/cron) + lazy sweep (max 1× per 5 menit) di admin dashboard + booking index.
- **Jam operasional & range hari** dapat diubah di Pengaturan; default 08:00–19:00 dan 7 hari ke depan.
- **Bahasa Indonesia** di semua label UI dan error message.

## Docs
- [docs/ERD.md](docs/ERD.md) — schema overview.
- [docs/BLACKBOX_TESTING.md](docs/BLACKBOX_TESTING.md) — skenario uji.
- [docs/SUS.md](docs/SUS.md) — instrumen System Usability Scale.
- [cara install.md](cara%20install.md) — install steps.
