<?php
/** Generic CRUD engine — driven by entities.php config. */
require __DIR__ . '/includes/admin-bootstrap.php';
require_once APP_ROOT . '/includes/codes.php';
require __DIR__ . '/entities.php';

$ENT = kd_entities();
$eKey = (string)($_GET['e'] ?? '');
if (!isset($ENT[$eKey])) {
    http_response_code(404);
    exit('Unknown entity');
}
$cfg = $ENT[$eKey];
$table = $cfg['table'];
$labelCol = $cfg['label_col'];
$action = (string)($_POST['action'] ?? $_GET['action'] ?? 'list');

/* ── helpers ─────────────────────────────────────────────── */

function crud_fetch(string $table, int $id): ?array
{
    $st = db()->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function crud_slug_unique(string $table, string $slug, ?int $excludeId): string
{
    $base = $slug !== '' ? $slug : 'item';
    $try = $base;
    $n = 2;
    while (true) {
        $st = db()->prepare("SELECT id FROM `$table` WHERE slug = ?" . ($excludeId ? ' AND id <> ' . (int)$excludeId : '') . ' LIMIT 1');
        $st->execute([$try]);
        if (!$st->fetch()) return $try;
        $try = $base . '-' . $n++;
    }
}

/** Build $data from POST according to field config. Throws KDUploadException. */
function crud_collect(array $cfg, ?array $row, ?int $id): array
{
    $data = [];
    foreach ($cfg['fields'] as $col => $f) {
        switch ($f['type']) {
            case 'text':
                $v = trim((string)($_POST[$col] ?? ''));
                if (isset($f['max'])) $v = mb_substr($v, 0, $f['max']);
                if ($v === '' && !empty($f['required'])) {
                    throw new KDUploadException($f['label'] . ' — ' . t('a_err_required', 'ئەم خانەیە پێویستە'));
                }
                $data[$col] = $v === '' ? null : $v;
                break;

            case 'textarea':
                $v = trim((string)($_POST[$col] ?? ''));
                if (isset($f['max'])) $v = mb_substr($v, 0, $f['max']);
                if ($v === '' && !empty($f['required'])) {
                    throw new KDUploadException($f['label'] . ' — ' . t('a_err_required', 'ئەم خانەیە پێویستە'));
                }
                $data[$col] = $v === '' ? null : $v;
                break;

            case 'rich':
                $v = trim((string)($_POST[$col] ?? ''));
                $data[$col] = $v === '' ? null : kd_purify($v);
                break;

            case 'number':
                $v = trim((string)($_POST[$col] ?? ''));
                if ($v === '') {
                    $data[$col] = !empty($f['nullable']) ? null : (int)($f['default'] ?? 0);
                } else {
                    $data[$col] = (int)$v;
                    if (isset($f['min']) && $data[$col] < $f['min']) $data[$col] = (int)$f['min'];
                }
                break;

            case 'select':
                $v = (string)($_POST[$col] ?? '');
                $data[$col] = array_key_exists($v, $f['options']) ? $v : (string)($f['default'] ?? array_key_first($f['options']));
                break;

            case 'select_query':
                $v = trim((string)($_POST[$col] ?? ''));
                $data[$col] = ($v === '' || $v === '0') ? null : (int)$v;
                break;

            case 'date':
            case 'datetime':
                $v = trim((string)($_POST[$col] ?? ''));
                if ($v === '') { $data[$col] = null; break; }
                $v = str_replace('T', ' ', $v);
                if ($f['type'] === 'datetime' && strlen($v) === 16) $v .= ':00';
                $data[$col] = $v;
                break;

            case 'check':
                $data[$col] = isset($_POST[$col]) ? 1 : 0;
                break;

            case 'slug':
                $v = trim((string)($_POST[$col] ?? ''));
                if ($v === '') {
                    $srcCol = $f['from'];
                    $v = (string)($_POST[$srcCol] ?? ($row[$srcCol] ?? ''));
                }
                $v = slugify($v);
                if (isset($f['max'])) $v = mb_substr($v, 0, $f['max']);
                $data[$col] = crud_slug_unique($cfg['table'], $v, $id);
                break;

            case 'code':
                if ($row && !empty($row[$col])) {
                    // keep existing code
                } elseif (!empty($f['prefix'])) {
                    $data[$col] = next_code($cfg['table'], $col, $f['prefix']);
                }
                break;

            case 'image':
                $del = isset($_POST['del_' . $col]);
                $file = $_FILES['file_' . $col] ?? null;
                if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $alt = (string)($_POST[$cfg['label_col']] ?? '');
                    $data[$col] = handle_upload($file, ['subdir' => $f['subdir'] ?? 'media', 'alt' => $alt]);
                } elseif ($del) {
                    $data[$col] = null;
                } elseif (!$row && !empty($f['required'])) {
                    throw new KDUploadException($f['label'] . ' — ' . t('a_err_required', 'ئەم خانەیە پێویستە'));
                }
                break;
        }
    }
    return $data;
}

