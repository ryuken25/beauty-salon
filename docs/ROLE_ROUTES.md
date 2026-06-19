# Aturan Route dan Akses Role SW Beauty Salon

Dokumen ini menjelaskan konvensi route, role, filter akses, dan alur navigasi di SW Beauty Salon.

---

## 1. Konvensi Route

Aplikasi ini mendefinisikan tiga area utama berdasarkan route prefix:

1. **`/admin/*` (Area Operasional)**
   * Digunakan untuk tugas harian operasional salon seperti pencatatan booking, walk-in, melihat jadwal, mengelola pelanggan, melihat riwayat transaksi, dan pengaturan profil.
   * Boleh diakses oleh role: **`admin`** dan **`pemilik`**.
   * Pemilik (owner) adalah *superset* dari admin sehingga pemilik tidak memerlukan duplikasi route di `/owner/*` untuk melakukan walk-in atau memproses transaksi.

2. **`/owner/*` (Area Manajerial)**
   * Digunakan untuk tugas analitik dan manajerial tingkat pemilik seperti melihat laporan grafik pendapatan, rincian cash flow, dan mengelola daftar layanan.
   * Hanya boleh diakses oleh role: **`pemilik`**.
   * Role `admin` atau `pelanggan` **tidak boleh** mengakses area ini.

3. **`/pelanggan/*` (Area Pelanggan)**
   * Digunakan untuk halaman dashboard pelanggan, booking online, dan melacak riwayat pemesanan.
   * Hanya boleh diakses oleh role: **`pelanggan`**.

---

## 2. Redirect Login & Filter

Akses dikendalikan melalui filter CodeIgniter 4 (`app/Config/Filters.php`):

| URI Prefix | Nama Filter | Role yang Diizinkan | Jika Ditolak |
| :--- | :--- | :--- | :--- |
| `/admin/*` | `admin` | `admin`, `pemilik` | Redirect ke `/login` (jika belum login) atau ke `/` (jika rolenya pelanggan). |
| `/owner/*` | `owner` | `pemilik` | Redirect ke `/admin/dashboard` dengan pesan error "Area ini khusus pemilik". |
| `/pelanggan/*` | `customer` | `pelanggan` | Redirect ke `/login`. |

### Alur Redirect Login:
* Akun **Admin** login → diarahkan langsung ke `/admin/dashboard`.
* Akun **Pemilik** login → diarahkan langsung ke `/admin/dashboard` (bukan `/owner/dashboard`). Pemilik dapat mengakses menu laporan dan layanan dari sidebar.
* Akun **Pelanggan** login → diarahkan langsung ke `/pelanggan/dashboard`.

---

## 3. Akses Menu Sidebar

* **Admin biasa** hanya melihat menu operasional:
  * Dashboard
  * Booking
  * Walk-in
  * Jadwal
  * Pelanggan
  * Transaksi
  * Pengaturan
* **Pemilik (Owner)** melihat semua menu admin di atas, ditambah grup menu **Manajerial**:
  * Laporan
  * Layanan
