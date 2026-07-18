<?php
declare(strict_types=1);

require_once __DIR__ . '/site-config.php';
// Resource / limit helpers need plan catalog (admin dashboard, panel bootstrap).
if (is_file(__DIR__ . '/plans.php')) {
    require_once __DIR__ . '/plans.php';
}

function hs_settings_file(): string
{
    return hs_data_file('user-settings');
}

function hs_all_user_settings(): array
{
    if (hs_is_mysql_installed()) {
        return hs_db_load_user_settings_map();
    }
    return hs_read_json(hs_settings_file());
}

function hs_save_all_user_settings(array $data): bool
{
    if (hs_is_mysql_installed()) {
        return hs_db_save_user_settings_map($data);
    }
    return hs_write_json(hs_settings_file(), $data);
}

function hs_user_settings_defaults(): array
{
    return [
        'php_version' => '8.2',
        'php_extensions' => [],
        'memory_limit' => '256M',
        'max_execution_time' => '120',
        'upload_max_filesize' => '64M',
        'post_max_size' => '64M',
        'max_input_vars' => '3000',
        'max_file_uploads' => '20',
        'allow_url_fopen' => '1',
        'allow_url_include' => '0',
        'short_open_tag' => '0',
        'default_charset' => 'UTF-8',
        'error_reporting' => 'E_ALL',
        'session_gc_maxlifetime' => '1440',
        'display_errors' => '0',
        'primary_domain' => hs_default_primary_domain(),
        'extra_domains' => [],
        'domain_registry' => [],
        'active_domain' => '',
        'ssl_enabled' => true,
        'ssh_enabled' => true,
        'domains' => [],
        'databases' => [],
        'cache_enabled' => true,
        'cdn_enabled' => false,
        'perf_ai_enabled' => false,
        'malware_last_scan' => '',
        'malware_status' => 'clean',
        'malware_findings' => [],
        'malware_scanned' => 0,
        'firewall_enabled' => true,
        'ip_blocklist' => [],
        'wp_auto_update' => true,
        'redirects' => [],
        'cron_jobs' => [],
        'dns_records' => [],
        'activity_log' => [],
        'backups' => [],
        'backup_schedule' => 'day',
        'backup_auto' => false,
        'backup_cron_token' => '',
        'max_input_time' => '120',
        'php_timezone' => 'Europe/Kyiv',
        'php_probe_token' => '',
        'php_synced' => false,
        'dev_mode' => false,
        'logo_file' => '',
        'db_remote' => false,
        'db_remote_ips' => '',
        'git_url' => '',
        'git_branch' => 'main',
        'git_deploy_subdir' => '',
        'git_token' => '',
        'git_last_output' => '',
        'htpasswd_user' => '',
        'htpasswd_pass' => '',
        'ip_allowlist' => [],
        'hotlink_protect' => false,
        'search_indexing' => true,
        'wp_staging_sites' => [],
        'wp_preset' => 'default',
        'migrate_queue' => [],
        'error_log_lines' => [],
        'ftp_password_token' => '',
        'usage_history' => [],
        'analytics_visits' => 0,
        'analytics_unique' => 0,
        'git_last_deploy' => '',
        'mailboxes' => [],
        'ai' => [
            'enabled' => false,
            'provider' => 'openai',
            'openai_api_key' => '',
            'grok_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'grok_model' => 'grok-3-mini',
        ],
    ];
}

function hs_user_settings_get(string $userId): array
{
    $all = hs_all_user_settings();
    $defaults = hs_user_settings_defaults();
    if (!isset($all[$userId]) || !is_array($all[$userId])) {
        $merged = $defaults;
    } else {
        $merged = array_merge($defaults, $all[$userId]);
    }
    if (hs_is_mysql_installed()) {
        $clientDbs = hs_db_client_databases_for_user($userId);
        if ($clientDbs !== []) {
            $merged['databases'] = $clientDbs;
        }
    }
    return $merged;
}

