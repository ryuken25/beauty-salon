<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <span class="gold-badge badge rounded-pill mb-2">Pengaturan</span>
  <h1 class="h2 mb-2">Pengaturan Salon</h1>
  <p class="small-muted mb-0">Atur jam operasional salon, kontak WhatsApp, dan daftar chat Telegram admin.</p>
</div>

<div class="form-card">
  <form method="post">
    <?= csrf_field() ?>

    <h2 class="h5 section-title mb-3">Jam Operasional Salon</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label">Jam Buka</label>
        <input type="time" class="form-control" name="salon_open_time" value="<?= esc($settings['salon_open_time'] ?? '09:00') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Jam Tutup</label>
        <input type="time" class="form-control" name="salon_close_time" value="<?= esc($settings['salon_close_time'] ?? '21:00') ?>" required>
      </div>
      <div class="col-12">
        <p class="small-muted mb-0">Slot booking hanya dapat dipilih dalam rentang jam operasional ini, terlepas dari jam kerja stylist individual.</p>
      </div>
    </div>

    <h2 class="h5 section-title mb-3">Kontak WhatsApp</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label">Nomor WhatsApp Salon (umum)</label>
        <input class="form-control" name="salon_whatsapp" value="<?= esc($settings['salon_whatsapp'] ?? '') ?>" placeholder="6281234567890">
      </div>
      <div class="col-md-6">
        <label class="form-label">Nomor WhatsApp Owner (untuk auto-redirect pelanggan)</label>
        <input class="form-control" name="salon_whatsapp_owner" value="<?= esc($settings['salon_whatsapp_owner'] ?? '') ?>" placeholder="6281234567890">
        <div class="small-muted mt-1">Pelanggan akan diarahkan ke nomor ini setelah submit booking.</div>
      </div>
    </div>

    <h2 class="h5 section-title mb-3">Telegram</h2>
    <div class="mb-3">
      <label class="form-label">Telegram Chat ID Diizinkan (pisahkan koma)</label>
      <input class="form-control" name="telegram_allowed_chat_ids" value="<?= esc($settings['telegram_allowed_chat_ids'] ?? '') ?>">
      <p class="small-muted mt-1">Token bot disimpan di file .env. Pada environment production, token & chat ID wajib diisi.</p>
    </div>

    <button class="btn btn-primary" type="submit">Simpan</button>
  </form>
</div>

<?= $this->endSection() ?>
