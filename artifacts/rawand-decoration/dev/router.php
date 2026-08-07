<?php
/**
 * Dev router for `php -S` — emulates the .htaccess rewrite rules used on Apache.
 * Not part of the deliverable site/ folder.
 *
 * Supports serving under a path prefix (BASE_PATH env, e.g. "/rawand") so the
 * artifact can live behind Replit's path-routed preview proxy. On real hosting
 * the site sits at the domain root and none of this applies.
 */
$site = realpath(__DIR__ . '/../site');
$uri  = urldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri  = '/' . ltrim($uri, '/');

// Reject dot segments outright — the manual static streaming below must never
// resolve a path outside site/ (php -S no longer guards us there).
foreach (explode('/', $uri) as $seg) {
    if ($seg === '.' || $seg === '..') {
        http_response_code(404);
        echo 'Not Found';
        return true;
    }
}

// Strip the dev base-path prefix (preview proxy forwards it unchanged)
$bp = rtrim((string)(getenv('BASE_PATH') ?: ''), '/');
if ($bp !== '' && $bp !== '/') {
    if ($uri === $bp || str_starts_with($uri, $bp . '/')) {
        $uri = substr($uri, strlen($bp));
        if ($uri === '' || $uri === false) $uri = '/';
    }
}

// Block sensitive paths (mirrors .htaccess protections)
if (preg_match('#^/(config|includes|templates|lang|libraries|logs|backups|tools)(/|$)#', $uri)
    || str_ends_with($uri, '.sql') || $uri === '/composer.json' || $uri === '/composer.lock') {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
// Never execute PHP inside uploads
if (preg_match('#^/uploads/.+\.(php|phar|phtml|cgi|sh)$#i', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

$file = $site . $uri;
if ($uri !== '/' && is_file($file)) {
    if (str_ends_with($file, '.php')) {
        chdir(dirname($file));
        require $file;
        return true;
    }
    if ($bp === '' || $bp === '/') {
        return false; // no prefix: let the built-in server stream static files
    }
    // With a prefix the built-in server would resolve the ORIGINAL URI and 404,
    // so stream the file ourselves. Containment check: only files strictly
    // inside site/ may ever be streamed.
    $real = realpath($file);
    if ($real === false || !str_starts_with($real, $site . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        echo 'Not Found';
        return true;
    }
    $file = $real;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = [
        'css' => 'text/css', 'js' => 'application/javascript', 'mjs' => 'application/javascript',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject', 'pdf' => 'application/pdf',
        'txt' => 'text/plain; charset=UTF-8', 'xml' => 'application/xml', 'json' => 'application/json',
        'map' => 'application/json', 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'zip' => 'application/zip',
    ][$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($file));
    header('Cache-Control: no-cache');
    readfile($file);
    return true;
}

// Clean URL routes (keep in sync with site/.htaccess)
$routes = [
    '#^/p/([A-Za-z0-9._\-]+)/?$#u'   => ['resolve.php',  'code'],
    '#^/palette/([^/]+)/?$#u'        => ['palette.php',  'slug'],
    '#^/shade/([^/]+)/?$#u'          => ['shade.php',    'slug'],
    '#^/product/([^/]+)/?$#u'        => ['product.php',  'slug'],
    '#^/project/([^/]+)/?$#u'        => ['project.php',  'slug'],
    '#^/post/([^/]+)/?$#u'           => ['post.php',     'slug'],
    '#^/category/([^/]+)/?$#u'       => ['products.php', 'category'],
    '#^/sitemap\.xml$#'              => ['sitemap.php',  null],
];
foreach ($routes as $re => [$target, $param]) {
    if (preg_match($re, $uri, $m)) {
        if ($param !== null) { $_GET[$param] = $m[1]; }
        $_SERVER['SCRIPT_NAME'] = '/' . $target;
        chdir($site);
        require $site . '/' . $target;
        return true;
    }
}

// Extensionless page → page.php (mirrors the .htaccess rule)
if (preg_match('#^/([a-z0-9-]+)/?$#', $uri, $m) && is_file($site . '/' . $m[1] . '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $m[1] . '.php';
    chdir($site);
    require $site . '/' . $m[1] . '.php';
    return true;
}

if ($uri === '/') {
    chdir($site);
    require $site . '/index.php';
    return true;
}
if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    $dir = rtrim($file, '/');
    $_SERVER['SCRIPT_NAME'] = rtrim($uri, '/') . '/index.php';
    chdir($dir);
    require $dir . '/index.php';
    return true;
}

http_response_code(404);
chdir($site);
require $site . '/404.php';
return true;
