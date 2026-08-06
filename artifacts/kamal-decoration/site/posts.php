<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$db = db();
$cats = $db->query("SELECT * FROM categories WHERE type = 'post' AND is_active = 1 ORDER BY sort_order")->fetchAll();

$catSlug = trim((string)($_GET['category'] ?? ''));
$activeCat = null;
foreach ($cats as $c) if ($c['slug'] === $catSlug) { $activeCat = $c; break; }

$where = 'is_published = 1 AND published_at <= NOW()';
$args = [];
if ($activeCat) { $where .= ' AND category_id = ?'; $args[] = $activeCat['id']; }

$stc = $db->prepare("SELECT COUNT(*) FROM posts WHERE $where");
$stc->execute($args);
$perPage = max(1, (int)setting('posts_per_page', '9'));
$pg = paginate((int)$stc->fetchColumn(), $perPage, (int)($_GET['page'] ?? 1));

$st = $db->prepare("SELECT * FROM posts WHERE $where ORDER BY published_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($args);
$posts = $st->fetchAll();

track_page_view('posts');

$PAGE = ['title' => t('posts_title'), 'desc' => t('posts_sub'), 'nav' => 'posts'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('posts_title')) ?></h1>
    <p class="page-sub"><?= e(t('posts_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_posts')]]); ?>

<section class="section">
  <div class="container">
    <?php if ($cats): ?>
    <div class="filter-bar">
      <div class="filter-chips">
        <a class="chip <?= $activeCat ? '' : 'active' ?>" href="<?= e(url('posts.php')) ?>"><?= e(t('filter_all')) ?></a>
        <?php foreach ($cats as $c): ?>
          <a class="chip <?= $activeCat && $activeCat['id'] === $c['id'] ? 'active' : '' ?>"
             href="<?= e(url('posts.php')) ?>?category=<?= e(rawurlencode($c['slug'])) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($posts): ?>
      <div class="grid-cards">
        <?php foreach ($posts as $po) render_post_card($po); ?>
      </div>
      <?= render_pagination($pg, url('posts.php') . ($activeCat ? '?category=' . rawurlencode($activeCat['slug']) : '')) ?>
    <?php else: ?>
      <div class="empty-state"><div class="ico">📰</div><p><?= e(t('no_items')) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
