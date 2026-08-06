<?php
/** Social links manager — placement toggles drive header/footer/contact/floating rendering. */
require __DIR__ . '/includes/admin-bootstrap.php';

$PLATFORMS = [
    'whatsapp' => 'واتسئاپ',
    'phone' => 'تەلەفۆن',
    'facebook' => 'فەیسبووک',
    'instagram' => 'ئینستاگرام',
    'tiktok' => 'تیکتۆک',
    'snapchat' => 'سناپچات',
    'telegram' => 'تێلێگرام',
    'viber' => 'ڤایبەر',
    'youtube' => 'یوتیوب',
    'maps' => 'نەخشە (Google Maps)',
    'link' => 'لینکی تر',
];
$FLAGS = [
    'show_header' => t('a_so_header', 'سەرپەڕە'),
    'show_footer' => t('a_so_footer', 'ژێرپەڕە'),
    'show_contact' => t('a_so_contact', 'پەیوەندی'),
    'show_floating' => t('a_so_floating', 'دوگمەی سەرەوە (فلۆتینگ)'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'add' || $act === 'update') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80);
        $platform = (string)($_POST['platform'] ?? 'link');
        if (!isset($PLATFORMS[$platform])) $platform = 'link';
        $url = mb_substr(trim((string)($_POST['url'] ?? '')), 0, 500);
        if ($url !== '') {
            // bare domain → assume https
            if (preg_match('~^[\w][\w.-]*\.[a-z]{2,}([/?#]|$)~iu', $url)) $url = 'https://' . $url;
            // scheme allow-list: blocks javascript:, data:, vbscript:, …
            if (!preg_match('~^(https?://|tel:|mailto:|viber://)~i', $url) || preg_match('/[\s<>"\'\\\\]/u', $url)) {
                flash('error', t('a_err_bad_url', 'بەستەرەکە دروست نییە — تەنیا https:// یان tel: یان mailto: ڕێگەپێدراوە'));
                redirect(admin_url('social.php'));
            }
        }
        $sort = (int)($_POST['sort_order'] ?? 0);
        $vals = [];
        foreach ($FLAGS as $flag => $_) $vals[$flag] = isset($_POST[$flag]) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            flash('error', t('a_err_required', 'ناو پێویستە'));
        } elseif ($act === 'add') {
            $st = db()->prepare('INSERT INTO social_links (name, platform, url, sort_order, is_active, show_header, show_footer, show_contact, show_floating) VALUES (?,?,?,?,?,?,?,?,?)');
            $st->execute([$name, $platform, $url, $sort, $active, $vals['show_header'], $vals['show_footer'], $vals['show_contact'], $vals['show_floating']]);
            log_activity('create', 'social', (int)db()->lastInsertId(), $name);
            flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $st = db()->prepare('UPDATE social_links SET name=?, platform=?, url=?, sort_order=?, is_active=?, show_header=?, show_footer=?, show_contact=?, show_floating=? WHERE id=?');
            $st->execute([$name, $platform, $url, $sort, $active, $vals['show_header'], $vals['show_footer'], $vals['show_contact'], $vals['show_floating'], $id]);
            log_activity('update', 'social', $id, $name);
            flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
        }
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM social_links WHERE id = ?')->execute([$id]);
        log_activity('delete', 'social', $id, '');
        flash('success', t('a_deleted', 'سڕایەوە'));
    }
    redirect(admin_url('social.php'));
}

$rows = db()->query('SELECT * FROM social_links ORDER BY sort_order, id')->fetchAll();

admin_header(t('a_social', 'سۆشیال میدیا'), 'social');
?>

<div class="help-box">
  💡 <?= e(t('a_so_help', 'بۆ هەر لینکێک دیاری بکە لە کوێ دەربکەوێت: سەرپەڕە، ژێرپەڕە، پەڕەی پەیوەندی، یان دوگمەی فلۆتینگی واتسئاپ. لینکی بەتاڵ پیشان نادرێت.')) ?>
</div>

<div class="panel">
  <h2 class="panel-title"><?= e(t('a_so_add', 'زیادکردنی لینکی نوێ')) ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="add">
    <div class="f-grid" style="grid-template-columns:1.4fr 1fr 2fr .6fr;align-items:end">
      <div class="f-row"><label><?= e(t('a_f_name', 'ناو')) ?> *</label><input type="text" name="name" required maxlength="80"></div>
      <div class="f-row"><label><?= e(t('a_so_platform', 'پلاتفۆرم')) ?></label>
        <select name="platform"><?php foreach ($PLATFORMS as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
      </div>
      <div class="f-row"><label><?= e(t('a_so_url', 'لینک')) ?></label><input type="text" name="url" dir="ltr" maxlength="500" placeholder="https://…"></div>
      <div class="f-row"><label><?= e(t('a_f_sort', 'ڕیز')) ?></label><input type="number" name="sort_order" value="0"></div>
    </div>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin:10px 0">
      <?php foreach ($FLAGS as $flag => $fl): ?>
        <label class="f-check" style="font-weight:500"><input type="checkbox" name="<?= e($flag) ?>" <?= in_array($flag, ['show_footer', 'show_contact'], true) ? 'checked' : '' ?>> <?= e($fl) ?></label>
      <?php endforeach; ?>
      <label class="f-check" style="font-weight:500"><input type="checkbox" name="is_active" checked> <?= e(t('a_f_active', 'چالاک')) ?></label>
    </div>
    <button class="btn btn-gold" type="submit">＋ <?= e(t('a_new', 'زیادکردن')) ?></button>
  </form>
</div>

<?php foreach ($rows as $r): ?>
  <form method="post" class="panel" style="margin-bottom:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="update">
    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <div class="f-grid" style="grid-template-columns:1.4fr 1fr 2fr .6fr;align-items:end">
      <div class="f-row"><label><?= e(t('a_f_name', 'ناو')) ?></label><input type="text" name="name" value="<?= e($r['name']) ?>" maxlength="80"></div>
      <div class="f-row"><label><?= e(t('a_so_platform', 'پلاتفۆرم')) ?></label>
        <select name="platform"><?php foreach ($PLATFORMS as $k => $l): ?><option value="<?= e($k) ?>" <?= $r['platform'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      </div>
      <div class="f-row"><label><?= e(t('a_so_url', 'لینک')) ?></label><input type="text" name="url" value="<?= e($r['url']) ?>" dir="ltr" maxlength="500"></div>
      <div class="f-row"><label><?= e(t('a_f_sort', 'ڕیز')) ?></label><input type="number" name="sort_order" value="<?= (int)$r['sort_order'] ?>"></div>
    </div>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin:10px 0;align-items:center">
      <?php foreach ($FLAGS as $flag => $fl): ?>
        <label class="f-check" style="font-weight:500"><input type="checkbox" name="<?= e($flag) ?>" <?= $r[$flag] ? 'checked' : '' ?>> <?= e($fl) ?></label>
      <?php endforeach; ?>
      <label class="f-check" style="font-weight:500"><input type="checkbox" name="is_active" <?= $r['is_active'] ? 'checked' : '' ?>> <?= e(t('a_f_active', 'چالاک')) ?></label>
      <div class="grow"></div>
      <button class="btn btn-gold btn-sm" type="submit">💾 <?= e(t('a_save', 'پاشەکەوتکردن')) ?></button>
      <button class="btn btn-danger btn-sm" type="submit" name="act" value="delete" formnovalidate
              onclick="return confirm('<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟')) ?>')">🗑</button>
    </div>
  </form>
<?php endforeach; ?>

<?php admin_footer(); ?>