/* ── actions ─────────────────────────────────────────────── */

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    require_once APP_ROOT . '/includes/uploads.php';
    $id = (int)($_POST['id'] ?? 0) ?: null;
    $row = $id ? crud_fetch($table, $id) : null;
    if ($id && !$row) { flash('error', t('a_err_notfound', 'تۆمارەکە نەدۆزرایەوە')); redirect(admin_url("crud.php?e=$eKey")); }

    try {
        $data = crud_collect($cfg, $row, $id);
    } catch (KDUploadException $ex) {
        flash('error', $ex->getMessage());
        redirect(admin_url("crud.php?e=$eKey&action=edit" . ($id ? "&id=$id" : '')));
    }

    if (isset($cfg['before_save'])) {
        $cfg['before_save']($data, $id);
    }

    if ($data) {
        if ($id) {
            $sets = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
            $st = db()->prepare("UPDATE `$table` SET $sets WHERE id = ?");
            $st->execute([...array_values($data), $id]);
        } else {
            $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
            $qs = implode(', ', array_fill(0, count($data), '?'));
            $st = db()->prepare("INSERT INTO `$table` ($cols) VALUES ($qs)");
            $st->execute(array_values($data));
            $id = (int)db()->lastInsertId();
        }
    }

    // gallery
    if (!empty($cfg['gallery']) && $id) {
        $g = $cfg['gallery'];
        foreach ((array)($_POST['gallery_del'] ?? []) as $gid) {
            $st = db()->prepare("DELETE FROM `{$g['table']}` WHERE id = ? AND `{$g['fk']}` = ?");
            $st->execute([(int)$gid, $id]);
        }
        if (!empty($g['caption_col'])) {
            foreach ((array)($_POST['gallery_caption'] ?? []) as $gid => $cap) {
                $st = db()->prepare("UPDATE `{$g['table']}` SET `{$g['caption_col']}` = ? WHERE id = ? AND `{$g['fk']}` = ?");
                $st->execute([mb_substr(trim((string)$cap), 0, 255) ?: null, (int)$gid, $id]);
            }
        }
        if (!empty($_FILES['gallery_new']['name'][0])) {
            $files = $_FILES['gallery_new'];
            $n = count($files['name']);
            for ($i = 0; $i < $n; $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                $one = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                try {
                    $rel = handle_upload($one, ['subdir' => $g['subdir'] ?? 'media']);
                    $st = db()->prepare("INSERT INTO `{$g['table']}` (`{$g['fk']}`, `{$g['image_col']}`) VALUES (?, ?)");
                    $st->execute([$id, $rel]);
                } catch (KDUploadException $ex) {
                    flash('error', $files['name'][$i] . ': ' . $ex->getMessage());
                }
            }
        }
    }

    // many-to-many
    foreach ((array)($cfg['m2m'] ?? []) as $mk => $m) {
        $st = db()->prepare("DELETE FROM `{$m['table']}` WHERE `{$m['own_col']}` = ?");
        $st->execute([$id]);
        foreach ((array)($_POST['m2m_' . $mk] ?? []) as $oid) {
            $st = db()->prepare("INSERT INTO `{$m['table']}` (`{$m['own_col']}`, `{$m['other_col']}`) VALUES (?, ?)");
            $st->execute([$id, (int)$oid]);
        }
    }

    // after-save hook (e.g. QR/barcode generation)
    if (!empty($cfg['after_save'])) {
        try {
            ($cfg['after_save'])($id);
        } catch (Throwable $ex) {
            flash('error', t('a_warn_codes', 'تۆمارەکە پاشەکەوت کرا، بەڵام دروستکردنی QR/بارکۆد سەرکەوتوو نەبوو') . ' (' . $ex->getMessage() . ')');
        }
    }

    log_activity($row ? 'update' : 'create', $eKey, $id, (string)($data[$labelCol] ?? $row[$labelCol] ?? ''));
    flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
    redirect(admin_url("crud.php?e=$eKey"));
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $row = crud_fetch($table, $id);
    if ($row) {
        $st = db()->prepare("DELETE FROM `$table` WHERE id = ?");
        $st->execute([$id]);
        log_activity('delete', $eKey, $id, (string)($row[$labelCol] ?? ''));
        flash('success', t('a_deleted', 'سڕایەوە'));
    }
    redirect(admin_url("crud.php?e=$eKey"));
}

