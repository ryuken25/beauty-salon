<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Booking pelanggan</span>
  <h1 class="h2 mb-2">Form Booking</h1>
  <p class="small-muted mb-0">Pilih layanan, stylist, tanggal, lalu tentukan slot waktu yang masih tersedia.</p>
</div>

<div class="form-card">
  <form method="post">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Layanan</label>
        <select class="form-select" name="service_id" id="service_id" required>
          <option value="">Pilih layanan</option>
          <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> - <?= $s['duration_minutes'] ?> menit - Rp<?= number_format($s['price'], 0, ',', '.') ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Stylist</label>
        <select class="form-select" name="stylist_id" id="stylist_id" required>
          <option value="">Pilih stylist</option>
          <?php foreach ($stylists as $st): ?>
            <option value="<?= $st['id'] ?>"><?= esc($st['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Tanggal</label>
        <input type="date" class="form-control" name="booking_date" id="booking_date" min="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-12">
        <label class="form-label">Slot Waktu 30 Menit</label>
        <div id="slots" class="d-flex flex-wrap gap-2 my-2">
          <span class="small-muted">Pilih layanan, stylist, dan tanggal.</span>
        </div>
        <input type="hidden" name="start_time" id="start_time" required>
        <div class="small-muted">Slot yang sudah terisi akan otomatis dinonaktifkan.</div>
      </div>
      <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea class="form-control" name="notes" rows="3" placeholder="Opsional, misalnya permintaan model atau catatan khusus."></textarea>
      </div>
    </div>
    <button class="btn btn-primary mt-4" type="submit">Submit Booking</button>
  </form>
</div>

<script>
async function loadSlots() {
  const serviceId = service_id.value;
  const stylistId = stylist_id.value;
  const date = booking_date.value;
  start_time.value = '';

  if (!serviceId || !stylistId || !date) {
    slots.innerHTML = '<span class="small-muted">Pilih layanan, stylist, dan tanggal.</span>';
    return;
  }

  slots.innerHTML = '<span class="small-muted">Memuat slot...</span>';
  const response = await fetch('/pelanggan/booking/slots?service_id=' + serviceId + '&stylist_id=' + stylistId + '&date=' + date);
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
    button.textContent = slot.start + ' - ' + slot.end + (slot.available ? '' : ' (Terisi)');
    button.disabled = !slot.available;
    if (slot.available) {
      button.onclick = () => {
        start_time.value = slot.start;
        document.querySelectorAll('#slots button').forEach((item) => {
          item.classList.remove('btn-primary', 'selected');
          if (!item.classList.contains('disabled')) {
            item.classList.add('btn-outline-primary');
          }
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
