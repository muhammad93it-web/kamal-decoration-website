<?php
/**
 * دیکۆراتی کەمال — bootstrap
 * Include this at the top of every page:  require __DIR__ . '/includes/bootstrap.php';
 */
declare(strict_types=1);

mb_internal_encoding('UTF-8');
define('APP_ROOT', dirname(__DIR__));
define('KD_VERSION', 'v2'); // وەشانی کۆد — لە ژێرپەڕە و پانێڵی بەڕێوەبردن دەردەکەوێت

require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/csrf.php';

// ── config / installer gate ─────────────────────────────────────
$__configFile = APP_ROOT . '/config/config.php';
$__isInstalled = is_file($__configFile) && is_file(APP_ROOT . '/config/install.lock');

if (!$__isInstalled && !defined('KD_INSTALLER')) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Not installed yet (missing config/config.php or config/install.lock)\n");
        exit(1);
    }
    header('Location: ' . base_url_guess() . '/install/');
    exit;
}

if (is_file($__configFile)) {
    require_once $__configFile;
}

if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', APP_ROOT . '/uploads');
if (!defined('LOG_DIR'))    define('LOG_DIR', APP_ROOT . '/logs');

// ── errors ──────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', (defined('APP_DEBUG') && APP_DEBUG) ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(LOG_DIR)) @mkdir(LOG_DIR, 0755, true);
ini_set('error_log', LOG_DIR . '/php-errors.log');

// ── session ─────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('kd_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => is_https(),
    ]);
    session_start();
}

// ── database (lazy PDO singleton) ───────────────────────────────
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') && DB_PORT !== '' ? DB_PORT : '3306')
             . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

// ── settings (cached per request) ───────────────────────────────
function settings_all(): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $r) {
                $all[$r['setting_key']] = (string)($r['setting_value'] ?? '');
            }
        } catch (Throwable $e) { /* not installed yet */ }
    }
    return $all;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    $v = $all[$key] ?? '';
    return $v !== '' ? $v : $default;
}

function set_setting(string $key, string $value): void
{
    $st = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute([$key, $value]);
}

// ── language ────────────────────────────────────────────────────
/** Translate a UI string. Falls back to $default, then to the key itself. */
function t(string $key, ?string $default = null): string
{
    static $L = null;
    if ($L === null) {
        $file = APP_ROOT . '/lang/ckb.php'; // future: pick by setting('active_language')
        $L = is_file($file) ? (require $file) : [];
        if (!is_array($L)) $L = [];
    }
    return $L[$key] ?? $default ?? $key;
}

require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/track.php';
require_once APP_ROOT . '/includes/social.php';
require_once APP_ROOT . '/includes/seo.php';

// Composer autoload (mPDF, QR, barcode, HTMLPurifier) — optional so the site
// still renders if libraries/vendor was not uploaded yet.
$__kdAutoload = APP_ROOT . '/libraries/vendor/autoload.php';
if (is_file($__kdAutoload)) {
    require_once $__kdAutoload;
}
