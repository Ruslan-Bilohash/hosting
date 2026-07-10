<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$data = $root . '/data';
$db = require $data . '/db.config.php';
$pass = (string) ($db['pass'] ?? $db['password'] ?? '');
$cfg = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => (string) ($db['user'] ?? ''),
    'pass' => $pass,
    'db_prefix' => 'hs_',
    'name_prefix' => 'u762384583_',
    'client_host' => 'localhost',
    'grant_host' => 'localhost',
    'mode' => 'shared',
    'shared_database' => (string) ($db['database'] ?? $db['dbname'] ?? ''),
    'shared_user' => (string) ($db['user'] ?? ''),
    'shared_pass' => $pass,
];
$out = "<?php\n/** Auto — shared MySQL for Hostinger */\nreturn " . var_export($cfg, true) . ";\n";
file_put_contents($data . '/mysql-provision.config.php', $out);
chmod($data . '/mysql-provision.config.php', 0640);
echo "PROVISION_OK\n";

require $root . '/config.php';
require $root . '/includes/database.php';
require $root . '/includes/mysql-provision.php';
require $root . '/includes/user-settings.php';
require $root . '/includes/storage.php';

$test = hs_mysql_provision_test();
echo 'TEST=' . (!empty($test['ok']) ? 'ok' : 'fail:' . ($test['error'] ?? '')) . "\n";

require $root . '/includes/client-auth.php';
foreach (hs_users() as $u) {
    if (($u['subscription_status'] ?? '') !== 'active') {
        continue;
    }
    $uid = (string) ($u['id'] ?? '');
    $uname = (string) ($u['username'] ?? 'user');
    if ($uid === '') {
        continue;
    }
    hs_user_settings_save($uid, ['databases' => [], 'db_provision_failed' => false]);
    $res = hs_ensure_user_database($uid, $uname, $u);
    $status = !empty($res['ok']) ? 'ok' : ('fail:' . ($res['error'] ?? ''));
    $entry = is_array($res['entry'] ?? null) ? $res['entry'] : [];
    echo 'USER ' . $uname . ' => ' . $status . ' ' . ($entry['logical_name'] ?? $entry['name'] ?? '') . "\n";
}