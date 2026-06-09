<?php
// Render modal promo hanya saat flag once aktif. Flag dihapus segera supaya
// refresh tidak munculin lagi. Admin/pemilik tidak kena (Auth hanya set flag
// untuk role pelanggan).
$show = (bool) session('promo_popup_once');
if ($show) {
    session()->remove('promo_popup_once');
}
$promos = $show ? (new \App\Models\LayananModel())->promoAktif(12) : [];
if (! $show || empty($promos)) return;
?>
<div class="modal fade modal-salon" id="promoLoginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-family:var(--font-display); color:var(--gold);">
          <i class="bi bi-tag-fill"></i> Lagi promo nih!
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="caption mb-2">Pilih layanan untuk lihat detail &amp; booking.</div>
        <?php foreach ($promos as $p):
          $hargaFinal = \App\Models\LayananModel::hargaFinal($p);
          $cover = \App\Models\LayananModel::cover($p);
          $range = \App\Models\LayananModel::promoRange($p);
        ?>
          <a class="card-salon card-salon--promo mb-2" href="<?= base_url('layanan/' . (int) $p['id']) ?>" style="display:block; text-decoration:none; color:inherit;">
            <div class="flex items-center gap-2" style="flex-wrap:nowrap;">
              <?php if ($cover): ?>
                <img src="<?= base_url($cover) ?>" alt="" style="width:64px; height:64px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border);">
              <?php else: ?>
                <div class="service-icon" style="width:64px; height:64px; font-size:1.4rem; flex-shrink:0;"><i class="bi <?= esc($p['ikon'] ?: 'bi-stars') ?>"></i></div>
              <?php endif ?>
              <div style="flex:1; min-width:0;">
                <div class="h3" style="margin:0;"><?= esc($p['nama']) ?></div>
                <div class="caption">
                  <span class="badge-promo" style="font-size:0.65rem; padding:1px 8px;">Promo <?= (int) $p['promo_persen'] ?>%</span>
                  <span class="price-strike">Rp <?= number_format((int) $p['harga'], 0, ',', '.') ?></span>
                  <span style="color:var(--gold); font-weight:600;">Rp <?= number_format($hargaFinal, 0, ',', '.') ?></span>
                </div>
                <?php if (! empty($p['promo_deskripsi'])): ?>
                  <div class="caption" style="color:var(--promo);"><?= esc($p['promo_deskripsi']) ?></div>
                <?php endif ?>
                <?php if ($range): ?>
                  <div class="caption" style="color:var(--text-secondary);"><i class="bi bi-calendar-range"></i> <?= esc($range) ?></div>
                <?php endif ?>
              </div>
              <i class="bi bi-chevron-right" style="color:var(--gold);"></i>
            </div>
          </a>
        <?php endforeach ?>
      </div>
      <div class="modal-footer">
        <a class="btn-salon-primary" href="<?= base_url('layanan') ?>">Lihat semua layanan</a>
        <button type="button" class="btn-salon-ghost" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('promoLoginModal');
  if (el && window.bootstrap) {
    new bootstrap.Modal(el).show();
  }
});
</script>
