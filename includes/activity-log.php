<?php
declare(strict_types=1);

const HS_ACTIVITY_LOG_MAX = 2000;
const HS_ACTIVITY_LOG_PER_PAGE = 20;

function hs_activity_log_dir(): string
{
    $dir = HS_DATA_DIR . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\nDeny from all\n");
    }
    return $dir;
}

function hs_activity_log_user_file(string $userId): string
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $userId) ?: 'unknown';
    return hs_activity_log_dir() . '/' . $safe . '.json';
}

/** @return array{user_id:string,entries:list<array<string,mixed>>,migrated_settings?:bool} */
function hs_activity_log_load(string $userId): array
{
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        return hs_db_activity_log_load($userId);
    }
    $file = hs_activity_log_user_file($userId);
    $data = hs_read_json($file);
    if ($data === []) {
        return ['user_id' => $userId, 'entries' => []];
    }
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        $data['entries'] = [];
    }
    $data['user_id'] = (string) ($data['user_id'] ?? $userId);
    return $data;
}

/** @param array{user_id:string,entries:list<array<string,mixed>>} $data */
function hs_activity_log_save(string $userId, array $data): bool
{
    $data['user_id'] = $userId;
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        $data['entries'] = [];
    }
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        return hs_db_activity_log_save($userId, $data);
    }
    return hs_write_json(hs_activity_log_user_file($userId), $data);
}

function hs_activity_log_client_ip(): string
{
    $candidates = [];
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidates[] = (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
            $candidates[] = trim($part);
        }
    }
    $candidates[] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $fallback = '';
    foreach ($candidates as $raw) {
        $ip = trim($raw);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            continue;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
        if ($fallback === '') {
            $fallback = $ip;
        }
    }

    return $fallback;
}

function hs_activity_log_request_country(): string
{
    $code = strtoupper(trim((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
    if ($code === '' || $code === 'XX' || $code === 'T1' || !preg_match('/^[A-Z]{2}$/', $code)) {
        return '';
    }

    return $code;
}

function hs_activity_log_geo_cache_file(): string
{
    return hs_activity_log_dir() . '/geoip-cache.json';
}

function hs_activity_log_country_for_ip(string $ip): string
{
    $ip = trim($ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return '';
    }

    $cache = [];
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        $cache = hs_db_meta_get_array(HS_DB_META_GEOIP_CACHE, []);
    }
    if ($cache === []) {
        $cache = hs_read_json(hs_activity_log_geo_cache_file());
    }
    if (!is_array($cache)) {
        $cache = [];
    }
    if (!empty($cache[$ip]) && is_string($cache[$ip])) {
        $cached = strtoupper(trim($cache[$ip]));
        return preg_match('/^[A-Z]{2}$/', $cached) ? $cached : '';
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true,
            'header' => "User-Agent: BILOHASH-Hosting/1.0\r\n",
        ],
    ]);
    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode';
    $json = @file_get_contents($url, false, $ctx);
    $code = '';
    if (is_string($json)) {
        $data = json_decode($json, true);
        if (is_array($data) && ($data['status'] ?? '') === 'success') {
            $code = strtoupper(trim((string) ($data['countryCode'] ?? '')));
        }
    }
    if ($code !== '' && preg_match('/^[A-Z]{2}$/', $code)) {
        $cache[$ip] = $code;
        if (count($cache) > 5000) {
            $cache = array_slice($cache, -4000, null, true);
        }
        if (hs_is_mysql_installed()) {
            require_once __DIR__ . '/db-migrate.php';
            hs_db_meta_set_array(HS_DB_META_GEOIP_CACHE, $cache);
        } else {
            hs_write_json(hs_activity_log_geo_cache_file(), $cache);
        }
    }

    return $code;
}

function hs_activity_log_client_country(string $ip = ''): string
{
    $fromHeader = hs_activity_log_request_country();
    if ($fromHeader !== '') {
        return $fromHeader;
    }

    return hs_activity_log_country_for_ip($ip !== '' ? $ip : hs_activity_log_client_ip());
}

/** @param array<string,mixed> $entry */
function hs_activity_log_entry_country_code(array $entry, bool $resolveMissing = false): string
{
    $code = strtoupper(trim((string) ($entry['country'] ?? '')));
    if ($code !== '' && preg_match('/^[A-Z]{2}$/', $code)) {
        return $code;
    }
    if (!$resolveMissing) {
        return '';
    }
    $ip = trim((string) ($entry['ip'] ?? ''));
    if ($ip === '') {
        return '';
    }

    return hs_activity_log_country_for_ip($ip);
}

/** @param array<string,mixed> $entry */
function hs_activity_log_entry_country_label(array $entry, string $lang, array $t = [], bool $resolveMissing = false): string
{
    $code = hs_activity_log_entry_country_code($entry, $resolveMissing);
    if ($code === '') {
        return '';
    }
    require_once __DIR__ . '/countries.php';

    return hs_country_label($code, $lang, $t);
}

