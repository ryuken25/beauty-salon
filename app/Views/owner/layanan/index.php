<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>
<?php
$ikonList = $ikon_list ?? ['bi-stars', 'bi-scissors', 'bi-flower1', 'bi-droplet', 'bi-hand-index', 'bi-palette', 'bi-gem', 'bi-eye', 'bi-heart', 'bi-magic'];
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Layanan</div>
    <div class="tagline">Kelola katalog layanan salon — galeri foto, ikon, promo</div>
  </div>
  <button class="btn-salon-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newForm"><i class="bi bi-plus"></i> Tambah layanan</button>
</div>

<div class="collapse mb-3" id="newForm">
  <form class="card-salon" method="post" action="<?= base_url('owner/layanan') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="h2 mb-2">Layanan baru</div>
    <div class="row-salon cols-2">
      <div><label class="form-salon-label">Nama *</label><input class="form-salon-input" name="nama" required></div>
      <div><label class="form-salon-label">Kategori *</label><input class="form-salon-input" name="kategori" required placeholder="Hair / Facial / Nails / Make Up / …"></div>
      <div><label class="form-salon-label">Durasi *</label><select class="form-salon-select" name="durasi_menit" required><?php foreach ([30,60,90,120,150,180,210,240] as $m): ?><option value="<?= $m ?>"><?= $m ?> menit</option><?php endforeach ?></select></div>
      <div><label class="form-salon-label">Harga (Rp) *</label><input class="form-salon-input" type="number" min="0" step="1000" name="harga" required></div>
      <div><label class="form-salon-label">Promo (%)</label><input class="form-salon-input" type="number" name="promo_persen" min="0" max="100" step="1" placeholder="0 / kosong = tanpa promo"></div>
      <div><label class="form-salon-label">Aktif</label><select class="form-salon-select" name="is_active"><option value="1">Aktif</option><option value="0">Non-aktif</option></select></div>
    </div>
    <div class="row-salon cols-2 mt-2">
      <div><label class="form-salon-label">Promo mulai (opsional)</label><input class="form-salon-input" type="date" name="promo_mulai"></div>
      <div><label class="form-salon-label">Promo selesai (opsional)</label><input class="form-salon-input" type="date" name="promo_selesai"></div>
    </div>
    <div class="form-salon-help">Kosongkan kedua tanggal kalau promo tanpa batas waktu. Promo otomatis non-aktif setelah tanggal selesai.</div>
    <div class="mt-2"><label class="form-salon-label">Deskripsi promo (opsional)</label><input class="form-salon-input" name="promo_deskripsi" maxlength="255" placeholder="Misal: Spesial pembukaan!"></div>
    <div class="mt-2"><label class="form-salon-label">Deskripsi</label><textarea class="form-salon-textarea" name="deskripsi" rows="2"></textarea></div>

    <div class="mt-2">
      <label class="form-salon-label">Ikon</label>
      <input type="hidden" name="ikon" data-icon-input value="bi-stars">
      <div class="icon-picker" data-icon-picker>
        <?php foreach ($ikonList as $i): ?>
          <button type="button" class="icon-pick<?= $i === 'bi-stars' ? ' icon-pick--active' : '' ?>" data-ikon="<?= esc($i) ?>"><i class="bi <?= esc($i) ?>"></i></button>
        <?php endforeach ?>
      </div>
    </div>

    <div class="mt-2">
      <label class="form-salon-label">Galeri foto (boleh banyak)</label>
      <input class="form-salon-input" type="file" name="gambar[]" multiple accept="image/png,image/jpeg,image/webp">
      <div class="form-salon-help">PNG/JPG/WEBP, maks 2MB/foto. Foto pertama dipakai sebagai cover.</div>
    </div>

    <button class="btn-salon-primary mt-2" type="submit"><i class="bi bi-check2"></i> Simpan</button>
  </form>
</div>

<?php if (empty($rows)): ?>
  <div class="empty-state"><i class="bi bi-list-ul empty-state__icon"></i><div class="empty-state__title">Belum ada layanan</div></div>
