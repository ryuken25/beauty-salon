<?= $this->extend('layouts/owner') ?>
<?= $this->section('content') ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Pengaturan</div>
    <div class="tagline">Konfigurasi salon, WhatsApp, akun</div>
  </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabSalon">Salon</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabWa">WhatsApp</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAkun">Akun</button></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tabSalon">
    <form class="card-salon" method="post">
      <?= csrf_field() ?>
      <div class="h2 mb-2">Informasi salon</div>
      <div class="row-salon cols-2">
        <div><label class="form-salon-label">Nama salon</label><input class="form-salon-input" name="nama_salon" value="<?= esc($s['nama_salon'] ?? '') ?>"></div>
        <div><label class="form-salon-label">Telepon</label><input class="form-salon-input" name="telp_salon" value="<?= esc($s['telp_salon'] ?? '') ?>"></div>
        <div style="grid-column:1/-1;"><label class="form-salon-label">Alamat</label><input class="form-salon-input" name="alamat_salon" value="<?= esc($s['alamat_salon'] ?? '') ?>"></div>
        <div><label class="form-salon-label">Jam buka</label><input class="form-salon-input" type="time" name="jam_buka" value="<?= esc($s['jam_buka'] ?? '08:00') ?>"></div>
        <div><label class="form-salon-label">Jam tutup</label><input class="form-salon-input" type="time" name="jam_tutup" value="<?= esc($s['jam_tutup'] ?? '19:00') ?>"></div>
        <div><label class="form-salon-label">Range hari booking (ke depan)</label><input class="form-salon-input" type="number" min="1" max="30" name="range_hari_booking" value="<?= esc($s['range_hari_booking'] ?? '7') ?>"></div>
        <div><label class="form-salon-label">Durasi slot</label><input class="form-salon-input" value="30 menit (fixed sesuai novelty)" disabled></div>
      </div>
      <button class="btn-salon-primary mt-2" type="submit">Simpan</button>
    </form>
  </div>

  <div class="tab-pane fade" id="tabWa">
    <form class="card-salon" method="post">
      <?= csrf_field() ?>
      <div class="h2 mb-2">WhatsApp manual</div>
      <div><label class="form-salon-label">Nomor HP owner (format internasional)</label><input class="form-salon-input" name="nomor_hp_owner" value="<?= esc($s['nomor_hp_owner'] ?? '') ?>" placeholder="6281234567890"></div>
      <div class="mt-2"><label class="form-salon-label">Template pesan diterima</label><textarea class="form-salon-textarea" rows="3" name="template_wa_diterima"><?= esc($s['template_wa_diterima'] ?? '') ?></textarea></div>
      <div class="mt-2"><label class="form-salon-label">Template pesan ditolak</label><textarea class="form-salon-textarea" rows="3" name="template_wa_ditolak"><?= esc($s['template_wa_ditolak'] ?? '') ?></textarea></div>
      <div class="mt-2"><label class="form-salon-label">Template reminder</label><textarea class="form-salon-textarea" rows="3" name="template_wa_reminder"><?= esc($s['template_wa_reminder'] ?? '') ?></textarea></div>
      <div class="mt-2"><label class="form-salon-label">Template selesai</label><textarea class="form-salon-textarea" rows="3" name="template_wa_selesai"><?= esc($s['template_wa_selesai'] ?? '') ?></textarea></div>
      <div class="caption mt-1">Variabel: {nama} {kode} {layanan} {tanggal} {jam_mulai} {jam_selesai} {nominal} {nomor_owner}</div>
      <button class="btn-salon-primary mt-2" type="submit">Simpan</button>
    </form>
  </div>

  <div class="tab-pane fade" id="tabAkun">
    <form class="card-salon mb-2" method="post" action="<?= base_url('owner/pengaturan/ganti-password') ?>">
      <?= csrf_field() ?>
      <div class="h2 mb-2">Ganti password</div>
      <div><label class="form-salon-label">Password lama</label><input class="form-salon-input" type="password" name="current_password" required></div>
      <div class="mt-1"><label class="form-salon-label">Password baru (min 8 karakter)</label><input class="form-salon-input" type="password" name="new_password" required minlength="8"></div>
      <button class="btn-salon-primary mt-2" type="submit">Ganti password</button>
    </form>
    <div class="card-salon">
      <div class="h2 mb-2">Daftar pengguna</div>
      <table class="table-salon">
        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Aktif</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr><td><?= esc($u['nama']) ?></td><td><?= esc($u['email']) ?></td><td><?= esc($u['role']) ?></td><td><?= $u['is_active'] ? 'Ya' : 'Tidak' ?></td></tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
