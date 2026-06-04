<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<div class="card-salon" style="background:var(--card); text-align:center;">
  <i class="bi bi-shield-lock" style="font-size:2rem; color:var(--gold);"></i>
  <div class="h2 mt-1" style="margin-bottom:0.5rem;">Lupa Password</div>

  <div class="alert-salon alert-salon--info" style="text-align:left;">
    <i class="bi bi-info-circle"></i>
    Reset password hanya bisa dilakukan oleh <strong>pemilik / admin salon</strong>.
    Silakan hubungi salon via WhatsApp dan sebutkan nama + nomor akun Anda — admin akan
    mengatur ulang password Anda.
  </div>

  <?php if ($wa_link !== ''): ?>
    <a class="btn-salon-success btn-salon--full mt-2" target="_blank" rel="noopener" href="<?= esc($wa_link) ?>">
      <i class="bi bi-whatsapp"></i> Hubungi salon via WhatsApp
    </a>
  <?php endif ?>

  <div class="mt-2 caption">
    <a href="<?= base_url('login') ?>">&larr; Kembali ke login</a>
  </div>
</div>
<?= $this->endSection() ?>
