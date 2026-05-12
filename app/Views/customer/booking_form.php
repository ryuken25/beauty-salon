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
            <option value="<?= $s['id'] ?>" data-duration="<?= (int) $s['duration_minutes'] ?>"><?= esc($s['name']) ?> · <?= $s['duration_minutes'] ?> menit · Rp<?= number_format($s['price'], 0, ',', '.') ?></option>
          <?php endforeach ?>
        </select>
        <div id="durationBadge" class="mt-2" style="display:none;">
          <span class="gold-badge badge rounded-pill">Durasi layanan: <span id="durationLabel">0</span> menit</span>
        </div>
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
        <label class="form-label">Slot Mulai (grid 30 menit)</label>
        <div id="slots" class="d-flex flex-wrap gap-2 my-2">
          <span class="small-muted">Pilih layanan, stylist, dan tanggal.</span>
        </div>
        <input type="hidden" name="start_time" id="start_time" required>
        <div id="occupiedHint" class="small-muted mt-2"></div>
        <div class="small-muted">Slot mulai akan otomatis menahan blok sesuai durasi layanan. Slot terisi dinonaktifkan.</div>
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
const durationBadge = document.getElementById('durationBadge');
const durationLabel = document.getElementById('durationLabel');
const occupiedHint = document.getElementById('occupiedHint');

function currentDuration() {
  const opt = service_id.options[service_id.selectedIndex];
  return opt ? parseInt(opt.dataset.duration || '0', 10) : 0;
}

function refreshDurationBadge() {
  const duration = currentDuration();
  if (duration > 0) {
    durationLabel.textContent = duration;
    durationBadge.style.display = '';
  } else {
    durationBadge.style.display = 'none';
  }
}

function updateOccupiedHint() {
  if (!start_time.value) { occupiedHint.textContent = ''; return; }
  const duration = currentDuration();
  if (!duration) { occupiedHint.textContent = ''; return; }
  const [h, m] = start_time.value.split(':').map(Number);
  const startMin = h * 60 + m;
  const endMin = startMin + duration;
  const endH = String(Math.floor(endMin / 60)).padStart(2, '0');
  const endM = String(endMin % 60).padStart(2, '0');
  occupiedHint.innerHTML = '🔒 Slot yang akan ditahan: <strong>' + start_time.value + ' – ' + endH + ':' + endM + '</strong> (' + duration + ' menit)';
}

async function loadSlots() {
  const serviceId = service_id.value;
  const stylistId = stylist_id.value;
  const date = booking_date.value;
  start_time.value = '';
  occupiedHint.textContent = '';
  refreshDurationBadge();

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
    button.textContent = slot.start + ' – ' + slot.end + (slot.available ? '' : ' (Terisi)');
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
        updateOccupiedHint();
      };
    }
    slots.appendChild(button);
  });
}

[service_id, stylist_id, booking_date].forEach((element) => element.addEventListener('change', loadSlots));
service_id.addEventListener('change', refreshDurationBadge);
</script>

<?= $this->endSection() ?>
