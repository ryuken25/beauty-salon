<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
  <div>
    <span class="gold-badge badge rounded-pill mb-2">Tim salon</span>
    <h1 class="h2 mb-2">Manajemen Stylist</h1>
    <p class="small-muted mb-0">Kelola data stylist dan jam kerja agar slot booking selalu akurat.</p>
  </div>
  <a class="btn btn-primary" href="<?= base_url('admin/stylist/new') ?>">Tambah Stylist</a>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Nama</th><th>Telepon</th><th>Owner</th><th>Aktif</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($stylists as $s): ?>
          <tr>
            <td><strong><?= esc($s['name']) ?></strong></td>
            <td><?= esc($s['phone']) ?></td>
            <td><?= $s['is_owner'] ? 'Ya' : 'Tidak' ?></td>
            <td><span class="badge <?= $s['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $s['is_active'] ? 'Ya' : 'Tidak' ?></span></td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/stylist/' . $s['id'] . '/edit') ?>">Ubah</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('admin/stylist/' . $s['id'] . '/jam-kerja') ?>">Jam Kerja</a>
                <form class="d-inline" method="post" action="<?= base_url('admin/stylist/' . $s['id']) ?>"><?= csrf_field() ?><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
