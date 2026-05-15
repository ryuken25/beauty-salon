# Black Box Testing — SW Beauty Salon

Pengujian sesuai Bab III Section 3.6 proposal SEMPRO: metode Black Box, fokus input/output tanpa menilai struktur internal kode. Catat hasil "Berhasil" atau "Gagal" untuk setiap skenario. Target: 100% skenario lulus sebelum penyerahan TA.

## Persiapan

- Database fresh (migrate + seed): `php spark migrate && php spark db:seed SalonSeeder`.
- Server lokal aktif: `php spark serve` di port 8080.
- Browser modern (Chrome / Firefox), buka `http://localhost:8080`.
- Akun: `owner@swbeautysalon.local` / `Password123!`, `admin@swbeautysalon.local` / `Password123!`.

## A. Customer flow (publik)

| ID | Skenario | Input | Output diharapkan | Hasil |
|---|---|---|---|---|
| BB-01 | Buka beranda | GET `/` | Tampil hero + 3 layanan unggulan + section "Mengapa memilih kami" | |
| BB-02 | Lihat semua layanan | GET `/layanan` | Tampil grid layanan + chip kategori berfungsi (klik filter) | |
| BB-03 | Filter layanan via chip | Klik chip "Hair" di `/layanan` | Hanya kartu kategori Hair yang tampil | |
| BB-04 | Booking sukses | Nama "Putri", HP "081234567890", layanan Hair Treatment, tanggal besok, jam 10:00 | Redirect ke `/booking/sukses/{kode}`, kode booking ditampilkan, tombol WA owner aktif | |
| BB-05 | Slot tertahan benar | Lihat DB tabel `booking_slots` setelah BB-04 | 3 row dengan `slot_waktu` 10:00, 10:30, 11:00 dan `status='held'` | |
| BB-06 | Slot bentrok ditolak | Coba booking layanan apapun dengan slot 10:30 di tanggal sama (setelah BB-04) | Pesan error "Slot waktu tidak tersedia" | |
| BB-07 | Booking di luar jam tutup | Pilih Hair Color (120m) jam 18:00 | Pesan error "Waktu di luar jam operasional salon" | |
| BB-08 | Tanggal masa lalu | Pilih tanggal kemarin | Tanggal tidak tampil di date strip / error "Tanggal di luar rentang booking" | |
| BB-09 | Tanggal > 7 hari | Pilih tanggal +8 hari ke depan | Tanggal tidak tampil di date strip | |
| BB-10 | Format HP invalid | HP "abc123" | Validasi form gagal, error ditampilkan | |
| BB-11 | Nama < 3 karakter | Nama "Pu" | Validasi gagal | |
| BB-12 | Slot picker — selected state | Pilih slot 10:00 untuk layanan 90 menit | Slot 10:00 berwarna gold (selected), 10:30 & 11:00 cream (held) | |
| BB-13 | Slot picker — booked state | Lihat slot yang sudah dibook orang lain | Tampil abu-abu strikethrough dengan diagonal stripe | |
| BB-14 | Slot picker — past state | Buka form, pilih hari ini, lihat slot pagi yang sudah lewat | Tampil cream pudar strikethrough, tidak bisa diklik | |
| BB-15 | Cek booking — HP terdaftar | POST `/cek-booking` dengan HP yang ada booking | Tampil list booking dengan badge status | |
| BB-16 | Cek booking — HP tidak terdaftar | POST `/cek-booking` dengan HP random | Tampil empty state "Tidak ada booking" | |
| BB-17 | Detail booking — HP cocok | Klik "Detail" dari halaman cek-booking | Tampil detail + timeline + tombol cancel kalau eligible | |
| BB-18 | Detail booking — HP tidak cocok | GET `/booking/{kode}?no_hp=salahnomor` | Redirect ke `/cek-booking` dengan error | |
| BB-19 | Customer cancel booking pending | Klik "Batalkan" di detail booking pending | Status berubah ke `cancelled`, slot dilepas | |
| BB-20 | Customer cancel — kurang dari 2 jam | Booking 1 jam ke depan, coba cancel | Error "minimal 2 jam sebelum jam booking" | |
| BB-21 | Customer cancel — booking selesai | Coba cancel booking dengan status completed | Error "tidak dapat dibatalkan karena status sudah final" | |
| BB-22 | WhatsApp button | Klik tombol "Chat owner" di halaman sukses | Buka tab baru `https://wa.me/{nomor}?text=...` dengan pesan terisi | |

## B. Admin authentication

