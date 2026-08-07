<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$PAGE = ['title' => t('privacy_title'), 'nav' => ''];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container"><h1 class="page-title"><?= e(t('privacy_title')) ?></h1></div>
</section>

<section class="section">
  <div class="container prose" style="max-width:820px">
    <p><?= e(t('privacy_p1', 'ئەم ماڵپەڕە هیچ زانیارییەکی کەسیت لێ کۆناکاتەوە مەگەر خۆت بە فۆرمی پەیوەندی بینێریت (ناو، ژمارە، ئیمەیل و پەیامەکەت). ئەو زانیاریانە تەنها بۆ وەڵامدانەوەی داواکارییەکەت بەکاردێن.')) ?></p>
    <p><?= e(t('privacy_p2', 'بۆ باشترکردنی خزمەتگوزارییەکانمان، ژمارەی بینینی پەڕەکان و گەڕانەکان بە شێوەیەکی گشتی (بەبێ ناسنامەی کەسی) تۆمار دەکرێن.')) ?></p>
    <p><?= e(t('privacy_p3', 'هیچ زانیارییەک بە لایەنی سێیەم نافرۆشرێت و ناگوازرێتەوە. بۆ هەر پرسیارێک دەربارەی زانیارییەکانت، پەیوەندیمان پێوە بکە.')) ?></p>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
