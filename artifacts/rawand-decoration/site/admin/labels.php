<?php
/** Printable label sheets (QR + barcode) for shades or products. */
require __DIR__ . '/includes/admin-bootstrap.php';

$type = (string)($_GET['type'] ?? 'shades');
if (!in_array($type, ['shades', 'products'], true)) $type = 'shades';

$palettes = db()->query('SELECT id, name, code FROM palettes ORDER BY sort_order, id')->fetchAll();
$pid = (int)($_GET['palette'] ?? ($palettes[0]['id'] ?? 0));

$items = [];
if ($type === 'shades' && $pid) {
    $st = db()->prepare('SELECT s.*, p.name AS palette_name FROM palette_shades s JOIN palettes p ON p.id = s.palette_id WHERE s.palette_id = ? ORDER BY s.position, s.id');
    $st->execute([$pid]);
    $items = $st->fetchAll();
} elseif ($type === 'products') {
    $items = db()->query("SELECT * FROM products WHERE code IS NOT NULL AND code <> '' AND is_active = 1 ORDER BY sort_order, id")->fetchAll();
}

admin_header(t('a_labels', 'لەیبڵەکان'), 'labels');
?>

<div class="toolbar no-print">
  <a class="btn <?= $type === 'shades' ? 'btn-gold' : 'btn-ghost' ?>" href="<?= e(admin_url('labels.php?type=shades&palette=' . $pid)) ?>"><?= e(t('a_shades', 'ڕەنگەکان')) ?></a>
  <a class="btn <?= $type === 'products' ? 'btn-gold' : 'btn-ghost' ?>" href="<?= e(admin_url('labels.php?type=products')) ?>"><?= e(t('a_products', 'بەرهەمەکان')) ?></a>
  <?php if ($type === 'shades'): ?>
    <form method="get">
      <input type="hidden" name="type" value="shades">
      <select name="palette" onchange="this.form.submit()">
        <?php foreach ($palettes as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $pid ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['code']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
  <div class="grow"></div>
  <button class="btn btn-ghost" onclick="window.print()">🖨 <?= e(t('a_lb_print', 'چاپکردن')) ?></button>
  <a class="btn btn-gold" href="<?= e(admin_url('label-pdf.php?type=' . $type . '&palette=' . $pid)) ?>" target="_blank">📄 PDF</a>
</div>

<div class="help-box no-print">
  💡 <?= e(t('a_lb_help', 'ئەم لەیبڵانە چاپ بکە و بە نموونەکانی دوکان بلکێنە. کڕیار بە کامێرای مۆبایل QRەکە سکان دەکات و ڕاستەوخۆ پەڕەی ڕەنگەکە دەکرێتەوە — تەنانەت ناوی دوکانیش لەسەر لەیبڵەکەیە.')) ?>
</div>

<?php if (!$items): ?>
  <div class="panel tac muted"><?= e(t('no_items', 'هیچ نییە')) ?></div>
<?php else: ?>
  <div class="label-sheet">
    <?php foreach ($items as $it):
        $isShade = $type === 'shades';
        $name = $isShade ? $it['name'] : $it['name'];
        $code = $it['code'];
        $qr = $it['qr_path'] ?? null;
        $bc = $it['barcode_path'] ?? null; ?>
      <div class="label-card">
        <?php if ($isShade): ?>
          <div class="lb-swatch" style="background:<?= e($it['hex_color']) ?>"></div>
        <?php elseif (!empty($it['main_image'])): ?>
          <div class="lb-swatch"><img src="<?= e(thumb_url($it['main_image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover"></div>
        <?php endif; ?>
        <div class="lb-brand"><?= e(setting('site_name', 'دیکۆراتی ڕەوەند')) ?></div>
        <div class="lb-name"><?= e($name) ?><?= $isShade ? ' — ' . e($it['palette_name']) : '' ?></div>
        <div class="lb-code" dir="ltr"><?= e($code) ?></div>
        <div class="lb-codes">
          <?php if ($qr): ?><img class="lb-qr" src="<?= e(upload_url($qr)) ?>" alt="QR"><?php endif; ?>
          <?php if ($bc): ?><img class="lb-bc" src="<?= e(upload_url($bc)) ?>" alt="barcode"><?php endif; ?>
        </div>
        <div class="lb-phone" dir="ltr"><?= e(setting('phone', '')) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php admin_footer(); ?>
