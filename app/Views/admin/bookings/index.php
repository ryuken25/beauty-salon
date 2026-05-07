<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Operasional</span>
  <h1 class="h2 mb-2">Daftar Booking</h1>
  <p class="small-muted mb-0">Filter booking berdasarkan status, tanggal, atau stylist.</p>
</div>

<form class="form-card mb-3">
  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <?php foreach (['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima / Terjadwal', 'rejected' => 'Ditolak', 'cancelled' => 'Batal', 'completed' => 'Selesai'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= request()->getGet('status') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Tanggal</label>
      <input type="date" name="date" value="<?= esc(request()->getGet('date')) ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Stylist</label>
      <select name="stylist_id" class="form-select">
        <option value="">Semua Stylist</option>
        <?php foreach ($stylists as $st): ?>
          <option value="<?= $st['id'] ?>" <?= request()->getGet('stylist_id') == $st['id'] ? 'selected' : '' ?>><?= esc($st['name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Filter</button></div>
  </div>
</form>

<?= view('partials/booking_table', ['bookings' => $bookings]) ?>

<?= $this->endSection() ?>
