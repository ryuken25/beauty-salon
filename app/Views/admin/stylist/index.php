<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Stylist</div>
    <div class="tagline">Daftar dan jadwal stylist</div>
  </div>
  <button class="btn-salon-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newStylist"><i class="bi bi-plus"></i> Tambah stylist</button>
</div>

<div class="collapse mb-3" id="newStylist">
  <form class="card-salon" method="post" action="<?= base_url('admin/stylist') ?>">
    <?= csrf_field() ?>
    <div class="h2 mb-2">Stylist baru</div>
    <div class="row-salon cols-2">
      <div><label class="form-salon-label">Nama *</label><input class="form-salon-input" name="nama" required></div>
      <div><label class="form-salon-label">No. HP</label><input class="form-salon-input" name="nomor_hp"></div>
      <div><label class="form-salon-label">Peran</label><input class="form-salon-input" name="peran" placeholder="Senior Stylist, dll"></div>
      <div><label class="form-salon-label">Default untuk customer</label><select class="form-salon-select" name="is_default"><option value="0">Tidak</option><option value="1">Ya</option></select></div>
      <div><label class="form-salon-label">Aktif</label><select class="form-salon-select" name="is_active"><option value="1">Aktif</option><option value="0">Non</option></select></div>
    </div>
    <button class="btn-salon-primary mt-2" type="submit">Simpan</button>
  </form>
</div>

<div class="row-salon cols-2">
  <?php foreach ($rows as $r): ?>
    <div class="card-salon <?= $r['is_default'] ? 'card-salon--active' : '' ?>">
      <div class="flex justify-between items-center mb-1">
        <div>
          <div class="h3"><?= esc($r['nama']) ?></div>
          <div class="tagline"><?= esc($r['peran']) ?></div>
        </div>
        <div>
          <?php if ($r['is_default']): ?><span class="badge-salon badge-salon--accepted">Default</span><?php endif ?>
          <span class="badge-salon <?= $r['is_active'] ? 'badge-salon--completed' : 'badge-salon--cancelled' ?>"><?= $r['is_active'] ? 'Aktif' : 'Non' ?></span>
        </div>
      </div>
      <div class="caption mb-2"><i class="bi bi-telephone"></i> <?= esc($r['nomor_hp']) ?></div>
      <a class="btn-salon-secondary btn-salon--sm" href="<?= base_url('admin/stylist/' . $r['id'] . '/jadwal') ?>"><i class="bi bi-calendar3"></i> Atur jadwal</a>
      <details class="mt-1">
        <summary class="btn-salon-ghost btn-salon--sm" style="cursor:pointer;">Edit</summary>
        <form method="post" action="<?= base_url('admin/stylist/' . $r['id'] . '/update') ?>" class="mt-2">
          <?= csrf_field() ?>
          <input class="form-salon-input mb-1" name="nama" value="<?= esc($r['nama']) ?>" required>
          <input class="form-salon-input mb-1" name="nomor_hp" value="<?= esc($r['nomor_hp']) ?>">
          <input class="form-salon-input mb-1" name="peran" value="<?= esc($r['peran']) ?>">
          <label class="flex items-center gap-1 caption"><input type="checkbox" name="is_default" value="1" <?= $r['is_default'] ? 'checked' : '' ?>> Default</label>
          <label class="flex items-center gap-1 caption"><input type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>> Aktif</label>
          <button class="btn-salon-primary btn-salon--sm mt-1" type="submit">Simpan</button>
        </form>
      </details>
    </div>
  <?php endforeach ?>
</div>

<?= $this->endSection() ?>
