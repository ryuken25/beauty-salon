<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div>
    <span class="gold-badge badge rounded-pill mb-2">Master data</span>
    <h1 class="h2 mb-2">Manajemen Layanan</h1>
    <p class="small-muted mb-0">Kelola kategori, durasi, harga, dan status layanan salon.</p>
  </div>
  <a class="btn btn-primary" href="<?= base_url('admin/layanan/new') ?>">Tambah Layanan</a>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Nama</th><th>Kategori</th><th>Durasi</th><th>Harga</th><th>Aktif</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><strong><?= esc($s['name']) ?></strong></td>
            <td><span class="gold-badge badge rounded-pill"><?= esc($s['category']) ?></span></td>
            <td><?= esc($s['duration_minutes']) ?> menit</td>
            <td>Rp<?= number_format($s['price'], 0, ',', '.') ?></td>
            <td><span class="badge <?= $s['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $s['is_active'] ? 'Ya' : 'Tidak' ?></span></td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/layanan/' . $s['id'] . '/edit') ?>">Ubah</a>
                <form class="d-inline" method="post" action="<?= base_url('admin/layanan/' . $s['id']) ?>"><?= csrf_field() ?><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
