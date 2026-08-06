<?php
require __DIR__ . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/codes.php';

$raw = trim((string)($_GET['code'] ?? ''));
$src = in_array($_GET['src'] ?? '', ['qr', 'manual', 'barcode'], true) ? $_GET['src'] : 'qr';

$code = strtoupper(normalize_text($raw));
$code = preg_replace('/\s+/', '-', $code);

$hit = $code !== '' ? find_by_code($code) : null;

$targetType = null;
$targetUrl = null;
$targetId = null;
if ($hit) {
    [$targetType, $row] = $hit;
    $targetId = (int)$row['id'];
    $targetUrl = url(match ($targetType) {
        'shade' => 'shade/' . $row['slug'],
        'product' => 'product/' . $row['slug'],
        default => 'palette/' . $row['slug'],
    });
}

log_scan($raw, $targetType, $targetId, (bool)$hit);

if ($targetUrl) {
    redirect($targetUrl);
}

http_response_code(404);
require APP_ROOT . '/templates/partials/cards.php';
$PAGE = ['title' => t('resolve_notfound_title', 'کۆدەکە نەدۆزرایەوە'), 'nav' => ''];
require APP_ROOT . '/templates/header.php';
?>

<section class="err-page">
  <div class="err-code" dir="ltr"><?= e($raw !== '' ? mb_substr($raw, 0, 20) : '؟') ?></div>
  <h1><?= e(t('resolve_notfound_title', 'کۆدەکە نەدۆزرایەوە')) ?></h1>
  <p><?= e(t('resolve_notfound_text', 'دڵنیابە کۆدەکە بە دروستی نووسراوە (بۆ نموونە KD-S101)، یان دووبارە سکانی بکەوە.')) ?></p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a class="btn btn-gold" href="<?= e(url('scanner.php')) ?>"><?= e(t('nav_scanner')) ?></a>
    <a class="btn btn-ghost" href="<?= e(url('search.php')) ?>"><?= e(t('btn_search')) ?></a>
    <a class="btn btn-ghost" href="<?= e(url('')) ?>"><?= e(t('nav_home')) ?></a>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
