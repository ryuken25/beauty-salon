<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Pengaturan</span>
  <h1 class="h2 mb-2">Pengaturan Dasar</h1>
  <p class="small-muted mb-0">Simpan kontak salon dan daftar chat Telegram yang boleh menerima notifikasi.</p>
</div>

<div class="form-card">
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Nomor WhatsApp Salon</label>
      <input class="form-control" name="salon_whatsapp" value="<?= esc($settings['salon_whatsapp'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Telegram Chat ID Diizinkan (pisahkan koma)</label>
      <input class="form-control" name="telegram_allowed_chat_ids" value="<?= esc($settings['telegram_allowed_chat_ids'] ?? '') ?>">
    </div>
    <p class="small-muted">Token bot disimpan di file .env, jangan ditulis ke source code.</p>
    <button class="btn btn-primary" type="submit">Simpan</button>
  </form>
</div>

<?= $this->endSection() ?>
