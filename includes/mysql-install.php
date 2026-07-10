<?php
declare(strict_types=1);

const HS_MYSQL_SCHEMA_VERSION = '2.0.0';

function hs_install_prefix_safe(string $prefix): string
{
    $prefix = preg_replace('/[^a-z0-9_]/i', '', $prefix) ?? 'hs_';
    return $prefix !== '' ? $prefix : 'hs_';
}

/** @return array{ok:bool,error:string,pdo:?PDO} */
function hs_install_db_connect(string $host, string $database, string $user, string $pass): array
{
    try {
        $pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4',
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return ['ok' => true, 'error' => '', 'pdo' => $pdo];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage(), 'pdo' => null];
    }
}

function hs_install_schema_path(string $appRoot): string
{
    foreach ([$appRoot . '/install/schema.sql', $appRoot . '/schema.sql'] as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }
    throw new RuntimeException('install/schema.sql not found.');
}

function hs_install_run_schema(PDO $pdo, string $prefix, string $schemaFile): void
{
    $sql = file_get_contents($schemaFile) ?: '';
    $sql = str_replace('{prefix}', hs_install_prefix_safe($prefix), $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
}

function hs_install_write_db_config(string $dataDir, array $cfg): bool
{
    if (!is_dir($dataDir) && !mkdir($dataDir, 0750, true)) {
        return false;
    }
    $php = "<?php\nreturn [\n"
        . "    'host' => " . var_export((string) ($cfg['host'] ?? 'localhost'), true) . ",\n"
        . "    'database' => " . var_export((string) ($cfg['database'] ?? ''), true) . ",\n"
        . "    'user' => " . var_export((string) ($cfg['user'] ?? ''), true) . ",\n"
        . "    'pass' => " . var_export((string) ($cfg['pass'] ?? ''), true) . ",\n"
        . "    'prefix' => " . var_export(hs_install_prefix_safe((string) ($cfg['prefix'] ?? 'hs_')), true) . ",\n"
        . "];\n";
    $file = $dataDir . '/db.config.php';
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        return false;
    }
    @chmod($file, 0640);
    return true;
}

function hs_install_write_admin_config(string $dataDir, string $user, string $pass): bool
{
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $php = "<?php\nreturn [\n"
        . "    'user' => " . var_export($user, true) . ",\n"
        . "    'password_hash' => " . var_export($hash, true) . ",\n"
        . "    'role' => 'super',\n"
        . "];\n";
    $file = $dataDir . '/admin.config.php';
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        return false;
    }
    @chmod($file, 0640);
    return true;
}

function hs_install_write_lock(string $lockFile, string $note = ''): bool
{
    $line = gmdate('c') . "\nHosting CMS " . HS_MYSQL_SCHEMA_VERSION . " — MySQL\n";
    if ($note !== '') {
        $line .= $note . "\n";
    }
    return file_put_contents($lockFile, $line, LOCK_EX) !== false;
}

function hs_install_lock_file(): string
{
    return HS_DATA_DIR . '/installed.lock';
}

function hs_install_mysql_ready(string $dataDir): bool
{
    return is_file($dataDir . '/db.config.php') && is_file(hs_install_lock_file());
}

