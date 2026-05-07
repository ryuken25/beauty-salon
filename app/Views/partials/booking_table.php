<?php
if (! function_exists('status_id')) {
    function status_id($s) { return ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima / Terjadwal', 'rejected' => 'Ditolak', 'cancelled' => 'Batal', 'completed' => 'Selesai'][$s] ?? $s; }
}
if (! function_exists('status_class')) {
    function status_class($s) { return ['accepted' => 'status-accepted', 'completed' => 'status-completed', 'rejected' => 'status-rejected', 'cancelled' => 'status-cancelled'][$s] ?? 'status-pending'; }
}
?>
<div class="table-card">
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Tanggal</th>
          <th>Jam</th>
          <th>Layanan</th>
          <th>Stylist</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bookings)): ?>
          <tr><td colspan="7" class="text-center small-muted py-4">Belum ada data booking.</td></tr>
        <?php endif ?>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><strong><?= esc($b['booking_code']) ?></strong></td>
            <td><?= esc($b['booking_date']) ?></td>
            <td><?= esc(substr($b['start_time'], 0, 5)) ?>-<?= esc(substr($b['end_time'], 0, 5)) ?></td>
            <td><?= esc($b['service_name'] ?? $b['service_id']) ?></td>
            <td><?= esc($b['stylist_name'] ?? $b['stylist_id']) ?></td>
            <td><span class="badge badge-status <?= status_class($b['status']) ?>"><?= status_id($b['status']) ?></span></td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?= isset($customer) ? base_url('pelanggan/booking/' . $b['id']) : base_url('admin/booking/' . $b['id']) ?>">Detail</a></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
