# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**SW Beauty Salon** — CodeIgniter 4 booking app for a salon in Tabanan, Bali. As of 2026-06-04: customers **must have accounts**. Pelanggan login at `/login` (nomor WA + password); staff (admin/pemilik) login at `/admin/login` (email + password). Stack: PHP 8.1+, MySQL/MariaDB, Bootstrap 5 + Bootstrap Icons + Chart.js via CDN, WhatsApp manual via `wa.me`. **No Telegram integration, no stylist.**

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
- Pemilik: `owner@swbeautysalon.local` (via `/admin/login`)
- Admin: `admin@swbeautysalon.local` (via `/admin/login`)
- Pelanggan: nomor WA `6281338109102` (via `/login`)

## Architecture

### Roles & access
- **Pelanggan = customer accounts.** Register `/register` (nama + nomor WA + password), login `/login` (nomor WA + password). Reset password admin-only (`/admin/pelanggan`); `/lupa-password` is info-only.
- **Staff** (admin/pemilik) login separately at `/admin/login` (email + password). Two-form split so neither side can authenticate the other role.
- Booking (`/booking` + `/booking/sukses/{kode}`) sits behind the `customer` filter — guests bounce to `/login`. Pelanggan dashboard `/pelanggan/dashboard` is history-only (booking own + 'Cek/Batal' deep link).
- **Pemilik = Admin superset.** One unified [layouts/panel.php](app/Views/layouts/panel.php) with a role-aware sidebar. `/admin/*` (filter `admin` = admin + pemilik): dashboard, booking verify/reject/cancel/complete + DP-verify, walk-in, jadwal, pelanggan account management, transaksi, pengaturan. `/owner/*` (filter `owner` = pemilik only): laporan (analytics), layanan CRUD. Admin typing `/owner/*` gets bounced to `/admin/dashboard`.
- Public `/cek-booking` (no auth): nomor WA only → list every booking on that number → dedicated cancel pages.

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
Customer self-cancels at `/cek-booking`: enter HP → see bookings → "Batalkan" opens a confirm modal (optional reason) → `POST /booking/{kode}/batal`. Allowed while `pending_verification` or `accepted` AND ≥ 2 jam sebelum `slot_mulai`. Logic in [BookingService::cancel](app/Services/BookingService.php). Admin can also cancel from `/admin/booking/{id}`.

## Hard constraints (from IMPLEMENTATION_PLAN aka [implementation.md](implementation.md))

- **Stack lock:** PHP 8.1+, CodeIgniter 4, MySQL, Bootstrap 5, Chart.js. No React/Vue/Tailwind/Sass/build tools. Custom CSS lives in [public/assets/css/salon-theme.css](public/assets/css/salon-theme.css).
- **No paid WhatsApp API.** `wa.me` links + copy-to-clipboard templates only.
- **No payment gateway**, no ML/AI/forecasting, no multi-branch, no inventory.
- **Customer wajib login** untuk booking. `/register` aktif (nama + nomor WA + password). `/cek-booking` publik (nomor WA only) untuk lookup/cancel tanpa login.
- **DP rule**: harga ≤ Rp 50.000 → DP penuh; > 50.000 → DP Rp 50.000. Wajib upload bukti saat booking online. Walk-in (admin) tidak butuh DP.
- **Auto-cancel**: booking pending yang lewat jadwal dibatalkan via `php spark bookings:auto-cancel` (jadwalkan Task Scheduler/cron) + lazy sweep (max 1× per 5 menit) di admin dashboard + booking index.
- **Jam operasional & range hari** dapat diubah di Pengaturan; default 08:00–19:00 dan 7 hari ke depan.
- **Bahasa Indonesia** di semua label UI dan error message.

## Docs
- [implementation.md](implementation.md) — IMPLEMENTATION_PLAN lengkap.
- [docs/ERD.md](docs/ERD.md) — schema overview.
- [docs/BLACKBOX_TESTING.md](docs/BLACKBOX_TESTING.md) — skenario uji.
- [docs/SUS.md](docs/SUS.md) — instrumen System Usability Scale.
- [cara install.md](cara%20install.md) — install steps.
