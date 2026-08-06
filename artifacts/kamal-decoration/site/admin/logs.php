<?php
require __DIR__ . '/includes/admin-bootstrap.php';

$tab = (string)($_GET['tab'] ?? 'activity');
if (!in_array($tab, ['activity', 'searches', 'scans', 'views'], true)) $tab = 'activity';

$TABS = [
    'activity' => t('a_log_activity', 'چالاکییەکان'),
    'searches' => t('a_log_searches', 'گەڕانەکان'),
    'scans' => t('a_log_scans', 'سکانەکان'),
    'views' => t('a_log_views', 'بینینەکان'),
];

$counts = [
    'activity' => (int)db()->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn(),
    'searches' => (int)db()->query('SELECT COUNT(*) FROM searches')->fetchColumn(),
    'scans' => (int)db()->query('SELECT COUNT(*) FROM scan_logs')->fetchColumn(),
    'views' => (int)db()->query('SELECT COUNT(*) FROM page_views')->fetchColumn(),
];

$pg = paginate($counts[$tab], 30, (int)($_GET['page'] ?? 1));
$lim = "LIMIT {$pg['per_page']} OFFSET {$pg['offset']}";

admin_header(t('a_logs', 'تۆمارەکان'), 'logs');
?>

<div class="tabs">
  <?php foreach ($TABS as $k => $l): ?>
    <a class="tab <?= $tab === $k ? 'active' : '' ?>" href="<?= e(admin_url('logs.php?tab=' . $k)) ?>"><?= e($l) ?> <small>(<?= e(knum($counts[$k])) ?>)</small></a>
  <?php endforeach; ?>
</div>

<div class="tbl-wrap panel" style="padding:0">
  <table class="tbl">
    <?php if ($tab === 'activity'):
        $rows = db()->query("SELECT a.*, u.username FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC $lim")->fetchAll(); ?>
      <tr><th><?= e(t('a_log_user', 'بەکارهێنەر')) ?></th><th><?= e(t('a_log_action', 'کردار')) ?></th><th><?= e(t('a_log_details', 'وردەکاری')) ?></th><th><?= e(t('a_date', 'بەروار')) ?></th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td dir="ltr"><?= e($r['username'] ?? '—') ?></td>
          <td><span class="code-pill" dir="ltr"><?= e($r['action']) ?><?= $r['entity'] ? ':' . e($r['entity']) : '' ?></span></td>
          <td><?= e(excerpt_of((string)$r['details'], 60)) ?><?= $r['entity_id'] ? ' <span class="muted">#' . (int)$r['entity_id'] . '</span>' : '' ?></td>
          <td><?= e(kdate($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>

    <?php elseif ($tab === 'searches'):
        $rows = db()->query("SELECT * FROM searches ORDER BY id DESC $lim")->fetchAll(); ?>
      <tr><th><?= e(t('a_log_query', 'وشەی گەڕان')) ?></th><th><?= e(t('a_log_results', 'ئەنجام')) ?></th><th>IP</th><th><?= e(t('a_date', 'بەروار')) ?></th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['query']) ?></strong><?php if ($r['normalized'] !== '' && $r['normalized'] !== $r['query']): ?> <span class="muted">→ <?= e($r['normalized']) ?></span><?php endif; ?></td>
          <td><?= (int)$r['results'] > 0 ? e(knum((int)$r['results'])) : '<span class="pill pill-off">0</span>' ?></td>
          <td dir="ltr" class="muted" style="font-size:.75rem"><?= e($r['ip']) ?></td>
          <td><?= e(kdate($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>

    <?php elseif ($tab === 'scans'):
        $rows = db()->query("SELECT * FROM scan_logs ORDER BY id DESC $lim")->fetchAll(); ?>
      <tr><th><?= e(t('a_f_code', 'کۆد')) ?></th><th><?= e(t('a_log_found', 'دۆزرایەوە؟')) ?></th><th><?= e(t('a_f_type', 'جۆر')) ?></th><th>IP</th><th><?= e(t('a_date', 'بەروار')) ?></th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><span class="code-pill" dir="ltr"><?= e($r['code']) ?></span></td>
          <td><span class="pill <?= $r['found'] ? 'pill-on' : 'pill-off' ?>"><?= $r['found'] ? '✓' : '✗' ?></span></td>
          <td><?= e((string)($r['target_type'] ?? '—')) ?></td>
          <td dir="ltr" class="muted" style="font-size:.75rem"><?= e($r['ip']) ?></td>
          <td><?= e(kdate($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>

    <?php else:
        $rows = db()->query("SELECT * FROM page_views ORDER BY id DESC $lim")->fetchAll(); ?>
      <tr><th><?= e(t('a_log_path', 'پەڕە')) ?></th><th><?= e(t('a_f_type', 'جۆر')) ?></th><th>IP</th><th><?= e(t('a_date', 'بەروار')) ?></th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td dir="ltr" style="font-size:.8rem"><?= e(excerpt_of($r['path'], 60)) ?></td>
          <td><?= e((string)($r['page_type'] ?? '—')) ?></td>
          <td dir="ltr" class="muted" style="font-size:.75rem"><?= e($r['ip']) ?></td>
          <td><?= e(kdate($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
      <tr><td colspan="5" class="tac muted" style="padding:30px"><?= e(t('no_items', 'هیچ نییە')) ?></td></tr>
    <?php endif; ?>
  </table>
</div>

<?= render_pagination($pg, admin_url('logs.php?tab=' . $tab)) ?>

<?php admin_footer(); ?>
