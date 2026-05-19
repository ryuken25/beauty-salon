<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$cls = 'badge-salon--' . ($booking['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $booking['status']));
$lbl = ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$booking['status']];
?>

<div class="page-header-salon">
  <div>
    <span class="caption" style="letter-spacing:2px;"><?= esc($booking['kode_booking']) ?></span>
    <div class="h1"><?= esc($booking['nama_pelanggan']) ?></div>
    <span class="badge-salon <?= $cls ?>"><?= esc($lbl) ?></span>
    <?php if ($booking['wa_sent']): ?> <span class="badge-salon badge-salon--completed"><i class="bi bi-whatsapp"></i> WA terkirim</span><?php endif ?>
  </div>
  <a class="btn-salon-ghost" href="<?= base_url('admin/booking') ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="split-60-40">
  <div>
    <div class="card-salon mb-2">
      <div class="h2 mb-2">Informasi booking</div>
      <table style="width:100%; font-size:0.875rem;">
        <tr><td class="label">No. HP</td><td class="text-right"><?= esc($booking['nomor_hp_pelanggan']) ?></td></tr>
        <tr><td class="label">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
        <tr><td class="label">Durasi</td><td class="text-right"><?= esc($booking['durasi_menit']) ?> menit</td></tr>
        <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime($booking['tanggal']))) ?></td></tr>
        <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
        <tr><td class="label">Sumber</td><td class="text-right"><?= esc(ucfirst($booking['sumber'])) ?></td></tr>
        <tr><td class="label">Harga</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
        <?php if ($booking['catatan']): ?><tr><td class="label">Catatan</td><td class="text-right"><?= esc($booking['catatan']) ?></td></tr><?php endif ?>
        <?php if ($booking['rejection_reason']): ?><tr><td class="label">Alasan tolak</td><td class="text-right"><?= esc($booking['rejection_reason']) ?></td></tr><?php endif ?>
        <?php if (! empty($booking['cancellation_reason'])): ?><tr><td class="label">Alasan batal</td><td class="text-right"><?= esc($booking['cancellation_reason']) ?></td></tr><?php endif ?>
        <?php if ($booking['verified_via']): ?><tr><td class="label">Verifikasi via</td><td class="text-right"><?= esc($booking['verified_via']) ?></td></tr><?php endif ?>
      </table>
    </div>

    <div class="card-salon mb-2">
      <div class="h2 mb-2">Pesan WhatsApp manual</div>
      <textarea id="waMsg" class="form-salon-textarea" rows="7" readonly><?= esc($wa_message) ?></textarea>
      <div class="flex flex-wrap gap-1 mt-2">
        <button type="button" class="btn-salon-secondary" onclick="navigator.clipboard.writeText(document.getElementById('waMsg').value).then(()=>alert('Pesan disalin.'))"><i class="bi bi-clipboard"></i> Salin pesan</button>
        <a class="btn-salon-success" target="_blank" rel="noopener" href="<?= esc($wa_link) ?>"><i class="bi bi-whatsapp"></i> Buka WhatsApp</a>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/wa-sent') ?>">
          <?= csrf_field() ?>
          <button class="btn-salon-ghost" type="submit"><i class="bi bi-check2"></i> Tandai sudah dikirim</button>
        </form>
      </div>
    </div>

    <div class="card-salon">
      <div class="h2 mb-2">Riwayat aktivitas</div>
      <?= view('partials/booking_timeline', ['logs' => $logs]) ?>
    </div>
  </div>

  <div>
    <div class="card-salon">
      <div class="h2 mb-2">Aksi</div>
      <?php if ($booking['status'] === 'pending_verification'): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/verify') ?>" class="mb-2">
          <?= csrf_field() ?>
          <button class="btn-salon-primary btn-salon--full" type="submit"><i class="bi bi-check-circle"></i> Verifikasi</button>
        </form>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/reject') ?>" class="mb-2">
          <?= csrf_field() ?>
          <input class="form-salon-input mb-1" type="text" name="rejection_reason" placeholder="Alasan penolakan (opsional)">
          <button class="btn-salon-danger btn-salon--full" type="submit"><i class="bi bi-x-circle"></i> Tolak</button>
        </form>
      <?php endif ?>
      <?php if ($booking['status'] === 'accepted'): ?>
        <button type="button" class="btn-salon-primary btn-salon--full mb-2" data-bs-toggle="modal" data-bs-target="#modalComplete">
          <i class="bi bi-trophy"></i> Selesaikan + transaksi
        </button>
      <?php endif ?>
      <?php if (in_array($booking['status'], ['pending_verification', 'accepted'], true)): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/cancel') ?>" onsubmit="return confirm('Batalkan booking ini?');">
          <?= csrf_field() ?>
          <button class="btn-salon-danger btn-salon--full" type="submit"><i class="bi bi-slash-circle"></i> Batalkan</button>
        </form>
      <?php endif ?>
      <?php if (in_array($booking['status'], ['completed', 'rejected', 'cancelled'], true)): ?>
        <div class="caption">Booking sudah final. Lihat riwayat di timeline.</div>
      <?php endif ?>
    </div>
  </div>
