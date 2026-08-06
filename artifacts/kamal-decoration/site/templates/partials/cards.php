<?php
/** Reusable card renderers for listing pages + home. */

function section_head(string $title, ?string $sub = null, ?string $moreUrl = null): void
{
    echo '<div class="section-head reveal">';
    echo '<div><h2 class="section-title">' . e($title) . '</h2>';
    if ($sub) echo '<p class="section-sub">' . e($sub) . '</p>';
    echo '</div>';
    if ($moreUrl) {
        echo '<a class="btn btn-ghost" href="' . e($moreUrl) . '">' . e(t('btn_view_all')) . ' <span class="arr">←</span></a>';
    }
    echo '</div>';
}

function render_category_card(array $c): void
{
    $href = url('category/' . rawurlencode($c['slug']));
    echo '<a class="cat-card reveal" href="' . e($href) . '">';
    if (!empty($c['image'])) {
        echo '<img src="' . e(upload_url($c['image'])) . '" alt="' . e($c['name']) . '" loading="lazy">';
    }
    echo '<span class="cat-overlay"></span>';
    echo '<span class="cat-name">' . e($c['name']) . '</span>';
    echo '</a>';
}

function render_product_card(array $p): void
{
    $href = url('product/' . rawurlencode($p['slug']));
    echo '<article class="card product-card reveal">';
    echo '<a class="card-media" href="' . e($href) . '">';
    if (!empty($p['main_image'])) {
        echo '<img src="' . e(upload_url($p['main_image'])) . '" alt="' . e($p['name']) . '" loading="lazy">';
    }
    if (empty($p['is_available'])) {
        echo '<span class="badge badge-muted">' . e(t('product_unavailable')) . '</span>';
    }
    echo '</a><div class="card-body">';
    if (!empty($p['category_name'])) {
        echo '<span class="card-kicker">' . e($p['category_name']) . '</span>';
    }
    echo '<h3 class="card-title"><a href="' . e($href) . '">' . e($p['name']) . '</a></h3>';
    if (!empty($p['short_desc'])) {
        echo '<p class="card-text">' . e(excerpt_of($p['short_desc'], 90)) . '</p>';
    }
    echo '<div class="card-foot">';
    if (setting('show_prices', '1') === '1' && $p['price'] !== null && $p['price'] !== '') {
        echo '<span class="price">' . e(money($p['price']))
            . (!empty($p['unit']) ? ' <small>' . e($p['unit']) . '</small>' : '') . '</span>';
    } else {
        echo '<span class="price price-ask">' . e(t('product_price_ask')) . '</span>';
    }
    echo '<a class="btn btn-small" href="' . e($href) . '">' . e(t('btn_details')) . '</a>';
    echo '</div></div></article>';
}

/** $shadeHexes: ordered hex list for the strip. */
function render_palette_card(array $pal, array $shadeHexes, int $shadeCount): void
{
    $href = url('palette/' . rawurlencode($pal['slug']));
    echo '<a class="card palette-card reveal" href="' . e($href) . '">';
    echo '<span class="card-media">';
    if (!empty($pal['cover_image'])) {
        echo '<img src="' . e(upload_url($pal['cover_image'])) . '" alt="' . e($pal['name']) . '" loading="lazy">';
    }
    echo '</span>';
    echo '<span class="palette-strip">';
    foreach ($shadeHexes as $hex) {
        echo '<span style="background:' . e($hex) . '"></span>';
    }
    echo '</span>';
    echo '<span class="card-body">';
    echo '<span class="card-title">' . e($pal['name']) . '</span>';
    echo '<span class="palette-meta"><span class="chip chip-code" dir="ltr">' . e($pal['code']) . '</span>'
       . '<span class="muted">' . $shadeCount . ' ' . e(t('palette_shades_count')) . '</span></span>';
    echo '</span></a>';
}

function render_shade_tile(array $s): void
{
    $href = url('shade/' . rawurlencode($s['slug']));
    $hex = $s['hex_color'] ?: '#CCCCCC';
    echo '<a class="shade-tile reveal" href="' . e($href) . '">';
    echo '<span class="shade-swatch" style="background:' . e($hex) . '"></span>';
    echo '<span class="shade-info">';
    echo '<span class="shade-name">' . e($s['name']) . '</span>';
    echo '<span class="shade-code" dir="ltr">' . e($s['code']) . '</span>';
    echo '</span></a>';
}

function render_project_card(array $pr): void
{
    $href = url('project/' . rawurlencode($pr['slug']));
    echo '<article class="card project-card reveal">';
    echo '<a class="card-media" href="' . e($href) . '">';
    if (!empty($pr['main_image'])) {
        echo '<img src="' . e(upload_url($pr['main_image'])) . '" alt="' . e($pr['title']) . '" loading="lazy">';
    }
    if (!empty($pr['before_image']) && !empty($pr['after_image'])) {
        echo '<span class="badge badge-gold">' . e(t('project_before_after')) . '</span>';
    }
    echo '</a><div class="card-body">';
    echo '<h3 class="card-title"><a href="' . e($href) . '">' . e($pr['title']) . '</a></h3>';
    echo '<div class="card-meta">';
    if (!empty($pr['location'])) echo '<span>📍 ' . e($pr['location']) . '</span>';
    if (!empty($pr['completed_at'])) echo '<span>🗓 ' . e(kdate($pr['completed_at'])) . '</span>';
    echo '</div>';
    echo '</div></article>';
}

function render_post_card(array $po): void
{
    $href = url('post/' . rawurlencode($po['slug']));
    echo '<article class="card post-card reveal">';
    echo '<a class="card-media" href="' . e($href) . '">';
    if (!empty($po['cover_image'])) {
        echo '<img src="' . e(upload_url($po['cover_image'])) . '" alt="' . e($po['title']) . '" loading="lazy">';
    }
    echo '</a><div class="card-body">';
    if (!empty($po['published_at'])) {
        echo '<span class="card-kicker">' . e(kdate($po['published_at'])) . '</span>';
    }
    echo '<h3 class="card-title"><a href="' . e($href) . '">' . e($po['title']) . '</a></h3>';
    if (!empty($po['excerpt'])) {
        echo '<p class="card-text">' . e(excerpt_of($po['excerpt'], 110)) . '</p>';
    }
    echo '<span class="link-more">' . e(t('btn_more')) . ' ←</span>';
    echo '</div></article>';
}

function render_testimonial(array $tst): void
{
    echo '<figure class="testimonial reveal">';
    echo '<div class="stars" dir="ltr">' . str_repeat('★', max(1, min(5, (int)$tst['rating'])))
       . str_repeat('☆', 5 - max(1, min(5, (int)$tst['rating']))) . '</div>';
    echo '<blockquote>' . e($tst['quote']) . '</blockquote>';
    echo '<figcaption>' . e($tst['name']);
    if (!empty($tst['location'])) echo '<span> — ' . e($tst['location']) . '</span>';
    echo '</figcaption></figure>';
}

function render_breadcrumbs(array $items): void
{
    echo '<nav class="breadcrumbs" aria-label="breadcrumb"><div class="container">';
    echo '<a href="' . e(url('')) . '">' . e(t('breadcrumb_home')) . '</a>';
    foreach ($items as $it) {
        echo '<span class="bc-sep">/</span>';
        if (isset($it['url'])) echo '<a href="' . e($it['url']) . '">' . e($it['label']) . '</a>';
        else echo '<span class="bc-current">' . e($it['label']) . '</span>';
    }
    echo '</div></nav>';
}
