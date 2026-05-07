# ERD Ringkas SW Beauty Salon

## Tabel dan Relasi

### `users`

Menyimpan akun login dengan role `customer`, `admin`, atau `owner`.

- `users.id` direlasikan opsional ke `customers.user_id`.
- `users.id` direlasikan opsional ke `stylists.user_id`.

### `customers`

Menyimpan data pelanggan online maupun walk-in.

- Satu customer dapat memiliki banyak `bookings`.

### `services`

Menyimpan data layanan salon, durasi, harga, kategori, dan status aktif.

- Satu service dapat digunakan banyak `bookings`.
- Harga booking disalin ke `bookings.service_price` agar transaksi memakai harga saat booking.

### `stylists`

Menyimpan data stylist.

- Satu stylist memiliki banyak `stylist_working_hours`.
- Satu stylist dapat memiliki banyak `bookings`.
- Satu stylist dapat memiliki banyak `booking_slots`.

### `stylist_working_hours`

Jam kerja mingguan stylist berdasarkan `day_of_week` 0-6.

- Unique per `stylist_id + day_of_week`.

### `stylist_day_offs`

Hari libur opsional per stylist.

### `bookings`

Tabel utama booking.

- `bookings.customer_id` ke `customers.id`.
- `bookings.service_id` ke `services.id`.
- `bookings.stylist_id` ke `stylists.id`.
- Status internal: `pending_verification`, `accepted`, `rejected`, `cancelled`, `completed`.
- Tidak dihapus saat batal/tolak, hanya status berubah.

### `booking_slots`

Menyimpan slot 30 menit yang ditempati booking.

- `booking_slots.booking_id` ke `bookings.id`.
- `booking_slots.stylist_id` ke `stylists.id`.
- Unique constraint wajib: `stylist_id + slot_date + slot_start`.
- Constraint ini mencegah double booking pada slot stylist yang sama.

### `transactions`

Transaksi otomatis setelah booking `accepted` menjadi `completed`.

- `transactions.booking_id` unique ke `bookings.id`.
- Mencegah transaksi ganda.
- `amount` berasal dari `bookings.service_price`.

### `notification_logs`

Log notifikasi Telegram dan pencatatan WhatsApp manual.

### `telegram_action_tokens`

Token pendek untuk inline keyboard Telegram.

- `booking_id` ke `bookings.id`.
- `action`: `accept` atau `reject`.
- `token` unique.
- `expires_at` dan `used_at` untuk validasi.

### `app_settings`

Konfigurasi dasar seperti nomor WhatsApp salon, Telegram allowed chat IDs, dan offset polling.

## Relasi Utama

```text
users 1--0..1 customers
users 1--0..1 stylists
customers 1--* bookings
services 1--* bookings
stylists 1--* bookings
stylists 1--* stylist_working_hours
stylists 1--* stylist_day_offs
bookings 1--* booking_slots
bookings 1--0..1 transactions
bookings 1--* telegram_action_tokens
bookings 0..1--* notification_logs
```