/** @return list<array{ok:bool,label:string,hint:string}> */
function hs_install_requirements(string $dataDir, string $appRoot): array
{
    $checks = [
        ['ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'label' => 'PHP 8.0+', 'hint' => PHP_VERSION],
        ['ok' => extension_loaded('pdo'), 'label' => 'PDO', 'hint' => ''],
        ['ok' => extension_loaded('pdo_mysql'), 'label' => 'PDO MySQL', 'hint' => ''],
        ['ok' => extension_loaded('json'), 'label' => 'JSON', 'hint' => ''],
    ];
    $writable = is_dir($dataDir) ? is_writable($dataDir) : @mkdir($dataDir, 0750, true);
    $checks[] = ['ok' => $writable, 'label' => 'Writable data/', 'hint' => $dataDir];
    $pub = $appRoot . '/' . HS_PUBLIC_HTML;
    $pubOk = is_dir($pub) ? is_writable($pub) : @mkdir($pub, 0755, true);
    $checks[] = ['ok' => $pubOk, 'label' => 'Writable public_html/', 'hint' => ''];
    return $checks;
}

/** @return array{users:list<array>,sites:list<array>,user_settings:array<string,array>} */
function hs_install_demo_seed(): array
{
    $domain = function_exists('hs_default_primary_domain') ? hs_default_primary_domain() : 'bilohash.com';
    require_once __DIR__ . '/user-settings.php';
    $defaults = hs_user_settings_defaults();
    $users = [
        [
            'id' => 'u_demo',
            'email' => 'demo@' . $domain,
            'username' => 'demo',
            'password_hash' => password_hash('demo', PASSWORD_DEFAULT),
            'name' => 'Demo Client',
            'plan' => 'business',
            'subscription_status' => 'active',
            'paid_until' => gmdate('c', strtotime('+1 year')),
            'created_at' => gmdate('c'),
            'active' => true,
        ],
        [
            'id' => 'u_admin',
            'email' => 'admin@' . $domain,
            'username' => 'admin',
            'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
            'name' => 'Platform Admin',
            'plan' => 'pro',
            'subscription_status' => 'active',
            'paid_until' => gmdate('c', strtotime('+1 year')),
            'created_at' => gmdate('c'),
            'active' => true,
        ],
    ];
    $sites = [
        [
            'id' => 's_demo_welcome',
            'user_id' => 'u_demo',
            'slug' => 'welcome',
            'install_base' => 'demo',
            'title' => 'Welcome Site',
            'domain' => '',
            'app' => 'empty',
            'status' => 'active',
            'created_at' => gmdate('c'),
        ],
        [
            'id' => 's_admin_welcome',
            'user_id' => 'u_admin',
            'slug' => 'welcome',
            'install_base' => 'admin',
            'title' => 'Welcome Site',
            'domain' => '',
            'app' => 'empty',
            'status' => 'active',
            'created_at' => gmdate('c'),
        ],
    ];
    require_once __DIR__ . '/plan-specs.php';
    $srv = hs_server_constants();
    $now = gmdate('c');
    $dns = [
        ['type' => 'NS', 'host' => '@', 'value' => $srv['ns1'], 'ttl' => 14400, 'created_at' => $now],
        ['type' => 'NS', 'host' => '@', 'value' => $srv['ns2'], 'ttl' => 14400, 'created_at' => $now],
        ['type' => 'A', 'host' => '@', 'value' => $srv['ip'], 'ttl' => 14400, 'created_at' => $now],
        ['type' => 'A', 'host' => 'www', 'value' => $srv['ip'], 'ttl' => 14400, 'created_at' => $now],
    ];
    $demoSettings = array_merge($defaults, [
        'primary_domain' => $domain,
        'active_domain' => $domain,
        'ftp_password_token' => 'DemoFTP2026!',
        'dns_records' => $dns,
        'demo_panel_seeded' => true,
    ]);
    $settings = [
        'u_demo' => $demoSettings,
        'u_admin_client' => $demoSettings,
    ];
    return ['users' => $users, 'sites' => $sites, 'user_settings' => $settings];
}

function hs_install_import_seed(PDO $pdo, string $prefix, array $seed): void
{
    $pfx = hs_install_prefix_safe($prefix);

    $stmtUser = $pdo->prepare('INSERT INTO `' . $pfx . 'users` (`id`, `data`) VALUES (?, ?)');
    foreach ($seed['users'] ?? [] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = (string) ($row['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $json = json_encode($row, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('JSON encode failed for user ' . $id);
        }
        $stmtUser->execute([$id, $json]);
    }

    $stmtSite = $pdo->prepare('INSERT INTO `' . $pfx . 'sites` (`id`, `data`) VALUES (?, ?)');
    foreach ($seed['sites'] ?? [] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = (string) ($row['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $json = json_encode($row, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('JSON encode failed for site ' . $id);
        }
        $stmtSite->execute([$id, $json]);
    }

    $stmtSet = $pdo->prepare('INSERT INTO `' . $pfx . 'user_settings` (`id`, `data`) VALUES (?, ?)');
    foreach ($seed['user_settings'] ?? [] as $uid => $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = (string) $uid;
        if ($id === '') {
            continue;
        }
        $json = json_encode($row, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('JSON encode failed for settings ' . $id);
        }
        $stmtSet->execute([$id, $json]);
    }

    $meta = $pdo->prepare('INSERT INTO `' . $pfx . 'meta` (`meta_key`, `meta_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `meta_value` = VALUES(`meta_value`)');
    $meta->execute(['schema_version', HS_MYSQL_SCHEMA_VERSION]);
    $meta->execute(['installed_at', gmdate('c')]);
    $meta->execute(['license_started_at', gmdate('c')]);
    $meta->execute(['license_demo_days', '30']);
}

function hs_install_create_demo_files(string $appRoot): void
{
    require_once __DIR__ . '/installer.php';
    $html = hs_welcome_index_html();
    foreach (['demo/welcome', 'admin/welcome'] as $rel) {
        $path = $appRoot . '/' . HS_PUBLIC_HTML . '/' . $rel;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $index = $path . '/index.html';
        if (!is_file($index)) {
            file_put_contents($index, $html);
        }
    }
}

/** @return array{ok:bool,error:string,admin_user?:string} */
function hs_install_run(array $post, string $appRoot, string $dataDir): array
{
    $host = trim((string) ($post['db_host'] ?? 'localhost'));
    $database = trim((string) ($post['db_name'] ?? ''));
    $user = trim((string) ($post['db_user'] ?? ''));
    $pass = (string) ($post['db_pass'] ?? '');
    $prefix = (string) ($post['db_prefix'] ?? 'hs_');
    $adminUser = trim((string) ($post['admin_user'] ?? 'admin'));
    $adminPass = (string) ($post['admin_pass'] ?? '');
    $seedDemo = !isset($post['seed_demo']) || !empty($post['seed_demo']);

    if ($database === '' || $user === '') {
        return ['ok' => false, 'error' => 'Вкажіть ім\'я бази та користувача MySQL.'];
    }
    if ($adminUser === '' || strlen($adminPass) < 6) {
        return ['ok' => false, 'error' => 'Логін адміна панелі та пароль (мін. 6 символів) обов\'язкові.'];
    }

    $conn = hs_install_db_connect($host, $database, $user, $pass);
    if (!$conn['ok'] || !$conn['pdo'] instanceof PDO) {
        return ['ok' => false, 'error' => 'Помилка підключення MySQL: ' . ($conn['error'] ?? '')];
    }

    if (!hs_install_write_db_config($dataDir, [
        'host' => $host,
        'database' => $database,
        'user' => $user,
        'pass' => $pass,
        'prefix' => $prefix,
    ])) {
        return ['ok' => false, 'error' => 'Не вдалося записати data/db.config.php'];
    }

    try {
        $schema = hs_install_schema_path($appRoot);
        hs_install_run_schema($conn['pdo'], $prefix, $schema);
        if ($seedDemo) {
            hs_install_import_seed($conn['pdo'], $prefix, hs_install_demo_seed());
            hs_install_create_demo_files($appRoot);
        }
    } catch (Throwable $e) {
        @unlink($dataDir . '/db.config.php');
        return ['ok' => false, 'error' => 'Помилка схеми/імпорту: ' . $e->getMessage()];
    }

    if (!hs_install_write_admin_config($dataDir, $adminUser, $adminPass)) {
        @unlink($dataDir . '/db.config.php');
        return ['ok' => false, 'error' => 'Не вдалося записати data/admin.config.php'];
    }

    if (!hs_install_write_lock(hs_install_lock_file(), 'Fresh MySQL install')) {
        return ['ok' => false, 'error' => 'Не вдалося створити installed.lock'];
    }

    return ['ok' => true, 'error' => '', 'admin_user' => $adminUser];
}