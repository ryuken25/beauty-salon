<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<div class="card-salon" style="background:var(--card);">
  <div class="text-center">
    <img src="<?= base_url('assets/img/logo.png') ?>" alt="SW Beauty Salon" style="width:80px;height:80px;object-fit:contain;margin-bottom:0.5rem;">
    <div class="h2" style="margin-bottom:0.25rem;">Daftar Akun Pelanggan</div>
    <div class="tagline mb-2">Booking lebih cepat dengan akun</div>
  </div>

  <form method="post" action="<?= base_url('register') ?>">
    <?= csrf_field() ?>
    <div class="mb-2">
      <label class="form-salon-label">Nama lengkap</label>
      <input class="form-salon-input" type="text" name="nama" required minlength="3" value="<?= esc(old('nama')) ?>">
    </div>
    <div class="mb-2">
      <label class="form-salon-label">Nomor WhatsApp</label>
      <input class="form-salon-input" type="tel" name="nomor_hp" required value="<?= esc(old('nomor_hp')) ?>" placeholder="08xxxxxxxxxx">
      <div class="form-salon-help">Nomor ini akan dipakai untuk login dan kontak booking.</div>
    </div>
    <div class="mb-2">
      <label class="form-salon-label">Email</label>
      <input class="form-salon-input" type="email" name="email" required value="<?= esc(old('email')) ?>" placeholder="nama@gmail.com">
      <div class="form-salon-help">Konfirmasi &amp; pengingat booking dikirim ke email ini.</div>
    </div>
    <div class="mb-2">
      <label class="form-salon-label">Password (min 8 karakter)</label>
      <input class="form-salon-input" type="password" name="password" required minlength="8">
    </div>
    <div class="mb-2">
      <label class="form-salon-label">Ulangi password</label>
      <input class="form-salon-input" type="password" name="password_confirm" required minlength="8">
    </div>
    <button class="btn-salon-primary btn-salon--full" type="submit">Buat akun</button>
  </form>

  <div class="mt-2 caption text-center">
    Sudah punya akun? <a href="<?= base_url('login') ?>">Masuk di sini</a>
  </div>
</div>
<?= $this->endSection() ?>
