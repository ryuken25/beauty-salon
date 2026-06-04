<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$statusMap = [
    'pending_verification' => ['Menunggu Verifikasi', 'pending'],
    'accepted' => ['Diterima', 'accepted'],
    'completed' => ['Selesai', 'completed'],
    'rejected' => ['Ditolak', 'rejected'],
    'cancelled' => ['Batal', 'cancelled'],
];
$payMap = [
    'unpaid' => ['Belum bayar DP', 'pending'],
    'dp_uploaded' => ['DP menunggu verifikasi', 'pending'],
    'dp_verified' => ['DP terverifikasi', 'completed'],
];
[$statusLabel, $statusCls] = $statusMap[$booking['status']] ?? [$booking['status'], 'pending'];
[$payLabel, $payCls] = $payMap[$booking['payment_status'] ?? 'unpaid'] ?? ['—', 'pending'];
$cancelable = in_array($booking['status'], ['pending_verification', 'accepted'], true);
?>

<div style="max-width:620px; margin:1rem auto;">
  <div class="flex justify-between items-center mb-2">
    <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('pelanggan/dashboard') ?>"><i class="bi bi-arrow-left"></i> Dashboard</a>
    <span class="badge-salon badge-salon--<?= esc($statusCls) ?>"><?= esc($statusLabel) ?></span>
  </div>

  <div class="card-salon">
    <div class="caption" style="letter-spacing:2px;"><?= esc($booking['kode_booking']) ?></div>
    <div class="h2 mb-2"><?= esc($booking['nama_layanan']) ?></div>

    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime((string) $booking['tanggal']))) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr((string) $booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr((string) $booking['slot_selesai'], 0, 5)) ?></td></tr>
      <tr><td class="label">Durasi</td><td class="text-right"><?= esc($booking['durasi_menit']) ?> menit</td></tr>
      <tr><td class="label">Harga</td><td class="text-right">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
      <tr><td class="label">DP</td><td class="text-right">
        Rp <?= number_format((int) ($booking['dp_amount'] ?? 0), 0, ',', '.') ?>
        <span class="badge-salon badge-salon--<?= esc($payCls) ?>" style="font-size:0.6875rem; margin-left:0.25rem;"><?= esc($payLabel) ?></span>
      </td></tr>
      <?php if (! empty($booking['catatan'])): ?>
        <tr><td class="label">Catatan</td><td class="text-right"><?= esc($booking['catatan']) ?></td></tr>
      <?php endif ?>
      <?php if (! empty($booking['cancellation_reason'])): ?>
        <tr><td class="label">Alasan batal</td><td class="text-right" style="color:var(--color-danger);"><?= esc($booking['cancellation_reason']) ?></td></tr>
      <?php endif ?>
      <?php if (! empty($booking['rejection_reason'])): ?>
        <tr><td class="label">Alasan tolak</td><td class="text-right" style="color:var(--color-danger);"><?= esc($booking['rejection_reason']) ?></td></tr>
      <?php endif ?>
    </table>

    <?php if ($cancelable): ?>
      <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
      <a class="btn-salon-danger btn-salon--full" href="<?= base_url('pelanggan/booking/' . rawurlencode($booking['kode_booking']) . '/batal') ?>">
        <i class="bi bi-x-circle"></i> Batalkan booking
      </a>
      <div class="form-salon-help mt-1">Pembatalan dibolehkan ≥ 2 jam sebelum jam mulai. Slot akan otomatis dilepas.</div>
    <?php endif ?>
  </div>

  <div class="text-center mt-3">
    <a href="<?= base_url('pelanggan/dashboard') ?>">&larr; Kembali ke dashboard</a>
  </div>
</div>

<?= $this->endSection() ?>
