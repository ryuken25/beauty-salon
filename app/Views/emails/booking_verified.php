<?= $this->extend('emails/_layout') ?>
<?= $this->section('content') ?>
<?php
$tanggal = date('d/m/Y', strtotime((string) $b['tanggal']));
$mulai = substr((string) $b['slot_mulai'], 0, 5);
$selesai = substr((string) $b['slot_selesai'], 0, 5);
$cekLink = rtrim((string) config('App')->baseURL, '/') . '/cek-booking';
?>
<h2 style="margin:0 0 12px;font-family:Georgia,serif;color:#C9A66B;">Booking Anda dikonfirmasi</h2>
<p style="margin:0 0 14px;">Halo <strong><?= esc($b['nama_pelanggan']) ?></strong>, booking Anda sudah <strong style="color:#7bd389;">diterima</strong> oleh admin. Sampai jumpa di salon!</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;">
  <tr><td style="padding:6px 0;color:#a8a39c;width:40%;">Kode Booking</td><td style="padding:6px 0;font-weight:600;color:#C9A66B;letter-spacing:1.5px;"><?= esc($b['kode_booking']) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Layanan</td><td style="padding:6px 0;"><?= esc($b['nama_layanan']) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Tanggal</td><td style="padding:6px 0;"><?= esc($tanggal) ?></td></tr>
  <tr><td style="padding:6px 0;color:#a8a39c;">Jam</td><td style="padding:6px 0;font-weight:600;"><?= esc($mulai) ?> &ndash; <?= esc($selesai) ?> WITA</td></tr>
</table>

<p style="margin:14px 0 6px;">Mohon datang tepat waktu. Bila perlu mengubah jadwal, hubungi salon via WhatsApp.</p>
<p style="margin:8px 0 0;font-size:13px;">Cek status &amp; riwayat: <a href="<?= esc($cekLink) ?>" style="color:#C9A66B;"><?= esc($cekLink) ?></a></p>
<?= $this->endSection() ?>
