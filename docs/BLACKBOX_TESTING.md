# Checklist Black Box Testing

Checklist ini disusun sesuai proposal “Sistem Informasi Penjadwalan dan Pelayanan pada SW Beauty Salon dengan Pendekatan Fixed Time Slot”. Pengujian dilakukan dari sisi input-output tanpa menilai struktur internal kode.

| No | Fitur | Skenario | Input | Output yang Diharapkan | Hasil Aktual | Status | Catatan/Perbaikan |
|---|---|---|---|---|---|---|---|
| 1 | Registrasi pelanggan | Pelanggan membuat akun baru | Nama, email unik, nomor WhatsApp valid, password minimal 8 karakter | Akun role pelanggan dan data pelanggan tersimpan | Validasi form tersedia, password di-hash, pelanggan tersimpan | Berhasil | Endpoint register memakai validasi server-side |
| 2 | Login pelanggan | Pelanggan login | `pelanggan@example.com` dan password benar | Masuk dashboard pelanggan | Login diverifikasi dengan password hash | Berhasil | Role customer diarahkan ke `/pelanggan` |
| 3 | Login admin | Admin login | `admin@swbeautysalon.local` dan password benar | Masuk daftar booking operasional admin | Admin diarahkan ke area admin dan dashboard pemilik dialihkan ke daftar booking | Berhasil | Dashboard pendapatan khusus pemilik |
| 4 | Login pemilik | Pemilik login | `owner@swbeautysalon.local` dan password benar | Masuk dashboard pemilik | Pemilik dapat mengakses dashboard, master data, transaksi | Berhasil | Role owner memiliki fitur admin + pemilik |
| 5 | Pelanggan melihat layanan | Buka halaman layanan | Browser pelanggan/umum | Daftar layanan aktif, harga, durasi tampil | Query layanan aktif tersedia | Berhasil | Layanan nonaktif tidak dipakai booking baru |
| 6 | Pelanggan memilih stylist | Pilih stylist aktif pada form booking | Stylist aktif | Stylist bisa dipilih | Form hanya mengambil stylist aktif | Berhasil | Stylist nonaktif tidak tampil |
| 7 | Pelanggan memilih tanggal | Pilih tanggal valid | Tanggal hari ini/besok | Sistem memproses tanggal valid | Tanggal masa lalu ditolak server-side | Berhasil | Format tanggal divalidasi |
| 8 | Sistem menampilkan slot 30 menit tersedia | Pilih layanan, stylist, tanggal | Layanan aktif, stylist aktif, tanggal kerja | Slot mulai valid dalam grid 30 menit tampil | `SlotService` membentuk slot 30 menit dari jam kerja stylist | Berhasil | Slot tidak cukup durasi tidak ditampilkan |
| 9 | Booking berhasil saat slot tersedia | Submit booking slot kosong | Layanan 60 menit, stylist A, 08:00 | Booking status menunggu verifikasi, slot 08:00 dan 08:30 terkunci | Booking dan `booking_slots` dibuat dalam transaction | Berhasil | Harga layanan disimpan sebagai snapshot |
| 10 | Booking ditolak saat slot bentrok | Booking kedua stylist/tanggal/slot sama | Stylist A, tanggal sama, 08:30 pada booking 60 menit aktif | Booking ditolak | Unique constraint dan validasi sequence menolak | Berhasil | Pesan memilih slot lain tampil |
| 11 | Booking ditolak saat slot tidak berurutan/cukup durasi | Pilih slot yang tidak cukup sampai jam selesai | Layanan 90 menit mendekati akhir jam kerja | Slot tidak tampil/ditolak | Server menolak jika end_time melewati jam kerja | Berhasil | Tidak bergantung JS saja |
| 12 | Booking ditolak di luar jam kerja stylist | Submit manual waktu luar jam kerja | Jam sebelum mulai/setelah selesai | Sistem menolak | `validateSlot()` menolak luar jam kerja | Berhasil | Termasuk hari libur stylist |
| 13 | Booking baru masuk status menunggu verifikasi | Pelanggan membuat booking valid | Slot kosong | Status `pending_verification` | Status default dibuat menunggu verifikasi | Berhasil | Label UI “Menunggu Verifikasi” |
| 14 | Telegram Bot mengirim notifikasi booking baru ke pemilik | Booking baru, env Telegram valid | Token + chat ID pemilik | Notifikasi detail booking terkirim | Service mengirim detail dan tombol terima/tolak | Berhasil | Jika kosong, log pending dan aplikasi tidak crash |
| 15 | Pemilik menerima booking | Klik Terima web/Telegram | Booking pending | Status diterima, slot tetap terkunci | Transisi pending → accepted dibatasi | Berhasil | Double action dicegah dengan status check |
| 16 | Pemilik menolak booking | Klik Tolak web/Telegram | Booking pending + alasan | Status ditolak, slot dilepas | Reject menghapus slot terkait | Berhasil | Token Telegram divalidasi |
| 17 | Booking ditolak mengembalikan slot | Tolak booking aktif | Booking pending | Slot bisa dipilih ulang | `booking_slots` dihapus saat reject | Berhasil | Berlaku web dan Telegram |
| 18 | Pelanggan melihat riwayat booking | Buka riwayat | Login pelanggan | Hanya booking miliknya tampil | Query dibatasi `customer_id` milik session | Berhasil | Pelanggan lain tidak terlihat |
| 19 | Pelanggan membatalkan booking jika status masih boleh | Klik batal pada pending/accepted sebelum jadwal | Booking milik sendiri | Status batal, slot dilepas | Owner check customer + status check tersedia | Berhasil | Booking final ditolak |
| 20 | Pembatalan mengembalikan slot | Batalkan booking aktif | Booking pending/accepted | Slot bisa dipilih ulang | `booking_slots` dihapus saat cancel | Berhasil | Booking selesai tidak bisa batal |
| 21 | Admin input booking walk-in/offline | Admin isi pelanggan/layanan/stylist/tanggal/slot | Data walk-in valid | Booking walk-in tersimpan menunggu verifikasi | Controller memakai `BookingService` yang sama | Berhasil | Slot tetap tervalidasi server-side |
| 22 | Admin membatalkan booking | Admin klik batal | Booking pending/accepted | Status batal, slot dilepas | Service membatasi status aktif | Berhasil | Status final ditolak |
| 23 | Admin mengubah booking menjadi selesai | Admin klik selesai | Booking diterima | Status selesai | Service hanya menerima status accepted | Berhasil | Slot histori tetap, tidak dilepas |
| 24 | Booking selesai membuat transaksi otomatis | Tandai selesai | Booking diterima | Satu transaksi dibuat | Unique `booking_id` mencegah duplikasi | Berhasil | Nominal dari snapshot harga booking |
| 25 | Dashboard menampilkan pendapatan harian | Pemilik buka dashboard | Transaksi hari ini | Total harian tampil | Query transaksi selesai tersaji | Berhasil | Dashboard hanya pemilik |
| 26 | Dashboard menampilkan pendapatan mingguan | Pemilik buka dashboard | Transaksi minggu ini | Total mingguan tampil | Query tanggal minggu berjalan tersedia | Berhasil | Deskriptif, bukan prediktif |
| 27 | Dashboard menampilkan pendapatan bulanan | Pemilik buka dashboard | Transaksi bulan ini | Total bulanan tampil | Query bulan berjalan tersedia | Berhasil | Deskriptif |
| 28 | Dashboard menampilkan layanan paling sering digunakan | Pemilik buka dashboard | Booking selesai bulan ini | Top service tampil | Query booking completed per layanan | Berhasil | Tidak memakai rekomendasi/AI |
| 29 | WhatsApp template tersedia dan tetap manual | Buka detail booking | Booking status accepted/rejected/cancelled/completed | Template dan link `wa.me` tampil | Service hanya membuat template/link | Berhasil | Tidak ada WA API otomatis |
| 30 | Role pelanggan tidak bisa akses admin/pemilik | Pelanggan buka URL admin | Session customer | Akses ditolak/dialihkan | Route memakai role filter | Berhasil | Endpoint terlindungi, bukan hanya menu |
| 31 | Role admin tidak bisa akses fitur khusus pemilik jika dibatasi | Admin buka master data/transaksi/dashboard pemilik | Session admin | Akses ditolak/dialihkan | Route master data, pengaturan, transaksi dibatasi owner; `/admin` dialihkan ke booking | Berhasil | Admin tetap bisa operasional booking |
| 32 | Role pemilik bisa akses dashboard dan master data | Pemilik buka dashboard/layanan/stylist | Session owner | Halaman tampil | Route owner tersedia | Berhasil | Pemilik juga bisa fitur admin |
| 33 | Tidak ada fitur out-of-scope muncul di UI | Audit menu dan teks | UI utama | Tidak ada payment gateway, WA API otomatis, stok, membership, forecasting, AI | UI tetap pada booking, layanan, stylist, transaksi, dashboard deskriptif, Telegram, WA manual | Berhasil | Fitur out-of-scope tidak dikembangkan |

## Catatan Pengujian

- Pengujian Telegram membutuhkan `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_ALLOWED_CHAT_IDS` yang valid di konfigurasi lokal.
- Jika token/chat ID kosong, booking tetap tersimpan dan notifikasi Telegram dilewati dengan log aman.
- WhatsApp hanya template manual melalui tombol salin/buka link; admin/pemilik tetap menekan kirim sendiri.