function hs_user_settings_save(string $userId, array $patch): bool
{
    $all = hs_all_user_settings();
    $current = hs_user_settings_get($userId);
    $all[$userId] = array_merge($current, $patch);
    if (!hs_save_all_user_settings($all)) {
        return false;
    }
    $user = hs_user_by_id($userId);
    if ($user !== null) {
        $diskKeys = ['redirects', 'hotlink_protect', 'search_indexing', 'htpasswd_user', 'htpasswd_pass'];
        foreach ($diskKeys as $k) {
            if (array_key_exists($k, $patch)) {
                hs_apply_site_config((string) ($user['username'] ?? ''), $all[$userId]);
                break;
            }
        }
    }
    return true;
}

function hs_folder_size_mb(string $path): float
{
    if (!is_dir($path)) {
        return 0.0;
    }
    $size = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $size += $f->getSize();
        }
    }
    return round($size / 1024 / 1024, 1);
}

function hs_add_subdomain(string $userId, string $name, string $folder = ''): bool
{
    require_once __DIR__ . '/subdomain-dns.php';
    require_once __DIR__ . '/storage.php';
    $name = strtolower(trim(preg_replace('/[^a-z0-9-]/', '', $name) ?? ''));
    if ($name === '' || strlen($name) > 32) {
        return false;
    }
    $user = hs_user_by_id($userId);
    if ($user === null) {
        return false;
    }
    $username = hs_user_public_rel_prefix($user);
    if ($username === '') {
        return false;
    }

    $folder = hs_normalize_public_html_folder($folder);
    // Default document root = this client's workspace only (never global public_html/).
    if ($folder === '') {
        $folder = $username;
    }
    if (!hs_public_html_folder_allowed_for_user($user, $folder)) {
        return false;
    }
    $abs = hs_public_path($folder);
    if (!is_dir($abs)) {
        if (!@mkdir($abs, 0755, true) && !is_dir($abs)) {
            return false;
        }
    }

    $settings = hs_user_settings_get($userId);
    $domains = $settings['domains'] ?? [];
    if (!is_array($domains)) {
        $domains = [];
    }
    // Drop any legacy entries that pointed outside this account (privacy fix).
    $domains = array_values(array_filter(
        $domains,
        static function ($d) use ($user): bool {
            if (!is_array($d)) {
                return false;
            }
            $f = hs_normalize_public_html_folder((string) ($d['folder'] ?? ''));
            if ($f === '') {
                return true;
            }

            return hs_public_html_folder_allowed_for_user($user, $f);
        }
    ));
    foreach ($domains as $d) {
        if (($d['name'] ?? '') === $name) {
            return false;
        }
    }
    $domains[] = [
        'name' => $name,
        'folder' => $folder,
        'created_at' => date('c'),
    ];
    if (!hs_user_settings_save($userId, ['domains' => $domains])) {
        return false;
    }
    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? hs_default_primary_domain())));
    hs_dns_ensure_subdomain_record($userId, $name, $primary, $user);
    $updated = hs_user_settings_get($userId);
    hs_apply_subdomain_routes((string) ($user['username'] ?? ''), $updated);
    return true;
}

function hs_normalize_db_label(?string $label): ?string
{
    if ($label === null) {
        return null;
    }
    $s = strtolower(trim($label));
    $s = preg_replace('/[^a-z0-9_]/', '', $s) ?? '';
    if ($s === '' || strlen($s) > 32) {
        return null;
    }
    return $s;
}

/**
 * @return array{ok:bool,entry?:array<string,mixed>,error?:string}
 */
