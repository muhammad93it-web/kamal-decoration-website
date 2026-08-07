<?php
define('KD_SKIP_TRACK', true);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [];
$add = function (string $loc, ?string $lastmod = null, string $freq = 'weekly', string $prio = '0.6') use (&$urls) {
    $urls[] = ['loc' => $loc, 'lastmod' => $lastmod, 'freq' => $freq, 'prio' => $prio];
};

$add(abs_url(''), null, 'daily', '1.0');
foreach (['products.php', 'palettes.php', 'projects.php', 'gallery.php', 'posts.php', 'about.php', 'contact.php', 'scanner.php'] as $pth) {
    $add(abs_url($pth), null, 'weekly', '0.7');
}

$db = db();
foreach ($db->query("SELECT slug, updated_at FROM products WHERE is_active = 1") as $r) {
    $add(abs_url('product/' . rawurlencode($r['slug'])), $r['updated_at'] ? date('Y-m-d', strtotime($r['updated_at'])) : null, 'weekly', '0.8');
}
foreach ($db->query("SELECT slug FROM categories WHERE type = 'product' AND is_active = 1") as $r) {
    $add(abs_url('category/' . rawurlencode($r['slug'])), null, 'weekly', '0.6');
}
foreach ($db->query("SELECT slug, updated_at FROM palettes WHERE is_active = 1") as $r) {
    $add(abs_url('palette/' . rawurlencode($r['slug'])), $r['updated_at'] ? date('Y-m-d', strtotime($r['updated_at'])) : null, 'weekly', '0.7');
}
foreach ($db->query("SELECT slug, updated_at FROM palette_shades WHERE is_active = 1") as $r) {
    $add(abs_url('shade/' . rawurlencode($r['slug'])), $r['updated_at'] ? date('Y-m-d', strtotime($r['updated_at'])) : null, 'monthly', '0.5');
}
foreach ($db->query("SELECT slug, updated_at FROM projects WHERE is_active = 1") as $r) {
    $add(abs_url('project/' . rawurlencode($r['slug'])), $r['updated_at'] ? date('Y-m-d', strtotime($r['updated_at'])) : null, 'monthly', '0.7');
}
foreach ($db->query("SELECT slug, updated_at FROM posts WHERE is_published = 1 AND published_at <= NOW()") as $r) {
    $add(abs_url('post/' . rawurlencode($r['slug'])), $r['updated_at'] ? date('Y-m-d', strtotime($r['updated_at'])) : null, 'monthly', '0.6');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    if ($u['lastmod']) echo "    <lastmod>{$u['lastmod']}</lastmod>\n";
    echo "    <changefreq>{$u['freq']}</changefreq>\n    <priority>{$u['prio']}</priority>\n  </url>\n";
}
echo "</urlset>\n";
