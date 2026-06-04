<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$statusMap = [
    'pending_verification' => ['Menunggu Verifikasi', 'pending'],
    'accepted' => ['Diterima', 'accepted'],
    'completed' => ['Selesai', 'completed'],
    'rejected' => ['Ditolak', 'rejected'],
    'cancelled' => ['Batal', 'cancelled'],
];
$cancelable = ['pending_verification', 'accepted'];
$payMap = [
    'unpaid' => 'Belum bayar DP',
    'dp_uploaded' => 'DP menunggu verifikasi',
    'dp_verified' => 'DP terverifikasi',
];
?>

<section class="section-header">
  <div class="h1">Cek Booking</div>
  <span class="tagline">Masukkan kode booking yang dikirim ke email Anda</span>
  <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
</section>

<div class="card-salon" style="max-width:520px; margin:0 auto;">
  <form method="post" action="<?= base_url('cek-booking') ?>">
    <?= csrf_field() ?>
    <label class="form-salon-label">Kode Booking</label>
    <input class="form-salon-input" type="text" name="kode_booking" required value="<?= esc(old('kode_booking') ?? $kode) ?>" placeholder="SW-YYYYMMDD-NNN" style="text-transform:uppercase; letter-spacing:1.5px;">
    <button class="btn-salon-primary btn-salon--full mt-2" type="submit"><i class="bi bi-search"></i> Cari booking</button>
  </form>
  <div class="form-salon-help mt-2" style="text-align:center;">
    Belum punya kode? Cek email konfirmasi booking. Atau <a href="<?= base_url('login') ?>">login</a> untuk lihat semua booking Anda.
  </div>
</div>

<?php if (! empty($booking)):
  [$label, $cls] = $statusMap[$booking['status']] ?? [$booking['status'], 'pending'];
  $isCancelable = in_array($booking['status'], $cancelable, true);
  $isLoggedInPelanggan = session('is_logged_in') && session('user_role') === 'pelanggan';
?>
  <div class="mt-3" style="max-width:520px; margin:1rem auto 0;">
    <div class="card-salon card-salon--<?= esc($cls) ?>">
      <div class="caption" style="letter-spacing:1.5px;"><?= esc($booking['kode_booking']) ?></div>
      <div class="h2 mb-2"><?= esc($booking['nama_layanan']) ?></div>

      <table style="width:100%; font-size:0.875rem;">
        <tr><td class="label">Tanggal</td><td class="text-right"><?= esc(date('d M Y', strtotime((string) $booking['tanggal']))) ?></td></tr>
        <tr><td class="label">Jam</td><td class="text-right" style="font-weight:500;"><?= esc(substr((string) $booking['slot_mulai'], 0, 5)) ?> – <?= esc(substr((string) $booking['slot_selesai'], 0, 5)) ?></td></tr>
        <tr><td class="label">DP</td><td class="text-right">Rp <?= number_format((int) ($booking['dp_amount'] ?? 0), 0, ',', '.') ?> · <?= esc($payMap[$booking['payment_status'] ?? 'unpaid'] ?? '—') ?></td></tr>
        <?php if (! empty($booking['cancellation_reason'])): ?>
          <tr><td class="label">Alasan batal</td><td class="text-right" style="color:var(--color-danger);"><?= esc($booking['cancellation_reason']) ?></td></tr>
        <?php endif ?>
      </table>

      <div class="text-center mt-2"><span class="badge-salon badge-salon--<?= esc($cls) ?>"><?= esc($label) ?></span></div>

      <?php if ($isCancelable): ?>
        <div class="ornament-rule"><span class="ornament-rule__line"></span><i class="bi bi-gem ornament-rule__icon"></i><span class="ornament-rule__line"></span></div>
        <?php if ($isLoggedInPelanggan): ?>
          <a class="btn-salon-danger btn-salon--full" href="<?= base_url('pelanggan/booking/' . rawurlencode($booking['kode_booking']) . '/batal') ?>">
            <i class="bi bi-x-circle"></i> Batalkan booking
          </a>
        <?php else: ?>
          <div class="alert-salon alert-salon--info" style="margin:0;">
            <i class="bi bi-info-circle"></i> <strong>Login untuk membatalkan booking ini</strong> — pembatalan hanya bisa lewat akun pelanggan.
            <div class="mt-1">
              <a class="btn-salon-secondary btn-salon--sm" href="<?= base_url('login') ?>"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            </div>
          </div>
        <?php endif ?>
      <?php endif ?>
    </div>
  </div>
<?php endif ?>

<div class="text-center mt-3">
  <a href="<?= base_url('/') ?>">&larr; Kembali ke beranda</a>
</div>

<?= $this->endSection() ?>
