<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$bulanShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$hariShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$today = date('Y-m-d');
?>

<section class="section-header">
  <div class="h1">Booking Layanan</div>
  <span class="tagline">Pilih layanan, tanggal, dan jam yang nyaman untuk Anda</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<form method="post" action="<?= base_url('booking') ?>" id="bookingForm" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- Honeypot: hidden from humans, bots fill it -->
  <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
  </div>

  <div class="card-salon mb-3">
    <div class="h2 mb-2">Booking sebagai</div>
    <div class="caption">
      <i class="bi bi-person-check" style="color:var(--gold);"></i>
      <strong><?= esc($akun_nama) ?></strong> · <?= esc($akun_hp) ?>
      &nbsp;<a class="caption" href="<?= base_url('pelanggan/dashboard') ?>" style="text-decoration:underline;">(bukan Anda?)</a>
    </div>
    <div class="mt-2">
      <label class="form-salon-label">Catatan (opsional)</label>
      <textarea class="form-salon-textarea" name="catatan" rows="2" placeholder="Permintaan khusus, dll"><?= esc(old('catatan')) ?></textarea>
    </div>
  </div>


  <div class="card-salon mb-3">
    <div class="h2 mb-2">Pilih layanan</div>
    <div class="row-salon cols-2" id="layananList">
      <?php foreach ($layanans as $l): ?>
        <label class="card-salon" data-id="<?= $l['id'] ?>" data-durasi="<?= (int) $l['durasi_menit'] ?>" style="cursor:pointer;">
          <input type="radio" name="layanan_id" value="<?= $l['id'] ?>" style="display:none;" <?= ($preselect_layanan_id === (int) $l['id'] || old('layanan_id') == $l['id']) ? 'checked' : '' ?>>
          <div class="flex justify-between items-center gap-1">
            <div style="flex:1;">
              <div class="h3"><?= esc($l['nama']) ?></div>
              <div class="tagline"><?= esc($l['kategori']) ?> · <?= esc($l['durasi_menit']) ?> menit</div>
            </div>
            <div class="text-right">
              <div class="h3">Rp <?= number_format((int) $l['harga'], 0, ',', '.') ?></div>
              <i class="bi bi-check-circle-fill" style="color:var(--color-gold); display:none;"></i>
            </div>
          </div>
        </label>
      <?php endforeach ?>
    </div>
  </div>

  <div class="card-salon mb-3">
    <div class="h2 mb-2">Pilih tanggal</div>
    <div class="date-strip" id="dateStrip">
      <?php foreach ($dates as $d):
        $ts = strtotime($d);
        $isToday = $d === $today;
      ?>
        <div class="date-strip-item <?= $isToday ? 'date-strip-item--today' : '' ?>" data-tanggal="<?= esc($d) ?>">
          <div class="date-strip-item__hari"><?= $isToday ? 'Hari ini' : $hariShort[(int) date('w', $ts)] ?></div>
          <div class="date-strip-item__tanggal"><?= date('d', $ts) ?></div>
          <div class="date-strip-item__bulan"><?= $bulanShort[(int) date('n', $ts) - 1] ?></div>
        </div>
      <?php endforeach ?>
    </div>
    <input type="hidden" name="tanggal" id="tanggalInput" value="<?= esc(old('tanggal') ?? $today) ?>">
  </div>

  <div class="card-salon mb-3">
    <div class="flex justify-between items-center mb-1">
      <div class="h2">Jam mulai</div>
      <div class="slot-legend">
        <span><span class="slot-legend__dot slot-legend__dot--available"></span>Tersedia</span>
        <span><span class="slot-legend__dot slot-legend__dot--selected"></span>Dipilih</span>
        <span><span class="slot-legend__dot slot-legend__dot--held"></span>Ditahan</span>
        <span><span class="slot-legend__dot slot-legend__dot--booked"></span>Terisi</span>
      </div>
    </div>
    <div class="slot-grid" id="slotGrid">
      <span class="caption">Pilih layanan dan tanggal dulu.</span>
    </div>
    <input type="hidden" name="slot_mulai" id="slotInput" value="<?= esc(old('slot_mulai')) ?>">
    <div id="slotInfo" class="caption mt-1"></div>
  </div>

  <!-- DP info + upload bukti -->
  <div class="card-salon mb-3" id="dpCard">
    <div class="h2 mb-1"><i class="bi bi-cash-coin" style="color:var(--gold);"></i> Pembayaran DP</div>
    <div class="caption mb-2">
      DP yang harus dibayar: <strong id="dpAmountText" style="color:var(--gold);">Pilih layanan dulu</strong>
      <span class="form-salon-help">Harga ≤ Rp 50.000 → DP penuh. Lebih dari itu → DP Rp 50.000.</span>
    </div>
    <?php if (! empty($info_pembayaran_dp)): ?>
      <div class="alert-salon alert-salon--info" style="white-space:pre-line;"><?= esc($info_pembayaran_dp) ?></div>
    <?php endif ?>
    <?php if (! empty($qris_image_path)): ?>
      <div class="text-center mb-2">
        <img src="<?= base_url($qris_image_path) ?>" alt="QRIS" style="max-width:220px; border:1px solid var(--gold-border); border-radius:8px; background:#fff; padding:6px;">
      </div>
    <?php endif ?>
    <label class="form-salon-label">Upload bukti transfer / QRIS *</label>
    <input class="form-salon-input" type="file" name="bukti_dp" accept="image/png,image/jpeg,image/jpg,image/webp" required>
    <div class="form-salon-help">PNG / JPG / WEBP, maksimal 2 MB. Admin akan memverifikasi sebelum booking dikonfirmasi.</div>
  </div>

  <div class="card-salon" id="summaryBar" style="position:sticky; bottom:0;">
    <div class="flex justify-between items-center flex-wrap gap-2">
      <div id="summaryText" class="caption">Pilih layanan, tanggal, dan jam untuk melihat ringkasan.</div>
      <button type="submit" class="btn-salon-primary" id="submitBtn" disabled>Konfirmasi booking →</button>
    </div>
  </div>
