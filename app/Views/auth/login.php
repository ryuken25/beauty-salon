<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center py-lg-4">
  <div class="col-md-7 col-lg-5">
    <div class="form-card">
      <div class="text-center mb-4">
        <span class="brand-mark mx-auto mb-3">SW</span>
        <h1 class="h3 mb-2">Selamat datang kembali</h1>
        <p class="small-muted mb-0">Masuk untuk mengelola booking atau melihat riwayat layanan.</p>
      </div>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" value="<?= old('email') ?>" autocomplete="email">
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" autocomplete="current-password">
        </div>
        <button class="btn btn-primary w-100" type="submit">Login</button>
      </form>
      <p class="small-muted text-center mt-3 mb-0">Belum punya akun? <a href="<?= base_url('register') ?>">Daftar pelanggan</a>.</p>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
