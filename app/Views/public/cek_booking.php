<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<section class="section-header">
  <div class="h1">Cek Status Booking</div>
  <span class="tagline">Masukkan nomor WhatsApp yang Anda gunakan saat booking</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<form class="card-salon mb-3" method="post" action="<?= base_url('cek-booking') ?>" style="max-width:560px; margin:0 auto;">
  <?= csrf_field() ?>
  <label class="form-salon-label">Nomor WhatsApp *</label>
  <div class="flex gap-1">
    <input class="form-salon-input" type="tel" name="nomor_hp" value="<?= esc($phone ?? '') ?>" placeholder="08xxxxxxxxxx" required>
    <button class="btn-salon-primary" type="submit">Cari</button>
  </div>
</form>

<?php if ($phone): ?>
  <?php if (! empty($bookings)): ?>
    <div class="row-salon" style="grid-template-columns:1fr;">
      <?php foreach ($bookings as $b):
        $statusClass = 'badge-salon--' . ($b['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $b['status']));
        $statusLabel = ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$b['status']];
      ?>
        <div class="card-salon">
          <div class="flex justify-between items-center flex-wrap gap-1">
            <div>
              <div class="h3"><?= esc($b['nama_layanan']) ?></div>
              <div class="caption"><?= esc($b['kode_booking']) ?></div>
              <div class="tagline mt-1">
                <i class="bi bi-calendar3"></i> <?= esc(date('d M Y', strtotime($b['tanggal']))) ?>
                · <i class="bi bi-clock"></i> <?= esc(substr($b['slot_mulai'], 0, 5)) ?>–<?= esc(substr($b['slot_selesai'], 0, 5)) ?>
              </div>
            </div>
            <div class="text-right">
              <span class="badge-salon <?= $statusClass ?>"><?= esc($statusLabel) ?></span>
              <div class="mt-1"><a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('booking/' . $b['kode_booking'] . '?no_hp=' . urlencode($phone)) ?>">Detail →</a></div>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-search empty-state__icon"></i>
      <div class="empty-state__title">Tidak ada booking</div>
      <div class="empty-state__hint">Belum ada booking dengan nomor HP ini. <a href="<?= base_url('booking') ?>">Buat booking baru</a>.</div>
    </div>
  <?php endif ?>
<?php endif ?>

<?= $this->endSection() ?>
