<?= $this->extend('layouts/panel') ?>
<?= $this->section('content') ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Pelanggan</div>
    <div class="tagline">Kelola akun pelanggan — ubah nama atau reset password</div>
  </div>
</div>

<form method="get" class="card-salon mb-3">
  <div class="row-salon cols-2">
    <div>
      <label class="form-salon-label">Cari nama / nomor WA / email</label>
      <input class="form-salon-input" type="text" name="q" value="<?= esc($q) ?>" placeholder="Misal: Ari, 0812…, atau @gmail">
    </div>
    <div style="display:flex; align-items:end; gap:0.5rem;">
      <button class="btn-salon-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
      <?php if ($q !== ''): ?>
        <a class="btn-salon-secondary" href="<?= base_url('admin/pelanggan') ?>">Reset</a>
      <?php endif ?>
    </div>
  </div>
</form>

<div class="card-salon">
  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-people empty-state__icon"></i>
      <div class="empty-state__title">Belum ada pelanggan</div>
      <div class="empty-state__hint">Akun pelanggan akan muncul setelah ada yang daftar di <a href="<?= base_url('register') ?>">/register</a>.</div>
    </div>
  <?php else: ?>
    <table class="table-salon">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Nomor WhatsApp</th>
          <th>Email</th>
          <th class="text-right">Booking</th>
          <th>Terakhir</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td style="font-weight:500;"><?= esc($r['nama']) ?></td>
            <td><?= esc($r['nomor_hp']) ?></td>
            <td><?= esc($r['email'] ?? '—') ?></td>
            <td class="text-right"><?= esc($r['total_booking']) ?></td>
            <td class="caption"><?= $r['terakhir'] ? esc(date('d M Y', strtotime($r['terakhir']))) : '—' ?></td>
            <td>
              <div class="flex gap-1" style="flex-wrap:wrap;">
                <button class="btn-salon-secondary btn-salon--sm" data-bs-toggle="modal" data-bs-target="#editNamaModal"
                        data-id="<?= $r['id'] ?>" data-nama="<?= esc($r['nama']) ?>">
                  <i class="bi bi-pencil"></i> Edit nama
                </button>
                <button class="btn-salon-danger btn-salon--sm" data-bs-toggle="modal" data-bs-target="#resetPwModal"
                        data-id="<?= $r['id'] ?>" data-nama="<?= esc($r['nama']) ?>">
                  <i class="bi bi-key"></i> Reset password
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <div class="caption mt-2">
      <i class="bi bi-info-circle"></i> Nomor WhatsApp &amp; email tidak dapat diubah — keduanya identitas akun &amp; tujuan notifikasi.
    </div>
  <?php endif ?>
</div>

<!-- Modal: Edit Nama -->
<div class="modal fade" id="editNamaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="editNamaForm" method="post" action="">
        <?= csrf_field() ?>
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" style="font-family:var(--font-display);"><i class="bi bi-pencil" style="color:var(--gold);"></i> Edit Nama Pelanggan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-salon-label">Nama lengkap *</label>
          <input class="form-salon-input" type="text" name="nama" id="editNamaInput" required minlength="3" maxlength="100">
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gold-border); gap:0.5rem;">
          <button type="button" class="btn-salon-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-salon-primary"><i class="bi bi-check2"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPwModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--gold-border); color:var(--text-primary);">
      <form id="resetPwForm" method="post" action="">
        <?= csrf_field() ?>
        <div class="modal-header" style="border-bottom:1px solid var(--gold-border);">
          <h5 class="modal-title" style="font-family:var(--font-display);"><i class="bi bi-key" style="color:var(--color-danger);"></i> Reset Password</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Atur password baru untuk <strong id="resetPwNama"></strong>:</p>
          <label class="form-salon-label">Password baru (min 8 karakter) *</label>
          <input class="form-salon-input" type="text" name="new_password" required minlength="8" placeholder="Catat & beritahu pelanggan">
          <div class="form-salon-help">Catat dulu password ini sebelum klik Reset — sesudahnya tidak ada cara melihatnya.</div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gold-border); gap:0.5rem;">
          <button type="button" class="btn-salon-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-salon-danger"><i class="bi bi-key"></i> Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const editModal = document.getElementById('editNamaModal');
  editModal?.addEventListener('show.bs.modal', (ev) => {
    const b = ev.relatedTarget;
    document.getElementById('editNamaInput').value = b.getAttribute('data-nama') || '';
    document.getElementById('editNamaForm').action =
      '<?= base_url('admin/pelanggan/') ?>' + b.getAttribute('data-id') + '/update-nama';
  });
  const resetModal = document.getElementById('resetPwModal');
  resetModal?.addEventListener('show.bs.modal', (ev) => {
    const b = ev.relatedTarget;
    document.getElementById('resetPwNama').textContent = b.getAttribute('data-nama') || '';
    document.getElementById('resetPwForm').action =
      '<?= base_url('admin/pelanggan/') ?>' + b.getAttribute('data-id') + '/reset-password';
  });
})();
</script>

<?= $this->endSection() ?>
