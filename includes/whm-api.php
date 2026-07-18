<?php
declare(strict_types=1);

/**
 * WHM / cPanel API helpers (Namecheap Reseller Nebula or any WHM host).
 * Config stored in data/whm-config.json.
 */

require_once __DIR__ . '/storage.php';

function hs_whm_config_path(): string
{
    if (!defined('HS_DATA_DIR')) {
        return dirname(__DIR__) . '/data/whm-config.json';
    }

    return rtrim((string) HS_DATA_DIR, '/\\') . '/whm-config.json';
}

/**
 * @return array<string, mixed>
 */
function hs_whm_config(bool $reload = false): array
{
    static $cache = null;
    if ($reload) {
        $cache = null;
    }
    if (is_array($cache)) {
        return $cache;
    }
    $defaults = [
        'enabled' => false,
        'host' => '',
        'port' => 2087,
        'use_ssl' => true,
        'api_user' => 'root',
        'api_token' => '',
        // When Stellar cannot open outbound :2087, call Nebula bridge over HTTPS :443
        // (tools/nebula-whm-bridge.php installed on bilomiwy domain)
        'bridge_url' => '',
        'bridge_secret' => '',
        // Namecheap Nebula typical pool (adjust to your reseller package)
        'max_accounts' => 25,
        'max_disk_gb' => 30,
        'reserved_disk_gb' => 0,
        'warn_accounts_pct' => 80,
        'warn_disk_pct' => 80,
        'auto_provision' => true,
        // Map SolaSkinner plans → WHM packages (created by Ensure packages)
        'packages' => [
            'starter' => 'sola_starter',
            'plus' => 'sola_plus',
            'business' => 'sola_business',
        ],
        'disk_gb' => [
            'starter' => 20,
            'plus' => 50,
            'business' => 100,
        ],
        'package_limits' => [],
        'default_contact_email' => 'support@solaskinner.com',
        'nameserver_1' => 'dns1.namecheaphosting.com',
        'nameserver_2' => 'dns2.namecheaphosting.com',
        'client_domain_suffix' => 'clients.solaskinner.com',
        'cpanel_port' => 2083,
        'username_prefix' => 'sola',
    ];
    $path = hs_whm_config_path();
    $raw = is_file($path) ? (string) @file_get_contents($path) : '';
    $data = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $cache = $defaults;

        return $cache;
    }
    $cache = array_merge($defaults, $data);

    return $cache;
}

/**
 * @param array<string, mixed> $patch
 * @return array{ok:bool,error?:string}
 */
function hs_whm_config_save(array $patch): array
{
    $cfg = hs_whm_config(true);
    $prevToken = trim((string) ($cfg['api_token'] ?? ''));
    $prevBridgeSecret = trim((string) ($cfg['bridge_secret'] ?? ''));
    foreach ($patch as $k => $v) {
        $cfg[(string) $k] = $v;
    }
    // Keep existing API token when form field left blank
    if (array_key_exists('api_token', $patch) && trim((string) $patch['api_token']) === '' && $prevToken !== '') {
        if (empty($patch['clear_token'])) {
            $cfg['api_token'] = $prevToken;
        }
    }
    if (array_key_exists('bridge_secret', $patch) && trim((string) $patch['bridge_secret']) === '' && $prevBridgeSecret !== '') {
        $cfg['bridge_secret'] = $prevBridgeSecret;
    }
    $path = hs_whm_config_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['ok' => false, 'error' => 'mkdir'];
    }
    $json = json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || @file_put_contents($path, $json) === false) {
        return ['ok' => false, 'error' => 'write'];
    }
    hs_whm_config(true);

    return ['ok' => true];
}

function hs_whm_enabled(): bool
{
    $cfg = hs_whm_config();
    if (empty($cfg['enabled'])) {
        return false;
    }
    $host = trim((string) ($cfg['host'] ?? ''));
    $token = trim((string) ($cfg['api_token'] ?? ''));

    return $host !== '' && $token !== '';
}

/**
 * Host + API user + token present (can call API even if Enable is off).
 */
function hs_whm_credentials_ready(?array $cfg = null): bool
{
    $cfg = $cfg ?? hs_whm_config();
    $host = trim((string) ($cfg['host'] ?? ''));
    $user = trim((string) ($cfg['api_user'] ?? ''));
    $token = trim((string) ($cfg['api_token'] ?? ''));
    $bridge = trim((string) ($cfg['bridge_url'] ?? ''));
    // Bridge mode still needs host label + token; host used for cPanel login URL
    if ($token === '' || $user === '') {
        return false;
    }

    return $bridge !== '' || $host !== '';
}

/**
 * Optional HTTPS bridge on Nebula (bypasses blocked outbound :2087 from Stellar).
 */
function hs_whm_bridge_url(?array $cfg = null): string
{
    $cfg = $cfg ?? hs_whm_config();

    return trim((string) ($cfg['bridge_url'] ?? ''));
}

