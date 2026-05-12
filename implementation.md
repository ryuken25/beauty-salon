# Megaprompt — Revisi Major SW Beauty Salon (CI4)

> Paste prompt ini ke Claude Code di root project. Pastikan lo udah `git clone https://github.com/ryuken25/beauty-salon` dan ada di branch baru.

---

## Konteks Project

Lo lagi kerja di **SW Beauty Salon**, aplikasi booking salon berbasis CodeIgniter 4. Stack:

- **Backend:** PHP 8 + CodeIgniter 4
- **Database:** MySQL (MariaDB)
- **Frontend:** Bootstrap 5 (CDN), Chart.js, CSS custom di `public/assets/css/salon-theme.css`
- **Tema visual:** black-gold soft, background cream/ivory — **JANGAN diubah**
- **Integrasi:** Telegram Bot API (long polling + webhook), WhatsApp manual via `wa.me` (no paid API)

Repo: `https://github.com/ryuken25/beauty-salon`

Aplikasi saat ini sudah punya: registrasi/login, role-based access (pelanggan/admin/pemilik), booking online dengan fixed slot 30 menit, manajemen layanan & stylist, dashboard pendapatan, transaksi otomatis saat booking selesai, dan Telegram opsional.

---

## Goals (5 Revisi)

Implement kelima revisi berikut secara komplit, tanpa break existing feature. Bikin di **branch terpisah**: `feature/dynamic-slots-and-telegram-verification`.

### Revisi 1 — Dynamic Booking Slot Duration (BREAKING CHANGE pada logic slot)

**Sekarang:** Setiap booking menahan satu slot fixed 30 menit, terlepas dari durasi layanan.

**Target:** Durasi slot yang ditahan = `services.durasi_menit` dari paket yang dipilih.

Contoh:
- Hair treatment 90 menit, booking jam 10:00 → slot 10:00–11:30 tertahan.
- Slot 10:30 dan 11:00 ikut blocked, tidak bisa di-book customer lain.
- Customer tetap pilih *start time* dengan snap kelipatan 30 menit (atau kelipatan slot terkecil yang udah dipake sistem).

**Implementasi:**
1. Tambah kolom `slot_end` (datetime) di tabel `bookings` jika belum ada — sistem isi otomatis dari `slot_start + services.durasi_menit`.
2. Refactor `BookingModel::isSlotAvailable()` (atau nama serupa) jadi method yang menerima `$serviceId` + `$slotStart` + `$stylistId`, lalu iterate per 30 menit dari `slot_start` sampai `slot_end`, cek semua slot kosong.
3. Update controller `Pelanggan/Booking::store()` (atau yang menangani submit): hitung `slot_end` server-side, jangan dari client.
4. Update UI booking di `app/Views/pelanggan/booking/baru.php`:
   - Tampilkan badge durasi pada kartu paket.
   - Saat customer pilih start time, render visual "occupied range" — slot-slot yang akan tertahan di-highlight beda warna.
   - Tampilkan info "Slot ditahan: HH:MM – HH:MM (X menit)".
5. Update query dashboard jadwal admin (`Admin/Booking::jadwal()`) supaya render slot range, bukan single point.

**Test case:**
- Book Hair treatment (90 menit) jam 10:00 → cek slot 10:00, 10:30, 11:00 semua tertahan di DB.
- Customer lain coba book Facial (60 menit) jam 10:30 → harus ditolak.
- Customer lain coba book Facial jam 11:30 → harus diterima.

---

### Revisi 2 — Telegram Verification Jadi Mandatory + Inline Buttons

**Sekarang:** Telegram opsional. Kalau `.env` kosong, sistem tetap jalan tanpa notif.

**Target:** Setiap booking baru otomatis trigger notif Telegram ke chat admin dengan **inline keyboard** dua tombol (Bahasa Indonesia):
- ✅ **Verifikasi**
- ❌ **Tolak**

**Implementasi:**

1. Promosi `TELEGRAM_BOT_TOKEN` + `TELEGRAM_ALLOWED_CHAT_IDS` jadi required. Validasi di startup (boot service atau `Config\App::__construct()`): kalau kosong di environment `production`, throw exception dengan pesan jelas. Di environment `development`, log warning tapi tetap jalan (biar dev lokal gak ribet).

