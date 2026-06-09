<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div class="mb-2">
  <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('layanan') ?>"><i class="bi bi-arrow-left"></i> Semua layanan</a>
</div>

<div class="row-salon cols-2" style="gap:1.5rem;">
  <div>
    <?php if (! empty($gambar)): ?>
      <div id="galeriCarousel" class="carousel slide layanan-gallery" data-bs-ride="false">
        <?php if (count($gambar) > 1): ?>
          <div class="carousel-indicators">
            <?php foreach ($gambar as $i => $g): ?>
              <button type="button" data-bs-target="#galeriCarousel" data-bs-slide-to="<?= $i ?>" <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach ?>
          </div>
        <?php endif ?>
        <div class="carousel-inner">
          <?php foreach ($gambar as $i => $g): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
              <img src="<?= base_url($g) ?>" alt="<?= esc($layanan['nama']) ?>">
            </div>
          <?php endforeach ?>
        </div>
        <?php if (count($gambar) > 1): ?>
          <button class="carousel-control-prev" type="button" data-bs-target="#galeriCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sebelumnya</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#galeriCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Berikutnya</span>
          </button>
        <?php endif ?>
      </div>
    <?php else: ?>
      <div class="layanan-gallery layanan-gallery--fallback">
        <i class="bi <?= esc($layanan['ikon'] ?? 'bi-flower1') ?>"></i>
      </div>
    <?php endif ?>
  </div>

  <div>
    <div class="caption" style="letter-spacing:2px; text-transform:uppercase; color:var(--gold);"><?= esc($layanan['kategori']) ?> · <?= esc($layanan['durasi_menit']) ?> menit</div>
    <h1 class="h1" style="margin-top:0.2rem;"><?= esc($layanan['nama']) ?></h1>

    <?php if ($is_promo): ?>
      <div class="card-salon card-salon--promo mb-2" style="padding:0.75rem 1rem;">
        <div class="flex items-center gap-2" style="flex-wrap:wrap;">
          <span class="badge-promo">Promo <?= (int) $layanan['promo_persen'] ?>%</span>
          <div>
            <span class="price-strike">Rp <?= number_format((int) $layanan['harga'], 0, ',', '.') ?></span>
            <span class="h2" style="color:var(--gold); margin:0;">Rp <?= number_format($harga_final, 0, ',', '.') ?></span>
          </div>
        </div>
        <?php if (! empty($layanan['promo_deskripsi'])): ?>
          <div class="caption mt-1" style="color:var(--promo);"><i class="bi bi-tag"></i> <?= esc($layanan['promo_deskripsi']) ?></div>
        <?php endif ?>
        <?php $range = \App\Models\LayananModel::promoRange($layanan); ?>
        <?php if ($range): ?>
          <div class="caption mt-1" style="color:var(--text-secondary);"><i class="bi bi-calendar-range"></i> Berlaku <?= esc($range) ?></div>
        <?php endif ?>
      </div>
    <?php else: ?>
      <div class="h2" style="color:var(--gold);">Rp <?= number_format((int) $layanan['harga'], 0, ',', '.') ?></div>
    <?php endif ?>

    <?php if (! empty($layanan['deskripsi'])): ?>
      <div class="mt-2" style="line-height:1.7; color:var(--text-primary);"><?= nl2br(esc($layanan['deskripsi'])) ?></div>
    <?php endif ?>

    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>

    <?php if ($is_logged_in): ?>
      <a class="btn-salon-primary btn-salon--full" href="<?= base_url('booking?layanan_id=' . (int) $layanan['id']) ?>">
        <i class="bi bi-calendar-check"></i> Pilih jam &amp; booking →
      </a>
    <?php else: ?>
      <a class="btn-salon-primary btn-salon--full" href="<?= base_url('login') ?>">
        <i class="bi bi-box-arrow-in-right"></i> Masuk dulu untuk booking
      </a>
      <div class="caption mt-1" style="text-align:center;">Belum punya akun? <a href="<?= base_url('register') ?>">Daftar di sini</a>.</div>
    <?php endif ?>
  </div>
</div>

<?= $this->endSection() ?>