<?php else: ?>
  <div class="row-salon cols-3">
    <?php foreach ($rows as $r):
      $gambarList = \App\Models\LayananModel::gambarList($r);
      $isPromo = \App\Models\LayananModel::isPromo($r);
      $hargaFinal = \App\Models\LayananModel::hargaFinal($r);
    ?>
      <div class="card-salon <?= $isPromo ? 'card-salon--promo' : '' ?>">
        <div class="flex justify-between items-center mb-1">
          <div class="service-icon" style="width:40px; height:40px; font-size:1rem;"><i class="bi <?= esc($r['ikon'] ?: 'bi-stars') ?>"></i></div>
          <div class="flex gap-1" style="align-items:center;">
            <?php if ($isPromo): ?><span class="badge-promo">Promo <?= (int) $r['promo_persen'] ?>%</span><?php endif ?>
            <span class="badge-salon <?= $r['is_active'] ? 'badge-salon--accepted' : 'badge-salon--cancelled' ?>"><?= $r['is_active'] ? 'Aktif' : 'Non' ?></span>
          </div>
        </div>
        <?php if (! empty($gambarList[0])): ?>
          <img class="layanan-cover" src="<?= base_url($gambarList[0]) ?>" alt="<?= esc($r['nama']) ?>">
        <?php endif ?>
        <div class="h3"><?= esc($r['nama']) ?></div>
        <div class="tagline mb-1"><?= esc($r['kategori']) ?> · <?= esc($r['durasi_menit']) ?> menit</div>
        <div class="h3 mb-2">
          <?php if ($isPromo): ?>
            <span class="price-strike">Rp <?= number_format((int) $r['harga'], 0, ',', '.') ?></span>
            <span style="color:var(--gold);">Rp <?= number_format($hargaFinal, 0, ',', '.') ?></span>
          <?php else: ?>
            Rp <?= number_format((int) $r['harga'], 0, ',', '.') ?>
          <?php endif ?>
        </div>

        <div class="flex gap-1">
          <details style="flex:1;">
            <summary class="btn-salon-secondary btn-salon--sm" style="cursor:pointer; width:100%; justify-content:center;">Edit</summary>
            <form method="post" action="<?= base_url('owner/layanan/' . $r['id'] . '/update') ?>" class="mt-2" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input class="form-salon-input mb-1" name="nama" value="<?= esc($r['nama']) ?>" required>
              <input class="form-salon-input mb-1" name="kategori" value="<?= esc($r['kategori']) ?>" required>
              <select class="form-salon-select mb-1" name="durasi_menit" required><?php foreach ([30,60,90,120,150,180,210,240] as $m): ?><option value="<?= $m ?>" <?= $m == $r['durasi_menit'] ? 'selected' : '' ?>><?= $m ?> menit</option><?php endforeach ?></select>
              <input class="form-salon-input mb-1" type="number" min="0" name="harga" value="<?= esc($r['harga']) ?>" required>

              <label class="form-salon-label mt-1">Promo (%)</label>
              <input class="form-salon-input mb-1" type="number" min="0" max="100" name="promo_persen" value="<?= esc($r['promo_persen'] ?? '') ?>" placeholder="0 / kosong = tanpa promo">
              <div class="row-salon cols-2 mb-1">
                <div><label class="form-salon-label">Mulai</label><input class="form-salon-input" type="date" name="promo_mulai" value="<?= esc($r['promo_mulai'] ? substr($r['promo_mulai'], 0, 10) : '') ?>"></div>
                <div><label class="form-salon-label">Selesai</label><input class="form-salon-input" type="date" name="promo_selesai" value="<?= esc($r['promo_selesai'] ? substr($r['promo_selesai'], 0, 10) : '') ?>"></div>
              </div>
              <input class="form-salon-input mb-1" name="promo_deskripsi" maxlength="255" value="<?= esc($r['promo_deskripsi'] ?? '') ?>" placeholder="Deskripsi promo (opsional)">

              <select class="form-salon-select mb-1" name="is_active"><option value="1" <?= $r['is_active'] ? 'selected' : '' ?>>Aktif</option><option value="0" <?= ! $r['is_active'] ? 'selected' : '' ?>>Non-aktif</option></select>
              <textarea class="form-salon-textarea mb-1" name="deskripsi"><?= esc($r['deskripsi']) ?></textarea>

              <label class="form-salon-label mt-1">Ikon</label>
              <input type="hidden" name="ikon" data-icon-input value="<?= esc($r['ikon'] ?: 'bi-stars') ?>">
              <div class="icon-picker" data-icon-picker>
                <?php foreach ($ikonList as $i): ?>
                  <button type="button" class="icon-pick<?= $i === ($r['ikon'] ?: 'bi-stars') ? ' icon-pick--active' : '' ?>" data-ikon="<?= esc($i) ?>"><i class="bi <?= esc($i) ?>"></i></button>
                <?php endforeach ?>
              </div>

              <?php if ($gambarList): ?>
                <label class="form-salon-label mt-2">Galeri saat ini (centang untuk hapus)</label>
                <div class="galeri-thumbs">
                  <?php foreach ($gambarList as $gp): ?>
                    <div class="galeri-thumb">
                      <img src="<?= base_url($gp) ?>" alt="">
                      <label class="galeri-thumb__hapus">
                        <input type="checkbox" name="hapus_gambar[]" value="<?= esc($gp) ?>">
                        Hapus
                      </label>
                    </div>
                  <?php endforeach ?>
                </div>
              <?php endif ?>

              <label class="form-salon-label mt-2">Tambah foto baru</label>
              <input class="form-salon-input mb-1" type="file" name="gambar[]" multiple accept="image/png,image/jpeg,image/webp">

              <button class="btn-salon-primary btn-salon--sm btn-salon--full mt-2" type="submit"><i class="bi bi-check2"></i> Simpan</button>
            </form>
          </details>
          <button type="button" class="btn-salon-danger btn-salon--sm" data-bs-toggle="modal" data-bs-target="#delLayanan"
                  data-id="<?= $r['id'] ?>" data-nama="<?= esc($r['nama']) ?>">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<!-- Delete confirmation modal -->