/* ── edit form ───────────────────────────────────────────── */

if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0) ?: null;
    $row = $id ? crud_fetch($table, $id) : null;
    if ($id && !$row) { flash('error', t('a_err_notfound', 'تۆمارەکە نەدۆزرایەوە')); redirect(admin_url("crud.php?e=$eKey")); }

    $hasRich = in_array('rich', array_column($cfg['fields'], 'type'), true);
    $title = ($row ? t('a_edit', 'دەستکاری') : t('a_new', 'زیادکردن')) . ' — ' . $cfg['title'];
    admin_header($title, $eKey);

    if ($hasRich): ?>
      <link rel="stylesheet" href="<?= e(asset('css/vendor/quill.snow.css')) ?>">
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="panel" style="max-width:980px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

      <div class="f-grid">
        <?php foreach ($cfg['fields'] as $col => $f):
            $val = $row[$col] ?? ($f['default'] ?? '');
            $fid = 'f_' . $col;
            $full = in_array($f['type'], ['textarea', 'rich'], true);
        ?>
          <div class="f-row <?= $full ? 'full' : '' ?>">
            <?php if ($f['type'] === 'check'): ?>
              <label class="f-check" for="<?= $fid ?>">
                <input type="checkbox" id="<?= $fid ?>" name="<?= e($col) ?>" value="1" <?= !empty($val) ? 'checked' : '' ?>>
                <?= e($f['label']) ?>
              </label>

            <?php elseif ($f['type'] === 'code'): ?>
              <label><?= e($f['label']) ?></label>
              <?php if (!empty($val)): ?>
                <span class="code-pill" dir="ltr" style="font-size:.9rem"><?= e($val) ?></span>
              <?php else: ?>
                <span class="muted" style="font-size:.8rem"><?= e(t('a_code_auto', 'خۆکارانە دروستدەبێت لە کاتی پاشەکەوتکردن')) ?></span>
              <?php endif; ?>

            <?php elseif ($f['type'] === 'image'): ?>
              <label><?= e($f['label']) ?> <?= !empty($f['required']) ? '*' : '' ?></label>
              <?php if (!empty($val)): ?>
                <img class="img-prev" src="<?= e(upload_url($val)) ?>" alt="">
                <label class="f-check" style="margin-bottom:8px">
                  <input type="checkbox" name="del_<?= e($col) ?>" value="1"> <?= e(t('a_remove_image', 'وێنەکە لاببە')) ?>
                </label>
              <?php else: ?>
                <img class="img-prev" id="prev_<?= $fid ?>" style="display:none" alt="">
              <?php endif; ?>
              <input type="file" name="file_<?= e($col) ?>" accept="image/*" <?= empty($val) ? 'data-preview="#prev_' . $fid . '"' : '' ?>>
              <?php if (!empty($f['hint'])): ?><div class="f-hint"><?= e($f['hint']) ?></div><?php endif; ?>

            <?php elseif ($f['type'] === 'textarea'): ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?> <?= !empty($f['required']) ? '*' : '' ?></label>
              <textarea id="<?= $fid ?>" name="<?= e($col) ?>" rows="<?= (int)($f['rows'] ?? 4) ?>" <?= isset($f['max']) ? 'maxlength="' . (int)$f['max'] . '"' : '' ?>><?= e((string)$val) ?></textarea>
              <?php if (!empty($f['hint'])): ?><div class="f-hint"><?= e($f['hint']) ?></div><?php endif; ?>

            <?php elseif ($f['type'] === 'rich'): ?>
              <label><?= e($f['label']) ?></label>
              <textarea id="<?= $fid ?>" name="<?= e($col) ?>" class="rich-source" rows="10"><?= e((string)$val) ?></textarea>
              <div class="rich-editor" data-input="#<?= $fid ?>" style="background:#fff;min-height:220px"></div>

            <?php elseif ($f['type'] === 'select'): ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?></label>
              <select id="<?= $fid ?>" name="<?= e($col) ?>">
                <?php foreach ($f['options'] as $ov => $ol): ?>
                  <option value="<?= e((string)$ov) ?>" <?= (string)$val === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option>
                <?php endforeach; ?>
              </select>

            <?php elseif ($f['type'] === 'select_query'): ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?></label>
              <select id="<?= $fid ?>" name="<?= e($col) ?>">
                <option value=""><?= e(t('a_none', '— هیچ —')) ?></option>
                <?php foreach (db()->query($f['options_sql']) as $opt): ?>
                  <option value="<?= (int)$opt['id'] ?>" <?= (string)$val === (string)$opt['id'] ? 'selected' : '' ?>><?= e($opt['name']) ?></option>
                <?php endforeach; ?>
              </select>

            <?php elseif ($f['type'] === 'slug'): ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?></label>
              <input type="text" id="<?= $fid ?>" name="<?= e($col) ?>" value="<?= e((string)$val) ?>" dir="ltr" data-slug-source="#f_<?= e($f['from']) ?>">
              <div class="f-hint"><?= e(t('a_slug_hint', 'بەتاڵی بهێڵەوە بۆ دروستکردنی خۆکارانە')) ?></div>

            <?php elseif ($f['type'] === 'date'): ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?></label>
              <input type="date" id="<?= $fid ?>" name="<?= e($col) ?>" value="<?= e((string)($val ? substr((string)$val, 0, 10) : '')) ?>">

            <?php elseif ($f['type'] === 'datetime'): ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?></label>
              <input type="datetime-local" id="<?= $fid ?>" name="<?= e($col) ?>" value="<?= e($val ? str_replace(' ', 'T', substr((string)$val, 0, 16)) : '') ?>">
              <?php if (!empty($f['hint'])): ?><div class="f-hint"><?= e($f['hint']) ?></div><?php endif; ?>

            <?php else: /* text / number */ ?>
              <label for="<?= $fid ?>"><?= e($f['label']) ?> <?= !empty($f['required']) ? '*' : '' ?></label>
              <input type="<?= $f['type'] === 'number' ? 'number' : 'text' ?>" id="<?= $fid ?>" name="<?= e($col) ?>"
                     value="<?= e((string)$val) ?>"
                     <?= isset($f['max']) ? 'maxlength="' . (int)$f['max'] . '"' : '' ?>
                     <?= isset($f['min']) ? 'min="' . (int)$f['min'] . '"' : '' ?>
                     <?= isset($f['step']) ? 'step="' . (int)$f['step'] . '"' : '' ?>
                     <?= !empty($f['dir']) ? 'dir="' . e($f['dir']) . '"' : '' ?>
                     <?= !empty($f['required']) ? 'required' : '' ?>>
              <?php if (!empty($f['hint'])): ?><div class="f-hint"><?= e($f['hint']) ?></div><?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php foreach ((array)($cfg['m2m'] ?? []) as $mk => $m):
            $selected = [];
            if ($id) {
                $st = db()->prepare("SELECT `{$m['other_col']}` FROM `{$m['table']}` WHERE `{$m['own_col']}` = ?");
                $st->execute([$id]);
                $selected = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            }
        ?>
          <div class="f-row full">
            <label><?= e($m['label']) ?></label>
            <div style="display:flex;flex-wrap:wrap;gap:14px">
              <?php foreach (db()->query($m['options_sql']) as $opt): ?>
                <label class="f-check" style="font-weight:500">
                  <input type="checkbox" name="m2m_<?= e($mk) ?>[]" value="<?= (int)$opt['id'] ?>"
                         <?= in_array((int)$opt['id'], $selected, true) ? 'checked' : '' ?>>
                  <?= e($opt['name']) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (!empty($cfg['gallery'])):
            $g = $cfg['gallery'];
            $gRows = [];
            if ($id) {
                $st = db()->prepare("SELECT * FROM `{$g['table']}` WHERE `{$g['fk']}` = ? ORDER BY sort_order, id");
                $st->execute([$id]);
                $gRows = $st->fetchAll();
            }
        ?>
          <div class="f-row full">
            <label><?= e(t('a_gallery', 'کۆمەڵە وێنە')) ?></label>
            <?php if ($gRows): ?>
              <div class="media-grid" style="margin-bottom:12px">
                <?php foreach ($gRows as $gr): ?>
                  <div class="media-item">
                    <img src="<?= e(thumb_url($gr[$g['image_col']])) ?>" alt="">
                    <div class="mi-body">
                      <?php if (!empty($g['caption_col'])): ?>
                        <input type="text" name="gallery_caption[<?= (int)$gr['id'] ?>]" value="<?= e((string)($gr[$g['caption_col']] ?? '')) ?>"
                               placeholder="<?= e(t('a_caption', 'ژێرنووس')) ?>" style="font-size:.72rem;padding:5px 8px">
                      <?php endif; ?>
                      <label class="f-check" style="font-weight:500;font-size:.72rem;margin-top:6px">
                        <input type="checkbox" name="gallery_del[]" value="<?= (int)$gr['id'] ?>"> <?= e(t('a_delete', 'سڕینەوە')) ?>
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <input type="file" name="gallery_new[]" accept="image/*" multiple>
            <div class="f-hint"><?= e(t('a_gallery_hint', 'دەتوانیت چەند وێنەیەک بە یەکجار هەڵبژێریت')) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <div class="form-foot">
        <button class="btn btn-gold" type="submit"><?= e(t('a_save', 'پاشەکەوتکردن')) ?></button>
        <a class="btn btn-ghost" href="<?= e(admin_url("crud.php?e=$eKey")) ?>"><?= e(t('a_cancel', 'گەڕانەوە')) ?></a>
      </div>
    </form>

    <?php if ($id): ?>
      <form method="post" action="<?= e(admin_url("crud.php?e=$eKey")) ?>" data-confirm="<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟ ناگەڕێتەوە!')) ?>" style="max-width:980px;text-align:end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <button class="btn btn-danger btn-sm" type="submit">🗑 <?= e(t('a_delete', 'سڕینەوە')) ?></button>
      </form>
    <?php endif; ?>

    <?php if ($hasRich): ?><script src="<?= e(asset('js/vendor/quill.js')) ?>"></script><?php endif; ?>
    <?php
    admin_footer();
    exit;
}

