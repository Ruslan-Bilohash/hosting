<?php
declare(strict_types=1);

/**
 * Real NS for panel DNS zone (must resolve on the public internet).
 *
 * @return array{0:string,1:string}
 */
function hs_server_ns_pair(): array
{
    $ns = function_exists('hs_display_nameservers') ? hs_display_nameservers() : [];
    if (count($ns) < 2 && function_exists('hs_registry_nameservers')) {
        $ns = hs_registry_nameservers();
    }
    $ns1 = strtolower(trim((string) ($ns[0] ?? '')));
    $ns2 = strtolower(trim((string) ($ns[1] ?? '')));
    // Never show fictional brand NS that do not exist in DNS
    if ($ns1 === '' || str_contains($ns1, 'example.com') || str_starts_with($ns1, 'ns1.solaskinner.') || str_starts_with($ns1, 'ns2.solaskinner.')) {
        $ns1 = 'dns1.namecheaphosting.com';
    }
    if ($ns2 === '' || str_contains($ns2, 'example.com') || str_starts_with($ns2, 'ns1.solaskinner.') || str_starts_with($ns2, 'ns2.solaskinner.')) {
        $ns2 = 'dns2.namecheaphosting.com';
    }

    return [$ns1, $ns2];
}

/** Hostinger-style values shown in the demo panel (not client-specific). */
function hs_demo_panel_constants(): array
{
    [$ns1, $ns2] = hs_server_ns_pair();

    return [
        'ip' => function_exists('hs_server_ip') ? hs_server_ip() : HS_SERVER_IP,
        'server_name' => 'demo-server',
        'location' => function_exists('hs_host_profile_value') ? (string) (hs_host_profile_value('server_location') ?: 'Europe') : 'Europe',
        'backup_location' => 'EU',
        'ns1' => $ns1,
        'ns2' => $ns2,
        'ftp_host' => hs_default_primary_domain(),
        'ftp_path' => 'public_html',
        'ftp_user_prefix' => HS_FTP_USER_PREFIX,
    ];
}

/** @param array<string, mixed>|null $user */
function hs_server_constants(?array $user = null): array
{
    if ($user !== null && hs_is_demo_panel_user($user)) {
        return hs_demo_panel_constants();
    }
    [$ns1, $ns2] = hs_server_ns_pair();
    $ip = function_exists('hs_server_ip') ? hs_server_ip() : HS_SERVER_IP;
    $loc = function_exists('hs_host_profile_value') ? (string) (hs_host_profile_value('server_location') ?: 'Europe') : 'Europe';

    return [
        'ip' => $ip,
        'server_name' => function_exists('hs_host_profile_value') ? (string) (hs_host_profile_value('server_hostname') ?: 'server1') : 'server1',
        'location' => $loc,
        'backup_location' => 'EU',
        'ns1' => $ns1,
        'ns2' => $ns2,
        'ftp_host' => hs_default_primary_domain(),
        'ftp_path' => 'public_html',
        'ftp_user_prefix' => HS_FTP_USER_PREFIX,
    ];
}

/** @return array<string, mixed> */
function hs_plan_hostinger_specs(string $planId): array
{
    require_once __DIR__ . '/plan-catalog.php';
    $p = hs_plan_catalog_plan($planId);
    return [
        'disk_gb' => (int) ($p['disk_gb'] ?? 5),
        'ram_mb' => (int) ($p['ram_mb'] ?? 1024),
        'cpu_cores' => (int) ($p['cpu_cores'] ?? 1),
        'inodes' => (int) ($p['inodes'] ?? 150000),
        'sites' => (int) ($p['sites'] ?? 1),
        'max_processes' => (int) ($p['max_processes'] ?? 40),
        'php_workers' => (int) ($p['php_workers'] ?? 20),
        'traffic' => (string) ($p['traffic'] ?? 'unlimited'),
    ];
}

