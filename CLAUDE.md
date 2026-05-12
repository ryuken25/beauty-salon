# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**SW Beauty Salon** — CodeIgniter 4 booking app for a beauty salon. PHP 8.1+, MySQL/MariaDB, Bootstrap 5 + Chart.js via CDN, Telegram Bot API (optional), WhatsApp manual via `wa.me` (no paid API).

## Common Commands

```bash
composer install
cp .env.localhost .env          # Windows: copy .env.localhost .env
php spark migrate               # apply migrations
php spark migrate:rollback      # rollback (must stay clean)
php spark db:seed SalonSeeder   # seed demo data + demo accounts
php spark serve                 # http://localhost:8080
php spark routes                # inspect routes (must have no duplicates/orphans)
php spark telegram:poll         # long-polling for Telegram bot (local, no HTTPS)
vendor/bin/phpunit              # run all tests
vendor/bin/phpunit --filter TestName tests/path/To/FileTest.php   # single test
```

PHP lint sweep (used in CI-like checks):
```bash
php -r "foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.')) as $f) { if ($f->isFile() && strtolower($f->getExtension()) === 'php' && strpos($f->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) === false) { passthru('php -l ' . escapeshellarg($f->getPathname()), $code); if ($code !== 0) exit($code); } }"
```

Demo accounts (password `Password123!`): `owner@swbeautysalon.local`, `admin@swbeautysalon.local`, `pelanggan@example.com`.

## Architecture

Standard CI4 layout. The non-obvious pieces are concentrated in **services** and the **booking/slot domain**.

### Layered domain code
- **Controllers** ([app/Controllers/Admin/](app/Controllers/Admin/), [app/Controllers/Customer/](app/Controllers/Customer/)) — thin; delegate state changes to services. Routes live in [app/Config/Routes.php](app/Config/Routes.php) and use Indonesian URL segments (`pelanggan/booking/baru`, `admin/booking/jadwal`, etc.) while controller namespaces are English (`Customer\BookingController`, `Admin\BookingController`).
- **Services** ([app/Services/](app/Services/)) hold business logic:
  - `BookingService` — booking lifecycle state transitions (accept/reject/cancel/complete). Central place for any new lifecycle hook.
  - `SlotService` — slot availability calculation. **The slot model is the load-bearing piece** of this app; see below.
  - `TelegramService` — Bot API calls + callback handling. Currently optional (no-op when token empty); spec calls for it to become mandatory in `production`.
  - `WhatsAppTemplateService` — generates `wa.me` URLs + copyable message templates. Manual-only by design — never integrate a paid WhatsApp API.
- **Models** ([app/Models/](app/Models/)) — extend `BaseAppModel`. Notable: `BookingSlotModel` (the join/lock table for slot occupancy), `AppSettingModel` (key/value settings store), `TelegramActionTokenModel` (signed callback tokens), `NotificationLogModel`.
- **Migrations** live in [app/Database/Migrations/](app/Database/Migrations/). Current baseline is a **single** consolidated migration `2026-04-30-000001_CreateSalonTables.php`. Add NEW dated migrations for any schema change — never edit the baseline.
- **Commands** — `php spark telegram:poll` ([app/Commands/TelegramPoll.php](app/Commands/TelegramPoll.php)) is the dev-mode equivalent of the webhook.

### Slot model (read before touching booking code)
Today the app uses **fixed 30-minute slots**. A booking with statuses `pending_verification`, `accepted`, or `completed` holds its slot; `rejected` / `cancelled` release it. The slot-holding logic lives in `SlotService` and `BookingSlotModel`.

The roadmap in [implementation.md](implementation.md) replaces this with **dynamic slot duration** (`services.durasi_menit` defines how many consecutive 30-min slots a booking occupies, snap-to-30 start times). Anything that touches slot math (`SlotService`, `BookingModel`, admin schedule view, customer booking form) must respect that durations span multiple base slots.

### Telegram integration
Two ingress paths share the same handler logic:
- **Webhook** — `POST /telegram/webhook` → `TelegramController::webhook()` (production w/ HTTPS).
- **Long polling** — `php spark telegram:poll` runs the same processing loop locally.

Allowed admin chats are gated by `TELEGRAM_ALLOWED_CHAT_IDS` (comma-separated). `TelegramActionTokenModel` issues signed tokens for inline-button callbacks so URLs/callbacks can't be forged.

### Booking status vocabulary (must match the UI labels)
| Internal | UI label (ID) | Slot held? |
|---|---|---|
| `pending_verification` | Menunggu Verifikasi | yes |
| `accepted` | Diterima / Terjadwal | yes |
| `rejected` | Ditolak | no |
| `cancelled` | Batal | no |
| `completed` | Selesai | historical |

Transactions (`TransactionModel`) are created **once** on transition to `completed` — financial source of truth. Don't double-write on retries.

## Hard constraints (from [implementation.md](implementation.md))

- **Do not change the visual theme.** Black-gold + cream/ivory; keep using `public/assets/css/salon-theme.css`. No new frontend deps — Bootstrap 5 + Chart.js via CDN only.
- **Never use a paid WhatsApp API** (Cloud API, Twilio, Meta Graph). Only `wa.me` links + copy-to-clipboard templates.
- **Don't modify the baseline migration** `2026-04-30-000001_CreateSalonTables.php`. Add new dated migrations instead.
- **All UI labels in Bahasa Indonesia.** Existing DB columns mix Indonesian + English snake_case (`slot_start`, `nama_pelanggan`); follow what's already in the table.
- **PHP 8.1+, MySQL 8.0+ / MariaDB 10.6+.** Don't drop below.
- `composer.json` edits should be rare — only when truly needed (e.g. `guzzlehttp/guzzle` for Telegram). Don't churn it.

## Docs worth reading
- [implementation.md](implementation.md) — current roadmap (5 revisions: dynamic slots, mandatory Telegram + inline buttons, WhatsApp auto-redirect, dual verification, booking audit trail). Spec calls for branch `feature/dynamic-slots-and-telegram-verification` and **plan-before-code per revision**.
- [docs/ERD.md](docs/ERD.md) — schema overview.
- [docs/BLACKBOX_TESTING.md](docs/BLACKBOX_TESTING.md) — manual test scenarios.
- [cara install.md](cara%20install.md) — install steps (Indonesian).
