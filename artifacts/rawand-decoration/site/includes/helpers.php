<?php
/** General helper functions — no DB required at load time. */

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    return false;
}

/** Best-effort absolute base URL (scheme + host), no trailing slash. */
function base_url_guess(): string
{
    if (PHP_SAPI === 'cli') return '';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = trim(explode(',', $host)[0]);
    // guard against host-header poisoning: strict charset or fall back
    if (!preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/i', $host)) $host = 'localhost';
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

/** Site base URL (no trailing slash). Priority: config SITE_URL → setting('site_url') → auto. */
function site_base(): string
{
    static $base = null;
    if ($base !== null) return $base;
    if (defined('SITE_URL') && SITE_URL !== '') return $base = rtrim(SITE_URL, '/');
    if (function_exists('setting')) {
        $s = setting('site_url');
        if ($s !== '') return $base = rtrim($s, '/');
    }
    return $base = base_url_guess();
}

/** Path prefix of the configured base URL ('' when installed at the domain root). */
function site_path(): string
{
    static $p = null;
    if ($p !== null) return $p;
    $path = (string)(parse_url(site_base(), PHP_URL_PATH) ?? '');
    return $p = rtrim($path, '/');
}

/**
 * Origin-relative URL (e.g. "/shade/rose"). Keeps links, CSS, and fonts
 * same-origin no matter which host/proxy serves the page — critical behind
 * Replit's preview proxy and harmless on shared hosting.
 */
function url(string $path = ''): string
{
    return site_path() . '/' . ltrim($path, '/');
}

/** Absolute URL — only for QR codes, sitemap, canonical/OG tags, share links. */
function abs_url(string $path = ''): string
{
    return site_base() . '/' . ltrim($path, '/');
}

/** Make any app URL absolute (leaves already-absolute URLs untouched). */
function absolutize(string $u): string
{
    if ($u === '' || preg_match('#^https?://#i', $u)) return $u;
    if ($u[0] === '/') {
        $base = site_base();
        $path = rtrim((string)(parse_url($base, PHP_URL_PATH) ?? ''), '/');
        $origin = $path !== '' ? substr($base, 0, strlen($base) - strlen($path)) : $base;
        return $origin . $u;
    }
    return abs_url($u);
}

function asset(string $rel): string
{
    return url('assets/' . ltrim($rel, '/'));
}

function upload_url(string $rel): string
{
    $rel = str_replace(['..', "\0", '\\'], '', $rel);
    return url('uploads/' . ltrim($rel, '/'));
}

/** Absolute filesystem path of an uploads-relative file — null unless it exists safely under uploads/. */
function upload_abs_path(string $rel): ?string
{
    $rel = trim(str_replace('\\', '/', $rel));
    if ($rel === '' || $rel[0] === '/' || str_contains($rel, "\0") || str_contains($rel, '..')) return null;
    $real = realpath(UPLOAD_DIR . '/' . $rel);
    $base = realpath(UPLOAD_DIR);
    if ($real === false || $base === false) return null;
    return str_starts_with($real, $base . DIRECTORY_SEPARATOR) ? $real : null;
}

/** Thumbnail URL if one exists, else the original image. */
function thumb_url(string $rel): string
{
    $rel = ltrim($rel, '/');
    $thumbRel = 'thumbnails/' . str_replace('/', '_', $rel);
    if (defined('UPLOAD_DIR') && is_file(UPLOAD_DIR . '/' . $thumbRel)) {
        return upload_url($thumbRel);
    }
    return upload_url($rel);
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['kd_flash'][] = ['type' => $type, 'msg' => $msg];
}

/** @return array<int, array{type: string, msg: string}> */
function flash_get(): array
{
    $f = $_SESSION['kd_flash'] ?? [];
    unset($_SESSION['kd_flash']);
    return is_array($f) ? $f : [];
}

/** URL-safe slug. Keeps Kurdish/Arabic letters (URLs encode them transparently). */
function slugify(string $text, string $fallbackPrefix = 'item'): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text);
    $text = trim((string)$text, '-');
    $text = preg_replace('/-{2,}/', '-', (string)$text);
    if ($text === '' || $text === null) {
        $text = $fallbackPrefix . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
    }
    return mb_substr($text, 0, 190, 'UTF-8');
}

