<?= $this->extend('layouts/panel') ?>
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

<form method="post" action="<?= base_url('admin/booking/walkin') ?>" enctype="multipart/form-data" style="max-width:760px;">
  <?= csrf_field() ?>

  <div class="card-salon mb-2">
    <div class="h2 mb-2">Data pelanggan</div>
    <div class="row-salon cols-3">
      <div><label class="form-salon-label">Nama *</label><input class="form-salon-input" name="nama_pelanggan" required value="<?= esc(old('nama_pelanggan')) ?>"></div>
      <div><label class="form-salon-label">No. HP *</label><input class="form-salon-input" name="nomor_hp_pelanggan" required value="<?= esc(old('nomor_hp_pelanggan')) ?>" placeholder="08xxxxxxxxxx"></div>
      <div><label class="form-salon-label">Email *</label><input class="form-salon-input" type="email" name="email_pelanggan" required value="<?= esc(old('email_pelanggan')) ?>" placeholder="pelanggan@example.com"></div>
    </div>
    <div class="mt-2"><label class="form-salon-label">Catatan</label><textarea class="form-salon-textarea" name="catatan" rows="2"><?= esc(old('catatan')) ?></textarea></div>
  </div>

  <div class="card-salon mb-2">
    <div class="h2 mb-2">Layanan</div>
    <select class="form-salon-select" name="layanan_id" id="layanan_id" required>
      <option value="" disabled selected>Pilih layanan</option>
    </select>
  </div>

  <!-- Price Breakdown Card -->
  <div class="card-salon mb-2 d-none" id="priceBreakdownCard">
    <div class="h3 mb-2">Rincian Estimasi Biaya</div>
    <table class="table-salon" style="margin: 0; width: 100%;">
      <tbody>
        <tr>
          <td style="border:none; padding: 4px 0; color:var(--text-secondary);">Harga Normal</td>
          <td style="border:none; padding: 4px 0; text-align:right;" id="bdHargaNormal">Rp -</td>
        </tr>
        <tr id="bdPromoRow" class="d-none">
          <td style="border:none; padding: 4px 0; color:var(--promo);">Promo/Diskon</td>
          <td style="border:none; padding: 4px 0; text-align:right; color:var(--promo);" id="bdPromoDiskon">Rp -</td>
        </tr>
        <tr>
          <td style="border:none; padding: 4px 0; font-weight:bold;">Harga setelah Promo</td>
          <td style="border:none; padding: 4px 0; text-align:right; font-weight:bold;" id="bdHargaPromo">Rp -</td>
        </tr>
        <tr>
          <td style="border:none; padding: 4px 0; color:var(--text-secondary);">DP yang harus dibayar</td>
          <td style="border:none; padding: 4px 0; text-align:right;" id="bdDp">Rp -</td>
        </tr>
        <tr>
          <td style="border:none; padding: 4px 0; font-weight:bold; color:var(--gold);">Sisa Pembayaran</td>
          <td style="border:none; padding: 4px 0; text-align:right; font-weight:bold; color:var(--gold);" id="bdSisa">Rp -</td>
        </tr>
      </tbody>
    </table>
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
    <div class="slot-grid" id="slotGrid"><span class="caption">Pilih layanan & tanggal terlebih dahulu.</span></div>
    <input type="hidden" name="slot_mulai" id="slotInput" required>
  </div>

  <!-- DP Card -->
  <div class="card-salon mb-2" id="dpCard">
    <div class="h2 mb-1"><i class="bi bi-cash-coin" style="color:var(--gold);"></i> Pembayaran DP *</div>
    <div class="caption mb-2">
      DP yang harus dibayar: <strong id="dpAmountText" style="color:var(--gold);">Pilih layanan terlebih dahulu</strong>
      <span class="form-salon-help">Harga ≤ Rp 50.000 → DP penuh. Lebih dari itu → DP Rp 50.000.</span>
    </div>
    <label class="form-salon-label">Upload bukti bayar DP *</label>
    <input class="form-salon-input" type="file" name="bukti_dp" accept="image/png,image/jpeg,image/jpg,image/webp" required>
    <div class="form-salon-help">PNG / JPG / WEBP. Bukti bayar DP yang diterima langsung saat walk-in.</div>
  </div>

  <button class="btn-salon-primary" type="submit"><i class="bi bi-check2"></i> Buat booking</button>
