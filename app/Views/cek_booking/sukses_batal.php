<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div style="max-width:480px; margin:1.5rem auto;">
  <div class="text-center">
    <div class="success-ring"><i class="bi bi-check-circle"></i></div>
    <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-stars ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
    <div class="h1 mb-1">Pembatalan Berhasil Dicatat</div>
    <div class="caption">Booking <strong><?= esc($booking['kode_booking']) ?></strong> sudah dibatalkan dan slot waktunya dilepas.</div>
  </div>

  <div class="card-salon mt-3" style="text-align:center;">
    <div class="caption mb-2">Langkah terakhir: beritahu admin lewat WhatsApp agar pembatalan tercatat di sisi salon.</div>

    <a class="btn-salon-success btn-salon--full" id="waBtn" target="_blank" rel="noopener" href="<?= esc($wa_link) ?>">
      <i class="bi bi-whatsapp"></i> Buka WhatsApp &amp; Kirim Pesan
    </a>
    <div class="caption mt-1" id="countdownText">Membuka WhatsApp otomatis dalam <span id="cd">3</span> detik…</div>

    <textarea id="waMsg" class="form-salon-textarea mt-2" rows="6" readonly><?= esc($wa_text) ?></textarea>
    <button type="button" class="btn-salon-secondary btn-salon--sm mt-1" id="copyBtn">
      <i class="bi bi-clipboard"></i> Salin Pesan
    </button>
    <div class="form-salon-help">Pakai tombol "Salin Pesan" kalau WhatsApp tidak terbuka otomatis.</div>
  </div>

  <div class="text-center mt-3">
    <a class="btn-salon-ghost" href="<?= base_url('/') ?>"><i class="bi bi-house"></i> Kembali ke Beranda</a>
  </div>
</div>

<script>
(function () {
  const waLink = <?= json_encode($wa_link) ?>;

  // Copy fallback
  document.getElementById('copyBtn').addEventListener('click', () => {
    const ta = document.getElementById('waMsg');
    navigator.clipboard.writeText(ta.value).then(() => {
      const b = document.getElementById('copyBtn');
      b.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
      setTimeout(() => { b.innerHTML = '<i class="bi bi-clipboard"></i> Salin Pesan'; }, 2000);
    });
  });

  // Countdown auto-open (manual button stays as fallback for pop-up blockers)
  let n = 3;
  const cd = document.getElementById('cd');
  const tick = setInterval(() => {
    n -= 1;
    if (n <= 0) {
      clearInterval(tick);
      document.getElementById('countdownText').textContent = 'Jika WhatsApp tidak terbuka, klik tombol hijau di atas.';
      if (waLink && waLink !== '#') window.open(waLink, '_blank', 'noopener');
    } else {
      cd.textContent = String(n);
    }
  }, 1000);
})();
</script>

<?= $this->endSection() ?>
