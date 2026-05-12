<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$cls = 'badge-salon--' . ($booking['status'] === 'pending_verification' ? 'pending' : str_replace('_', '-', $booking['status']));
$lbl = ['pending_verification' => 'Menunggu Verifikasi', 'accepted' => 'Diterima', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'][$booking['status']];
?>

<div class="page-header-salon">
  <div>
    <span class="caption" style="letter-spacing:2px;"><?= esc($booking['kode_booking']) ?></span>
    <div class="h1"><?= esc($booking['nama_pelanggan']) ?></div>
    <span class="badge-salon <?= $cls ?>"><?= esc($lbl) ?></span>
    <?php if ($booking['wa_sent']): ?> <span class="badge-salon badge-salon--completed"><i class="bi bi-whatsapp"></i> WA terkirim</span><?php endif ?>
  </div>
  <a class="btn-salon-ghost" href="<?= base_url('admin/booking') ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="split-60-40">
  <div>
    <div class="card-salon mb-2">
      <div class="h2 mb-2">Informasi booking</div>
      <table style="width:100%; font-size:0.875rem;">
        <tr><td class="label">No. HP</td><td class="text-right"><?= esc($booking['nomor_hp_pelanggan']) ?></td></tr>
        <tr><td class="label">Layanan</td><td class="text-right"><?= esc($booking['nama_layanan']) ?></td></tr>
        <tr><td class="label">Durasi</td><td class="text-right"><?= esc($booking['durasi_menit']) ?> menit</td></tr>
        <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime($booking['tanggal']))) ?></td></tr>
        <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr($booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr($booking['slot_selesai'], 0, 5)) ?></td></tr>
        <tr><td class="label">Stylist</td><td class="text-right"><?= esc($booking['nama_stylist']) ?></td></tr>
        <tr><td class="label">Sumber</td><td class="text-right"><?= esc(ucfirst($booking['sumber'])) ?></td></tr>
        <tr><td class="label">Harga</td><td class="text-right" style="font-weight:500;">Rp <?= number_format((int) $booking['harga_layanan'], 0, ',', '.') ?></td></tr>
        <?php if ($booking['catatan']): ?><tr><td class="label">Catatan</td><td class="text-right"><?= esc($booking['catatan']) ?></td></tr><?php endif ?>
        <?php if ($booking['rejection_reason']): ?><tr><td class="label">Alasan tolak</td><td class="text-right"><?= esc($booking['rejection_reason']) ?></td></tr><?php endif ?>
        <?php if ($booking['verified_via']): ?><tr><td class="label">Verifikasi via</td><td class="text-right"><?= esc($booking['verified_via']) ?></td></tr><?php endif ?>
      </table>
    </div>

    <div class="card-salon mb-2">
      <div class="h2 mb-2">Pesan WhatsApp manual</div>
      <textarea id="waMsg" class="form-salon-textarea" rows="7" readonly><?= esc($wa_message) ?></textarea>
      <div class="flex flex-wrap gap-1 mt-2">
        <button type="button" class="btn-salon-secondary" onclick="navigator.clipboard.writeText(document.getElementById('waMsg').value).then(()=>alert('Pesan disalin.'))"><i class="bi bi-clipboard"></i> Salin pesan</button>
        <a class="btn-salon-success" target="_blank" rel="noopener" href="<?= esc($wa_link) ?>"><i class="bi bi-whatsapp"></i> Buka WhatsApp</a>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/wa-sent') ?>">
          <?= csrf_field() ?>
          <button class="btn-salon-ghost" type="submit"><i class="bi bi-check2"></i> Tandai sudah dikirim</button>
        </form>
      </div>
    </div>

    <div class="card-salon">
      <div class="h2 mb-2">Riwayat aktivitas</div>
      <?= view('partials/booking_timeline', ['logs' => $logs]) ?>
    </div>
  </div>

  <div>
    <div class="card-salon">
      <div class="h2 mb-2">Aksi</div>
      <?php if ($booking['status'] === 'pending_verification'): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/verify') ?>" class="mb-2">
          <?= csrf_field() ?>
          <button class="btn-salon-primary btn-salon--full" type="submit"><i class="bi bi-check-circle"></i> Verifikasi</button>
        </form>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/reject') ?>" class="mb-2">
          <?= csrf_field() ?>
          <input class="form-salon-input mb-1" type="text" name="rejection_reason" placeholder="Alasan penolakan (opsional)">
          <button class="btn-salon-danger btn-salon--full" type="submit"><i class="bi bi-x-circle"></i> Tolak</button>
        </form>
      <?php endif ?>
      <?php if ($booking['status'] === 'accepted'): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/complete') ?>" class="mb-2">
          <?= csrf_field() ?>
          <label class="form-salon-label">Metode bayar</label>
          <select class="form-salon-select mb-1" name="metode_bayar">
            <option value="cash">Cash</option>
            <option value="transfer">Transfer</option>
            <option value="qris">QRIS</option>
          </select>
          <input class="form-salon-input mb-1" name="catatan" placeholder="Catatan (opsional)">
          <button class="btn-salon-primary btn-salon--full" type="submit"><i class="bi bi-trophy"></i> Selesaikan + transaksi</button>
        </form>
      <?php endif ?>
      <?php if (in_array($booking['status'], ['pending_verification', 'accepted'], true)): ?>
        <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/cancel') ?>" onsubmit="return confirm('Batalkan booking ini?');">
          <?= csrf_field() ?>
          <button class="btn-salon-danger btn-salon--full" type="submit"><i class="bi bi-slash-circle"></i> Batalkan</button>
        </form>
      <?php endif ?>
      <?php if (in_array($booking['status'], ['completed', 'rejected', 'cancelled'], true)): ?>
        <div class="caption">Booking sudah final. Lihat riwayat di timeline.</div>
      <?php endif ?>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
