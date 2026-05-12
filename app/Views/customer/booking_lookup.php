<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
if (! function_exists('lbl_status_pelanggan')) {
    function lbl_status_pelanggan($s) {
        return ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima / Terjadwal', 'rejected' => 'Ditolak', 'cancelled' => 'Batal', 'completed' => 'Selesai'][$s] ?? $s;
    }
}
?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Cek booking</span>
  <h1 class="h2 mb-2">Cek Status Booking</h1>
  <p class="small-muted mb-0">Masukkan kode booking dan nomor WhatsApp yang dipakai saat memesan.</p>
</div>

<div class="form-card mb-3">
  <form method="post" action="<?= base_url('pelanggan/booking/cek') ?>">
    <?= csrf_field() ?>
    <div class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Kode Booking</label>
        <input class="form-control" name="booking_code" placeholder="SWB-XXXXXX-XXXXXX" required>
      </div>
      <div class="col-md-5">
        <label class="form-label">No. WhatsApp</label>
        <input class="form-control" name="customer_phone" placeholder="08xxxxxxxxxx" required>
      </div>
      <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Cek</button></div>
    </div>
    <?php if (! empty($error)): ?>
      <div class="alert alert-danger mt-3 mb-0"><?= esc($error) ?></div>
    <?php endif ?>
  </form>
</div>

<?php if (! empty($booking)): ?>
  <div class="salon-card mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h2 class="h4 mb-1"><?= esc($booking['booking_code']) ?></h2>
          <span class="badge badge-status status-<?= esc($booking['status']) ?>"><?= esc(lbl_status_pelanggan($booking['status'])) ?></span>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <p class="mb-1"><strong>Nama:</strong> <?= esc($booking['customer_name']) ?></p>
          <p class="mb-1"><strong>Layanan:</strong> <?= esc($booking['service_name']) ?> (<?= esc($booking['duration_minutes']) ?> menit)</p>
          <p class="mb-0"><strong>Stylist:</strong> <?= esc($booking['stylist_name']) ?></p>
        </div>
        <div class="col-md-6">
          <p class="mb-1"><strong>Tanggal:</strong> <?= esc(date('d/m/Y', strtotime($booking['booking_date']))) ?></p>
          <p class="mb-1"><strong>Jam:</strong> <?= esc(substr($booking['start_time'], 0, 5)) ?> – <?= esc(substr($booking['end_time'], 0, 5)) ?></p>
          <p class="mb-0"><strong>Harga:</strong> Rp<?= number_format((float) $booking['service_price'], 0, ',', '.') ?></p>
        </div>
      </div>
      <?php if (in_array($booking['status'], ['pending_verification', 'accepted'], true)): ?>
        <hr>
        <form method="post" action="<?= base_url('pelanggan/booking/' . $booking['id'] . '/batal') ?>" onsubmit="return confirm('Yakin batalkan booking ini?');">
          <?= csrf_field() ?>
          <input type="hidden" name="booking_code" value="<?= esc($booking['booking_code']) ?>">
          <input type="hidden" name="customer_phone" value="<?= esc($booking['customer_phone']) ?>">
          <button class="btn btn-outline-danger" type="submit">Batalkan Booking</button>
        </form>
      <?php endif ?>
    </div>
  </div>

  <?php if (! empty($logs)): ?>
    <?= view('partials/booking_timeline', ['logs' => $logs]) ?>
  <?php endif ?>
<?php endif ?>

<?= $this->endSection() ?>
