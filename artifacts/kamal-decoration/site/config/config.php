<?php
/**
 * دیکۆراتی کەمال — فایلی ڕێکخستنی نموونە
 * ئەم فایلە کۆپی بکە بە ناوی config.php لە هەمان فۆڵدەر، پاشان زانیارییەکانی بنکەدراوەکەت بنووسە.
 *
 * KAMAL DECORATION — example configuration file.
 * Copy this file to config.php in the same folder and fill in your database details.
 */

// ───── بنکەدراوە (MySQL) ─────
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'kamal_decoration');
define('DB_USER', 'kamal');
define('DB_PASS', 'kamal_dev_pass');

// ───── ناونیشانی ماڵپەڕ ─────
// بۆ نموونە: https://kamaldecoration.com  (بەتاڵی بهێڵەوە بۆ دۆزینەوەی خۆکارانە)
define('SITE_URL', '');

// ───── دۆخی گەشەپێدان ─────
// true تەنها لە کاتی چاککردندا — هەڵەکان پیشان دەدات
define('APP_DEBUG', true);

// ───── ڕێچکەکان ─────
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('LOG_DIR', BASE_PATH . '/logs');
