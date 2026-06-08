<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<?php
$alreadyLoggedIn = (bool) session('is_logged_in');
$currentNama = session('user_nama');
$currentRole = session('user_role');
$roleLabel = ['admin' => 'Admin', 'pemilik' => 'Pemilik', 'pelanggan' => 'Pelanggan'][$currentRole] ?? '';
$dashUrl = in_array($currentRole, ['admin', 'pemilik'], true) ? base_url('admin/dashboard') : base_url('pelanggan/dashboard');
?>
<div class="card-salon" style="text-align:center; background:var(--card);">
  <img src="<?= base_url('assets/img/logo.png') ?>" alt="SW Beauty Salon" style="width:88px;height:88px;object-fit:contain;margin-bottom:0.857rem;">
  <div class="h2" style="margin-bottom:0.25rem; font-size:1.571rem;">Masuk</div>
  <div class="tagline mb-3">Pelanggan & staff — satu pintu masuk</div>

  <?php if ($alreadyLoggedIn): ?>
    <div class="alert-salon alert-salon--info" style="text-align:left;">
      <div style="font-weight:600; margin-bottom:0.25rem;">
        <i class="bi bi-person-check"></i> Anda sudah login sebagai <?= esc($currentNama) ?>
        <?php if ($roleLabel !== ''): ?><span style="color:var(--gold);">· <?= esc($roleLabel) ?></span><?php endif ?>
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
      <label class="form-salon-label">Email atau Nomor WhatsApp</label>
      <input class="form-salon-input" type="text" name="identifier" required <?= $alreadyLoggedIn ? '' : 'autofocus' ?> value="<?= esc(old('identifier')) ?>" placeholder="nama@email.com atau 08xxxxxxxxxx">
      <div class="caption mt-1" style="color:var(--text-secondary);">Pelanggan pakai nomor WhatsApp · staff/pemilik pakai email.</div>
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
    Belum punya akun? <a href="<?= base_url('register') ?>">Daftar di sini</a>
  </div>
  <div class="mt-1 caption">
    <a href="<?= base_url('lupa-password') ?>">Lupa password?</a>
  </div>
  <div class="mt-1 caption">
    <a href="<?= base_url('/') ?>">&larr; Kembali ke beranda</a>
  </div>
</div>
<?= $this->endSection() ?>
