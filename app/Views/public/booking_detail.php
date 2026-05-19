<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$booking['status']];
$statusClass = 'badge-salon--' . ($booking['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $booking['status']));
$canCancel = in_array($booking['status'], ['pending_verification', 'accepted'], true)
    && strtotime("{$booking['tanggal']} {$booking['slot_mulai']}") - time() > 2 * 3600;
?>

<div style="max-width:680px; margin:1.5rem auto;">
  <div class="page-header-salon">
    <div>
      <span class="caption" style="letter-spacing:2px;"><?= esc($booking['kode_booking']) ?></span>
      <div class="h1"><?= esc($booking['nama_layanan']) ?></div>
      <span class="badge-salon <?= $statusClass ?>"><?= esc($statusLabel) ?></span>
    </div>
    <a class="btn-salon-ghost" href="<?= base_url('cek-booking') ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
  </div>

  <div class="card-salon mb-3">
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Nama</td><td class="text-right"><?= esc($booking['nama_pelanggan']) ?></td></tr>
      <tr><td class="label">No. HP</td><td class="text-right"><?= esc($booking['nomor_hp_pelanggan']) ?></td></tr>
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime($booking['tanggal']))) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
      <tr><td class="label">Durasi</td><td class="text-right"><?= esc($booking['durasi_menit']) ?> menit</td></tr>
      <tr><td class="label">Total</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
      <?php if (! empty($booking['catatan'])): ?>
        <tr><td class="label">Catatan</td><td class="text-right"><?= esc($booking['catatan']) ?></td></tr>
      <?php endif ?>
      <?php if (! empty($booking['rejection_reason'])): ?>
        <tr><td class="label">Alasan ditolak</td><td class="text-right"><?= esc($booking['rejection_reason']) ?></td></tr>
      <?php endif ?>
    </table>
  </div>

  <div class="flex flex-wrap gap-1 mb-3">
    <?php if ($wa_link): ?>
      <a class="btn-salon-success" target="_blank" rel="noopener" href="<?= esc($wa_link) ?>"><i class="bi bi-whatsapp"></i> Chat owner</a>
    <?php endif ?>
    <?php if ($canCancel): ?>
      <form method="post" action="<?= base_url('booking/' . $booking['kode_booking'] . '/batal') ?>" onsubmit="return confirm('Batalkan booking ini?');">
        <?= csrf_field() ?>
        <input type="hidden" name="nomor_hp" value="<?= esc($phone) ?>">
        <button class="btn-salon-danger" type="submit"><i class="bi bi-x-circle"></i> Batalkan booking</button>
      </form>
    <?php endif ?>
  </div>

  <?php if (! empty($logs)): ?>
    <div class="card-salon">
      <div class="h2 mb-2">Riwayat aktivitas</div>
      <?= view('partials/booking_timeline', ['logs' => $logs]) ?>
    </div>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
