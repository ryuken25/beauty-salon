<?= $this->extend('layouts/owner') ?>
<?= $this->section('content') ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Stylist</div>
    <div class="tagline">Kelola tim stylist salon</div>
  </div>
  <button class="btn-salon-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newStylist">
    <i class="bi bi-plus"></i> Tambah stylist
  </button>
</div>

<!-- Create -->
<div class="collapse mb-3" id="newStylist">
  <form class="card-salon" method="post" action="<?= base_url('owner/stylist') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="h2 mb-2">Stylist baru</div>
    <div class="row-salon cols-2">
      <div><label class="form-salon-label">Nama *</label><input class="form-salon-input" name="nama" required></div>
      <div><label class="form-salon-label">Spesialisasi</label><input class="form-salon-input" name="spesialisasi" placeholder="Hair / Nail / Make Up / …"></div>
      <div><label class="form-salon-label">Jam kerja mulai</label><input class="form-salon-input" type="time" name="jam_kerja_mulai" value="08:00"></div>
      <div><label class="form-salon-label">Jam kerja selesai</label><input class="form-salon-input" type="time" name="jam_kerja_selesai" value="19:00"></div>
      <div><label class="form-salon-label">Foto (opsional)</label><input class="form-salon-input" type="file" name="foto" accept="image/*"></div>
      <div><label class="form-salon-label">Status</label><select class="form-salon-select" name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
    </div>
    <button class="btn-salon-primary mt-2" type="submit">Simpan</button>
  </form>
</div>

<?php if (empty($rows)): ?>
  <div class="card-salon empty-state">
    <i class="bi bi-person-badge empty-state__icon"></i>
    <div class="empty-state__title">Belum ada stylist</div>
    <div class="empty-state__hint">Tambah stylist agar pelanggan bisa memilih saat booking.</div>
  </div>
<?php else: ?>
  <div class="row-salon cols-3">
    <?php foreach ($rows as $r): ?>
      <div class="card-salon">
        <div class="flex items-center gap-1 mb-2">
          <?php if (! empty($r['foto'])): ?>
            <img src="<?= base_url($r['foto']) ?>" alt="<?= esc($r['nama']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid var(--gold-border);">
          <?php else: ?>
            <span class="service-icon" style="width:48px;height:48px;"><i class="bi bi-person"></i></span>
          <?php endif ?>
          <div style="flex:1;">
            <div class="h3" style="margin:0;"><?= esc($r['nama']) ?></div>
            <div class="caption"><?= esc($r['spesialisasi'] ?: 'Umum') ?></div>
          </div>
          <span class="badge-salon <?= $r['is_active'] ? 'badge-salon--accepted' : 'badge-salon--cancelled' ?>">
            <?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
          </span>
        </div>
        <div class="caption mb-2">
          <i class="bi bi-clock"></i>
          <?= $r['jam_kerja_mulai'] ? esc(substr($r['jam_kerja_mulai'], 0, 5)) : '—' ?>
          – <?= $r['jam_kerja_selesai'] ? esc(substr($r['jam_kerja_selesai'], 0, 5)) : '—' ?>
        </div>

        <div class="flex gap-1">
          <details style="flex:1;">
            <summary class="btn-salon-secondary btn-salon--sm" style="cursor:pointer; width:100%; justify-content:center;">Edit</summary>
            <form method="post" action="<?= base_url('owner/stylist/' . $r['id'] . '/update') ?>" class="mt-2" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input class="form-salon-input mb-1" name="nama" value="<?= esc($r['nama']) ?>" required>
              <input class="form-salon-input mb-1" name="spesialisasi" value="<?= esc($r['spesialisasi']) ?>" placeholder="Spesialisasi">
              <div class="flex gap-1 mb-1">
                <input class="form-salon-input" type="time" name="jam_kerja_mulai" value="<?= esc(substr((string) $r['jam_kerja_mulai'], 0, 5)) ?>">
                <input class="form-salon-input" type="time" name="jam_kerja_selesai" value="<?= esc(substr((string) $r['jam_kerja_selesai'], 0, 5)) ?>">
              </div>
              <input class="form-salon-input mb-1" type="file" name="foto" accept="image/*">
              <select class="form-salon-select mb-1" name="is_active">
                <option value="1" <?= $r['is_active'] ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= ! $r['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
              </select>
              <button class="btn-salon-primary btn-salon--sm btn-salon--full" type="submit">Simpan perubahan</button>
            </form>
          </details>
          <button type="button" class="btn-salon-danger btn-salon--sm" data-bs-toggle="modal" data-bs-target="#delStylist"
                  data-id="<?= $r['id'] ?>" data-nama="<?= esc($r['nama']) ?>">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<!-- Delete confirmation modal -->
<div class="modal fade" id="delStylist" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="delStylistForm" method="post" action="">
        <?= csrf_field() ?>
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" style="font-family:var(--font-display);"><i class="bi bi-trash" style="color:var(--color-danger);"></i> Hapus Stylist</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Hapus stylist <strong id="delStylistNama"></strong>?</p>
          <div class="alert-salon alert-salon--info" style="margin:0;">
            <i class="bi bi-info-circle"></i> Jika stylist sudah punya riwayat booking, dia hanya akan <strong>dinonaktifkan</strong> (soft delete) supaya data histori tetap utuh. Jika belum pernah dipakai, akan dihapus permanen.
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
  const modal = document.getElementById('delStylist');
  if (! modal) return;
  modal.addEventListener('show.bs.modal', (ev) => {
    const btn = ev.relatedTarget;
    document.getElementById('delStylistNama').textContent = btn.getAttribute('data-nama') || '';
    document.getElementById('delStylistForm').action =
      '<?= base_url('owner/stylist/') ?>' + btn.getAttribute('data-id') + '/delete';
  });
})();
</script>

<?= $this->endSection() ?>
