<?php
declare(strict_types=1);

function hs_mysql_provision_config_file(): string
{
    return HS_DATA_DIR . '/mysql-provision.config.php';
}

/** @return array<string, mixed>|null */
function hs_mysql_provision_config(): ?array
{
    static $cache = false;
    static $mtime = -1;
    $file = hs_mysql_provision_config_file();
    if (!is_readable($file)) {
        return null;
    }
    $fm = filemtime($file) ?: 0;
    if ($cache !== false && $mtime === $fm) {
        return $cache;
    }
    $data = require $file;
    $cache = is_array($data) ? $data : null;
    $mtime = $fm;
    return $cache;
}

function hs_mysql_provision_enabled(): bool
{
    if (!hs_is_mysql_installed() || hs_mysql_provision_config() === null) {
        return false;
    }
    if (hs_mysql_provision_shared_mode()) {
        return hs_mysql_provision_shared_database() !== '' && hs_mysql_provision_shared_user() !== '';
    }
    return hs_mysql_provision_admin_pdo() instanceof PDO;
}

function hs_mysql_provision_port(): int
{
    $cfg = hs_mysql_provision_config();
    $port = (int) ($cfg['port'] ?? 3306);
    return $port > 0 && $port < 65536 ? $port : 3306;
}

function hs_mysql_build_dsn(string $host, int $port, ?string $database = null): string
{
    $dsn = 'mysql:host=' . $host;
    if ($port !== 3306) {
        $dsn .= ';port=' . $port;
    }
    if ($database !== null && $database !== '') {
        $dsn .= ';dbname=' . $database;
    }
    return $dsn . ';charset=utf8mb4';
}

/** Host returned to client apps (localhost on same server, or remote hostname). */
function hs_mysql_provision_client_host(): string
{
    $cfg = hs_mysql_provision_config();
    $client = trim((string) ($cfg['client_host'] ?? ''));
    if ($client !== '') {
        return $client;
    }
    $host = trim((string) ($cfg['host'] ?? 'localhost'));
    return $host !== '' ? $host : 'localhost';
}

/** MySQL user host for GRANT (localhost or % for remote). */
function hs_mysql_provision_grant_host(): string
{
    $cfg = hs_mysql_provision_config();
    $grant = trim((string) ($cfg['grant_host'] ?? 'localhost'));
    return $grant !== '' ? $grant : 'localhost';
}

function hs_mysql_provision_name_prefix(): string
{
    $cfg = hs_mysql_provision_config();
    return preg_replace('/[^a-z0-9_]/i', '', (string) ($cfg['name_prefix'] ?? '')) ?? '';
}

/** dedicated = CREATE DATABASE per client; shared = one CMS DB + table_prefix per client (Hostinger shared). */
function hs_mysql_provision_mode(): string
{
    $cfg = hs_mysql_provision_config();
    $mode = strtolower(trim((string) ($cfg['mode'] ?? 'dedicated')));
    return $mode === 'shared' ? 'shared' : 'dedicated';
}

function hs_mysql_provision_shared_mode(): bool
{
    return hs_mysql_provision_mode() === 'shared';
}

