<?php
/** Database backup — SUPER ADMIN only. Streams a full SQL dump (pure PHP, shared-hosting safe). */
require __DIR__ . '/includes/admin-bootstrap.php';
require_super();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'download') {
    csrf_verify();
    log_activity('backup', 'database', null, 'download');

    $pdo = db();
    $fname = 'rawand-backup-' . date('Ymd-His') . '.sql';

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('X-Content-Type-Options: nosniff');
    while (ob_get_level()) ob_end_clean();

    echo "-- دیکۆراتی ڕەوەند — باکئەپی داتابەیس\n";
    echo '-- ' . date('Y-m-d H:i:s') . "\n\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) {
        $create = $pdo->query("SHOW CREATE TABLE `$tbl`")->fetch();
        echo "DROP TABLE IF EXISTS `$tbl`;\n";
        echo ($create['Create Table'] ?? array_values((array)$create)[1]) . ";\n\n";

        $st = $pdo->query("SELECT * FROM `$tbl`");
        $batch = [];
        $cols = null;
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) {
                $cols = '`' . implode('`, `', array_keys($row)) . '`';
            }
            $vals = array_map(function ($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote((string)$v);
            }, array_values($row));
            $batch[] = '(' . implode(', ', $vals) . ')';
            if (count($batch) >= 200) {
                echo "INSERT INTO `$tbl` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n";
                $batch = [];
                flush();
            }
        }
        if ($batch && $cols !== null) {
            echo "INSERT INTO `$tbl` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n";
        }
        echo "\n";
        flush();
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n-- تەواو\n";
    exit;
}

$stats = [];
foreach (['products', 'palette_shades', 'projects', 'posts', 'contact_messages', 'media'] as $tbl) {
    $stats[$tbl] = (int)db()->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
}

admin_header(t('a_backups', 'باکئەپ'), 'backups');
?>

<div class="help-box">
  💡 <?= e(t('a_bk_help', 'ئەم فایلە هەموو داتاکانی ماڵپەڕەکە لەخۆدەگرێت (بەرهەم، ڕەنگ، پرۆژە، پەیام…). بە بەردەوامی دایبگرە و لە شوێنێکی سەلامەت هەڵیبگرە. وێنەکان لە فۆڵدەری uploads دان — لە cPanel جیا کۆپییان بکە.')) ?>
</div>

<div class="stat-grid" style="margin-bottom:18px">
  <div class="stat"><div class="stat-num"><?= e(knum($stats['products'])) ?></div><div class="stat-label"><?= e(t('a_products', 'بەرهەمەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($stats['palette_shades'])) ?></div><div class="stat-label"><?= e(t('a_shades', 'ڕەنگەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($stats['projects'])) ?></div><div class="stat-label"><?= e(t('a_projects', 'پرۆژەکان')) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e(knum($stats['contact_messages'])) ?></div><div class="stat-label"><?= e(t('a_messages', 'پەیامەکان')) ?></div></div>
</div>

<div class="panel" style="max-width:640px">
  <h2 class="panel-title"><?= e(t('a_bk_download', 'داگرتنی باکئەپی داتابەیس')) ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="download">
    <button class="btn btn-gold" type="submit">⬇️ <?= e(t('a_bk_btn', 'داگرتنی فایلی SQL')) ?></button>
  </form>
  <div class="f-hint" style="margin-top:10px"><?= e(t('a_bk_restore', 'گەڕاندنەوە: لە phpMyAdmin داتابەیسەکە هەڵبژێرە و لە بەشی Import ئەم فایلە باربکە.')) ?></div>
</div>

<?php admin_footer(); ?>