| ID | Skenario | Input | Output diharapkan | Hasil |
|---|---|---|---|---|
| BB-30 | Login berhasil (pemilik) | Email `owner@…`, pwd `Password123!` | Redirect `/admin/dashboard`, sidebar lengkap (Layanan, Stylist, Transaksi, Pengaturan) | |
| BB-31 | Login berhasil (admin) | Email `admin@…`, pwd `Password123!` | Redirect `/admin/dashboard`, sidebar terbatas | |
| BB-32 | Login gagal — password salah | Pwd `salah` | Tetap di halaman login, error "Email atau password salah" | |
| BB-33 | Login gagal — akun nonaktif | User di-set `is_active=0` lalu coba login | Error | |
| BB-34 | Brute force protection | Login 6 kali berturut-turut gagal | Diblokir 15 menit dengan pesan throttle | |
| BB-35 | Akses `/admin/dashboard` tanpa login | GET `/admin/dashboard` (anonim) | Redirect ke `/admin/login` | |
| BB-36 | Admin akses route pemilik-only | Login sebagai admin, GET `/admin/layanan` | Redirect `/admin/dashboard` dengan error | |
| BB-37 | Logout | POST `/admin/logout` | Session destroyed, redirect `/` | |
| BB-38 | Navbar publik bersih | Buka `/` (anonim) | TIDAK ada link "Admin" di navbar | |

## C. Booking management admin

| ID | Skenario | Input | Output diharapkan | Hasil |
|---|---|---|---|---|
| BB-40 | List booking | GET `/admin/booking` | Tampil tabel + filter chips status | |
| BB-41 | Filter status | Klik chip "Pending" | Hanya booking pending_verification tampil | |
| BB-42 | Filter tanggal & cari | Pilih tanggal, isi nama | Hasil terfilter sesuai input | |
| BB-43 | Verifikasi via dashboard | Buka detail booking pending, klik "Verifikasi" | Status jadi `accepted`, `verified_via='dashboard:<uid>'`, entry log baru | |
| BB-44 | Tolak via dashboard | Klik "Tolak" + alasan | Status `rejected`, slot dilepas (cek `booking_slots`) | |
| BB-45 | Selesaikan booking | Klik "Selesaikan + transaksi" di booking accepted | Status `completed`, row baru di `transaksi` dengan nominal=harga | |
| BB-46 | Selesaikan dua kali (idempoten) | Coba selesaikan booking yang sudah completed | Error "sudah berubah status" | |
| BB-47 | Walk-in booking | GET `/admin/booking/walkin`, isi form, submit | Booking sumber=`walkin`, status langsung `accepted` | |
| BB-48 | Jadwal harian | GET `/admin/booking/jadwal?tanggal=2026-05-15` | Tabel timeline per stylist, sel berwarna sesuai status | |
| BB-49 | WhatsApp template (admin) | Buka detail, klik "Salin pesan" | Pesan dicopy ke clipboard, alert konfirmasi | |
| BB-50 | Tandai WA sudah dikirim | Klik "Tandai sudah dikirim" | `wa_sent=1`, badge "WA terkirim" muncul | |

## D. CRUD pemilik

| ID | Skenario | Input | Output diharapkan | Hasil |
|---|---|---|---|---|
| BB-60 | Tambah layanan | Form layanan baru | Layanan masuk list, muncul di `/layanan` publik kalau aktif | |
| BB-61 | Edit layanan | Ubah harga | Harga baru tersimpan, terlihat di booking form | |
| BB-62 | Non-aktifkan layanan | Set `is_active=0` | Tidak muncul di booking form, masih ada di list admin | |
| BB-63 | Tambah stylist | Form stylist | Stylist baru muncul, auto-generated schedule senin-minggu 08:00-19:00 | |
| BB-64 | Edit jadwal stylist | Set Minggu = libur | Stylist tidak menerima booking di hari Minggu (cek API `/api/slots`) | |
| BB-65 | Set stylist default | Toggle is_default ke stylist lain | Stylist lama otomatis di-unset, hanya 1 default | |

## F. Dashboard

| ID | Skenario | Input | Output diharapkan | Hasil |
|---|---|---|---|---|
| BB-80 | Pendapatan hari ini (pemilik) | Sebelum ada transaksi hari ini | Rp 0 | |
| BB-81 | Pendapatan after complete | Selesaikan 1 booking 250k | Pendapatan hari ini = Rp 250.000 | |
| BB-82 | Chart 7 hari | Buka dashboard pemilik | Chart Chart.js terisi 7 bar (terakhir paling solid) | |
| BB-83 | Top layanan | Beberapa booking completed bulan ini | List layanan terpopuler + bar gold proporsional | |
| BB-84 | Admin dashboard limited | Login sebagai admin | Tidak tampil card Pendapatan & Stylist aktif, tidak ada chart pendapatan | |

## G. Operasional

| ID | Skenario | Input | Output diharapkan | Hasil |
|---|---|---|---|---|
| BB-90 | Migrate clean | `php spark migrate:rollback && php spark migrate` | Tidak ada error, semua tabel dibuat ulang | |
| BB-91 | Seeder clean | `php spark db:seed SalonSeeder` setelah migrate fresh | Demo data terisi, 2 user + 1 stylist + 8 layanan + settings | |
| BB-92 | Routes inspect | `php spark routes` | Tidak ada duplicate/orphan, semua handler ada | |
| BB-93 | Lint sweep | PHP lint script di README | Tidak ada syntax error | |
| BB-94 | Responsive 375px | Buka beranda + booking form di viewport 375px | Tidak ada horizontal scroll, slot grid 4 kolom | |

Total: 50+ skenario. Target lulus 100% sebelum submit TA.
