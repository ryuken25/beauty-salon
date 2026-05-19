<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$statusMap = [
    'pending_verification' => ['Menunggu Verifikasi', 'pending'],
    'accepted' => ['Diterima', 'accepted'],
    'completed' => ['Selesai', 'completed'],
    'rejected' => ['Ditolak', 'rejected'],
    'cancelled' => ['Batal', 'cancelled'],
];
?>

<section class="section-header">
  <div class="h1">Halo, <?= esc($nama) ?></div>
  <span class="tagline">Riwayat &amp; booking aktif Anda</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<div class="flex justify-between items-center flex-wrap gap-2 mb-2">
  <div class="caption">Total <?= count($bookings) ?> booking</div>
  <a class="btn-salon-primary" href="<?= base_url('booking') ?>"><i class="bi bi-plus-circle"></i> Booking baru</a>
</div>

<?php if (empty($bookings)): ?>
  <div class="card-salon empty-state">
    <i class="bi bi-calendar-x empty-state__icon"></i>
    <div class="empty-state__title">Belum ada booking</div>
    <div class="empty-state__hint">Mulai booking layanan pertama Anda.</div>
    <a class="btn-salon-primary mt-2" href="<?= base_url('booking') ?>">Booking sekarang</a>
  </div>
<?php else: ?>
  <div class="row-salon">
    <?php foreach ($bookings as $b):
      [$label, $cls] = $statusMap[$b['status']] ?? [$b['status'], 'pending'];
      $canCancel = in_array($b['status'], ['pending_verification', 'accepted'], true);
    ?>
      <div class="card-salon card-salon--<?= $cls ?>">
        <div class="flex justify-between items-center flex-wrap gap-1">
          <div>
            <div class="h3"><?= esc($b['nama_layanan']) ?></div>
            <div class="caption">
              <i class="bi bi-calendar"></i> <?= esc(date('d M Y', strtotime($b['tanggal']))) ?>
              · <i class="bi bi-clock"></i>
              <?= esc(substr((string) $b['slot_mulai'], 0, 5)) ?>–<?= esc(substr((string) $b['slot_selesai'], 0, 5)) ?>
            </div>
            <div class="caption">Kode: <strong><?= esc($b['kode_booking']) ?></strong></div>
          </div>
          <div class="text-right">
            <span class="badge-salon badge-salon--<?= $cls ?>"><?= esc($label) ?></span>
            <div class="mt-1 flex gap-1 justify-end" style="flex-wrap:wrap;">
              <a class="btn-salon-secondary btn-salon--sm" href="<?= base_url('booking/' . $b['kode_booking'] . '?no_hp=' . urlencode($b['nomor_hp_pelanggan'])) ?>">Detail</a>
              <?php if ($canCancel): ?>
                <button
                  type="button"
                  class="btn-salon-danger btn-salon--sm"
                  data-bs-toggle="modal"
                  data-bs-target="#cancelModal"
                  data-id="<?= esc($b['id']) ?>"
                  data-kode="<?= esc($b['kode_booking']) ?>"
                  data-layanan="<?= esc($b['nama_layanan']) ?>"
                  data-tanggal="<?= esc(date('d M Y', strtotime($b['tanggal']))) ?>"
                  data-jam="<?= esc(substr((string) $b['slot_mulai'], 0, 5)) ?>–<?= esc(substr((string) $b['slot_selesai'], 0, 5)) ?>">
                  <i class="bi bi-x-circle"></i> Batalkan
                </button>
              <?php endif ?>
            </div>
          </div>
        </div>
        <?php if (! empty($b['cancellation_reason'])): ?>
          <div class="caption mt-1" style="color:var(--color-danger);">
            <i class="bi bi-info-circle"></i> Alasan batal: <?= esc($b['cancellation_reason']) ?>
          </div>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<!-- Modal: konfirmasi pembatalan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="cancelForm" method="post" action="">
        <?= csrf_field() ?>
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" id="cancelModalLabel" style="font-family:var(--font-display); color:var(--text-primary);">
            <i class="bi bi-x-circle" style="color:var(--color-danger);"></i> Konfirmasi Pembatalan Booking
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="card-salon mb-3" style="background:var(--bg);">
            <table style="width:100%; font-size:0.875rem;">
              <tr><td class="label" style="padding:0.25rem 0;">Kode</td><td class="text-right"><span id="cm-kode"></span></td></tr>
              <tr><td class="label" style="padding:0.25rem 0;">Layanan</td><td class="text-right"><span id="cm-layanan"></span></td></tr>
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
            <div class="form-salon-help">Maks 500 karakter. Membantu salon untuk peningkatan layanan.</div>
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

  const form     = document.getElementById('cancelForm');
  const submit   = document.getElementById('cm-submit');
  const baseUrl  = '<?= base_url('pelanggan/booking/') ?>';

  modal.addEventListener('show.bs.modal', (ev) => {
    const btn = ev.relatedTarget;
    if (! btn) return;
    document.getElementById('cm-kode').textContent    = btn.getAttribute('data-kode') || '';
    document.getElementById('cm-layanan').textContent = btn.getAttribute('data-layanan') || '';
    document.getElementById('cm-tanggal').textContent = btn.getAttribute('data-tanggal') || '';
    document.getElementById('cm-jam').textContent     = btn.getAttribute('data-jam') || '';
    const id = btn.getAttribute('data-id');
    form.action = baseUrl + id + '/cancel';
    // Reset state for repeated opens
    submit.disabled = false;
    submit.innerHTML = '<i class="bi bi-x-circle"></i> Ya, Batalkan';
    form.reset();
  });

  form.addEventListener('submit', () => {
    setTimeout(() => {
      submit.disabled = true;
      submit.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
    }, 0);
  });
})();
</script>

<?php if (session()->getFlashdata('error')): ?>
<script>(function(){ /* flash already rendered by layout */ })();</script>
<?php endif ?>

<?= $this->endSection() ?>
