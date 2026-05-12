<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card-salon text-center">
  <div class="display" style="font-size:1rem; letter-spacing:4px;">SW BEAUTY SALON</div>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
  <div class="h1 mb-1">Login admin</div>
  <div class="tagline mb-2">Akses panel pengelolaan salon</div>

  <form method="post" action="<?= base_url('admin/login') ?>" class="text-left mt-2">
    <?= csrf_field() ?>
    <label class="form-salon-label">Email</label>
    <input class="form-salon-input mb-2" type="email" name="email" required value="<?= esc(old('email')) ?>" autocomplete="username">
    <label class="form-salon-label">Password</label>
    <input class="form-salon-input mb-3" type="password" name="password" required autocomplete="current-password">
    <button class="btn-salon-primary btn-salon--full" type="submit">Masuk</button>
  </form>

  <div class="caption mt-2"><a href="<?= base_url('/') ?>">← Kembali ke beranda</a></div>
</div>

<?= $this->endSection() ?>