<div class="modal fade modal-salon" id="delLayanan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="delLayananForm" method="post" action="">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title" style="font-family:var(--font-display);"><i class="bi bi-trash" style="color:var(--color-danger);"></i> Hapus Layanan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Hapus layanan <strong id="delLayananNama"></strong>?</p>
          <div class="alert-salon alert-salon--info" style="margin:0;">
            <i class="bi bi-info-circle"></i> Layanan yang sudah pernah dipakai booking hanya akan <strong>diarsipkan</strong> (soft delete) supaya riwayat transaksi tetap utuh. Jika belum pernah dipakai, dihapus permanen + file galeri ikut terhapus.
          </div>
        </div>
        <div class="modal-footer" style="gap:0.5rem;">
          <button type="button" class="btn-salon-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-salon-danger"><i class="bi bi-trash"></i> Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  // Icon picker — wiring untuk semua [data-icon-picker] di halaman.
  document.querySelectorAll('[data-icon-picker]').forEach((wrap) => {
    const input = wrap.parentElement.querySelector('[data-icon-input]');
    wrap.querySelectorAll('.icon-pick').forEach((btn) => {
      btn.addEventListener('click', () => {
        wrap.querySelectorAll('.icon-pick').forEach((b) => b.classList.remove('icon-pick--active'));
        btn.classList.add('icon-pick--active');
        if (input) input.value = btn.dataset.ikon;
      });
    });
  });

  // Delete modal — populate action url.
  const modal = document.getElementById('delLayanan');
  if (modal) {
    modal.addEventListener('show.bs.modal', (ev) => {
      const btn = ev.relatedTarget;
      document.getElementById('delLayananNama').textContent = btn.getAttribute('data-nama') || '';
      document.getElementById('delLayananForm').action =
        '<?= base_url('owner/layanan/') ?>' + btn.getAttribute('data-id') + '/delete';
    });
  }
})();
</script>

<?= $this->endSection() ?>
