<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$st = db()->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.is_active = 1 LIMIT 1"
);
$st->execute([$slug]);
$p = $st->fetch();
if (!$p) { http_response_code(404); require APP_ROOT . '/404.php'; exit; }

$imgs = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$imgs->execute([$p['id']]);
$gallery = $imgs->fetchAll();

$pals = db()->prepare(
    'SELECT pal.* FROM palettes pal JOIN product_palettes pp ON pp.palette_id = pal.id
     WHERE pp.product_id = ? AND pal.is_active = 1 ORDER BY pal.sort_order'
);
$pals->execute([$p['id']]);
$palettes = $pals->fetchAll();

$rel = db()->prepare(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.id <> ? AND (p.category_id = ? OR ? IS NULL)
     ORDER BY RAND() LIMIT 4"
);
$rel->execute([$p['id'], $p['category_id'], $p['category_id']]);
$related = $rel->fetchAll();

track_page_view('product', (int)$p['id']);
bump_views('products', (int)$p['id']);

$pageUrl = url('product/' . rawurlencode($p['slug']));
$waText = t_replace(t('wa_product'), ['name' => $p['name'], 'code' => (string)$p['code'], 'url' => $pageUrl]);

$PAGE = ['title' => $p['name'], 'desc' => $p['short_desc'] ?? '', 'image' => $p['main_image'] ?? '', 'nav' => 'products', 'type' => 'product'];
require APP_ROOT . '/templates/header.php';

render_breadcrumbs(array_filter([
    ['label' => t('nav_products'), 'url' => url('products.php')],
    $p['category_name'] ? ['label' => $p['category_name'], 'url' => url('category/' . rawurlencode($p['category_slug']))] : null,
    ['label' => $p['name']],
]));
?>

<section class="section">
  <div class="container detail-layout">
    <div>
      <div class="gallery-main" id="galleryMain">
        <?php $mainImg = $p['main_image'] ?: ($gallery[0]['image'] ?? ''); ?>
        <?php if ($mainImg): ?>
          <a href="<?= e(upload_url($mainImg)) ?>" data-lightbox="product" data-alt="<?= e($p['name']) ?>">
            <img src="<?= e(upload_url($mainImg)) ?>" alt="<?= e($p['name']) ?>">
          </a>
        <?php endif; ?>
      </div>
      <?php if ($gallery): ?>
        <div class="gallery-thumbs">
          <?php if ($mainImg): ?>
            <button type="button" class="active" data-full="<?= e(upload_url($mainImg)) ?>"><img src="<?= e(thumb_url($mainImg)) ?>" alt=""></button>
          <?php endif; ?>
          <?php foreach ($gallery as $g): ?>
            <button type="button" data-full="<?= e(upload_url($g['image'])) ?>"><img src="<?= e(thumb_url($g['image'])) ?>" alt=""></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="detail-info">
      <div class="detail-meta">
        <?php if ($p['category_name']): ?>
          <a class="chip" href="<?= e(url('category/' . rawurlencode($p['category_slug']))) ?>"><?= e($p['category_name']) ?></a>
        <?php endif; ?>
        <?php if ($p['code']): ?><span class="chip chip-code" dir="ltr"><?= e($p['code']) ?></span><?php endif; ?>
        <span class="chip"><?= $p['is_available'] ? '✓ ' . e(t('product_available')) : e(t('product_unavailable')) ?></span>
      </div>
      <h1><?= e($p['name']) ?></h1>

      <?php if (setting('show_prices', '1') === '1' && $p['price'] !== null): ?>
        <div class="detail-price"><?= e(money($p['price'])) ?>
          <?php if ($p['unit']): ?><small><?= e($p['unit']) ?></small><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="detail-price" style="font-size:1.05rem"><?= e(t('product_price_ask')) ?></div>
      <?php endif; ?>

      <?php if ($p['short_desc']): ?><p class="detail-desc"><?= e($p['short_desc']) ?></p><?php endif; ?>

      <div class="detail-actions">
        <a class="btn btn-wa" href="<?= e(wa_link($waText)) ?>" target="_blank" rel="noopener">
          <?= social_icon('whatsapp') ?> <?= e(t('product_whatsapp_ask')) ?>
        </a>
        <button class="btn btn-ghost" type="button" data-copy="<?= e($pageUrl) ?>" data-copied="<?= e(t('copied')) ?>"><?= e(t('btn_copy_link')) ?></button>
      </div>

      <?php if ($palettes): ?>
        <h3 style="font-size:.95rem;margin-bottom:10px"><?= e(t('product_palettes')) ?></h3>
        <div class="detail-meta">
          <?php foreach ($palettes as $pal): ?>
            <a class="chip" href="<?= e(url('palette/' . rawurlencode($pal['slug']))) ?>">🎨 <?= e($pal['name']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($p['specifications']): ?>
        <h3 style="font-size:.95rem;margin:18px 0 10px"><?= e(t('product_specs')) ?></h3>
        <table class="spec-table">
          <?php foreach (preg_split('/\r?\n/', trim($p['specifications'])) as $line):
              if (trim($line) === '') continue;
              $parts = explode(':', $line, 2); ?>
            <tr>
              <th><?= e(trim($parts[0])) ?></th>
              <td><?= e(trim($parts[1] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($p['description']): ?>
<section class="section section-tint" style="padding-block:52px">
  <div class="container">
    <h2 class="section-title" style="margin-bottom:22px"><?= e(t('product_desc')) ?></h2>
    <div class="prose"><?= nl2br(e($p['description'])) ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($related): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('product_related')); ?>
    <div class="grid-cards">
      <?php foreach ($related as $r) render_product_card($r); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
