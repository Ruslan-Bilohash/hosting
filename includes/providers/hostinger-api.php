<?php
declare(strict_types=1);

/**
 * Hostinger Cloud API — DNS, VPS metrics, backups.
 * Token: hPanel → API → data/hostinger.config.php
 * Docs: https://developers.hostinger.com
 */

function hs_hostinger_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $file = HS_DATA_DIR . '/hostinger.config.php';
    if (!is_file($file)) {
        $cfg = [];
        return $cfg;
    }
    $raw = require $file;
    $cfg = is_array($raw) ? $raw : [];
    return $cfg;
}

function hs_hostinger_configured(): bool
{
    $cfg = hs_hostinger_config();
    return trim((string) ($cfg['api_token'] ?? '')) !== '';
}

function hs_hostinger_api_base(): string
{
    $cfg = hs_hostinger_config();
    $base = trim((string) ($cfg['api_base'] ?? 'https://developers.hostinger.com'));
    return rtrim($base, '/');
}

/** @return array{ok:bool,data?:mixed,error?:string,http_code?:int} */
function hs_hostinger_api(string $method, string $path, ?array $body = null): array
{
    if (!hs_hostinger_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    $cfg = hs_hostinger_config();
    $url = hs_hostinger_api_base() . $path;
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . (string) $cfg['api_token'],
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => $headers,
    ];
    $method = strtoupper($method);
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }
    } elseif ($method !== 'GET') {
        $opts[CURLOPT_CUSTOMREQUEST] = $method;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode((string) $raw, true);
    if ($code >= 400) {
        return [
            'ok' => false,
            'error' => is_array($data) ? (string) ($data['message'] ?? $data['error'] ?? 'api_error') : 'api_http_' . $code,
            'http_code' => $code,
            'data' => $data,
        ];
    }
    return ['ok' => true, 'data' => $data, 'http_code' => $code];
}

/** @return array{ok:bool,records?:list<array<string,mixed>>,error?:string} */
function hs_hostinger_dns_get(string $domain): array
{
    $res = hs_hostinger_api('GET', '/api/dns/v1/zones/' . rawurlencode($domain));
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?? 'api_error'];
    }
    $data = $res['data'] ?? [];
    $records = is_array($data) ? ($data['records'] ?? $data) : [];
    return ['ok' => true, 'records' => is_array($records) ? $records : []];
}

/**
 * Add A record for subdomain on a zone (e.g. hosting + bilohash.com).
 *
 * @return array{ok:bool,error?:string}
 */
function hs_hostinger_dns_add_subdomain(string $zone, string $sub, string $ip): array
{
    $zone = strtolower(trim($zone));
    $sub = strtolower(trim($sub));
    if ($zone === '' || $sub === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return ['ok' => false, 'error' => 'invalid_args'];
    }
    $body = [
        'zone' => [
            [
                'name' => $sub,
                'type' => 'A',
                'ttl' => 14400,
                'records' => [['content' => $ip]],
            ],
        ],
    ];
    $res = hs_hostinger_api('PUT', '/api/dns/v1/zones/' . rawurlencode($zone), $body);
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?? 'dns_update_failed'];
    }
    return ['ok' => true];
}

/**
 * Point hosting.bilohash.com to shared Hostinger origin (CMS panel).
 *
 * @return array{ok:bool,error?:string}
 */
function hs_hostinger_dns_hosting_subdomain(string $zone = 'bilohash.com', string $ip = '45.84.204.61'): array
{
    return hs_hostinger_dns_add_subdomain($zone, 'hosting', $ip);
}

/**
 * Register subdomain in Hostinger hosting account (creates vhost + DNS).
 *
 * @return array{ok:bool,data?:mixed,error?:string}
 */
function hs_hostinger_create_website_subdomain(
    string $account,
    string $domain,
    string $subdomain,
    string $directory = 'hosting',
): array {
    $body = [
        'subdomain' => $subdomain,
        'directory' => $directory,
    ];
    $path = '/api/hosting/v1/accounts/' . rawurlencode($account)
        . '/websites/' . rawurlencode($domain) . '/subdomains';
    $res = hs_hostinger_api('POST', $path, $body);
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?? 'subdomain_failed', 'data' => $res['data'] ?? null];
    }
    return ['ok' => true, 'data' => $res['data'] ?? null];
}

/** One-shot: hosting.bilohash.com for BILOHASH Hosting CMS */
function hs_hostinger_setup_hosting_subdomain(string $account = 'u762384583'): array
{
    $dns = hs_hostinger_dns_hosting_subdomain('bilohash.com', '45.84.204.61');
    $sub = hs_hostinger_create_website_subdomain($account, 'bilohash.com', 'hosting', 'hosting');
    return [
        'ok' => $dns['ok'] && $sub['ok'],
        'dns' => $dns,
        'subdomain' => $sub,
    ];
}

/**
 * Upsert A record pointing domain to VPS IP.
 *
 * @return array{ok:bool,error?:string}
 */
function hs_hostinger_dns_point_to_vps(string $domain, ?string $ip = null): array
{
    $domain = strtolower(trim($domain));
    if ($domain === '') {
        return ['ok' => false, 'error' => 'invalid_domain'];
    }
    $ip = $ip ?? hs_vps_server_ip();
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return ['ok' => false, 'error' => 'invalid_ip'];
    }

    $body = [
        'zone' => [
            [
                'name' => '@',
                'type' => 'A',
                'ttl' => 14400,
                'records' => [['content' => $ip]],
            ],
            [
                'name' => 'www',
                'type' => 'CNAME',
                'ttl' => 14400,
                'records' => [['content' => $domain]],
            ],
        ],
    ];

    $res = hs_hostinger_api('PUT', '/api/dns/v1/zones/' . rawurlencode($domain), $body);
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?? 'dns_update_failed'];
    }
    return ['ok' => true];
}

/** @return array{ok:bool,vms?:list<array<string,mixed>>,error?:string} */
function hs_hostinger_vps_list(): array
{
    $res = hs_hostinger_api('GET', '/api/vps/v1/virtual-machines');
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?? 'api_error'];
    }
    $data = $res['data'] ?? [];
    $vms = is_array($data) ? ($data['data'] ?? $data) : [];
    return ['ok' => true, 'vms' => is_array($vms) ? $vms : []];
}

/** @return array{ok:bool,metrics?:array<string,mixed>,error?:string} */
function hs_hostinger_vps_metrics(int $vmId, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $path = '/api/vps/v1/virtual-machines/' . $vmId . '/metrics';
    if ($dateFrom !== null && $dateTo !== null) {
        $path .= '?date_from=' . rawurlencode($dateFrom) . '&date_to=' . rawurlencode($dateTo);
    }
    $res = hs_hostinger_api('GET', $path);
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'] ?? 'api_error'];
    }
    return ['ok' => true, 'metrics' => is_array($res['data'] ?? null) ? $res['data'] : []];
}