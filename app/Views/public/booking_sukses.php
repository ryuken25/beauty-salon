<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php $tanggalLabel = date('d M Y', strtotime($booking['tanggal'])); ?>

<div style="max-width:480px; margin:1.5rem auto;">
  <div class="text-center">
    <div class="success-ring"><i class="bi bi-check-circle"></i></div>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-stars ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div class="h1 mb-1">Booking berhasil dibuat</div>
    <div class="caption">Mohon menunggu konfirmasi via WhatsApp dari salon.</div>
  </div>

  <!-- Kode booking — penting untuk cek / batal booking -->
  <div class="card-salon mt-3" style="text-align:center; background:var(--bg); border-color:var(--gold);">
    <div class="label">Kode Booking Anda</div>
    <div id="kodeBooking" style="font-family:var(--font-display); font-size:1.857rem; color:var(--gold); letter-spacing:2px; margin:0.25rem 0;"><?= esc($booking['kode_booking']) ?></div>
    <button type="button" class="btn-salon-secondary btn-salon--sm" id="copyKode">
      <i class="bi bi-clipboard"></i> Salin kode
    </button>
    <div class="form-salon-help" style="margin-top:0.4rem;">Simpan kode ini untuk cek status atau membatalkan booking.</div>
  </div>

  <div class="card-salon mt-3">
    <table style="width:100%; font-size:0.875rem;">
      <tr><td class="label">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
      <tr><td class="label">Tanggal</td><td class="text-right"><?= esc($tanggalLabel) ?></td></tr>
      <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
      <tr><td class="label">Estimasi total</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
    </table>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div class="text-center"><span class="badge-salon badge-salon--pending">Menunggu verifikasi</span></div>
  </div>

  <div class="flex flex-wrap gap-2 mt-3" style="justify-content:center;">
    <a class="btn-salon-primary" href="<?= base_url('pelanggan/dashboard') ?>"><i class="bi bi-grid"></i> Ke dashboard saya</a>
    <a class="btn-salon-secondary" href="<?= base_url('cek-booking') ?>"><i class="bi bi-search"></i> Cek status booking</a>
  </div>
  <div class="text-center mt-2">
    <a href="<?= base_url('/') ?>">&larr; Kembali ke beranda</a>
  </div>
</div>

<script>
(function () {
  const btn = document.getElementById('copyKode');
  if (! btn) return;
  btn.addEventListener('click', () => {
    const kode = document.getElementById('kodeBooking').textContent.trim();
    navigator.clipboard.writeText(kode).then(() => {
      btn.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
      setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i> Salin kode'; }, 2000);
    });
  });
})();
</script>

<?= $this->endSection() ?>
