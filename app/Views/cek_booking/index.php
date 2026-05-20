<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = [
    'pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima',
    'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal',
];
?>

<section class="section-header">
  <div class="h1">Cek &amp; Batalkan Booking</div>
  <span class="tagline">Masukkan kode booking dan nomor WhatsApp Anda</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<form class="card-salon mb-3" method="post" action="<?= base_url('cek-booking') ?>" style="max-width:560px; margin:0 auto;">
  <?= csrf_field() ?>
  <div class="row-salon cols-2">
    <div>
      <label class="form-salon-label">Kode Booking *</label>
      <input class="form-salon-input" type="text" name="kode_booking" value="<?= esc(old('kode_booking') ?? $kode) ?>" placeholder="SW-20260520-001" required>
    </div>
    <div>
      <label class="form-salon-label">Nomor WhatsApp *</label>
      <input class="form-salon-input" type="tel" name="nomor_hp" value="<?= esc(old('nomor_hp') ?? $phone) ?>" placeholder="08xxxxxxxxxx" required>
    </div>
  </div>
  <button class="btn-salon-primary btn-salon--full mt-2" type="submit"><i class="bi bi-search"></i> Cari Booking</button>
</form>

<?php if ($booking !== null): ?>
  <?php
  $cls = 'badge-salon--' . ($booking['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $booking['status']));
  ?>
  <div class="card-salon" style="max-width:560px; margin:0 auto;">
    <div class="flex justify-between items-center mb-2">
      <div class="h2" style="margin:0;"><?= esc($booking['nama_layanan']) ?></div>
      <span class="badge-salon <?= $cls ?>"><?= esc($statusLabel[$booking['status']] ?? $booking['status']) ?></span>
    </div>
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Kode</td><td class="text-right"><strong><?= esc($booking['kode_booking']) ?></strong></td></tr>
      <tr><td class="label">Nama</td><td class="text-right"><?= esc($booking['nama_pelanggan']) ?></td></tr>
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime($booking['tanggal']))) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
      <tr><td class="label">Stylist</td><td class="text-right"><?= esc($booking['nama_stylist'] ?? '—') ?></td></tr>
      <tr><td class="label">Total</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
      <?php if (! empty($booking['cancellation_reason'])): ?>
        <tr><td class="label">Alasan batal</td><td class="text-right"><?= esc($booking['cancellation_reason']) ?></td></tr>
      <?php endif ?>
    </table>

    <?php if ($cancelable): ?>
      <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
      <a class="btn-salon-danger btn-salon--full"
         href="<?= base_url('cek-booking/' . rawurlencode($booking['kode_booking']) . '/batal?no_hp=' . urlencode($phone)) ?>">
        <i class="bi bi-x-circle"></i> Batalkan Booking
      </a>
    <?php else: ?>
      <div class="caption mt-2 text-center">Booking ini sudah final — tidak dapat dibatalkan.</div>
    <?php endif ?>
  </div>
<?php endif ?>

<?= $this->endSection() ?>
