<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Laporan</span>
  <h1 class="h2 mb-2">Transaksi dan Laporan Pendapatan</h1>
  <p class="small-muted mb-0">Transaksi otomatis dibuat saat booking diterima sudah ditandai selesai.</p>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead><tr><th>Kode Transaksi</th><th>Kode Booking</th><th>Pelanggan</th><th>Layanan</th><th>Tanggal</th><th>Metode</th><th>Jumlah</th></tr></thead>
      <tbody>
        <?php $total = 0; foreach ($transactions as $t): $total += $t['amount']; ?>
          <tr>
            <td><strong><?= esc($t['transaction_code']) ?></strong></td>
            <td><?= esc($t['booking_code']) ?></td>
            <td><?= esc($t['customer_name']) ?></td>
            <td><?= esc($t['service_name']) ?></td>
            <td><?= esc($t['transaction_date']) ?></td>
            <td><?= esc($t['payment_method']) ?></td>
            <td>Rp<?= number_format($t['amount'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
      <tfoot><tr><th colspan="6">Total</th><th>Rp<?= number_format($total, 0, ',', '.') ?></th></tr></tfoot>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
