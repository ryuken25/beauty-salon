<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<?php
$alreadyLoggedIn = (bool) session('is_logged_in');
$currentNama = session('user_nama');
$currentRole = session('user_role');
$roleLabel = ['admin' => 'Admin', 'pemilik' => 'Pemilik'][$currentRole] ?? '';
$dashUrl = in_array($currentRole, ['admin', 'pemilik'], true) ? base_url('admin/dashboard') : base_url('/');
?>
<div class="card-salon" style="text-align:center; background:var(--card);">
  <img src="<?= base_url('assets/img/logo.png') ?>" alt="SW Beauty Salon" style="width:88px;height:88px;object-fit:contain;margin-bottom:0.857rem;">
  <div class="h2" style="margin-bottom:0.25rem; font-size:1.571rem;">Masuk</div>
  <div class="tagline mb-3">Khusus Admin &amp; Pemilik</div>

  <?php if ($alreadyLoggedIn): ?>
    <div class="alert-salon alert-salon--info" style="text-align:left;">
      <div style="font-weight:600; margin-bottom:0.25rem;">
        <i class="bi bi-person-check"></i> Anda sudah login sebagai <?= esc($currentNama) ?>
        <span style="color:var(--gold);">· <?= esc($roleLabel) ?></span>
      </div>
      <div class="caption">Untuk login dengan akun lain, klik <strong>Logout</strong> dulu. Atau lanjut ke dashboard Anda.</div>
      <div class="flex gap-1 mt-2" style="flex-wrap:wrap;">
        <a class="btn-salon-secondary btn-salon--sm" href="<?= esc($dashUrl) ?>">&rarr; Ke dashboard saya</a>
        <a class="btn-salon-danger btn-salon--sm" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>
  <?php endif ?>

  <form method="post" action="<?= base_url('login') ?>" style="text-align:left;">
    <?= csrf_field() ?>
    <div class="mb-2">
      <label class="form-salon-label">Email</label>
      <input class="form-salon-input" type="email" name="email" required <?= $alreadyLoggedIn ? '' : 'autofocus' ?> value="<?= esc(old('email')) ?>" placeholder="nama@email.com">
    </div>
    <div class="mb-2">
      <label class="form-salon-label">Password</label>
      <input class="form-salon-input" type="password" name="password" required>
    </div>
    <button class="btn-salon-primary btn-salon--full" type="submit">
      <?= $alreadyLoggedIn ? 'Masuk dengan akun ini' : 'Masuk' ?>
    </button>
  </form>

  <div class="mt-2 caption">
    <a href="<?= base_url('/') ?>">&larr; Kembali ke beranda</a>
  </div>
</div>
<?= $this->endSection() ?>
