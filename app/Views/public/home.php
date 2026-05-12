<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<section class="hero-onyx">
  <div class="ornament-rule ornament-rule--wide"><span class="ornament-rule__line"></span><i class="bi bi-stars ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
  <div class="display">SW BEAUTY SALON</div>
  <div class="tagline">Reserve · Refine · Revel</div>
  <p class="lead">Perawatan kecantikan kelas atas dengan sentuhan personal di Tabanan, Bali.</p>
  <a class="btn-salon-secondary" style="background:var(--color-ivory); color:var(--color-onyx); border-color:var(--color-ivory);" href="<?= base_url('booking') ?>">Booking sekarang</a>
  <div class="ornament-rule ornament-rule--wide mt-3"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<section class="section-header">
  <div class="h2">Layanan unggulan</div>
  <span class="tagline">Pilihan terbaik para tamu kami</span>
</section>

<div class="row-salon cols-3">
  <?php foreach ($services as $s): ?>
    <div class="card-salon">
      <div class="service-icon mb-2"><i class="bi <?= esc($s['ikon'] ?: 'bi-stars') ?>"></i></div>
      <div class="h3"><?= esc($s['nama']) ?></div>
      <div class="tagline mb-2"><?= esc($s['kategori']) ?></div>
      <div class="flex justify-between items-center">
        <span class="caption"><?= esc($s['durasi_menit']) ?> menit</span>
        <span class="h3">Rp <?= number_format((int) $s['harga'], 0, ',', '.') ?></span>
      </div>
    </div>
  <?php endforeach ?>
</div>

<div class="text-center mt-3">
  <a class="btn-salon-secondary" href="<?= base_url('layanan') ?>">Lihat semua layanan</a>
</div>

<section class="section-header">
  <div class="h2">Mengapa memilih kami</div>
  <span class="tagline">Pelayanan yang dipikirkan secara matang</span>
</section>

<div class="row-salon cols-3">
  <div class="card-salon text-center">
    <i class="bi bi-clock-history" style="font-size:1.5rem; color:var(--color-gold);"></i>
    <div class="h3 mt-1">Booking fleksibel</div>
    <div class="tagline">Pilih jam yang pas untuk Anda</div>
  </div>
  <div class="card-salon text-center">
    <i class="bi bi-award" style="font-size:1.5rem; color:var(--color-gold);"></i>
    <div class="h3 mt-1">Stylist tersertifikasi</div>
    <div class="tagline">Profesional terbaik kami</div>
  </div>
  <div class="card-salon text-center">
    <i class="bi bi-heart" style="font-size:1.5rem; color:var(--color-gold);"></i>
    <div class="h3 mt-1">Layanan personal</div>
    <div class="tagline">Sesuai kebutuhan Anda</div>
  </div>
</div>

<?= $this->endSection() ?>
