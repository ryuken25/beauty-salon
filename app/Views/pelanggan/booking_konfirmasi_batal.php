<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div style="max-width:520px; margin:1.5rem auto;">
  <section class="section-header text-center">
    <div class="h1">Batalkan Booking</div>
    <span class="tagline">Konfirmasi pembatalan</span>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-exclamation-triangle ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
  </section>

  <div class="card-salon">
    <div class="caption" style="letter-spacing:2px;"><?= esc($booking['kode_booking']) ?></div>
    <div class="h2 mb-2"><?= esc($booking['nama_layanan']) ?></div>
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime((string) $booking['tanggal']))) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr((string) $booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr((string) $booking['slot_selesai'], 0, 5)) ?></td></tr>
    </table>

    <div class="alert-salon alert-salon--info mt-2" style="border-left:4px solid var(--color-danger);">
      <i class="bi bi-info-circle"></i>
      Setelah dibatalkan, slot akan otomatis dilepas. Pembatalan tercatat di sistem salon.
    </div>

    <form method="post" action="<?= base_url('pelanggan/booking/' . rawurlencode($booking['kode_booking']) . '/batal') ?>">
      <?= csrf_field() ?>
      <label class="form-salon-label">Alasan pembatalan (opsional)</label>
      <textarea class="form-salon-textarea" name="cancellation_reason" rows="3" maxlength="500" placeholder="Misal: ada acara mendadak"><?= esc(old('cancellation_reason')) ?></textarea>

      <div class="flex flex-wrap gap-2 mt-2">
        <a class="btn-salon-secondary" href="<?= base_url('pelanggan/booking/' . rawurlencode($booking['kode_booking'])) ?>">Tidak jadi</a>
        <button class="btn-salon-danger" type="submit"><i class="bi bi-x-circle"></i> Ya, Batalkan Booking</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
