<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$nama = $booking['customer_name'] ?? 'Pelanggan';
$tanggalLabel = date('d/m/Y', strtotime($booking['booking_date']));
$jamMulai = substr($booking['start_time'], 0, 5);
$jamSelesai = substr($booking['end_time'], 0, 5);
$template = "Halo SW Beauty Salon, saya {$nama} sudah melakukan booking:\n\n"
    . "• ID: {$booking['booking_code']}\n"
    . "• Layanan: {$booking['service_name']}\n"
    . "• Tanggal: {$tanggalLabel}\n"
    . "• Jam: {$jamMulai} – {$jamSelesai}\n"
    . "• Stylist: {$booking['stylist_name']}\n\n"
    . 'Mohon konfirmasi. Terima kasih.';
$ownerPhone = preg_replace('/\D+/', '', (string) ($owner_whatsapp ?? ''));
if ($ownerPhone !== '' && str_starts_with($ownerPhone, '0')) {
    $ownerPhone = '62' . substr($ownerPhone, 1);
}
$waLink = $ownerPhone !== '' ? 'https://wa.me/' . $ownerPhone . '?text=' . rawurlencode($template) : '';
?>

<div class="page-header text-center">
  <span class="gold-badge badge rounded-pill mb-2">Booking diterima sistem</span>
  <h1 class="h2 mb-2">✅ Booking Berhasil Dibuat</h1>
  <p class="small-muted mb-0">Booking Anda menunggu verifikasi admin. Kirim konfirmasi ke owner via WhatsApp agar diproses lebih cepat.</p>
</div>

<div class="salon-card mb-3">
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-6">
        <h2 class="h5 section-title mb-3">Detail Booking</h2>
        <p class="mb-2"><strong>Kode Booking:</strong> <?= esc($booking['booking_code']) ?></p>
        <p class="mb-2"><strong>Layanan:</strong> <?= esc($booking['service_name']) ?></p>
        <p class="mb-2"><strong>Durasi:</strong> <?= esc($booking['duration_minutes']) ?> menit</p>
        <p class="mb-0"><strong>Harga:</strong> Rp<?= number_format((float) ($booking['service_price'] ?? 0), 0, ',', '.') ?></p>
      </div>
      <div class="col-md-6">
        <h2 class="h5 section-title mb-3">Jadwal</h2>
        <p class="mb-2"><strong>Stylist:</strong> <?= esc($booking['stylist_name']) ?></p>
        <p class="mb-2"><strong>Tanggal:</strong> <?= esc($tanggalLabel) ?></p>
        <p class="mb-2"><strong>Jam:</strong> <?= esc($jamMulai) ?> – <?= esc($jamSelesai) ?></p>
        <p class="mb-0"><strong>Status:</strong> <span class="badge badge-status status-pending">Menunggu Verifikasi Admin</span></p>
      </div>
    </div>
  </div>
</div>

<div class="form-card text-center">
  <h2 class="h5 section-title mb-3">Konfirmasi ke Owner</h2>
  <?php if ($waLink !== ''): ?>
    <p class="small-muted mb-3">Klik tombol di bawah untuk membuka WhatsApp dengan pesan konfirmasi yang sudah terisi otomatis.</p>
    <a class="btn btn-success btn-lg" style="background-color:#25d366;border-color:#25d366;" target="_blank" rel="noopener" href="<?= esc($waLink) ?>">
      💬 Chat Owner via WhatsApp
    </a>
    <p class="small-muted mt-3 mb-0">Admin juga sudah menerima notifikasi Telegram otomatis dengan tombol verifikasi cepat.</p>
  <?php else: ?>
    <p class="small-muted mb-0">Nomor WhatsApp owner belum diatur. Admin akan menghubungi Anda melalui WhatsApp manual setelah verifikasi.</p>
  <?php endif ?>
  <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
    <a class="btn btn-outline-primary" href="<?= base_url('pelanggan/booking/cek') ?>">Cek Status Booking</a>
    <a class="btn btn-outline-secondary" href="<?= base_url('/') ?>">Kembali ke Beranda</a>
  </div>
  <div class="alert alert-info mt-4 mb-0">
    <strong>Simpan kode booking:</strong> <code><?= esc($booking['booking_code']) ?></code><br>
    Gunakan kode ini bersama No. WhatsApp Anda untuk mengecek status / membatalkan booking di halaman <em>Cek Booking</em>.
  </div>
</div>

<?= $this->endSection() ?>
