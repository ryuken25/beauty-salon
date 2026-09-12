<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<section class="section-header">
  <div class="h1">Layanan kami</div>
  <span class="tagline">Reservasi cepat dan mudah</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<div class="chip-row" id="kategoriChips">
  <button type="button" class="chip chip--active" data-kategori="">Semua</button>
  <?php foreach ($kategori as $k): ?>
    <button type="button" class="chip" data-kategori="<?= esc($k) ?>"><?= esc($k) ?></button>
  <?php endforeach ?>
</div>

<div class="row-salon cols-3" id="layananGrid">
  <?php foreach ($services as $s):
    $cover = \App\Models\LayananModel::cover($s);
    $isPromo = \App\Models\LayananModel::isPromo($s);
    $hargaFinal = \App\Models\LayananModel::hargaFinal($s);
    $cardCls = 'card-salon' . ($isPromo ? ' card-salon--promo' : '');
    $waLayanan = wa_admin_link(wa_message('layanan', ['layanan' => $s['nama']]));
  ?>
  <div class="layanan-cell" data-kategori="<?= esc($s['kategori']) ?>">
    <a class="<?= $cardCls ?>" href="<?= base_url('layanan/' . (int) $s['id']) ?>" style="text-decoration:none; color:inherit; display:block;">
      <?php if ($cover): ?>
        <img class="layanan-cover" src="<?= base_url($cover) ?>" alt="<?= esc($s['nama']) ?>">
      <?php else: ?>
        <div class="service-icon mb-2"><i class="bi <?= esc($s['ikon'] ?: 'bi-stars') ?>"></i></div>
      <?php endif ?>

      <?php if ($isPromo): ?>
        <span class="badge-promo mb-1"><i class="bi bi-tag"></i> Promo <?= (int) $s['promo_persen'] ?>%</span>
      <?php endif ?>

      <div class="h3"><?= esc($s['nama']) ?></div>
      <div class="tagline mb-1"><?= esc($s['kategori']) ?></div>
      <div class="caption mb-2"><?= esc(mb_substr((string) $s['deskripsi'], 0, 80)) ?><?= mb_strlen((string) $s['deskripsi']) > 80 ? '…' : '' ?></div>

      <div class="flex justify-between items-center mb-2">
        <span class="caption"><?= esc($s['durasi_menit']) ?> menit</span>
        <span class="h3" style="text-align:right;">
          <?php if ($isPromo): ?>
            <span class="price-strike">Rp <?= number_format((int) $s['harga'], 0, ',', '.') ?></span><br>
            <span style="color:var(--gold);">Rp <?= number_format($hargaFinal, 0, ',', '.') ?></span>
          <?php else: ?>
            Rp <?= number_format((int) $s['harga'], 0, ',', '.') ?>
          <?php endif ?>
        </span>
      </div>

      <span class="btn-salon-secondary btn-salon--sm btn-salon--full" style="pointer-events:none;">
        <i class="bi bi-eye"></i> Lihat detail
      </span>
    </a>
    <?php if ($waLayanan !== null): ?>
      <a class="btn-wa-inline btn-wa-inline--full" href="<?= esc($waLayanan) ?>" target="_blank" rel="noopener noreferrer"
         aria-label="Chat WhatsApp admin SW Beauty Salon">
        <i class="bi bi-whatsapp"></i> Tanya admin soal layanan ini
      </a>
    <?php endif ?>
  </div>
  <?php endforeach ?>
</div>

<script>
document.querySelectorAll('#kategoriChips .chip').forEach((c) => {
  c.onclick = () => {
    document.querySelectorAll('#kategoriChips .chip').forEach((x) => x.classList.remove('chip--active'));
    c.classList.add('chip--active');
    const k = c.dataset.kategori;
    document.querySelectorAll('#layananGrid .layanan-cell').forEach((cell) => {
      cell.style.display = (!k || cell.dataset.kategori === k) ? '' : 'none';
    });
  };
});
</script>

<?= $this->endSection() ?>