</form>

<script>
const allSlots = <?= json_encode($all_slots) ?>;
let state = { layananId: null, durasi: null, tanggal: document.getElementById('tanggalInput').value, slot: null, booked: [], openMin: 8*60, closeMin: 19*60 };

function pad(n) { return String(n).padStart(2, '0'); }
function toMin(t) { const [h, m] = t.split(':').map(Number); return h*60 + m; }
function fromMin(m) { return pad(Math.floor(m/60)) + ':' + pad(m%60); }
function todayISO() { return new Date().toISOString().slice(0,10); }
function nowMinutes() { const d = new Date(); return d.getHours()*60 + d.getMinutes(); }

function pickLayanan(card) {
  state.layananId = parseInt(card.dataset.id, 10);
  state.durasi = parseInt(card.dataset.durasi, 10);
  document.querySelectorAll('#layananList .card-salon').forEach((c) => {
    c.classList.toggle('card-salon--active', c === card);
    c.querySelector('.bi-check-circle-fill').style.display = c === card ? 'inline-block' : 'none';
  });
  card.querySelector('input').checked = true;
  // DP rule: ≤ 50k → full, else 50k.
  const hargaStr = card.querySelectorAll('.h3')[1].textContent.replace(/[^0-9]/g, '');
  const harga = parseInt(hargaStr, 10) || 0;
  const dp = Math.min(harga, 50000);
  document.getElementById('dpAmountText').textContent = 'Rp ' + dp.toLocaleString('id-ID');
  refreshSlots();
}

function pickDate(item) {
  state.tanggal = item.dataset.tanggal;
  document.getElementById('tanggalInput').value = state.tanggal;
  document.querySelectorAll('#dateStrip .date-strip-item').forEach((x) => x.classList.toggle('date-strip-item--selected', x === item));
  state.slot = null;
  document.getElementById('slotInput').value = '';
  refreshSlots();
}

async function refreshSlots() {
  const grid = document.getElementById('slotGrid');
  if (!state.layananId || !state.tanggal) { grid.innerHTML = '<span class="caption">Pilih layanan dan tanggal dulu.</span>'; updateSummary(); return; }
  grid.innerHTML = '<span class="caption">Memuat slot...</span>';
  try {
    const r = await fetch('<?= base_url('api/slots') ?>?layanan_id=' + state.layananId + '&tanggal=' + state.tanggal);
    const data = await r.json();
    if (data.error) { grid.innerHTML = '<span class="caption" style="color:var(--color-danger);">' + data.error + '</span>'; return; }
    state.booked = data.booked || [];
    state.durasi = data.durasi_menit;
    state.openMin = toMin(allSlots[0]);
    state.closeMin = toMin(allSlots[allSlots.length-1]) + 30;
    renderGrid();
  } catch (e) {
    grid.innerHTML = '<span class="caption" style="color:var(--color-danger);">Gagal memuat slot.</span>';
  }
}

