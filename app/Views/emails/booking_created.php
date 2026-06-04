<?= $this->extend('emails/_layout') ?>
<?= $this->section('content') ?>
<?php
$tanggal = date('d/m/Y', strtotime((string) $b['tanggal']));
$mulai = substr((string) $b['slot_mulai'], 0, 5);
$selesai = substr((string) $b['slot_selesai'], 0, 5);
$dp = number_format((int) ($b['dp_amount'] ?? 0), 0, ',', '.');
?>
<h2 style="margin:0 0 12px;font-family:Georgia,serif;color:#C9A66B;">Halo, <?= esc($b['nama_pelanggan']) ?></h2>
<p style="margin:0 0 14px;">Booking Anda sudah kami terima dan saat ini <strong style="color:#C9A66B;">menunggu verifikasi admin</strong>. Anda akan menerima email lanjutan begitu booking dikonfirmasi.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;">
  <tr><td style="padding:6px 0;color:#a8a39c;width:40%;">Kode Booking</td><td style="padding:6px 0;font-weight:600;color:#C9A66B;letter-spacing:1.5px;"><?= esc($b['kode_booking']) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Layanan</td><td style="padding:6px 0;"><?= esc($b['nama_layanan']) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Tanggal</td><td style="padding:6px 0;"><?= esc($tanggal) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Jam</td><td style="padding:6px 0;font-weight:600;"><?= esc($mulai) ?> &ndash; <?= esc($selesai) ?> WITA</td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">DP</td><td style="padding:6px 0;">Rp <?= esc($dp) ?></td></tr>
</table>

<p style="margin:14px 0 8px;color:#a8a39c;font-size:13px;">Kami akan mengirim email pengingat sekitar 30 menit sebelum jadwal Anda.</p>
<?= $this->endSection() ?>
