<?php
/** Visitor analytics: page views, search log, scan log. Failures are silent. */

function track_page_view(?string $pageType = null, ?int $targetId = null): void
{
    try {
        if (is_bot() || is_logged_in()) return;
        $st = db()->prepare(
            'INSERT INTO page_views (path, page_type, target_id, ip, user_agent) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([
            mb_substr($_SERVER['REQUEST_URI'] ?? '/', 0, 300),
            $pageType,
            $targetId,
            get_ip(),
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {}
}

function log_search(string $query, string $normalized, int $results): void
{
    try {
        if (is_bot()) return;
        $st = db()->prepare('INSERT INTO searches (query, normalized, results, ip) VALUES (?, ?, ?, ?)');
        $st->execute([mb_substr($query, 0, 300), mb_substr($normalized, 0, 300), $results, get_ip()]);
    } catch (Throwable $e) {}
}

function log_scan(string $code, ?string $targetType, ?int $targetId, bool $found): void
{
    try {
        $st = db()->prepare(
            'INSERT INTO scan_logs (code, target_type, target_id, found, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            mb_substr($code, 0, 60),
            $targetType,
            $targetId,
            $found ? 1 : 0,
            get_ip(),
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {}
}

/** Increment a views counter column safely. */
function bump_views(string $table, int $id): void
{
    try {
        if (is_bot() || is_logged_in()) return;
        if (!in_array($table, ['products', 'projects', 'posts'], true)) return;
        db()->prepare("UPDATE {$table} SET views = views + 1 WHERE id = ?")->execute([$id]);
    } catch (Throwable $e) {}
}
