<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statuses = ['' => 'Semua', 'pending_verification' => 'Pending', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'];
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Daftar booking</div>
    <div class="tagline">Kelola semua reservasi</div>
  </div>
  <a class="btn-salon-primary" href="<?= base_url('admin/booking/walkin') ?>"><i class="bi bi-plus"></i> Walk-in baru</a>
</div>

<form method="get" class="card-salon mb-3">
  <div class="chip-row">
    <?php foreach ($statuses as $val => $label):
      $isActive = (string) $filter_status === (string) $val;
      $href = '?' . http_build_query(['status' => $val, 'tanggal' => $filter_tanggal, 'q' => $filter_q]);
    ?>
      <a class="chip <?= $isActive ? 'chip--active' : '' ?>" href="<?= esc($href) ?>"><?= esc($label) ?></a>
    <?php endforeach ?>
  </div>
  <div class="row-salon cols-3">
    <div>
      <label class="form-salon-label">Tanggal</label>
      <input type="date" class="form-salon-input" name="tanggal" value="<?= esc($filter_tanggal) ?>">
    </div>
    <div>
      <label class="form-salon-label">Cari nama / kode / HP</label>
      <input class="form-salon-input" type="text" name="q" value="<?= esc($filter_q) ?>" placeholder="Cari…">
    </div>
    <div style="display:flex; align-items:end;">
      <button class="btn-salon-primary btn-salon--full" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </div>
  </div>
  <input type="hidden" name="status" value="<?= esc($filter_status) ?>">
</form>

<div class="card-salon">
  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-calendar-x empty-state__icon"></i><div class="empty-state__title">Belum ada booking</div></div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table-salon">
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Layanan</th><th>Tanggal & Jam</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r):
            $cls = 'badge-salon--' . ($r['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $r['status']));
            $lbl = ['pending_verification' => 'Pending', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$r['status']];
            $rowStyle = $r['status'] === 'pending_verification' ? 'border-left: 3px solid var(--color-pending);' : ($r['status'] === 'cancelled' ? 'opacity: 0.7;' : '');
          ?>
            <tr style="<?= $rowStyle ?>">
              <td><strong><?= esc($r['kode_booking']) ?></strong><?= $r['sumber'] === 'walkin' ? ' <span class="caption">walk-in</span>' : '' ?></td>
              <td><?= esc($r['nama_pelanggan']) ?><div class="caption"><?= esc($r['nomor_hp_pelanggan']) ?></div></td>
              <td><?= esc($r['nama_layanan']) ?></td>
              <td><?= esc(date('d M Y', strtotime($r['tanggal']))) ?><div class="caption"><?= esc(substr($r['slot_mulai'], 0, 5)) ?>–<?= esc(substr($r['slot_selesai'], 0, 5)) ?></div></td>
              <td><span class="badge-salon <?= $cls ?>"><?= esc($lbl) ?></span></td>
              <td><a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('admin/booking/' . $r['id']) ?>">Detail</a></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
