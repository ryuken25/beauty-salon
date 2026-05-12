<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $hari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']; ?>

<div class="page-header-salon">
  <div>
    <div class="h1">Jadwal · <?= esc($stylist['nama']) ?></div>
    <div class="tagline">Hari kerja dan jam operasional</div>
  </div>
  <a class="btn-salon-ghost" href="<?= base_url('admin/stylist') ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<form class="card-salon" method="post">
  <?= csrf_field() ?>
  <table class="table-salon">
    <thead><tr><th>Hari</th><th>Mulai</th><th>Selesai</th><th>Libur</th></tr></thead>
    <tbody>
      <?php foreach ($hari as $h): $s = $schedules[$h]; ?>
        <tr>
          <td style="text-transform:capitalize;"><?= esc($h) ?></td>
          <td><input class="form-salon-input" type="time" name="mulai_<?= $h ?>" value="<?= esc(substr($s['jam_mulai'], 0, 5)) ?>"></td>
          <td><input class="form-salon-input" type="time" name="selesai_<?= $h ?>" value="<?= esc(substr($s['jam_selesai'], 0, 5)) ?>"></td>
          <td><label class="flex items-center gap-1 caption"><input type="checkbox" name="libur_<?= $h ?>" value="1" <?= $s['is_libur'] ? 'checked' : '' ?>> Libur</label></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <button class="btn-salon-primary mt-2" type="submit">Simpan jadwal</button>
</form>

<?= $this->endSection() ?>
