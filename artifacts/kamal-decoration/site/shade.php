<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';
require_once APP_ROOT . '/includes/codes.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$st = db()->prepare(
    'SELECT s.*, p.name AS palette_name, p.slug AS palette_slug, p.family AS palette_family
     FROM palette_shades s JOIN palettes p ON p.id = s.palette_id
     WHERE s.slug = ? AND s.is_active = 1 LIMIT 1'
);
$st->execute([$slug]);
$s = $st->fetch();
if (!$s) { http_response_code(404); require APP_ROOT . '/404.php'; exit; }

// self-heal: generate QR/barcode files if missing (e.g. fresh import)
$needQr = empty($s['qr_path']) || !is_file(UPLOAD_DIR . '/' . $s['qr_path']);
$needBc = empty($s['barcode_path']) || !is_file(UPLOAD_DIR . '/' . $s['barcode_path']);
if ($needQr || $needBc) {
    try {
        ensure_shade_codes((int)$s['id']);
        $st->execute([$slug]);
        $s = $st->fetch();
    } catch (Throwable $e) { /* libraries missing — page still works */ }
}

// siblings in the same palette (dark→light)
$sib = db()->prepare('SELECT * FROM palette_shades WHERE palette_id = ? AND is_active = 1 ORDER BY position, id');
$sib->execute([$s['palette_id']]);
$siblings = array_values(array_filter($sib->fetchAll(), fn($x) => (int)$x['id'] !== (int)$s['id']));

// related products via palette
$pr = db()->prepare(
    'SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     JOIN product_palettes pp ON pp.product_id = p.id
     WHERE pp.palette_id = ? AND p.is_active = 1 ORDER BY p.sort_order LIMIT 4'
);
$pr->execute([$s['palette_id']]);
$products = $pr->fetchAll();

track_page_view('shade', (int)$s['id']);

$pageUrl = url('shade/' . rawurlencode($s['slug']));
$waText = t_replace(t('wa_shade'), ['name' => $s['name'], 'code' => $s['code'], 'url' => $pageUrl]);

$PAGE = ['title' => $s['name'] . ' (' . $s['code'] . ')', 'desc' => t('shade_palette') . ' ' . $s['palette_name'], 'nav' => 'palettes'];
require APP_ROOT . '/templates/header.php';

render_breadcrumbs([
    ['label' => t('nav_palettes'), 'url' => url('palettes.php')],
    ['label' => $s['palette_name'], 'url' => url('palette/' . rawurlencode($s['palette_slug']))],
    ['label' => $s['name']],
]);
?>

<section class="section">
  <div class="container shade-hero">
    <div class="shade-big" style="background:<?= e($s['hex_color']) ?>">
      <span class="hexlabel" dir="ltr"><?= e($s['hex_color']) ?></span>
    </div>

    <div>
      <h1 style="font-size:clamp(1.4rem,2.8vw,2rem);margin-bottom:18px"><?= e($s['name']) ?></h1>
      <dl class="info-rows" style="margin:0">
        <div class="info-row"><dt><?= e(t('shade_code')) ?></dt>
          <dd><span class="chip chip-code" dir="ltr"><?= e($s['code']) ?></span>
              <button class="btn btn-small btn-ghost" type="button" data-copy="<?= e($s['code']) ?>" data-copied="<?= e(t('copied')) ?>"><?= e(t('btn_copy_link', 'کۆپی')) ?></button></dd></div>
        <div class="info-row"><dt><?= e(t('shade_hex')) ?></dt>
          <dd><span dir="ltr"><?= e($s['hex_color']) ?></span></dd></div>
        <div class="info-row"><dt><?= e(t('shade_palette')) ?></dt>
          <dd><a href="<?= e(url('palette/' . rawurlencode($s['palette_slug']))) ?>" style="color:var(--accent-deep)"><?= e($s['palette_name']) ?></a></dd></div>
        <?php if ($s['palette_family']): ?>
        <div class="info-row"><dt><?= e(t('shade_family')) ?></dt><dd><?= e($s['palette_family']) ?></dd></div>
        <?php endif; ?>
        <?php if ($s['notes']): ?>
        <div class="info-row"><dt>📝</dt><dd style="font-weight:400"><?= e($s['notes']) ?></dd></div>
        <?php endif; ?>
      </dl>

      <div class="detail-actions">
        <a class="btn btn-wa" href="<?= e(wa_link($waText)) ?>" target="_blank" rel="noopener">
          <?= social_icon('whatsapp') ?> <?= e(t('shade_whatsapp_ask')) ?>
        </a>
        <button class="btn btn-ghost" type="button" data-share="<?= e($pageUrl) ?>" data-share-title="<?= e($s['name']) ?>" data-copied="<?= e(t('copied')) ?>"><?= e(t('shade_share')) ?></button>
      </div>

      <div class="code-imgs">
        <?php if (!empty($s['qr_path']) && is_file(UPLOAD_DIR . '/' . $s['qr_path'])): ?>
          <div class="code-box">
            <img src="<?= e(upload_url($s['qr_path'])) ?>" alt="QR — <?= e($s['code']) ?>" loading="lazy">
            <div class="lbl"><?= e(t('shade_qr')) ?></div>
          </div>
        <?php endif; ?>
        <?php if (!empty($s['barcode_path']) && is_file(UPLOAD_DIR . '/' . $s['barcode_path'])): ?>
          <div class="code-box">
            <img src="<?= e(upload_url($s['barcode_path'])) ?>" alt="Barcode — <?= e($s['code']) ?>" loading="lazy">
            <div class="lbl"><?= e(t('shade_barcode')) ?> <span dir="ltr"><?= e($s['code']) ?></span></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($siblings): ?>
<section class="section section-tint">
  <div class="container">
    <?php section_head(t('shade_similar'), t('dark_to_light')); ?>
    <div class="grid-shades">
      <?php foreach ($siblings as $sb) render_shade_tile($sb); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($products): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('shade_products')); ?>
    <div class="grid-cards">
      <?php foreach ($products as $p) render_product_card($p); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
