<?php
declare(strict_types=1);

/** Hostinger-style values shown in the demo panel (not client-specific). */
function hs_demo_panel_constants(): array
{
    return [
        'ip' => '45.84.204.61',
        'server_name' => 'server1482',
        'location' => 'Europe (Lithuania)',
        'backup_location' => 'France',
        'ns1' => 'ns1.dns-parking.com',
        'ns2' => 'ns2.dns-parking.com',
        'ftp_host' => 'bilohash.com',
        'ftp_path' => 'public_html',
        'ftp_user_prefix' => 'u762384583',
    ];
}

/** @param array<string, mixed>|null $user */
function hs_server_constants(?array $user = null): array
{
    if ($user !== null && hs_is_demo_panel_user($user)) {
        return hs_demo_panel_constants();
    }
    return [
        'ip' => '45.84.204.61',
        'server_name' => 'server1482',
        'location' => 'Europe (Lithuania)',
        'backup_location' => 'France',
        'ns1' => 'ns1.dns-parking.com',
        'ns2' => 'ns2.dns-parking.com',
        'ftp_host' => hs_default_primary_domain(),
        'ftp_path' => 'public_html',
        'ftp_user_prefix' => 'u762384583',
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

function hs_ftp_username(string $domain, ?array $user = null): string
{
    $srv = hs_server_constants($user);
    if ($user !== null && hs_is_demo_panel_user($user)) {
        $domain = (string) $srv['ftp_host'];
    }
    $domain = trim($domain) !== '' ? trim($domain) : (string) $srv['ftp_host'];
    return $srv['ftp_user_prefix'] . '.' . $domain;
}

/** Hostinger-style upload root shown on plan details. */
function hs_ftp_plan_path(): string
{
    return (string) (hs_server_constants()['ftp_path'] ?? 'public_html');
}

/** Per-account FTP folder (demo: public_html only; others: public_html/username). */
function hs_ftp_account_path(string $username, ?array $user = null): string
{
    if ($user !== null && hs_is_demo_panel_user($user)) {
        return hs_ftp_plan_path();
    }
    return hs_ftp_plan_path() . '/' . preg_replace('/[^a-z0-9_-]/i', '', $username);
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