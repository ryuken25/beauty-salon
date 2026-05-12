<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Transaksi</div>
    <div class="tagline">Pendapatan otomatis dari booking selesai</div>
  </div>
</div>

<form method="get" class="card-salon mb-3">
  <div class="row-salon cols-3">
    <div><label class="form-salon-label">Dari</label><input class="form-salon-input" type="date" name="start" value="<?= esc($start) ?>"></div>
    <div><label class="form-salon-label">Sampai</label><input class="form-salon-input" type="date" name="end" value="<?= esc($end) ?>"></div>
    <div style="display:flex; align-items:end;"><button class="btn-salon-primary btn-salon--full" type="submit"><i class="bi bi-funnel"></i> Terapkan</button></div>
  </div>
</form>

<div class="row-salon cols-3 mb-3">
  <div class="card-salon card-salon--featured"><div class="metric"><span class="label">Total pendapatan</span><span class="metric__value">Rp <?= number_format($total, 0, ',', '.') ?></span></div></div>
  <div class="card-salon"><div class="metric"><span class="label">Jumlah transaksi</span><span class="metric__value"><?= esc($count) ?></span></div></div>
  <div class="card-salon"><div class="metric"><span class="label">Rata-rata per transaksi</span><span class="metric__value">Rp <?= number_format($avg, 0, ',', '.') ?></span></div></div>
</div>

<div class="card-salon">
  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-receipt empty-state__icon"></i><div class="empty-state__title">Belum ada transaksi</div></div>
  <?php else: ?>
    <table class="table-salon">
      <thead><tr><th>Tanggal</th><th>Kode</th><th>Pelanggan</th><th>Layanan</th><th>Metode</th><th class="text-right">Nominal</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= esc(date('d M Y H:i', strtotime($r['tanggal_transaksi']))) ?></td>
            <td><?= esc($r['kode_booking']) ?></td>
            <td><?= esc($r['nama_pelanggan']) ?></td>
            <td><?= esc($r['nama_layanan']) ?></td>
            <td><?= esc(ucfirst($r['metode_bayar'])) ?></td>
            <td class="text-right">Rp <?= number_format((int) $r['nominal'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
