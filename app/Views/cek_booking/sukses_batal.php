<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div style="max-width:480px; margin:1.5rem auto;">
  <div class="text-center">
    <div class="success-ring"><i class="bi bi-check-circle"></i></div>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-stars ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div class="h1 mb-1">Pembatalan Berhasil</div>
    <div class="caption">Booking <strong><?= esc($booking['kode_booking']) ?></strong> sudah dibatalkan dan slot waktunya otomatis dilepas. Pembatalan ini sudah tercatat di sistem salon — tidak perlu konfirmasi tambahan.</div>
  </div>

  <div class="card-salon mt-3">
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime((string) $booking['tanggal']))) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right"><?= esc(substr((string) $booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr((string) $booking['slot_selesai'], 0, 5)) ?></td></tr>
    </table>
    <div class="text-center mt-2"><span class="badge-salon badge-salon--cancelled">Batal</span></div>
  </div>

  <div class="flex flex-wrap gap-2 mt-3" style="justify-content:center;">
    <?php if (session('is_logged_in') && session('user_role') === 'pelanggan'): ?>
      <a class="btn-salon-primary" href="<?= base_url('pelanggan/dashboard') ?>"><i class="bi bi-grid"></i> Ke dashboard</a>
    <?php endif ?>
    <a class="btn-salon-secondary" href="<?= base_url('/') ?>"><i class="bi bi-house"></i> Beranda</a>
  </div>
</div>

<?= $this->endSection() ?>
