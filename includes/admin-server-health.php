<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/resource-usage.php';
require_once __DIR__ . '/performance.php';
require_once __DIR__ . '/installer.php';

function hs_admin_server_health_file(): string
{
    return HS_DATA_DIR . '/admin-server-health.json';
}

/** @return array<string, mixed> */
function hs_admin_server_health_load(): array
{
    return hs_read_json(hs_admin_server_health_file());
}

/** @param array<string, mixed> $data */
function hs_admin_server_health_save(array $data): void
{
    hs_write_json(hs_admin_server_health_file(), $data);
}

/** @return array<string, mixed> */
/**
 * Best-effort cPanel account home disk usage (MB) for this hosting user.
 * Falls back to public_html size when shell/quota is unavailable.
 */
function hs_admin_account_disk_mb(): array
{
    $pub = hs_public_path();
    $home = dirname(dirname($pub)); // .../public_html → account home (often)
    if (basename($pub) === 'public_html' && is_dir(dirname($pub))) {
        $home = dirname($pub);
    }
    $usedMb = 0.0;
    if (function_exists('hs_folder_size_mb') && is_dir($pub)) {
        $usedMb = (float) hs_folder_size_mb($pub);
    }
    // Prefer du on account home if available (more accurate for email/etc.)
    if (is_dir($home) && function_exists('exec') && !in_array('exec', array_map('strtolower', array_map('trim', explode(',', (string) ini_get('disable_functions')))), true)) {
        $out = [];
        @exec('du -sk ' . escapeshellarg($home) . ' 2>/dev/null', $out);
        if (isset($out[0]) && preg_match('/^(\d+)/', (string) $out[0], $m)) {
            $usedMb = round(((float) $m[1]) / 1024, 1);
        }
    }
    $quotaMb = 0.0;
    // Soft limit from config if set (Stellar plan disk is not exposed to PHP on shared)
    if (defined('HS_ACCOUNT_DISK_GB') && (float) HS_ACCOUNT_DISK_GB > 0) {
        $quotaMb = (float) HS_ACCOUNT_DISK_GB * 1024;
    }

    return [
        'used_mb' => $usedMb,
        'quota_mb' => $quotaMb,
        'home' => $home,
        'public_html' => $pub,
    ];
}

/**
 * Compact health snapshot for admin tools dashboard JSON.
 *
 * @return array<string, mixed>
 */