</form>

<script>
const allSlots = <?= json_encode($all_slots) ?>;
const layanans = <?= json_encode($layanans) ?>;
const isWalkin = true;
let state = { layananId: null, durasi: null, tanggal: '<?= $today ?>', slot: null, booked: [], openMin: 8*60, closeMin: 19*60 };

function pad(n){return String(n).padStart(2,'0');} 
function toMin(t){const [h,m]=t.split(':').map(Number);return h*60+m;} 
function fromMin(m){return pad(Math.floor(m/60))+':'+pad(m%60);} 
function todayISO(){return new Date().toISOString().slice(0,10);} 
function nowM(){const d=new Date();return d.getHours()*60+d.getMinutes();}

function getPromoInfo(layanan, dateStr) {
  if (!layanan.promo_persen || parseInt(layanan.promo_persen, 10) <= 0) {
    return null;
  }
  const targetDate = dateStr;
  if (layanan.promo_mulai && targetDate < layanan.promo_mulai.substring(0, 10)) {
    return null;
  }
  if (layanan.promo_selesai && targetDate > layanan.promo_selesai.substring(0, 10)) {
    return null;
  }
  const hargaNormal = parseInt(layanan.harga, 10);
  const promoPersen = parseInt(layanan.promo_persen, 10);
  const hargaPromo = Math.round(hargaNormal * (100 - promoPersen) / 100);
  const diskonAmt = hargaNormal - hargaPromo;
  return {
    promo_name: layanan.promo_deskripsi || ('Promo ' + layanan.nama),
    promo_persen: promoPersen,
    harga_normal: hargaNormal,
    harga_promo: hargaPromo,
    diskon: diskonAmt
  };
}

function updateLayananDropdown() {
  const select = document.getElementById('layanan_id');
  const selectedValue = select.value;
  
  select.innerHTML = '<option value="" disabled selected>Pilih layanan</option>';
  
  layanans.forEach(l => {
    const opt = document.createElement('option');
    opt.value = l.id;
    opt.dataset.durasi = l.durasi_menit;
    
    const promo = getPromoInfo(l, state.tanggal);
    let text = l.nama + ' · ' + l.durasi_menit + ' menit · ';
    if (promo) {
      text += 'Promo Rp ' + promo.harga_promo.toLocaleString('id-ID') + ' dari Rp ' + promo.harga_normal.toLocaleString('id-ID');
      if (promo.promo_name) {
        text += ' (' + promo.promo_name + ')';
      }
    } else {
      text += 'Rp ' + parseInt(l.harga, 10).toLocaleString('id-ID');
    }
    opt.textContent = text;
    select.appendChild(opt);
  });
  
  if (selectedValue) {
    select.value = selectedValue;
  } else {
    select.value = "";
  }
}

function updatePriceBreakdown() {
  const card = document.getElementById('priceBreakdownCard');
  if (!state.layananId) {
    card.classList.add('d-none');
    document.getElementById('dpAmountText').textContent = 'Pilih layanan terlebih dahulu';
    return;
  }
  
  const l = layanans.find(x => x.id === state.layananId);
  if (!l) {
    card.classList.add('d-none');
    document.getElementById('dpAmountText').textContent = 'Pilih layanan terlebih dahulu';
    return;
  }
  
  card.classList.remove('d-none');
  
  const promo = getPromoInfo(l, state.tanggal);
  const hargaNormal = parseInt(l.harga, 10);
  const hargaFinal = promo ? promo.harga_promo : hargaNormal;
  const diskon = promo ? promo.diskon : 0;
  const dp = Math.min(hargaFinal, 50000);
  const sisa = hargaFinal - dp;
  
  document.getElementById('bdHargaNormal').textContent = 'Rp ' + hargaNormal.toLocaleString('id-ID');
  
  const promoRow = document.getElementById('bdPromoRow');
  if (diskon > 0) {
    promoRow.classList.remove('d-none');
    document.getElementById('bdPromoDiskon').textContent = '-Rp ' + diskon.toLocaleString('id-ID') + (promo.promo_name ? ' (' + promo.promo_name + ')' : '');
  } else {
    promoRow.classList.add('d-none');
  }
  
  document.getElementById('bdHargaPromo').textContent = 'Rp ' + hargaFinal.toLocaleString('id-ID');
  document.getElementById('bdDp').textContent = 'Rp ' + dp.toLocaleString('id-ID');
  document.getElementById('bdSisa').textContent = 'Rp ' + sisa.toLocaleString('id-ID');
  document.getElementById('dpAmountText').textContent = 'Rp ' + dp.toLocaleString('id-ID');
}

