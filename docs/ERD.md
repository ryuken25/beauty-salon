# ERD — SW Beauty Salon

Visualisasi ringkas schema. Lihat migration terbaru
`app/Database/Migrations/2026-06-05-100000_AddEmailReminderFlag.php`
+ migrasi sebelumnya untuk DDL definitif.

## Hubungan utama

- **`users`** — akun login. Role `admin`, `pemilik`, atau `pelanggan`. Pelanggan: nomor WA (`nomor_hp`, UNIQUE) + email (UNIQUE) + password. Staff: email + password.
- **`layanan`** — katalog layanan (CRUD pemilik). `durasi_menit` kelipatan 30. Soft delete.
- **`bookings`** — booking utama. `user_id` FK→users nullable (walk-in admin tanpa akun). Akses publik via `/cek-booking` (kode booking only) dengan rate-limit; pelanggan login akses via `/pelanggan/booking/{kode}` dengan ownership cek `user_id`.
- **`booking_slots`** — 1 row per 30-menit yang ditahan booking. Tabel ini sumber kebenaran untuk anti double-booking. Nama kolom: `slot_waktu` (bukan `slot`).
- **`transaksi`** — 1:1 ke `bookings`. Dibuat otomatis ketika booking `completed`.
- **`booking_logs`** — audit trail lifecycle (`created`, `verified`, `rejected`, `cancelled`, `completed`, `wa_sent`, `email_created`, `email_verified`, `email_reminder`).
- **`settings`** (key-value) — konfigurasi runtime: `jam_buka`, `jam_tutup`, `range_hari_booking`, `info_pembayaran_dp`, `template_wa_*`, dst.

## Skema kolom utama

```
users(id PK, email UQ NULL, password_hash, nama, nomor_hp UQ NULL,
      role ENUM[admin,pemilik,pelanggan], is_active, timestamps)

layanan(id PK, nama, kategori, deskripsi, durasi_menit, harga, ikon,
        is_active, deleted_at, timestamps)

bookings(
  id PK, kode_booking UQ, user_id FK NULL -> users,
  nama_pelanggan, nomor_hp_pelanggan, email_pelanggan NULL,
  layanan_id FK -> layanan,
  tanggal, slot_mulai, slot_selesai, jumlah_slot, harga_layanan,
  dp_amount, dp_proof_path NULL,
  payment_status ENUM[unpaid,dp_uploaded,dp_verified],
  email_reminder_sent_at NULL,
  status ENUM[pending_verification,accepted,rejected,cancelled,completed],
  sumber ENUM[online,walkin], catatan, wa_sent,
  verified_via, verified_at, completed_at,
  cancelled_at, cancelled_by, rejection_reason, cancellation_reason,
  timestamps
)
    INDEX (tanggal, slot_mulai), INDEX (status), INDEX (nomor_hp_pelanggan)

booking_slots(id PK, booking_id FK, tanggal, slot_waktu,
              status ENUM[held,released], created_at)
    INDEX (tanggal, slot_waktu)

transaksi(id PK, booking_id FK UQ, nominal, base_price, additional_price,
          metode_bayar, tanggal_transaksi, catatan, created_at)

settings(id PK, key_name UQ, value, updated_at)

booking_logs(id PK, booking_id FK, event_type, actor, actor_role,
             payload JSON, notes, created_at)
    INDEX (booking_id, created_at)
```

## Status lifecycle

```
pending_verification
   ├── verify (dashboard)        ──► accepted ──► complete ──► completed (+ create transaksi)
   ├── reject                    ──► rejected   (slots dilepas)
   ├── cancel (admin/customer)   ──► cancelled  (slots dilepas)
   └── auto-cancel (system)      ──► cancelled  (slots dilepas; jadwal sudah lewat)

accepted ──► cancel ──► cancelled (slots dilepas)
accepted + 30 menit sebelum slot_mulai
         ──► email reminder dikirim, email_reminder_sent_at di-set
```

## Aturan pembatalan customer

- Hanya status `pending_verification` atau `accepted`.
- Minimal 2 jam sebelum `slot_mulai`.
- Pelanggan login → `/pelanggan/booking/{kode}/batal` (ownership via `user_id` session).
- Publik (`/cek-booking`) → read-only. Tombol batal hanya muncul kalau login (redirect ke jalur pelanggan).