</div>

<?php if ($booking['status'] === 'accepted'): ?>
<!-- Modal: Selesaikan booking + transaksi -->
<div class="modal fade" id="modalComplete" tabindex="-1" aria-labelledby="modalCompleteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="formComplete" method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/complete') ?>">
        <?= csrf_field() ?>
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" id="modalCompleteLabel" style="font-family:var(--font-display); color:var(--text-primary);">
            <i class="bi bi-trophy" style="color:var(--gold);"></i> Selesaikan booking
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <!-- Ringkasan booking -->
          <div class="card-salon mb-3" style="background:var(--bg);">
            <div class="label mb-1">Ringkasan</div>
            <table style="width:100%; font-size:0.875rem;">
              <tr><td class="label" style="padding:0.2rem 0;">Kode</td><td class="text-right"><?= esc($booking['kode_booking']) ?></td></tr>
              <tr><td class="label" style="padding:0.2rem 0;">Pelanggan</td><td class="text-right"><?= esc($booking['nama_pelanggan']) ?></td></tr>
              <tr><td class="label" style="padding:0.2rem 0;">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
              <tr><td class="label" style="padding:0.2rem 0;">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime($booking['tanggal']))) ?> · <?= esc(substr($booking['slot_mulai'], 0, 5)) ?>–<?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
            </table>
          </div>

          <!-- Toggle: Catat manual -->
          <label class="card-salon mb-3 flex items-center gap-1" style="cursor:pointer; background:var(--bg);">
            <input type="checkbox" id="manualMode" name="manual_mode" value="1" style="margin-right:0.6rem; width:18px; height:18px; accent-color:var(--gold);">
            <span>
              <span style="font-weight:600;">Catat manual</span>
              <span class="caption" style="display:block;">Centang kalau ingin memasukkan nominal sendiri (paket khusus, harga negosiasi, dll). Catatan wajib diisi.</span>
            </span>
          </label>

          <!-- === Mode otomatis === -->
          <div id="autoBlock">
            <div class="mb-2">
              <label class="form-salon-label">Base Price (otomatis dari layanan)</label>
              <div class="input-group">
                <span class="input-group-text" style="background:var(--surface); color:var(--gold); border:1px solid rgba(201,166,107,0.2); border-right:none; border-top-right-radius:0; border-bottom-right-radius:0;">Rp</span>
                <input type="text" id="basePriceDisplay" class="form-salon-input" value="<?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?>" readonly style="border-top-left-radius:0; border-bottom-left-radius:0; cursor:not-allowed;">
              </div>
            </div>

            <div class="mb-2">
              <label class="form-salon-label">Biaya tambahan (opsional)</label>
              <div class="input-group">
                <span class="input-group-text" style="background:var(--surface); color:var(--gold); border:1px solid rgba(201,166,107,0.2); border-right:none; border-top-right-radius:0; border-bottom-right-radius:0;">Rp</span>
                <input type="number" id="additionalPrice" name="additional_price" class="form-salon-input" value="0" min="0" max="999999998" step="1" style="border-top-left-radius:0; border-bottom-left-radius:0;">
              </div>
              <div class="form-salon-help">Jasa ekstra, produk tambahan, dll. Wajib isi catatan jika diisi.</div>
            </div>
          </div>

          <!-- === Mode manual === -->
          <div id="manualBlock" style="display:none;">
            <div class="mb-2">
              <label class="form-salon-label">Nominal manual <span style="color:var(--color-danger);">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="background:var(--surface); color:var(--gold); border:1px solid rgba(201,166,107,0.2); border-right:none; border-top-right-radius:0; border-bottom-right-radius:0;">Rp</span>
                <input type="number" id="manualNominal" name="manual_nominal" class="form-salon-input" placeholder="Contoh: 400000" min="1" max="999999998" step="1" disabled style="border-top-left-radius:0; border-bottom-left-radius:0;">
              </div>
              <div class="form-salon-help">Nominal yang ditagih ke pelanggan (paket / harga khusus).</div>
            </div>
          </div>

          <!-- Catatan (shared, tampil saat manual, atau saat additional > 0) -->
          <div class="mb-2" id="noteWrapper" style="display:none;">
            <label class="form-salon-label" id="noteLabel">Catatan <span style="color:var(--color-danger);">*</span></label>
            <textarea id="noteField" name="note" class="form-salon-textarea" rows="2" maxlength="500" placeholder=""></textarea>
            <div class="form-salon-help">Maks 500 karakter.</div>
          </div>

          <!-- Metode bayar (shared) -->
          <div class="mb-2">
            <label class="form-salon-label">Metode bayar</label>
            <select class="form-salon-select" name="metode_bayar" id="metodeBayar">
              <option value="cash">Cash</option>
              <option value="transfer">Transfer</option>
              <option value="qris">QRIS</option>
            </select>
          </div>

          <!-- Total (live) -->
          <div class="card-salon" style="background:var(--bg); border-color:var(--gold);">
            <div class="flex justify-between items-center">
              <span class="label" id="totalLabel">Total</span>
              <span id="totalDisplay" style="font-family:var(--font-display); font-size:1.71rem; font-weight:600; color:var(--gold);">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></span>
            </div>
          </div>

        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gold-border); gap:0.5rem;">
          <button type="button" class="btn-salon-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-salon-primary" id="submitComplete">
            <i class="bi bi-check2-circle"></i> Konfirmasi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const BASE_PRICE = <?= (int) $booking['harga_layanan'] ?>;
  const fmt = (n) => 'Rp ' + n.toLocaleString('id-ID');

  const manualCb     = document.getElementById('manualMode');
  const autoBlock    = document.getElementById('autoBlock');
  const manualBlock  = document.getElementById('manualBlock');
  const additional   = document.getElementById('additionalPrice');
  const manualInput  = document.getElementById('manualNominal');
  const noteWrapper  = document.getElementById('noteWrapper');
  const noteLabel    = document.getElementById('noteLabel');
  const noteField    = document.getElementById('noteField');
  const totalLabel   = document.getElementById('totalLabel');
  const totalEl      = document.getElementById('totalDisplay');
  const submitBtn    = document.getElementById('submitComplete');
  const form         = document.getElementById('formComplete');

  function recalc() {
    if (manualCb.checked) {
      const v = Math.max(0, parseInt(manualInput.value || '0', 10) || 0);
      totalEl.textContent = fmt(v);
    } else {
      const add = Math.max(0, parseInt(additional.value || '0', 10) || 0);
      totalEl.textContent = fmt(BASE_PRICE + add);
    }
  }

  function syncMode() {
    const manual = manualCb.checked;

    // Toggle block visibility
    autoBlock.style.display    = manual ? 'none' : '';
    manualBlock.style.display  = manual ? '' : 'none';

    // Enable/disable so disabled-fields are not submitted (avoid stray data)
    additional.disabled  = manual;
    manualInput.disabled = !manual;

    // Note visibility + required-state
    if (manual) {
      noteWrapper.style.display = '';
      noteLabel.innerHTML = 'Catatan manual <span style="color:var(--color-danger);">*</span>';
      noteField.placeholder = 'Wajib diisi: deskripsi paket / alasan harga khusus';
      noteField.required = true;
      manualInput.required = true;
      totalLabel.textContent = 'Total (manual)';
    } else {
      const add = Math.max(0, parseInt(additional.value || '0', 10) || 0);
      const showNote = add > 0;
      noteWrapper.style.display = showNote ? '' : 'none';
      noteLabel.innerHTML = 'Catatan biaya tambahan <span style="color:var(--color-danger);">*</span>';
      noteField.placeholder = 'Wajib diisi: rincian biaya tambahan';
      noteField.required = showNote;
      manualInput.required = false;
      totalLabel.textContent = 'Total';
    }

    recalc();
  }

  additional.addEventListener('input', syncMode);
  manualInput.addEventListener('input', recalc);
  manualCb.addEventListener('change', syncMode);

  form.addEventListener('submit', () => {
    // Use setTimeout so the form fires its submit before we disable the button
    // (avoids the rare browser quirk where disabling the submitter cancels it).
    setTimeout(() => {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
    }, 0);
  });

  // Initial state
  syncMode();
})();
</script>
<?php endif ?>

<?= $this->endSection() ?>
