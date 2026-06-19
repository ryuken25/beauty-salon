<?= $this->extend('emails/_layout') ?>
<?= $this->section('content') ?>
<?php
$tanggal = date('d/m/Y', strtotime((string) $b['tanggal']));
$mulai = substr((string) $b['slot_mulai'], 0, 5);
$selesai = substr((string) $b['slot_selesai'], 0, 5);
$cekLink = rtrim((string) config('App')->baseURL, '/') . '/cek-booking';
$hargaLayanan = (int) ($b['final_service_price'] ?? $b['harga_layanan']);
$dpAmount = (int) ($b['dp_amount'] ?? 0);
$remaining = (int) ($b['remaining_payment'] ?? ($hargaLayanan - $dpAmount));
?>
<h2 style="margin:0 0 12px;font-family:Georgia,serif;color:#C9A66B;">DP Booking Terverifikasi</h2>
<p style="margin:0 0 14px;">Halo <strong><?= esc($b['nama_pelanggan']) ?></strong>, pembayaran uang muka (DP) Anda untuk booking layanan kecantikan telah <strong style="color:#7bd389;">terverifikasi</strong>. Booking Anda kini terjadwal.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;border:1px solid #2a2418;border-radius:6px;background:#1b1b1c;">
  <tr>
    <td style="padding:10px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;width:40%;">Kode Booking</td>
    <td style="padding:10px 14px;font-weight:600;color:#C9A66B;letter-spacing:1px;border-bottom:1px solid #2a2418;"><?= esc($b['kode_booking']) ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Layanan</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;"><?= esc($b['nama_layanan']) ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Jadwal Treatment</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;"><?= esc($tanggal) ?> pukul <?= esc($mulai) ?> &ndash; <?= esc($selesai) ?> WITA</td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Harga Layanan</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;font-weight:500;">Rp <?= number_format($hargaLayanan, 0, ',', '.') ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">DP Terbayar</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#7bd389;font-weight:600;">Rp <?= number_format($dpAmount, 0, ',', '.') ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Status DP</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#7bd389;font-weight:600;text-transform:uppercase;font-size:12px;letter-spacing:1px;">Terverifikasi</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;color:#a8a39c;font-weight:500;">Sisa Pembayaran</td>
    <td style="padding:10px 14px;color:#C9A66B;font-weight:600;font-size:15px;">Rp <?= number_format($remaining, 0, ',', '.') ?></td>
  </tr>
</table>

<p style="margin:14px 0 14px;font-size:13px;color:#a8a39c;">
  <strong>Catatan:</strong> Sisa estimasi pembayaran dibayarkan secara langsung di salon setelah treatment selesai.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;border-top:1px solid #2a2418;">
  <tr>
    <td style="padding-top:14px;font-size:13px;color:#a8a39c;">
      Nama Pelanggan: <strong><?= esc($b['nama_pelanggan']) ?></strong><br>
      Nomor WhatsApp: <strong><?= esc($b['nomor_hp_pelanggan']) ?></strong><br>
      <?php if (!empty($b['email_pelanggan'])): ?>Email Pelanggan: <strong><?= esc($b['email_pelanggan']) ?></strong><br><?php endif ?>
    </td>
  </tr>
</table>

<p style="margin:16px 0 0;font-size:13px;">Cek status &amp; riwayat secara berkala di: <a href="<?= esc($cekLink) ?>" style="color:#C9A66B;"><?= esc($cekLink) ?></a></p>
<?= $this->endSection() ?>