function hs_admin_server_health_snapshot(): array
{
    try {
        $server = hs_admin_server_info();
        $platform = hs_admin_platform_resources();
        $store = hs_admin_server_health_load();
        return [
            'ok' => true,
            'time' => gmdate('c'),
            'php' => PHP_VERSION,
            'server' => [
                'ip' => $server['shared_ip'] ?? ($server['ip'] ?? ''),
                'hostname' => $server['hostname'] ?? (string) (gethostname() ?: ''),
                'disk_used_mb' => $server['disk_used_mb'] ?? null,
                'disk_total_mb' => $server['disk_total_mb'] ?? null,
                'disk_pct' => $server['disk_pct'] ?? null,
                'load1' => $server['load1'] ?? null,
                'account_disk_mb' => $server['account_disk_mb'] ?? null,
            ],
            'platform' => [
                'clients' => $platform['clients'] ?? null,
                'sites' => $platform['sites'] ?? null,
                'storage_used_mb' => $platform['storage_used_mb'] ?? null,
            ],
            'last_probe_at' => $store['last_probe_at'] ?? null,
        ];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function hs_admin_server_info(): array
{
    $pubRoot = hs_public_path();
    // disk_* = filesystem the web root lives on (often whole shared node — label accordingly)
    $diskTotal = @disk_total_space($pubRoot);
    $diskFree = @disk_free_space($pubRoot);
    $diskTotal = $diskTotal !== false ? (int) $diskTotal : 0;
    $diskFree = $diskFree !== false ? (int) $diskFree : 0;
    $diskUsed = max(0, $diskTotal - $diskFree);

    $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
    $load1 = is_array($load) ? round((float) ($load[0] ?? 0), 2) : null;
    $load5 = is_array($load) && isset($load[1]) ? round((float) $load[1], 2) : null;
    $load15 = is_array($load) && isset($load[2]) ? round((float) $load[2], 2) : null;

    $sharedIp = hs_server_ip();
    $vpsIp = function_exists('hs_vps_server_ip') ? hs_vps_server_ip() : $sharedIp;
    $hasSeparateVps = $vpsIp !== '' && $vpsIp !== $sharedIp && filter_var($vpsIp, FILTER_VALIDATE_IP);

    $vps = ['configured' => false, 'id' => 0, 'hostname' => '', 'state' => '', 'plan' => ''];
    if (is_file(HS_DATA_DIR . '/hostinger.config.php')) {
        require_once __DIR__ . '/providers/hostinger-api.php';
        if (function_exists('hs_hostinger_configured') && hs_hostinger_configured()) {
            $cfg = hs_hostinger_config();
            $vmId = (int) ($cfg['vps_id'] ?? 0);
            $list = hs_hostinger_vps_list();
            if (!empty($list['ok']) && is_array($list['vms'] ?? null)) {
                foreach ($list['vms'] as $vm) {
                    if (!is_array($vm)) {
                        continue;
                    }
                    $id = (int) ($vm['id'] ?? 0);
                    if ($vmId > 0 && $id !== $vmId) {
                        continue;
                    }
                    $vps = [
                        'configured' => true,
                        'id' => $id,
                        'hostname' => (string) ($vm['hostname'] ?? $vm['name'] ?? ''),
                        'state' => (string) ($vm['state'] ?? $vm['status'] ?? ''),
                        'plan' => (string) ($vm['plan'] ?? $vm['template'] ?? ''),
                    ];
                    if ($vmId <= 0) {
                        break;
                    }
                }
            }
        }
    }

    $accountDisk = hs_admin_account_disk_mb();
    $whmOn = false;
    $nebula = ['enabled' => false, 'host' => '', 'user' => ''];
    if (is_file(__DIR__ . '/whm-api.php')) {
        require_once __DIR__ . '/whm-api.php';
        if (function_exists('hs_whm_enabled')) {
            $whmOn = hs_whm_enabled();
            if ($whmOn) {
                $cfg = hs_whm_config();
                $nebula = [
                    'enabled' => true,
                    'host' => (string) ($cfg['host'] ?? ''),
                    'user' => (string) ($cfg['api_user'] ?? ''),
                    'max_disk_gb' => (int) ($cfg['max_disk_gb'] ?? 30),
                    'max_accounts' => (int) ($cfg['max_accounts'] ?? 25),
                ];
            }
        }
    }

    return [
        'hostname' => (string) (gethostname() ?: php_uname('n')),
        'php_version' => PHP_VERSION,
        'server_software' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? ''),
        'shared_ip' => $sharedIp,
        'vps_ip' => $hasSeparateVps ? $vpsIp : '',
        'has_separate_vps' => $hasSeparateVps,
        'disk_total_mb' => round($diskTotal / 1048576, 1),
        'disk_free_mb' => round($diskFree / 1048576, 1),
        'disk_used_mb' => round($diskUsed / 1048576, 1),
        'disk_pct' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0,
        'account_disk_mb' => (float) ($accountDisk['used_mb'] ?? 0),
        'account_quota_mb' => (float) ($accountDisk['quota_mb'] ?? 0),
        'load_1m' => $load1,
        'load_5m' => $load5,
        'load_15m' => $load15,
        'memory_mb' => (int) round(memory_get_usage(true) / 1048576),
        'memory_peak_mb' => (int) round(memory_get_peak_usage(true) / 1048576),
        'checked_at' => gmdate('c'),
        'vps' => $vps,
        'whm' => $nebula,
        'document_root' => (string) ($_SERVER['DOCUMENT_ROOT'] ?? $pubRoot),
    ];
}

/** @return array{disk_mb:float,cpu_avg:float,memory_mb:int,bandwidth_gb:float,inodes:int,sites:int,clients:int} */
function hs_admin_platform_resources(): array
{
    $disk = 0.0;
    $cpuSum = 0.0;
    $cpuCnt = 0;
    $memory = 0;
    $bandwidth = 0.0;
    $inodes = 0;
    $sites = 0;
    $clients = 0;

    foreach (hs_users() as $user) {
        if (!is_array($user)) {
            continue;
        }
        $uid = (string) ($user['id'] ?? '');
        if ($uid === '') {
            continue;
        }
        $userSites = hs_sites_for_user($uid);
        $res = hs_resource_usage($user, $userSites);
        $disk += (float) ($res['storage_used_mb'] ?? 0);
        $cpuSum += (float) ($res['cpu_percent'] ?? 0);
        $cpuCnt++;
        $memory += (int) ($res['memory_mb'] ?? 0);
        $bandwidth += (float) ($res['bandwidth_gb'] ?? 0);
        $inodes += (int) ($res['inodes_used'] ?? 0);
        $sites += count($userSites);
        $clients++;
    }

    return [
        'disk_mb' => round($disk, 1),
        'cpu_avg' => $cpuCnt > 0 ? round($cpuSum / $cpuCnt, 1) : 0.0,
        'memory_mb' => $memory,
        'bandwidth_gb' => round($bandwidth, 2),
        'inodes' => $inodes,
        'sites' => $sites,
        'clients' => $clients,
    ];
}

/** @return list<array{id:string,domain:string,owner:string,plan:string,disk_mb:float,url:string,status:string}> */
function hs_admin_site_usage_rows(): array
{
    $usersById = [];
    foreach (hs_users() as $u) {
        if (is_array($u) && ($u['id'] ?? '') !== '') {
            $usersById[(string) $u['id']] = $u;
        }
    }

    $rows = [];
    foreach (hs_sites() as $site) {
        if (!is_array($site)) {
            continue;
        }
        $userId = (string) ($site['user_id'] ?? '');
        $user = $usersById[$userId] ?? null;
        if ($user === null) {
            continue;
        }
        $rel = hs_install_path_rel($user, $site);
        $diskMb = hs_folder_size_mb(hs_public_path($rel));
        $url = hs_public_url_for_site($user, $site);
        $domain = (string) ($site['domain'] ?? $site['name'] ?? $site['slug'] ?? '');
        $rows[] = [
            'id' => (string) ($site['id'] ?? ''),
            'domain' => $domain,
            'owner' => (string) ($user['username'] ?? ''),
            'plan' => (string) ($user['plan'] ?? ''),
            'disk_mb' => $diskMb,
            'url' => $url,
            'status' => (string) ($site['status'] ?? 'active'),
        ];
    }

    usort($rows, static fn ($a, $b) => ($b['disk_mb'] <=> $a['disk_mb']) ?: strcmp($a['domain'], $b['domain']));
    return $rows;
}

/** @return list<array{id:string,label:string,url:string}> */
function hs_admin_probe_targets(array $t): array
{
    global $site_url;
    $targets = [];
    $cmsBase = rtrim((string) $site_url, '/') . '/';
    $targets[] = [
        'id' => 'hosting',
        'label' => (string) ($t['admin_probe_hosting'] ?? 'Hosting CMS'),
        'url' => $cmsBase,
    ];

    $canonical = hs_host_profile_value('canonical_url');
    if (is_string($canonical) && $canonical !== '' && rtrim($canonical, '/') !== rtrim($cmsBase, '/')) {
        $targets[] = [
            'id' => 'home',
            'label' => (string) ($t['admin_probe_home'] ?? 'Landing'),
            'url' => rtrim($canonical, '/') . '/',
        ];
    }

    // Public login page — always reachable without session (full admin may 302)
    $adminLogin = rtrim((string) $site_url, '/') . '/admin/login.php';
    $targets[] = [
        'id' => 'admin',
        'label' => (string) ($t['admin_probe_admin'] ?? 'Admin panel'),
        'url' => $adminLogin,
    ];

    // Optional external targets only if explicitly enabled (avoid long hangs)
    if (defined('HS_ADMIN_PROBE_EXTERNAL') && HS_ADMIN_PROBE_EXTERNAL) {
        $vpsIp = hs_vps_server_ip();
        $sharedIp = hs_server_ip();
        if ($vpsIp !== '' && $vpsIp !== $sharedIp && filter_var($vpsIp, FILTER_VALIDATE_IP)) {
            $targets[] = [
                'id' => 'vps',
                'label' => (string) ($t['admin_probe_vps'] ?? 'VPS') . ' (' . $vpsIp . ')',
                'url' => 'http://' . $vpsIp . '/',
            ];
        }
        $hpUrl = hs_host_profile_value('host_platform_url');
        if (is_string($hpUrl) && $hpUrl !== '') {
            $targets[] = [
                'id' => 'host_platform',
                'label' => (string) ($t['admin_probe_host_platform'] ?? 'Host Platform API'),
                'url' => rtrim($hpUrl, '/') . '/health',
            ];
        }
    }

    return $targets;
}

/**
 * Lightweight HTTP probe for admin dashboard (single attempt, short timeout).
 * Avoids 3×35s timeouts that made "Run check" hang/fail in the browser.
 *
 * @return array<string, mixed>
 */
function hs_admin_probe_url_fast(string $url, int $timeoutSec = 8): array
{
    $url = trim($url);
    $empty = [
        'ok' => false,
        'url' => $url,
        'http_code' => 0,
        'error' => 'fetch',
        'ttfb_ms' => 0,
        'load_ms' => 0,
        'tested_at' => gmdate('c'),
    ];
    if ($url === '') {
        return $empty + ['error' => 'no_url'];
    }

    $timeoutSec = max(2, min(12, $timeoutSec));
    $ua = 'Mozilla/5.0 (compatible; SolaskinnerAdminProbe/1.0)';
    $start = microtime(true);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        // Shared hosting often fails self-HTTPS with strict verify (loopback / SNI).
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_CONNECTTIMEOUT => min(4, $timeoutSec),
            CURLOPT_USERAGENT => $ua,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_ENCODING => '',
            CURLOPT_NOBODY => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $code = (int) ($info['http_code'] ?? 0);
        $ttfb = round(((float) ($info['starttransfer_time'] ?? 0)) * 1000, 1);
        $total = round(((float) ($info['total_time'] ?? (microtime(true) - $start))) * 1000, 1);
        if ($body === false || $code === 0) {
            return $empty + [
                'error' => $err !== '' ? $err : 'fetch',
                'ttfb_ms' => $ttfb,
                'load_ms' => $total,
            ];
        }
        // Any HTTP response is a successful measurement (incl. 302/401 on protected URLs)
        return [
            'ok' => $code > 0 && $code < 500,
            'url' => (string) ($info['url'] ?? $url),
            'http_code' => $code,
            'error' => $code >= 400 ? ('http_' . $code) : '',
            'ttfb_ms' => $ttfb,
            'load_ms' => $total,
            'size_kb' => round(((int) ($info['size_download'] ?? strlen((string) $body))) / 1024, 1),
            'runs' => 1,
            'tested_at' => gmdate('c'),
        ];
    }

    // Fallback without curl
    if (function_exists('hs_perf_http_probe')) {
        $p = hs_perf_http_probe($url, false);
        return [
            'ok' => !empty($p['ok']),
            'url' => $url,
            'http_code' => (int) ($p['code'] ?? 0),
            'error' => (string) ($p['error'] ?? ''),
            'ttfb_ms' => round((float) ($p['ttfb'] ?? 0), 1),
            'load_ms' => round((float) ($p['total'] ?? 0), 1),
            'size_kb' => round(((int) ($p['size'] ?? 0)) / 1024, 1),
            'runs' => 1,
            'tested_at' => gmdate('c'),
        ];
    }

    return $empty;
}

