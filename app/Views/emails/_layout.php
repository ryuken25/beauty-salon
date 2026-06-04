<?php
$alamat = 'Batunya, Kec. Baturiti, Kabupaten Tabanan, Bali 82191';
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>SW Beauty Salon</title>
</head>
<body style="margin:0;padding:0;background:#0c0c0d;color:#e8e6e3;font-family:Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0c0c0d;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="background:#161617;border:1px solid #2a2418;border-radius:10px;max-width:560px;width:100%;">
          <tr>
            <td style="padding:24px 28px 8px;border-bottom:1px solid #2a2418;">
              <div style="font-family:Georgia,'Times New Roman',serif;font-size:22px;color:#C9A66B;letter-spacing:1px;">SW Beauty Salon</div>
              <div style="font-size:12px;color:#a8a39c;letter-spacing:2px;text-transform:uppercase;margin-top:2px;">Reserve &middot; Refine &middot; Revel</div>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 28px;color:#e8e6e3;">
              <?= $this->renderSection('content') ?>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px 24px;border-top:1px solid #2a2418;color:#a8a39c;font-size:12px;">
              <div><?= esc($alamat) ?></div>
              <div style="margin-top:6px;">Email ini dikirim otomatis. Untuk batal atau ubah jadwal, balas via WhatsApp ke admin salon.</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