function hs_is_demo_panel_user(?array $user): bool
{
    if (!is_array($user)) {
        return false;
    }
    if (function_exists('hs_impersonation_active') && hs_impersonation_active()) {
        return false;
    }
    if ((string) ($user['username'] ?? '') === 'demo') {
        return true;
    }
    return defined('HS_DEMO_MODE') && HS_DEMO_MODE && (string) ($user['username'] ?? '') === 'demo';
}

/** Domain shown on plan / FTP panels (demo always uses Hostinger-style host). */
function hs_plan_display_domain(array $user, array $settings): string
{
    if (hs_is_demo_panel_user($user)) {
        return (string) hs_demo_panel_constants()['ftp_host'];
    }
    $srv = hs_server_constants();
    $domain = trim((string) ($settings['active_domain'] ?? $settings['primary_domain'] ?? ''));
    return $domain !== '' ? $domain : (string) $srv['ftp_host'];
}

/** hostinger = prefix.domain (legacy hPanel); cpanel = main cPanel FTP user (shared multi-tenant). */
function hs_ftp_username_mode(): string
{
    $mode = function_exists('hs_host_profile_value') ? (string) (hs_host_profile_value('ftp_username_mode') ?? '') : '';
    $mode = strtolower(trim($mode));

    return in_array($mode, ['hostinger', 'cpanel', 'client'], true) ? $mode : 'cpanel';
}

/**
 * FTP login username shown to clients.
 * Shared Namecheap: real cPanel account user (e.g. solaffhv) — NOT a fake prefix.domain string.
 */
function hs_ftp_username(string $domain, ?array $user = null): string
{
    $srv = hs_server_constants($user);
    if ($user !== null && hs_is_demo_panel_user($user)) {
        $domain = (string) $srv['ftp_host'];
    }
    $mode = hs_ftp_username_mode();
    if ($mode === 'cpanel') {
        // Real shared hosting FTP account (cPanel system user)
        if (defined('HS_SSH_USER') && HS_SSH_USER !== '') {
            return (string) HS_SSH_USER;
        }
        $prefix = trim((string) ($srv['ftp_user_prefix'] ?? ''));

        return $prefix !== '' ? $prefix : 'ftp';
    }
    if ($mode === 'client' && $user !== null) {
        return preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    }
    // hostinger legacy
    $domain = trim($domain) !== '' ? trim($domain) : (string) $srv['ftp_host'];
    $prefix = trim((string) ($srv['ftp_user_prefix'] ?? ''));

    return $prefix !== '' ? ($prefix . '.' . $domain) : $domain;
}

/** Hostinger-style upload root shown on plan details. */
function hs_ftp_plan_path(): string
{
    return (string) (hs_server_constants()['ftp_path'] ?? 'public_html');
}

/**
 * Client site folder as shown in panel (domain docroot when set).
 * Example: public_html/braserver/solaskinner.shop/
 */
function hs_ftp_account_path(string $username, ?array $user = null): string
{
    if ($user !== null && hs_is_demo_panel_user($user)) {
        return hs_ftp_plan_path();
    }
    $username = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';
    $settings = null;
    $userId = is_array($user) ? (string) ($user['id'] ?? '') : '';
    if ($userId !== '' && function_exists('hs_user_settings_get')) {
        require_once __DIR__ . '/user-settings.php';
        $settings = hs_user_settings_get($userId);
    }
    if (is_array($user) && function_exists('hs_domain_docroot_rel') && function_exists('hs_active_domain')) {
        require_once __DIR__ . '/domain-workspace.php';
        $domain = function_exists('hs_active_domain')
            ? hs_active_domain($settings ?? [])
            : (string) (($settings['active_domain'] ?? $settings['primary_domain'] ?? '') ?? '');
        $domain = strtolower(trim((string) $domain));
        if ($domain !== '' && str_contains($domain, '.')) {
            $rel = hs_domain_docroot_rel($user, $domain, $settings);

            return 'public_html/' . trim($rel, '/') . '/';
        }
    }

    return hs_ftp_plan_path() . '/' . $username . '/';
}

