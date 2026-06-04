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
$cancelable = ['pending_verification', 'accepted'];
?>

<section class="section-header">
  <div class="h1">Cek &amp; Batal Booking</div>
  <span class="tagline">Masukkan nomor WhatsApp untuk melihat semua booking Anda</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<div class="card-salon" style="max-width:520px; margin:0 auto;">
  <form method="post" action="<?= base_url('cek-booking') ?>">
    <?= csrf_field() ?>
    <label class="form-salon-label">Nomor WhatsApp Anda</label>
    <input class="form-salon-input" type="tel" name="nomor_hp" required value="<?= esc(old('nomor_hp') ?? $phone) ?>" placeholder="08xxxxxxxxxx">
    <button class="btn-salon-primary btn-salon--full mt-2" type="submit"><i class="bi bi-search"></i> Lihat booking saya</button>
  </form>
</div>

<?php if (! empty($bookings)): ?>
  <div class="mt-3" style="max-width:760px; margin:0 auto;">
    <div class="caption mb-1">Ditemukan <?= count($bookings) ?> booking untuk nomor <strong><?= esc($phone) ?></strong>:</div>
    <?php foreach ($bookings as $b):
      [$label, $cls] = $statusMap[$b['status']] ?? [$b['status'], 'pending'];
      $isCancelable = in_array($b['status'], $cancelable, true);
    ?>
      <div class="card-salon card-salon--<?= $cls ?> mb-2">
        <div class="flex justify-between items-center flex-wrap gap-1">
          <div>
            <div class="caption" style="letter-spacing:1.5px;"><?= esc($b['kode_booking']) ?></div>
            <div class="h3"><?= esc($b['nama_layanan']) ?></div>
            <div class="caption">
              <i class="bi bi-calendar"></i> <?= esc(date('d M Y', strtotime($b['tanggal']))) ?>
              · <i class="bi bi-clock"></i>
              <?= esc(substr((string) $b['slot_mulai'], 0, 5)) ?>–<?= esc(substr((string) $b['slot_selesai'], 0, 5)) ?>
            </div>
            <div class="caption">
              DP: Rp <?= number_format((int) ($b['dp_amount'] ?? 0), 0, ',', '.') ?>
              <?php if (! empty($b['cancellation_reason'])): ?>
                · Alasan batal: <span style="color:var(--color-danger);"><?= esc($b['cancellation_reason']) ?></span>
              <?php endif ?>
            </div>
          </div>
          <div class="text-right">
            <span class="badge-salon badge-salon--<?= $cls ?>"><?= esc($label) ?></span>
            <?php if ($isCancelable): ?>
              <div class="mt-1">
                <a class="btn-salon-danger btn-salon--sm"
                   href="<?= base_url('cek-booking/' . rawurlencode($b['kode_booking']) . '/batal?no_hp=' . urlencode($phone)) ?>">
                  <i class="bi bi-x-circle"></i> Batalkan
                </a>
              </div>
            <?php endif ?>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<div class="text-center mt-3">
  <a href="<?= base_url('/') ?>">&larr; Kembali ke beranda</a>
</div>

<?= $this->endSection() ?>