/** @return array<string, mixed> */
function hs_admin_probe_url(string $url): array
{
    // One fast run is enough for admin status (was 3×35s → browser/PHP timeout)
    return hs_admin_probe_url_fast($url, 5);
}

/** @return list<array<string, mixed>> */
function hs_admin_run_all_probes(array $t): array
{
    $out = [];
    @set_time_limit(45);
    @ini_set('max_execution_time', '45');
    foreach (hs_admin_probe_targets($t) as $target) {
        try {
            $probe = hs_admin_probe_url((string) $target['url']);
        } catch (Throwable $e) {
            $probe = [
                'ok' => false,
                'url' => (string) ($target['url'] ?? ''),
                'http_code' => 0,
                'error' => 'exception',
                'ttfb_ms' => 0,
                'load_ms' => 0,
                'tested_at' => gmdate('c'),
            ];
        }
        $out[] = array_merge($target, $probe);
    }
    return $out;
}

/**
 * Ensure last probe results exist (seed only when never run — avoids slow admin loads).
 * Keeps dashboard usable even if JS fetch fails (Trusted Types, network, etc.).
 *
 * @return list<array<string, mixed>>
 */
function hs_admin_ensure_probe_results(array $t, int $maxAgeSec = 1800): array
{
    $store = hs_admin_server_health_load();
    $last = is_array($store['last_probes'] ?? null) ? $store['last_probes'] : [];
    if ($last !== []) {
        return $last;
    }
    // First visit only: one fast pass so the table is not empty without JS
    try {
        $results = hs_admin_run_all_probes($t);
        hs_admin_save_probe_run($results);
        return $results;
    } catch (Throwable $e) {
        return [];
    }
}

