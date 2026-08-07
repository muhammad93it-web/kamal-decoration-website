<?php
/** QR tools — set site URL and regenerate every QR/barcode after a domain change. */
require __DIR__ . '/includes/admin-bootstrap.php';
require_once APP_ROOT . '/includes/codes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'set_url') {
        $u = trim((string)($_POST['site_url'] ?? ''));
        $u = rtrim($u, '/');
        if ($u !== '' && !preg_match('~^https?://~', $u)) $u = 'https://' . $u;
        $st = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $st->execute(['site_url', $u]);
        log_activity('settings', 'site_url', null, $u);
        flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
        redirect(admin_url('qr-tools.php'));
    }

    if ($act === 'regen_all') {
        try {
            $n = regenerate_all_codes();
            log_activity('regen_all_codes', 'system', null, $n . ' codes');
            flash('success', t('a_qr_regen_done', 'هەموو QR و بارکۆدەکان نوێکرانەوە') . ' (' . knum($n) . ')');
        } catch (Throwable $ex) {
            flash('error', t('a_warn_codes', 'دروستکردنی QR/بارکۆد سەرکەوتوو نەبوو') . ' (' . $ex->getMessage() . ')');
        }
        redirect(admin_url('qr-tools.php'));
    }
}

$siteUrl = setting('site_url', '');
$shadeCount = (int)db()->query('SELECT COUNT(*) FROM palette_shades')->fetchColumn();
$productCount = (int)db()->query("SELECT COUNT(*) FROM products WHERE code IS NOT NULL AND code <> ''")->fetchColumn();
$missing = (int)db()->query("SELECT
    (SELECT COUNT(*) FROM palette_shades WHERE qr_path IS NULL OR qr_path = '') +
    (SELECT COUNT(*) FROM products WHERE code IS NOT NULL AND code <> '' AND (qr_path IS NULL OR qr_path = ''))")->fetchColumn();

admin_header(t('a_qr_tools', 'ئامرازەکانی QR'), 'qr-tools');
?>

<div class="help-box">
  💡 <?= e(t('a_qr_help', 'هەر QRێک لینکی تایبەتی بەرهەم/ڕەنگەکە لەخۆدەگرێت. کاتێک ماڵپەڕەکە دەگوازیتەوە بۆ دۆمەینی نوێ (نموونە: rawanddecoration.com)، لێرە لینکەکە بگۆڕە و پاشان دوگمەی «نوێکردنەوەی هەموو» دابگرە بۆ ئەوەی هەموو QRەکان بە لینکی نوێ دروستببنەوە.')) ?>
</div>

<div class="stat-grid" style="margin-bottom:18px">
  <div class="stat"><div class="stat-num"><?= e(knum($shadeCount)) ?></div><div class="stat-label"><?= e(t('a_shades', 'ڕەنگەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($productCount)) ?></div><div class="stat-label"><?= e(t('a_qr_products_with_code', 'بەرهەمی کۆددار')) ?></div></div>
  <div class="stat"><div class="stat-num" style="color:<?= $missing ? '#B4544A' : 'inherit' ?>"><?= e(knum($missing)) ?></div><div class="stat-label"><?= e(t('a_qr_missing', 'QRی دروستنەکراو')) ?></div></div>
</div>

<div class="panel" style="max-width:720px">
  <h2 class="panel-title"><?= e(t('a_set_site_url', 'لینکی ماڵپەڕ')) ?></h2>
  <form method="post" style="display:flex;gap:10px;flex-wrap:wrap">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="set_url">
    <input type="text" name="site_url" dir="ltr" value="<?= e($siteUrl) ?>" placeholder="https://rawanddecoration.com" style="flex:1;min-width:260px">
    <button class="btn btn-gold" type="submit"><?= e(t('a_save', 'پاشەکەوتکردن')) ?></button>
  </form>
  <div class="f-hint"><?= e(t('a_qr_url_hint', 'ئەگەر بەتاڵ بێت، لینکی ئێستای ماڵپەڕ بەکاردەهێنرێت.')) ?></div>
</div>

<div class="panel" style="max-width:720px">
  <h2 class="panel-title"><?= e(t('a_qr_regen_all', 'نوێکردنەوەی هەموو QR و بارکۆدەکان')) ?></h2>
  <p class="muted" style="font-size:.85rem;margin-bottom:12px"><?= e(t('a_qr_regen_note', 'بۆ هەموو ڕەنگەکان و بەرهەمە کۆددارەکان QR و بارکۆدی نوێ دروستدەکات. چەند چرکەیەک دەخایەنێت.')) ?></p>
  <form method="post" data-confirm="<?= e(t('a_qr_regen_confirm', 'دڵنیایت؟ هەموو QRەکان دروستدەبنەوە.')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="regen_all">
    <button class="btn btn-gold" type="submit">🔄 <?= e(t('a_qr_regen_all', 'نوێکردنەوەی هەموو QR و بارکۆدەکان')) ?></button>
  </form>
</div>

<?php admin_footer(); ?>
