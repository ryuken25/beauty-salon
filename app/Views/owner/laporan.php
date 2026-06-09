<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>
<?php
$hariId = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl = $hariId[(int) date('w')] . ', ' . date('j') . ' ' . $bulanId[(int) date('n') - 1] . ' ' . date('Y');
$statusLabels = [
    'pending_verification' => 'Pending',
    'accepted' => 'Diterima',
    'completed' => 'Selesai',
    'rejected' => 'Ditolak',
    'cancelled' => 'Batal',
];
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Laporan</div>
    <div class="tagline">Analitik pendapatan &amp; performa · <?= esc($tgl) ?></div>
  </div>
</div>

<!-- Revenue metrics -->
<div class="row-salon cols-3 mb-3">
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
  <div class="card-salon">
    <div class="metric">
      <span class="label">Pendapatan 7 hari</span>
      <span class="metric__value">Rp <?= number_format($total_minggu, 0, ',', '.') ?></span>
      <span class="metric__caption">Tren mingguan</span>
    </div>
  </div>
  <div class="card-salon">
    <div class="metric">
      <span class="label">Pendapatan bulan ini</span>
      <span class="metric__value">Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?></span>
      <span class="metric__caption"><?= esc(date('F Y')) ?></span>
    </div>
  </div>
</div>

<!-- Chart + Top services -->
<div class="split-60-40 mb-3">
  <div class="card-salon">
    <div class="flex justify-between items-center mb-1">
      <div>
        <span class="label">Pendapatan 7 hari terakhir</span>
        <div class="tagline">Bar terakhir = hari ini</div>
      </div>
    </div>
    <canvas id="chart7days" height="120"></canvas>
  </div>

  <div class="card-salon">
    <span class="label">Layanan terpopuler</span>
    <div class="tagline mb-2">Bulan ini · top 5</div>
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

<!-- Status distribution — clickable per status, warna sesuai status -->
<?php
$bulanThis = date('Y-m');
$statusIcon = [
    'pending_verification' => 'bi-hourglass-split',
    'accepted' => 'bi-check2-circle',
    'completed' => 'bi-trophy',
    'rejected' => 'bi-x-circle',
    'cancelled' => 'bi-slash-circle',
];
$statusBoxCls = [
    'pending_verification' => 'status-box status-box--pending',
    'accepted' => 'status-box status-box--accepted',
    'completed' => 'status-box status-box--completed',
    'rejected' => 'status-box status-box--rejected',
    'cancelled' => 'status-box status-box--cancelled',
];
?>
<div class="card-salon">
  <div class="flex justify-between items-center mb-2" style="flex-wrap:wrap; gap:0.5rem;">
    <div>
      <div class="h2" style="margin:0;">Status booking bulan ini</div>
      <div class="caption">Bulan ini, klik untuk lihat detail.</div>
    </div>
    <span class="badge-salon badge-salon--accepted"><?= esc(date('F Y')) ?></span>
  </div>
  <div class="row-salon cols-5">
    <?php foreach ($status_map as $st => $jumlah): ?>
      <a class="<?= $statusBoxCls[$st] ?? 'status-box' ?>" href="<?= base_url('admin/booking?status=' . urlencode($st) . '&bulan=' . urlencode($bulanThis)) ?>">
        <div class="status-box__label"><i class="bi <?= esc($statusIcon[$st] ?? 'bi-circle') ?>"></i> <?= esc($statusLabels[$st]) ?></div>
        <div class="status-box__count"><?= esc($jumlah) ?></div>
      </a>
    <?php endforeach ?>
  </div>
</div>

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
        x: { ticks: { color: '#9D9180' }, grid: { color: 'rgba(201,166,107,0.08)' } },
        y: {
          ticks: { color: '#9D9180', callback: (v) => 'Rp ' + Number(v / 1000).toFixed(0) + 'k' },
          grid: { color: 'rgba(201,166,107,0.08)' },
        },
      },
    },
  });
})();
</script>

<?= $this->endSection() ?>
