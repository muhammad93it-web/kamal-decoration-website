<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$db = db();
$cats = $db->query("SELECT * FROM categories WHERE type = 'product' AND is_active = 1 ORDER BY sort_order")->fetchAll();

$catSlug = trim((string)($_GET['category'] ?? ''));
$activeCat = null;
if ($catSlug !== '') {
    foreach ($cats as $c) if ($c['slug'] === $catSlug) { $activeCat = $c; break; }
}

$sort = $_GET['sort'] ?? 'newest';
$orderBy = match ($sort) {
    'price_low'  => 'p.price IS NULL, p.price ASC',
    'price_high' => 'p.price IS NULL, p.price DESC',
    'popular'    => 'p.views DESC',
    default      => 'p.id DESC',
};

$where = 'p.is_active = 1';
$args = [];
if ($activeCat) { $where .= ' AND p.category_id = ?'; $args[] = $activeCat['id']; }

$stc = $db->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$stc->execute($args);
$total = (int)$stc->fetchColumn();

$pg = paginate($total, 12, (int)($_GET['page'] ?? 1));
$st = $db->prepare(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $where ORDER BY $orderBy LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$st->execute($args);
$products = $st->fetchAll();

track_page_view('products');

$PAGE = [
    'title' => $activeCat ? $activeCat['name'] : t('products_title'),
    'desc' => $activeCat['description'] ?? t('products_sub'),
    'nav' => 'products',
    'image' => $activeCat['image'] ?? '',
];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e($activeCat ? $activeCat['name'] : t('products_title')) ?></h1>
    <p class="page-sub"><?= e($activeCat ? ($activeCat['description'] ?? '') : t('products_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs($activeCat
    ? [['label' => t('nav_products'), 'url' => url('products.php')], ['label' => $activeCat['name']]]
    : [['label' => t('nav_products')]]); ?>

<section class="section">
  <div class="container">
    <div class="filter-bar">
      <div class="filter-chips">
        <a class="chip <?= $activeCat ? '' : 'active' ?>" href="<?= e(url('products.php')) ?>"><?= e(t('filter_all')) ?></a>
        <?php foreach ($cats as $c): ?>
          <a class="chip <?= $activeCat && $activeCat['id'] === $c['id'] ? 'active' : '' ?>"
             href="<?= e(url('category/' . rawurlencode($c['slug']))) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <form class="filter-select" method="get" action="<?= e(url('products.php')) ?>">
        <?php if ($activeCat): ?><input type="hidden" name="category" value="<?= e($activeCat['slug']) ?>"><?php endif; ?>
        <label for="sortSel" style="margin:0"><?= e(t('filter_sort')) ?>:</label>
        <select id="sortSel" name="sort" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e(t('sort_newest')) ?></option>
          <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= e(t('sort_popular')) ?></option>
          <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>><?= e(t('sort_price_low')) ?></option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>><?= e(t('sort_price_high')) ?></option>
        </select>
      </form>
    </div>

    <?php if ($products): ?>
      <div class="grid-cards">
        <?php foreach ($products as $p) render_product_card($p); ?>
      </div>
      <?= render_pagination($pg, url('products.php')) ?>
    <?php else: ?>
      <div class="empty-state"><div class="ico">🗂</div><p><?= e(t('no_items')) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
