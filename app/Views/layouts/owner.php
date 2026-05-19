<?php
$uri = trim(service('uri')->getPath(), '/');
if (! function_exists('active_owner')) {
    function active_owner(string $segment, string $uri): string {
        if ($segment === 'dashboard') return $uri === 'owner/dashboard' || $uri === 'owner' ? 'active' : '';
        return str_starts_with($uri, 'owner/' . $segment) ? 'active' : '';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Pemilik · SW Beauty Salon') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;1,400&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-admin">
  <aside class="sidebar-admin">
    <div class="sidebar-admin__brand"><i class="bi bi-gem"></i> <span>SW · PEMILIK</span></div>

    <a class="sidebar-admin__item <?= active_owner('dashboard', $uri) ?>" href="<?= base_url('owner/dashboard') ?>"><i class="bi bi-bar-chart-line"></i><span>Dashboard</span></a>
    <a class="sidebar-admin__item <?= active_owner('layanan', $uri) ?>" href="<?= base_url('owner/layanan') ?>"><i class="bi bi-scissors"></i><span>Layanan</span></a>
    <a class="sidebar-admin__item <?= active_owner('transaksi', $uri) ?>" href="<?= base_url('owner/transaksi') ?>"><i class="bi bi-cash-coin"></i><span>Transaksi</span></a>
    <a class="sidebar-admin__item <?= active_owner('pengaturan', $uri) ?>" href="<?= base_url('owner/pengaturan') ?>"><i class="bi bi-gear"></i><span>Pengaturan</span></a>

    <div class="sidebar-admin__spacer"></div>

    <a class="sidebar-admin__item" href="<?= base_url('admin/dashboard') ?>" style="border:1px dashed var(--gold-border);">
      <i class="bi bi-calendar2-check"></i><span>Panel Admin →</span>
    </a>
    <form method="post" action="<?= base_url('logout') ?>" style="padding: 0 0.5rem;">
      <?= csrf_field() ?>
      <button class="sidebar-admin__item" type="submit" style="background:none; border:none; width:100%; text-align:left;"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
    </form>
    <div class="sidebar-admin__footer"><?= esc(session('user_nama') ?? '') ?> · <span style="color:var(--gold);">Pemilik</span></div>
  </aside>

  <main class="admin-main">
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert-salon alert-salon--success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert-salon alert-salon--error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif ?>
    <?= $this->renderSection('content') ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
