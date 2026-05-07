<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="hero-section mb-4">
  <div class="hero-card row align-items-center g-4">
    <div class="col-lg-7">
      <span class="gold-badge badge rounded-pill mb-3">Salon lokal yang hangat dan terpercaya</span>
      <h1 class="display-5 fw-bold mb-3">SW Beauty Salon</h1>
      <p class="lead mb-4">Layanan salon lokal yang rapi, nyaman, dan mudah dibooking. Pilih layanan, stylist, dan slot waktu yang jelas tanpa perlu menunggu lama.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary btn-lg" href="<?= base_url('pelanggan/booking/baru') ?>">Booking Sekarang</a>
        <a class="btn btn-outline-primary btn-lg" href="<?= base_url('layanan') ?>">Lihat Layanan</a>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="soft-card">
        <h5 class="section-title mb-3">Kenapa booking di sini?</h5>
        <div class="d-grid gap-2">
          <div class="feature-pill"><span class="feature-dot"></span><span>Booking mudah</span></div>
          <div class="feature-pill"><span class="feature-dot"></span><span>Slot waktu jelas</span></div>
          <div class="feature-pill"><span class="feature-dot"></span><span>Konfirmasi WhatsApp</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
  <div>
    <h2 class="section-title h3 mb-1">Layanan Populer</h2>
    <p class="small-muted mb-0">Pilihan layanan salon yang sering dibooking pelanggan.</p>
  </div>
  <a class="btn btn-outline-primary" href="<?= base_url('layanan') ?>">Semua Layanan</a>
</div>

<div class="row g-3">
  <?php foreach ($services as $s): ?>
    <div class="col-md-4">
      <div class="card salon-card h-100">
        <div class="card-body">
          <span class="gold-badge badge rounded-pill mb-3"><?= esc($s['category']) ?></span>
          <h5 class="fw-bold mb-2"><?= esc($s['name']) ?></h5>
          <p class="small-muted mb-3"><?= esc($s['description']) ?></p>
          <div class="d-flex justify-content-between align-items-center gap-2">
            <span class="service-price">Rp<?= number_format($s['price'], 0, ',', '.') ?></span>
            <span class="small-muted"><?= esc($s['duration_minutes']) ?> menit</span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<?= $this->endSection() ?>
