<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Daftar layanan</span>
  <h1 class="h2 mb-2">Pilih layanan yang sesuai</h1>
  <p class="small-muted mb-0">Semua layanan mengikuti data aktif dari sistem, lengkap dengan harga dan durasi pengerjaan.</p>
</div>

<div class="row g-3">
  <?php foreach ($services as $s): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card salon-card h-100">
        <div class="card-body d-flex flex-column">
          <span class="gold-badge badge rounded-pill align-self-start mb-3"><?= esc($s['category']) ?></span>
          <h5 class="fw-bold mb-2"><?= esc($s['name']) ?></h5>
          <p class="small-muted flex-grow-1"><?= esc($s['description']) ?></p>
          <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <div>
              <div class="small-muted">Harga</div>
              <div class="service-price">Rp<?= number_format($s['price'], 0, ',', '.') ?></div>
            </div>
            <div class="text-end">
              <div class="small-muted">Durasi</div>
              <strong><?= esc($s['duration_minutes']) ?> menit</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<?= $this->endSection() ?>
