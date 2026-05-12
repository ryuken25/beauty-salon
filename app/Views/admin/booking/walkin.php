<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$bulanShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$hariShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$today = date('Y-m-d');
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Walk-in baru</div>
    <div class="tagline">Booking offline pelanggan walk-in</div>
  </div>
</div>

<form method="post" action="<?= base_url('admin/booking/walkin') ?>" style="max-width:760px;">
  <?= csrf_field() ?>

  <div class="card-salon mb-2">
    <div class="h2 mb-2">Data pelanggan</div>
    <div class="row-salon cols-2">
      <div><label class="form-salon-label">Nama *</label><input class="form-salon-input" name="nama_pelanggan" required value="<?= esc(old('nama_pelanggan')) ?>"></div>
      <div><label class="form-salon-label">No. HP *</label><input class="form-salon-input" name="nomor_hp_pelanggan" required value="<?= esc(old('nomor_hp_pelanggan')) ?>" placeholder="08xxxxxxxxxx"></div>
    </div>
    <div class="mt-2"><label class="form-salon-label">Catatan</label><textarea class="form-salon-textarea" name="catatan" rows="2"><?= esc(old('catatan')) ?></textarea></div>
  </div>

  <div class="card-salon mb-2">
    <div class="h2 mb-2">Layanan</div>
    <select class="form-salon-select" name="layanan_id" id="layanan_id" required>
      <option value="">Pilih layanan</option>
      <?php foreach ($layanans as $l): ?>
        <option value="<?= $l['id'] ?>" data-durasi="<?= $l['durasi_menit'] ?>"><?= esc($l['nama']) ?> · <?= $l['durasi_menit'] ?> menit · Rp <?= number_format((int) $l['harga'], 0, ',', '.') ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="card-salon mb-2">
    <div class="h2 mb-2">Tanggal</div>
    <div class="date-strip" id="dateStrip">
      <?php foreach ($dates as $d): $ts = strtotime($d); $isToday = $d === $today; ?>
        <div class="date-strip-item <?= $isToday ? 'date-strip-item--today' : '' ?>" data-tanggal="<?= esc($d) ?>">
          <div class="date-strip-item__hari"><?= $isToday ? 'Hari ini' : $hariShort[(int) date('w', $ts)] ?></div>
          <div class="date-strip-item__tanggal"><?= date('d', $ts) ?></div>
          <div class="date-strip-item__bulan"><?= $bulanShort[(int) date('n', $ts) - 1] ?></div>
        </div>
      <?php endforeach ?>
    </div>
    <input type="hidden" name="tanggal" id="tanggalInput" value="<?= esc($today) ?>">
  </div>

  <div class="card-salon mb-2">
    <div class="h2 mb-2">Jam mulai</div>
    <div class="slot-grid" id="slotGrid"><span class="caption">Pilih layanan & tanggal.</span></div>
    <input type="hidden" name="slot_mulai" id="slotInput" required>
  </div>

  <button class="btn-salon-primary" type="submit"><i class="bi bi-check2"></i> Buat booking</button>
</form>

<script>
const allSlots = <?= json_encode($all_slots) ?>;
let state = { layananId: null, durasi: null, tanggal: '<?= $today ?>', slot: null, booked: [], openMin: 8*60, closeMin: 19*60 };

function pad(n){return String(n).padStart(2,'0');} function toMin(t){const [h,m]=t.split(':').map(Number);return h*60+m;} function fromMin(m){return pad(Math.floor(m/60))+':'+pad(m%60);} function todayISO(){return new Date().toISOString().slice(0,10);} function nowM(){const d=new Date();return d.getHours()*60+d.getMinutes();}

document.getElementById('layanan_id').onchange = (e) => { const opt = e.target.options[e.target.selectedIndex]; state.layananId = parseInt(e.target.value,10)||null; state.durasi = parseInt(opt.dataset.durasi||'0',10)||null; refreshSlots(); };
document.querySelectorAll('#dateStrip .date-strip-item').forEach((it) => { it.onclick = () => { state.tanggal = it.dataset.tanggal; document.getElementById('tanggalInput').value = state.tanggal; document.querySelectorAll('#dateStrip .date-strip-item').forEach((x)=>x.classList.toggle('date-strip-item--selected', x===it)); state.slot=null; document.getElementById('slotInput').value=''; refreshSlots(); }; if (it.dataset.tanggal === state.tanggal) it.classList.add('date-strip-item--selected'); });

async function refreshSlots() {
  const grid = document.getElementById('slotGrid');
  if (!state.layananId || !state.tanggal) { grid.innerHTML = '<span class="caption">Pilih layanan & tanggal.</span>'; return; }
  grid.innerHTML = '<span class="caption">Memuat…</span>';
  const r = await fetch('<?= base_url('api/slots') ?>?layanan_id=' + state.layananId + '&tanggal=' + state.tanggal);
  const data = await r.json();
  if (data.error) { grid.innerHTML = '<span class="caption" style="color:var(--color-danger);">' + data.error + '</span>'; return; }
  state.booked = data.booked || []; state.durasi = data.durasi_menit;
  state.openMin = data.stylist_open ? toMin(data.stylist_open) : toMin(allSlots[0]);
  state.closeMin = data.stylist_close ? toMin(data.stylist_close) : (toMin(allSlots[allSlots.length-1])+30);
  render();
}
function n(){return Math.ceil(state.durasi/30);} function insuf(s){const sm=toMin(s);const em=sm+state.durasi;if(em>state.closeMin) return true;for(let i=1;i<n();i++){if(state.booked.includes(fromMin(sm+i*30))) return true;}return false;} function held(s){if(!state.slot) return false;const sm=toMin(state.slot);const m=toMin(s);return m>sm && m<sm+state.durasi;}
function render() {
  const grid = document.getElementById('slotGrid'); grid.innerHTML = '';
  const isToday = state.tanggal === todayISO(); const cur = nowM();
  allSlots.forEach((s) => { const d = document.createElement('div'); d.className='slot'; d.textContent=s; const m=toMin(s);
    if (m<state.openMin||m>=state.closeMin) d.classList.add('slot--insufficient');
    else if (isToday && m<cur) d.classList.add('slot--past');
    else if (state.booked.includes(s)) d.classList.add('slot--booked');
    else if (state.slot && s===state.slot) d.classList.add('slot--selected');
    else if (held(s)) d.classList.add('slot--held');
    else if (insuf(s)) d.classList.add('slot--insufficient');
    else { d.classList.add('slot--available'); d.onclick = () => { state.slot=s; document.getElementById('slotInput').value=s; render(); }; }
    grid.appendChild(d);
  });
}
</script>

<?= $this->endSection() ?>
