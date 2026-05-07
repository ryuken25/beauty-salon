<?php
use App\Services\WhatsAppTemplateService;
if (! function_exists('lbl')) {
    function lbl($s) { return ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima / Terjadwal', 'rejected' => 'Ditolak', 'cancelled' => 'Batal', 'completed' => 'Selesai'][$s] ?? $s; }
}
if (! function_exists('detail_status_class')) {
    function detail_status_class($s) { return ['accepted' => 'status-accepted', 'completed' => 'status-completed', 'rejected' => 'status-rejected', 'cancelled' => 'status-cancelled'][$s] ?? 'status-pending'; }
}
$wa = new WhatsAppTemplateService();
$type = $booking['status'] === 'accepted' ? 'accepted' : ($booking['status'] === 'rejected' ? 'rejected' : ($booking['status'] === 'cancelled' ? 'cancelled' : 'completed'));
$msg = $wa->message($type, $booking);
$link = $wa->link($booking['customer_phone'] ?? '', $msg);
?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div>
    <span class="gold-badge badge rounded-pill mb-2">Detail booking</span>
    <h1 class="h2 mb-2"><?= esc($booking['booking_code']) ?></h1>
    <span class="badge badge-status <?= detail_status_class($booking['status']) ?>"><?= lbl($booking['status']) ?></span>
  </div>
  <a class="btn btn-outline-primary" href="<?= ($admin ?? false) ? base_url('admin/booking') : base_url('pelanggan/booking') ?>">Kembali</a>
</div>

<div class="salon-card mb-3">
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-6">
        <h2 class="h5 section-title mb-3">Data Pelanggan</h2>
        <p class="mb-2"><strong>Pelanggan:</strong> <?= esc($booking['customer_name'] ?? '') ?></p>
        <p class="mb-2"><strong>No. WhatsApp:</strong> <?= esc($booking['customer_phone'] ?? '') ?></p>
        <p class="mb-2"><strong>Layanan:</strong> <?= esc($booking['service_name']) ?></p>
        <p class="mb-0"><strong>Harga:</strong> Rp<?= number_format($booking['service_price'] ?? 0, 0, ',', '.') ?></p>
      </div>
      <div class="col-md-6">
        <h2 class="h5 section-title mb-3">Jadwal</h2>
        <p class="mb-2"><strong>Stylist:</strong> <?= esc($booking['stylist_name']) ?></p>
        <p class="mb-2"><strong>Tanggal:</strong> <?= esc($booking['booking_date']) ?></p>
        <p class="mb-2"><strong>Jam:</strong> <?= esc(substr($booking['start_time'], 0, 5)) ?> - <?= esc(substr($booking['end_time'], 0, 5)) ?></p>
        <p class="mb-2"><strong>Durasi:</strong> <?= esc($booking['duration_minutes'] ?? '') ?> menit</p>
        <p class="mb-0"><strong>Catatan:</strong> <?= esc($booking['notes']) ?></p>
      </div>
    </div>
  </div>
</div>

<?php if ($admin ?? false): ?>
  <div class="soft-card mb-3">
    <h2 class="h5 section-title mb-3">Aksi Admin</h2>
    <div class="d-flex flex-wrap admin-actions">
      <?php if ($booking['status'] === 'pending_verification'): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/terima') ?>"><?= csrf_field() ?><button class="btn btn-success" type="submit">Terima</button></form>
        <form class="d-flex flex-wrap gap-2" method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/tolak') ?>"><?= csrf_field() ?><input class="form-control" name="rejection_reason" placeholder="Alasan penolakan"><button class="btn btn-danger" type="submit">Tolak</button></form>
      <?php endif ?>
      <?php if (in_array($booking['status'], ['pending_verification', 'accepted'])): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/batal') ?>"><?= csrf_field() ?><button class="btn btn-outline-danger" type="submit">Batalkan</button></form>
      <?php endif ?>
      <?php if ($booking['status'] === 'accepted'): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/selesai') ?>"><?= csrf_field() ?><button class="btn btn-primary" type="submit">Selesai + Buat Transaksi</button></form>
      <?php endif ?>
    </div>
  </div>
<?php endif ?>

<div class="form-card">
  <h2 class="h5 section-title mb-3">Template WhatsApp Manual</h2>
  <textarea id="waMsg" class="form-control mb-3" rows="7" readonly><?= esc($msg) ?></textarea>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-outline-primary" type="button" onclick="salinPesan('waMsg')">Salin Pesan</button>
    <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= esc($link) ?>">Buka WhatsApp</a>
    <?php if ($admin ?? false): ?>
      <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/wa-sent') ?>"><?= csrf_field() ?><button class="btn btn-outline-secondary" type="submit">Tandai WA Sudah Dikirim</button></form>
    <?php endif ?>
  </div>
</div>
