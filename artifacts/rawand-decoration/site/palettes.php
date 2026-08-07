<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$palettes = db()->query("SELECT * FROM palettes WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll();
$shadeMap = [];
if ($palettes) {
    $ids = implode(',', array_map(fn($p) => (int)$p['id'], $palettes));
    foreach (db()->query("SELECT palette_id, hex_color FROM palette_shades WHERE is_active = 1 AND palette_id IN ($ids) ORDER BY position") as $r) {
        $shadeMap[(int)$r['palette_id']][] = $r['hex_color'];
    }
}

track_page_view('palettes');

$PAGE = ['title' => t('palettes_title'), 'desc' => t('palettes_sub'), 'nav' => 'palettes'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('palettes_title')) ?></h1>
    <p class="page-sub"><?= e(t('palettes_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_palettes')]]); ?>

<section class="section">
  <div class="container">
    <?php if ($palettes): ?>
      <div class="grid-cards">
        <?php foreach ($palettes as $pal):
            $hexes = $shadeMap[(int)$pal['id']] ?? [];
            render_palette_card($pal, $hexes, count($hexes));
        endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><div class="ico">🎨</div><p><?= e(t('no_items')) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