/**
 * Path from cPanel home for FileZilla (may include nested public_html/ on multi-tenant).
 */
function hs_ftp_home_path(array $user, ?array $settings = null): string
{
    $username = (string) ($user['username'] ?? 'user');
    $display = trim(hs_ftp_account_path($username, $user), '/');
    // Disk: /home/{cpanel}/public_html/public_html/{user}/{domain}/ when CMS lives in public_html
    if (function_exists('hs_public_path') && function_exists('hs_domain_docroot_rel')) {
        $userId = (string) ($user['id'] ?? '');
        if ($settings === null && $userId !== '' && function_exists('hs_user_settings_get')) {
            $settings = hs_user_settings_get($userId);
        }
        $domain = is_array($settings)
            ? strtolower(trim((string) (function_exists('hs_active_domain') ? hs_active_domain($settings) : ($settings['active_domain'] ?? ''))))
            : '';
        if ($domain !== '' && str_contains($domain, '.')) {
            $rel = hs_domain_docroot_rel($user, $domain, $settings);
            $abs = hs_public_path($rel);
            $home = getenv('HOME') ?: ('/home/' . (defined('HS_SSH_USER') ? HS_SSH_USER : 'user'));
            if (str_starts_with($abs, $home . '/')) {
                return substr($abs, strlen($home) + 1);
            }
        }
    }

    return $display;
}

/** FTP IP/hostname labels for plan & files panels (demo uses ftp:// prefix). */
function hs_ftp_display_host(string $ipOrHost, ?array $user = null): string
{
    $val = trim($ipOrHost);
    if ($user !== null && hs_is_demo_panel_user($user) && !str_starts_with($val, 'ftp://')) {
        return 'ftp://' . $val;
    }
    return $val;
}

function hs_ssh_password_is_server(): bool
{
    return hs_ssh_password_available();
}

/**
 * SSH connection context for account / technical panels.
 *
 * @param array<string,mixed> $user
 * @param array<string,mixed>|null $settings
 * @return array{host:string,port:int,user:string,password:string,command:string,enabled:bool,folder:string}
 */
function hs_ssh_client_context(array $user, ?array $settings = null): array
{
    $userId = (string) ($user['id'] ?? '');
    $username = (string) ($user['username'] ?? 'user');
    if ($settings === null && $userId !== '') {
        require_once __DIR__ . '/user-settings.php';
        $settings = hs_user_settings_get($userId);
    }
    $settings = is_array($settings) ? $settings : [];
    $host = defined('HS_SSH_HOST') ? (string) HS_SSH_HOST : (function_exists('hs_server_ip') ? hs_server_ip() : '');
    $port = defined('HS_SSH_PORT') ? (int) HS_SSH_PORT : 22;
    // Shared multi-tenant: SSH is the main cPanel user (not panel login username)
    $sshUser = defined('HS_SSH_USER') && HS_SSH_USER !== ''
        ? (string) HS_SSH_USER
        : hs_ftp_username((string) ($settings['active_domain'] ?? $settings['primary_domain'] ?? ''), $user);
    $password = '';
    // Do not claim panel master password works for SSH on shared cPanel (usually false)
    $enabled = !empty($settings['ssh_enabled']);
    $folder = hs_ftp_account_path($username, $user);
    $command = 'ssh -p ' . $port . ' ' . $sshUser . '@' . $host;
    $dedicated = function_exists('hs_whm_enabled') && hs_whm_enabled()
        && function_exists('hs_cpanel_account_for_user')
        && hs_cpanel_account_for_user($userId) !== null;

    return [
        'host' => $host,
        'port' => $port,
        'user' => $sshUser,
        'password' => $password,
        'command' => $command,
        'enabled' => $enabled,
        'folder' => $folder,
        'shared' => !$dedicated,
        'dedicated' => $dedicated,
    ];
}

