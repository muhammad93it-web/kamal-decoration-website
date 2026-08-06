<?php
/** Authentication, roles, login rate limiting, activity log. */

function current_user(): ?array
{
    return $_SESSION['kd_user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_has_role(string $role): bool
{
    $u = current_user();
    return $u !== null && in_array($role, $u['roles'] ?? [], true);
}

function is_super(): bool
{
    return user_has_role('super_admin');
}

/** Gate an admin page. */
function require_login(): void
{
    $u = current_user();
    if ($u === null) {
        $_SESSION['kd_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('admin/login.php');
    }
    // lightweight session fingerprint (user agent binding)
    $fp = substr(sha1($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 16);
    if (($u['fp'] ?? '') !== $fp) {
        logout_user();
        redirect('admin/login.php');
    }
}

/** Gate a super-admin-only page. */
function require_super(): void
{
    require_login();
    if (!is_super()) {
        http_response_code(403);
        die('<meta charset="utf-8">' . e(t('a_forbidden')));
    }
}

/** Is this username/IP temporarily locked out? (5 fails in 10 minutes) */
function login_locked(string $username): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE success = 0 AND created_at > (NOW() - INTERVAL 10 MINUTE)
           AND (username = ? OR ip = ?)'
    );
    $st->execute([$username, get_ip()]);
    return (int)$st->fetchColumn() >= 5;
}

function record_login_attempt(string $username, bool $success): void
{
    $st = db()->prepare('INSERT INTO login_attempts (username, ip, success) VALUES (?, ?, ?)');
    $st->execute([mb_substr($username, 0, 100), get_ip(), $success ? 1 : 0]);
}

/** Try to log in. Returns true on success. */
function attempt_login(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') return false;

    $st = db()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
    $st->execute([$username]);
    $user = $st->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_login_attempt($username, false);
        return false;
    }

    // roles
    $rs = db()->prepare(
        'SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?'
    );
    $rs->execute([$user['id']]);
    $roles = array_column($rs->fetchAll(), 'name');

    session_regenerate_id(true);
    $_SESSION['kd_user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'] ?: $user['username'],
        'roles' => $roles,
        'fp' => substr(sha1($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 16),
    ];
    unset($_SESSION['kd_csrf']); // fresh token for the new session

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    record_login_attempt($username, true);
    log_activity('login', 'user', (int)$user['id']);
    return true;
}

function logout_user(): void
{
    unset($_SESSION['kd_user']);
    session_regenerate_id(true);
}

function log_activity(string $action, ?string $entity = null, ?int $entityId = null, ?string $details = null): void
{
    try {
        $u = current_user();
        $st = db()->prepare(
            'INSERT INTO activity_logs (user_id, action, entity, entity_id, details, ip) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $u['id'] ?? null,
            mb_substr($action, 0, 60),
            $entity !== null ? mb_substr($entity, 0, 60) : null,
            $entityId,
            $details !== null ? mb_substr($details, 0, 500) : null,
            get_ip(),
        ]);
    } catch (Throwable $e) { /* logging must never break the app */ }
}
