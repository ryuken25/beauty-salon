<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div>
    <span class="gold-badge badge rounded-pill mb-2">Area pelanggan</span>
    <h1 class="h2 mb-2">Dashboard Pelanggan</h1>
    <p class="small-muted mb-0">Hasil verifikasi booking akan diinformasikan melalui WhatsApp manual oleh admin/pemilik.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= base_url('pelanggan/booking/baru') ?>">Buat Booking</a>
    <a class="btn btn-outline-primary" href="<?= base_url('pelanggan/booking') ?>">Riwayat Booking</a>
  </div>
</div>

<h2 class="section-title h4 mb-3">Booking Terbaru</h2>
<?= view('partials/booking_table', ['bookings' => $bookings, 'customer' => true]) ?>

<?= $this->endSection() ?>
