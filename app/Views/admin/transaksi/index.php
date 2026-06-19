<?= $this->extend('layouts/panel') ?>
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
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Kode</th>
          <th>Pelanggan</th>
          <th>Layanan</th>
          <th>Metode</th>
          <th class="text-right">Total Layanan</th>
          <th class="text-right">DP</th>
          <th class="text-right">Sisa Bayar</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $base = (int) ($r['base_price'] ?? 0);
          $add  = (int) ($r['additional_price'] ?? 0);
          $catatan = trim((string) ($r['catatan'] ?? ''));
          $dpPaid = (int) ($r['dp_paid'] ?? 0);
          $sisaBayar = (int) ($r['sisa_bayar'] ?? 0);
          $totalLayanan = $base + $add;
        ?>
          <tr>
            <td><?= esc(date('d M Y H:i', strtotime($r['tanggal']))) ?></td>
            <td>
              <?= esc($r['kode_booking']) ?>
              <?php if ($r['tipe'] === 'dp'): ?>
                <span class="badge-salon badge-salon--pending" style="font-size: 0.65rem; padding: 0.1rem 0.3rem; margin-left: 0.2rem; vertical-align: middle;">DP</span>
              <?php endif ?>
            </td>
            <td>
              <?= esc($r['nama_pelanggan']) ?>
              <?php if ($catatan !== ''): ?>
                <div class="caption" style="margin-top:0.2rem;"><i class="bi bi-chat-left-text"></i> <?= esc($catatan) ?></div>
              <?php endif ?>
            </td>
            <td><?= esc($r['nama_layanan']) ?></td>
            <td><?= esc(ucfirst($r['metode_bayar'])) ?><?= $r['tipe'] === 'dp' ? ' (DP)' : '' ?></td>
            <td class="text-right">Rp <?= number_format($totalLayanan, 0, ',', '.') ?></td>
            <td class="text-right" style="color:<?= $dpPaid > 0 ? 'var(--gold)' : 'var(--text-muted)' ?>;">
              <?= $dpPaid > 0 ? 'Rp ' . number_format($dpPaid, 0, ',', '.') : '—' ?>
            </td>
            <td class="text-right" style="font-weight:600; color:<?= $r['tipe'] === 'dp' ? 'var(--text-muted)' : 'var(--gold)' ?>;">
              <?= $r['tipe'] === 'dp' ? '—' : 'Rp ' . number_format($sisaBayar, 0, ',', '.') ?>
            </td>
            <td class="text-center">
              <?php if ($r['tipe'] === 'dp'): ?>
                <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('admin/booking/' . $r['booking_id'] . '/receipt') ?>" style="padding: 0.25rem 0.5rem;"><i class="bi bi-receipt"></i> Receipt</a>
              <?php else: ?>
                <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('admin/transaksi/' . $r['id'] . '/nota') ?>" style="padding: 0.25rem 0.5rem;"><i class="bi bi-receipt"></i> Nota</a>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