function hs_admin_track_server_health(): void
{
    $store = hs_admin_server_health_load();
    $history = is_array($store['history'] ?? null) ? $store['history'] : [];
    $last = $history !== [] ? $history[array_key_last($history)] : null;
    $lastTs = is_array($last) ? strtotime((string) ($last['ts'] ?? '')) : false;
    if ($lastTs !== false && (time() - $lastTs) < 3600) {
        return;
    }

    $platform = hs_admin_platform_resources();
    $server = hs_admin_server_info();
    $history[] = [
        'ts' => gmdate('c'),
        'disk_mb' => $platform['disk_mb'],
        'cpu_avg' => $platform['cpu_avg'],
        'memory_mb' => $platform['memory_mb'],
        'bandwidth_gb' => $platform['bandwidth_gb'],
        'server_disk_pct' => $server['disk_pct'],
        'load_1m' => $server['load_1m'],
    ];

    if (count($history) > 720) {
        $history = array_slice($history, -720);
    }

    $store['history'] = $history;
    $store['server'] = $server;
    $store['platform'] = $platform;
    $store['updated_at'] = gmdate('c');
    hs_admin_server_health_save($store);
}

/** @return array{labels:list<string>,cpu:list<float>,memory:list<int>,disk:list<float>,bandwidth:list<float>} */
function hs_admin_platform_chart_series(int $days = 30): array
{
    $store = hs_admin_server_health_load();
    $history = is_array($store['history'] ?? null) ? $store['history'] : [];
    $cutoff = strtotime('-' . max(1, $days) . ' days');
    $labels = [];
    $cpu = [];
    $memory = [];
    $disk = [];
    $bandwidth = [];
    foreach ($history as $row) {
        if (!is_array($row)) {
            continue;
        }
        $ts = strtotime((string) ($row['ts'] ?? ''));
        if ($ts === false || $ts < $cutoff) {
            continue;
        }
        $labels[] = gmdate('d.m H:i', $ts);
        $cpu[] = round((float) ($row['cpu_avg'] ?? 0), 1);
        $memory[] = (int) ($row['memory_mb'] ?? 0);
        $disk[] = round((float) ($row['disk_mb'] ?? 0), 1);
        $bandwidth[] = round((float) ($row['bandwidth_gb'] ?? 0), 2);
    }
    return compact('labels', 'cpu', 'memory', 'disk', 'bandwidth');
}