/**
 * @param array<string, string|int|float|bool> $params
 * @return array{ok:bool,error?:string,data?:array<string,mixed>,raw?:string,version?:string,accounts?:int,http?:int}
 */
function hs_whm_api(string $function, array $params = [], bool $force = false): array
{
    $cfg = hs_whm_config();
    if (!$force && !hs_whm_enabled()) {
        return ['ok' => false, 'error' => 'whm_disabled'];
    }
    if ($force && !hs_whm_credentials_ready($cfg)) {
        return ['ok' => false, 'error' => 'missing_credentials'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'php_curl_missing'];
    }
    $host = trim((string) ($cfg['host'] ?? ''));
    $port = (int) ($cfg['port'] ?? 2087);
    $ssl = !array_key_exists('use_ssl', $cfg) || !empty($cfg['use_ssl']);
    $user = trim((string) ($cfg['api_user'] ?? 'root'));
    $token = trim((string) ($cfg['api_token'] ?? ''));
    if ($token === '') {
        return ['ok' => false, 'error' => 'missing_credentials'];
    }
    $bridge = hs_whm_bridge_url($cfg);
    if ($bridge === '' && $host === '') {
        return ['ok' => false, 'error' => 'missing_credentials'];
    }

    if ($bridge !== '') {
        // Stellar → Nebula public HTTPS → localhost WHM
        $bridgeSecret = trim((string) ($cfg['bridge_secret'] ?? ''));
        $payload = json_encode([
            'function' => $function,
            'api_user' => $user,
            'api_token' => $token,
            'params' => $params,
        ], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return ['ok' => false, 'error' => 'json_encode'];
        }
        $ch = curl_init($bridge);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'curl_init'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 55,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Sola-Bridge-Secret: ' . $bridgeSecret,
            ],
        ]);
    } else {
        $scheme = $ssl ? 'https' : 'http';
        $qs = http_build_query(array_merge(['api.version' => 1], $params));
        $url = $scheme . '://' . $host . ':' . $port . '/json-api/' . rawurlencode($function) . '?' . $qs;

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'curl_init'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Authorization: whm ' . $user . ':' . $token,
            ],
        ]);
    }

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno !== 0 || !is_string($body)) {
        $msg = $err !== '' ? $err : 'curl_' . $errno;
        if ($bridge === '' && (str_contains(strtolower($msg), 'timed out') || $errno === 28)) {
            $msg .= ' — port 2087 blocked from Stellar; install tools/nebula-whm-bridge.php on Nebula and set Bridge URL';
        }

        return ['ok' => false, 'error' => $msg, 'http' => $code];
    }
    // Bridge error envelope
    if ($bridge !== '' && $code >= 400) {
        $wrap = json_decode($body, true);
        if (is_array($wrap) && isset($wrap['error']) && empty($wrap['metadata'])) {
            return ['ok' => false, 'error' => 'bridge: ' . (string) $wrap['error'], 'raw' => substr($body, 0, 300), 'http' => $code];
        }
    }
    if ($code === 401 || $code === 403) {
        $reason = 'auth_failed_http_' . $code;
        // WHM often returns JSON: Access denied (IP whitelist or bad token)
        if (stripos($body, 'Access denied') !== false) {
            $reason = 'Access denied (HTTP ' . $code . ') — check API token IP whitelist + token value';
        }

        return ['ok' => false, 'error' => $reason, 'raw' => substr($body, 0, 300)];
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'bad_json_http_' . $code, 'raw' => substr($body, 0, 500)];
    }
    // Bridge may return {ok:false} before WHM
    if ($bridge !== '' && isset($json['ok']) && $json['ok'] === false && isset($json['error']) && !isset($json['metadata'])) {
        return ['ok' => false, 'error' => 'bridge: ' . (string) $json['error'], 'data' => $json, 'http' => $code];
    }
    $meta = $json['metadata'] ?? null;
    if (is_array($meta) && isset($meta['result']) && (int) $meta['result'] === 0) {
        $reason = (string) ($meta['reason'] ?? 'api_error');

        return ['ok' => false, 'error' => $reason, 'data' => $json, 'http' => $code];
    }

    return ['ok' => true, 'data' => $json, 'raw' => $body, 'http' => $code];
}

/**
 * Test WHM with saved credentials even if Enable is off ($force=true).
 *
 * @return array{ok:bool,error?:string,version?:string,accounts?:int}
 */
