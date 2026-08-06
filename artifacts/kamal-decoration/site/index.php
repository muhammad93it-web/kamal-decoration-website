<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$db = db();

// hero slides (respect optional date window)
$slides = $db->query(
    "SELECT * FROM sliders
     WHERE is_active = 1
       AND (starts_at IS NULL OR starts_at <= NOW())
       AND (ends_at IS NULL OR ends_at >= NOW())
     ORDER BY sort_order, id"
)->fetchAll();

$featCats = $db->query(
    "SELECT * FROM categories WHERE type = 'product' AND is_active = 1 AND is_featured = 1 ORDER BY sort_order LIMIT 6"
)->fetchAll();

$featProducts = $db->query(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY p.sort_order, p.id DESC LIMIT 8"
)->fetchAll();

$palettes = $db->query(
    "SELECT * FROM palettes WHERE is_active = 1 ORDER BY sort_order LIMIT 3"
)->fetchAll();
$paletteShades = [];
if ($palettes) {
    $ids = implode(',', array_map(fn($p) => (int)$p['id'], $palettes));
    foreach ($db->query("SELECT palette_id, hex_color FROM palette_shades WHERE is_active = 1 AND palette_id IN ($ids) ORDER BY position") as $r) {
        $paletteShades[(int)$r['palette_id']][] = $r['hex_color'];
    }
}

$projects = $db->query(
    "SELECT * FROM projects WHERE is_active = 1 ORDER BY is_featured DESC, sort_order, id DESC LIMIT 3"
)->fetchAll();

$posts = $db->query(
    "SELECT * FROM posts WHERE is_published = 1 AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3"
)->fetchAll();

$testimonials = $db->query(
    "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id LIMIT 4"
)->fetchAll();

track_page_view('home');

$PAGE = ['nav' => 'home', 'image' => $slides[0]['image'] ?? ''];
require APP_ROOT . '/templates/header.php';
?>

<!-- هێرۆ -->
<section class="hero">
  <?php if ($slides): foreach ($slides as $i => $s): ?>
    <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>"
         data-title="<?= e($s['title'] ?? '') ?>" data-sub="<?= e($s['subtitle'] ?? '') ?>">
      <img src="<?= e(upload_url($s['image'])) ?>" alt="<?= e($s['title'] ?: setting('site_name')) ?>"
           <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
    </div>
  <?php endforeach; else: ?>
    <div class="hero-slide active" style="background:linear-gradient(135deg,#232120,#4C2F1D)"></div>
  <?php endif; ?>

  <div class="container hero-content">
    <span class="hero-kicker"><?= e(setting('tagline')) ?></span>
    <h1 class="hero-title" id="heroTitle"><?= e(setting('hero_title', 'دیکۆراتی کەمال')) ?></h1>
    <p class="hero-sub" id="heroSub"><?= e(setting('hero_subtitle')) ?></p>
    <div class="hero-actions">
      <?php if (setting('hero_btn1_text') !== ''): ?>
        <a class="btn btn-gold" href="<?= e(url(setting('hero_btn1_url', 'products.php'))) ?>"><?= e(setting('hero_btn1_text')) ?></a>
      <?php endif; ?>
      <?php if (setting('hero_btn2_text') !== ''): ?>
        <a class="btn btn-outline-light" href="<?= e(url(setting('hero_btn2_url', 'projects.php'))) ?>"><?= e(setting('hero_btn2_text')) ?></a>
      <?php endif; ?>
      <?php if (setting('hero_btn3_text') !== ''): ?>
        <a class="btn btn-wa" href="<?= e(wa_link(t('wa_general'))) ?>" target="_blank" rel="noopener">
          <?= social_icon('whatsapp') ?> <?= e(setting('hero_btn3_text')) ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-dots" id="heroDots"></div>
</section>

<!-- بەشە سەرەکییەکان -->
<?php if ($featCats): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('home_featured_cats'), t('home_featured_cats_sub'), url('products.php')); ?>
    <div class="grid-cats">
      <?php foreach ($featCats as $c) render_category_card($c); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- پاڵێتی ڕەنگەکان -->
<?php if ($palettes): ?>
<section class="section section-tint">
  <div class="container">
    <?php section_head(t('home_palettes'), t('home_palettes_sub'), url('palettes.php')); ?>
    <div class="grid-cards">
      <?php foreach ($palettes as $pal):
          $hexes = $paletteShades[(int)$pal['id']] ?? [];
          render_palette_card($pal, $hexes, count($hexes));
      endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- بەرهەمە دیارەکان -->
<?php if ($featProducts): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('home_featured_products'), null, url('products.php')); ?>
    <div class="grid-cards">
      <?php foreach ($featProducts as $p) render_product_card($p); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- بۆچی ئێمە -->
<section class="section section-dark">
  <div class="container">
    <?php section_head(t('home_why')); ?>
    <div class="grid-why">
      <?php
      $whyIcons = [
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 7.7l5.4-.8z"/></svg>',
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a4.5 4.5 0 0 0-6.4 6.4l-4 4V20h3.3l4-4a4.5 4.5 0 0 0 6.4-6.4l-3 3-2.3-2.3z"/></svg>',
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1v22M17 5.5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.4 8.6 8.6 0 0 1-3.9-.9L3 21l2-5.4a8.3 8.3 0 0 1-1-4A8.4 8.4 0 0 1 12.5 3a8.4 8.4 0 0 1 8.5 8.5z"/></svg>',
      ];
      for ($i = 1; $i <= 4; $i++): ?>
      <div class="why-item reveal">
        <div class="why-icon"><?= $whyIcons[$i - 1] ?></div>
        <h3><?= e(t("why_{$i}_title")) ?></h3>
        <p><?= e(t("why_{$i}_text")) ?></p>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- پرۆژەکان -->
<?php if ($projects): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('home_projects'), null, url('projects.php')); ?>
    <div class="grid-cards">
      <?php foreach ($projects as $pr) render_project_card($pr); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ڕای کڕیاران -->
<?php if ($testimonials): ?>
<section class="section section-tint">
  <div class="container">
    <?php section_head(t('home_testimonials')); ?>
    <div class="grid-testimonials">
      <?php foreach ($testimonials as $tst) render_testimonial($tst); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- بابەتەکان -->
<?php if ($posts): ?>
<section class="section">
  <div class="container">
    <?php section_head(t('home_posts'), null, url('posts.php')); ?>
    <div class="grid-cards">
      <?php foreach ($posts as $po) render_post_card($po); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
/* rotate hero text with slides when a slide has its own title */
document.addEventListener('DOMContentLoaded', function () {
  var defaults = {
    title: document.getElementById('heroTitle') ? document.getElementById('heroTitle').textContent : '',
    sub: document.getElementById('heroSub') ? document.getElementById('heroSub').textContent : ''
  };
  var obs = new MutationObserver(function () {
    var act = document.querySelector('.hero-slide.active');
    if (!act) return;
    var tEl = document.getElementById('heroTitle'), sEl = document.getElementById('heroSub');
    if (tEl) tEl.textContent = act.dataset.title || defaults.title;
    if (sEl) sEl.textContent = act.dataset.sub || defaults.sub;
  });
  document.querySelectorAll('.hero-slide').forEach(function (s) {
    obs.observe(s, { attributes: true, attributeFilter: ['class'] });
  });
});
</script>

<?php jsonld_localbusiness(); ?>
<?php require APP_ROOT . '/templates/footer.php'; ?>
