<?php
/** Shade manager — rows per palette, auto QR/barcode. */
require __DIR__ . '/includes/admin-bootstrap.php';
require_once APP_ROOT . '/includes/codes.php';

$palettes = db()->query('SELECT id, name, code FROM palettes ORDER BY sort_order, id')->fetchAll();
if (!$palettes) {
    admin_header(t('a_shades', 'ڕەنگەکان'), 'shades');
    echo '<div class="panel tac"><p class="muted">' . e(t('a_sh_no_palettes', 'سەرەتا پاڵێتێک دروست بکە')) . '</p>'
       . '<a class="btn btn-gold" href="' . e(admin_url('crud.php?e=palettes&action=edit')) . '">＋ ' . e(t('a_new', 'زیادکردن')) . '</a></div>';
    admin_footer();
    exit;
}

$pid = (int)($_GET['palette'] ?? $_POST['palette'] ?? $palettes[0]['id']);
$pal = null;
foreach ($palettes as $p) if ((int)$p['id'] === $pid) $pal = $p;
if (!$pal) { $pal = $palettes[0]; $pid = (int)$pal['id']; }

function shade_slug_unique(string $slug, ?int $excludeId = null): string
{
    $base = $slug !== '' ? $slug : 'shade';
    $try = $base; $n = 2;
    while (true) {
        $st = db()->prepare('SELECT id FROM palette_shades WHERE slug = ?' . ($excludeId ? ' AND id <> ' . (int)$excludeId : '') . ' LIMIT 1');
        $st->execute([$try]);
        if (!$st->fetch()) return $try;
        $try = $base . '-' . $n++;
    }
}

function shade_codes(int $id): void
{
    try { ensure_shade_codes($id); }
    catch (Throwable $ex) { flash('error', t('a_warn_codes', 'پاشەکەوت کرا، بەڵام دروستکردنی QR/بارکۆد سەرکەوتوو نەبوو') . ' (' . $ex->getMessage() . ')'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'add') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        $hex = strtoupper(trim((string)($_POST['hex_color'] ?? '#CCCCCC')));
        if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) $hex = '#CCCCCC';
        if ($name === '') {
            flash('error', t('a_err_required', 'ناو پێویستە'));
        } else {
            $pos = (int)($_POST['position'] ?? 0);
            $slug = shade_slug_unique(slugify($name));
            $code = next_code('palette_shades', 'code', 'KD-S');
            $st = db()->prepare('INSERT INTO palette_shades (palette_id, name, slug, code, hex_color, position, notes, is_active) VALUES (?,?,?,?,?,?,?,1)');
            $st->execute([$pid, $name, $slug, $code, $hex, $pos, trim((string)($_POST['notes'] ?? '')) ?: null]);
            $sid = (int)db()->lastInsertId();
            shade_codes($sid);
            log_activity('create', 'shade', $sid, $name . ' ' . $code);
            flash('success', t('a_saved', 'پاشەکەوت کرا ✓') . ' — ' . $code);
        }
        redirect(admin_url('shades.php?palette=' . $pid));
    }

    if ($act === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT * FROM palette_shades WHERE id = ? AND palette_id = ?');
        $st->execute([$id, $pid]);
        if ($sh = $st->fetch()) {
            $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150) ?: $sh['name'];
            $hex = strtoupper(trim((string)($_POST['hex_color'] ?? '')));
            if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) $hex = $sh['hex_color'];
            $st = db()->prepare('UPDATE palette_shades SET name = ?, hex_color = ?, position = ?, is_active = ? WHERE id = ?');
            $st->execute([$name, $hex, (int)($_POST['position'] ?? $sh['position']), isset($_POST['is_active']) ? 1 : 0, $id]);
            shade_codes($id);
            log_activity('update', 'shade', $id, $name);
            flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
        }
        redirect(admin_url('shades.php?palette=' . $pid));
    }

    if ($act === 'regen') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT * FROM palette_shades WHERE id = ? AND palette_id = ?');
        $st->execute([$id, $pid]);
        if ($sh = $st->fetch()) {
            try {
                $qr = generate_qr($sh['code']);
                $bc = generate_barcode($sh['code']);
                db()->prepare('UPDATE palette_shades SET qr_path = ?, barcode_path = ? WHERE id = ?')->execute([$qr, $bc, $id]);
                log_activity('regen_codes', 'shade', $id, $sh['code']);
                flash('success', t('a_sh_regen_ok', 'QR و بارکۆد نوێکرانەوە ✓'));
            } catch (Throwable $ex) {
                flash('error', t('a_warn_codes', 'دروستکردنی QR/بارکۆد سەرکەوتوو نەبوو') . ' (' . $ex->getMessage() . ')');
            }
        }
        redirect(admin_url('shades.php?palette=' . $pid));
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT * FROM palette_shades WHERE id = ? AND palette_id = ?');
        $st->execute([$id, $pid]);
        if ($sh = $st->fetch()) {
            foreach ([$sh['qr_path'], $sh['barcode_path']] as $rel) {
                if ($rel && ($abs = upload_abs_path($rel))) @unlink($abs);
            }
            db()->prepare('DELETE FROM palette_shades WHERE id = ?')->execute([$id]);
            log_activity('delete', 'shade', $id, $sh['name'] . ' ' . $sh['code']);
            flash('success', t('a_deleted', 'سڕایەوە'));
        }
        redirect(admin_url('shades.php?palette=' . $pid));
    }
}

