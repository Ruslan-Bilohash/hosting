<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/storage.php';

/** Schema 2.0 — full CMS data in MySQL (JSON document columns). */
const HS_MYSQL_SCHEMA_V2 = '2.0.0';

/** Meta keys stored in `{prefix}meta` (not separate JSON files). */
const HS_DB_META_PLANS_CATALOG = 'plans_catalog';
const HS_DB_META_EXCHANGE_RATES = 'exchange_rates';
const HS_DB_META_INVOICE_COUNTER = 'invoice_counter';
const HS_DB_META_CLIENT_COUNTER = 'client_counter';
const HS_DB_META_GEOIP_CACHE = 'geoip_cache';
const HS_DB_META_JSON_MIGRATED = 'json_migrated_at';

/** @return list<string> */
function hs_db_schema_v2_tables(): array
{
    return [
        'invoices',
        'domain_orders',
        'hosting_orders',
        'activity_logs',
    ];
}

function hs_db_schema_sql_v2(string $prefix): string
{
    $pfx = preg_replace('/[^a-z0-9_]/i', '', $prefix) ?: 'hs_';

    return <<<SQL
CREATE TABLE IF NOT EXISTS `{$pfx}invoices` (
  `id` VARCHAR(64) NOT NULL,
  `data` JSON NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{$pfx}domain_orders` (
  `id` VARCHAR(64) NOT NULL,
  `data` JSON NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{$pfx}hosting_orders` (
  `id` VARCHAR(64) NOT NULL,
  `data` JSON NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{$pfx}activity_logs` (
  `user_id` VARCHAR(64) NOT NULL,
  `data` JSON NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}

function hs_db_schema_version(): string
{
    if (!hs_is_mysql_installed()) {
        return '';
    }
    try {
        $val = hs_db_meta_get_scalar(HS_DB_META_JSON_MIGRATED, '');
        if ($val !== '') {
            return HS_MYSQL_SCHEMA_V2;
        }
        $ver = hs_db_meta_get_scalar('schema_version', '');
        return is_string($ver) ? $ver : '';
    } catch (Throwable) {
        return '';
    }
}

function hs_db_ensure_schema(): void
{
    if (!hs_is_mysql_installed()) {
        return;
    }
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $pdo = hs_db_require_pdo();
        $pfx = hs_db_prefix();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `' . $pfx . 'meta` (
              `meta_key` VARCHAR(64) NOT NULL,
              `meta_value` LONGTEXT NOT NULL,
              PRIMARY KEY (`meta_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // Upgrade meta column for large blobs (activity / geoip).
        try {
            $pdo->exec(
                'ALTER TABLE `' . $pfx . 'meta` MODIFY `meta_value` LONGTEXT NOT NULL'
            );
        } catch (Throwable) {
            // Column may already be LONGTEXT.
        }

        foreach (array_filter(array_map('trim', explode(';', hs_db_schema_sql_v2($pfx)))) as $stmt) {
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        }

        $current = hs_db_meta_get_scalar('schema_version', '');
        if ($current === '' || version_compare((string) $current, HS_MYSQL_SCHEMA_V2, '<')) {
            hs_db_meta_set_scalar('schema_version', HS_MYSQL_SCHEMA_V2);
        }
    } catch (Throwable) {
        // Non-fatal — entity layer may still use JSON fallback paths.
    }
}

/** @return mixed */
function hs_db_meta_get_scalar(string $key, mixed $default = ''): mixed
{
    try {
        $pdo = hs_db_require_pdo();
        $stmt = $pdo->prepare(
            'SELECT `meta_value` FROM `' . hs_db_table('meta') . '` WHERE `meta_key` = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return $default;
        }
        $raw = (string) ($row['meta_value'] ?? '');
        if ($raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $raw;
    } catch (Throwable) {
        return $default;
    }
}

function hs_db_meta_set_scalar(string $key, mixed $value): bool
{
    $pdo = hs_db_require_pdo();
    if (is_string($value) || is_int($value) || is_float($value)) {
        $stored = (string) $value;
    } else {
        $stored = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($stored === false) {
            return false;
        }
    }
    $stmt = $pdo->prepare(
        'INSERT INTO `' . hs_db_table('meta') . '` (`meta_key`, `meta_value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `meta_value` = VALUES(`meta_value`)'
    );

    return $stmt->execute([$key, $stored]);
}

/** @return array<string, mixed> */
function hs_db_meta_get_array(string $key, array $default = []): array
{
    $val = hs_db_meta_get_scalar($key, $default);
    return is_array($val) ? $val : $default;
}

/** @param array<string, mixed> $value */
function hs_db_meta_set_array(string $key, array $value): bool
{
    return hs_db_meta_set_scalar($key, $value);
}

/** @return array{user_id:string,entries:list<array<string,mixed>>} */
function hs_db_activity_log_load(string $userId): array
{
    hs_db_ensure_schema();
    $pdo = hs_db_require_pdo();
    $stmt = $pdo->prepare(
        'SELECT `data` FROM `' . hs_db_table('activity_logs') . '` WHERE `user_id` = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return ['user_id' => $userId, 'entries' => []];
    }
    $decoded = hs_db_row_decode($row);
    if (!isset($decoded['entries']) || !is_array($decoded['entries'])) {
        $decoded['entries'] = [];
    }
    $decoded['user_id'] = (string) ($decoded['user_id'] ?? $userId);

    return $decoded;
}

/** @param array{user_id:string,entries:list<array<string,mixed>>} $data */
function hs_db_activity_log_save(string $userId, array $data): bool
{
    hs_db_ensure_schema();
    $pdo = hs_db_require_pdo();
    $data['user_id'] = $userId;
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        $data['entries'] = [];
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO `' . hs_db_table('activity_logs') . '` (`user_id`, `data`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)'
    );

    return $stmt->execute([$userId, $json]);
}

function hs_db_json_backup_dir(): string
{
    $dir = HS_DATA_DIR . '/json-backup';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\nDeny from all\n");
    }

    return $dir;
}

