<?php
/**
 * دیکۆراتی ڕەوەند — دامەزرێنەری کوردی
 * Kurdish web installer: requirements → database → import → admin account → config.php + install.lock
 * Self-contained: must run BEFORE config.php exists (no bootstrap).
 */
declare(strict_types=1);
mb_internal_encoding('UTF-8');
session_name('kd_install');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
session_start();

$ROOT = dirname(__DIR__);
$CONFIG = $ROOT . '/config/config.php';
$LOCK = $ROOT . '/config/install.lock';

function ie(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['kd_install_csrf'])) $_SESSION['kd_install_csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['kd_install_csrf'];

$installed = is_file($CONFIG) && is_file($LOCK);
$step = $installed ? 'done_before' : (string)($_GET['step'] ?? '1');
$errors = [];
$okMsg = '';

/* ── CSRF gate: neutralize any POST without a valid token ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !hash_equals($CSRF, (string)($_POST['_csrf'] ?? ''))) {
    $errors[] = 'فۆڕمەکە بەسەرچوو — تکایە پەڕەکە نوێ بکەرەوە و دووبارە هەوڵ بدەوە';
    $_POST['do'] = '';
}

/* ── requirement checks ── */
function kd_requirements(string $ROOT): array
{
    $w = fn(string $p) => is_dir($p) ? is_writable($p) : @mkdir($p, 0755, true);
    return [
        ['PHP 8.1+', PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=')],
        ['PDO MySQL', 'pdo_mysql', extension_loaded('pdo_mysql')],
        ['mbstring', 'mbstring', extension_loaded('mbstring')],
        ['GD (وێنە)', 'gd', extension_loaded('gd')],
        ['fileinfo', 'fileinfo', extension_loaded('fileinfo')],
        ['فۆڵدەری config/ بنووسرێت', 'config/', $w($ROOT . '/config')],
        ['فۆڵدەری uploads/ بنووسرێت', 'uploads/', $w($ROOT . '/uploads')],
        ['فۆڵدەری logs/ بنووسرێت', 'logs/', $w($ROOT . '/logs')],
        ['فایلی database.sql هەیە', 'database.sql', is_file($ROOT . '/database.sql')],
    ];
}

/* ── split database.sql into statements ── */
function kd_split_sql(string $sql): array
{
    $lines = preg_split('/\r?\n/', $sql);
    $stmts = [];
    $buf = '';
    foreach ($lines as $line) {
        if (preg_match('/^\s*--/', $line) || trim($line) === '') {
            if ($buf === '') continue;
        }
        $buf .= $line . "\n";
        if (preg_match('/;\s*$/', rtrim($line))) {
            $stmts[] = $buf;
            $buf = '';
        }
    }
    if (trim($buf) !== '') $stmts[] = $buf;
    return $stmts;
}

function kd_pdo(string $host, string $port, string $name, string $user, string $pass): PDO
{
    return new PDO(
        "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

/* ── step 2 POST: test DB + import ── */
if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'db') {
    $host = trim((string)($_POST['host'] ?? 'localhost')) ?: 'localhost';
    $port = trim((string)($_POST['port'] ?? '3306')) ?: '3306';
    $name = trim((string)($_POST['name'] ?? ''));
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');

    if ($name === '' || $user === '') {
        $errors[] = 'ناوی بنکەدراوە و ناوی بەکارهێنەر پێویستن';
    } else {
        try {
            $pdo = kd_pdo($host, $port, $name, $user, $pass);
            $existing = (int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
            if ($existing > 0 && empty($_POST['overwrite'])) {
                $errors[] = "ئەم بنکەدراوەیە $existing خشتەی تێدایە. ئەگەر دەتەوێت هەمووی بسڕدرێتەوە و لە نوێوە دابمەزرێت، خانەی «سڕینەوەی خشتە کۆنەکان» هەڵبژێرە.";
            } else {
                $sql = (string)file_get_contents($ROOT . '/database.sql');
                foreach (kd_split_sql($sql) as $stmt) {
                    $pdo->exec($stmt);
                }
                $_SESSION['kd_db'] = compact('host', 'port', 'name', 'user', 'pass');
                header('Location: ?step=3');
                exit;
            }
        } catch (PDOException $ex) {
            $errors[] = 'نەتوانرا پەیوەندی بە بنکەدراوە بکرێت: ' . $ex->getMessage();
        }
    }
    $step = '2';
}

/* ── step 3 POST: admin + config ── */
if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'admin') {
    if (empty($_SESSION['kd_db'])) {
        header('Location: ?step=2');
        exit;
    }
    $d = $_SESSION['kd_db'];
    $username = trim((string)($_POST['username'] ?? ''));
    $display = trim((string)($_POST['display_name'] ?? '')) ?: 'بەڕێوەبەر';
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $errors[] = 'ناوی بەکارهێنەر: ٣-٥٠ پیتی ئینگلیزی/ژمارە بێت';
    }
    if (strlen($password) < 8) {
        $errors[] = 'وشەی نهێنی لانیکەم ٨ پیت بێت';
    } elseif ($password !== $password2) {
        $errors[] = 'دووبارەکردنەوەی وشەی نهێنی وەک یەک نییە';
    }

    if (!$errors) {
        try {
            $pdo = kd_pdo($d['host'], $d['port'], $d['name'], $d['user'], $d['pass']);
            // the dump seeds a default admin — remove any same-name user first (user_roles cascades)
            $pdo->prepare('DELETE FROM users WHERE username = ?')->execute([$username]);
            $st = $pdo->prepare('INSERT INTO users (username, display_name, password_hash, is_active) VALUES (?,?,?,1)');
            $st->execute([$username, $display, password_hash($password, PASSWORD_DEFAULT)]);
            $uid = (int)$pdo->lastInsertId();
            $rid = (int)$pdo->query("SELECT id FROM roles WHERE name = 'super_admin'")->fetchColumn();
            if ($rid) $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)')->execute([$uid, $rid]);

            // site_url from current request
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
            $hostHdr = $_SERVER['HTTP_HOST'] ?? 'localhost';
            if (!preg_match('/^[a-zA-Z0-9.\-]+(:\d{1,5})?$/', $hostHdr)) $hostHdr = 'localhost';
            $basePath = rtrim(str_replace('/install', '', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $siteUrl = ($https ? 'https://' : 'http://') . $hostHdr . $basePath;
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
                ->execute(['site_url', $siteUrl]);

            // write config.php
            $tpl = (string)file_get_contents($ROOT . '/config/config.example.php');
            $cfg = strtr($tpl, [
                "define('DB_HOST', 'localhost');" => "define('DB_HOST', " . var_export($d['host'], true) . ');',
                "define('DB_PORT', '3306');" => "define('DB_PORT', " . var_export($d['port'], true) . ');',
                "define('DB_NAME', 'your_database_name');" => "define('DB_NAME', " . var_export($d['name'], true) . ');',
                "define('DB_USER', 'your_database_user');" => "define('DB_USER', " . var_export($d['user'], true) . ');',
                "define('DB_PASS', 'your_database_password');" => "define('DB_PASS', " . var_export($d['pass'], true) . ');',
            ]);
            if (file_put_contents($CONFIG, $cfg) === false) {
                throw new RuntimeException('نەتوانرا config.php بنووسرێت — مۆڵەتی فۆڵدەری config/ بپشکنە');
            }
            file_put_contents($LOCK, date('c') . "\n");
            unset($_SESSION['kd_db']);
            session_destroy();
            header('Location: ?step=done');
            exit;
        } catch (Throwable $ex) {
            $errors[] = 'هەڵە: ' . $ex->getMessage();
        }
    }
    $step = '3';
}

if ($step === '3' && empty($_SESSION['kd_db']) && !$installed) $step = '2';
if ($step === 'done' && !is_file($LOCK)) $step = '1';
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>دامەزراندن — دیکۆراتی ڕەوەند</title>
<style>
  :root { --dark:#232120; --bone:#FAF7F2; --beige:#E9DFD2; --gold:#BFA05A; --line:#DFD5C4; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Noto Kufi Arabic', Tahoma, sans-serif; background:var(--bone); color:var(--dark);
         min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:16px; max-width:640px; width:100%;
          padding:34px; box-shadow:0 20px 60px rgba(35,33,32,.12); }
  h1 { font-size:1.35rem; margin-bottom:4px; }
  .sub { color:#8A7C68; font-size:.85rem; margin-bottom:22px; }
  .steps { display:flex; gap:6px; margin-bottom:24px; }
  .steps span { flex:1; height:5px; border-radius:99px; background:var(--beige); }
  .steps span.on { background:var(--gold); }
  table { width:100%; border-collapse:collapse; margin-bottom:18px; }
  td { padding:9px 6px; border-bottom:1px solid var(--beige); font-size:.88rem; }
  .ok { color:#4A7A4A; font-weight:700; } .bad { color:#B4544A; font-weight:700; }
  label { display:block; font-size:.82rem; font-weight:700; margin:12px 0 5px; }
  input[type=text], input[type=password] { width:100%; padding:11px 13px; border:1.5px solid var(--line);
    border-radius:9px; font:inherit; background:var(--bone); }
  input:focus { outline:none; border-color:var(--gold); background:#fff; }
  .btn { display:inline-block; margin-top:20px; background:var(--gold); color:#fff; border:none; cursor:pointer;
         padding:12px 26px; border-radius:10px; font:inherit; font-weight:700; text-decoration:none; }
  .btn:hover { filter:brightness(1.06); }
  .err { background:#FBEDEB; border:1px solid #E5B5AF; color:#8C3A32; border-radius:10px;
         padding:11px 14px; font-size:.85rem; margin-bottom:8px; }
  .note { background:#F4EFE5; border:1px solid var(--line); border-radius:10px; padding:11px 14px;
          font-size:.82rem; color:#6d5f4b; margin-top:14px; line-height:1.8; }
  .hint { font-size:.75rem; color:#8A7C68; margin-top:4px; }
  code { background:var(--beige); border-radius:5px; padding:1px 7px; font-size:.82rem; direction:ltr; display:inline-block; }
  .check { font-size:.85rem; margin-top:10px; display:flex; gap:8px; align-items:center; }
</style>
</head>
<body>
<div class="card">
  <h1>دیکۆراتی ڕەوەند — دامەزراندن</h1>
  <div class="sub">Rawand Decoration — Installer</div>

  <?php if ($step !== 'done' && $step !== 'done_before'): ?>
    <div class="steps">
      <span class="<?= in_array($step, ['1', '2', '3'], true) ? 'on' : '' ?>"></span>
      <span class="<?= in_array($step, ['2', '3'], true) ? 'on' : '' ?>"></span>
      <span class="<?= $step === '3' ? 'on' : '' ?>"></span>
    </div>
  <?php endif; ?>

  <?php foreach ($errors as $er): ?><div class="err"><?= ie($er) ?></div><?php endforeach; ?>

  <?php if ($step === 'done_before'): ?>
    <p style="line-height:2">ماڵپەڕەکە پێشتر دامەزراوە ✓</p>
    <div class="note">بۆ دامەزراندنەوە لە سەرەتاوە: فایلی <code>config/install.lock</code> و <code>config/config.php</code> بسڕەوە و ئەم پەڕەیە بکەوە.</div>
    <a class="btn" href="../">چوونە ماڵپەڕ</a>

  <?php elseif ($step === '1'): ?>
    <h2 style="font-size:1rem;margin-bottom:10px">هەنگاوی ١ — پشکنینی سێرڤەر</h2>
    <table>
      <?php $allOk = true; foreach (kd_requirements($ROOT) as [$label, $detail, $pass]): $allOk = $allOk && $pass; ?>
        <tr>
          <td><?= ie($label) ?> <span class="hint" style="direction:ltr"><?= ie((string)$detail) ?></span></td>
          <td style="width:60px;text-align:left"><?= $pass ? '<span class="ok">✓</span>' : '<span class="bad">✗</span>' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php if ($allOk): ?>
      <a class="btn" href="?step=2">بەردەوامبوون ←</a>
    <?php else: ?>
      <div class="err">هەندێک مەرج نەگونجاوە — پەیوەندی بە هۆستەکەت بکە یان لە cPanel &larr; Select PHP Version چالاکیان بکە، پاشان ئەم پەڕەیە نوێ بکەوە.</div>
    <?php endif; ?>

  <?php elseif ($step === '2'): ?>
    <h2 style="font-size:1rem;margin-bottom:6px">هەنگاوی ٢ — بنکەدراوە (MySQL)</h2>
    <div class="note" style="margin:0 0 8px">لە cPanel &larr; <b>MySQL Databases</b>: بنکەدراوەیەک و بەکارهێنەرێک دروست بکە و بەکارهێنەرەکە بە <b>ALL PRIVILEGES</b> زیاد بکە بۆ بنکەدراوەکە. پاشان زانیارییەکان لێرە بنووسە.</div>
    <form method="post">
      <input type="hidden" name="do" value="db">
      <input type="hidden" name="_csrf" value="<?= ie($CSRF) ?>">
      <label>هۆست (Host)</label>
      <input type="text" name="host" dir="ltr" value="<?= ie((string)($_POST['host'] ?? 'localhost')) ?>">
      <div class="hint">لە Namecheap زۆربەی کات <code>localhost</code>ـە</div>
      <label>پۆرت (Port)</label>
      <input type="text" name="port" dir="ltr" value="<?= ie((string)($_POST['port'] ?? '3306')) ?>">
      <label>ناوی بنکەدراوە</label>
      <input type="text" name="name" dir="ltr" value="<?= ie((string)($_POST['name'] ?? '')) ?>" placeholder="user_rawand">
      <label>ناوی بەکارهێنەری بنکەدراوە</label>
      <input type="text" name="user" dir="ltr" value="<?= ie((string)($_POST['user'] ?? '')) ?>" placeholder="user_rawand">
      <label>وشەی نهێنی بنکەدراوە</label>
      <input type="password" name="pass" dir="ltr" value="">
      <label class="check"><input type="checkbox" name="overwrite" value="1"> سڕینەوەی خشتە کۆنەکان ئەگەر هەبن (ئاگاداربە!)</label>
      <button class="btn" type="submit">پەیوەندی و بارکردنی داتا ←</button>
    </form>

  <?php elseif ($step === '3'): ?>
    <h2 style="font-size:1rem;margin-bottom:6px">هەنگاوی ٣ — هەژماری بەڕێوەبەر</h2>
    <div class="note" style="margin:0 0 8px">داتاکان بە سەرکەوتوویی بارکران ✓ — ئێستا هەژماری بەڕێوەبەری سەرەکی دروست بکە.</div>
    <form method="post">
      <input type="hidden" name="do" value="admin">
      <input type="hidden" name="_csrf" value="<?= ie($CSRF) ?>">
      <label>ناوی بەکارهێنەر (بە ئینگلیزی)</label>
      <input type="text" name="username" dir="ltr" value="<?= ie((string)($_POST['username'] ?? 'admin')) ?>">
      <label>ناوی پیشاندان</label>
      <input type="text" name="display_name" value="<?= ie((string)($_POST['display_name'] ?? 'ڕەوەند')) ?>">
      <label>وشەی نهێنی (لانیکەم ٨ پیت)</label>
      <input type="password" name="password" dir="ltr">
      <label>دووبارەکردنەوەی وشەی نهێنی</label>
      <input type="password" name="password2" dir="ltr">
      <button class="btn" type="submit">تەواوکردنی دامەزراندن ✓</button>
    </form>

  <?php else: /* done */ ?>
    <h2 style="font-size:1rem;margin-bottom:10px">🎉 دامەزراندن تەواو بوو!</h2>
    <p style="line-height:2;font-size:.9rem">
      ماڵپەڕەکەت ئێستا ئامادەیە. هەنگاوەکانی دواتر:
    </p>
    <ol style="padding-right:20px;line-height:2.2;font-size:.88rem">
      <li>بچۆ ژوورەوە بۆ <a href="../admin/" style="color:#8A6B3F;font-weight:700">بەشی بەڕێوەبردن</a></li>
      <li>لە <b>ڕێکخستنەکان &larr; لۆگۆ و ناسنامە</b> لۆگۆکەت داببنێ</li>
      <li>لە <b>ئامرازەکانی QR</b> دوگمەی «نوێکردنەوەی هەموو» دابگرە بۆ دروستکردنی QRەکان</li>
      <li>بۆ پاراستن، فۆڵدەری <code>install</code> بسڕەوە لە File Manager</li>
    </ol>
    <a class="btn" href="../admin/">چوونەژوورەوە ←</a>
    <a class="btn" href="../" style="background:#8A7C68">بینینی ماڵپەڕ</a>
  <?php endif; ?>
</div>
</body>
</html>
