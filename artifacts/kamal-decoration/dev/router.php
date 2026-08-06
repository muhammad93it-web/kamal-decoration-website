<?php
/**
 * Dev router for `php -S` — emulates the .htaccess rewrite rules used on Apache.
 * Not part of the deliverable site/ folder.
 */
$site = realpath(__DIR__ . '/../site');
$uri  = urldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri  = '/' . ltrim($uri, '/');

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
    return false; // let the built-in server stream static files
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