/* ── list ────────────────────────────────────────────────── */

$q = trim((string)($_GET['q'] ?? ''));
$where = '1';
$args = [];
if ($q !== '' && !empty($cfg['searchable'])) {
    $parts = [];
    foreach ($cfg['searchable'] as $sc) { $parts[] = "t.`$sc` LIKE ?"; $args[] = '%' . normalize_text($q) . '%'; }
    $where = '(' . implode(' OR ', $parts) . ')';
}

$join = $cfg['list_join'] ?? '';
$select = $cfg['list_select'] ?? 't.*';

$stc = db()->prepare("SELECT COUNT(*) FROM `$table` t $join WHERE $where");
$stc->execute($args);
$pg = paginate((int)$stc->fetchColumn(), 20, (int)($_GET['page'] ?? 1));

$order = $cfg['order'] ?? 'id DESC';
$orderT = implode(', ', array_map(fn($o) => 't.' . trim($o), explode(',', $order)));
$st = db()->prepare("SELECT $select FROM `$table` t $join WHERE $where ORDER BY $orderT LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($args);
$rows = $st->fetchAll();

admin_header($cfg['title'], $eKey);
?>

<div class="toolbar">
  <form method="get" action="<?= e(admin_url('crud.php')) ?>">
    <input type="hidden" name="e" value="<?= e($eKey) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('a_search', 'گەڕان…')) ?>">
    <button class="btn btn-ghost" type="submit"><?= e(t('btn_search', 'گەڕان')) ?></button>
  </form>
  <div class="grow"></div>
  <a class="btn btn-gold" href="<?= e(admin_url("crud.php?e=$eKey&action=edit")) ?>">＋ <?= e(t('a_new', 'زیادکردن')) ?></a>
</div>

<div class="tbl-wrap panel" style="padding:0">
  <table class="tbl">
    <tr>
      <?php foreach ($cfg['list'] as $col): ?><th><?= e($col['label']) ?></th><?php endforeach; ?>
      <th style="width:1%"></th>
    </tr>
    <?php if (!$rows): ?>
      <tr><td colspan="<?= count($cfg['list']) + 1 ?>" class="tac muted" style="padding:30px"><?= e(t('no_items', 'هیچ نییە')) ?></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <?php foreach ($cfg['list'] as $col):
            $v = $r[$col['key']] ?? null; ?>
          <td>
            <?php switch ($col['type']):
                case 'img': ?>
                  <?php if ($v): ?><img class="thumb" src="<?= e(thumb_url($v)) ?>" alt=""><?php else: ?><span class="sw" style="background:var(--beige-soft)"></span><?php endif; ?>
                <?php break;
                case 'bool': ?>
                  <span class="pill <?= $v ? 'pill-on' : 'pill-off' ?>"><?= $v ? '✓' : '✗' ?></span>
                <?php break;
                case 'code': ?>
                  <?php if ($v): ?><span class="code-pill" dir="ltr"><?= e($v) ?></span><?php endif; ?>
                <?php break;
                case 'date': ?>
                  <?= $v ? e(kdate($v)) : '<span class="muted">—</span>' ?>
                <?php break;
                case 'money': ?>
                  <?= $v !== null && $v !== '' ? e(money($v)) : '<span class="muted">' . e(t('product_price_ask', 'پرسیار بکە')) . '</span>' ?>
                <?php break;
                case 'num': ?>
                  <?= e(knum((int)$v)) ?>
                <?php break;
                case 'stars': ?>
                  <span dir="ltr" style="color:var(--accent)"><?= str_repeat('★', max(1, min(5, (int)$v))) ?></span>
                <?php break;
                case 'map': ?>
                  <?= e($col['map'][$v] ?? (string)$v) ?>
                <?php break;
                default: ?>
                  <?php if ($col['key'] === $labelCol): ?>
                    <a href="<?= e(admin_url("crud.php?e=$eKey&action=edit&id=" . (int)$r['id'])) ?>" style="font-weight:700"><?= e((string)$v) ?></a>
                  <?php else: ?>
                    <?= e(excerpt_of((string)$v, 40)) ?>
                  <?php endif; ?>
            <?php endswitch; ?>
          </td>
        <?php endforeach; ?>
        <td>
          <div class="row-actions">
            <?php if (!empty($cfg['row_actions'])): foreach ($cfg['row_actions']($r) as $ra): ?>
              <a class="btn btn-ghost btn-xs" href="<?= e($ra['url']) ?>"><?= e($ra['label']) ?></a>
            <?php endforeach; endif; ?>
            <a class="btn btn-ghost btn-xs" href="<?= e(admin_url("crud.php?e=$eKey&action=edit&id=" . (int)$r['id'])) ?>">✏️ <?= e(t('a_edit', 'دەستکاری')) ?></a>
            <form method="post" data-confirm="<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟ ناگەڕێتەوە!')) ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger btn-xs" type="submit">🗑</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?= render_pagination($pg, admin_url("crud.php?e=$eKey" . ($q !== '' ? '&q=' . rawurlencode($q) : ''))) ?>

<?php admin_footer(); ?>
