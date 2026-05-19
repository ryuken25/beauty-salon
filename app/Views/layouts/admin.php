<?php
$uri = trim(service('uri')->getPath(), '/');
$role = session('user_role');
function active_admin(string $segment, string $uri): string {
    if ($segment === 'dashboard') return $uri === 'admin/dashboard' || $uri === 'admin' ? 'active' : '';
    return str_starts_with($uri, 'admin/' . $segment) ? 'active' : '';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Admin · SW Beauty Salon') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;1,400&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-admin">
  <aside class="sidebar-admin">
    <div class="sidebar-admin__brand"><i class="bi bi-gem"></i> <span>SW · ADMIN</span></div>

    <a class="sidebar-admin__item <?= active_admin('dashboard', $uri) ?>" href="<?= base_url('admin/dashboard') ?>"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
    <a class="sidebar-admin__item <?= active_admin('booking', $uri) && ! str_starts_with($uri, 'admin/booking/walkin') && ! str_starts_with($uri, 'admin/booking/jadwal') ? 'active' : '' ?>" href="<?= base_url('admin/booking') ?>"><i class="bi bi-calendar2-check"></i><span>Booking</span></a>
    <a class="sidebar-admin__item <?= str_starts_with($uri, 'admin/booking/walkin') ? 'active' : '' ?>" href="<?= base_url('admin/booking/walkin') ?>"><i class="bi bi-person-plus"></i><span>Walk-in</span></a>
    <a class="sidebar-admin__item <?= str_starts_with($uri, 'admin/booking/jadwal') ? 'active' : '' ?>" href="<?= base_url('admin/booking/jadwal') ?>"><i class="bi bi-calendar3"></i><span>Jadwal</span></a>
    <a class="sidebar-admin__item <?= active_admin('pelanggan', $uri) ?>" href="<?= base_url('admin/pelanggan') ?>"><i class="bi bi-people"></i><span>Pelanggan</span></a>

    <div class="sidebar-admin__spacer"></div>

    <?php if ($role === 'pemilik'): ?>
      <a class="sidebar-admin__item" href="<?= base_url('owner/dashboard') ?>" style="border:1px dashed var(--gold-border);">
        <i class="bi bi-bar-chart-line"></i><span>Panel Pemilik →</span>
      </a>
    <?php endif ?>
    <form method="post" action="<?= base_url('admin/logout') ?>" style="padding: 0 0.5rem;">
      <?= csrf_field() ?>
      <button class="sidebar-admin__item" type="submit" style="background:none; border:none; width:100%; text-align:left;"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
    </form>
    <div class="sidebar-admin__footer"><?= esc(session('user_nama') ?? '') ?> · <span style="color:var(--gold);"><?= esc(ucfirst($role)) ?></span></div>
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