/** @return list<array<string, mixed>> */
function hs_admin_probe_history(int $limit = 20): array
{
    $store = hs_admin_server_health_load();
    $probes = is_array($store['probes'] ?? null) ? $store['probes'] : [];
    return array_slice(array_reverse($probes), 0, max(1, $limit));
}

/** @param list<array<string, mixed>> $results */
function hs_admin_save_probe_run(array $results): void
{
    $store = hs_admin_server_health_load();
    $probes = is_array($store['probes'] ?? null) ? $store['probes'] : [];
    $probes[] = [
        'ts' => gmdate('c'),
        'results' => $results,
    ];
    if (count($probes) > 100) {
        $probes = array_slice($probes, -100);
    }
    $store['probes'] = $probes;
    $store['last_probes'] = $results;
    $store['last_probe_at'] = gmdate('c');
    hs_admin_server_health_save($store);
}

function hs_admin_probe_status_class(array $probe): string
{
    if (empty($probe['ok'])) {
        return 'is-down';
    }
    $ttfb = (float) ($probe['ttfb_ms'] ?? 0);
    if ($ttfb <= 300) {
        return 'is-fast';
    }
    if ($ttfb <= 800) {
        return 'is-ok';
    }
    return 'is-slow';
}

function hs_admin_render_server_health_section(array $t, string $lang): string
{
    hs_admin_track_server_health();
    $server = hs_admin_server_info();
    $platform = hs_admin_platform_resources();
    // Seed probes server-side so the table is never empty when PHP can reach itself
    $lastProbes = hs_admin_ensure_probe_results($t, 1800);
    $store = hs_admin_server_health_load();
    $lastProbeAt = (string) ($store['last_probe_at'] ?? '');
    $siteRows = hs_admin_site_usage_rows();
    $chartSeries = hs_admin_platform_chart_series(30);

    // Node disk = entire shared Namecheap volume (often ~10 TB) — informational only
    $diskFs = hs_format_disk_gb((float) $server['disk_used_mb']) . ' / ' . hs_format_disk_gb((float) $server['disk_total_mb']) . ' GB'
        . ' (' . (string) ($server['disk_pct'] ?? 0) . '%)';
    $acctMb = (float) ($server['account_disk_mb'] ?? 0);
    $acctQuota = (float) ($server['account_quota_mb'] ?? 0);
    $acctLabel = hs_format_disk_gb($acctMb) . ' GB';
    if ($acctQuota > 0) {
        $acctLabel .= ' / ' . hs_format_disk_gb($acctQuota) . ' GB';
    }

    $whm = is_array($server['whm'] ?? null) ? $server['whm'] : [];
    $nebulaDiskGb = !empty($whm['enabled']) ? (int) ($whm['max_disk_gb'] ?? 30) : 0;
    $nebulaAcct = !empty($whm['enabled']) ? (int) ($whm['max_accounts'] ?? 0) : 0;
    $nebulaPoolLabel = $nebulaDiskGb > 0
        ? ((string) $nebulaDiskGb . ' GB')
        : '—';
    if ($nebulaAcct > 0 && $nebulaDiskGb > 0) {
        $nebulaPoolLabel .= ' · ' . $nebulaAcct . ' ' . ($t['admin_server_nebula_acct'] ?? 'acct');
    }

    $loadStr = $server['load_1m'] !== null
        ? (string) $server['load_1m']
            . ($server['load_5m'] !== null ? ' · ' . $server['load_5m'] : '')
            . ($server['load_15m'] !== null ? ' · ' . $server['load_15m'] : '')
        : '—';

    // Primary cards: package/account limits first — NOT the ~10 TB shared node volume
    $statCards = [
        ['label' => $t['admin_server_hostname'] ?? 'Hostname', 'value' => (string) $server['hostname']],
        ['label' => $t['admin_server_ip'] ?? 'Server IP', 'value' => (string) $server['shared_ip']],
    ];
    if ($nebulaDiskGb > 0) {
        $statCards[] = [
            'label' => $t['admin_server_disk_nebula'] ?? 'Nebula pool (all client cPanels)',
            'value' => $nebulaPoolLabel,
            'value_style' => 'font-size:1.15rem',
        ];
    }
    $statCards[] = [
        'label' => $t['admin_server_disk_account'] ?? 'This Stellar account (CMS home)',
        'value' => $acctLabel,
        'value_style' => 'font-size:1.05rem',
    ];
    $statCards[] = ['label' => $t['admin_server_load'] ?? 'Load 1 · 5 · 15 min', 'value' => $loadStr];
    $statCards[] = ['label' => $t['admin_server_php'] ?? 'PHP', 'value' => (string) $server['php_version']];
    $statCards[] = ['label' => $t['admin_platform_cpu'] ?? 'Avg CPU (clients)', 'value' => (string) $platform['cpu_avg'] . '%'];
    $statCards[] = ['label' => $t['admin_platform_memory'] ?? 'Memory (clients)', 'value' => (string) $platform['memory_mb'] . ' MB'];
    $statCards[] = ['label' => $t['admin_platform_clients'] ?? 'Clients', 'value' => (string) ($platform['clients'] ?? 0)];
    $statCards[] = ['label' => $t['admin_platform_sites'] ?? 'Sites', 'value' => (string) ($platform['sites'] ?? 0)];
    if (!empty($server['has_separate_vps']) && (string) ($server['vps_ip'] ?? '') !== '') {
        array_splice($statCards, 2, 0, [[
            'label' => $t['admin_server_vps_ip'] ?? 'VPS IP',
            'value' => (string) $server['vps_ip'],
        ]]);
    }
    $serverCards = hs_admin_render_stat_grid($statCards);

    $vpsInfo = '';
    $vps = is_array($server['vps'] ?? null) ? $server['vps'] : [];
    if (!empty($vps['configured'])) {
        $vpsInfo = '<p class="hp-muted hs-admin-vps-line"><i class="fa-solid fa-server"></i> VPS #'
            . hs_h((string) ($vps['id'] ?? '')) . ' · '
            . hs_h((string) ($vps['hostname'] ?? '')) . ' · '
            . hs_h((string) ($vps['state'] ?? '')) . '</p>';
    }
    if (!empty($whm['enabled'])) {
        $vpsInfo .= '<p class="hp-muted hs-admin-vps-line"><i class="fa-solid fa-network-wired"></i> '
            . hs_h($t['admin_server_nebula'] ?? 'Reseller Nebula WHM') . ': '
            . hs_h((string) ($whm['host'] ?? '')) . ' · '
            . hs_h((string) ($whm['user'] ?? '')) . ' · '
            . (int) ($whm['max_accounts'] ?? 0) . ' acct / '
            . (int) ($whm['max_disk_gb'] ?? 0) . ' GB</p>';
    }
    // Always explain node disk so 4229/9898 GB is not mistaken for "our 30 GB"
    $vpsInfo .= '<p class="hp-muted hs-admin-vps-line"><i class="fa-solid fa-hard-drive"></i> '
        . hs_h($t['admin_server_disk_fs_label'] ?? 'Shared node filesystem (PHP disk_total_space)') . ': '
        . '<strong>' . hs_h($diskFs) . '</strong> — '
        . hs_h($t['admin_server_disk_fs_explain'] ?? 'This is the whole Namecheap shared server volume (~TB), not your package. Your Nebula reseller pool is the GB limit above; client plans share that pool.')
        . '</p>';

    $probeRows = '';
    if ($lastProbes === []) {
        $probeRows = '<tr><td colspan="5" class="hp-muted">' . hs_h($t['admin_probe_none'] ?? 'No response checks yet. Run a check below.') . '</td></tr>';
    } else {
        foreach ($lastProbes as $p) {
            $cls = hs_admin_probe_status_class($p);
            $status = !empty($p['ok'])
                ? 'HTTP ' . (int) ($p['http_code'] ?? 0)
                : hs_h((string) ($p['error'] ?? 'error'));
            $probeRows .= '<tr class="hs-admin-probe-row ' . hs_h($cls) . '">'
                . '<td>' . hs_h((string) ($p['label'] ?? '')) . '</td>'
                . '<td><code class="hs-admin-probe-url">' . hs_h((string) ($p['url'] ?? '')) . '</code></td>'
                . '<td><strong>' . hs_h((string) ($p['ttfb_ms'] ?? '—')) . '</strong> ms</td>'
                . '<td>' . hs_h((string) ($p['load_ms'] ?? '—')) . ' ms</td>'
                . '<td>' . $status . '</td></tr>';
        }
    }

    $siteTableRows = '';
    if ($siteRows === []) {
        $siteTableRows = '<tr><td colspan="5" class="hp-muted">' . hs_h($t['admin_sites_empty'] ?? 'No sites.') . '</td></tr>';
    } else {
        foreach ($siteRows as $row) {
            $url = (string) ($row['url'] ?? '');
            $link = $url !== ''
                ? '<a href="' . hs_h($url) . '" target="_blank" rel="noopener">' . hs_h($row['domain']) . '</a>'
                : hs_h($row['domain']);
            $siteTableRows .= '<tr>'
                . '<td>' . $link . '</td>'
                . '<td>' . hs_h($row['owner']) . '</td>'
                . '<td>' . hs_h($row['plan']) . '</td>'
                . '<td>' . hs_h(hs_format_disk_gb((float) $row['disk_mb'])) . ' GB</td>'
                . '<td><span class="hs-plan-status hs-plan-status-' . hs_h($row['status'] === 'active' ? 'active' : 'pending') . '">' . hs_h($row['status']) . '</span></td>'
                . '</tr>';
        }
    }

    $probeMeta = $lastProbeAt !== ''
        ? '<p class="hp-muted hs-admin-probe-meta">' . hs_h($t['admin_probe_last'] ?? 'Last check') . ': ' . hs_h(hs_format_date($lastProbeAt)) . '</p>'
        : '';

    $chartJson = json_encode([
        'series' => $chartSeries,
        'i18n' => [
            'cpu' => $t['admin_platform_cpu_chart'] ?? 'Avg CPU %',
            'memory' => $t['admin_platform_memory_chart'] ?? 'Memory MB',
            'disk' => $t['admin_platform_disk_chart'] ?? 'Client disk MB',
            'bandwidth' => $t['admin_platform_bw_chart'] ?? 'Bandwidth GB',
        ],
    ], JSON_UNESCAPED_UNICODE);

    return '<section class="hs-admin-server-health">'
        . '<h2 style="margin:0 0 1rem"><i class="fa-solid fa-server"></i> ' . hs_h($t['admin_server_title'] ?? 'Server & platform') . '</h2>'
        . $serverCards
        . $vpsInfo
        . '<div class="hs-admin-server-grid">'
        . '<section class="hp-card hs-chart-card">'
        . '<h3 class="hp-card-title">' . hs_h($t['admin_platform_chart_title'] ?? 'Platform resources (30 days)') . '</h3>'
        . '<div class="hp-card-body"><div class="hs-chart-wrap" style="height:280px"><canvas id="admin-chart-platform"></canvas></div></div>'
        . '</section>'
        . '<section class="hp-card hs-admin-probe-card">'
        . '<div class="hp-card-title hs-admin-probe-head">'
        . '<span><i class="fa-solid fa-gauge-high"></i> ' . hs_h($t['admin_probe_title'] ?? 'Server response check') . '</span>'
        . '<button type="button" class="hs-btn hs-btn-primary hp-dash-btn-sm" data-hs-admin-probe-run>'
        . '<i class="fa-solid fa-play"></i> ' . hs_h($t['admin_probe_run'] ?? 'Run check') . '</button>'
        . '</div>'
        . '<div class="hp-card-body">'
        . $probeMeta
        . '<div class="hs-table-wrap"><table class="hs-table hs-admin-probe-table"><thead><tr>'
        . '<th>' . hs_h($t['admin_probe_col_target'] ?? 'Target') . '</th>'
        . '<th>URL</th>'
        . '<th>TTFB</th>'
        . '<th>' . hs_h($t['admin_probe_col_load'] ?? 'Response') . '</th>'
        . '<th>' . hs_h($t['admin_probe_col_status'] ?? 'Status') . '</th>'
        . '</tr></thead><tbody data-hs-admin-probe-body>' . $probeRows . '</tbody></table></div>'
        . '<p class="hp-muted hs-chart-hint">' . hs_h($t['admin_probe_hint'] ?? 'Measures DNS, connect, TTFB and total load time from this server.') . '</p>'
        . '</div></section>'
        . '</div>'
        . '<h3 style="margin:1.5rem 0 .75rem">' . hs_h($t['admin_sites_usage_title'] ?? 'Site resource usage') . '</h3>'
        . '<div class="hs-table-wrap"><table class="hs-table"><thead><tr>'
        . '<th>' . hs_h($t['admin_sites_usage_domain'] ?? 'Site') . '</th>'
        . '<th>' . hs_h($t['admin_domain_orders_col_client'] ?? 'Client') . '</th>'
        . '<th>' . hs_h($t['admin_plans_type_hosting'] ?? 'Plan') . '</th>'
        . '<th>' . hs_h($t['admin_disk'] ?? 'Disk') . '</th>'
        . '<th>' . hs_h($t['admin_client_status'] ?? 'Status') . '</th>'
        . '</tr></thead><tbody>' . $siteTableRows . '</tbody></table></div>'
        . '<script>window.HS_ADMIN_PLATFORM_CHART=' . $chartJson . ';</script>'
        . '</section>';
}