/** Kurdish month names. */
function kd_months(): array
{
    return ['کانوونی دووەم','شوبات','ئازار','نیسان','ئایار','حوزەیران','تەممووز','ئاب','ئەیلوول','تشرینی یەکەم','تشرینی دووەم','کانوونی یەکەم'];
}

/** Format a date/datetime in Kurdish: 14 ی ئایار 2026 */
function kdate(?string $dt): string
{
    if (!$dt) return '';
    $ts = strtotime($dt);
    if (!$ts) return '';
    $m = kd_months()[(int)date('n', $ts) - 1];
    return date('j', $ts) . 'ی ' . $m . ' ' . date('Y', $ts);
}

function knum($n): string
{
    return number_format((float)$n, 0, '.', ',');
}

function money($amount): string
{
    $sym = function_exists('setting') ? setting('currency_symbol', 'د.ع') : 'د.ع';
    return knum($amount) . ' ' . $sym;
}

/** Normalize search text: Arabic → Kurdish letter forms, collapse spaces, lowercase. */
function normalize_text(string $s): string
{
    $map = [
        'ي' => 'ی', 'ك' => 'ک', 'ى' => 'ی', 'ة' => 'ە',
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ؤ' => 'و',
        'ـ' => '', // tatweel
    ];
    $s = strtr($s, $map);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return (string)$s;
}

function excerpt_of(?string $text, int $len = 160): string
{
    $t = trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
    if (mb_strlen($t, 'UTF-8') <= $len) return $t;
    return mb_substr($t, 0, $len, 'UTF-8') . '…';
}

function get_ip(): string
{
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($xff !== '') {
        $ip = trim(explode(',', $xff)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function is_bot(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $ua === '' || preg_match('/bot|crawl|spider|slurp|curl|wget|facebookexternalhit|preview/i', $ua) === 1;
}

/** WhatsApp deep link with prefilled text. */
function wa_link(string $text = ''): string
{
    $num = function_exists('setting') ? setting('whatsapp') : '';
    $num = preg_replace('/\D+/', '', $num);
    $u = 'https://wa.me/' . $num;
    if ($text !== '') $u .= '?text=' . rawurlencode($text);
    return $u;
}

/** Replace {placeholders} in a translated template string. */
function t_replace(string $template, array $vars): string
{
    foreach ($vars as $k => $v) $template = str_replace('{' . $k . '}', $v, $template);
    return $template;
}

/** Simple pagination calculator. */
function paginate(int $total, int $perPage, int $current): array
{
    $pages = max(1, (int)ceil($total / max(1, $perPage)));
    $current = min(max(1, $current), $pages);
    return [
        'total' => $total, 'per_page' => $perPage, 'pages' => $pages,
        'current' => $current, 'offset' => ($current - 1) * $perPage,
    ];
}

/** Render pagination links preserving existing query args. */
function render_pagination(array $p, string $baseUrl): string
{
    if ($p['pages'] <= 1) return '';
    $qs = $_GET;
    $link = function (int $page, string $label, bool $active = false, bool $disabled = false) use ($qs, $baseUrl) {
        $qs['page'] = $page;
        $href = $baseUrl . '?' . http_build_query($qs);
        $cls = 'page-btn' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '');
        return $disabled
            ? '<span class="' . $cls . '">' . $label . '</span>'
            : '<a class="' . $cls . '" href="' . e($href) . '">' . $label . '</a>';
    };
    $h = '<nav class="pagination" aria-label="pagination">';
    $h .= $link($p['current'] - 1, '‹ ' . t('pagination_prev'), false, $p['current'] <= 1);
    $start = max(1, $p['current'] - 2);
    $end = min($p['pages'], $p['current'] + 2);
    if ($start > 1) $h .= $link(1, '1') . ($start > 2 ? '<span class="page-dots">…</span>' : '');
    for ($i = $start; $i <= $end; $i++) $h .= $link($i, (string)$i, $i === $p['current']);
    if ($end < $p['pages']) $h .= ($end < $p['pages'] - 1 ? '<span class="page-dots">…</span>' : '') . $link($p['pages'], (string)$p['pages']);
    $h .= $link($p['current'] + 1, t('pagination_next') . ' ›', false, $p['current'] >= $p['pages']);
    $h .= '</nav>';
    return $h;
}
