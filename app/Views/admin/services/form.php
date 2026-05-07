<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Layanan</span>
  <h1 class="h2 mb-2"><?= $service ? 'Ubah' : 'Tambah' ?> Layanan</h1>
  <p class="small-muted mb-0">Pastikan durasi dan harga sesuai layanan yang tersedia di salon.</p>
</div>

<div class="form-card">
  <form method="post" action="<?= $service ? base_url('admin/layanan/' . $service['id']) : base_url('admin/layanan') ?>">
    <?= csrf_field() ?>
    <?php if ($service): ?><input type="hidden" name="_method" value="PUT"><?php endif ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Kategori</label><input class="form-control" name="category" value="<?= esc($service['category'] ?? '') ?>"></div>
      <div class="col-md-6"><label class="form-label">Nama</label><input class="form-control" name="name" value="<?= esc($service['name'] ?? '') ?>"></div>
      <div class="col-12"><label class="form-label">Deskripsi</label><textarea class="form-control" name="description" rows="3"><?= esc($service['description'] ?? '') ?></textarea></div>
      <div class="col-md-6"><label class="form-label">Durasi Menit</label><input type="number" class="form-control" name="duration_minutes" value="<?= esc($service['duration_minutes'] ?? 30) ?>"></div>
      <div class="col-md-6"><label class="form-label">Harga</label><input type="number" class="form-control" name="price" value="<?= esc($service['price'] ?? 0) ?>"></div>
      <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= ($service['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="form-check-label">Aktif</span></label></div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
      <button class="btn btn-primary" type="submit">Simpan</button>
      <a class="btn btn-outline-secondary" href="<?= base_url('admin/layanan') ?>">Kembali</a>
    </div>
  </form>
</div>

<?= $this->endSection() ?>
