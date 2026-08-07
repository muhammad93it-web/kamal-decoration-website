<?php
/** Public page header. Expects optional $PAGE = [title, desc, image, type, nav, body_class]. */
$PAGE = $PAGE ?? [];

if (setting('maintenance') === '1' && !is_logged_in()) {
    http_response_code(503);
    include APP_ROOT . '/templates/maintenance.php';
    exit;
}

$logoPath = setting('logo_path');
$siteName = setting('site_name', 'دیکۆراتی ڕەوەند');
$activeNav = $PAGE['nav'] ?? '';
$navItems = [
    'home'     => ['index.php', t('nav_home')],
    'products' => ['products.php', t('nav_products')],
    'palettes' => ['palettes.php', t('nav_palettes')],
    'projects' => ['projects.php', t('nav_projects')],
    'gallery'  => ['gallery.php', t('nav_gallery')],
    'posts'    => ['posts.php', t('nav_posts')],
    'contact'  => ['contact.php', t('nav_contact')],
];
?>
<!doctype html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php seo_head($PAGE); ?>
<?php if (setting('favicon_path') !== ''): ?>
<link rel="icon" href="<?= e(upload_url(setting('favicon_path'))) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>?v=2">
<style>:root{--accent:<?= e(setting('color_accent', '#46549B')) ?>;}</style>
<script>window.KD_BASE = <?= json_encode(site_path(), JSON_UNESCAPED_SLASHES) ?>;</script>
</head>
<body class="<?= e($PAGE['body_class'] ?? '') ?>">

<header class="site-header" id="siteHeader">
  <div class="container header-inner">
    <button class="nav-toggle" id="navToggle" aria-label="<?= e(t('menu')) ?>" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?>">
      <?php if ($logoPath !== ''): ?>
        <img src="<?= e(upload_url($logoPath)) ?>" alt="<?= e($siteName) ?>" class="brand-logo">
      <?php else: ?>
        <span class="brand-text"><?= e($siteName) ?></span>
        <span class="brand-sub"><?= e(setting('tagline')) ?></span>
      <?php endif; ?>
    </a>

    <nav class="main-nav" aria-label="ناوبردنی سەرەکی">
      <?php foreach ($navItems as $key => [$href, $label]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $key === $activeNav ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <form class="header-search" action="<?= e(url('search.php')) ?>" method="get" role="search" id="headerSearch">
        <svg class="search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" name="q" id="searchInput" placeholder="<?= e(t('search_placeholder')) ?>"
               autocomplete="off" aria-label="<?= e(t('btn_search')) ?>">
        <div class="suggest-box" id="suggestBox" hidden></div>
      </form>
      <a class="icon-btn scan-btn" href="<?= e(url('scanner.php')) ?>" title="<?= e(t('nav_scanner')) ?>" aria-label="<?= e(t('nav_scanner')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
      </a>
      <?php foreach (social_links('header') as $l): ?>
        <a class="icon-btn header-social" href="<?= e($l['url']) ?>" target="_blank" rel="noopener" title="<?= e($l['name']) ?>" aria-label="<?= e($l['name']) ?>"><?= social_icon($l['platform']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</header>

<div class="drawer-backdrop" id="drawerBackdrop" hidden></div>
<aside class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-head">
    <span class="drawer-title"><?= e($siteName) ?></span>
    <button class="drawer-close" id="drawerClose" aria-label="<?= e(t('close')) ?>">✕</button>
  </div>
  <form class="drawer-search" action="<?= e(url('search.php')) ?>" method="get" role="search">
    <input type="search" name="q" placeholder="<?= e(t('search_placeholder')) ?>" autocomplete="off">
    <button type="submit" aria-label="<?= e(t('btn_search')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    </button>
  </form>
  <nav class="drawer-nav">
    <?php foreach ($navItems as $key => [$href, $label]): ?>
      <a href="<?= e(url($href)) ?>" class="<?= $key === $activeNav ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= e(url('about.php')) ?>" class="<?= $activeNav === 'about' ? 'active' : '' ?>"><?= e(t('nav_about')) ?></a>
    <a href="<?= e(url('scanner.php')) ?>" class="<?= $activeNav === 'scanner' ? 'active' : '' ?>"><?= e(t('nav_scanner')) ?></a>
  </nav>
  <div class="drawer-socials">
    <?php foreach (social_links('footer') as $l): ?>
      <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($l['name']) ?>"><?= social_icon($l['platform']) ?></a>
    <?php endforeach; ?>
  </div>
</aside>

<main id="main">
