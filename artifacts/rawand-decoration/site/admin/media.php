<?php
require __DIR__ . '/includes/admin-bootstrap.php';
require_once APP_ROOT . '/includes/uploads.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'upload' && !empty($_FILES['files']['name'][0])) {
        $files = $_FILES['files'];
        $ok = 0;
        for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $one = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
            try {
                handle_upload($one, ['subdir' => 'media']);
                $ok++;
            } catch (KDUploadException $ex) {
                flash('error', $files['name'][$i] . ': ' . $ex->getMessage());
            }
        }
        if ($ok) flash('success', t('a_m_uploaded', 'وێنە بارکرا') . ' (' . knum($ok) . ')');
        log_activity('upload', 'media', null, $ok . ' files');
        redirect(admin_url('media.php'));
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT * FROM media WHERE id = ?');
        $st->execute([$id]);
        if ($m = $st->fetch()) {
            foreach ([$m['path'], $m['thumb_path']] as $rel) {
                if ($rel && ($abs = upload_abs_path($rel))) @unlink($abs);
            }
            db()->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
            log_activity('delete', 'media', $id, $m['filename']);
            flash('success', t('a_deleted', 'سڕایەوە'));
        }
        redirect(admin_url('media.php'));
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$where = '1';
$args = [];
if ($q !== '') { $where = '(filename LIKE ? OR alt_text LIKE ?)'; $args = ['%' . $q . '%', '%' . $q . '%']; }

$stc = db()->prepare("SELECT COUNT(*) FROM media WHERE $where");
$stc->execute($args);
$pg = paginate((int)$stc->fetchColumn(), 32, (int)($_GET['page'] ?? 1));

$st = db()->prepare("SELECT * FROM media WHERE $where ORDER BY id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($args);
$items = $st->fetchAll();

admin_header(t('a_media', 'میدیا'), 'media');
?>

<div class="panel">
  <h2 class="panel-title"><?= e(t('a_m_upload', 'بارکردنی وێنەی نوێ')) ?></h2>
  <form method="post" enctype="multipart/form-data" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="upload">
    <input type="file" name="files[]" accept="image/*" multiple required style="flex:1;min-width:220px">
    <button class="btn btn-gold" type="submit"><?= e(t('a_m_upload_btn', 'بارکردن')) ?></button>
  </form>
  <div class="f-hint" style="margin-top:8px"><?= e(t('a_m_hint', 'JPG، PNG، WebP، GIF — گەورەترین قەبارە 15MB')) ?></div>
</div>

<div class="toolbar">
  <form method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('a_search', 'گەڕان…')) ?>">
    <button class="btn btn-ghost" type="submit"><?= e(t('btn_search', 'گەڕان')) ?></button>
  </form>
</div>

<?php if ($items): ?>
<div class="media-grid">
  <?php foreach ($items as $m):
      $src = $m['thumb_path'] ?: $m['path']; ?>
    <div class="media-item">
      <a href="<?= e(upload_url($m['path'])) ?>" target="_blank"><img src="<?= e(upload_url($src)) ?>" alt="<?= e((string)$m['alt_text']) ?>" loading="lazy"></a>
      <div class="mi-body">
        <div class="mi-name" title="<?= e($m['filename']) ?>"><?= e($m['filename']) ?></div>
        <div class="mi-meta"><?= e(knum((int)$m['width'])) ?>×<?= e(knum((int)$m['height'])) ?> · <?= e(knum((int)round($m['size_bytes'] / 1024))) ?>KB</div>
      </div>
      <div class="mi-actions">
        <button class="btn btn-ghost btn-xs" type="button" data-copy="<?= e(upload_url($m['path'])) ?>">📋</button>
        <form method="post" data-confirm="<?= e(t('a_m_confirm', 'سڕینەوەی وێنەکە؟ ئەگەر لە شوێنێک بەکارهاتبێت، وون دەبێت!')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="act" value="delete">
          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
          <button class="btn btn-danger btn-xs" type="submit">🗑</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?= render_pagination($pg, admin_url('media.php' . ($q !== '' ? '?q=' . rawurlencode($q) : ''))) ?>
<?php else: ?>
  <div class="panel tac muted"><?= e(t('no_items', 'هیچ نییە')) ?></div>
<?php endif; ?>

<?php admin_footer(); ?>
