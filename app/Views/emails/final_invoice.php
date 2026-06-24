<?= $this->extend('emails/_layout') ?>
<?= $this->section('content') ?>
<?php
$tanggal = date('d/m/Y', strtotime((string) $b['tanggal']));
$mulai = substr((string) $b['slot_mulai'], 0, 5);
$selesai = substr((string) $b['slot_selesai'], 0, 5);
$hargaNormal = (int) ($b['original_service_price'] ?? $b['harga_layanan']);
$promoName = $b['promo_name'] ?? null;
$discountVal = (int) ($b['promo_discount_value'] ?? 0);
$hargaFinal = (int) ($b['final_service_price'] ?? $b['harga_layanan']);
$biayaTambahan = (int) ($t['additional_price'] ?? 0);
$dpPaid = (int) ($t['dp_paid'] ?? 0);
$sisaBayar = (int) ($t['sisa_bayar'] ?? 0);
$totalPendapatan = (int) ($t['nominal'] ?? ($hargaFinal + $biayaTambahan));
$metodeBayar = strtoupper((string) ($t['metode_bayar'] ?? 'CASH'));
$catatanTrans = trim((string) ($t['catatan'] ?? ''));
$cekLink = rtrim((string) config('App')->baseURL, '/') . '/cek-booking';
?>
<h2 style="margin:0 0 12px;font-family:Georgia,serif;color:#C9A66B;">Invoice Treatment Selesai</h2>
<p style="margin:0 0 14px;">Halo <strong><?= esc($b['nama_pelanggan']) ?></strong>, terima kasih telah melakukan perawatan di salon kami. Berikut adalah rincian tagihan final Anda:</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;border:1px solid #2a2418;border-radius:6px;background:#1b1b1c;">
  <tr>
    <td style="padding:10px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;width:40%;">No. Invoice / Transaksi</td>
    <td style="padding:10px 14px;font-weight:600;color:#C9A66B;border-bottom:1px solid #2a2418;">#<?= esc($t['id'] ?? '—') ?> (Booking: <?= esc($b['kode_booking']) ?>)</td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Tanggal Invoice</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;"><?= esc(date('d M Y H:i', strtotime($t['tanggal_transaksi']))) ?> WITA</td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Layanan</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;"><?= esc($b['nama_layanan']) ?> (<?= esc($tanggal) ?>)</td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Harga Normal</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;text-decoration:<?= $discountVal > 0 ? 'line-through' : 'none' ?>;">Rp <?= number_format($hargaNormal, 0, ',', '.') ?></td>
  </tr>
  <?php if ($discountVal > 0): ?>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Promo/Diskon</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#7bd389;">-<?= esc($discountVal) ?>% <?= $promoName ? '(' . esc($promoName) . ')' : '' ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Harga Promo</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;font-weight:500;">Rp <?= number_format($hargaFinal, 0, ',', '.') ?></td>
  </tr>
  <?php endif ?>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Biaya Tambahan</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;">Rp <?= number_format($biayaTambahan, 0, ',', '.') ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">
      Uang Muka (DP) Terbayar
      <?php if ($dpPaid > 0 && ! empty($b['dp_verified_at'])): ?>
        <div style="font-size:11px;color:#a8a39c;font-weight:normal;margin-top:2px;">
          Terbayar: <?= esc(date('d M Y H:i', strtotime($b['dp_verified_at']))) ?> WITA
        </div>
      <?php endif ?>
    </td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;vertical-align:top;">-Rp <?= number_format($dpPaid, 0, ',', '.') ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Metode Pelunasan</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#e8e6e3;font-weight:500;vertical-align:top;"><?= esc($metodeBayar) ?></td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Status</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#7bd389;font-weight:600;text-transform:uppercase;vertical-align:top;">LUNAS</td>
  </tr>
  <tr>
    <td style="padding:8px 14px;color:#a8a39c;border-bottom:1px solid #2a2418;">Pelunasan Dibayar</td>
    <td style="padding:8px 14px;border-bottom:1px solid #2a2418;color:#7bd389;font-weight:600;">Rp <?= number_format($sisaBayar, 0, ',', '.') ?></td>
  </tr>
  <tr>
    <td style="padding:10px 14px;color:#a8a39c;font-weight:500;">Total Pendapatan Salon</td>
    <td style="padding:10px 14px;color:#C9A66B;font-weight:600;font-size:16px;">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></td>
  </tr>
</table>

<?php if ($catatanTrans !== ''): ?>
<div style="margin:14px 0;padding:12px;background:#161617;border-left:3px solid #C9A66B;font-size:13px;border-radius:4px;">
  <strong>Catatan Transaksi:</strong><br>
  <?= esc($catatanTrans) ?>
</div>
<?php endif ?>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;border-top:1px solid #2a2418;">
  <tr>
    <td style="padding-top:14px;font-size:13px;color:#a8a39c;">
      Nama Pelanggan: <strong><?= esc($b['nama_pelanggan']) ?></strong><br>
      Nomor WhatsApp: <strong><?= esc($b['nomor_hp_pelanggan']) ?></strong><br>
      <?php if (!empty($b['email_pelanggan'])): ?>Email Pelanggan: <strong><?= esc($b['email_pelanggan']) ?></strong><br><?php endif ?>
    </td>
  </tr>
</table>

<p style="margin:18px 0 0;font-family:Georgia,serif;font-style:italic;color:#C9A66B;text-align:center;font-size:15px;">
  Terima kasih atas kunjungan Anda. Kecantikan dan kepuasan Anda adalah kebanggaan kami!
</p>
<?= $this->endSection() ?>
