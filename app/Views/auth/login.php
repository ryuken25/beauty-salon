<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<div class="card-salon" style="text-align:center; background:var(--card);">
  <img src="<?= base_url('assets/img/logo.png') ?>" alt="SW Beauty Salon" style="width:88px;height:88px;object-fit:contain;margin-bottom:0.857rem;">
  <div class="h2" style="margin-bottom:0.25rem; font-size:1.571rem;">Selamat datang</div>
  <div class="tagline mb-3">Admin · Pemilik · Pelanggan</div>

  <form method="post" action="<?= base_url('login') ?>" style="text-align:left;">
    <?= csrf_field() ?>
    <div class="mb-2">
      <label class="form-salon-label">Email</label>
      <input class="form-salon-input" type="email" name="email" required autofocus value="<?= esc(old('email')) ?>" placeholder="nama@email.com">
    </div>
    <div class="mb-2">
      <label class="form-salon-label">Password</label>
      <input class="form-salon-input" type="password" name="password" required>
    </div>
    <button class="btn-salon-primary btn-salon--full" type="submit">Masuk</button>
  </form>

  <div class="mt-2 caption">
    Belum punya akun? <a href="<?= base_url('register') ?>">Daftar sebagai pelanggan</a>
  </div>
  <div class="mt-1 caption">
    <a href="<?= base_url('/') ?>">&larr; Kembali ke beranda</a>
  </div>
</div>
<?= $this->endSection() ?>
