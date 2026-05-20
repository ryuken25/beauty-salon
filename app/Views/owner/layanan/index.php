<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>
<?php
$ikonList = ['bi-stars', 'bi-scissors', 'bi-flower1', 'bi-droplet', 'bi-hand-index', 'bi-palette', 'bi-gem', 'bi-eye', 'bi-heart', 'bi-magic'];
?>

<div class="page-header-salon">
  <div>
    <div class="h1">Layanan</div>
    <div class="tagline">Kelola katalog layanan salon</div>
  </div>
  <button class="btn-salon-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newForm"><i class="bi bi-plus"></i> Tambah layanan</button>
</div>

<div class="collapse mb-3" id="newForm">
  <form class="card-salon" method="post" action="<?= base_url('owner/layanan') ?>">
    <?= csrf_field() ?>
    <div class="h2 mb-2">Layanan baru</div>
    <div class="row-salon cols-2">
      <div><label class="form-salon-label">Nama *</label><input class="form-salon-input" name="nama" required></div>
      <div><label class="form-salon-label">Kategori *</label><input class="form-salon-input" name="kategori" required placeholder="Hair / Facial / Nails / Make Up / …"></div>
      <div><label class="form-salon-label">Durasi *</label><select class="form-salon-select" name="durasi_menit" required><?php foreach ([30,60,90,120,150,180,210,240] as $m): ?><option value="<?= $m ?>"><?= $m ?> menit</option><?php endforeach ?></select></div>
      <div><label class="form-salon-label">Harga (Rp) *</label><input class="form-salon-input" type="number" min="0" step="1000" name="harga" required></div>
      <div><label class="form-salon-label">Ikon</label><select class="form-salon-select" name="ikon"><?php foreach ($ikonList as $i): ?><option value="<?= $i ?>"><?= $i ?></option><?php endforeach ?></select></div>
      <div><label class="form-salon-label">Aktif</label><select class="form-salon-select" name="is_active"><option value="1">Aktif</option><option value="0">Non-aktif</option></select></div>
    </div>
    <div class="mt-2"><label class="form-salon-label">Deskripsi</label><textarea class="form-salon-textarea" name="deskripsi" rows="2"></textarea></div>
    <button class="btn-salon-primary mt-2" type="submit">Simpan</button>
  </form>
</div>

<?php if (empty($rows)): ?>
  <div class="empty-state"><i class="bi bi-list-ul empty-state__icon"></i><div class="empty-state__title">Belum ada layanan</div></div>
<?php else: ?>
  <div class="row-salon cols-3">
    <?php foreach ($rows as $r): ?>
      <div class="card-salon">
        <div class="flex justify-between items-center mb-1"><div class="service-icon" style="width:40px; height:40px; font-size:1rem;"><i class="bi <?= esc($r['ikon'] ?: 'bi-stars') ?>"></i></div><span class="badge-salon <?= $r['is_active'] ? 'badge-salon--accepted' : 'badge-salon--cancelled' ?>"><?= $r['is_active'] ? 'Aktif' : 'Non' ?></span></div>
        <div class="h3"><?= esc($r['nama']) ?></div>
        <div class="tagline mb-1"><?= esc($r['kategori']) ?> · <?= esc($r['durasi_menit']) ?> menit</div>
        <div class="h3 mb-2">Rp <?= number_format((int) $r['harga'], 0, ',', '.') ?></div>
        <div class="flex gap-1">
          <details style="flex:1;">
            <summary class="btn-salon-secondary btn-salon--sm" style="cursor:pointer; width:100%; justify-content:center;">Edit</summary>
            <form method="post" action="<?= base_url('owner/layanan/' . $r['id'] . '/update') ?>" class="mt-2">
              <?= csrf_field() ?>
              <input class="form-salon-input mb-1" name="nama" value="<?= esc($r['nama']) ?>" required>
              <input class="form-salon-input mb-1" name="kategori" value="<?= esc($r['kategori']) ?>" required>
              <select class="form-salon-select mb-1" name="durasi_menit" required><?php foreach ([30,60,90,120,150,180,210,240] as $m): ?><option value="<?= $m ?>" <?= $m == $r['durasi_menit'] ? 'selected' : '' ?>><?= $m ?> menit</option><?php endforeach ?></select>
              <input class="form-salon-input mb-1" type="number" min="0" name="harga" value="<?= esc($r['harga']) ?>" required>
              <select class="form-salon-select mb-1" name="ikon"><?php foreach ($ikonList as $i): ?><option value="<?= $i ?>" <?= $i === $r['ikon'] ? 'selected' : '' ?>><?= $i ?></option><?php endforeach ?></select>
              <select class="form-salon-select mb-1" name="is_active"><option value="1" <?= $r['is_active'] ? 'selected' : '' ?>>Aktif</option><option value="0" <?= ! $r['is_active'] ? 'selected' : '' ?>>Non-aktif</option></select>
              <textarea class="form-salon-textarea mb-1" name="deskripsi"><?= esc($r['deskripsi']) ?></textarea>
              <button class="btn-salon-primary btn-salon--sm btn-salon--full" type="submit">Simpan</button>
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
<div class="modal fade" id="delLayanan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="delLayananForm" method="post" action="">
        <?= csrf_field() ?>
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" style="font-family:var(--font-display);"><i class="bi bi-trash" style="color:var(--color-danger);"></i> Hapus Layanan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Hapus layanan <strong id="delLayananNama"></strong>?</p>
          <div class="alert-salon alert-salon--info" style="margin:0;">
            <i class="bi bi-info-circle"></i> Layanan yang sudah pernah dipakai booking hanya akan <strong>diarsipkan</strong> (soft delete) supaya riwayat transaksi tetap utuh. Jika belum pernah dipakai, dihapus permanen.
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gold-border); gap:0.5rem;">
          <button type="button" class="btn-salon-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-salon-danger"><i class="bi bi-trash"></i> Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const modal = document.getElementById('delLayanan');
  if (! modal) return;
  modal.addEventListener('show.bs.modal', (ev) => {
    const btn = ev.relatedTarget;
    document.getElementById('delLayananNama').textContent = btn.getAttribute('data-nama') || '';
    document.getElementById('delLayananForm').action =
      '<?= base_url('owner/layanan/') ?>' + btn.getAttribute('data-id') + '/delete';
  });
})();
</script>

<?= $this->endSection() ?>
