<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Login · SW Beauty Salon') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,500&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/css/salon-theme.css') ?>" rel="stylesheet">
</head>
<body>
<div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding: 1.5rem;">
  <div style="width:100%; max-width:400px;">
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert-salon alert-salon--success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert-salon alert-salon--error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif ?>
    <?= $this->renderSection('content') ?>
  </div>
</div>
</body>
</html>
