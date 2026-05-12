<?php $uriPath = trim(service('uri')->getPath(), '/'); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'SW Beauty Salon') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-shell">
  <nav class="navbar navbar-expand-lg navbar-dark navbar-salon">
    <div class="container">
      <a class="navbar-brand brand-wrap" href="<?= base_url('/') ?>">
        <span class="brand-mark">SW</span>
        <span><span class="fw-bold">SW Beauty Salon</span><span class="brand-subtext">Booking Salon</span></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <li class="nav-item"><a class="nav-link <?= $uriPath === 'layanan' ? 'active' : '' ?>" href="<?= base_url('layanan') ?>">Layanan</a></li>
          <li class="nav-item"><a class="nav-link <?= $uriPath === 'pelanggan/booking/baru' ? 'active' : '' ?>" href="<?= base_url('pelanggan/booking/baru') ?>">Booking</a></li>
          <li class="nav-item"><a class="nav-link <?= $uriPath === 'pelanggan/booking/cek' ? 'active' : '' ?>" href="<?= base_url('pelanggan/booking/cek') ?>">Cek Booking</a></li>
          <?php if (session('user_id') && in_array(session('role'), ['admin', 'owner'], true)): ?>
            <li class="nav-item"><a class="nav-link <?= str_starts_with($uriPath, 'admin') ? 'active' : '' ?>" href="<?= base_url('admin') ?>"><?= session('role') === 'owner' ? 'Pemilik' : 'Admin' ?></a></li>
            <li class="nav-item"><form method="post" action="<?= base_url('logout') ?>"><?= csrf_field() ?><button class="btn btn-link nav-link" type="submit">Logout</button></form></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link <?= str_starts_with($uriPath, 'admin') || $uriPath === 'login' ? 'active' : '' ?>" href="<?= base_url('admin') ?>">Admin</a></li>
          <?php endif ?>
        </ul>
      </div>
    </div>
  </nav>
  <main class="app-main container py-4">
    <?php foreach (['success' => 'success', 'error' => 'danger'] as $k => $c): if (session($k)): ?>
      <div class="alert alert-<?= $c ?>"><?= esc(session($k)) ?></div>
    <?php endif; endforeach ?>
    <?= $this->renderSection('content') ?>
  </main>
  <footer class="footer-salon py-3">
    <div class="container small">© <?= date('Y') ?> SW Beauty Salon. Booking mudah, pelayanan rapi.</div>
  </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>function salinPesan(id){navigator.clipboard.writeText(document.getElementById(id).value).then(()=>alert('Pesan berhasil disalin.'))}</script>
</body>
</html>
