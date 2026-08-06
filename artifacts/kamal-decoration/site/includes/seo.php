<?php
/** SEO helpers: meta tags, Open Graph, JSON-LD. */

/**
 * Echo the SEO head block.
 * $o keys: title, desc, image (absolute or uploads-relative), url, type
 */
function seo_head(array $o = []): void
{
    $siteName = setting('site_name', 'دیکۆراتی کەمال');
    $title = trim($o['title'] ?? '');
    $fullTitle = $title !== '' ? $title . ' — ' . $siteName : setting('seo_title', $siteName);
    $desc = trim($o['desc'] ?? '') ?: setting('seo_description', setting('tagline'));
    $desc = excerpt_of($desc, 300);
    $urlAbs = $o['url'] ?? (site_base() . ($_SERVER['REQUEST_URI'] ?? '/'));
    $urlAbs = strtok($urlAbs, '?') ?: $urlAbs;
    $img = $o['image'] ?? setting('og_image');
    if ($img !== '' && !preg_match('#^https?://#', $img)) $img = upload_url($img);
    $type = $o['type'] ?? 'website';

    echo '<title>' . e($fullTitle) . "</title>\n";
    echo '<meta name="description" content="' . e($desc) . "\">\n";
    echo '<link rel="canonical" href="' . e($urlAbs) . "\">\n";
    echo '<meta property="og:site_name" content="' . e($siteName) . "\">\n";
    echo '<meta property="og:title" content="' . e($fullTitle) . "\">\n";
    echo '<meta property="og:description" content="' . e($desc) . "\">\n";
    echo '<meta property="og:type" content="' . e($type) . "\">\n";
    echo '<meta property="og:url" content="' . e($urlAbs) . "\">\n";
    echo '<meta property="og:locale" content="ckb_IQ">' . "\n";
    if ($img !== '') {
        echo '<meta property="og:image" content="' . e($img) . "\">\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    }
}

/** LocalBusiness structured data (home page). */
function jsonld_localbusiness(): void
{
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'HomeAndConstructionBusiness',
        'name' => setting('site_name', 'دیکۆراتی کەمال'),
        'alternateName' => setting('site_name_latin', 'Kamal Decoration'),
        'description' => setting('seo_description'),
        'telephone' => setting('phone'),
        'url' => site_base(),
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Ranya',
            'addressRegion' => 'Kurdistan Region',
            'addressCountry' => 'IQ',
        ],
    ];
    $logo = setting('logo_path');
    if ($logo !== '') $data['image'] = upload_url($logo);
    $maps = setting('maps_link');
    if ($maps !== '') $data['hasMap'] = $maps;
    echo '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>' . "\n";
}