2. Bikin service `app/Libraries/TelegramNotifier.php` (atau update yang udah ada) dengan method:
   ```
   sendBookingVerificationRequest(Booking $booking): void
   ```
   Payload-nya kirim message dengan info booking lengkap + `reply_markup` inline keyboard:
   ```json
   {
     "inline_keyboard": [[
       {"text": "✅ Verifikasi", "callback_data": "booking_verify:<booking_id>"},
       {"text": "❌ Tolak", "callback_data": "booking_deny:<booking_id>"}
     ]]
   }
   ```

3. Hook ke booking creation: setelah `BookingModel::insert()` atau di event `after_insert`, panggil `TelegramNotifier::sendBookingVerificationRequest()`.

4. Handle callback di `TelegramController::webhook()` dan `app/Commands/TelegramPoll.php`:
   - Parse `callback_data`, split by `:`.
   - Validasi chat ID ada di `TELEGRAM_ALLOWED_CHAT_IDS`.
   - Call `BookingService::verify($bookingId, 'telegram:'.$chatId)` atau `BookingService::reject(...)`.
   - Reply dengan `answerCallbackQuery` + edit message asli jadi "✅ Sudah diverifikasi oleh @admin pada HH:MM".

5. Update perintah `/pending` supaya juga render inline buttons buat tiap booking pending.

**Test case:**
- Submit booking baru → admin terima Telegram message dalam ≤5 detik dengan 2 button.
- Klik "Verifikasi" → booking status berubah ke `accepted`, message di-edit jadi info verifikasi.
- Klik "Tolak" → status jadi `rejected`, slot dilepas.

---

### Revisi 3 — WhatsApp Auto-Redirect untuk Pelanggan

**Sekarang:** WhatsApp template hanya disediakan di sisi admin (tombol copy + buka wa.me).

**Target:** Setelah pelanggan submit booking, mereka langsung mendapat halaman sukses dengan tombol prominent **"Chat owner via WhatsApp"** yang membuka `wa.me/<nomor_owner>` dengan pesan auto-filled.

**Implementasi:**

1. Tambah setting `whatsapp_owner_number` di tabel `settings` (atau di pengaturan dashboard) — format internasional, contoh `6281234567890`.

2. Tambah method `SettingModel::getOwnerWhatsApp(): ?string`.

3. Update flow di `Pelanggan/Booking::store()`:
   - Setelah booking sukses dibuat dan Telegram notif terkirim, redirect ke view `pelanggan/booking/sukses` dengan data booking + URL WA.

4. Bikin/refactor view `app/Views/pelanggan/booking/sukses.php`:
   - Tampilan: success icon, ID booking, summary detail booking, status "Menunggu verifikasi admin".
   - Tombol besar hijau "Chat owner via WhatsApp" linking ke:
     ```
     https://wa.me/{nomor}?text={template_encoded}
     ```
   - Template pesan (urlencode-d):
     ```
     Halo SW Beauty Salon, saya {nama} sudah melakukan booking:
     
     • ID: {booking_code}
     • Layanan: {nama_layanan}
     • Tanggal: {tanggal}
     • Jam: {jam_mulai} – {jam_selesai}
     • Stylist: {nama_stylist}
     
     Mohon konfirmasi. Terima kasih.
     ```
   - Info kecil di bawah tombol: "Admin sudah dikirimi notifikasi Telegram otomatis."

5. Tetap pertahankan WhatsApp template manual di sisi admin (tidak dihapus) — itu fitur beda use case.

**Test case:**
- Submit booking → langsung lihat halaman sukses.
- Klik tombol WA → buka WhatsApp (atau web.whatsapp.com) dengan nomor owner + pesan terisi.

---

### Revisi 4 — Dual Admin Verification (Telegram + Dashboard, Sinkron)

**Sekarang:** Admin verifikasi hanya via dashboard.

**Target:** Admin bisa verifikasi via:
- **Telegram inline button** (cepat, untuk yang lagi di luar)
- **Dashboard admin** (existing flow)

Hasilnya sinkron — kalau diverifikasi via dashboard, admin di Telegram dapat update message "✅ Sudah diverifikasi via Dashboard pada HH:MM".

**Implementasi:**

