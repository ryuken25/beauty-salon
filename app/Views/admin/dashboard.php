<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>
<?php
$hariId = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl = $hariId[(int) date('w')] . ', ' . date('j') . ' ' . $bulanId[(int) date('n') - 1] . ' ' . date('Y');
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Dashboard</div>
    <div class="tagline"><?= esc($tgl) ?></div>
  </div>
  <?php if ($pending > 0): ?>
    <a class="btn-salon-primary" href="<?= base_url('admin/booking?status=pending_verification') ?>"><i class="bi bi-bell"></i> <?= esc($pending) ?> pending</a>
  <?php endif ?>
</div>

<!-- 3 metric operational: pending, hari ini, accepted — semua clickable -->
<?php $today = date('Y-m-d'); ?>
<div class="row-salon cols-3 mb-3">
  <a class="card-salon metric-link <?= $pending > 0 ? 'card-salon--pending' : '' ?>" href="<?= base_url('admin/booking?status=pending_verification') ?>">
    <div class="metric">
      <span class="label">Pending verifikasi</span>
      <span class="metric__value <?= $pending > 0 ? 'metric-pending-strong' : '' ?>"><?= esc($pending) ?></span>
      <span class="metric__caption">
        <?= $pending > 0 ? 'Perlu aksi — klik untuk lihat' : 'Aman' ?>
      </span>
    </div>
  </a>
  <a class="card-salon metric-link" href="<?= base_url('admin/booking?tanggal=' . $today) ?>">
    <div class="metric">
      <span class="label">Booking hari ini</span>
      <span class="metric__value"><?= esc($booking_hari_ini) ?></span>
      <span class="metric__caption"><?= esc($booking_hari_ini_selesai) ?> selesai — klik untuk daftar</span>
    </div>
  </a>
  <a class="card-salon metric-link" href="<?= base_url('admin/booking?status=accepted&tanggal=' . $today) ?>">
    <div class="metric">
      <span class="label">Sudah diterima</span>
      <span class="metric__value"><?= esc($accepted_hari_ini) ?></span>
      <span class="metric__caption">Antri hari ini — klik untuk lihat</span>
    </div>
  </a>
</div>

<!-- Quick actions -->
<div class="card-salon mb-3" style="background:var(--gold-soft); border-color:var(--gold-border);">
  <div class="flex items-center justify-between flex-wrap gap-2">
    <div>
      <div style="font-family:var(--font-display); font-size:1.125rem; color:var(--text-primary);">Aksi cepat</div>
      <div class="caption">Operasi harian — verifikasi, walk-in, jadwal, pelanggan.</div>
    </div>
    <div class="flex flex-wrap gap-1">
      <a class="btn-salon-primary btn-salon--sm" href="<?= base_url('admin/booking?status=pending_verification') ?>"><i class="bi bi-check2-square"></i> Verifikasi pending</a>
      <a class="btn-salon-secondary btn-salon--sm" href="<?= base_url('admin/booking/walkin') ?>"><i class="bi bi-person-plus"></i> Walk-in</a>
      <a class="btn-salon-secondary btn-salon--sm" href="<?= base_url('admin/booking/jadwal') ?>"><i class="bi bi-calendar3"></i> Jadwal hari ini</a>
    </div>
  </div>
</div>

<div class="card-salon">
  <div class="flex justify-between items-center mb-2">
    <div class="h2">Booking terbaru</div>
    <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('admin/booking') ?>">Lihat semua →</a>
  </div>
  <?php if (empty($booking_terbaru)): ?>
    <div class="caption">Belum ada booking.</div>
  <?php else: ?>
    <table class="table-salon">
      <thead><tr><th>Pelanggan</th><th>Layanan</th><th>Jam</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($booking_terbaru as $b):
          $cls = 'badge-salon--' . ($b['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $b['status']));
          $lbl = ['pending_verification' => 'Pending', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$b['status']];
        ?>
          <tr>
            <td><?= esc($b['nama_pelanggan']) ?><div class="caption"><?= esc($b['kode_booking']) ?></div></td>
            <td><?= esc($b['nama_layanan']) ?></td>
            <td><?= esc(substr($b['slot_mulai'], 0, 5)) ?>–<?= esc(substr($b['slot_selesai'], 0, 5)) ?></td>
            <td><span class="badge-salon <?= $cls ?>"><?= esc($lbl) ?></span></td>
            <td><a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('admin/booking/' . $b['id']) ?>">Detail</a></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
