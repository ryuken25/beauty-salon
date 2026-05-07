<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Stylist</span>
  <h1 class="h2 mb-2"><?= $stylist ? 'Ubah' : 'Tambah' ?> Stylist</h1>
  <p class="small-muted mb-0">Data stylist digunakan untuk menampilkan pilihan petugas saat booking.</p>
</div>

<div class="form-card">
  <form method="post" action="<?= $stylist ? base_url('admin/stylist/' . $stylist['id']) : base_url('admin/stylist') ?>">
    <?= csrf_field() ?>
    <?php if ($stylist): ?><input type="hidden" name="_method" value="PUT"><?php endif ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Nama</label><input class="form-control" name="name" value="<?= esc($stylist['name'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Telepon</label><input class="form-control" name="phone" value="<?= esc($stylist['phone'] ?? '') ?>"></div>
      <div class="col-12 d-flex flex-wrap gap-4">
        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_owner" value="1" <?= ($stylist['is_owner'] ?? 0) ? 'checked' : '' ?>> <span class="form-check-label">Owner</span></label>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= ($stylist['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="form-check-label">Aktif</span></label>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
      <button class="btn btn-primary" type="submit">Simpan</button>
      <a class="btn btn-outline-secondary" href="<?= base_url('admin/stylist') ?>">Kembali</a>
    </div>
  </form>
</div>

<?= $this->endSection() ?>
