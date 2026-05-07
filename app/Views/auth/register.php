<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center py-lg-4">
  <div class="col-lg-7">
    <div class="form-card">
      <div class="text-center mb-4">
        <span class="gold-badge badge rounded-pill mb-2">Akun pelanggan</span>
        <h1 class="h3 mb-2">Daftar Booking Salon</h1>
        <p class="small-muted mb-0">Isi data singkat agar admin mudah menghubungi lewat WhatsApp.</p>
      </div>
      <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input class="form-control" name="name" value="<?= old('name') ?>" autocomplete="name">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" value="<?= old('email') ?>" autocomplete="email">
          </div>
          <div class="col-md-6">
            <label class="form-label">No. WhatsApp</label>
            <input class="form-control" name="phone" value="<?= old('phone') ?>" autocomplete="tel">
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" autocomplete="new-password">
          </div>
          <div class="col-12">
            <label class="form-label">Alamat</label>
            <textarea class="form-control" name="address" rows="3"><?= old('address') ?></textarea>
          </div>
        </div>
        <button class="btn btn-primary w-100 mt-4" type="submit">Daftar</button>
      </form>
      <p class="small-muted text-center mt-3 mb-0">Sudah punya akun? <a href="<?= base_url('login') ?>">Login di sini</a>.</p>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
