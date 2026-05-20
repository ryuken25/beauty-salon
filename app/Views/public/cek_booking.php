<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<section class="section-header">
  <div class="h1">Cek Status Booking</div>
  <span class="tagline">Masukkan nomor WhatsApp yang Anda gunakan saat booking</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<form class="card-salon mb-3" method="post" action="<?= base_url('cek-booking') ?>" style="max-width:560px; margin:0 auto;">
  <?= csrf_field() ?>
  <label class="form-salon-label">Nomor WhatsApp *</label>
  <div class="flex gap-1">
    <input class="form-salon-input" type="tel" name="nomor_hp" value="<?= esc($phone ?? '') ?>" placeholder="08xxxxxxxxxx" required>
    <button class="btn-salon-primary" type="submit">Cari</button>
  </div>
</form>

<?php if ($phone): ?>
  <?php if (! empty($bookings)): ?>
    <div class="row-salon" style="grid-template-columns:1fr;">
      <?php foreach ($bookings as $b):
        $statusClass = 'badge-salon--' . ($b['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $b['status']));
        $statusLabel = ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$b['status']];
        $canCancel = in_array($b['status'], ['pending_verification', 'accepted'], true);
        $jam = substr((string) $b['slot_mulai'], 0, 5) . '–' . substr((string) $b['slot_selesai'], 0, 5);
      ?>
        <div class="card-salon">
          <div class="flex justify-between items-center flex-wrap gap-1">
            <div>
              <div class="h3"><?= esc($b['nama_layanan']) ?></div>
              <div class="caption"><?= esc($b['kode_booking']) ?><?= ! empty($b['nama_stylist']) ? ' · Stylist: ' . esc($b['nama_stylist']) : '' ?></div>
              <div class="tagline mt-1">
                <i class="bi bi-calendar3"></i> <?= esc(date('d M Y', strtotime($b['tanggal']))) ?>
                · <i class="bi bi-clock"></i> <?= esc($jam) ?>
              </div>
              <?php if (! empty($b['cancellation_reason'])): ?>
                <div class="caption mt-1" style="color:var(--color-danger);"><i class="bi bi-info-circle"></i> Alasan batal: <?= esc($b['cancellation_reason']) ?></div>
              <?php endif ?>
            </div>
            <div class="text-right">
              <span class="badge-salon <?= $statusClass ?>"><?= esc($statusLabel) ?></span>
              <div class="mt-1 flex gap-1 justify-end" style="flex-wrap:wrap;">
                <a class="btn-salon-ghost btn-salon--sm" href="<?= base_url('booking/' . $b['kode_booking'] . '?no_hp=' . urlencode($phone)) ?>">Detail &rarr;</a>
                <?php if ($canCancel): ?>
                  <button type="button" class="btn-salon-danger btn-salon--sm"
                    data-bs-toggle="modal" data-bs-target="#cancelModal"
                    data-kode="<?= esc($b['kode_booking']) ?>"
                    data-layanan="<?= esc($b['nama_layanan']) ?>"
                    data-stylist="<?= esc($b['nama_stylist'] ?? '-') ?>"
                    data-tanggal="<?= esc(date('d M Y', strtotime($b['tanggal']))) ?>"
                    data-jam="<?= esc($jam) ?>">
                    <i class="bi bi-x-circle"></i> Batalkan
                  </button>
                <?php endif ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-search empty-state__icon"></i>
      <div class="empty-state__title">Tidak ada booking</div>
      <div class="empty-state__hint">Belum ada booking dengan nomor HP ini. <a href="<?= base_url('booking') ?>">Buat booking baru</a>.</div>
    </div>
  <?php endif ?>
<?php endif ?>

<!-- Modal: konfirmasi pembatalan booking (Opsi A — mandiri pakai kode + HP) -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="cancelForm" method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="nomor_hp" value="<?= esc($phone ?? '') ?>">
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" style="font-family:var(--font-display);"><i class="bi bi-x-circle" style="color:var(--color-danger);"></i> Konfirmasi Pembatalan Booking</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="card-salon mb-3" style="background:var(--bg);">
            <table style="width:100%; font-size:0.875rem;">
              <tr><td class="label" style="padding:0.25rem 0;">Kode</td><td class="text-right"><span id="cm-kode"></span></td></tr>
              <tr><td class="label" style="padding:0.25rem 0;">Layanan</td><td class="text-right"><span id="cm-layanan"></span></td></tr>
              <tr><td class="label" style="padding:0.25rem 0;">Stylist</td><td class="text-right"><span id="cm-stylist"></span></td></tr>
              <tr><td class="label" style="padding:0.25rem 0;">Tanggal</td><td class="text-right"><span id="cm-tanggal"></span></td></tr>
              <tr><td class="label" style="padding:0.25rem 0;">Jam</td><td class="text-right"><span id="cm-jam"></span></td></tr>
            </table>
          </div>
          <div class="alert-salon alert-salon--error" style="margin:0 0 1rem;">
            <i class="bi bi-exclamation-triangle"></i>
            Apakah Anda yakin ingin membatalkan booking ini? Slot waktu akan dilepas dan tidak bisa dikembalikan.
          </div>
          <div>
            <label class="form-salon-label">Alasan pembatalan (opsional)</label>
            <textarea class="form-salon-textarea" name="cancellation_reason" rows="3" maxlength="500" placeholder="Misal: berhalangan hadir, ada urusan mendadak, dll."></textarea>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gold-border); gap:0.5rem;">
          <button type="button" class="btn-salon-secondary" data-bs-dismiss="modal">Tidak, Kembali</button>
          <button type="submit" class="btn-salon-danger" id="cm-submit"><i class="bi bi-x-circle"></i> Ya, Batalkan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const modal = document.getElementById('cancelModal');
  if (! modal) return;
  const form = document.getElementById('cancelForm');
  const submit = document.getElementById('cm-submit');
  modal.addEventListener('show.bs.modal', (ev) => {
    const b = ev.relatedTarget;
    document.getElementById('cm-kode').textContent    = b.getAttribute('data-kode') || '';
    document.getElementById('cm-layanan').textContent = b.getAttribute('data-layanan') || '';
    document.getElementById('cm-stylist').textContent = b.getAttribute('data-stylist') || '-';
    document.getElementById('cm-tanggal').textContent = b.getAttribute('data-tanggal') || '';
    document.getElementById('cm-jam').textContent     = b.getAttribute('data-jam') || '';
    form.action = '<?= base_url('booking/') ?>' + b.getAttribute('data-kode') + '/batal';
    submit.disabled = false;
    submit.innerHTML = '<i class="bi bi-x-circle"></i> Ya, Batalkan';
    form.reset();
  });
  form.addEventListener('submit', () => {
    setTimeout(() => { submit.disabled = true; submit.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...'; }, 0);
  });
})();
</script>

<?= $this->endSection() ?>