1. Centralize logic verifikasi di `app/Services/BookingService.php` (bikin baru kalau belum ada):
   ```
   verify(int $bookingId, string $verifiedBy): bool
   reject(int $bookingId, string $rejectedBy, ?string $reason = null): bool
   cancel(int $bookingId, string $cancelledBy): bool
   complete(int $bookingId, string $completedBy): bool
   ```
   `$verifiedBy` format: `telegram:<chat_id>` atau `dashboard:<user_id>`.

2. Tambah kolom `verified_via` (varchar, nullable) di tabel `bookings` via migration.

3. Refactor controller `Admin/Booking::verify()` dan webhook handler Telegram supaya keduanya panggil `BookingService::verify()`.

4. Setelah verify dari dashboard → trigger `TelegramNotifier::sendVerificationConfirmation()` ke chat admin dengan format:
   ```
   ✅ Booking #BK-XXX sudah diverifikasi via Dashboard oleh {nama_admin} pada {timestamp}.
   ```

5. Sebaliknya, kalau verify dari Telegram → dashboard auto-refresh tampilkan status terbaru (existing behavior atau pakai polling 30 detik).

**Test case:**
- Verify via Telegram → dashboard list booking pending refresh, item tersebut hilang dari pending list.
- Verify via dashboard → admin terima Telegram message konfirmasi.
- `bookings.verified_via` keisi sesuai sumber verifikasi.

---

### Revisi 5 — Transaction Log / Booking Audit Trail

**Sekarang:** `transactions` table cuma simpan transaksi finansial pas booking `completed`.

**Target:** Tabel baru `booking_logs` (audit trail) yang simpan semua event lifecycle booking, kayak ledger Excel tapi di database. Bisa dipake buat trace siapa, kapan, ngapain.

**Implementasi:**

1. Migration baru `CreateBookingLogsTable`:
   ```
   booking_logs
   ─────────────
   id              BIGINT PK
   booking_id      INT FK -> bookings.id
   event_type      VARCHAR(30)   -- created, verified, rejected, cancelled, completed, wa_sent, telegram_sent, telegram_received
   actor           VARCHAR(100)  -- "telegram:123", "dashboard:user_5", "system"
   actor_role      VARCHAR(20)   -- pelanggan, admin, pemilik, system
   payload         JSON          -- snapshot data relevan
   notes           TEXT NULL
   created_at      DATETIME
   ```

2. Bikin `app/Models/BookingLogModel.php`.

3. Tambah helper di `BookingService`:
   ```php
   private function logEvent(int $bookingId, string $eventType, string $actor, string $actorRole, array $payload = [], ?string $notes = null): void
   ```
   Panggil di setiap state transition.

4. Update view `app/Views/admin/booking/detail.php` — tambah section "Riwayat Aktivitas":
   - Timeline view (vertikal, mirip Git commit history).
   - Tiap entry: icon event, label, actor, timestamp relative ("2 menit lalu"), payload expand-on-click.

5. Existing `transactions` table tetap dipertahankan — itu tetap jadi source of truth untuk laporan finansial. `booking_logs` adalah audit trail yang lebih luas, bukan pengganti.

**Test case:**
- Booking dibuat → 1 entry log (`created`).
- Telegram notif terkirim → 1 entry (`telegram_sent`).
- Verify via Telegram → 1 entry (`verified`, actor `telegram:<id>`).
- Booking selesai → 1 entry (`completed`) + entry transaksi finansial tetap dibuat di `transactions`.
- Buka detail booking di dashboard → timeline lengkap ter-render.

---

## Database Migration Order

Buat migration baru, jangan modify yang lama:

1. `2026XXXX_000001_AddSlotEndToBookings.php` — kolom `slot_end DATETIME`.
2. `2026XXXX_000002_AddVerifiedViaToBookings.php` — kolom `verified_via VARCHAR(50) NULL`.
3. `2026XXXX_000003_CreateBookingLogsTable.php` — tabel audit.
4. `2026XXXX_000004_AddWhatsAppOwnerToSettings.php` — row baru di `settings` (atau kolom, tergantung schema existing).

Jalankan: `php spark migrate`. Pastikan rollback (`php spark migrate:rollback`) juga clean.

---

## Constraint & Aturan Main

