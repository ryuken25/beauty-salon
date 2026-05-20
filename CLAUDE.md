# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**SW Beauty Salon** — CodeIgniter 4 booking app for a salon in Tabanan, Bali. Booking is **fully public** — customers do not have accounts. Login at `/login` is for **admin & pemilik only** (2 actors). Stack: PHP 8.1+, MySQL/MariaDB, Bootstrap 5 + Bootstrap Icons + Chart.js via CDN, WhatsApp manual via `wa.me`. **No Telegram integration.**

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

Demo accounts (password `Password123!`):
- Pemilik: `owner@swbeautysalon.local`
- Admin: `admin@swbeautysalon.local`

## Architecture

### Roles & access
- Customer flow is **fully public** (no auth, no account). Booking by name + WhatsApp number (+ optional email); lookup status & self-cancel at `/cek-booking` by HP number with a confirm modal; admin can also cancel from the dashboard.
- Login lives at `/login` (alias `/admin/login`) — **admin & pemilik only**, not linked from the public navbar.
- **Pemilik = Admin superset.** One unified [layouts/panel.php](app/Views/layouts/panel.php) with a role-aware sidebar. `/admin/*` (filter `admin` = admin + pemilik): dashboard, booking verify/reject/cancel/complete, walk-in, jadwal, pelanggan, transaksi, pengaturan. `/owner/*` (filter `owner` = pemilik only): laporan (analytics), layanan CRUD, stylist CRUD. Admin typing `/owner/*` gets bounced to `/admin/dashboard`.
- Stylist is a labelled attribute on each booking — full CRUD with soft delete at `/owner/stylist`. Slots stay salon-wide (stylist is not a slot dimension).

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
- `users` (admin & pemilik only — no pelanggan role), `layanan` (soft delete), `stylists` (soft delete), `bookings` (`kode_booking` format `SW-YYYYMMDD-NNN`, `nama_pelanggan`, `nomor_hp_pelanggan`, `email_pelanggan` nullable, `layanan_id`, `stylist_id` nullable, `slot_mulai`, `slot_selesai`, `jumlah_slot`, status, `cancellation_reason`, …), `booking_slots`, `transaksi`, `settings` (key/value), `booking_logs`.
- Baseline migration: `2026-05-12-100000_ResetAndCreateSalonSchema.php`. Add NEW dated migrations for any further schema change — **never** rely on `$db->getFieldNames()` inside a migration (CI4 caches it for the whole migrate run; probe `information_schema` instead).

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
Customer self-cancels at `/cek-booking`: enter HP → see bookings → "Batalkan" opens a confirm modal (optional reason) → `POST /booking/{kode}/batal`. Allowed while `pending_verification` or `accepted` AND ≥ 2 jam sebelum `slot_mulai`. Logic in [BookingService::cancel](app/Services/BookingService.php). Admin can also cancel from `/admin/booking/{id}`.

## Hard constraints (from IMPLEMENTATION_PLAN aka [implementation.md](implementation.md))

- **Stack lock:** PHP 8.1+, CodeIgniter 4, MySQL, Bootstrap 5, Chart.js. No React/Vue/Tailwind/Sass/build tools. Custom CSS lives in [public/assets/css/salon-theme.css](public/assets/css/salon-theme.css).
- **No paid WhatsApp API.** `wa.me` links + copy-to-clipboard templates only.
- **No payment gateway**, no ML/AI/forecasting, no multi-branch, no inventory.
- **Customer tidak punya akun.** Booking anonymous (nama + HP + email opsional). Cancel lewat kode + HP. No `/register`, no pelanggan login.
- **Login URL tidak ditampilkan di navbar publik.**
- **Jam operasional & range hari** dapat diubah di Pengaturan; default 08:00–19:00 dan 7 hari ke depan.
- **Bahasa Indonesia** di semua label UI dan error message.

## Docs
- [implementation.md](implementation.md) — IMPLEMENTATION_PLAN lengkap.
- [docs/ERD.md](docs/ERD.md) — schema overview.
- [docs/BLACKBOX_TESTING.md](docs/BLACKBOX_TESTING.md) — skenario uji.
- [docs/SUS.md](docs/SUS.md) — instrumen System Usability Scale.
- [cara install.md](cara%20install.md) — install steps.
