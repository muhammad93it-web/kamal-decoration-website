<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$st = db()->prepare(
    "SELECT pr.*, c.name AS category_name FROM projects pr
     LEFT JOIN categories c ON c.id = pr.category_id
     WHERE pr.slug = ? AND pr.is_active = 1 LIMIT 1"
);
$st->execute([$slug]);
$pr = $st->fetch();
if (!$pr) { http_response_code(404); require APP_ROOT . '/404.php'; exit; }

$gi = db()->prepare('SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order, id');
$gi->execute([$pr['id']]);
$gallery = $gi->fetchAll();

$rel = db()->prepare('SELECT * FROM projects WHERE is_active = 1 AND id <> ? ORDER BY RAND() LIMIT 3');
$rel->execute([$pr['id']]);
$related = $rel->fetchAll();

track_page_view('project', (int)$pr['id']);
bump_views('projects', (int)$pr['id']);

$hasBA = !empty($pr['before_image']) && !empty($pr['after_image']);
$summary = excerpt_of((string)($pr['description'] ?? ''), 180);
$pageUrl = url('project/' . rawurlencode($pr['slug']));

$PAGE = ['title' => $pr['title'], 'desc' => $summary, 'image' => $pr['main_image'] ?? '', 'nav' => 'projects', 'type' => 'article'];
require APP_ROOT . '/templates/header.php';

render_breadcrumbs([
    ['label' => t('nav_projects'), 'url' => url('projects.php')],
    ['label' => $pr['title']],
]);
?>

<section class="page-hero">
  <div class="container">
    <div class="detail-meta" style="margin-bottom:12px">
      <?php if ($pr['category_name']): ?><span class="chip" style="background:rgba(250,247,242,.12);border-color:transparent;color:#FAF7F2"><?= e($pr['category_name']) ?></span><?php endif; ?>
      <?php if ($pr['location']): ?><span class="chip" style="background:rgba(250,247,242,.12);border-color:transparent;color:#FAF7F2">📍 <?= e($pr['location']) ?></span><?php endif; ?>
      <?php if ($pr['completed_at']): ?><span class="chip" style="background:rgba(250,247,242,.12);border-color:transparent;color:#FAF7F2">🗓 <?= e(kdate($pr['completed_at'])) ?></span><?php endif; ?>
      <?php if ($pr['client_name']): ?><span class="chip" style="background:rgba(250,247,242,.12);border-color:transparent;color:#FAF7F2">👤 <?= e($pr['client_name']) ?></span><?php endif; ?>
    </div>
    <h1 class="page-title"><?= e($pr['title']) ?></h1>
    <?php if ($summary): ?><p class="page-sub"><?= e($summary) ?></p><?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if ($hasBA): ?>
      <?php section_head(t('project_before_after'), t('project_ba_hint')); ?>
      <div class="ba-wrap reveal" style="max-width:960px;margin-inline:auto">
        <img src="<?= e(upload_url($pr['before_image'])) ?>" alt="<?= e(t('project_before')) ?> — <?= e($pr['title']) ?>">
        <img class="ba-after-img" src="<?= e(upload_url($pr['after_image'])) ?>" alt="<?= e(t('project_after')) ?> — <?= e($pr['title']) ?>">
        <span class="ba-tag ba-tag-before"><?= e(t('project_before')) ?></span>
        <span class="ba-tag ba-tag-after"><?= e(t('project_after')) ?></span>
        <div class="ba-handle"></div>
      </div>
    <?php elseif ($pr['main_image']): ?>
      <div style="border-radius:var(--radius-lg);overflow:hidden;max-width:960px;margin-inline:auto" class="reveal">
        <img src="<?= e(upload_url($pr['main_image'])) ?>" alt="<?= e($pr['title']) ?>">
      </div>
    <?php endif; ?>

    <?php if ($pr['description']): ?>
      <div class="prose" style="max-width:820px;margin:44px auto 0"><?= nl2br(e($pr['description'])) ?></div>
    <?php endif; ?>
  </div>
</section>

<?php if ($gallery): ?>
<section class="section section-tint">
  <div class="container">
    <?php section_head(t('project_gallery')); ?>
    <div class="masonry">
      <?php foreach ($gallery as $g): ?>
        <a href="<?= e(upload_url($g['image'])) ?>" data-lightbox="project" data-alt="<?= e($g['caption'] ?? $pr['title']) ?>">
          <img src="<?= e(upload_url($g['image'])) ?>" alt="<?= e($g['caption'] ?: $pr['title']) ?>" loading="lazy">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0;<?= $gallery ? '' : 'padding-top:0' ?>">
  <div class="container" style="text-align:center">
    <a class="btn btn-wa" href="<?= e(wa_link(t_replace(t('wa_project'), ['name' => $pr['title'], 'url' => $pageUrl]))) ?>" target="_blank" rel="noopener">
      <?= social_icon('whatsapp') ?> <?= e(t('project_whatsapp_ask')) ?>
    </a>
  </div>
</section>

<?php if ($related): ?>
<section class="section" style="padding-top:0">
  <div class="container">
    <?php section_head(t('project_related')); ?>
    <div class="grid-cards">
      <?php foreach ($related as $r) render_project_card($r); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