function hs_ensure_ssh_password_token(string $userId): string
{
    require_once __DIR__ . '/master-password.php';
    $pass = hs_master_password_plain($userId);
    if ($pass !== '') {
        return $pass;
    }
    $pass = hs_generate_secure_password();
    hs_master_password_sync($userId, $pass);
    return $pass;
}

/** Password shown in SSH panel (server config takes priority). */
function hs_ssh_display_password(string $userId): string
{
    require_once __DIR__ . '/master-password.php';
    return hs_master_password_plain($userId);
}

/** Seed Hostinger-style panel constants for demo accounts (once per user). */
function hs_seed_demo_panel_settings(): void
{
    if (!defined('HS_DEMO_MODE') || !HS_DEMO_MODE) {
        return;
    }
    require_once __DIR__ . '/user-settings.php';
    $srv = hs_demo_panel_constants();
    $defaultDomain = (string) $srv['ftp_host'];
    $demoPrimary = 'demo.com';
    $demoParked = 'domain.com';
    $now = gmdate('c');
    $expires = gmdate('c', strtotime('+1 year'));

    foreach (['u_demo', 'u_admin_client'] as $uid) {
        $cur = hs_user_settings_get($uid);
        $isDemoUser = $uid === 'u_demo';
        $domain = $isDemoUser ? $demoPrimary : $defaultDomain;
        $dns = [
            ['type' => 'NS', 'host' => '@', 'value' => $srv['ns1'], 'ttl' => 14400, 'created_at' => $now],
            ['type' => 'NS', 'host' => '@', 'value' => $srv['ns2'], 'ttl' => 14400, 'created_at' => $now],
            ['type' => 'A', 'host' => '@', 'value' => $srv['ip'], 'ttl' => 14400, 'created_at' => $now],
            ['type' => 'A', 'host' => 'www', 'value' => $srv['ip'], 'ttl' => 14400, 'created_at' => $now],
            ['type' => 'CNAME', 'host' => 'ftp', 'value' => $domain, 'ttl' => 14400, 'created_at' => $now],
        ];
        $registry = $isDemoUser ? [
            [
                'domain' => $demoPrimary,
                'role' => 'primary',
                'registered_at' => $now,
                'expires_at' => $expires,
                'purchased' => true,
                'order_id' => 'do_demo_seed',
            ],
            [
                'domain' => $demoParked,
                'role' => 'parked',
                'registered_at' => $now,
                'expires_at' => $expires,
                'purchased' => true,
                'order_id' => 'do_demo_seed2',
            ],
        ] : [];
        $patch = [
            'primary_domain' => $domain,
            'active_domain' => $domain,
            'extra_domains' => $isDemoUser ? [$demoParked] : ($cur['extra_domains'] ?? []),
            'domain_registry' => $isDemoUser ? $registry : ($cur['domain_registry'] ?? []),
            'ftp_password_token' => 'demo',
            'ssh_password_token' => 'demo',
            'dns_records' => $dns,
            'demo_panel_seeded' => true,
        ];
        $needsSeed = $isDemoUser
            || empty($cur['demo_panel_seeded'])
            || strtolower((string) ($cur['primary_domain'] ?? '')) !== strtolower($domain)
            || !is_array($cur['dns_records'] ?? null)
            || $cur['dns_records'] === [];
        if ($isDemoUser) {
            $reg = is_array($cur['domain_registry'] ?? null) ? $cur['domain_registry'] : [];
            $doms = array_map(static fn(array $e): string => strtolower((string) ($e['domain'] ?? '')), array_filter($reg, 'is_array'));
            $needsSeed = $needsSeed
                || strtolower((string) ($cur['primary_domain'] ?? '')) !== $demoPrimary
                || !in_array($demoParked, $doms, true);
        }
        if (!$needsSeed) {
            continue;
        }
        hs_user_settings_save($uid, array_merge($cur, $patch));
    }
}

function hs_generate_secure_password(int $length = 16): string
{
    $length = max(12, min(32, $length));
    $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
    $max = strlen($chars) - 1;
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, $max)];
    }
    return $pass;
}