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
?>

<section class="section-header">
  <div class="h1">Halo, <?= esc($nama) ?></div>
  <span class="tagline">Riwayat &amp; booking aktif Anda</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<div class="flex justify-between items-center flex-wrap gap-2 mb-2">
  <div class="caption">Total <?= count($bookings) ?> booking</div>
  <a class="btn-salon-primary" href="<?= base_url('booking') ?>"><i class="bi bi-plus-circle"></i> Booking baru</a>
</div>

<?php if (empty($bookings)): ?>
  <div class="card-salon empty-state">
    <i class="bi bi-calendar-x empty-state__icon"></i>
    <div class="empty-state__title">Belum ada booking</div>
    <div class="empty-state__hint">Mulai booking layanan pertama Anda.</div>
    <a class="btn-salon-primary mt-2" href="<?= base_url('booking') ?>">Booking sekarang</a>
  </div>
<?php else: ?>
  <div class="row-salon">
    <?php foreach ($bookings as $b):
      [$label, $cls] = $statusMap[$b['status']] ?? [$b['status'], 'pending'];
    ?>
      <div class="card-salon card-salon--<?= $cls ?>">
        <div class="flex justify-between items-center flex-wrap gap-1">
          <div>
            <div class="h3"><?= esc($b['nama_layanan']) ?></div>
            <div class="caption"><i class="bi bi-calendar"></i> <?= esc($b['tanggal']) ?> · <i class="bi bi-clock"></i> <?= esc(substr((string) $b['slot_mulai'], 0, 5)) ?>–<?= esc(substr((string) $b['slot_selesai'], 0, 5)) ?></div>
            <div class="caption">Kode: <strong><?= esc($b['kode_booking']) ?></strong> · Stylist: <?= esc($b['nama_stylist']) ?></div>
          </div>
          <div class="text-right">
            <span class="badge-salon badge-salon--<?= $cls ?>"><?= esc($label) ?></span>
            <div class="mt-1">
              <a class="btn-salon-secondary btn-salon--sm" href="<?= base_url('booking/' . $b['kode_booking'] . '?no_hp=' . urlencode($b['nomor_hp_pelanggan'])) ?>">Detail</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<?= $this->endSection() ?>