function hs_mysql_provision_shared_database(): string
{
    $cfg = hs_mysql_provision_config();
    $name = trim((string) ($cfg['shared_database'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    $dbCfg = hs_db_config();
    return trim((string) ($dbCfg['database'] ?? $dbCfg['dbname'] ?? ''));
}

function hs_mysql_provision_shared_user(): string
{
    $cfg = hs_mysql_provision_config();
    $user = trim((string) ($cfg['shared_user'] ?? ''));
    if ($user !== '') {
        return $user;
    }
    $dbCfg = hs_db_config();
    return trim((string) ($dbCfg['user'] ?? $dbCfg['username'] ?? ''));
}

function hs_mysql_provision_shared_pass(): string
{
    $cfg = hs_mysql_provision_config();
    if (($cfg['shared_pass'] ?? '') !== '') {
        return (string) $cfg['shared_pass'];
    }
    $dbCfg = hs_db_config();
    return (string) ($dbCfg['pass'] ?? $dbCfg['password'] ?? '');
}

function hs_mysql_shared_table_prefix(string $username): string
{
    $prefix = hs_mysql_provision_db_prefix() . hs_mysql_provision_slug($username) . '_';
    return preg_replace('/[^a-z0-9_]/i', '', $prefix) ?: 'hs_user_';
}

function hs_mysql_provision_admin_pdo(): ?PDO
{
    static $pdo = null;
    static $failed = false;
    static $cfgKey = '';
    $cfg = hs_mysql_provision_config();
    $key = $cfg ? json_encode([$cfg['host'] ?? '', $cfg['port'] ?? '', $cfg['user'] ?? '']) : '';
    if ($key !== $cfgKey) {
        $pdo = null;
        $failed = false;
        $cfgKey = $key;
    }
    if ($failed) {
        return null;
    }
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($cfg === null) {
        return null;
    }
    $host = trim((string) ($cfg['host'] ?? 'localhost'));
    $user = (string) ($cfg['user'] ?? '');
    $pass = (string) ($cfg['pass'] ?? '');
    if ($user === '' || $host === '') {
        return null;
    }
    try {
        $pdo = new PDO(
            hs_mysql_build_dsn($host, hs_mysql_provision_port()),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Throwable) {
        $failed = true;
        return null;
    }
    return $pdo;
}

function hs_mysql_provision_db_prefix(): string
{
    $cfg = hs_mysql_provision_config();
    $prefix = preg_replace('/[^a-z0-9_]/i', '', (string) ($cfg['db_prefix'] ?? 'hs_')) ?? 'hs_';
    return $prefix !== '' ? strtolower($prefix) : 'hs_';
}

function hs_mysql_provision_slug(string $username): string
{
    $slug = preg_replace('/[^a-z0-9]/', '', strtolower($username)) ?? 'user';
    if ($slug === '') {
        $slug = 'user';
    }
    return substr($slug, 0, 16);
}

/** Stable primary database name per client (one DB per user). */
function hs_mysql_provision_primary_ident(string $username): string
{
    return hs_mysql_provision_name_prefix()
        . hs_mysql_provision_db_prefix()
        . hs_mysql_provision_slug($username);
}

function hs_mysql_provision_ident(string $username): string
{
    return hs_mysql_provision_name_prefix()
        . hs_mysql_provision_db_prefix()
        . hs_mysql_provision_slug($username)
        . '_' . bin2hex(random_bytes(3));
}

function hs_mysql_quote_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function hs_mysql_quote_user(string $user, string $host = 'localhost'): string
{
    return hs_mysql_quote_ident($user) . '@' . hs_mysql_quote_ident($host);
}

/** @return array{ok:bool,error:string} */
function hs_mysql_provision_test(): array
{
    if (hs_mysql_provision_shared_mode()) {
        $db = hs_mysql_provision_shared_database();
        $user = hs_mysql_provision_shared_user();
        $pass = hs_mysql_provision_shared_pass();
        $host = hs_mysql_provision_client_host();
        if ($db === '' || $user === '') {
            return ['ok' => false, 'error' => 'shared_not_configured'];
        }
        try {
            new PDO(
                hs_mysql_build_dsn($host, hs_mysql_provision_port(), $db),
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
    $pdo = hs_mysql_provision_admin_pdo();
    if (!$pdo instanceof PDO) {
        return ['ok' => false, 'error' => 'connection'];
    }
    $testDb = hs_mysql_provision_name_prefix() . hs_mysql_provision_db_prefix() . 'provision_test_' . bin2hex(random_bytes(3));
    $db = hs_mysql_quote_ident($testDb);
    try {
        $pdo->exec('CREATE DATABASE ' . $db . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('DROP DATABASE ' . $db);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/** @param array<string, mixed> $cfg */
function hs_mysql_provision_save_config(array $cfg): bool
{
    $host = trim((string) ($cfg['host'] ?? 'localhost'));
    $user = trim((string) ($cfg['user'] ?? ''));
    if ($host === '' || $user === '') {
        return false;
    }
    $mode = strtolower(trim((string) ($cfg['mode'] ?? 'dedicated')));
    $out = [
        'host' => $host,
        'port' => max(1, min(65535, (int) ($cfg['port'] ?? 3306))),
        'user' => $user,
        'pass' => (string) ($cfg['pass'] ?? ''),
        'db_prefix' => preg_replace('/[^a-z0-9_]/i', '', (string) ($cfg['db_prefix'] ?? 'hs_')) ?: 'hs_',
        'name_prefix' => preg_replace('/[^a-z0-9_]/i', '', (string) ($cfg['name_prefix'] ?? '')) ?? '',
        'client_host' => trim((string) ($cfg['client_host'] ?? '')),
        'grant_host' => trim((string) ($cfg['grant_host'] ?? 'localhost')) ?: 'localhost',
        'mode' => $mode === 'shared' ? 'shared' : 'dedicated',
        'shared_database' => trim((string) ($cfg['shared_database'] ?? '')),
        'shared_user' => trim((string) ($cfg['shared_user'] ?? '')),
        'shared_pass' => (string) ($cfg['shared_pass'] ?? ''),
    ];
    $php = "<?php\n/** Auto-generated — MySQL provisioning for client databases */\nreturn "
        . var_export($out, true) . ";\n";
    $file = hs_mysql_provision_config_file();
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        return false;
    }
    @chmod($file, 0640);
    return true;
}

/** @return list<string> */
function hs_mysql_remote_grant_hosts(string $ipsCsv): array
{
    $parts = preg_split('/[\s,;]+/', trim($ipsCsv), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $hosts = [];
    foreach ($parts as $ip) {
        $ip = trim((string) $ip);
        if ($ip !== '' && preg_match('/^[0-9.%a-fA-F:]+$/', $ip)) {
            $hosts[] = $ip;
        }
    }

    return $hosts !== [] ? $hosts : ['%'];
}

/** @return array{ok:bool,error:string} */
function hs_mysql_provision_drop_db(string $dbName, string $dbUser): array
{
    $pdo = hs_mysql_provision_admin_pdo();
    if (!$pdo instanceof PDO || $dbName === '' || $dbUser === '') {
        return ['ok' => false, 'error' => 'no_admin'];
    }
    $db = hs_mysql_quote_ident($dbName);
    $hosts = array_unique(array_filter([
        'localhost',
        '%',
        hs_mysql_provision_grant_host(),
    ]));
    try {
        $pdo->exec('DROP DATABASE IF EXISTS ' . $db);
        foreach ($hosts as $host) {
            try {
                $pdo->exec('DROP USER IF EXISTS ' . hs_mysql_quote_user($dbUser, $host));
            } catch (Throwable) {
            }
        }
        $pdo->exec('FLUSH PRIVILEGES');

        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * @return array{ok:bool,error?:string}
 */
function hs_mysql_apply_user_remote_grants(string $userId, bool $remote, string $ipsCsv): array
{
    if (hs_mysql_provision_shared_mode()) {
        return ['ok' => true];
    }
    $pdo = hs_mysql_provision_admin_pdo();
    if (!$pdo instanceof PDO) {
        return ['ok' => false, 'error' => 'no_admin'];
    }
    $settings = hs_user_settings_get($userId);
    $dbs = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    $hosts = $remote ? hs_mysql_remote_grant_hosts($ipsCsv) : ['localhost'];
    try {
        foreach ($dbs as $db) {
            if (!is_array($db) || empty($db['provisioned']) || !empty($db['shared'])) {
                continue;
            }
            $dbUser = (string) ($db['user'] ?? '');
            $dbName = (string) ($db['name'] ?? '');
            $dbPass = (string) ($db['password'] ?? '');
            if ($dbUser === '' || $dbName === '') {
                continue;
            }
            $dbIdent = hs_mysql_quote_ident($dbName);
            foreach ($hosts as $host) {
                $userHost = hs_mysql_quote_user($dbUser, $host);
                try {
                    $pdo->exec('CREATE USER IF NOT EXISTS ' . $userHost . ' IDENTIFIED BY ' . $pdo->quote($dbPass));
                } catch (Throwable) {
                    try {
                        $pdo->exec('CREATE USER ' . $userHost . ' IDENTIFIED BY ' . $pdo->quote($dbPass));
                    } catch (Throwable) {
                    }
                }
                $pdo->exec('GRANT ALL PRIVILEGES ON ' . $dbIdent . '.* TO ' . $userHost);
            }
            if (!$remote) {
                try {
                    $pdo->exec('DROP USER IF EXISTS ' . hs_mysql_quote_user($dbUser, '%'));
                } catch (Throwable) {
                }
            }
        }
        $pdo->exec('FLUSH PRIVILEGES');

        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/** @return array{ok:bool,error:string} */
function hs_mysql_provision_create_db(string $dbName, string $dbUser, string $dbPass): array
{
    $pdo = hs_mysql_provision_admin_pdo();
    if (!$pdo instanceof PDO) {
        return ['ok' => false, 'error' => 'MySQL provision admin not configured or connection failed.'];
    }
    $db = hs_mysql_quote_ident($dbName);
    $grantHost = hs_mysql_provision_grant_host();
    $userHost = hs_mysql_quote_user($dbUser, $grantHost);
    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $db . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        try {
            $pdo->exec('CREATE USER IF NOT EXISTS ' . $userHost . ' IDENTIFIED BY ' . $pdo->quote($dbPass));
        } catch (Throwable) {
            $pdo->exec('CREATE USER ' . $userHost . ' IDENTIFIED BY ' . $pdo->quote($dbPass));
        }
        $pdo->exec('GRANT ALL PRIVILEGES ON ' . $db . '.* TO ' . $userHost);
        $pdo->exec('FLUSH PRIVILEGES');
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        try {
            $pdo->exec('DROP DATABASE IF EXISTS ' . $db);
        } catch (Throwable) {
        }
        try {
            $pdo->exec('DROP USER IF EXISTS ' . $userHost);
        } catch (Throwable) {
        }
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function hs_client_db_config_dir(): string
{
    $dir = HS_DATA_DIR . '/client-db';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return HS_DATA_DIR;
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\n");
    }

    return $dir;
}

function hs_client_db_config_file(string $username): string
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';

    return hs_client_db_config_dir() . '/' . $username . '.php';
}

/** Remove legacy credentials mistakenly written under public_html (pre-2026-07). */
function hs_remove_legacy_public_db_config(string $username): void
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';
    $legacy = hs_public_path($username . '/config.php');
    if (is_file($legacy)) {
        @unlink($legacy);
    }
}

function hs_write_client_account_config(string $username, array $dbEntry, ?string $userId = null): bool
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';
    hs_remove_legacy_public_db_config($username);
    $settings = $userId !== null ? hs_user_settings_get($userId) : [];
    $domain = (string) ($settings['primary_domain'] ?? hs_default_primary_domain());
    global $site_url;
    $cfg = [
        'db_host' => (string) ($dbEntry['host'] ?? 'localhost'),
        'db_name' => (string) ($dbEntry['name'] ?? ''),
        'db_user' => (string) ($dbEntry['user'] ?? ''),
        'db_pass' => (string) ($dbEntry['password'] ?? ''),
        'db_table_prefix' => (string) ($dbEntry['table_prefix'] ?? ''),
        'primary_domain' => $domain,
        'site_url' => rtrim((string) ($site_url ?? ''), '/') . '/' . HS_PUBLIC_HTML . '/' . $username . '/',
    ];
    $php = "<?php\n/** Auto-generated by Hosting CMS — credentials stored outside public_html */\nreturn "
        . var_export($cfg, true) . ";\n";
    $file = hs_client_db_config_file($username);
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        return false;
    }
    @chmod($file, 0640);

    return true;
}

/**
 * @return array{ok:bool,entry?:array<string,mixed>,error?:string,skipped?:bool}
 */
function hs_provision_client_database(string $userId, string $username, array $user, bool $primary = false, ?string $labelSuffix = null, ?string $website = null): array
{
    $existing = hs_db_client_databases_for_user($userId);
    if ($existing === []) {
        $settings = hs_user_settings_get($userId);
        $existing = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    }
    $limit = hs_user_database_limit($user);
    if (count($existing) >= $limit) {
        return ['ok' => false, 'error' => 'limit'];
    }

    if ($primary) {
        $logicalName = hs_mysql_provision_primary_ident($username);
    } elseif ($labelSuffix !== null && $labelSuffix !== '') {
        $logicalName = hs_mysql_provision_name_prefix()
            . hs_mysql_provision_db_prefix()
            . hs_mysql_provision_slug($username)
            . '_' . $labelSuffix;
    } else {
        $logicalName = hs_mysql_provision_ident($username);
    }
    foreach ($existing as $db) {
        if (!is_array($db)) {
            continue;
        }
        $matchName = (string) ($db['logical_name'] ?? $db['name'] ?? '');
        if ($matchName === $logicalName) {
            return ['ok' => true, 'entry' => $db, 'skipped' => true];
        }
    }

    $clientHost = hs_mysql_provision_client_host();

    if (hs_mysql_provision_shared_mode()) {
        $dbName = hs_mysql_provision_shared_database();
        $dbUser = hs_mysql_provision_shared_user();
        $dbPass = hs_mysql_provision_shared_pass();
        if ($dbName === '' || $dbUser === '') {
            return ['ok' => false, 'error' => 'shared_not_configured'];
        }
        $test = hs_mysql_provision_test();
        if (empty($test['ok'])) {
            return ['ok' => false, 'error' => $test['error'] ?? 'shared_connection'];
        }
        $entry = [
            'id' => 'db_' . bin2hex(random_bytes(6)),
            'name' => $dbName,
            'user' => $dbUser,
            'password' => $dbPass,
            'host' => $clientHost,
            'logical_name' => $labelSuffix ?? $logicalName,
            'table_prefix' => hs_mysql_shared_table_prefix($username),
            'website' => $website !== null && trim($website) !== '' ? trim($website) : null,
            'created_at' => gmdate('c'),
            'provisioned' => true,
            'shared' => true,
            'primary' => $primary,
        ];
    } else {
        $dbName = $logicalName;
        $dbUser = $dbName;
        $dbPass = bin2hex(random_bytes(12));
        $prov = hs_mysql_provision_create_db($dbName, $dbUser, $dbPass);
        if (!$prov['ok']) {
            return ['ok' => false, 'error' => $prov['error']];
        }
        $entry = [
            'id' => 'db_' . bin2hex(random_bytes(6)),
            'name' => $dbName,
            'user' => $dbUser,
            'password' => $dbPass,
            'host' => $clientHost,
            'logical_name' => $labelSuffix ?? $dbName,
            'website' => $website !== null && trim($website) !== '' ? trim($website) : null,
            'created_at' => gmdate('c'),
            'provisioned' => true,
            'primary' => $primary,
        ];
    }

    if (hs_is_mysql_installed()) {
        if (!hs_db_insert_client_database($userId, $entry)) {
            return ['ok' => false, 'error' => 'save'];
        }
    }

    $dbs = $existing;
    $dbs[] = $entry;
    if (!hs_user_settings_save($userId, ['databases' => $dbs])) {
        return ['ok' => false, 'error' => 'save'];
    }

    hs_write_client_account_config($username, $entry, $userId);

    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'db_create', $dbName);
    }

    return ['ok' => true, 'entry' => $entry];
}

/**
 * Ensure active client has a primary MySQL database (one per user).
 *
 * @return array{ok:bool,entry?:array<string,mixed>,error?:string,skipped?:bool}
 */
function hs_ensure_user_database(string $userId, string $username, ?array $user = null): array
{
    if ($user === null) {
        $user = hs_user_by_id($userId) ?? [];
    }
    require_once __DIR__ . '/plan-specs.php';
    if (hs_is_demo_panel_user($user)) {
        hs_remove_legacy_public_db_config($username);
        return ['ok' => true, 'skipped' => true, 'error' => 'demo_no_provision'];
    }
    if (($user['subscription_status'] ?? '') !== 'active') {
        return ['ok' => false, 'error' => 'inactive'];
    }
    if (!hs_is_mysql_installed()) {
        return ['ok' => false, 'error' => 'no_mysql'];
    }
    if (!hs_mysql_provision_enabled()) {
        return ['ok' => false, 'error' => 'provision_config'];
    }
    $settings = hs_user_settings_get($userId);
    if (!empty($settings['db_provision_failed'])) {
        if (hs_mysql_provision_shared_mode()) {
            $retryTest = hs_mysql_provision_test();
            if (!empty($retryTest['ok'])) {
                hs_user_settings_save($userId, ['db_provision_failed' => false]);
                $settings = hs_user_settings_get($userId);
            } else {
                return ['ok' => false, 'error' => 'provision_failed'];
            }
        } else {
            return ['ok' => false, 'error' => 'provision_failed'];
        }
    }
    $dbs = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    foreach ($dbs as $db) {
        if (is_array($db) && !empty($db['provisioned'])) {
            return ['ok' => true, 'entry' => $db, 'skipped' => true];
        }
    }
    $res = hs_provision_client_database($userId, $username, $user, true);
    if (empty($res['ok']) && empty($res['skipped'])) {
        hs_user_settings_save($userId, ['db_provision_failed' => true]);
    }
    return $res;
}