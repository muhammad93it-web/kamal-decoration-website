<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$db = db();
$cats = $db->query("SELECT * FROM categories WHERE type = 'project' AND is_active = 1 ORDER BY sort_order")->fetchAll();

$catSlug = trim((string)($_GET['category'] ?? ''));
$activeCat = null;
foreach ($cats as $c) if ($c['slug'] === $catSlug) { $activeCat = $c; break; }

$where = 'is_active = 1';
$args = [];
if ($activeCat) { $where .= ' AND category_id = ?'; $args[] = $activeCat['id']; }

$stc = $db->prepare("SELECT COUNT(*) FROM projects WHERE $where");
$stc->execute($args);
$pg = paginate((int)$stc->fetchColumn(), 9, (int)($_GET['page'] ?? 1));

$st = $db->prepare("SELECT * FROM projects WHERE $where ORDER BY is_featured DESC, sort_order, id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($args);
$projects = $st->fetchAll();

track_page_view('projects');

$PAGE = ['title' => t('projects_title'), 'desc' => t('projects_sub'), 'nav' => 'projects'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('projects_title')) ?></h1>
    <p class="page-sub"><?= e(t('projects_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_projects')]]); ?>

<section class="section">
  <div class="container">
    <?php if ($cats): ?>
    <div class="filter-bar">
      <div class="filter-chips">
        <a class="chip <?= $activeCat ? '' : 'active' ?>" href="<?= e(url('projects.php')) ?>"><?= e(t('filter_all')) ?></a>
        <?php foreach ($cats as $c): ?>
          <a class="chip <?= $activeCat && $activeCat['id'] === $c['id'] ? 'active' : '' ?>"
             href="<?= e(url('projects.php')) ?>?category=<?= e(rawurlencode($c['slug'])) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($projects): ?>
      <div class="grid-cards">
        <?php foreach ($projects as $pr) render_project_card($pr); ?>
      </div>
      <?= render_pagination($pg, url('projects.php') . ($activeCat ? '?category=' . rawurlencode($activeCat['slug']) : '')) ?>
    <?php else: ?>
      <div class="empty-state"><div class="ico">🏗</div><p><?= e(t('no_items')) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
