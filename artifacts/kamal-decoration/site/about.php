<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$testimonials = db()->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id LIMIT 4")->fetchAll();

track_page_view('about');

$PAGE = ['title' => t('about_title'), 'desc' => t('about_sub'), 'nav' => 'about'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('about_title')) ?></h1>
    <p class="page-sub"><?= e(t('about_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_about')]]); ?>

<section class="section">
  <div class="container" style="max-width:820px">
    <div class="prose">
      <p><?= e(t('about_p1', 'دیکۆراتی کەمال لە شاری ڕانیە دامەزراوە و ماوەی چەندین ساڵە خزمەتی خاوەن ماڵ و بازرگانەکان دەکات لە بواری دیکۆری ناوماڵ و بازرگانی. ئێمە بڕوامان بە جوانی و کوالیتی هەیە — هەر پرۆژەیەک بۆ ئێمە دەرفەتێکە بۆ دروستکردنی شوێنێکی جوانتر.')) ?></p>
      <p><?= e(t('about_p2', 'کارەکانمان بریتییە لە دابینکردن و جێبەجێکردنی WPC و ڕووپۆشی دیوار، کاغەزی دیوار، پانێڵی PVC مەڕمەڕی، فۆمی 3D، سەقفی ئاسمانی و پانێڵی داری بەدیل — هەموو ئەوانە بە دەستی تیمێکی شارەزا و بە دڵنیایی لە کوالیتی.')) ?></p>
      <p><?= e(t('about_p3', 'سیستەمی پاڵێتی ڕەنگ و کۆدی تایبەتمان بۆ هەر ڕەنگێک دروستکردووە بۆ ئەوەی هەڵبژاردنی ڕەنگ بۆ تۆ ئاسانتر بێت — کۆدەکە بنێرە یان QR سکان بکە و یەکسەر بزانە چی هەڵدەبژێریت.')) ?></p>
    </div>

    <div class="detail-actions" style="margin-top:30px">
      <a class="btn btn-wa" href="<?= e(wa_link(t('wa_general'))) ?>" target="_blank" rel="noopener">
        <?= social_icon('whatsapp') ?> <?= e(t('btn_whatsapp')) ?>
      </a>
      <a class="btn btn-ghost" href="<?= e(url('contact.php')) ?>"><?= e(t('nav_contact')) ?></a>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <?php section_head(t('home_why')); ?>
    <div class="grid-why">
      <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="why-item reveal">
        <div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 7.7l5.4-.8z"/></svg></div>
        <h3><?= e(t("why_{$i}_title")) ?></h3>
        <p><?= e(t("why_{$i}_text")) ?></p>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<?php if ($testimonials): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('home_testimonials')); ?>
    <div class="grid-testimonials">
      <?php foreach ($testimonials as $tst) render_testimonial($tst); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