$st = db()->prepare('SELECT * FROM palette_shades WHERE palette_id = ? ORDER BY position, id');
$st->execute([$pid]);
$shades = $st->fetchAll();
$nextPos = $shades ? ((int)end($shades)['position'] + 10) : 10;

admin_header(t('a_shades', 'ڕەنگەکان') . ' — ' . $pal['name'], 'shades');
?>

<div class="toolbar">
  <form method="get">
    <select name="palette" onchange="this.form.submit()">
      <?php foreach ($palettes as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $pid ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['code']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </form>
  <div class="grow"></div>
  <a class="btn btn-ghost" href="<?= e(admin_url('crud.php?e=palettes&action=edit&id=' . $pid)) ?>">✏️ <?= e(t('a_sh_edit_palette', 'دەستکاری پاڵێت')) ?></a>
  <a class="btn btn-ghost" href="<?= e(admin_url('labels.php?palette=' . $pid)) ?>">🏷 <?= e(t('a_labels', 'لەیبڵەکان')) ?></a>
</div>

<div class="panel">
  <h2 class="panel-title"><?= e(t('a_sh_add', 'زیادکردنی ڕەنگی نوێ')) ?></h2>
  <form method="post" class="shade-form">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="add">
    <input type="hidden" name="palette" value="<?= $pid ?>">
    <div class="f-grid" style="grid-template-columns:2fr 1fr 1fr 2fr auto;align-items:end">
      <div class="f-row"><label><?= e(t('a_f_name', 'ناو')) ?> *</label><input type="text" name="name" required maxlength="150"></div>
      <div class="f-row"><label><?= e(t('a_f_hex', 'ڕەنگ')) ?></label>
        <div class="hex-pair" data-hex-pair><input type="color" value="#A9805B"><input type="text" name="hex_color" value="#A9805B" dir="ltr" maxlength="7"></div>
      </div>
      <div class="f-row"><label><?= e(t('a_f_position', 'ڕیز (تاریک→ڕوون)')) ?></label><input type="number" name="position" value="<?= $nextPos ?>"></div>
      <div class="f-row"><label><?= e(t('a_f_notes', 'تێبینی')) ?></label><input type="text" name="notes" maxlength="255"></div>
      <div class="f-row"><button class="btn btn-gold" type="submit">＋ <?= e(t('a_new', 'زیادکردن')) ?></button></div>
    </div>
  </form>
  <div class="f-hint"><?= e(t('a_sh_hint', 'کۆد و QR و بارکۆد خۆکارانە دروستدەبن. ژمارەی ڕیز: بچووکترین = تاریکترین')) ?></div>
</div>

<?php if (!$shades): ?>
  <div class="panel tac muted"><?= e(t('no_items', 'هیچ نییە')) ?></div>
<?php else: ?>
  <div class="panel" style="padding:0;overflow:hidden">
    <?php foreach ($shades as $sh): ?>
      <form method="post" class="shade-row">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="update">
        <input type="hidden" name="palette" value="<?= $pid ?>">
        <input type="hidden" name="id" value="<?= (int)$sh['id'] ?>">
        <span class="sw" style="background:<?= e($sh['hex_color']) ?>"></span>
        <input type="text" name="name" value="<?= e($sh['name']) ?>" maxlength="150">
        <div class="hex-pair" data-hex-pair>
          <input type="color" value="<?= e($sh['hex_color']) ?>">
          <input type="text" name="hex_color" value="<?= e($sh['hex_color']) ?>" dir="ltr" maxlength="7">
        </div>
        <input type="number" name="position" value="<?= (int)$sh['position'] ?>" title="<?= e(t('a_f_position', 'ڕیز')) ?>">
        <span class="code-pill" dir="ltr"><?= e($sh['code']) ?></span>
        <label class="f-check" style="font-size:.75rem"><input type="checkbox" name="is_active" <?= $sh['is_active'] ? 'checked' : '' ?>> <?= e(t('a_f_active', 'چالاک')) ?></label>
        <div class="row-actions">
          <button class="btn btn-gold btn-xs" type="submit">💾</button>
          <?php if ($sh['qr_path']): ?><a class="btn btn-ghost btn-xs" href="<?= e(upload_url($sh['qr_path'])) ?>" target="_blank" title="QR">▣</a><?php endif; ?>
          <?php if ($sh['barcode_path']): ?><a class="btn btn-ghost btn-xs" href="<?= e(upload_url($sh['barcode_path'])) ?>" target="_blank" title="Barcode">|||</a><?php endif; ?>
          <button class="btn btn-ghost btn-xs" type="submit" name="act" value="regen" title="<?= e(t('a_sh_regen', 'نوێکردنەوەی QR/بارکۆد')) ?>">🔄</button>
          <a class="btn btn-ghost btn-xs" href="<?= e(url('shade/' . rawurlencode($sh['slug']))) ?>" target="_blank" title="<?= e(t('a_view', 'بینین')) ?>">👁</a>
          <button class="btn btn-danger btn-xs" type="submit" name="act" value="delete"
                  formnovalidate onclick="return confirm('<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟')) ?>')">🗑</button>
        </div>
      </form>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php admin_footer(); ?>
