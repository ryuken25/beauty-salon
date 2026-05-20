<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>
<?php
$prevDay = date('Y-m-d', strtotime("{$tanggal} -1 day"));
$nextDay = date('Y-m-d', strtotime("{$tanggal} +1 day"));
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Jadwal harian</div>
    <div class="tagline"><?= esc(date('l, d M Y', strtotime($tanggal))) ?></div>
  </div>
  <div class="flex gap-1">
    <a class="btn-salon-ghost" href="?tanggal=<?= esc($prevDay) ?>"><i class="bi bi-chevron-left"></i></a>
    <a class="btn-salon-secondary" href="?tanggal=<?= esc(date('Y-m-d')) ?>">Hari ini</a>
    <a class="btn-salon-ghost" href="?tanggal=<?= esc($nextDay) ?>"><i class="bi bi-chevron-right"></i></a>
  </div>
</div>

<div class="card-salon">
  <?php if (empty($all_slots)): ?>
    <div class="caption">Belum ada slot tersedia. Periksa pengaturan jam buka/tutup.</div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table-salon" style="min-width:600px;">
        <thead>
          <tr>
            <th>Jam</th>
            <th>Booking</th>
            <th>Pelanggan</th>
            <th>Layanan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_slots as $s):
            $cell = $slot_map[$s] ?? null;
            $b = $cell['booking'] ?? null;
            $cls = $b ? 'badge-salon--' . ($b['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $b['status'])) : '';
            $lbl = $b ? ['pending_verification' => 'Pending', 'accepted' => 'Diterima', 'completed' => 'Selesai'][$b['status']] : '';
          ?>
            <tr style="<?= $b ? '' : 'opacity:0.55;' ?>">
              <td style="font-family:var(--font-display); font-weight:500;"><?= esc($s) ?></td>
              <?php if ($b && $cell['is_start']): ?>
                <td><a href="<?= base_url('admin/booking/' . $b['id']) ?>" style="color:var(--gold); font-weight:600;"><?= esc($b['kode_booking']) ?></a></td>
                <td><?= esc($b['nama_pelanggan']) ?></td>
                <td><?= esc($b['nama_layanan']) ?><div class="caption"><?= esc(substr($b['slot_mulai'], 0, 5)) ?>–<?= esc(substr($b['slot_selesai'], 0, 5)) ?></div></td>
                <td><span class="badge-salon <?= $cls ?>"><?= esc($lbl) ?></span></td>
              <?php elseif ($b): ?>
                <td colspan="4" class="caption"><i class="bi bi-arrow-return-right" style="color:var(--gold);"></i> lanjutan <?= esc($b['kode_booking']) ?></td>
              <?php else: ?>
                <td colspan="4" class="caption">—</td>
              <?php endif ?>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