function jumlahSlot() { return Math.ceil(state.durasi / 30); }

function isInsufficient(startSlot) {
  const startMin = toMin(startSlot);
  const endMin = startMin + state.durasi;
  if (endMin > state.closeMin) return true;
  const n = jumlahSlot();
  for (let i = 1; i < n; i++) {
    if (state.booked.includes(fromMin(startMin + i*30))) return true;
  }
  return false;
}

function isHeld(slot) {
  if (!state.slot) return false;
  const startMin = toMin(state.slot);
  const slotMin = toMin(slot);
  return slotMin > startMin && slotMin < startMin + state.durasi;
}

let slotClickLock = false;
function pickSlot(slot) {
  if (slotClickLock) return;
  slotClickLock = true;
  state.slot = (state.slot === slot) ? null : slot;
  document.getElementById('slotInput').value = state.slot || '';
  renderGrid();
  updateSummary();
  setTimeout(() => { slotClickLock = false; }, 180);
}

function renderGrid() {
  const grid = document.getElementById('slotGrid');
  grid.innerHTML = '';
  const isToday = state.tanggal === todayISO();
  const nowM = nowMinutes();
  allSlots.forEach((slot) => {
    const d = document.createElement('div');
    d.className = 'slot';
    d.textContent = slot;
    const m = toMin(slot);
    let clickable = false;
    if (m < state.openMin || m >= state.closeMin) {
      d.classList.add('slot--insufficient');
    } else if (isToday && m < nowM) {
      d.classList.add('slot--past');
    } else if (state.booked.includes(slot)) {
      d.classList.add('slot--booked');
    } else if (isInsufficient(slot) && !(state.slot && slot === state.slot)) {
      d.classList.add('slot--insufficient');
    } else {
      if (state.slot && slot === state.slot) d.classList.add('slot--selected');
      else if (isHeld(slot)) d.classList.add('slot--held');
      else d.classList.add('slot--available');
      clickable = true;
    }
    if (clickable) {
      d.addEventListener('click', (ev) => { ev.preventDefault(); pickSlot(slot); }, { once: false });
    }
    grid.appendChild(d);
  });
  updateSummary();
}

function updateSummary() {
  const layananEl = state.layananId ? document.querySelector('#layananList .card-salon[data-id="' + state.layananId + '"]') : null;
  const layananName = layananEl ? layananEl.querySelector('.h3').textContent : '—';
  const layananHarga = layananEl ? layananEl.querySelectorAll('.h3')[1].textContent : '';
  const info = document.getElementById('slotInfo');
  if (state.slot && state.durasi) {
    const endMin = toMin(state.slot) + state.durasi;
    info.innerHTML = '🔒 Slot ditahan: <strong>' + state.slot + ' – ' + fromMin(endMin) + '</strong> (' + state.durasi + ' menit)';
  } else { info.textContent = ''; }
  const text = document.getElementById('summaryText');
  if (state.layananId && state.tanggal && state.slot) {
    const endMin = toMin(state.slot) + state.durasi;
    text.innerHTML = '<strong>' + layananName + '</strong> · ' + state.tanggal + ' · ' + state.slot + '–' + fromMin(endMin) + ' · ' + layananHarga;
    document.getElementById('submitBtn').disabled = false;
  } else {
    text.textContent = 'Pilih layanan, tanggal, dan jam untuk melihat ringkasan.';
    document.getElementById('submitBtn').disabled = true;
  }
}

document.querySelectorAll('#layananList .card-salon').forEach((c) => { c.onclick = () => pickLayanan(c); if (c.querySelector('input').checked) pickLayanan(c); });
document.querySelectorAll('#dateStrip .date-strip-item').forEach((c) => { c.onclick = () => pickDate(c); if (c.dataset.tanggal === state.tanggal) pickDate(c); });
</script>

<?= $this->endSection() ?>
