<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';
require_once APP_ROOT . '/includes/codes.php';

$qRaw = trim((string)($_GET['q'] ?? ''));
$q = normalize_text($qRaw);

// exact code? jump straight to it (KD-S101, kd s101, …)
if ($q !== '' && preg_match('/^[a-z]{1,4}[\s\-]?[a-z]?\d{1,6}$/i', str_replace(' ', '-', $q))) {
    $hit = find_by_code(strtoupper(str_replace(' ', '-', $q)));
    if ($hit) {
        [$hitType, $hitRow] = $hit;
        $hitUrl = match ($hitType) {
            'shade'   => url('shade/' . $hitRow['slug']),
            'palette' => url('palette/' . $hitRow['slug']),
            'product' => url('product/' . $hitRow['slug']),
            default   => null,
        };
        if ($hitUrl !== null) {
            log_search($qRaw, $q, 1);
            redirect($hitUrl);
        }
    }
}

$results = [];
$total = 0;

if (mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';
    $db = db();

    $st = $db->prepare(
        "SELECT s.*, p.name AS palette_name FROM palette_shades s
         JOIN palettes p ON p.id = s.palette_id
         WHERE s.is_active = 1 AND (s.name LIKE ? OR UPPER(s.code) LIKE UPPER(?)) LIMIT 12"
    );
    $st->execute([$like, $like]);
    $results['shades'] = $st->fetchAll();

    $st = $db->prepare(
        "SELECT p.*, c.name AS category_name FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.is_active = 1 AND (p.name LIKE ? OR UPPER(p.code) LIKE UPPER(?) OR p.short_desc LIKE ?) LIMIT 12"
    );
    $st->execute([$like, $like, $like]);
    $results['products'] = $st->fetchAll();

    $st = $db->prepare(
        "SELECT * FROM palettes WHERE is_active = 1 AND (name LIKE ? OR UPPER(code) LIKE UPPER(?) OR family LIKE ?) LIMIT 6"
    );
    $st->execute([$like, $like, $like]);
    $results['palettes'] = $st->fetchAll();

    $st = $db->prepare(
        "SELECT * FROM projects WHERE is_active = 1 AND (title LIKE ? OR description LIKE ? OR location LIKE ?) LIMIT 6"
    );
    $st->execute([$like, $like, $like]);
    $results['projects'] = $st->fetchAll();

    $st = $db->prepare(
        "SELECT * FROM posts WHERE is_published = 1 AND published_at <= NOW() AND (title LIKE ? OR excerpt LIKE ?) LIMIT 6"
    );
    $st->execute([$like, $like]);
    $results['posts'] = $st->fetchAll();

    $total = array_sum(array_map('count', $results));
    log_search($qRaw, $q, $total);
}

// palette shade strips for palette results
$shadeMap = [];
if (!empty($results['palettes'])) {
    $ids = implode(',', array_map(fn($p) => (int)$p['id'], $results['palettes']));
    foreach (db()->query("SELECT palette_id, hex_color FROM palette_shades WHERE is_active = 1 AND palette_id IN ($ids) ORDER BY position") as $r) {
        $shadeMap[(int)$r['palette_id']][] = $r['hex_color'];
    }
}

$PAGE = ['title' => t('search_title') . ($qRaw !== '' ? ' — ' . $qRaw : ''), 'nav' => ''];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('search_title')) ?></h1>
    <form method="get" action="<?= e(url('search.php')) ?>" style="margin-top:22px;max-width:520px;display:flex;gap:10px">
      <input type="search" name="q" value="<?= e($qRaw) ?>" placeholder="<?= e(t('search_placeholder')) ?>"
             style="flex:1;background:rgba(250,247,242,.96)" autofocus>
      <button class="btn btn-gold" type="submit"><?= e(t('btn_search')) ?></button>
    </form>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if ($qRaw === '' || mb_strlen($q) < 2): ?>
      <div class="empty-state"><div class="ico">🔎</div><p><?= e(t('search_hint')) ?></p></div>
    <?php elseif ($total === 0): ?>
      <div class="empty-state">
        <div class="ico">😕</div>
        <p><?= e(t('search_no_results')) ?> «<?= e($qRaw) ?>»</p>
        <a class="btn btn-ghost btn-small" href="<?= e(url('scanner.php')) ?>" style="margin-top:10px"><?= e(t('nav_scanner')) ?></a>
      </div>
    <?php else: ?>
      <p class="muted" style="margin-bottom:34px"><?= e(t_replace(t('search_results_for'), ['count' => knum($total), 'q' => $qRaw])) ?></p>

      <?php if (!empty($results['shades'])): ?>
        <h2 class="section-title" style="margin-bottom:20px;font-size:1.2rem"><?= e(t('search_shades')) ?> (<?= e(knum(count($results['shades']))) ?>)</h2>
        <div class="grid-shades" style="margin-bottom:44px">
          <?php foreach ($results['shades'] as $s) render_shade_tile($s); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['products'])): ?>
        <h2 class="section-title" style="margin-bottom:20px;font-size:1.2rem"><?= e(t('search_products')) ?> (<?= e(knum(count($results['products']))) ?>)</h2>
        <div class="grid-cards" style="margin-bottom:44px">
          <?php foreach ($results['products'] as $p) render_product_card($p); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['palettes'])): ?>
        <h2 class="section-title" style="margin-bottom:20px;font-size:1.2rem"><?= e(t('search_palettes')) ?> (<?= e(knum(count($results['palettes']))) ?>)</h2>
        <div class="grid-cards" style="margin-bottom:44px">
          <?php foreach ($results['palettes'] as $pal):
              $hx = $shadeMap[(int)$pal['id']] ?? [];
              render_palette_card($pal, $hx, count($hx));
          endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['projects'])): ?>
        <h2 class="section-title" style="margin-bottom:20px;font-size:1.2rem"><?= e(t('search_projects')) ?> (<?= e(knum(count($results['projects']))) ?>)</h2>
        <div class="grid-cards" style="margin-bottom:44px">
          <?php foreach ($results['projects'] as $pr) render_project_card($pr); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($results['posts'])): ?>
        <h2 class="section-title" style="margin-bottom:20px;font-size:1.2rem"><?= e(t('search_posts')) ?> (<?= e(knum(count($results['posts']))) ?>)</h2>
        <div class="grid-cards">
          <?php foreach ($results['posts'] as $po) render_post_card($po); ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
