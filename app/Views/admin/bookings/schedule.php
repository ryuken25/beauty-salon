<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Jadwal harian</span>
  <h1 class="h2 mb-2">Jadwal Booking</h1>
  <p class="small-muted mb-0">Lihat booking aktif sesuai tanggal agar pelayanan salon tetap rapi.</p>
</div>

<form class="form-card mb-3">
  <div class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Tanggal</label>
      <input type="date" name="date" value="<?= esc($date) ?>" class="form-control">
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Lihat</button></div>
  </div>
</form>

<?= view('partials/booking_table', ['bookings' => $bookings]) ?>

<?= $this->endSection() ?>