- **JANGAN ubah tampilan tema** black-gold + cream/ivory. Class CSS di `salon-theme.css` tetap pakai.
- **JANGAN tambah dependency frontend baru.** Tetap Bootstrap 5 + Chart.js via CDN.
- **JANGAN pake WhatsApp paid API** (Cloud API, Twilio, Meta Graph). Tetap manual via `wa.me`.
- **JANGAN modify** `composer.json` kecuali bener-bener perlu (misalnya butuh `guzzlehttp/guzzle` buat Telegram async — itu boleh).
- Pertahankan struktur CI4 default: Controller, Model, View, Migration, Library/Service.
- Semua nama field di DB pake `snake_case` Indonesia/English mix yang udah ada (e.g., `slot_start`, `nama_pelanggan`).
- Semua label UI dalam **Bahasa Indonesia**.
- Compatibility: PHP 8.1+, MySQL 8.0+ / MariaDB 10.6+.

---

## Implementation Order (Step-by-Step)

Jangan kerjain paralel, ikutin urutan ini biar dependency aman:

1. **Setup branch:** `git checkout -b feature/dynamic-slots-and-telegram-verification`
2. **Migration dulu:** Bikin keempat migration di atas. Run, test rollback, run lagi.
3. **Refactor service layer:** Bikin `BookingService` + `BookingLogModel` + `logEvent()` helper.
4. **Revisi 1 (slot dinamis):** Update `BookingModel`, controller, view booking. Test isolated.
5. **Revisi 5 (logging):** Wire up `logEvent()` di tiap state change `BookingService`. Test detail booking page.
6. **Revisi 2 (Telegram mandatory):** Refactor `TelegramNotifier`, tambah inline keyboard, callback handler.
7. **Revisi 4 (dual verification):** Sambungin dashboard verify ke `BookingService`, kirim konfirmasi balik ke Telegram.
8. **Revisi 3 (WhatsApp redirect):** Bikin view sukses + setting nomor owner.
9. **Integration test:** Run skenario full end-to-end (lihat acceptance criteria di bawah).
10. **Update `cara install.md` dan `docs/BLACKBOX_TESTING.md`** dengan flow baru.

---

## Acceptance Criteria (End-to-End)

✅ Customer book Hair treatment (90 menit) jam 10:00 → DB `bookings.slot_start = 10:00`, `slot_end = 11:30`.

✅ Customer lain coba book Facial (60 menit) jam 10:30 di hari yang sama dengan stylist yang sama → ditolak dengan error "Slot tidak tersedia".

✅ Setelah submit booking, admin Telegram terima message dalam ≤5 detik dengan 2 inline button.

✅ Customer di halaman sukses lihat tombol WhatsApp prominent, klik → buka `wa.me` dengan template pesan terisi.

✅ Admin klik "Verifikasi" di Telegram → booking jadi `accepted`, message di-edit jadi "✅ Sudah diverifikasi", `bookings.verified_via = 'telegram'`.

✅ Admin verify booking lain via dashboard → admin Telegram terima konfirmasi balik "Sudah diverifikasi via Dashboard".

✅ Buka detail booking di dashboard → tab "Riwayat Aktivitas" tampilkan timeline lengkap dari `created` sampai event terakhir.

✅ `php spark migrate:rollback` clean, lalu `php spark migrate` clean tanpa error.

✅ `php spark routes` tidak nampilkan route duplikat atau orphan.

✅ Existing demo account (`owner@swbeautysalon.local`, `admin@swbeautysalon.local`, `pelanggan@example.com`) semua masih bisa login dan menjalankan flow masing-masing.

---

## Mulai

Lakukan dulu **eksplorasi codebase**:

1. `find app -type f -name "*.php" | head -50` — lihat struktur.
2. `cat app/Models/BookingModel.php` — pahami model existing.
3. `cat app/Controllers/Pelanggan/Booking.php` — pahami flow customer.
4. `cat app/Controllers/Admin/Booking.php` — pahami flow admin.
5. `ls app/Database/Migrations/` — lihat migration existing.
6. `cat .env.example` — lihat env var yang ada.

Setelah paham struktur, mulai dari Step 1 implementation order di atas. Sebelum coding tiap revisi, **show me your plan dulu** — apa file yang mau diubah, schema migration-nya, struktur method baru. Aku approve dulu baru lo eksekusi.

Kalau ada ambiguity dalam spec di atas (misalnya nama kolom existing yang tidak ku-mention secara presisi), **read the codebase first**, jangan asumsi. Kalau bener-bener stuck, tanya dulu.

Ready? Lanjut.