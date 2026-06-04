<?= $this->extend('emails/_layout') ?>
<?= $this->section('content') ?>
<?php
$mulai = substr((string) $b['slot_mulai'], 0, 5);
$selesai = substr((string) $b['slot_selesai'], 0, 5);
?>
<h2 style="margin:0 0 12px;font-family:Georgia,serif;color:#C9A66B;">Sebentar lagi sesi Anda</h2>
<p style="margin:0 0 14px;">Halo <strong><?= esc($b['nama_pelanggan']) ?></strong>, ini pengingat bahwa sesi Anda akan dimulai sekitar <strong style="color:#C9A66B;">30 menit lagi</strong>.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;">
  <tr><td style="padding:6px 0;color:#a8a39c;width:40%;">Kode Booking</td><td style="padding:6px 0;font-weight:600;color:#C9A66B;letter-spacing:1.5px;"><?= esc($b['kode_booking']) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Layanan</td><td style="padding:6px 0;"><?= esc($b['nama_layanan']) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Jam</td><td style="padding:6px 0;font-weight:600;"><?= esc($mulai) ?> &ndash; <?= esc($selesai) ?> WITA</td></tr>
</table>

<p style="margin:14px 0 6px;">Mohon datang tepat waktu. Kalau ada kendala, segera hubungi admin via WhatsApp.</p>
<?= $this->endSection() ?>
