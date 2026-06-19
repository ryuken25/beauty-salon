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

<!-- Revenue metrics — clickable jadi switch mode grafik -->
<div class="row-salon cols-3 mb-3" id="revenueModeRow">
  <button type="button" class="card-salon revenue-mode" data-mode="hari" data-offset="0">
    <div class="metric">
      <span class="label">Pendapatan hari ini</span>
      <span class="metric__value">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></span>
      <span class="metric__caption">
        <?php if ($trend === null): ?>
          Klik untuk lihat per jam
        <?php else: ?>
          <i class="bi bi-arrow-<?= $trend >= 0 ? 'up' : 'down' ?>"></i>
          <?= esc($trend) ?>% vs kemarin · klik untuk per jam
        <?php endif ?>
      </span>
    </div>
  </button>
  <button type="button" class="card-salon card-salon--featured revenue-mode revenue-mode--active" data-mode="minggu" data-offset="0">
    <div class="metric">
      <span class="label">Pendapatan 7 hari</span>
      <span class="metric__value">Rp <?= number_format($total_minggu, 0, ',', '.') ?></span>
      <span class="metric__caption" style="color:rgba(20,17,15,0.7);">Tren mingguan · aktif</span>
    </div>
  </button>
  <button type="button" class="card-salon revenue-mode" data-mode="bulan" data-offset="0">
    <div class="metric">
      <span class="label">Pendapatan bulan ini</span>
      <span class="metric__value">Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?></span>
      <span class="metric__caption"><?= esc(date('F Y')) ?> · klik untuk grafik bulan</span>
    </div>
  </button>
</div>

<style>
  .revenue-mode { cursor:pointer; text-align:left; appearance:none; width:100%; font:inherit; color:inherit; }
  .revenue-mode--active { box-shadow: 0 0 0 2px var(--gold) inset; }
</style>

<!-- Chart + Top services -->
<div class="split-60-40 mb-3">
  <div class="card-salon">
    <div class="flex justify-between items-center mb-1" style="flex-wrap:wrap; gap:0.5rem;">
      <div>
        <span class="label" id="chartTitle">7 hari terakhir</span>
        <div class="tagline">Total: <span id="chartTotal">Rp <?= number_format($total_minggu, 0, ',', '.') ?></span></div>
      </div>
      <div class="flex gap-1">
        <button type="button" class="btn-salon-ghost btn-salon--sm" id="chartPrev"><i class="bi bi-chevron-left"></i> Prev</button>
        <button type="button" class="btn-salon-ghost btn-salon--sm" id="chartNext" disabled>Next <i class="bi bi-chevron-right"></i></button>
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

