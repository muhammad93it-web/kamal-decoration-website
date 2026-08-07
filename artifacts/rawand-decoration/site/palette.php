<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$st = db()->prepare('SELECT * FROM palettes WHERE slug = ? AND is_active = 1 LIMIT 1');
$st->execute([$slug]);
$pal = $st->fetch();
if (!$pal) { http_response_code(404); require APP_ROOT . '/404.php'; exit; }

$sh = db()->prepare('SELECT * FROM palette_shades WHERE palette_id = ? AND is_active = 1 ORDER BY position, id');
$sh->execute([$pal['id']]);
$shades = $sh->fetchAll();

$pr = db()->prepare(
    'SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     JOIN product_palettes pp ON pp.product_id = p.id
     WHERE pp.palette_id = ? AND p.is_active = 1 ORDER BY p.sort_order LIMIT 8'
);
$pr->execute([$pal['id']]);
$products = $pr->fetchAll();

track_page_view('palette', (int)$pal['id']);

$PAGE = ['title' => $pal['name'], 'desc' => $pal['description'] ?? '', 'image' => $pal['cover_image'] ?? '', 'nav' => 'palettes'];
require APP_ROOT . '/templates/header.php';

render_breadcrumbs([
    ['label' => t('nav_palettes'), 'url' => url('palettes.php')],
    ['label' => $pal['name']],
]);
?>

<section class="page-hero">
  <div class="container">
    <div class="detail-meta" style="margin-bottom:12px">
      <span class="chip chip-code" dir="ltr" style="background:rgba(250,247,242,.12);border-color:transparent;color:#FAF7F2"><?= e($pal['code']) ?></span>
      <?php if ($pal['family']): ?><span class="chip" style="background:rgba(250,247,242,.12);border-color:transparent;color:#FAF7F2"><?= e(t('shade_family')) ?>: <?= e($pal['family']) ?></span><?php endif; ?>
    </div>
    <h1 class="page-title"><?= e($pal['name']) ?></h1>
    <?php if ($pal['description']): ?><p class="page-sub"><?= e($pal['description']) ?></p><?php endif; ?>
  </div>
</section>

<?php if ($shades): ?>
<div class="palette-strip" style="height:26px">
  <?php foreach ($shades as $s): ?><span style="background:<?= e($s['hex_color']) ?>"></span><?php endforeach; ?>
</div>
<?php endif; ?>

<section class="section">
  <div class="container">
    <?php section_head(t('palette_shades_title'), t('dark_to_light')); ?>
    <?php if ($shades): ?>
      <div class="grid-shades">
        <?php foreach ($shades as $s) render_shade_tile($s); ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><div class="ico">🎨</div><p><?= e(t('no_items')) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<?php if ($products): ?>
<section class="section section-tint">
  <div class="container">
    <?php section_head(t('shade_products')); ?>
    <div class="grid-cards">
      <?php foreach ($products as $p) render_product_card($p); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
