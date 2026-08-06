<?php
/**
 * DEV ONLY (Replit harness): idempotent seed after database.sql import.
 *  - creates the dev admin account (admin / Kamal@2026) when users is empty
 *  - points site_url at the current Replit domain
 *  - builds missing thumbnails for the seeded sample images
 *  - generates missing QR/barcode files (needs composer vendor libs)
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");

define('KD_SKIP_TRACK', true);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/uploads.php';

$pdo = db();

/* 1) admin user */
$n = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($n === 0) {
    $st = $pdo->prepare('INSERT INTO users (username, display_name, password_hash, is_active) VALUES (?,?,?,1)');
    $st->execute(['admin', 'کەمال', password_hash('Kamal@2026', PASSWORD_DEFAULT)]);
    $uid = (int)$pdo->lastInsertId();
    $rid = (int)$pdo->query("SELECT id FROM roles WHERE name = 'super_admin'")->fetchColumn();
    if ($rid) $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)')->execute([$uid, $rid]);
    echo "[seed] created admin user (admin / Kamal@2026)\n";
}

/* 2) site_url from Replit env */
$domain = getenv('REPLIT_DEV_DOMAIN') ?: strtok((string)getenv('REPLIT_DOMAINS'), ',');
if ($domain) {
    $want = 'https://' . trim($domain);
    $cur = setting('site_url', '');
    if ($cur !== $want) {
        $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
            ->execute(['site_url', $want]);
        echo "[seed] site_url = $want\n";
        // force settings cache refresh for this process
        $GLOBALS['__kd_settings'] = null;
    }
}

/* 3) thumbnails for sample images */
$made = 0;
foreach (['palettes', 'products', 'projects', 'media', 'posts', 'sliders'] as $dir) {
    $abs = UPLOAD_DIR . '/' . $dir;
    if (!is_dir($abs)) continue;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS)) as $f) {
        if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $f->getFilename())) continue;
        $rel = ltrim(str_replace(UPLOAD_DIR, '', $f->getPathname()), '/');
        $thumbAbs = UPLOAD_DIR . '/thumbnails/' . str_replace('/', '_', $rel);
        if (is_file($thumbAbs)) continue;
        $mime = mime_content_type($f->getPathname()) ?: 'image/jpeg';
        try {
            if (function_exists('kd_make_thumb')) {
                kd_make_thumb($f->getPathname(), $thumbAbs, $mime);
                $made++;
            }
        } catch (Throwable $e) { /* ignore */ }
    }
}
if ($made) echo "[seed] thumbnails created: $made\n";

/* 4) QR + barcode files */
if (is_file(APP_ROOT . '/libraries/vendor/autoload.php')) {
    require_once APP_ROOT . '/includes/codes.php';
    $done = 0;
    foreach ($pdo->query('SELECT id, qr_path, barcode_path FROM palette_shades')->fetchAll() as $s) {
        $missing = empty($s['qr_path']) || !is_file(UPLOAD_DIR . '/' . $s['qr_path'])
                || empty($s['barcode_path']) || !is_file(UPLOAD_DIR . '/' . $s['barcode_path']);
        if (!$missing) continue;
        try { ensure_shade_codes((int)$s['id']); $done++; } catch (Throwable $e) {
            echo '[seed] shade ' . $s['id'] . ' codes failed: ' . $e->getMessage() . "\n";
            break; // same failure would repeat for every row
        }
    }
    foreach ($pdo->query("SELECT id, qr_path, barcode_path FROM products WHERE code IS NOT NULL AND code <> ''")->fetchAll() as $p) {
        $missing = empty($p['qr_path']) || !is_file(UPLOAD_DIR . '/' . $p['qr_path'])
                || empty($p['barcode_path']) || !is_file(UPLOAD_DIR . '/' . $p['barcode_path']);
        if (!$missing) continue;
        try { ensure_product_codes((int)$p['id']); $done++; } catch (Throwable $e) {
            echo '[seed] product ' . $p['id'] . ' codes failed: ' . $e->getMessage() . "\n";
            break;
        }
    }
    if ($done) echo "[seed] QR/barcode generated for $done items\n";
} else {
    echo "[seed] vendor libs not ready — skipping QR generation\n";
}

echo "[seed] done\n";
