<?= $this->extend('layouts/admin') ?>
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
  <?php if (empty($stylists)): ?>
    <div class="caption">Belum ada stylist aktif.</div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table-salon" style="min-width:600px;">
        <thead><tr><th>Stylist</th><?php foreach ($all_slots as $s): ?><th style="font-size:0.6rem; text-align:center;"><?= esc($s) ?></th><?php endforeach ?></tr></thead>
        <tbody>
          <?php foreach ($stylists as $st): ?>
            <tr>
              <td style="font-weight:500;"><?= esc($st['nama']) ?></td>
              <?php foreach ($all_slots as $s):
                $key = $st['id'] . '|' . $s;
                $cell = $slot_map[$key] ?? null;
                $bg = '#fff'; $text = '';
                if ($cell) {
                  $b = $cell['booking'];
                  $bg = match ($b['status']) {
                    'pending_verification' => 'var(--color-pending-bg)',
                    'accepted' => 'rgba(184,146,74,0.3)',
                    'completed' => 'var(--color-completed-bg)',
                    default => '#fff',
                  };
                  $text = $cell['is_start'] ? esc($b['nama_pelanggan'] . ' · ' . $b['nama_layanan']) : '↳';
                }
              ?>
                <td style="background:<?= $bg ?>; text-align:center; font-size:0.7rem; padding:0.25rem;">
                  <?php if ($cell && $cell['is_start']): ?>
                    <a href="<?= base_url('admin/booking/' . $cell['booking']['id']) ?>" style="color:inherit; text-decoration:none;" title="<?= $text ?>"><?= esc($cell['booking']['kode_booking']) ?></a>
                  <?php elseif ($cell): ?>
                    <span style="color:var(--color-gold-dark);">↳</span>
                  <?php else: ?>
                    <span style="color:#ccc;">·</span>
                  <?php endif ?>
                </td>
              <?php endforeach ?>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="caption mt-2">
      <span style="background:var(--color-pending-bg); padding:2px 8px; border-radius:4px;">Pending</span>
      <span style="background:rgba(184,146,74,0.3); padding:2px 8px; border-radius:4px;">Diterima</span>
      <span style="background:var(--color-completed-bg); padding:2px 8px; border-radius:4px;">Selesai</span>
    </div>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
