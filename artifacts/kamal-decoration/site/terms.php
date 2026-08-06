<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$PAGE = ['title' => t('terms_title'), 'nav' => ''];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container"><h1 class="page-title"><?= e(t('terms_title')) ?></h1></div>
</section>

<section class="section">
  <div class="container prose" style="max-width:820px">
    <p><?= e(t('terms_p1', 'هەموو ناوەڕۆکی ئەم ماڵپەڕە (وێنە، دەق، کۆدی ڕەنگ و ناوی بەرهەمەکان) موڵکی دیکۆراتی کەمالە و بەکارهێنانەوەی بەبێ ڕەزامەندی ڕێپێنەدراوە.')) ?></p>
    <p><?= e(t('terms_p2', 'نرخەکان ڕەنگە بگۆڕدرێن بەبێ ئاگادارکردنەوەی پێشوەخت. بۆ نرخی کۆتایی و بەردەستبوونی بەرهەم، تکایە پەیوەندیمان پێوە بکە.')) ?></p>
    <p><?= e(t('terms_p3', 'ڕەنگی نیشاندراو لە شاشەکان لەوانەیە کەمێک جیاواز بێت لە ڕەنگی ڕاستەقینەی بەرهەم — بۆ دڵنیابوون داوای نموونەی ڕاستەقینە بکە.')) ?></p>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
