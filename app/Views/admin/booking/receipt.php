<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt DP #<?= esc($booking['kode_booking']) ?> - SW Beauty Salon</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;1,400&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
  <style>
    body {
      background: var(--bg);
      color: var(--text-primary);
      font-family: 'Plus Jakarta Sans', sans-serif;
      padding: 2rem 1rem;
    }
    .receipt-paper {
      background: var(--card);
      border: 1px solid var(--gold-border);
      border-radius: var(--radius-md);
      max-width: 500px;
      margin: 0 auto;
      padding: 2.5rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .receipt-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .receipt-header h1 {
      font-family: 'Playfair Display', serif;
      color: var(--gold);
      font-size: 1.8rem;
      margin: 0 0 0.5rem;
    }
    .receipt-header p {
      font-size: 0.8rem;
      color: var(--text-secondary);
      margin: 0;
    }
    .receipt-divider {
      border-top: 1px dashed var(--gold-border);
      margin: 1.5rem 0;
    }
    .receipt-details td {
      padding: 0.4rem 0;
      font-size: 0.875rem;
    }
    .receipt-details .label {
      color: var(--text-secondary);
    }
    .receipt-details .value {
      text-align: right;
      font-weight: 500;
      color: var(--text-primary);
    }
    .receipt-items {
      width: 100%;
      margin: 1.5rem 0;
      font-size: 0.875rem;
    }
    .receipt-items th {
      border-bottom: 1px solid var(--gold-border);
      padding-bottom: 0.5rem;
      color: var(--text-secondary);
      font-weight: 500;
    }
    .receipt-items td {
      padding: 0.6rem 0;
    }
    .receipt-totals td {
      padding: 0.4rem 0;
      font-size: 0.875rem;
    }
    .receipt-totals .highlight {
      font-size: 1.15rem;
      font-weight: 600;
      color: var(--gold);
    }
    .no-print-actions {
      max-width: 500px;
      margin: 1.5rem auto 0;
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    @media print {
      body {
        background: #fff !important;
        color: #000 !important;
        padding: 0 !important;
      }
      .receipt-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        background: #fff !important;
        color: #000 !important;
      }
      .receipt-header h1 {
        color: #000 !important;
      }
      .receipt-totals .highlight {
        color: #000 !important;
      }
      .no-print-actions {
        display: none !important;
      }
    }
  </style>
</head>
<body>

<div class="receipt-paper">
  <div class="receipt-header">
    <h1>SW Beauty Salon</h1>
    <p>Batunya, Kec. Baturiti, Tabanan, Bali</p>
    <p>Telp: +62 878-6218-3074</p>
  </div>

  <div style="text-align:center; margin-bottom:1.5rem;">
    <span class="badge-salon badge-salon--completed" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; padding:0.4rem 0.8rem;">RECEIPT DP TERVERIFIKASI</span>
  </div>

  <table class="receipt-details" style="width:100%;">
    <tr>
      <td class="label">No. Booking</td>
      <td class="value"><?= esc($booking['kode_booking']) ?></td>
    </tr>
    <tr>
      <td class="label">Tanggal Verifikasi DP</td>
      <td class="value"><?= esc($booking['dp_verified_at'] ? date('d M Y H:i', strtotime($booking['dp_verified_at'])) : date('d M Y H:i', strtotime($booking['verified_at']))) ?> WITA</td>
    </tr>
    <tr>
      <td class="label">Pelanggan</td>
      <td class="value"><?= esc($booking['nama_pelanggan']) ?></td>
    </tr>
    <tr>
      <td class="label">No. WhatsApp</td>
      <td class="value"><?= esc($booking['nomor_hp_pelanggan']) ?></td>
    </tr>
    <?php if (! empty($booking['email_pelanggan'])): ?>
    <tr>
      <td class="label">Email</td>
      <td class="value"><?= esc($booking['email_pelanggan']) ?></td>
    </tr>
    <?php endif ?>
    <tr>
      <td class="label">Kasir / Penerima</td>
      <td class="value"><?= esc($cashier) ?></td>
    </tr>
  </table>

  <div class="receipt-divider"></div>

  <table class="receipt-items">
    <thead>
      <tr>
        <th style="text-align:left;">Layanan / Treatment</th>
        <th style="text-align:right;">Harga</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $hargaNormal = (int) ($booking['original_service_price'] ?? $booking['harga_layanan']);
      $hargaFinal = (int) ($booking['final_service_price'] ?? $booking['harga_layanan']);
      $discountVal = (int) ($booking['promo_discount_value'] ?? 0);
      $promoName = $booking['promo_name'] ?? null;
      $dpPaid = (int) ($booking['dp_amount'] ?? 0);
      $remaining = (int) ($booking['remaining_payment'] ?? ($hargaFinal - $dpPaid));
      ?>
      <tr>
        <td style="color:var(--text-primary); font-weight:500;">
          <?= esc($booking['nama_layanan']) ?>
          <?php if ($discountVal > 0): ?>
            <div class="caption" style="color:var(--text-secondary); font-size:0.75rem; font-weight:normal; margin-top:0.2rem;">
              Normal: Rp <?= number_format($hargaNormal, 0, ',', '.') ?> (Diskon <?= esc($discountVal) ?>%<?= $promoName ? ' - ' . esc($promoName) : '' ?>)
            </div>
          <?php endif ?>
        </td>
        <td style="text-align:right; font-weight:500; vertical-align:top;">
          Rp <?= number_format($hargaFinal, 0, ',', '.') ?>
        </td>
      </tr>
    </tbody>
  </table>

  <div class="receipt-divider"></div>

  <table class="receipt-totals" style="width:100%;">
    <tr>
      <td class="label">Total Layanan</td>
      <td class="value">Rp <?= number_format($hargaFinal, 0, ',', '.') ?></td>
    </tr>
    <tr>
      <td class="label" style="font-weight:600; color:var(--text-primary);">DP Diterima</td>
      <td class="value" style="font-weight:600; color:var(--gold);">Rp <?= number_format($dpPaid, 0, ',', '.') ?></td>
    </tr>
    <tr class="receipt-divider"></tr>
    <tr>
      <td class="label highlight">Sisa Pembayaran Estimasi</td>
      <td class="value highlight">Rp <?= number_format($remaining, 0, ',', '.') ?></td>
    </tr>
  </table>

  <div class="receipt-divider" style="margin-top:1.5rem;"></div>

  <div style="text-align:center; font-size:0.75rem; color:var(--text-secondary); line-height:1.4;">
    Sisa estimasi pembayaran diselesaikan di salon setelah treatment selesai.<br>
    Terima kasih atas pembayaran DP Anda.<br><strong>SW Beauty Salon</strong>
  </div>
</div>

<div class="no-print-actions">
  <button class="btn-salon-primary" style="flex:1; min-width:140px;" onclick="window.print()"><i class="bi bi-printer"></i> Print Receipt</button>
  
  <?php if (! empty($booking['email_pelanggan'])): ?>
    <form method="post" action="<?= base_url('admin/booking/' . $booking['id'] . '/resend-email-dp') ?>" style="flex:1; min-width:160px; margin:0;">
      <?= csrf_field() ?>
      <button class="btn-salon-ghost btn-salon--full" type="submit"><i class="bi bi-envelope"></i> Kirim Ulang Email</button>
    </form>
  <?php endif ?>

  <a class="btn-salon-secondary" style="flex:1; text-align:center; display:block; text-decoration:none; min-width:100px;" href="<?= base_url('admin/booking/' . $booking['id']) ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

</body>
</html>
