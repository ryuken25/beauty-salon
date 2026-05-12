<?php
$eventLabels = [
    'created' => ['icon' => '📝', 'label' => 'Booking dibuat'],
    'telegram_sent' => ['icon' => '📨', 'label' => 'Notifikasi Telegram terkirim'],
    'telegram_received' => ['icon' => '📥', 'label' => 'Aksi Telegram diterima'],
    'verified' => ['icon' => '✅', 'label' => 'Diverifikasi'],
    'rejected' => ['icon' => '❌', 'label' => 'Ditolak'],
    'cancelled' => ['icon' => '🚫', 'label' => 'Dibatalkan'],
    'completed' => ['icon' => '🏁', 'label' => 'Selesai'],
    'wa_sent' => ['icon' => '💬', 'label' => 'WhatsApp manual terkirim'],
];

if (! function_exists('relative_time_id')) {
    function relative_time_id(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) {
            return $diff . ' detik lalu';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' hari lalu';
        }
        return date('d/m/Y H:i', strtotime($datetime));
    }
}
?>

<div class="soft-card mb-3">
  <h2 class="h5 section-title mb-3">Riwayat Aktivitas</h2>
  <?php if (empty($logs)): ?>
    <p class="small-muted mb-0">Belum ada aktivitas yang tercatat.</p>
  <?php else: ?>
    <ol class="list-unstyled mb-0" style="border-left:2px solid #e6d2a7;margin-left:.5rem;padding-left:1rem;">
      <?php foreach ($logs as $log): ?>
        <?php $meta = $eventLabels[$log['event_type']] ?? ['icon' => '•', 'label' => ucfirst(str_replace('_', ' ', $log['event_type']))]; ?>
        <li class="mb-3" style="position:relative;">
          <span style="position:absolute;left:-1.55rem;top:0;font-size:1rem;background:#fffaf0;padding:0 .15rem;"><?= esc($meta['icon']) ?></span>
          <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
            <strong><?= esc($meta['label']) ?></strong>
            <span class="small-muted" title="<?= esc($log['created_at']) ?>"><?= esc(relative_time_id($log['created_at'])) ?></span>
          </div>
          <div class="small-muted">
            <?= esc($log['actor'] ?? 'sistem') ?>
            <?php if (! empty($log['actor_role'])): ?>
              · <?= esc($log['actor_role']) ?>
            <?php endif ?>
          </div>
          <?php if (! empty($log['payload'])): ?>
            <details class="mt-1">
              <summary class="small-muted" style="cursor:pointer;">Detail payload</summary>
              <pre class="mb-0 mt-1" style="background:#fffaf0;padding:.5rem;border-radius:.35rem;font-size:.8rem;white-space:pre-wrap;"><?= esc(json_encode(json_decode((string) $log['payload'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </details>
          <?php endif ?>
          <?php if (! empty($log['notes'])): ?>
            <div class="small-muted">Catatan: <?= esc($log['notes']) ?></div>
          <?php endif ?>
        </li>
      <?php endforeach ?>
    </ol>
  <?php endif ?>
</div>
