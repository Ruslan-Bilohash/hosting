<?php
declare(strict_types=1);

function hs_db_config_file(): string
{
    return HS_DATA_DIR . '/db.config.php';
}

function hs_installed_lock_file(): string
{
    return HS_DATA_DIR . '/installed.lock';
}

/** @return array<string, mixed>|null */
function hs_db_config(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $file = hs_db_config_file();
    if (!is_readable($file)) {
        $cache = null;
        return null;
    }
    $data = require $file;
    $cache = is_array($data) ? $data : null;
    return $cache;
}

function hs_db_prefix(): string
{
    $cfg = hs_db_config();
    $prefix = preg_replace('/[^a-z0-9_]/i', '', (string) ($cfg['prefix'] ?? 'hs_'));
    return $prefix !== '' ? $prefix : 'hs_';
}

function hs_db_table(string $name): string
{
    return hs_db_prefix() . $name;
}

function hs_db_pdo(): ?PDO
{
    static $pdo = null;
    static $failed = false;
    if ($failed) {
        return null;
    }
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = hs_db_config();
    if ($cfg === null) {
        return null;
    }
    $host = (string) ($cfg['host'] ?? 'localhost');
    $name = (string) ($cfg['database'] ?? $cfg['dbname'] ?? '');
    $user = (string) ($cfg['user'] ?? $cfg['username'] ?? '');
    $pass = (string) ($cfg['pass'] ?? $cfg['password'] ?? '');
    if ($name === '' || $user === '') {
        return null;
    }
    try {
        $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable) {
        $failed = true;
        return null;
    }
    return $pdo;
}

function hs_db_require_pdo(): PDO
{
    $pdo = hs_db_pdo();
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Database not configured. Run install.php first.');
    }
    return $pdo;
}

function hs_is_mysql_installed(): bool
{
    return is_file(hs_installed_lock_file()) && hs_db_pdo() instanceof PDO;
}

function hs_is_install_script(): bool
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return str_ends_with($script, '/install.php')
        || str_ends_with($script, '/migrate-to-mysql.php');
}

function hs_install_url(): string
{
    global $base_path;
    $prefix = rtrim((string) ($base_path ?? ''), '/');
    return ($prefix !== '' ? $prefix : '') . '/install.php';
}

function hs_install_redirect_if_needed(): void
{
    if (hs_is_mysql_installed() || hs_is_install_script()) {
        return;
    }
    $base = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));
    if (in_array($base, ['install.php', 'migrate-to-mysql.php'], true)) {
        return;
    }
    header('Location: ' . hs_install_url(), true, 302);
    exit;
}

/** @return array<string, mixed> */
function hs_db_row_decode(array $row): array
{
    if (isset($row['data'])) {
        $decoded = is_string($row['data']) ? json_decode($row['data'], true) : $row['data'];
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    unset($row['data']);
    return $row;
}

/** @return list<array<string, mixed>> */
function hs_db_load_collection(string $table): array
{
    $pdo = hs_db_require_pdo();
    $sql = 'SELECT * FROM `' . hs_db_table($table) . '`';
    $rows = $pdo->query($sql)->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $out[] = hs_db_row_decode($row);
    }
    return $out;
}

/** @param list<array<string, mixed>> $items */
function hs_db_save_collection(string $table, array $items, string $idKey): bool
{
    $pdo = hs_db_require_pdo();
    $tbl = hs_db_table($table);
    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM `' . $tbl . '`');
        $stmt = $pdo->prepare('INSERT INTO `' . $tbl . '` (`id`, `data`) VALUES (?, ?)');
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = trim((string) ($item[$idKey] ?? ''));
            if ($id === '') {
                continue;
            }
            $json = json_encode($item, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new RuntimeException('JSON encode failed for ' . $table);
            }
            $stmt->execute([$id, $json]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable) {
        $pdo->rollBack();
        return false;
    }
}

/** @return array<string, array<string, mixed>> */
function hs_db_load_user_settings_map(): array
{
    $pdo = hs_db_require_pdo();
    $rows = $pdo->query('SELECT `id`, `data` FROM `' . hs_db_table('user_settings') . '`')->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $id = (string) ($row['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $decoded = hs_db_row_decode($row);
        $out[$id] = $decoded;
    }
    return $out;
}

/** @param array<string, array<string, mixed>> $all */
function hs_db_save_user_settings_map(array $all): bool
{
    $pdo = hs_db_require_pdo();
    $tbl = hs_db_table('user_settings');
    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM `' . $tbl . '`');
        $stmt = $pdo->prepare('INSERT INTO `' . $tbl . '` (`id`, `data`) VALUES (?, ?)');
        foreach ($all as $userId => $settings) {
            if (!is_array($settings)) {
                continue;
            }
            $id = (string) $userId;
            if ($id === '') {
                continue;
            }
            $json = json_encode($settings, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new RuntimeException('JSON encode failed for user_settings');
            }
            $stmt->execute([$id, $json]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable) {
        $pdo->rollBack();
        return false;
    }
}

function hs_db_upsert_user_settings(string $userId, array $settings): bool
{
    $pdo = hs_db_require_pdo();
    $json = json_encode($settings, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO `' . hs_db_table('user_settings') . '` (`id`, `data`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)'
    );
    return $stmt->execute([$userId, $json]);
}

/** @return list<array<string, mixed>> */
function hs_db_client_databases_for_user(string $userId): array
{
    if (!hs_is_mysql_installed()) {
        return [];
    }
    $pdo = hs_db_pdo();
    if (!$pdo instanceof PDO) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT `id`, `data` FROM `' . hs_db_table('client_databases') . '` WHERE `user_id` = ? ORDER BY `id` ASC'
    );
    $stmt->execute([$userId]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $decoded = hs_db_row_decode($row);
        if (($decoded['id'] ?? '') === '' && isset($row['id'])) {
            $decoded['id'] = (string) $row['id'];
        }
        $out[] = $decoded;
    }
    return $out;
}

function hs_db_insert_client_database(string $userId, array $entry): bool
{
    $pdo = hs_db_require_pdo();
    $id = (string) ($entry['id'] ?? '');
    if ($id === '') {
        $id = 'db_' . bin2hex(random_bytes(6));
        $entry['id'] = $id;
    }
    $json = json_encode($entry, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO `' . hs_db_table('client_databases') . '` (`id`, `user_id`, `data`) VALUES (?, ?, ?)'
    );
    return $stmt->execute([$id, $userId, $json]);
}

function hs_db_delete_client_database(string $userId, string $dbId): bool
{
    if (!hs_is_mysql_installed() || $dbId === '') {
        return false;
    }
    $pdo = hs_db_pdo();
    if (!$pdo instanceof PDO) {
        return false;
    }
    $stmt = $pdo->prepare(
        'DELETE FROM `' . hs_db_table('client_databases') . '` WHERE `id` = ? AND `user_id` = ?'
    );

    return $stmt->execute([$dbId, $userId]);
}