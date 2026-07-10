<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/config.php';
require $root . '/includes/database.php';
require $root . '/includes/mysql-provision.php';

echo 'installed=' . (hs_is_mysql_installed() ? 'yes' : 'no') . PHP_EOL;
echo 'provision=' . (hs_mysql_provision_enabled() ? 'yes' : 'no') . PHP_EOL;
$cfg = hs_mysql_provision_config();
if ($cfg) {
    echo 'host=' . ($cfg['host'] ?? '') . ' port=' . ($cfg['port'] ?? 3306) . PHP_EOL;
    echo 'client_host=' . hs_mysql_provision_client_host() . PHP_EOL;
}
$pdo = hs_mysql_provision_admin_pdo();
echo 'admin_pdo=' . ($pdo ? 'ok' : 'fail') . PHP_EOL;
if ($pdo) {
    $test = hs_mysql_provision_test();
    echo 'create_test=' . ($test['ok'] ? 'ok' : 'fail:' . $test['error']) . PHP_EOL;
}