function hs_create_database(string $userId, string $username, ?array $user = null, ?string $label = null, ?string $website = null): array
{
    if ($user === null) {
        $user = hs_user_by_id($userId) ?? [];
    }
    $labelNorm = hs_normalize_db_label($label);
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/mysql-provision.php';
        if (!hs_mysql_provision_enabled()) {
            return ['ok' => false, 'error' => 'provision_config'];
        }
        return hs_provision_client_database($userId, $username, $user, false, $labelNorm, $website);
    }
    $settings = hs_user_settings_get($userId);
    $dbs = $settings['databases'] ?? [];
    if (!is_array($dbs)) {
        $dbs = [];
    }
    if (count($dbs) >= hs_user_database_limit($user)) {
        return ['ok' => false, 'error' => 'limit'];
    }
    $slug = preg_replace('/[^a-z0-9]/', '', strtolower($username)) ?: 'user';
    $suffix = $labelNorm ?? bin2hex(random_bytes(3));
    $dbName = 'u' . $slug . '_' . $suffix;
    $dbUser = $dbName;
    $dbPass = bin2hex(random_bytes(8));
    $entry = [
        'id' => 'db_' . bin2hex(random_bytes(6)),
        'name' => $dbName,
        'user' => $dbUser,
        'password' => $dbPass,
        'host' => 'localhost',
        'logical_name' => $labelNorm ?? $dbName,
        'website' => $website !== null && trim($website) !== '' ? trim($website) : null,
        'created_at' => gmdate('c'),
        'provisioned' => false,
    ];
    $dbs[] = $entry;
    if (!hs_user_settings_save($userId, ['databases' => $dbs])) {
        return ['ok' => false, 'error' => 'save'];
    }
    return ['ok' => true, 'entry' => $entry];
}

/**
 * @return array{ok:bool,error?:string}
 */
function hs_delete_database(string $userId, string $dbId, ?array $user = null): array
{
    if ($dbId === '') {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if ($user === null) {
        $user = hs_user_by_id($userId) ?? [];
    }
    if (function_exists('hs_is_demo_panel_user') && hs_is_demo_panel_user($user)) {
        return ['ok' => false, 'error' => 'demo'];
    }
    $settings = hs_user_settings_get($userId);
    $dbs = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    $found = null;
    $foundIdx = -1;
    foreach ($dbs as $i => $db) {
        if (!is_array($db)) {
            continue;
        }
        if ((string) ($db['id'] ?? '') === $dbId) {
            $found = $db;
            $foundIdx = (int) $i;
            break;
        }
    }
    if ($found === null || $foundIdx < 0) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (!empty($found['primary'])) {
        return ['ok' => false, 'error' => 'primary'];
    }
    if (!empty($found['shared'])) {
        return ['ok' => false, 'error' => 'shared'];
    }
    if (!empty($found['provisioned']) && hs_is_mysql_installed()) {
        require_once __DIR__ . '/mysql-provision.php';
        if (!hs_mysql_provision_shared_mode()) {
            $drop = hs_mysql_provision_drop_db((string) ($found['name'] ?? ''), (string) ($found['user'] ?? ''));
            if (!$drop['ok']) {
                return ['ok' => false, 'error' => $drop['error'] ?? 'drop_failed'];
            }
        }
        hs_db_delete_client_database($userId, $dbId);
    }
    array_splice($dbs, $foundIdx, 1);
    if (!hs_user_settings_save($userId, ['databases' => $dbs])) {
        return ['ok' => false, 'error' => 'save'];
    }
    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'db_delete', (string) ($found['name'] ?? $dbId));
    }

    return ['ok' => true];
}

function hs_count_inodes(string $path): int
{
    if (!is_dir($path)) {
        return 0;
    }
    $count = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $f) {
        $count++;
        if ($count > 500000) {
            break;
        }
    }
    return $count;
}

function hs_format_compact_num(int $n): string
{
    if ($n >= 1000000) {
        return rtrim(rtrim(number_format($n / 1000000, 2, '.', ''), '0'), '.') . 'M';
    }
    if ($n >= 1000) {
        return rtrim(rtrim(number_format($n / 1000, 2, '.', ''), '0'), '.') . 'K';
    }
    return (string) $n;
}

function hs_format_disk_gb(float $mb): string
{
    $gb = $mb / 1024;
    if ($gb < 0.1) {
        return '0.1';
    }
    if ($gb < 10) {
        return rtrim(rtrim(number_format($gb, 1, '.', ''), '0'), '.');
    }
    return (string) (int) round($gb);
}

