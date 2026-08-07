<?php
/**
 * DEV ONLY (Replit harness): writes config/config.php + install.lock non-interactively.
 * Usage: php make-dev-config.php <dbname> <dbuser> <dbpass> [host] [port]
 * The production path is the Kurdish web installer at /install.
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");

$root = dirname(__DIR__);
[$dbname, $dbuser, $dbpass] = [($argv[1] ?? 'rawand_decoration'), ($argv[2] ?? 'rawand'), ($argv[3] ?? 'rawand_dev_pass')];
$host = $argv[4] ?? '127.0.0.1';
$port = $argv[5] ?? '3308';

$tpl = file_get_contents($root . '/config/config.example.php');
if ($tpl === false) exit("config.example.php missing\n");

$rep = [
    "define('DB_HOST', 'localhost');" => "define('DB_HOST', " . var_export($host, true) . ');',
    "define('DB_PORT', '3306');" => "define('DB_PORT', " . var_export((string)$port, true) . ');',
    "define('DB_NAME', 'your_database_name');" => "define('DB_NAME', " . var_export($dbname, true) . ');',
    "define('DB_USER', 'your_database_user');" => "define('DB_USER', " . var_export($dbuser, true) . ');',
    "define('DB_PASS', 'your_database_password');" => "define('DB_PASS', " . var_export($dbpass, true) . ');',
    "define('APP_DEBUG', false);" => "define('APP_DEBUG', true);",
];
$cfg = strtr($tpl, $rep);

file_put_contents($root . '/config/config.php', $cfg);
file_put_contents($root . '/config/install.lock', date('c') . " dev\n");
echo "[dev] wrote config/config.php + install.lock\n";
