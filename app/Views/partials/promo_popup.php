<?php
// Render modal promo + layanan baru hanya saat flag once aktif.
// Flag dihapus segera supaya refresh tidak munculin lagi.
// Admin/pemilik tidak kena (Auth hanya set flag untuk role pelanggan).
$show = (bool) session('promo_popup_once');
if ($show) {
    session()->remove('promo_popup_once');
}
$model = new \App\Models\LayananModel();
$promos = $show ? $model->promoAktif(12) : [];
$baru   = $show ? $model->baruAktif(12)   : [];

// Keluar kalau tidak ada konten sama sekali.
if (! $show || (empty($promos) && empty($baru))) return;

// Dedup: buang dari $baru item yang id-nya sudah ada di $promos.
$promoIds = array_column($promos, 'id');
$baru = array_values(array_filter($baru, fn($b) => ! in_array($b['id'], $promoIds, true)));

// Keluar lagi kalau setelah dedup tetap kosong semua.
if (empty($promos) && empty($baru)) return;
?>
<div class="modal fade modal-salon" id="promoLoginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-family:var(--font-display); color:var(--gold);">
          <i class="bi bi-tag-fill"></i>
          <?php if (! empty($promos) && ! empty($baru)): ?>
            Promo &amp; Layanan Baru
          <?php elseif (! empty($promos)): ?>
            Lagi promo nih!
          <?php else: ?>
            Ada layanan baru!
          <?php endif ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="caption mb-2">Pilih layanan untuk lihat detail &amp; booking.</div>

        <?php if (! empty($promos)): ?>
          <div style="font-size:0.8rem; font-weight:600; color:var(--promo); text-transform:uppercase; letter-spacing:1px; margin-bottom:0.5rem;">
            <i class="bi bi-tag-fill"></i> Promo Aktif
          </div>
          <?php foreach ($promos as $p):
            $hargaFinal = \App\Models\LayananModel::hargaFinal($p);
            $cover      = \App\Models\LayananModel::cover($p);
            $range      = \App\Models\LayananModel::promoRange($p);
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
        <?php endif ?>

        <?php if (! empty($baru)): ?>
          <?php if (! empty($promos)): ?>
            <hr style="border-color:var(--gold-border); margin:0.75rem 0;">
          <?php endif ?>
          <div style="font-size:0.8rem; font-weight:600; color:var(--champagne); text-transform:uppercase; letter-spacing:1px; margin-bottom:0.5rem;">
            <i class="bi bi-stars"></i> Layanan Baru
          </div>
          <?php foreach ($baru as $b):
            $cover = \App\Models\LayananModel::cover($b);
            $umur  = \App\Models\LayananModel::umurHariBaru($b);
          ?>
            <a class="card-salon mb-2" href="<?= base_url('layanan/' . (int) $b['id']) ?>" style="display:block; text-decoration:none; color:inherit;">
              <div class="flex items-center gap-2" style="flex-wrap:nowrap;">
                <?php if ($cover): ?>
                  <img src="<?= base_url($cover) ?>" alt="" style="width:64px; height:64px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border);">
                <?php else: ?>
                  <div class="service-icon" style="width:64px; height:64px; font-size:1.4rem; flex-shrink:0;"><i class="bi <?= esc($b['ikon'] ?: 'bi-stars') ?>"></i></div>
                <?php endif ?>
                <div style="flex:1; min-width:0;">
                  <div class="h3" style="margin:0;"><?= esc($b['nama']) ?></div>
                  <div class="caption">
                    <span class="badge-baru" style="font-size:0.65rem; padding:1px 8px;">Baru</span>
                    <span style="color:var(--gold); font-weight:600;">Rp <?= number_format((int) $b['harga'], 0, ',', '.') ?></span>
                  </div>
                  <?php if ($umur !== null): ?>
                    <div class="caption" style="color:var(--text-secondary);"><i class="bi bi-clock"></i> Ditambahkan <?= $umur ?> hari lalu</div>
                  <?php endif ?>
                </div>
                <i class="bi bi-chevron-right" style="color:var(--gold);"></i>
              </div>
            </a>
          <?php endforeach ?>
        <?php endif ?>

      </div>
      <div class="modal-footer">
        <a class="btn-salon-primary" href="<?= base_url('layanan') ?>">Lihat semua layanan</a>
        <button type="button" class="btn-salon-ghost" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
.badge-baru {
  display: inline-block;
  padding: 0.25rem 0.714rem;
  border-radius: var(--radius-pill);
  font-size: 0.65rem;
  font-weight: 500;
  letter-spacing: 0.5px;
  background: rgba(201,166,107,0.12);
  border: 1px solid var(--gold-border);
  color: var(--gold);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('promoLoginModal');
  if (el && window.bootstrap) {
    new bootstrap.Modal(el).show();
  }
});
</script>
