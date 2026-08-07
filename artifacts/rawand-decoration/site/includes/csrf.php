<?php
/** CSRF protection + honeypot helpers. */

function csrf_token(): string
{
    if (empty($_SESSION['kd_csrf'])) {
        $_SESSION['kd_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['kd_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals($_SESSION['kd_csrf'] ?? '', $sent);
}

/** Verify CSRF on POST or stop with 419. */
function csrf_verify(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_ok()) {
        http_response_code(419);
        die('<meta charset="utf-8">' . e(t('csrf_invalid', 'داواکارییەکە بەسەرچووە — تکایە لاپەڕەکە نوێ بکەوە')));
    }
}

/** Honeypot field (hidden from humans; bots fill it). */
function hp_field(): string
{
    return '<div class="hp-wrap" aria-hidden="true"><label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>';
}

function hp_ok(): bool
{
    return ($_POST['website_url'] ?? '') === '';
}
