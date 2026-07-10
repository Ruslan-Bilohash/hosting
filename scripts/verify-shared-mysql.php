<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/config.php';
require $root . '/includes/database.php';
require $root . '/includes/mysql-provision.php';
require $root . '/includes/user-settings.php';
require $root . '/includes/storage.php';

echo 'mode=' . hs_mysql_provision_mode() . "\n";
echo 'enabled=' . (hs_mysql_provision_enabled() ? 'yes' : 'no') . "\n";
echo 'ssh_set=' . (HS_SSH_PASSWORD_SET ? 'yes' : 'no') . "\n";
echo 'ssh_pass=' . (hs_ssh_password_available() ? 'yes' : 'no') . "\n";

$u = hs_user_by_login('demo');
if (!$u) {
    echo "demo_user=missing\n";
    exit(1);
}
$uid = (string) $u['id'];
$s = hs_user_settings_get($uid);
echo 'demo_dbs=' . json_encode($s['databases'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

if (!empty($s['db_provision_failed'])) {
    hs_user_settings_save($uid, ['db_provision_failed' => false]);
    $s = hs_user_settings_get($uid);
    echo "cleared_provision_failed\n";
}
if (($s['databases'] ?? []) === []) {
    $res = hs_ensure_user_database($uid, 'demo', $u);
    echo 'provision=' . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
    $s = hs_user_settings_get($uid);
    echo 'demo_dbs_after=' . json_encode($s['databases'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
}

$legacyCfg = $root . '/public_html/demo/config.php';
$secureCfg = $root . '/data/client-db/demo.php';
echo 'demo_config_legacy=' . (is_file($legacyCfg) ? 'yes' : 'no') . "\n";
echo 'demo_config_secure=' . (is_file($secureCfg) ? 'yes' : 'no') . "\n";