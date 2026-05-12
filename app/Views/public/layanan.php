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
  <?php foreach ($services as $s): ?>
    <div class="card-salon" data-kategori="<?= esc($s['kategori']) ?>">
      <div class="service-icon mb-2"><i class="bi <?= esc($s['ikon'] ?: 'bi-stars') ?>"></i></div>
      <div class="h3"><?= esc($s['nama']) ?></div>
      <div class="tagline mb-1"><?= esc($s['kategori']) ?></div>
      <div class="caption mb-2"><?= esc(mb_substr((string) $s['deskripsi'], 0, 80)) ?><?= mb_strlen((string) $s['deskripsi']) > 80 ? '…' : '' ?></div>
      <div class="flex justify-between items-center mb-2">
        <span class="caption"><?= esc($s['durasi_menit']) ?> menit</span>
        <span class="h3">Rp <?= number_format((int) $s['harga'], 0, ',', '.') ?></span>
      </div>
      <a class="btn-salon-primary btn-salon--sm btn-salon--full" href="<?= base_url('booking?layanan_id=' . $s['id']) ?>">Booking</a>
    </div>
  <?php endforeach ?>
</div>

<script>
document.querySelectorAll('#kategoriChips .chip').forEach((c) => {
  c.onclick = () => {
    document.querySelectorAll('#kategoriChips .chip').forEach((x) => x.classList.remove('chip--active'));
    c.classList.add('chip--active');
    const k = c.dataset.kategori;
    document.querySelectorAll('#layananGrid .card-salon').forEach((card) => {
      card.style.display = (!k || card.dataset.kategori === k) ? '' : 'none';
    });
  };
});
</script>

<?= $this->endSection() ?>
