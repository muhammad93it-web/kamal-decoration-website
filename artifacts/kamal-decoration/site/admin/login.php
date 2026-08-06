<?php
define('KD_ADMIN_PUBLIC', true);
require __DIR__ . '/includes/admin-bootstrap.php';

if (is_logged_in()) {
    redirect(admin_url(''));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!csrf_ok()) {
        $error = t('csrf_invalid', 'فۆرمەکە بەسەرچووە — دووبارە هەوڵبدەوە.');
    } elseif ($username === '' || $password === '') {
        $error = t('a_login_required', 'ناوی بەکارهێنەر و وشەی نهێنی داخڵ بکە.');
    } elseif (login_locked($username)) {
        $error = t('a_login_locked', 'هەوڵی زۆر — ١٠ خولەک چاوەڕێبکە و دووبارە هەوڵبدەوە.');
    } elseif (attempt_login($username, $password)) {
        $next = (string)($_GET['next'] ?? '');
        // only allow redirects inside the admin area (relative or same-site absolute)
        $adminPrefix = url('admin/');
        $okRel = $next !== '' && str_starts_with($next, $adminPrefix)
            && !str_contains($next, '//') && !str_contains($next, '\\');
        $okAbs = $next !== '' && str_starts_with($next, site_base() . '/admin/');
        if ($okRel || $okAbs) {
            redirect($next);
        }
        redirect(admin_url(''));
    } else {
        $error = t('a_login_wrong', 'ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە.');
    }
}
?><!doctype html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e(t('a_login_title', 'چوونەژوورەوە')) ?> — <?= e(setting('site_name', 'دیکۆراتی کەمال')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=1">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-brand">
      <?php $logo = setting('logo_path'); if ($logo): ?>
        <img src="<?= e(upload_url($logo)) ?>" alt="">
      <?php endif; ?>
      <h1><?= e(setting('site_name', 'دیکۆراتی کەمال')) ?></h1>
      <p><?= e(t('a_login_sub', 'پانێڵی بەڕێوەبردن — تەنها بۆ ستاف')) ?></p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="f-row">
        <label for="lu"><?= e(t('a_username', 'ناوی بەکارهێنەر')) ?></label>
        <input type="text" id="lu" name="username" required autofocus dir="ltr" value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="f-row">
        <label for="lp"><?= e(t('a_password', 'وشەی نهێنی')) ?></label>
        <input type="password" id="lp" name="password" required dir="ltr">
      </div>
      <button class="btn btn-gold" type="submit" style="width:100%;padding:12px"><?= e(t('a_login_btn', 'چوونەژوورەوە')) ?></button>
    </form>

    <p class="tac muted" style="font-size:.72rem;margin:18px 0 0">
      <a href="<?= e(url('')) ?>">← <?= e(t('a_back_site', 'گەڕانەوە بۆ ماڵپەڕ')) ?></a>
    </p>
  </div>
</body>
</html>
