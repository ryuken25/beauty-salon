# ERD — SW Beauty Salon

Visualisasi ringkas schema. Lihat migration `app/Database/Migrations/2026-05-12-100000_ResetAndCreateSalonSchema.php` untuk DDL definitif.

## Hubungan utama

- **`users`** — akun login. Role `admin`, `pemilik`, atau `pelanggan`. `nomor_hp` opsional (dipakai pelanggan).
- **`stylists`** + **`stylist_schedules`** — daftar stylist + jam kerja per hari (`hari` ENUM senin..minggu).
- **`layanan`** — katalog layanan (CRUD pemilik). `durasi_menit` kelipatan 30.
- **`bookings`** — booking utama. `user_id` nullable (booking anonim tetap diizinkan). Otentikasi customer anonim via kombinasi `(kode_booking, nomor_hp_pelanggan)`.
- **`booking_slots`** — 1 row per 30-menit yang ditahan booking. Tabel ini sumber kebenaran untuk anti double-booking.
- **`transaksi`** — 1:1 ke `bookings`. Dibuat otomatis ketika booking `completed`.
- **`booking_logs`** — audit trail lifecycle (`created`, `verified`, `rejected`, `cancelled`, `completed`, `wa_sent`).
- **`settings`** (key-value) — konfigurasi runtime: jam buka/tutup, range hari booking, template WA.

## Skema kolom utama

```
users(id PK, email UQ, password_hash, nama, nomor_hp, role ENUM[admin,pemilik,pelanggan], is_active, timestamps)

stylists(id PK, nama, nomor_hp, peran, is_default, is_active, timestamps)

stylist_schedules(id PK, stylist_id FK, hari ENUM, jam_mulai, jam_selesai, is_libur, timestamps)
    UNIQUE (stylist_id, hari)

layanan(id PK, nama, kategori, deskripsi, durasi_menit, harga, ikon, is_active, timestamps)

bookings(
  id PK, kode_booking UQ, user_id FK NULL -> users,
  nama_pelanggan, nomor_hp_pelanggan,
  layanan_id FK -> layanan, stylist_id FK -> stylists,
  tanggal, slot_mulai, slot_selesai, jumlah_slot, harga_layanan,
  status ENUM[pending_verification,accepted,rejected,cancelled,completed],
  sumber ENUM[online,walkin], catatan, wa_sent,
  verified_via, verified_at, completed_at,
  cancelled_at, cancelled_by, rejection_reason, timestamps
)
    INDEX (tanggal, slot_mulai), INDEX (status), INDEX (nomor_hp_pelanggan)

booking_slots(id PK, booking_id FK, stylist_id FK, tanggal, slot_waktu, status ENUM[held,released], created_at)
    INDEX (stylist_id, tanggal, slot_waktu)

transaksi(id PK, booking_id FK UQ, nominal, metode_bayar, tanggal_transaksi, catatan, created_at)

settings(id PK, key_name UQ, value, updated_at)

booking_logs(id PK, booking_id FK, event_type, actor, actor_role, payload JSON, notes, created_at)
    INDEX (booking_id, created_at)
```

## Status lifecycle

```
pending_verification
   ├── verify (dashboard)        ──► accepted ──► complete ──► completed (+ create transaksi)
   ├── reject                    ──► rejected   (slots dilepas)
   └── cancel (admin/customer)   ──► cancelled  (slots dilepas)

accepted ──► cancel ──► cancelled (slots dilepas)
```

## Aturan pembatalan customer

- Hanya status `pending_verification` atau `accepted`.
- Minimal 2 jam sebelum `slot_mulai`.
- Validasi via kombinasi `kode_booking` + `nomor_hp_pelanggan`.