function hs_db_backup_json_file(string $file): void
{
    if (!is_file($file)) {
        return;
    }
    $stamp = gmdate('Ymd-His');
    $dest = hs_db_json_backup_dir() . '/' . basename($file, '.json') . '-' . $stamp . '.json';
    @copy($file, $dest);
}

/**
 * Import all JSON runtime data into MySQL (idempotent when tables already populated).
 *
 * @return array{ok:bool,migrated:list<string>,skipped:list<string>,errors:list<string>}
 */
function hs_mysql_migrate_from_json(bool $backup = true): array
{
    $result = ['ok' => true, 'migrated' => [], 'skipped' => [], 'errors' => []];

    if (!hs_is_mysql_installed()) {
        return ['ok' => false, 'migrated' => [], 'skipped' => [], 'errors' => ['MySQL not installed']];
    }

    hs_db_ensure_schema();

    $imports = [
        ['table' => 'users', 'file' => hs_data_file('users'), 'id' => 'id', 'empty_ok' => false],
        ['table' => 'sites', 'file' => hs_data_file('sites'), 'id' => 'id', 'empty_ok' => false],
        ['table' => 'invoices', 'file' => hs_data_file('invoices'), 'id' => 'id', 'empty_ok' => true],
        ['table' => 'domain_orders', 'file' => hs_data_file('domain-orders'), 'id' => 'id', 'empty_ok' => true],
        ['table' => 'hosting_orders', 'file' => hs_data_file('hosting-orders'), 'id' => 'id', 'empty_ok' => true],
    ];

    foreach ($imports as $spec) {
        $label = (string) $spec['table'];
        $existing = hs_db_load_collection($label);
        if ($existing !== []) {
            $result['skipped'][] = $label . ' (already in MySQL)';
            continue;
        }
        $rows = hs_read_json((string) $spec['file']);
        if ($rows === [] && empty($spec['empty_ok'])) {
            $result['skipped'][] = $label . ' (no JSON file)';
            continue;
        }
        if (!is_array($rows)) {
            $result['errors'][] = $label . ': invalid JSON';
            $result['ok'] = false;
            continue;
        }
        if ($backup) {
            hs_db_backup_json_file((string) $spec['file']);
        }
        if (!hs_db_save_collection($label, array_values($rows), (string) $spec['id'])) {
            $result['errors'][] = $label . ': save failed';
            $result['ok'] = false;
            continue;
        }
        $result['migrated'][] = $label . ' (' . count($rows) . ' rows)';
    }

    // user-settings map
    $settingsFile = hs_data_file('user-settings');
    $settingsMap = hs_read_json($settingsFile);
    if (is_array($settingsMap) && $settingsMap !== []) {
        $existingSettings = hs_db_load_user_settings_map();
        if ($existingSettings === []) {
            if ($backup) {
                hs_db_backup_json_file($settingsFile);
            }
            if (hs_db_save_user_settings_map($settingsMap)) {
                $result['migrated'][] = 'user_settings (' . count($settingsMap) . ' users)';
            } else {
                $result['errors'][] = 'user_settings: save failed';
                $result['ok'] = false;
            }
        } else {
            $result['skipped'][] = 'user_settings (already in MySQL)';
        }
    }

    // Meta blobs
    $metaFiles = [
        HS_DB_META_PLANS_CATALOG => hs_data_file('plans-catalog'),
        HS_DB_META_EXCHANGE_RATES => HS_DATA_DIR . '/exchange-rates.json',
        HS_DB_META_INVOICE_COUNTER => hs_data_file('invoice-counter'),
        HS_DB_META_CLIENT_COUNTER => hs_data_file('client-counter'),
        HS_DB_META_GEOIP_CACHE => HS_DATA_DIR . '/logs/geoip-cache.json',
    ];
    foreach ($metaFiles as $metaKey => $path) {
        $existing = hs_db_meta_get_scalar($metaKey, null);
        if ($existing !== null && $existing !== '' && $existing !== []) {
            $result['skipped'][] = $metaKey . ' (already in MySQL)';
            continue;
        }
        $blob = hs_read_json($path);
        if ($blob === []) {
            $result['skipped'][] = $metaKey . ' (no JSON file)';
            continue;
        }
        if ($backup && is_file($path)) {
            hs_db_backup_json_file($path);
        }
        if (!hs_db_meta_set_scalar($metaKey, $blob)) {
            $result['errors'][] = $metaKey . ': save failed';
            $result['ok'] = false;
            continue;
        }
        $result['migrated'][] = $metaKey;
    }

    // Activity logs per user
    $logDir = HS_DATA_DIR . '/logs';
    if (is_dir($logDir)) {
        foreach (glob($logDir . '/*.json') ?: [] as $logFile) {
            if (basename($logFile) === 'geoip-cache.json') {
                continue;
            }
            $userId = preg_replace('/[^a-z0-9_-]/i', '', basename($logFile, '.json')) ?: '';
            if ($userId === '') {
                continue;
            }
            $existingLog = hs_db_activity_log_load($userId);
            if (($existingLog['entries'] ?? []) !== []) {
                $result['skipped'][] = 'activity_log:' . $userId . ' (already in MySQL)';
                continue;
            }
            $blob = hs_read_json($logFile);
            if ($blob === [] || !is_array($blob['entries'] ?? null)) {
                $blob = ['user_id' => $userId, 'entries' => is_array($blob) ? $blob : []];
            }
            if ($backup) {
                hs_db_backup_json_file($logFile);
            }
            if (!hs_db_activity_log_save($userId, $blob)) {
                $result['errors'][] = 'activity_log:' . $userId . ': save failed';
                $result['ok'] = false;
                continue;
            }
            $result['migrated'][] = 'activity_log:' . $userId;
        }
    }

    if ($result['ok'] && $result['migrated'] !== []) {
        hs_db_meta_set_scalar(HS_DB_META_JSON_MIGRATED, gmdate('c'));
        hs_db_meta_set_scalar('schema_version', HS_MYSQL_SCHEMA_V2);
    }

    return $result;
}