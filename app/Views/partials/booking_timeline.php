<?php
$eventLabels = [
    'created' => ['icon' => 'bi-pencil-square', 'label' => 'Booking dibuat'],
    'telegram_sent' => ['icon' => 'bi-send', 'label' => 'Notifikasi Telegram terkirim'],
    'verified' => ['icon' => 'bi-check-circle', 'label' => 'Diverifikasi'],
    'rejected' => ['icon' => 'bi-x-circle', 'label' => 'Ditolak'],
    'cancelled' => ['icon' => 'bi-slash-circle', 'label' => 'Dibatalkan'],
    'completed' => ['icon' => 'bi-trophy', 'label' => 'Selesai'],
    'wa_sent' => ['icon' => 'bi-whatsapp', 'label' => 'WhatsApp manual terkirim'],
];

if (! function_exists('rel_time_id')) {
    function rel_time_id(string $dt): string {
        $diff = time() - strtotime($dt);
        if ($diff < 60) return $diff . ' detik lalu';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
        return date('d/m/Y H:i', strtotime($dt));
    }
}
?>

<?php if (empty($logs)): ?>
  <div class="caption">Belum ada aktivitas tercatat.</div>
<?php else: ?>
  <ol style="list-style:none; padding-left:1rem; border-left:1px solid var(--color-cream-line); margin:0;">
    <?php foreach ($logs as $log):
      $meta = $eventLabels[$log['event_type']] ?? ['icon' => 'bi-dot', 'label' => ucfirst(str_replace('_', ' ', $log['event_type']))];
    ?>
      <li style="margin-bottom:0.75rem; position:relative;">
        <span style="position:absolute; left:-1.45rem; top:0; background:#fff; padding:0 0.15rem; color:var(--color-gold);"><i class="bi <?= esc($meta['icon']) ?>"></i></span>
        <div class="flex justify-between items-center flex-wrap gap-1">
          <strong><?= esc($meta['label']) ?></strong>
          <span class="caption" title="<?= esc($log['created_at']) ?>"><?= esc(rel_time_id($log['created_at'])) ?></span>
        </div>
        <div class="caption">
          <?= esc($log['actor'] ?? 'sistem') ?>
          <?= ! empty($log['actor_role']) ? ' · ' . esc($log['actor_role']) : '' ?>
        </div>
        <?php if (! empty($log['payload'])): ?>
          <details>
            <summary class="caption" style="cursor:pointer;">Payload</summary>
            <pre style="background:var(--color-ivory-warm); padding:0.5rem; border-radius:6px; font-size:0.75rem; white-space:pre-wrap; margin:0.25rem 0 0;"><?= esc(json_encode(json_decode((string) $log['payload'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
          </details>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ol>
<?php endif ?>
