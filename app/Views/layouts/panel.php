<?php
$uri = trim(service('uri')->getPath(), '/');
$role = session('user_role');
$isPemilik = $role === 'pemilik';

if (! function_exists('panel_active')) {
    /** Active when the current URI starts with $seg — but Booking, Walk-in
     *  and Jadwal must not bleed into each other. */
    function panel_active(string $seg, string $uri): string
    {
        if ($seg === 'admin/booking') {
            $isSub = str_starts_with($uri, 'admin/booking/walkin') || str_starts_with($uri, 'admin/booking/jadwal');
            return (str_starts_with($uri, 'admin/booking') && ! $isSub) ? 'active' : '';
        }
        return ($uri === $seg || str_starts_with($uri, $seg . '/')) ? 'active' : '';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Panel · SW Beauty Salon') ?></title>
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
    <div class="sidebar-admin__brand"><i class="bi bi-gem"></i> <span>SW · <?= $isPemilik ? 'PEMILIK' : 'ADMIN' ?></span></div>

    <!-- Operasional — admin & pemilik -->
    <a class="sidebar-admin__item <?= panel_active('admin/dashboard', $uri) ?>" href="<?= base_url('admin/dashboard') ?>"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
    <a class="sidebar-admin__item <?= panel_active('admin/booking', $uri) ?>" href="<?= base_url('admin/booking') ?>"><i class="bi bi-calendar2-check"></i><span>Booking</span></a>
    <a class="sidebar-admin__item <?= str_starts_with($uri, 'admin/booking/walkin') ? 'active' : '' ?>" href="<?= base_url('admin/booking/walkin') ?>"><i class="bi bi-person-plus"></i><span>Walk-in</span></a>
    <a class="sidebar-admin__item <?= str_starts_with($uri, 'admin/booking/jadwal') ? 'active' : '' ?>" href="<?= base_url('admin/booking/jadwal') ?>"><i class="bi bi-calendar3"></i><span>Jadwal</span></a>
    <a class="sidebar-admin__item <?= panel_active('admin/pelanggan', $uri) ?>" href="<?= base_url('admin/pelanggan') ?>"><i class="bi bi-people"></i><span>Pelanggan</span></a>
    <a class="sidebar-admin__item <?= panel_active('admin/transaksi', $uri) ?>" href="<?= base_url('admin/transaksi') ?>"><i class="bi bi-cash-coin"></i><span>Transaksi</span></a>
    <a class="sidebar-admin__item <?= panel_active('admin/pengaturan', $uri) ?>" href="<?= base_url('admin/pengaturan') ?>"><i class="bi bi-gear"></i><span>Pengaturan</span></a>

    <?php if ($isPemilik): ?>
      <div class="sidebar-admin__group">Manajerial</div>
      <a class="sidebar-admin__item <?= panel_active('owner/laporan', $uri) ?>" href="<?= base_url('owner/laporan') ?>"><i class="bi bi-bar-chart-line"></i><span>Laporan</span></a>
      <a class="sidebar-admin__item <?= panel_active('owner/layanan', $uri) ?>" href="<?= base_url('owner/layanan') ?>"><i class="bi bi-scissors"></i><span>Layanan</span></a>
    <?php endif ?>

    <div class="sidebar-admin__spacer"></div>

    <form method="post" action="<?= base_url('logout') ?>" style="padding: 0 0.5rem;">
      <?= csrf_field() ?>
      <button class="sidebar-admin__item" type="submit" style="background:none; border:none; width:100%; text-align:left;"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
    </form>
    <div class="sidebar-admin__footer"><?= esc(session('user_nama') ?? '') ?> · <span style="color:var(--gold);"><?= esc(ucfirst((string) $role)) ?></span></div>
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
