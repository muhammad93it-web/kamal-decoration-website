<?php
require __DIR__ . '/includes/admin-bootstrap.php';

$db = db();
$counts = [
    'products' => (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'palettes' => (int)$db->query('SELECT COUNT(*) FROM palettes')->fetchColumn(),
    'shades'   => (int)$db->query('SELECT COUNT(*) FROM palette_shades')->fetchColumn(),
    'projects' => (int)$db->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'posts'    => (int)$db->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'messages' => unread_messages_count(),
];
$viewsToday = (int)$db->query('SELECT COUNT(*) FROM page_views WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$searchesToday = (int)$db->query('SELECT COUNT(*) FROM searches WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$scansToday = (int)$db->query('SELECT COUNT(*) FROM scan_logs WHERE DATE(created_at) = CURDATE()')->fetchColumn();

// 7-day views series
$series = array_fill(0, 7, 0);
$labels = [];
for ($i = 6; $i >= 0; $i--) $labels[] = date('Y-m-d', strtotime("-$i days"));
$st = $db->query("SELECT DATE(created_at) d, COUNT(*) n FROM page_views WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at)");
$byDay = [];
foreach ($st as $r) $byDay[$r['d']] = (int)$r['n'];
foreach ($labels as $i => $d) $series[$i] = $byDay[$d] ?? 0;
$maxV = max(1, max($series));

$recentMsgs = $db->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 5')->fetchAll();
$recentSearches = $db->query('SELECT * FROM searches ORDER BY id DESC LIMIT 8')->fetchAll();
$recentScans = $db->query('SELECT * FROM scan_logs ORDER BY id DESC LIMIT 5')->fetchAll();

admin_header(t('a_dashboard', 'داشبۆرد'), 'dashboard');
?>

<div class="grid-stats">
  <div class="stat hot"><div class="stat-num"><?= e(knum($viewsToday)) ?></div><div class="stat-label"><?= e(t('a_views_today', 'بینینی ئەمڕۆ')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($searchesToday)) ?></div><div class="stat-label"><?= e(t('a_searches_today', 'گەڕانی ئەمڕۆ')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($scansToday)) ?></div><div class="stat-label"><?= e(t('a_scans_today', 'سکانی ئەمڕۆ')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($counts['messages'])) ?></div><div class="stat-label"><?= e(t('a_unread_msgs', 'پەیامی نەخوێندراوە')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($counts['products'])) ?></div><div class="stat-label"><?= e(t('a_products', 'بەرهەمەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($counts['shades'])) ?></div><div class="stat-label"><?= e(t('a_shades', 'ڕەنگەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($counts['projects'])) ?></div><div class="stat-label"><?= e(t('a_projects', 'پرۆژەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($counts['posts'])) ?></div><div class="stat-label"><?= e(t('a_posts', 'بابەتەکان')) ?></div></div>
</div>

<div class="panel">
  <h2 class="panel-title"><?= e(t('a_views_7d', 'بینینی ماڵپەڕ — ٧ ڕۆژی ڕابردوو')) ?></h2>
  <div class="bars">
    <?php foreach ($series as $i => $v): ?>
      <div class="bar-col">
        <span class="bar-val"><?= e(knum($v)) ?></span>
        <div class="bar" style="height:<?= max(2, round($v / $maxV * 100)) ?>%"></div>
        <span class="bar-lbl"><?= e(kdate($labels[$i])) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:22px" class="dash-cols">
  <div class="panel" style="margin-bottom:0">
    <h2 class="panel-title"><?= e(t('a_recent_msgs', 'دوایین پەیامەکان')) ?>
      <a class="btn btn-ghost btn-xs" style="float:left" href="<?= e(admin_url('messages.php')) ?>"><?= e(t('a_all', 'هەموو')) ?></a></h2>
    <?php if ($recentMsgs): ?>
      <div class="tbl-wrap"><table class="tbl">
        <tr><th><?= e(t('contact_name', 'ناو')) ?></th><th><?= e(t('contact_phone', 'ژمارە')) ?></th><th><?= e(t('a_date', 'بەروار')) ?></th></tr>
        <?php foreach ($recentMsgs as $m): ?>
          <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
            <td><a href="<?= e(admin_url('messages.php?view=' . (int)$m['id'])) ?>"><?= e($m['name']) ?></a></td>
            <td dir="ltr"><?= e($m['phone']) ?></td>
            <td><?= e(kdate($m['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table></div>
    <?php else: ?><p class="muted"><?= e(t('no_items', 'هیچ نییە')) ?></p><?php endif; ?>
  </div>

  <div class="panel" style="margin-bottom:0">
    <h2 class="panel-title"><?= e(t('a_recent_searches', 'دوایین گەڕانەکان')) ?></h2>
    <?php if ($recentSearches): ?>
      <div class="tbl-wrap"><table class="tbl">
        <tr><th><?= e(t('a_query', 'وشە')) ?></th><th><?= e(t('a_results', 'ئەنجام')) ?></th></tr>
        <?php foreach ($recentSearches as $s): ?>
          <tr><td><?= e($s['query']) ?></td><td><?= e(knum((int)$s['results_count'])) ?></td></tr>
        <?php endforeach; ?>
      </table></div>
    <?php else: ?><p class="muted"><?= e(t('no_items', 'هیچ نییە')) ?></p><?php endif; ?>

    <?php if ($recentScans): ?>
      <h2 class="panel-title" style="margin-top:18px"><?= e(t('a_recent_scans', 'دوایین سکانەکان')) ?></h2>
      <div class="tbl-wrap"><table class="tbl">
        <?php foreach ($recentScans as $s): ?>
          <tr>
            <td><span class="code-pill" dir="ltr"><?= e($s['code']) ?></span></td>
            <td><?= $s['found'] ? '<span class="pill pill-on">✓</span>' : '<span class="pill pill-off">✗</span>' ?></td>
            <td><?= e(kdate($s['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<style>@media(max-width:980px){.dash-cols{grid-template-columns:1fr!important}}</style>

<?php admin_footer(); ?>
