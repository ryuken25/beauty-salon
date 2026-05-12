<?php $uriPath = trim(service('uri')->getPath(), '/'); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'SW Beauty Salon') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,500&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="nav-salon">
  <a class="nav-salon__brand" href="<?= base_url('/') ?>">
    <span class="nav-salon__wordmark">SW BEAUTY SALON</span>
    <span class="nav-salon__tagline">Reserve · Refine · Revel</span>
  </a>
  <div class="nav-salon__links">
    <a class="nav-salon__link <?= $uriPath === '' ? 'active' : '' ?>" href="<?= base_url('/') ?>">Beranda</a>
    <a class="nav-salon__link <?= $uriPath === 'layanan' ? 'active' : '' ?>" href="<?= base_url('layanan') ?>">Layanan</a>
    <a class="nav-salon__link <?= str_starts_with($uriPath, 'booking') && $uriPath !== 'cek-booking' ? 'active' : '' ?>" href="<?= base_url('booking') ?>">Booking</a>
    <a class="nav-salon__link <?= $uriPath === 'cek-booking' ? 'active' : '' ?>" href="<?= base_url('cek-booking') ?>">Cek Booking</a>
    <a class="btn-salon-primary" href="<?= base_url('booking') ?>">Booking sekarang</a>
  </div>
</nav>

<main class="container-salon" style="padding-top: 1.5rem; padding-bottom: 3rem;">
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert-salon alert-salon--success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert-salon alert-salon--error"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif ?>
  <?= $this->renderSection('content') ?>
</main>

<footer class="footer-salon">
  <div class="container-salon">
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div><i class="bi bi-geo-alt"></i> Batunya, Baturiti, Tabanan, Bali · <i class="bi bi-telephone"></i> +62 878-6218-3074</div>
    <div class="tagline mt-1">Crafted with care · © <?= date('Y') ?> SW Beauty Salon</div>
  </div>
</footer>
</body>
</html>