/** @param array{type:string,action?:string,detail?:string,duration_sec?:int|null,at?:string} $entry */
function hs_activity_log_append(string $userId, array $entry): void
{
    if ($userId === '') {
        return;
    }
    hs_activity_log_migrate_from_settings($userId);
    $data = hs_activity_log_load($userId);
    $ip = hs_activity_log_client_ip();
    $row = [
        'id' => 'l_' . bin2hex(random_bytes(6)),
        'at' => (string) ($entry['at'] ?? gmdate('c')),
        'type' => (string) ($entry['type'] ?? 'change'),
        'action' => (string) ($entry['action'] ?? ''),
        'detail' => (string) ($entry['detail'] ?? ''),
        'duration_sec' => isset($entry['duration_sec']) ? (int) $entry['duration_sec'] : null,
        'ip' => $ip,
        'country' => hs_activity_log_client_country($ip),
    ];
    array_unshift($data['entries'], $row);
    if (count($data['entries']) > HS_ACTIVITY_LOG_MAX) {
        $data['entries'] = array_slice($data['entries'], 0, HS_ACTIVITY_LOG_MAX);
    }
    hs_activity_log_save($userId, $data);
}

function hs_activity_log_migrate_from_settings(string $userId): void
{
    $data = hs_activity_log_load($userId);
    if (!empty($data['migrated_settings'])) {
        return;
    }
    $settings = hs_user_settings_get($userId);
    $legacy = is_array($settings['activity_log'] ?? null) ? $settings['activity_log'] : [];
    $existing = count($data['entries']);
    if ($legacy !== [] && $existing === 0) {
        $migrated = [];
        foreach (array_reverse($legacy) as $e) {
            if (!is_array($e)) {
                continue;
            }
            $action = (string) ($e['action'] ?? '');
            $migrated[] = [
                'id' => 'l_' . bin2hex(random_bytes(4)),
                'at' => (string) ($e['at'] ?? gmdate('c')),
                'type' => $action === 'panel_visit' ? 'visit' : 'change',
                'action' => $action,
                'detail' => (string) ($e['detail'] ?? ''),
                'duration_sec' => null,
                'ip' => '',
            ];
        }
        $data['entries'] = array_reverse($migrated);
    }
    $data['migrated_settings'] = true;
    hs_activity_log_save($userId, $data);
}

/** @return list<array<string,mixed>> */
function hs_activity_log_entries(string $userId): array
{
    hs_activity_log_migrate_from_settings($userId);
    return hs_activity_log_load($userId)['entries'];
}

/** @return array{entries:list<array<string,mixed>>,total:int,page:int,pages:int,per_page:int} */
function hs_activity_log_page(string $userId, int $page = 1, int $perPage = HS_ACTIVITY_LOG_PER_PAGE): array
{
    $all = hs_activity_log_entries($userId);
    $total = count($all);
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    return [
        'entries' => array_slice($all, $offset, $perPage),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
    ];
}

/** @return array{logins:int,changes:int,visits:int,last_login:string,last_login_ip:string,last_login_country:string,session_time_sec:int} */
function hs_activity_log_stats(string $userId): array
{
    $logins = 0;
    $changes = 0;
    $visits = 0;
    $lastLogin = '';
    $lastLoginIp = '';
    $lastLoginCountry = '';
    $sessionTime = 0;
    foreach (hs_activity_log_entries($userId) as $e) {
        $type = (string) ($e['type'] ?? '');
        if ($type === 'login') {
            $logins++;
            if ($lastLogin === '') {
                $lastLogin = (string) ($e['at'] ?? '');
                $lastLoginIp = trim((string) ($e['ip'] ?? ''));
                $lastLoginCountry = hs_activity_log_entry_country_code($e, true);
            }
        } elseif ($type === 'change') {
            $changes++;
        } elseif ($type === 'visit') {
            $visits++;
        } elseif ($type === 'logout') {
            $sessionTime += (int) ($e['duration_sec'] ?? 0);
        }
    }
    return [
        'logins' => $logins,
        'changes' => $changes,
        'visits' => $visits,
        'last_login' => $lastLogin,
        'last_login_ip' => $lastLoginIp,
        'last_login_country' => $lastLoginCountry,
        'session_time_sec' => $sessionTime,
    ];
}

function hs_activity_log_format_duration(int $seconds, array $t = []): string
{
    if ($seconds <= 0) {
        return $t['analytics_duration_zero'] ?? '—';
    }
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    $parts = [];
    if ($h > 0) {
        $parts[] = $h . ' ' . ($t['analytics_duration_h'] ?? 'h');
    }
    if ($m > 0) {
        $parts[] = $m . ' ' . ($t['analytics_duration_m'] ?? 'min');
    }
    if ($parts === [] && $s > 0) {
        $parts[] = $s . ' ' . ($t['analytics_duration_s'] ?? 'sec');
    }
    return implode(' ', $parts);
}

function hs_activity_log_type_label(string $type, array $t = []): string
{
    $key = 'analytics_log_type_' . $type;
    if (!empty($t[$key])) {
        return (string) $t[$key];
    }
    static $fallback = [
        'login' => 'Login',
        'logout' => 'Logout',
        'visit' => 'Page view',
        'change' => 'Change',
    ];
    return $fallback[$type] ?? $type;
}