function hs_plan_display_limits(string $planId): array
{
    require_once __DIR__ . '/plan-catalog.php';
    $p = hs_plan_catalog_plan($planId);
    return [
        'disk_gb' => (int) ($p['disk_gb'] ?? 5),
        'inodes' => (int) ($p['inodes'] ?? 50000),
        'sites' => (int) ($p['sites'] ?? 1),
    ];
}

function hs_performance_scores(array $user, array $sites): array
{
    $baseDesktop = 88 + min(8, count($sites) * 2);
    $baseMobile = 68 + min(8, count($sites));
    if (($user['plan'] ?? '') === 'pro') {
        $baseDesktop += 2;
        $baseMobile += 2;
    }
    $scanBase = strtotime('-2 days 18:44');
    return [
        'desktop' => min(100, $baseDesktop),
        'mobile' => min(100, $baseMobile),
        'desktop_scan' => date('Y-m-d H:i', $scanBase),
        'mobile_scan' => date('Y-m-d H:i', $scanBase + 300),
    ];
}

function hs_resource_usage(array $user, array $sites): array
{
    if (!function_exists('hs_plan') && is_file(__DIR__ . '/plans.php')) {
        require_once __DIR__ . '/plans.php';
    }
    $planId = (string) ($user['plan'] ?? 'starter');
    $plan = function_exists('hs_plan')
        ? hs_plan($planId)
        : ['storage_mb' => 5120, 'id' => $planId];
    $storageMax = (int) ($plan['storage_mb'] ?? 512);
    $sitesMax = hs_user_site_limit($user);
    $userPath = hs_public_path((string) ($user['username'] ?? ''));
    $usedMb = hs_folder_size_mb($userPath);
    $cpu = min(95, 12 + count($sites) * 8 + (int) ($usedMb / 50));
    $inodesPct = min(98, 5 + count($sites) * 15 + (int) ($usedMb * 2));
    $limits = hs_plan_display_limits($planId);
    $inodesUsed = hs_count_inodes($userPath);
    $perf = hs_performance_scores($user, $sites);
    $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    if (!empty($settings['speed_tested_at'])) {
        $perf['desktop'] = (int) ($settings['speed_desktop'] ?? $perf['desktop']);
        $perf['mobile'] = (int) ($settings['speed_mobile'] ?? $perf['mobile']);
        $perf['desktop_scan'] = hs_format_date((string) $settings['speed_tested_at']);
        $perf['mobile_scan'] = $perf['desktop_scan'];
    }
    return [
        'storage_used_mb' => $usedMb,
        'storage_max_mb' => $storageMax,
        'disk_used_gb' => hs_format_disk_gb($usedMb),
        'disk_max_gb' => $limits['disk_gb'],
        'sites_used' => count($sites),
        'sites_max' => $sitesMax,
        'sites_max_display' => $limits['sites'],
        'cpu_percent' => $cpu,
        'cpu_display' => max(1, min(99, (int) round($cpu / max(1, count($sites) + 1)))),
        'inodes_percent' => $inodesPct,
        'inodes_used' => $inodesUsed,
        'inodes_used_fmt' => hs_format_compact_num($inodesUsed),
        'inodes_max' => $limits['inodes'],
        'inodes_max_fmt' => hs_format_compact_num($limits['inodes']),
        'memory_mb' => (int) round(32 + count($sites) * 8 + $usedMb * 0.45),
        'bandwidth_gb' => min(100, round($usedMb / 10, 1)),
        'bandwidth_max_gb' => match ($planId) {
            'vps' => 3072,
            default => $storageMax >= 2048 ? 100 : 50,
        },
        'perf_desktop' => $perf['desktop'],
        'perf_mobile' => $perf['mobile'],
        'perf_desktop_scan' => $perf['desktop_scan'],
        'perf_mobile_scan' => $perf['mobile_scan'],
    ];
}

function hs_plan_hosting_label(string $planId, array $t): string
{
    $key = 'plan_hosting_' . $planId;
    return (string) ($t[$key] ?? $t['plan_' . $planId] ?? $planId);
}