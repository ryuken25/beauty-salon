<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Admin & pemilik</span>
  <h1 class="h2 mb-2">Dashboard Pemilik/Admin</h1>
  <p class="small-muted mb-0">Pantau pendapatan, status booking, dan layanan yang paling sering dipilih pelanggan.</p>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="stat-card"><div class="stat-label">Pendapatan Hari Ini</div><h2 class="stat-value h3">Rp<?= number_format($today, 0, ',', '.') ?></h2></div></div>
  <div class="col-md-4"><div class="stat-card"><div class="stat-label">Pendapatan Minggu Ini</div><h2 class="stat-value h3">Rp<?= number_format($week, 0, ',', '.') ?></h2></div></div>
  <div class="col-md-4"><div class="stat-card"><div class="stat-label">Pendapatan Bulan Ini</div><h2 class="stat-value h3">Rp<?= number_format($month, 0, ',', '.') ?></h2></div></div>
</div>

<div class="soft-card mb-3">
  <div class="d-flex flex-wrap admin-actions">
    <a class="btn btn-primary" href="<?= base_url('admin/booking/pending') ?>">Booking Menunggu Verifikasi</a>
    <a class="btn btn-outline-primary" href="<?= base_url('admin/booking') ?>">Daftar Booking</a>
    <a class="btn btn-outline-primary" href="<?= base_url('admin/booking/walkin') ?>">Input Walk-in</a>
    <a class="btn btn-outline-primary" href="<?= base_url('admin/booking/jadwal') ?>">Jadwal</a>
    <a class="btn btn-outline-primary" href="<?= base_url('admin/transaksi') ?>">Transaksi</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="soft-card h-100">
      <h2 class="h5 section-title mb-3">Grafik Pendapatan Harian Bulan Berjalan</h2>
      <canvas id="chart"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="soft-card mb-3">
      <h2 class="h5 section-title mb-3">Jumlah Booking per Status</h2>
      <div class="d-grid gap-2">
        <?php foreach ($status as $s): ?>
          <div class="d-flex justify-content-between"><span><?= esc($s['status']) ?></span><strong><?= esc($s['total']) ?></strong></div>
        <?php endforeach ?>
      </div>
    </div>
    <div class="soft-card">
      <h2 class="h5 section-title mb-3">Layanan Terpopuler Bulan Ini</h2>
      <div class="d-grid gap-2">
        <?php foreach ($popular as $p): ?>
          <div class="d-flex justify-content-between"><span><?= esc($p['name']) ?></span><strong><?= esc($p['total']) ?></strong></div>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('chart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($daily, 'd')) ?>,
    datasets: [{
      label: 'Pendapatan',
      data: <?= json_encode(array_map('floatval', array_column($daily, 'total'))) ?>,
      backgroundColor: '#c9a24d',
      borderColor: '#17120d',
      borderWidth: 1
    }]
  }
})
</script>

<?= $this->endSection() ?>
