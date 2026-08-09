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

    /** Normalise + validate a URL. '' stays '', bad URLs return null. */
    $cleanUrl = function (string $url): ?string {
        $url = mb_substr(trim($url), 0, 500);
        if ($url === '') return '';
        // bare domain → assume https
        if (preg_match('~^[\w][\w.-]*\.[a-z]{2,}([/?#]|$)~iu', $url)) $url = 'https://' . $url;
        // scheme allow-list: blocks javascript:, data:, vbscript:, …
        if (!preg_match('~^(https?://|tel:|mailto:|viber://)~i', $url) || preg_match('/[\s<>"\'\\\\]/u', $url)) return null;
        return $url;
    };

    if ($act === 'add') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 80);
        $platform = (string)($_POST['platform'] ?? 'link');
        if (!isset($PLATFORMS[$platform])) $platform = 'link';
        $url = $cleanUrl((string)($_POST['url'] ?? ''));

        if ($url === null) {
            flash('error', t('a_err_bad_url', 'بەستەرەکە دروست نییە — تەنیا https:// یان tel: یان mailto: ڕێگەپێدراوە'));
        } elseif ($name === '') {
            flash('error', t('a_err_required', 'ناو پێویستە'));
        } else {
            $vals = [];
            foreach ($FLAGS as $flag => $_) $vals[$flag] = isset($_POST[$flag]) ? 1 : 0;
            $st = db()->prepare('INSERT INTO social_links (name, platform, url, sort_order, is_active, show_header, show_footer, show_contact, show_floating) VALUES (?,?,?,?,?,?,?,?,?)');
            $st->execute([$name, $platform, $url, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, $vals['show_header'], $vals['show_footer'], $vals['show_contact'], $vals['show_floating']]);
            log_activity('create', 'social', (int)db()->lastInsertId(), $name);
            flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
        }
    }

    if ($act === 'bulk') {
        // one form saves every row together; the 🗑 button also removes its row after saving the rest
        $delId = isset($_POST['del']) ? (int)$_POST['del'] : 0;
        $names = (array)($_POST['name'] ?? []);
        $bad = 0;
        // only touch rows that actually exist — ignore tampered/unknown ids
        $validIds = array_flip(array_map('intval', db()->query('SELECT id FROM social_links')->fetchAll(PDO::FETCH_COLUMN)));
        $st = db()->prepare('UPDATE social_links SET name=?, platform=?, url=?, sort_order=?, is_active=?, show_header=?, show_footer=?, show_contact=?, show_floating=? WHERE id=?');
        db()->beginTransaction();
        try {
            foreach ($names as $id => $nm) {
                $id = (int)$id;
                if ($id <= 0 || $id === $delId || !isset($validIds[$id])) continue;
                $name = mb_substr(trim((string)$nm), 0, 80);
                $platform = (string)($_POST['platform'][$id] ?? 'link');
                if (!isset($PLATFORMS[$platform])) $platform = 'link';
                $url = $cleanUrl((string)($_POST['url'][$id] ?? ''));
                if ($name === '' || $url === null) { $bad++; continue; }
                $st->execute([
                    $name, $platform, $url,
                    (int)($_POST['sort_order'][$id] ?? 0),
                    isset($_POST['is_active'][$id]) ? 1 : 0,
                    isset($_POST['show_header'][$id]) ? 1 : 0,
                    isset($_POST['show_footer'][$id]) ? 1 : 0,
                    isset($_POST['show_contact'][$id]) ? 1 : 0,
                    isset($_POST['show_floating'][$id]) ? 1 : 0,
                    $id,
                ]);
            }
            if ($delId > 0 && isset($validIds[$delId])) db()->prepare('DELETE FROM social_links WHERE id = ?')->execute([$delId]);
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
        log_activity('update', 'social', $delId, $delId ? 'bulk+delete' : 'bulk');
        if ($bad > 0) flash('error', t('a_so_bulk_bad', 'هەندێک ڕیز پاشەکەوت نەکرا — ناو بەتاڵ بوو یان لینکەکە هەڵە بوو'));
        flash('success', $delId ? t('a_deleted', 'سڕایەوە') : t('a_saved', 'پاشەکەوت کرا ✓'));
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

<?php if ($rows): ?>
<form method="post" id="socialBulk">
  <?= csrf_field() ?>
  <input type="hidden" name="act" value="bulk">
  <?php /* default submit for the Enter key — otherwise Enter would hit the first 🗑 button */ ?>
  <button type="submit" style="display:none" aria-hidden="true" tabindex="-1"></button>

  <?php foreach ($rows as $r): $id = (int)$r['id']; ?>
    <div class="panel" style="margin-bottom:12px">
      <div class="f-grid" style="grid-template-columns:1.4fr 1fr 2fr .6fr;align-items:end">
        <div class="f-row"><label><?= e(t('a_f_name', 'ناو')) ?></label><input type="text" name="name[<?= $id ?>]" value="<?= e($r['name']) ?>" maxlength="80"></div>
        <div class="f-row"><label><?= e(t('a_so_platform', 'پلاتفۆرم')) ?></label>
          <select name="platform[<?= $id ?>]"><?php foreach ($PLATFORMS as $k => $l): ?><option value="<?= e($k) ?>" <?= $r['platform'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
        </div>
        <div class="f-row"><label><?= e(t('a_so_url', 'لینک')) ?></label><input type="text" name="url[<?= $id ?>]" value="<?= e($r['url']) ?>" dir="ltr" maxlength="500"></div>
        <div class="f-row"><label><?= e(t('a_f_sort', 'ڕیز')) ?></label><input type="number" name="sort_order[<?= $id ?>]" value="<?= (int)$r['sort_order'] ?>"></div>
      </div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;margin:10px 0;align-items:center">
        <?php foreach ($FLAGS as $flag => $fl): ?>
          <label class="f-check" style="font-weight:500"><input type="checkbox" name="<?= e($flag) ?>[<?= $id ?>]" <?= $r[$flag] ? 'checked' : '' ?>> <?= e($fl) ?></label>
        <?php endforeach; ?>
        <label class="f-check" style="font-weight:500"><input type="checkbox" name="is_active[<?= $id ?>]" <?= $r['is_active'] ? 'checked' : '' ?>> <?= e(t('a_f_active', 'چالاک')) ?></label>
        <div class="grow"></div>
        <button class="btn btn-danger btn-sm" type="submit" name="del" value="<?= $id ?>" formnovalidate
                onclick="return confirm('<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟')) ?>')">🗑</button>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="bulk-bar">
    <span>💡 <?= e(t('a_so_bulk_hint', 'گۆڕانکاری لە چەند ڕیزێک بکە — هەمووی پێکەوە پاشەکەوت دەبن')) ?></span>
    <div class="grow"></div>
    <button class="btn btn-gold" type="submit">💾 <?= e(t('a_save_all', 'پاشەکەوتکردنی هەموو گۆڕانکارییەکان')) ?></button>
  </div>
</form>
<?php endif; ?>

<?php admin_footer(); ?>
