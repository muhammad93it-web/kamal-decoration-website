<?php
/** User management — SUPER ADMIN only. */
require __DIR__ . '/includes/admin-bootstrap.php';
require_super();

$roles = db()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
$me = current_user();

function user_roles_of(int $uid): array
{
    $st = db()->prepare('SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?');
    $st->execute([$uid]);
    return array_column($st->fetchAll(), 'name');
}

function active_super_count(?int $excludeUid = null): int
{
    $sql = "SELECT COUNT(DISTINCT u.id) FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE r.name = 'super_admin' AND u.is_active = 1";
    if ($excludeUid) $sql .= ' AND u.id <> ' . (int)$excludeUid;
    return (int)db()->query($sql)->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0) ?: null;
        $username = mb_substr(trim((string)($_POST['username'] ?? '')), 0, 50);
        $display = mb_substr(trim((string)($_POST['display_name'] ?? '')), 0, 120);
        $email = mb_substr(trim((string)($_POST['email'] ?? '')), 0, 190) ?: null;
        $password = (string)($_POST['password'] ?? '');
        $roleName = (string)($_POST['role'] ?? 'editor');
        if (!in_array($roleName, array_column($roles, 'name'), true)) $roleName = 'editor';
        $active = isset($_POST['is_active']) ? 1 : 0;

        $err = null;
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            $err = t('a_u_err_username', 'ناوی بەکارهێنەر: 3-50 پیتی ئینگلیزی/ژمارە');
        } elseif (!$id && strlen($password) < 8) {
            $err = t('a_u_err_pass', 'وشەی نهێنی لانیکەم ٨ پیت بێت');
        } elseif ($id && $password !== '' && strlen($password) < 8) {
            $err = t('a_u_err_pass', 'وشەی نهێنی لانیکەم ٨ پیت بێت');
        } else {
            $st = db()->prepare('SELECT id FROM users WHERE username = ?' . ($id ? ' AND id <> ' . (int)$id : ''));
            $st->execute([$username]);
            if ($st->fetch()) $err = t('a_u_err_taken', 'ئەم ناوی بەکارهێنەرە گیراوە');
        }

        // guard: don't demote/deactivate the last active super admin
        if (!$err && $id) {
            $wasSuper = in_array('super_admin', user_roles_of($id), true);
            $stillSuper = $roleName === 'super_admin' && $active === 1;
            if ($wasSuper && !$stillSuper && active_super_count($id) === 0) {
                $err = t('a_u_err_last_super', 'ناتوانرێت — ئەمە دوایین بەڕێوەبەری سەرەکییە');
            }
        }

        if ($err) {
            flash('error', $err);
            redirect(admin_url('users.php' . ($id ? '?edit=' . $id : '?new=1')));
        }

        if ($id) {
            $sql = 'UPDATE users SET username = ?, display_name = ?, email = ?, is_active = ?';
            $args = [$username, $display, $email, $active];
            if ($password !== '') { $sql .= ', password_hash = ?'; $args[] = password_hash($password, PASSWORD_DEFAULT); }
            $sql .= ' WHERE id = ?';
            $args[] = $id;
            db()->prepare($sql)->execute($args);
        } else {
            $st = db()->prepare('INSERT INTO users (username, display_name, email, password_hash, is_active) VALUES (?,?,?,?,?)');
            $st->execute([$username, $display, $email, password_hash($password, PASSWORD_DEFAULT), $active]);
            $id = (int)db()->lastInsertId();
        }

        $roleId = null;
        foreach ($roles as $r) if ($r['name'] === $roleName) $roleId = (int)$r['id'];
        db()->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
        if ($roleId) db()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$id, $roleId]);

        log_activity('save', 'user', $id, $username . ' (' . $roleName . ')');
        flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
        redirect(admin_url('users.php'));
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$me['id']) {
            flash('error', t('a_u_err_self', 'ناتوانیت هەژماری خۆت بسڕیتەوە'));
        } elseif (in_array('super_admin', user_roles_of($id), true) && active_super_count($id) === 0) {
            flash('error', t('a_u_err_last_super', 'ناتوانرێت — ئەمە دوایین بەڕێوەبەری سەرەکییە'));
        } else {
            $st = db()->prepare('SELECT username FROM users WHERE id = ?');
            $st->execute([$id]);
            $un = (string)$st->fetchColumn();
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            log_activity('delete', 'user', $id, $un);
            flash('success', t('a_deleted', 'سڕایەوە'));
        }
        redirect(admin_url('users.php'));
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
$editRow = null;
$editRoles = [];
if ($editId) {
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$editId]);
    $editRow = $st->fetch();
    if ($editRow) $editRoles = user_roles_of($editId);
}

$users = db()->query('SELECT * FROM users ORDER BY id')->fetchAll();

admin_header(t('a_users', 'بەکارهێنەران'), 'users');
?>

