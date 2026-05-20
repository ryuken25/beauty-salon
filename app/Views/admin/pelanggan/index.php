<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Pelanggan</div>
    <div class="tagline">Daftar pelanggan dari riwayat booking</div>
  </div>
</div>

<form method="get" class="card-salon mb-3">
  <div class="row-salon cols-2">
    <div>
      <label class="form-salon-label">Cari nama / HP</label>
      <input class="form-salon-input" type="text" name="q" value="<?= esc($q) ?>" placeholder="Misal: Ari atau 0812…">
    </div>
    <div style="display:flex; align-items:end; gap:0.5rem;">
      <button class="btn-salon-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
      <?php if ($q !== ''): ?>
        <a class="btn-salon-secondary" href="<?= base_url('admin/pelanggan') ?>">Reset</a>
      <?php endif ?>
    </div>
  </div>
</form>

<div class="card-salon">
  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-people empty-state__icon"></i>
      <div class="empty-state__title">Belum ada pelanggan</div>
      <div class="empty-state__hint">Pelanggan akan muncul setelah booking pertama dibuat.</div>
    </div>
  <?php else: ?>
    <table class="table-salon">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Nomor HP</th>
          <th>Email</th>
          <th class="text-right">Total booking</th>
          <th class="text-right">Selesai</th>
          <th class="text-right">Aktif</th>
          <th>Terakhir</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td style="font-weight:500;"><?= esc($r['nama_pelanggan']) ?></td>
            <td><?= esc($r['nomor_hp_pelanggan']) ?></td>
            <td class="caption"><?= esc($r['email_pelanggan'] ?: '—') ?></td>
            <td class="text-right"><?= esc($r['total_booking']) ?></td>
            <td class="text-right" style="color:var(--color-completed-fg);"><?= esc($r['total_selesai']) ?></td>
            <td class="text-right" style="color:<?= (int) $r['aktif'] > 0 ? 'var(--gold)' : 'var(--text-muted)' ?>;"><?= esc($r['aktif']) ?></td>
            <td class="caption"><?= esc(date('d M Y', strtotime($r['terakhir']))) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <div class="caption mt-2">Menampilkan <?= count($rows) ?> pelanggan teratas (sortir: booking terbaru).</div>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
