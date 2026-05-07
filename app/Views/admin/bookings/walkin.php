<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Booking offline</span>
  <h1 class="h2 mb-2">Input Booking Walk-in/Offline</h1>
  <p class="small-muted mb-0">Catat pelanggan yang datang langsung, pilih layanan, dan simpan slotnya agar jadwal tidak bentrok.</p>
</div>

<div class="form-card">
  <form method="post">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Pelanggan Terdaftar</label>
        <select class="form-select" name="customer_id">
          <option value="">Pelanggan baru walk-in</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?> - <?= esc($c['phone']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Nama Pelanggan Baru</label>
        <input class="form-control" name="customer_name">
      </div>
      <div class="col-md-3">
        <label class="form-label">No. WhatsApp Baru</label>
        <input class="form-control" name="customer_phone">
      </div>
      <div class="col-md-6">
        <label class="form-label">Layanan</label>
        <select class="form-select" name="service_id" id="service_id" required>
          <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> - <?= $s['duration_minutes'] ?> menit</option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Stylist</label>
        <select class="form-select" name="stylist_id" id="stylist_id" required>
          <?php foreach ($stylists as $st): ?>
            <option value="<?= $st['id'] ?>"><?= esc($st['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tanggal</label>
        <input type="date" class="form-control" id="booking_date" name="booking_date" min="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-12">
        <label class="form-label">Slot</label>
        <div id="slots" class="d-flex flex-wrap gap-2 my-2"><span class="small-muted">Pilih tanggal untuk memuat slot.</span></div>
        <input type="hidden" name="start_time" id="start_time" required>
      </div>
      <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea class="form-control" name="notes" rows="3"></textarea>
      </div>
    </div>
    <button class="btn btn-primary mt-4" type="submit">Simpan Walk-in</button>
  </form>
</div>

<script>
async function loadSlots() {
  start_time.value = '';
  if (!service_id.value || !stylist_id.value || !booking_date.value) {
    slots.innerHTML = '<span class="small-muted">Pilih layanan, stylist, dan tanggal.</span>';
    return;
  }
  slots.innerHTML = '<span class="small-muted">Memuat slot...</span>';
  const response = await fetch('/admin/booking/slots?service_id=' + service_id.value + '&stylist_id=' + stylist_id.value + '&date=' + booking_date.value);
  const data = await response.json();
  slots.innerHTML = '';
  if (!data.length) {
    slots.innerHTML = '<span class="small-muted">Belum ada slot tersedia untuk pilihan ini.</span>';
    return;
  }
  data.forEach((slot) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm slot-btn ' + (slot.available ? 'btn-outline-primary' : 'btn-outline-secondary disabled');
    button.disabled = !slot.available;
    button.textContent = slot.start + ' - ' + slot.end + (slot.available ? '' : ' (Terisi)');
    if (slot.available) {
      button.onclick = () => {
        start_time.value = slot.start;
        document.querySelectorAll('#slots button').forEach((item) => {
          item.classList.remove('btn-primary', 'selected');
          if (!item.classList.contains('disabled')) item.classList.add('btn-outline-primary');
        });
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary', 'selected');
      };
    }
    slots.appendChild(button);
  });
}

[service_id, stylist_id, booking_date].forEach((element) => element.addEventListener('change', loadSlots));
</script>

<?= $this->endSection() ?>
