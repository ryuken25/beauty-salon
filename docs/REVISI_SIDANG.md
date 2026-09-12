# Revisi Sidang Tugas Akhir — SW Beauty Salon

Pemetaan masukan penguji ke perubahan yang dikerjakan, beserta file yang
tersentuh. Dikerjakan 13 September 2026.

## Ringkasan

| No | Poin revisi penguji | Perubahan yang dilakukan | File terkait |
|---|---|---|---|
| — | Pertanyaan `id` → auto increment: apakah seluruh primary key benar-benar auto increment? | Audit seluruh migration dan pengecekan langsung ke database. Hasilnya seluruh tabel sudah memakai `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`, sehingga **tidak ada migration baru** yang dibuat. Hasil audit dan struktur tiap tabel didokumentasikan untuk lampiran laporan. | [docs/STRUKTUR_TABEL.md](STRUKTUR_TABEL.md) (baru) |
| 4 | "Pada aplikasi web sebaiknya dicantumkan nomor WA otomatis ke Admin untuk memudahkan pelanggan jika ingin bertanya." | Nomor WhatsApp admin dijadikan pengaturan yang bisa diubah dari panel, lalu ditampilkan di sisi pelanggan sebagai tombol mengambang "Tanya Admin", tombol per layanan, dan nomor yang bisa diklik di footer, halaman booking sukses, serta halaman cek booking. Pesan sudah terisi otomatis sesuai halaman yang dibuka. | lihat tabel di bawah |

## Rincian poin 4 — nomor WhatsApp admin

### File baru

| File | Alasan |
|---|---|
| `app/Config/Salon.php` | Menyimpan nilai bawaan nomor WhatsApp admin sekaligus titik override lewat `.env` (`salon.waAdmin`). |
| `app/Helpers/whatsapp_helper.php` | Satu tempat untuk normalisasi nomor, pembentukan tautan `wa.me`, format tampilan, dan penyusunan pesan. Tidak ada view yang menulis nomor secara langsung. |
| `app/Views/partials/wa_float.php` | Markup tombol WhatsApp mengambang, memakai SVG inline (tanpa menambah icon font atau CDN). |

### File yang diubah

| File | Alasan |
|---|---|
| `app/Config/Autoload.php` | Mendaftarkan helper `whatsapp` supaya fungsinya tersedia di seluruh view. |
| `app/Controllers/Admin/PengaturanController.php` | Menambah key `wa_admin` ke daftar pengaturan yang bisa disimpan + validasi format nomor. Sekaligus memastikan tab pengaturan yang tidak ikut disubmit tidak tertimpa nilai kosong. |
| `app/Views/admin/pengaturan/index.php` | Field "Nomor WhatsApp Admin" di tab WhatsApp, lengkap dengan contoh pengisian dan preview tautan yang bisa langsung diklik untuk diuji. |
| `app/Views/layouts/public.php` | Memasang partial tombol mengambang + baris "Butuh bantuan? WhatsApp kami di …" di footer. |
| `app/Views/layouts/auth.php` | Memasang partial tombol mengambang untuk halaman login, register, dan lupa password. |
| `app/Views/public/layanan.php` | Tombol "Tanya admin soal layanan ini" pada tiap kartu layanan, dengan nama layanan ikut di pesan. |
| `app/Views/public/booking_sukses.php` | Tombol chat admin di bawah blok kode booking, pesan sudah memuat kode dan nama pemesan. |
| `app/Views/cek_booking/index.php` | Tautan WhatsApp admin ketika pencarian kode tidak menghasilkan booking. |
| `app/Views/pelanggan/booking_detail.php` | Tombol tanya admin pada detail booking pelanggan. |
| `public/assets/css/salon-theme.css` | Gaya tombol mengambang, tombol WhatsApp inline, dan penyesuaian ruang di layar kecil. |
| `.env.example`, `.env.localhost` | Mendokumentasikan variabel opsional `salon.waAdmin`. |
| `README.md` | Menambah butir fitur tombol WhatsApp ke admin. |

### Cara kerja singkat

Sumber nomor dibaca berurutan, yang pertama ketemu dipakai:

1. pengaturan `wa_admin` di tabel `settings` — diubah dari `/admin/pengaturan`, tab WhatsApp
2. variabel `salon.waAdmin` di file `.env`
3. nilai bawaan di `app/Config/Salon.php`

Nomor yang tersimpan boleh ditulis `08…`, `+62…`, atau `62…`. Fungsi
`wa_normalize()` menyeragamkannya menjadi format `62…` untuk atribut `href`,
sementara `wa_display()` menampilkannya kembali dalam format lokal berkelompok
(`0878-6218-3074`) agar mudah dibaca pelanggan.

Kalau nomor belum diisi atau formatnya keliru, seluruh komponen WhatsApp tidak
dirender sama sekali — lebih baik tidak ada tombol daripada tombol yang menuju
nomor rusak.

### Pesan yang terisi otomatis

| Halaman | Pesan |
|---|---|
| Beranda, login, register | Halo Admin SW Beauty Salon, saya ingin bertanya tentang layanan salon. |
| `/layanan` (tombol per kartu) dan `/layanan/{id}` | Halo Admin SW Beauty Salon, saya ingin bertanya tentang layanan {nama layanan}. |
| `/booking/sukses/{kode}` | Halo Admin, saya ingin menanyakan booking dengan kode {kode} atas nama {nama}. |
| `/pelanggan/booking/{kode}` | Sama seperti di atas, memakai kode booking yang sedang dibuka. |
| `/cek-booking` | Memuat kode booking bila ada, kalau tidak memakai pesan umum. |

Seluruh nilai dinamis dilewatkan `rawurlencode()` di dalam `wa_link()`, jadi nama
layanan atau nama pemesan yang memuat spasi maupun karakter khusus tetap aman
dikirim lewat URL.

### Yang sengaja tidak diubah

- Alur WhatsApp arah sebaliknya (admin → pelanggan): template pesan, tombol
  salin, dan penanda "sudah dikirim" di panel admin tetap seperti semula, dan
  tetap ditangani `App\Services\WhatsAppTemplateService`.
- Tombol WhatsApp pelanggan **tidak** dirender di layout panel
  (`app/Views/layouts/panel.php`), jadi admin dan pemilik tidak melihatnya.
- Tidak ada paket, library, atau API eksternal baru. WhatsApp tetap manual lewat
  tautan `wa.me`, sesuai batasan proyek.

## Cara menguji

1. `php spark serve`, buka `http://localhost:8080/`. Tombol hijau bulat muncul di
   kanan bawah; arahkan kursor ke tombol untuk memunculkan label "Tanya Admin".
2. Klik tombolnya. WhatsApp terbuka di tab baru dengan nomor tujuan dan pesan
   yang sudah terisi. Cocokkan nomor tujuannya dengan nomor di
   `/admin/pengaturan` → tab WhatsApp.
3. Buka `/layanan`. Tiap kartu punya tombol "Tanya admin soal layanan ini" dan
   pesannya menyebut nama layanan yang bersangkutan.
4. Buka `/cek-booking`, masukkan kode asal-asalan. Setelah pencarian gagal akan
   muncul ajakan menghubungi admin lengkap dengan nomornya.
5. Masuk sebagai admin, ubah nomor di `/admin/pengaturan`, simpan, lalu muat
   ulang halaman publik. Nomor di footer dan tujuan tombol ikut berubah.
6. Kosongkan nomor di pengaturan dan hapus `salon.waAdmin` dari `.env` — sistem
   jatuh ke nilai bawaan di `app/Config/Salon.php`.
