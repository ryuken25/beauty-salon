<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Jam kerja</span>
  <h1 class="h2 mb-2">Jam Kerja <?= esc($stylist['name']) ?></h1>
  <p class="small-muted mb-0">Atur hari dan jam kerja agar pilihan slot pelanggan tetap sesuai jadwal stylist.</p>
</div>

<form method="post" class="table-card">
  <?= csrf_field() ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Hari</th><th>Bekerja</th><th>Mulai</th><th>Selesai</th></tr></thead>
      <tbody>
        <?php $names = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']; $map = []; foreach ($hours as $h) $map[$h['day_of_week']] = $h; for ($d = 0; $d <= 6; $d++): $h = $map[$d] ?? ['is_working' => 1, 'start_time' => '08:00', 'end_time' => '19:00']; ?>
          <tr>
            <td><strong><?= $names[$d] ?></strong></td>
            <td><input class="form-check-input" type="checkbox" name="work_<?= $d ?>" value="1" <?= $h['is_working'] ? 'checked' : '' ?>></td>
            <td><input type="time" class="form-control" name="start_<?= $d ?>" value="<?= substr($h['start_time'], 0, 5) ?>"></td>
            <td><input type="time" class="form-control" name="end_<?= $d ?>" value="<?= substr($h['end_time'], 0, 5) ?>"></td>
          </tr>
        <?php endfor ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex flex-wrap gap-2 mt-3">
    <button class="btn btn-primary" type="submit">Simpan Jam Kerja</button>
    <a class="btn btn-outline-secondary" href="<?= base_url('admin/stylist') ?>">Kembali</a>
  </div>
</form>

<?= $this->endSection() ?>
