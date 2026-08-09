<?php
/**
 * Admin bootstrap — loads site bootstrap, enforces login, provides layout.
 */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!defined('KD_ADMIN_PUBLIC')) {
    require_login();
}

// admin pages must never be cached — otherwise the browser "back" button
// shows stale unread rows / badge counts after an action
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/** Purify rich HTML (Quill output). Falls back to strip_tags when the vendor lib is absent. */
function kd_purify(string $html): string
{
    $auto = APP_ROOT . '/libraries/vendor/autoload.php';
    if (is_file($auto)) {
        require_once $auto;
        if (class_exists(\HTMLPurifier::class)) {
            static $purifier = null;
            if ($purifier === null) {
                $cfg = \HTMLPurifier_Config::createDefault();
                $cfg->set('Core.Encoding', 'UTF-8');
                $cfg->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,h2,h3,h4,ul,ol,li,a[href|target|rel],img[src|alt],blockquote,span[style],sub,sup');
                $cfg->set('CSS.AllowedProperties', 'color,background-color,text-align');
                $cfg->set('Attr.AllowedFrameTargets', ['_blank']);
                $cfg->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
                $cfg->set('Cache.SerializerPath', sys_get_temp_dir());
                $purifier = new \HTMLPurifier($cfg);
            }
            return $purifier->purify($html);
        }
    }
    // Fail-safe fallback (vendor lib missing): bare formatting tags only, ALL attributes stripped.
    $clean = strip_tags($html, '<p><br><b><strong><i><em><u><s><h2><h3><h4><ul><ol><li><blockquote><sub><sup>');
    return (string)preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $clean);
}

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function unread_messages_count(): int
{
    static $n = null;
    if ($n === null) {
        $n = (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    }
    return $n;
}

/** Sidebar + header of every admin page. */
function admin_header(string $title, string $active = ''): void
{
    $u = current_user();
    $unread = unread_messages_count();
    $items = [
        ['key' => 'dashboard',    'url' => admin_url(''),                'icon' => '📊', 'label' => t('a_dashboard', 'داشبۆرد')],
        ['key' => 'messages',     'url' => admin_url('messages.php'),    'icon' => '✉️', 'label' => t('a_messages', 'پەیامەکان'), 'badge' => $unread],
        ['sep' => t('a_sec_content', 'ناوەڕۆک')],
        ['key' => 'products',     'url' => admin_url('crud.php?e=products'),     'icon' => '📦', 'label' => t('a_products', 'بەرهەمەکان')],
        ['key' => 'categories',   'url' => admin_url('crud.php?e=categories'),   'icon' => '🗂', 'label' => t('a_categories', 'بەشەکان')],
        ['key' => 'palettes',     'url' => admin_url('crud.php?e=palettes'),     'icon' => '🎨', 'label' => t('a_palettes', 'پاڵێتەکان')],
        ['key' => 'shades',       'url' => admin_url('shades.php'),      'icon' => '🌈', 'label' => t('a_shades', 'ڕەنگەکان')],
        ['key' => 'projects',     'url' => admin_url('crud.php?e=projects'),     'icon' => '🏗', 'label' => t('a_projects', 'پرۆژەکان')],
        ['key' => 'posts',        'url' => admin_url('crud.php?e=posts'),        'icon' => '📰', 'label' => t('a_posts', 'بابەتەکان')],
        ['key' => 'sliders',      'url' => admin_url('crud.php?e=sliders'),      'icon' => '🖼', 'label' => t('a_sliders', 'سلایدشۆ')],
        ['key' => 'testimonials', 'url' => admin_url('crud.php?e=testimonials'), 'icon' => '⭐', 'label' => t('a_testimonials', 'ڕای کڕیاران')],
        ['key' => 'media',        'url' => admin_url('media.php'),       'icon' => '🗃', 'label' => t('a_media', 'میدیا')],
        ['sep' => t('a_sec_tools', 'ئامرازەکان')],
        ['key' => 'labels',       'url' => admin_url('labels.php'),      'icon' => '🏷', 'label' => t('a_labels', 'لەیبڵی ڕەنگ')],
        ['key' => 'qr-tools',     'url' => admin_url('qr-tools.php'),    'icon' => '🔳', 'label' => t('a_qr_tools', 'QR و بارکۆد')],
        ['key' => 'social',       'url' => admin_url('social.php'),      'icon' => '🔗', 'label' => t('a_social', 'سۆشیال میدیا')],
        ['key' => 'logs',         'url' => admin_url('logs.php'),        'icon' => '📈', 'label' => t('a_logs', 'ڕاپۆرتەکان')],
        ['sep' => t('a_sec_system', 'سیستەم')],
        ['key' => 'settings',     'url' => admin_url('settings.php'),    'icon' => '⚙️', 'label' => t('a_settings', 'ڕێکخستنەکان')],
    ];
    if (is_super()) {
        $items[] = ['key' => 'users',   'url' => admin_url('users.php'),   'icon' => '👥', 'label' => t('a_users', 'بەکارهێنەران')];
        $items[] = ['key' => 'backups', 'url' => admin_url('backups.php'), 'icon' => '💾', 'label' => t('a_backups', 'باکاپ')];
    }
    ?><!doctype html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> — <?= e(t('a_panel', 'بەڕێوەبردن')) ?> | <?= e(setting('site_name', 'دیکۆراتی ڕەوەند')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=<?= e(KD_VERSION) ?>">
<style>:root{--accent:<?= e(setting('color_accent', '#BFA05A')) ?>;}</style>
<script>window.KD_BASE = <?= json_encode(site_path(), JSON_UNESCAPED_SLASHES) ?>;</script>
</head>
<body class="admin-body">
<button class="a-burger" id="aBurger" aria-label="menu">☰</button>

<aside class="a-side" id="aSide">
  <div class="a-brand">
    <?php $logo = setting('logo_path'); if ($logo): ?>
      <img src="<?= e(upload_url($logo)) ?>" alt="" class="a-logo">
    <?php else: ?>
      <span class="a-brand-text"><?= e(setting('site_name', 'دیکۆراتی ڕەوەند')) ?></span>
    <?php endif; ?>
    <span class="a-brand-sub"><?= e(t('a_panel', 'بەڕێوەبردن')) ?></span>
  </div>
  <nav class="a-nav">
    <?php foreach ($items as $it): ?>
      <?php if (isset($it['sep'])): ?>
        <div class="a-sep"><?= e($it['sep']) ?></div>
      <?php else: ?>
        <a href="<?= e($it['url']) ?>" class="<?= $active === $it['key'] ? 'active' : '' ?>">
          <span class="a-ico"><?= $it['icon'] ?></span> <?= e($it['label']) ?>
          <?php if (!empty($it['badge'])): ?><span class="a-badge"><?= e(knum($it['badge'])) ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <div class="a-side-foot">
    <a href="<?= e(url('')) ?>" target="_blank">🌐 <?= e(t('a_view_site', 'بینینی ماڵپەڕ')) ?></a>
    <a href="<?= e(admin_url('logout.php')) ?>">🚪 <?= e(t('a_logout', 'چوونەدەرەوە')) ?></a>
    <span class="a-ver" dir="ltr"><?= e(t('a_version', 'وەشان')) ?> <?= e(KD_VERSION) ?></span>
  </div>
</aside>
<div class="a-side-backdrop" id="aSideBackdrop" hidden></div>

<main class="a-main">
  <header class="a-top">
    <h1 class="a-title"><?= e($title) ?></h1>
    <div class="a-user">
      <span class="a-user-name"><?= e($u['display_name'] ?: $u['username']) ?></span>
      <span class="a-user-role"><?= e(is_super() ? t('a_role_super', 'بەڕێوەبەری باڵا') : t('a_role_editor', 'ئیدیتەر')) ?></span>
    </div>
  </header>
  <?php foreach (flash_get() as $f): ?>
    <div class="alert <?= $f['type'] === 'error' ? 'alert-error' : 'alert-success' ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
</main>
<script src="<?= e(url('admin/assets/admin.js')) ?>?v=<?= e(KD_VERSION) ?>"></script>
</body>
</html><?php
}
