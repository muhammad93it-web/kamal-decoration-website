<?php
require __DIR__ . '/includes/admin-bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['act'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($act === 'delete') {
        db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        log_activity('delete', 'message', $id, '');
        flash('success', t('a_deleted', 'سڕایەوە'));
        redirect(admin_url('messages.php'));
    }
    if ($act === 'unread') {
        db()->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$id]);
        redirect(admin_url('messages.php'));
    }
}

$view = (int)($_GET['id'] ?? 0);
$msg = null;
if ($view) {
    $st = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $st->execute([$view]);
    $msg = $st->fetch();
    if ($msg && !$msg['is_read']) {
        db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$view]);
        $msg['is_read'] = 1;
    }
}

$only = (string)($_GET['f'] ?? '');
$where = $only === 'unread' ? 'is_read = 0' : '1';
$stc = db()->query("SELECT COUNT(*) FROM contact_messages WHERE $where");
$pg = paginate((int)$stc->fetchColumn(), 20, (int)($_GET['page'] ?? 1));
$rows = db()->query("SELECT * FROM contact_messages WHERE $where ORDER BY id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}")->fetchAll();

admin_header(t('a_messages', 'پەیامەکان'), 'messages');
?>

<?php if ($msg): ?>
  <div class="panel" style="max-width:760px">
    <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;flex-wrap:wrap">
      <div>
        <h2 class="panel-title" style="margin-bottom:4px"><?= e($msg['subject'] ?: t('a_msg_no_subject', 'بێ بابەت')) ?></h2>
        <div class="muted" style="font-size:.8rem">
          <strong><?= e($msg['name']) ?></strong>
          <?php if ($msg['phone']): ?> · <span dir="ltr"><?= e($msg['phone']) ?></span><?php endif; ?>
          <?php if ($msg['email']): ?> · <span dir="ltr"><?= e($msg['email']) ?></span><?php endif; ?>
          · <?= e(kdate($msg['created_at'])) ?>
        </div>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('messages.php')) ?>">← <?= e(t('a_back', 'گەڕانەوە')) ?></a>
    </div>
    <div style="background:var(--bone);border:1px solid var(--line);border-radius:10px;padding:16px;margin:14px 0;white-space:pre-wrap;line-height:1.9"><?= e($msg['message']) ?></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if ($msg['phone']): ?>
        <a class="btn btn-gold btn-sm" target="_blank" href="<?= e(wa_link($msg['phone'], t('a_msg_wa_prefix', 'سڵاو') . ' ' . $msg['name'] . '،')) ?>">💬 <?= e(t('a_msg_reply_wa', 'وەڵام بە واتسئاپ')) ?></a>
        <a class="btn btn-ghost btn-sm" href="tel:<?= e(preg_replace('/\D+/', '', $msg['phone'])) ?>">📞 <?= e(t('a_msg_call', 'پەیوەندی')) ?></a>
      <?php endif; ?>
      <?php if ($msg['email']): ?><a class="btn btn-ghost btn-sm" href="mailto:<?= e($msg['email']) ?>">✉️ <?= e(t('a_msg_email', 'ئیمەیل')) ?></a><?php endif; ?>
      <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="act" value="unread"><input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
        <button class="btn btn-ghost btn-sm" type="submit"><?= e(t('a_msg_mark_unread', 'وەک نەخوێندراوە دابنێ')) ?></button></form>
      <form method="post" style="display:inline" data-confirm="<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟')) ?>"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
        <button class="btn btn-danger btn-sm" type="submit">🗑 <?= e(t('a_delete', 'سڕینەوە')) ?></button></form>
    </div>
  </div>
<?php endif; ?>

<div class="toolbar">
  <a class="btn <?= $only === '' ? 'btn-gold' : 'btn-ghost' ?>" href="<?= e(admin_url('messages.php')) ?>"><?= e(t('a_msg_all', 'هەموو')) ?></a>
  <a class="btn <?= $only === 'unread' ? 'btn-gold' : 'btn-ghost' ?>" href="<?= e(admin_url('messages.php?f=unread')) ?>"><?= e(t('a_msg_unread', 'نەخوێندراوەکان')) ?></a>
</div>

<div class="tbl-wrap panel" style="padding:0">
  <table class="tbl">
    <tr>
      <th><?= e(t('a_f_name', 'ناو')) ?></th>
      <th><?= e(t('contact_phone', 'ژمارەی مۆبایل')) ?></th>
      <th><?= e(t('a_msg_subject', 'بابەت')) ?></th>
      <th><?= e(t('a_date', 'بەروار')) ?></th>
      <th style="width:1%"></th>
    </tr>
    <?php if (!$rows): ?>
      <tr><td colspan="5" class="tac muted" style="padding:30px"><?= e(t('a_msg_none', 'هیچ پەیامێک نییە')) ?></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr <?= !$r['is_read'] ? 'style="background:#FCF9F3;font-weight:700"' : '' ?>>
        <td><a href="<?= e(admin_url('messages.php?id=' . (int)$r['id'])) ?>"><?= !$r['is_read'] ? '● ' : '' ?><?= e($r['name']) ?></a></td>
        <td dir="ltr"><?= e($r['phone']) ?></td>
        <td><?= e(excerpt_of($r['subject'] ?: $r['message'], 44)) ?></td>
        <td><?= e(kdate($r['created_at'])) ?></td>
        <td>
          <div class="row-actions">
            <a class="btn btn-ghost btn-xs" href="<?= e(admin_url('messages.php?id=' . (int)$r['id'])) ?>">👁</a>
            <form method="post" data-confirm="<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟')) ?>" style="display:inline">
              <?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger btn-xs" type="submit">🗑</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?= render_pagination($pg, admin_url('messages.php' . ($only ? '?f=' . $only : ''))) ?>

<?php admin_footer(); ?>
