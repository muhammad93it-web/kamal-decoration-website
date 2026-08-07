<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$st = db()->prepare(
    "SELECT po.*, c.name AS category_name, c.slug AS category_slug FROM posts po
     LEFT JOIN categories c ON c.id = po.category_id
     WHERE po.slug = ? AND po.is_published = 1 AND po.published_at <= NOW() LIMIT 1"
);
$st->execute([$slug]);
$po = $st->fetch();
if (!$po) { http_response_code(404); require APP_ROOT . '/404.php'; exit; }

$rel = db()->prepare(
    "SELECT * FROM posts WHERE is_published = 1 AND published_at <= NOW() AND id <> ? ORDER BY published_at DESC LIMIT 3"
);
$rel->execute([$po['id']]);
$related = $rel->fetchAll();

track_page_view('post', (int)$po['id']);
bump_views('posts', (int)$po['id']);

$pageUrl = url('post/' . rawurlencode($po['slug']));

$PAGE = ['title' => $po['title'], 'desc' => $po['excerpt'] ?? '', 'image' => $po['cover_image'] ?? '', 'nav' => 'posts', 'type' => 'article'];
require APP_ROOT . '/templates/header.php';

render_breadcrumbs([
    ['label' => t('nav_posts'), 'url' => url('posts.php')],
    ['label' => $po['title']],
]);
?>

<article class="section">
  <div class="container" style="max-width:820px">
    <div class="detail-meta">
      <?php if ($po['category_name']): ?>
        <a class="chip" href="<?= e(url('posts.php')) ?>?category=<?= e(rawurlencode($po['category_slug'])) ?>"><?= e($po['category_name']) ?></a>
      <?php endif; ?>
      <span class="chip">🗓 <?= e(kdate($po['published_at'])) ?></span>
      <span class="chip">👁 <?= e(knum((int)$po['views'] + 1)) ?></span>
    </div>
    <h1 style="font-size:clamp(1.4rem,3vw,2.1rem);margin:16px 0 22px"><?= e($po['title']) ?></h1>

    <?php if ($po['cover_image']): ?>
      <div style="border-radius:var(--radius-lg);overflow:hidden;margin-bottom:30px">
        <img src="<?= e(upload_url($po['cover_image'])) ?>" alt="<?= e($po['title']) ?>">
      </div>
    <?php endif; ?>

    <div class="prose"><?= $po['body'] /* purified with HTMLPurifier on save */ ?></div>

    <div class="detail-actions" style="margin-top:36px;padding-top:22px;border-top:1px dashed var(--line)">
      <button class="btn btn-ghost" type="button" data-share="<?= e($pageUrl) ?>" data-share-title="<?= e($po['title']) ?>" data-copied="<?= e(t('copied')) ?>"><?= e(t('post_share')) ?></button>
      <a class="btn btn-wa btn-small" href="<?= e(wa_link($po['title'] . "\n" . $pageUrl)) ?>" target="_blank" rel="noopener"><?= social_icon('whatsapp') ?> WhatsApp</a>
    </div>
  </div>
</article>

<?php if ($related): ?>
<section class="section section-tint">
  <div class="container">
    <?php section_head(t('post_related')); ?>
    <div class="grid-cards">
      <?php foreach ($related as $r) render_post_card($r); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