<!-- ── Laporan Rincian Pendapatan (Filter Tanggal) ──────────────── -->
<div class="card-salon mt-3">
  <div class="flex justify-between items-center mb-2" style="flex-wrap:wrap; gap:0.5rem;">
    <div>
      <div class="h2" style="margin:0;">Rincian Pendapatan &amp; DP</div>
      <div class="caption">Berdasarkan tanggal transaksi selesai.</div>
    </div>
  </div>

  <form method="get" class="mb-3">
    <div class="row-salon cols-3">
      <div>
        <label class="form-salon-label">Dari Tanggal</label>
        <input class="form-salon-input" type="date" name="start" value="<?= esc($start) ?>">
      </div>
      <div>
        <label class="form-salon-label">Sampai Tanggal</label>
        <input class="form-salon-input" type="date" name="end" value="<?= esc($end) ?>">
      </div>
      <div style="display:flex; align-items:end;">
        <button class="btn-salon-primary btn-salon--full" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
      </div>
    </div>
  </form>

  <div class="row-salon cols-3 mb-3">
    <div class="card-salon" style="border-left: 4px solid var(--gold);">
      <div class="metric">
        <span class="label">Total DP Diterima</span>
        <span class="metric__value" style="color:var(--gold);">Rp <?= number_format($total_dp_range, 0, ',', '.') ?></span>
      </div>
    </div>
    <div class="card-salon" style="border-left: 4px solid var(--gold);">
      <div class="metric">
        <span class="label">Total Pelunasan Diterima</span>
        <span class="metric__value" style="color:var(--gold);">Rp <?= number_format($total_sisa_range, 0, ',', '.') ?></span>
      </div>
    </div>
    <div class="card-salon card-salon--featured">
      <div class="metric">
        <span class="label">Total Pendapatan Keseluruhan</span>
        <span class="metric__value">Rp <?= number_format($total_pendapatan_range, 0, ',', '.') ?></span>
      </div>
    </div>
  </div>

  <?php if (empty($transactions)): ?>
    <div class="empty-state">
      <i class="bi bi-receipt empty-state__icon"></i>
      <div class="empty-state__title">Tidak ada transaksi pada periode ini</div>
    </div>
  <?php else: ?>
    <table class="table-salon">
      <thead>
        <tr>
          <th>Tanggal Transaksi</th>
          <th>Kode Booking</th>
          <th>Pelanggan</th>
          <th>Layanan</th>
          <th class="text-right">Total Layanan</th>
          <th class="text-right">DP</th>
          <th class="text-right">Sisa Bayar / Pelunasan</th>
          <th class="text-right">Total Pendapatan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): 
          $totalLayanan = (int) $t['base_price'] + (int) $t['additional_price'];
          $dpPaid = (int) $t['dp_paid'];
          $sisaBayar = (int) $t['sisa_bayar'];
          $totalRevenue = (int) $t['nominal'];
        ?>
          <tr>
            <td><?= esc(date('d M Y H:i', strtotime($t['tanggal_transaksi']))) ?></td>
            <td><strong><?= esc($t['kode_booking']) ?></strong></td>
            <td><?= esc($t['nama_pelanggan']) ?></td>
            <td><?= esc($t['nama_layanan']) ?></td>
            <td class="text-right">Rp <?= number_format($totalLayanan, 0, ',', '.') ?></td>
            <td class="text-right" style="color:<?= $dpPaid > 0 ? 'var(--gold)' : 'var(--text-muted)' ?>;">
              <?= $dpPaid > 0 ? 'Rp ' . number_format($dpPaid, 0, ',', '.') : '—' ?>
            </td>
            <td class="text-right">Rp <?= number_format($sisaBayar, 0, ',', '.') ?></td>
            <td class="text-right" style="font-weight:600; color:var(--gold);">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <div class="caption mt-2" style="font-style:italic;">
      * DP dan pelunasan dihitung masuk berdasarkan tanggal transaksi selesai untuk periode <?= esc(date('d M Y', strtotime($start))) ?> s/d <?= esc(date('d M Y', strtotime($end))) ?>.
    </div>
  <?php endif ?>
</div>

<script>
(function () {
  const ctx = document.getElementById('chart7days').getContext('2d');
  const titleEl = document.getElementById('chartTitle');
  const totalEl = document.getElementById('chartTotal');
  const prevBtn = document.getElementById('chartPrev');
  const nextBtn = document.getElementById('chartNext');
  const modeBtns = document.querySelectorAll('.revenue-mode');
  const REVENUE_URL = '<?= base_url('owner/laporan/revenue') ?>';

  const initialLabels = <?= json_encode($chart_labels) ?>;
  const initialValues = <?= json_encode($chart_values) ?>;

  let state = { mode: 'minggu', offset: 0 };

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: initialLabels,
      datasets: [{
        data: initialValues,
        backgroundColor: initialValues.map((_, i) => i === initialValues.length - 1 ? '#C9A66B' : 'rgba(201,166,107,0.35)'),
        borderRadius: 6,
        borderSkipped: false,
      }],
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

  async function loadRevenue(mode, offset) {
    state = { mode, offset };
    try {
      const res = await fetch(REVENUE_URL + '?mode=' + encodeURIComponent(mode) + '&offset=' + offset);
      const data = await res.json();
      chart.data.labels = data.labels;
      chart.data.datasets[0].data = data.values;
      const lastIdx = data.values.length - 1;
      chart.data.datasets[0].backgroundColor = data.values.map((_, i) =>
        offset === 0 && i === lastIdx ? '#C9A66B' : 'rgba(201,166,107,0.35)'
      );
      chart.update();
      titleEl.textContent = data.title;
      totalEl.textContent = 'Rp ' + Number(data.total).toLocaleString('id-ID');
      prevBtn.disabled = ! data.can_prev;
      nextBtn.disabled = ! data.can_next;
    } catch (e) {
      console.error('Gagal load revenue', e);
    }
  }

  modeBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      modeBtns.forEach((b) => b.classList.remove('revenue-mode--active'));
      btn.classList.add('revenue-mode--active');
      loadRevenue(btn.dataset.mode, 0);
    });
  });
  prevBtn.addEventListener('click', () => loadRevenue(state.mode, state.offset - 1));
  nextBtn.addEventListener('click', () => loadRevenue(state.mode, state.offset + 1));
})();
</script>

<?= $this->endSection() ?>
