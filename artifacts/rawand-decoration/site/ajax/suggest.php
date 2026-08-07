<?php
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$qRaw = trim((string)($_GET['q'] ?? ''));
$q = normalize_text($qRaw);

if (mb_strlen($q) < 2) {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$like = '%' . $q . '%';
$db = db();
$items = [];

// shades — swatch + code
$st = $db->prepare(
    "SELECT s.name, s.code, s.slug, s.hex_color, p.name AS pname FROM palette_shades s
     JOIN palettes p ON p.id = s.palette_id
     WHERE s.is_active = 1 AND (s.name LIKE ? OR UPPER(s.code) LIKE UPPER(?)) ORDER BY s.position LIMIT 5"
);
$st->execute([$like, $like]);
foreach ($st as $r) {
    $items[] = [
        'label' => $r['name'],
        'sub' => $r['code'] . ' · ' . $r['pname'],
        'url' => url('shade/' . rawurlencode($r['slug'])),
        'color' => $r['hex_color'],
    ];
}

// products — thumb
$st = $db->prepare(
    "SELECT name, code, slug, main_image FROM products
     WHERE is_active = 1 AND (name LIKE ? OR UPPER(code) LIKE UPPER(?)) ORDER BY id DESC LIMIT 5"
);
$st->execute([$like, $like]);
foreach ($st as $r) {
    $items[] = [
        'label' => $r['name'],
        'sub' => (string)$r['code'],
        'url' => url('product/' . rawurlencode($r['slug'])),
        'thumb' => $r['main_image'] ? thumb_url($r['main_image']) : null,
    ];
}

// palettes
$st = $db->prepare(
    "SELECT name, code, slug, cover_image FROM palettes
     WHERE is_active = 1 AND (name LIKE ? OR UPPER(code) LIKE UPPER(?)) LIMIT 3"
);
$st->execute([$like, $like]);
foreach ($st as $r) {
    $items[] = [
        'label' => $r['name'],
        'sub' => $r['code'] . ' · ' . t('nav_palettes'),
        'url' => url('palette/' . rawurlencode($r['slug'])),
        'thumb' => $r['cover_image'] ? thumb_url($r['cover_image']) : null,
    ];
}

// projects
$st = $db->prepare("SELECT title, slug, main_image FROM projects WHERE is_active = 1 AND title LIKE ? LIMIT 3");
$st->execute([$like]);
foreach ($st as $r) {
    $items[] = [
        'label' => $r['title'],
        'sub' => t('nav_projects'),
        'url' => url('project/' . rawurlencode($r['slug'])),
        'thumb' => $r['main_image'] ? thumb_url($r['main_image']) : null,
    ];
}

// posts
$st = $db->prepare("SELECT title, slug, cover_image FROM posts WHERE is_published = 1 AND published_at <= NOW() AND title LIKE ? LIMIT 3");
$st->execute([$like]);
foreach ($st as $r) {
    $items[] = [
        'label' => $r['title'],
        'sub' => t('nav_posts'),
        'url' => url('post/' . rawurlencode($r['slug'])),
        'thumb' => $r['cover_image'] ? thumb_url($r['cover_image']) : null,
    ];
}

echo json_encode([
    'items' => array_slice($items, 0, 12),
    'empty_label' => t('search_no_results'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
