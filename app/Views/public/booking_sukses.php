<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $tanggalLabel = date('d M Y', strtotime($booking['tanggal'])); ?>

<div style="max-width:480px; margin:1.5rem auto;">
  <div class="text-center">
    <div class="success-ring"><i class="bi bi-check-circle"></i></div>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-stars ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div class="h1 mb-1">Booking diterima</div>
    <div class="caption" style="letter-spacing:2px;"><?= esc($booking['kode_booking']) ?></div>
  </div>

  <div class="card-salon mt-3">
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc($tanggalLabel) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
      <tr><td class="label">Total</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
    </table>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div class="text-center"><span class="badge-salon badge-salon--pending">Menunggu verifikasi</span></div>
  </div>

  <?php if ($wa_link): ?>
    <a class="btn-salon-success btn-salon--full mt-3" target="_blank" rel="noopener" href="<?= esc($wa_link) ?>">
      <i class="bi bi-whatsapp"></i> Chat owner via WhatsApp
    </a>
    <div class="caption text-center mt-1">Pesan akan terisi otomatis.</div>
  <?php endif ?>


  <div class="text-center mt-3">
    <div class="tagline">Terima kasih sudah memesan</div>
    <div class="mt-2"><a href="<?= base_url('/') ?>">← Kembali ke beranda</a> · <a href="<?= base_url('cek-booking') ?>">Cek status booking</a></div>
  </div>
</div>

<?= $this->endSection() ?>