// Initialize options
updateLayananDropdown();

document.getElementById('layanan_id').onchange = (e) => {
  const opt = e.target.options[e.target.selectedIndex];
  state.layananId = parseInt(e.target.value, 10) || null;
  state.durasi = parseInt(opt.dataset.durasi || '0', 10) || null;
  updatePriceBreakdown();
  refreshSlots();
};

document.querySelectorAll('#dateStrip .date-strip-item').forEach((it) => {
  it.onclick = () => {
    state.tanggal = it.dataset.tanggal;
    document.getElementById('tanggalInput').value = state.tanggal;
    document.querySelectorAll('#dateStrip .date-strip-item').forEach((x) => x.classList.toggle('date-strip-item--selected', x === it));
    state.slot = null;
    document.getElementById('slotInput').value = '';
    updateLayananDropdown();
    updatePriceBreakdown();
    refreshSlots();
  };
  if (it.dataset.tanggal === state.tanggal) it.classList.add('date-strip-item--selected');
});

async function refreshSlots() {
  const grid = document.getElementById('slotGrid');
  if (!state.layananId || !state.tanggal) { grid.innerHTML = '<span class="caption">Pilih layanan & tanggal.</span>'; return; }
  grid.innerHTML = '<span class="caption">Memuat…</span>';
  const r = await fetch('<?= base_url('api/slots') ?>?layanan_id=' + state.layananId + '&tanggal=' + state.tanggal);
  const data = await r.json();
  if (data.error) { grid.innerHTML = '<span class="caption" style="color:var(--color-danger);">' + data.error + '</span>'; return; }
  state.booked = data.booked || []; state.durasi = data.durasi_menit;
  state.openMin = toMin(allSlots[0]);
  state.closeMin = toMin(allSlots[allSlots.length-1]) + 30;
  render();
}
function n(){return Math.ceil(state.durasi/30);} function insuf(s){const sm=toMin(s);const em=sm+state.durasi;if(em>state.closeMin) return true;for(let i=1;i<n();i++){if(state.booked.includes(fromMin(sm+i*30))) return true;}return false;} function held(s){if(!state.slot) return false;const sm=toMin(state.slot);const m=toMin(s);return m>sm && m<sm+state.durasi;}
let slotLock = false;
function pickSlot(s) {
  if (slotLock) return;
  slotLock = true;
  state.slot = (state.slot === s) ? null : s;
  document.getElementById('slotInput').value = state.slot || '';
  render();
  setTimeout(() => { slotLock = false; }, 180);
}
function render() {
  const grid = document.getElementById('slotGrid'); grid.innerHTML = '';
  const isToday = state.tanggal === todayISO(); const cur = nowM();
  allSlots.forEach((s) => { const d = document.createElement('div'); d.className='slot'; d.textContent=s; const m=toMin(s);
    let clickable = false;
    if (m<state.openMin||m>=state.closeMin) d.classList.add('slot--insufficient');
    else if (isToday && m<cur) d.classList.add('slot--past');
    else if (state.booked.includes(s)) d.classList.add('slot--booked');
    else if (insuf(s) && !(state.slot && s===state.slot)) d.classList.add('slot--insufficient');
    else {
      if (state.slot && s===state.slot) d.classList.add('slot--selected');
      else if (held(s)) d.classList.add('slot--held');
      else d.classList.add('slot--available');
      clickable = true;
    }
    if (clickable) d.addEventListener('click', (ev) => { ev.preventDefault(); pickSlot(s); });
    grid.appendChild(d);
  });
}
</script>

<?= $this->endSection() ?>
