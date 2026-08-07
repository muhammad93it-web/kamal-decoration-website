<?php
/**
 * دیکۆراتی ڕەوەند — فایلی ڕێکخستنی نموونە
 * ئەم فایلە کۆپی بکە بە ناوی config.php لە هەمان فۆڵدەر، پاشان زانیارییەکانی بنکەدراوەکەت بنووسە.
 *
 * RAWAND DECORATION — example configuration file.
 * Copy this file to config.php in the same folder and fill in your database details.
 */

// ───── بنکەدراوە (MySQL) ─────
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// ───── ناونیشانی ماڵپەڕ ─────
// بۆ نموونە: https://rawanddecoration.com  (بەتاڵی بهێڵەوە بۆ دۆزینەوەی خۆکارانە)
define('SITE_URL', '');

// ───── دۆخی گەشەپێدان ─────
// true تەنها لە کاتی چاککردندا — هەڵەکان پیشان دەدات
define('APP_DEBUG', false);

// ───── ڕێچکەکان ─────
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('LOG_DIR', BASE_PATH . '/logs');
