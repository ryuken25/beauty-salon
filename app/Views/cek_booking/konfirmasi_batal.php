<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<section class="section-header">
  <div class="h1">Konfirmasi Pembatalan Booking</div>
  <span class="tagline">Periksa detail di bawah sebelum membatalkan</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<div style="max-width:560px; margin:0 auto;">
  <div class="card-salon mb-3">
    <div class="label mb-1">Detail booking</div>
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Kode</td><td class="text-right"><strong><?= esc($booking['kode_booking']) ?></strong></td></tr>
      <tr><td class="label">Nama</td><td class="text-right"><?= esc($booking['nama_pelanggan']) ?></td></tr>
      <tr><td class="label">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime($booking['tanggal']))) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
      <tr><td class="label">Total</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
    </table>
  </div>

  <div class="alert-salon alert-salon--error mb-3">
    <i class="bi bi-exclamation-triangle"></i>
    Apakah Anda yakin ingin membatalkan booking ini? Slot waktu akan dilepas dan
    tidak bisa dikembalikan. Setelah konfirmasi, Anda akan diarahkan ke WhatsApp
    untuk memberitahukan pembatalan ke admin.
  </div>

  <form id="batalForm" method="post" action="<?= base_url('cek-booking/' . rawurlencode($booking['kode_booking']) . '/batal') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="nomor_hp" value="<?= esc($phone) ?>">
    <div class="card-salon mb-3">
      <label class="form-salon-label">Alasan pembatalan (opsional)</label>
      <textarea class="form-salon-textarea" name="cancellation_reason" rows="3" maxlength="500"
                placeholder="Misal: berhalangan hadir, ada urusan mendadak, dll."></textarea>
      <div class="form-salon-help">Maks 500 karakter. Membantu salon untuk peningkatan layanan.</div>
    </div>
    <div class="flex gap-1" style="flex-wrap:wrap;">
      <button type="submit" class="btn-salon-danger" id="batalSubmit" style="flex:1; justify-content:center;">
        <i class="bi bi-x-circle"></i> Ya, Batalkan &amp; Lapor via WhatsApp
      </button>
      <a class="btn-salon-secondary" href="<?= base_url('cek-booking') ?>" style="flex:0 0 auto; justify-content:center;">
        Tidak, Kembali
      </a>
    </div>
  </form>
</div>

<script>
(function () {
  const form = document.getElementById('batalForm');
  const btn = document.getElementById('batalSubmit');
  form.addEventListener('submit', () => {
    setTimeout(() => { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...'; }, 0);
  });
})();
</script>

<?= $this->endSection() ?>
