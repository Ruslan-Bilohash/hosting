<?php
declare(strict_types=1);

$dataDir = dirname(__DIR__) . '/data';
$dbFile = $dataDir . '/db.config.php';
$outFile = $dataDir . '/mysql-provision.config.php';

if (!is_readable($dbFile)) {
    fwrite(STDERR, "Missing db.config.php\n");
    exit(1);
}
if (is_file($outFile)) {
    echo "mysql-provision.config.php already exists\n";
    exit(0);
}
$db = require $dbFile;
$php = "<?php\nreturn [\n"
    . "    'host' => " . var_export((string) ($db['host'] ?? 'localhost'), true) . ",\n"
    . "    'port' => 3306,\n"
    . "    'user' => " . var_export((string) ($db['user'] ?? ''), true) . ",\n"
    . "    'pass' => " . var_export((string) ($db['pass'] ?? ''), true) . ",\n"
    . "    'db_prefix' => 'hs_',\n"
    . "    'name_prefix' => '',\n"
    . "    'client_host' => " . var_export((string) ($db['host'] ?? 'localhost'), true) . ",\n"
    . "    'grant_host' => 'localhost',\n"
    . "];\n";
file_put_contents($outFile, $php);
chmod($outFile, 0640);
echo "Written mysql-provision.config.php (WARNING: CMS user may lack CREATE DATABASE — use admin/mysql.php)\n";