function hs_whm_test_connection(bool $force = true): array
{
    $cfg = hs_whm_config(true);
    if (!hs_whm_credentials_ready($cfg)) {
        return ['ok' => false, 'error' => 'missing_credentials'];
    }
    $ver = hs_whm_api('version', [], $force || hs_whm_enabled());
    if (empty($ver['ok'])) {
        return ['ok' => false, 'error' => (string) ($ver['error'] ?? 'fail')];
    }
    $version = '';
    $data = $ver['data'] ?? [];
    if (is_array($data)) {
        $version = (string) ($data['data']['version'] ?? $data['version'] ?? '');
    }
    $list = hs_whm_api('listaccts', [], $force || hs_whm_enabled());
    $count = 0;
    if (!empty($list['ok']) && is_array($list['data']['data']['acct'] ?? null)) {
        $count = count($list['data']['data']['acct']);
    } elseif (!empty($list['ok']) && is_array($list['data']['acct'] ?? null)) {
        $count = count($list['data']['acct']);
    }

    return ['ok' => true, 'version' => $version, 'accounts' => $count];
}

/**
 * @return array{ok:bool,error?:string}
 */
function hs_whm_ensure_package(
    string $name,
    int $diskGb,
    int $maxParked = 0,
    int $maxAddon = 2,
    int $maxSql = 5,
    int $maxPop = 10,
    int $maxFtp = 5,
    int $maxSub = 10,
    bool $hasshell = false
): array {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name) ?? '';
    if ($name === '') {
        return ['ok' => false, 'error' => 'package_name'];
    }
    $quota = max(1, $diskGb) * 1024; // MB
    $params = [
        'name' => $name,
        'quota' => $quota,
        'maxpark' => max(0, $maxParked),
        'maxaddon' => max(0, $maxAddon),
        'maxsql' => max(0, $maxSql),
        'maxpop' => max(0, $maxPop),
        'maxftp' => max(0, $maxFtp),
        'maxsub' => max(0, $maxSub),
        'hasshell' => $hasshell ? 1 : 0,
        'cgi' => 1,
        'frontpage' => 0,
    ];
    // create first; if exists, edit
    $create = hs_whm_api('addpkg', $params);
    if (!empty($create['ok'])) {
        return ['ok' => true];
    }
    $err = strtolower((string) ($create['error'] ?? ''));
    if (str_contains($err, 'exist') || str_contains($err, 'already')) {
        $edit = hs_whm_api('editpkg', $params);

        return !empty($edit['ok']) ? ['ok' => true] : ['ok' => false, 'error' => (string) ($edit['error'] ?? 'editpkg')];
    }

    // Some WHM versions use different function names — treat as soft ok if package may exist
    return ['ok' => false, 'error' => (string) ($create['error'] ?? 'addpkg')];
}

/**
 * @return array{ok:bool,error?:string,username?:string,domain?:string}
 */
function hs_whm_createacct(
    string $username,
    string $domain,
    string $password,
    string $package,
    string $email,
    int $diskGb
): array {
    $username = strtolower(preg_replace('/[^a-z0-9]/', '', $username) ?? '');
    $domain = strtolower(trim($domain));
    if ($username === '' || $domain === '' || strlen($password) < 8) {
        return ['ok' => false, 'error' => 'params'];
    }
    $cfg = hs_whm_config();
    $params = [
        'username' => $username,
        'domain' => $domain,
        'password' => $password,
        'plan' => $package,
        'contactemail' => $email !== '' ? $email : (string) ($cfg['default_contact_email'] ?? ''),
        'quota' => max(1, $diskGb) * 1024,
        'cgi' => 1,
        'hasshell' => 0,
    ];
    if (trim((string) ($cfg['nameserver_1'] ?? '')) !== '') {
        $params['nameserver1'] = (string) $cfg['nameserver_1'];
    }
    if (trim((string) ($cfg['nameserver_2'] ?? '')) !== '') {
        $params['nameserver2'] = (string) $cfg['nameserver_2'];
    }
    $res = hs_whm_api('createacct', $params);
    if (empty($res['ok'])) {
        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'createacct')];
    }

    return ['ok' => true, 'username' => $username, 'domain' => $domain];
}

/**
 * One-click cPanel login URL via WHM create_user_session.
 *
 * @return array{ok:bool,url?:string,error?:string}
 */
function hs_whm_cpanel_sso_url(string $cpanelUser): array
{
    $cpanelUser = strtolower(trim($cpanelUser));
    if ($cpanelUser === '') {
        return ['ok' => false, 'error' => 'user'];
    }
    if (!hs_whm_enabled()) {
        return ['ok' => false, 'error' => 'whm_disabled'];
    }
    $res = hs_whm_api('create_user_session', [
        'user' => $cpanelUser,
        'service' => 'cpaneld',
    ]);
    if (empty($res['ok'])) {
        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'sso')];
    }
    $data = $res['data'] ?? [];
    $url = '';
    if (is_array($data)) {
        $url = (string) ($data['data']['url'] ?? $data['url'] ?? '');
    }
    if ($url === '') {
        return ['ok' => false, 'error' => 'no_url'];
    }

    return ['ok' => true, 'url' => $url];
}
