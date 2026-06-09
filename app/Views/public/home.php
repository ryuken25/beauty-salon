<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<!-- ── Editorial Hero ─────────────────────────────────────── -->
<section class="hero-editorial" style="margin: -1.714rem -2rem 0; padding: 3.5rem 2rem 4rem;">
  <div class="hero-editorial__grid" style="max-width:1200px; margin:0 auto;">

    <!-- Left: copy + CTAs -->
    <div>
      <p class="hero-editorial__eyebrow"><i class="bi bi-gem" style="margin-right:0.4rem;"></i>KECANTIKAN · TABANAN · BALI</p>
      <h1 class="hero-editorial__title">
        Ritual perawatan<br><em>terbaik untuk Anda</em>
      </h1>
      <p class="hero-editorial__lead">
        Salon premium dengan tangan-tangan terampil dan layanan personal. Reservasi mudah, hasilnya luar biasa.
      </p>
      <div class="hero-editorial__ctas">
        <a class="btn-salon-primary" href="<?= base_url('booking') ?>">Booking sekarang</a>
        <a class="btn-salon-secondary" href="<?= base_url('layanan') ?>">Lihat layanan</a>
      </div>
      <div class="mt-2">
        <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('cek-booking') ?>" style="padding-left:0;">
          <i class="bi bi-search"></i> Cek atau Batalkan Booking
        </a>
      </div>
      <div class="ornament-rule" style="margin:2rem 0 0; justify-content:flex-start;">
        <span class="ornament-rule__line" style="flex:0 0 40px;"></span>
        <i class="bi bi-stars ornament-rule__icon"></i>
        <span style="font-size:0.7143rem; letter-spacing:2px; color:var(--text-muted); text-transform:uppercase;">Dipercaya pelanggan Tabanan</span>
      </div>
    </div>

    <!-- Right: image grid -->
    <div class="hero-editorial__images">
      <!-- Large top tile: logo -->
      <div class="hero-img hero-img--large">
        <img src="<?= base_url('assets/img/logo.png') ?>" alt="SW Beauty Salon">
      </div>
      <!-- Bottom-left: decorative photo placeholder -->
      <div class="hero-img hero-img--photo">
        <i class="bi bi-scissors" style="color:var(--gold-border);"></i>
      </div>
      <!-- Bottom-right: stat card (solid gold pop) -->
      <div class="hero-img--stat">
        <span class="stat-num">500+</span>
        <span class="stat-label">Pelanggan<br>puas</span>
      </div>
    </div>

  </div>
</section>

<!-- ── Layanan Unggulan ────────────────────────────────────── -->
<section class="section-header">
  <div class="h2">Layanan unggulan</div>
  <span class="tagline">Pilihan terbaik para tamu kami</span>
</section>

<div class="row-salon cols-3">
  <?php foreach ($services as $s):
    $cover = \App\Models\LayananModel::cover($s);
    $isPromo = \App\Models\LayananModel::isPromo($s);
    $hargaFinal = \App\Models\LayananModel::hargaFinal($s);
  ?>
    <a class="card-salon <?= $isPromo ? 'card-salon--promo' : '' ?>" href="<?= base_url('layanan/' . (int) $s['id']) ?>" style="text-decoration:none; color:inherit; display:block;">
      <?php if ($cover): ?>
        <img class="layanan-cover" src="<?= base_url($cover) ?>" alt="<?= esc($s['nama']) ?>">
      <?php else: ?>
        <div class="service-icon mb-2"><i class="bi <?= esc($s['ikon'] ?: 'bi-stars') ?>"></i></div>
      <?php endif ?>
      <?php if ($isPromo): ?>
        <span class="badge-promo mb-1"><i class="bi bi-tag"></i> Promo <?= (int) $s['promo_persen'] ?>%</span>
      <?php endif ?>
      <div class="h3"><?= esc($s['nama']) ?></div>
      <div class="tagline mb-2"><?= esc($s['kategori']) ?></div>
      <div class="flex justify-between items-center">
        <span class="caption"><?= esc($s['durasi_menit']) ?> menit</span>
        <span class="label" style="font-size:0.9286rem; letter-spacing:0;">
          <?php if ($isPromo): ?>
            <span class="price-strike">Rp <?= number_format((int) $s['harga'], 0, ',', '.') ?></span>
            <span style="color:var(--gold);">Rp <?= number_format($hargaFinal, 0, ',', '.') ?></span>
          <?php else: ?>
            Rp <?= number_format((int) $s['harga'], 0, ',', '.') ?>
          <?php endif ?>
        </span>
      </div>
    </a>
  <?php endforeach ?>
</div>

<div class="text-center mt-3">
  <a class="btn-salon-secondary" href="<?= base_url('layanan') ?>">Lihat semua layanan</a>
</div>

<!-- ── Why Us ──────────────────────────────────────────────── -->
<section class="section-header">
  <div class="h2">Mengapa memilih kami</div>
  <span class="tagline">Pelayanan yang dipikirkan secara matang</span>
</section>

<div class="row-salon cols-3">
  <div class="card-salon text-center">
    <i class="bi bi-clock-history" style="font-size:1.5rem; color:var(--gold); margin-bottom:0.5rem;"></i>
    <div class="h3 mt-1">Booking fleksibel</div>
    <div class="tagline">Pilih jam yang pas untuk Anda, kapan saja</div>
  </div>
  <!-- Solid gold pop card -->
  <div class="card-stat-gold text-center">
    <i class="bi bi-award" style="font-size:1.5rem; color:#14110F; margin-bottom:0.5rem;"></i>
    <div class="h3 mt-1" style="color:#14110F;">Owner langsung</div>
    <div style="font-family:var(--font-display); font-style:italic; font-size:0.9286rem; color:rgba(20,17,15,0.65);">Dilayani langsung oleh pemilik</div>
  </div>
  <div class="card-salon text-center">
    <i class="bi bi-heart" style="font-size:1.5rem; color:var(--gold); margin-bottom:0.5rem;"></i>
    <div class="h3 mt-1">Layanan personal</div>
    <div class="tagline">Sesuai kebutuhan dan keinginan Anda</div>
  </div>
</div>

<?= $this->endSection() ?>
