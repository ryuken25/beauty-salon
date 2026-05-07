<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div>
    <span class="gold-badge badge rounded-pill mb-2">Riwayat</span>
    <h1 class="h2 mb-2">Riwayat Booking</h1>
    <p class="small-muted mb-0">Lihat status booking dan detail layanan yang pernah dibuat.</p>
  </div>
  <a class="btn btn-primary" href="<?= base_url('pelanggan/booking/baru') ?>">Booking Baru</a>
</div>

<?= view('partials/booking_table', ['bookings' => $bookings, 'customer' => true]) ?>

<?= $this->endSection() ?>
