<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$hariId = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl = $hariId[(int) date('w')] . ', ' . date('j') . ' ' . $bulanId[(int) date('n') - 1] . ' ' . date('Y');
$isPemilik = $role === 'pemilik';
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Dashboard <?= $isPemilik ? '' : '· Admin' ?></div>
    <div class="tagline"><?= esc($tgl) ?></div>
  </div>
  <?php if ($pending > 0): ?>
    <a class="btn-salon-primary" href="<?= base_url('admin/booking?status=pending_verification') ?>"><i class="bi bi-bell"></i> <?= esc($pending) ?> pending</a>
  <?php endif ?>
</div>

<!-- Metric cards: pemilik dapat 3, admin dapat 2 -->
<div class="row-salon <?= $isPemilik ? 'cols-3' : 'cols-2' ?> mb-3">

  <?php if ($isPemilik): ?>
    <div class="card-salon card-salon--featured">
      <div class="metric">
        <span class="label">Pendapatan hari ini</span>
        <span class="metric__value">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></span>
        <span class="metric__caption" style="color:rgba(20,17,15,0.7);">
          <?php if ($trend === null): ?>
            Belum ada pembanding kemarin
          <?php else: ?>
            <i class="bi bi-arrow-<?= $trend >= 0 ? 'up' : 'down' ?>"></i>
            <?= esc($trend) ?>% vs kemarin
          <?php endif ?>
        </span>
      </div>
    </div>
  <?php endif ?>

  <div class="card-salon">
    <div class="metric">
      <span class="label">Booking hari ini</span>
      <span class="metric__value"><?= esc($booking_hari_ini) ?></span>
      <span class="metric__caption"><?= esc($booking_hari_ini_selesai) ?> selesai</span>
    </div>
  </div>

  <div class="card-salon <?= $pending > 0 ? 'card-salon--pending' : '' ?>">
    <div class="metric">
      <span class="label">Pending verifikasi</span>
      <span class="metric__value"><?= esc($pending) ?></span>
      <span class="metric__caption" style="<?= $pending > 0 ? 'color:var(--color-pending);' : '' ?>">
        <?= $pending > 0 ? 'Perlu aksi' : 'Aman' ?>
      </span>
    </div>
  </div>
</div>

<?php if ($isPemilik): ?>
  <!-- Pemilik-only: chart + layanan terpopuler -->
  <div class="split-60-40 mb-3">
    <div class="card-salon">
      <div class="flex justify-between items-center mb-1">
        <div>
          <span class="label">Pendapatan 7 hari</span>
          <div class="tagline">Tren mingguan</div>
        </div>
        <div class="h3">Rp <?= number_format($total_minggu, 0, ',', '.') ?></div>
      </div>
      <canvas id="chart7days" height="120"></canvas>
    </div>

    <div class="card-salon">
      <span class="label">Layanan terpopuler</span>
      <div class="tagline mb-2">Bulan ini</div>
      <?php if (empty($top_services)): ?>
        <div class="caption">Belum ada data bulan ini.</div>
      <?php else: ?>
        <?php foreach ($top_services as $s):
          $pct = (int) round($s['jumlah'] / $total_top * 100);
        ?>
          <div class="mb-2">
            <div class="flex justify-between items-center mb-1">
              <span style="color:var(--text-primary); font-size:0.875rem; font-weight:500;"><?= esc($s['nama']) ?></span>
              <span class="caption" style="color:var(--gold); font-weight:600;"><?= esc($pct) ?>%</span>
            </div>
            <div style="background:rgba(201,166,107,0.12); height:6px; border-radius:3px; overflow:hidden;">
              <div style="background:var(--gold); height:100%; width:<?= esc($pct) ?>%;"></div>
            </div>
          </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  </div>
<?php else: ?>
  <!-- Admin: panel ringkas -->
  <div class="card-salon mb-3" style="background:var(--gold-soft); border-color:var(--gold-border);">
    <div class="flex items-center gap-2">
      <i class="bi bi-shield-check" style="font-size:1.5rem; color:var(--gold);"></i>
      <div>
        <div style="font-family:var(--font-display); font-size:1.125rem; color:var(--text-primary);">Selamat datang, <?= esc(session('user_nama')) ?></div>
        <div class="caption">Akses admin: verifikasi booking, walk-in, dan jadwal harian. Untuk laporan pendapatan & pengaturan, hubungi pemilik.</div>
      </div>
    </div>
  </div>
<?php endif ?>

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

<?php if ($isPemilik): ?>
<script>
(function () {
  const ctx = document.getElementById('chart7days').getContext('2d');
  const values = <?= json_encode($chart_values) ?>;
  const lastIdx = values.length - 1;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chart_labels) ?>,
      datasets: [{
        data: values,
        backgroundColor: values.map((_, i) => i === lastIdx ? '#C9A66B' : 'rgba(201,166,107,0.35)'),
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F1B18',
          titleColor: '#F5EBDC',
          bodyColor: '#F5EBDC',
          borderColor: 'rgba(201,166,107,0.35)',
          borderWidth: 1,
          callbacks: { label: (c) => 'Rp ' + Number(c.raw).toLocaleString('id-ID') },
        },
      },
      scales: {
        x: {
          ticks: { color: '#9D9180' },
          grid: { color: 'rgba(201,166,107,0.08)' },
        },
        y: {
          ticks: {
            color: '#9D9180',
            callback: (v) => 'Rp ' + Number(v / 1000).toFixed(0) + 'k',
          },
          grid: { color: 'rgba(201,166,107,0.08)' },
        },
      },
    },
  });
})();
</script>
<?php endif ?>

<?= $this->endSection() ?>