<?php if ($isNew || $editRow): ?>
  <form method="post" class="panel" style="max-width:640px">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="save">
    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
    <h2 class="panel-title"><?= $editRow ? e(t('a_edit', 'دەستکاری')) . ' — ' . e($editRow['username']) : e(t('a_u_new', 'بەکارهێنەری نوێ')) ?></h2>
    <div class="f-grid">
      <div class="f-row"><label><?= e(t('login_username', 'ناوی بەکارهێنەر')) ?> *</label>
        <input type="text" name="username" dir="ltr" required value="<?= e($editRow['username'] ?? '') ?>" pattern="[a-zA-Z0-9_.\-]{3,50}"></div>
      <div class="f-row"><label><?= e(t('a_u_display', 'ناوی پیشاندان')) ?></label>
        <input type="text" name="display_name" value="<?= e($editRow['display_name'] ?? '') ?>"></div>
      <div class="f-row"><label><?= e(t('a_u_email', 'ئیمەیل (ئارەزوومەندانە)')) ?></label>
        <input type="email" name="email" dir="ltr" value="<?= e($editRow['email'] ?? '') ?>"></div>
      <div class="f-row"><label><?= e(t('login_password', 'وشەی نهێنی')) ?> <?= $editRow ? '(' . e(t('a_u_pass_keep', 'بەتاڵ = وەک خۆی')) . ')' : '*' ?></label>
        <input type="password" name="password" dir="ltr" autocomplete="new-password" <?= $editRow ? '' : 'required' ?> minlength="8"></div>
      <div class="f-row">
        <label><?= e(t('a_u_role', 'ڕۆڵ')) ?></label>
        <?php $cur = $editRoles[0] ?? 'editor'; ?>
        <?php foreach ($roles as $r): ?>
          <label class="f-check" style="font-weight:500">
            <input type="radio" name="role" value="<?= e($r['name']) ?>" <?= $cur === $r['name'] ? 'checked' : '' ?>>
            <?= e($r['label']) ?>
          </label>
        <?php endforeach; ?>
        <div class="f-hint"><?= e(t('a_u_role_hint', 'بەڕێوەبەری سەرەکی: هەموو شتێک + بەکارهێنەران و باکئەپ. دەستکاریکەر: تەنیا ناوەڕۆک.')) ?></div>
      </div>
      <div class="f-row"><label class="f-check"><input type="checkbox" name="is_active" <?= ($editRow['is_active'] ?? 1) ? 'checked' : '' ?>> <?= e(t('a_f_active', 'چالاک')) ?></label></div>
    </div>
    <div class="form-foot">
      <button class="btn btn-gold" type="submit"><?= e(t('a_save', 'پاشەکەوتکردن')) ?></button>
      <a class="btn btn-ghost" href="<?= e(admin_url('users.php')) ?>"><?= e(t('a_cancel', 'گەڕانەوە')) ?></a>
    </div>
  </form>
<?php else: ?>
  <div class="toolbar">
    <div class="grow"></div>
    <a class="btn btn-gold" href="<?= e(admin_url('users.php?new=1')) ?>">＋ <?= e(t('a_u_new', 'بەکارهێنەری نوێ')) ?></a>
  </div>
<?php endif; ?>

<div class="tbl-wrap panel" style="padding:0">
  <table class="tbl">
    <tr>
      <th><?= e(t('login_username', 'ناوی بەکارهێنەر')) ?></th>
      <th><?= e(t('a_u_display', 'ناوی پیشاندان')) ?></th>
      <th><?= e(t('a_u_role', 'ڕۆڵ')) ?></th>
      <th><?= e(t('a_u_last_login', 'دوایین چوونەژوورەوە')) ?></th>
      <th><?= e(t('a_f_active', 'چالاک')) ?></th>
      <th style="width:1%"></th>
    </tr>
    <?php foreach ($users as $u): $ur = user_roles_of((int)$u['id']); ?>
      <tr>
        <td><strong dir="ltr"><?= e($u['username']) ?></strong><?= (int)$u['id'] === (int)$me['id'] ? ' <span class="muted">(' . e(t('a_u_you', 'تۆ')) . ')</span>' : '' ?></td>
        <td><?= e($u['display_name']) ?></td>
        <td><?= in_array('super_admin', $ur, true) ? '<span class="pill pill-gold">' . e(t('role_super', 'بەڕێوەبەری سەرەکی')) . '</span>' : '<span class="pill">' . e(t('role_editor', 'دەستکاریکەر')) . '</span>' ?></td>
        <td><?= $u['last_login_at'] ? e(kdate($u['last_login_at'])) : '<span class="muted">—</span>' ?></td>
        <td><span class="pill <?= $u['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= $u['is_active'] ? '✓' : '✗' ?></span></td>
        <td>
          <div class="row-actions">
            <a class="btn btn-ghost btn-xs" href="<?= e(admin_url('users.php?edit=' . (int)$u['id'])) ?>">✏️</a>
            <?php if ((int)$u['id'] !== (int)$me['id']): ?>
              <form method="post" data-confirm="<?= e(t('a_confirm_delete', 'دڵنیایت لە سڕینەوە؟')) ?>" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button class="btn btn-danger btn-xs" type="submit">🗑</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php admin_footer(); ?>
