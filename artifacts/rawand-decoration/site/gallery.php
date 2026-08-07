<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$items = [];

foreach (db()->query(
    "SELECT gi.image, gi.caption, pr.title, pr.slug FROM project_images gi
     JOIN projects pr ON pr.id = gi.project_id AND pr.is_active = 1
     ORDER BY gi.id DESC LIMIT 40"
) as $r) {
    $items[] = [
        'image' => $r['image'],
        'caption' => $r['caption'] ?: $r['title'],
        'url' => url('project/' . rawurlencode($r['slug'])),
    ];
}

foreach (db()->query(
    "SELECT main_image, name, slug FROM products WHERE is_active = 1 AND main_image IS NOT NULL ORDER BY id DESC LIMIT 24"
) as $r) {
    $items[] = [
        'image' => $r['main_image'],
        'caption' => $r['name'],
        'url' => url('product/' . rawurlencode($r['slug'])),
    ];
}

foreach (db()->query(
    "SELECT after_image AS image, title, slug FROM projects WHERE is_active = 1 AND after_image IS NOT NULL ORDER BY id DESC LIMIT 12"
) as $r) {
    $items[] = [
        'image' => $r['image'],
        'caption' => $r['title'],
        'url' => url('project/' . rawurlencode($r['slug'])),
    ];
}

// stable shuffle per day so the wall feels alive but not jumpy
mt_srand((int)date('Ymd'));
shuffle($items);
mt_srand();

track_page_view('gallery');

$PAGE = ['title' => t('gallery_title'), 'desc' => t('gallery_sub'), 'nav' => 'gallery'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('gallery_title')) ?></h1>
    <p class="page-sub"><?= e(t('gallery_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_gallery')]]); ?>

<section class="section">
  <div class="container">
    <?php if ($items): ?>
      <div class="masonry">
        <?php foreach ($items as $it): ?>
          <a href="<?= e(upload_url($it['image'])) ?>" data-lightbox="wall" data-alt="<?= e($it['caption']) ?>">
            <img src="<?= e(upload_url($it['image'])) ?>" alt="<?= e($it['caption']) ?>" loading="lazy">
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><div class="ico">🖼</div><p><?= e(t('no_items')) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
