<?php
declare(strict_types=1);

/**
 * Per-client cPanel accounts on Namecheap Reseller Nebula (WHM).
 * Pool: Namecheap Nebula — 25 accounts · 30 GB SSD (quotas follow sold plan: 1/2/3 GB).
 */

// WHM API optional — missing/broken file must not white-screen admin
if (is_file(__DIR__ . '/whm-api.php')) {
    require_once __DIR__ . '/whm-api.php';
}
require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/master-password.php';
require_once __DIR__ . '/storage.php';

if (!function_exists('hs_whm_enabled')) {
    function hs_whm_enabled(): bool
    {
        return false;
    }
}
if (!function_exists('hs_whm_credentials_ready')) {
    function hs_whm_credentials_ready(?array $cfg = null): bool
    {
        return false;
    }
}
if (!function_exists('hs_whm_config')) {
    /** @return array<string, mixed> */
    function hs_whm_config(bool $reload = false): array
    {
        return ['enabled' => false, 'auto_provision' => false];
    }
}
if (!function_exists('hs_whm_config_save')) {
    /** @param array<string, mixed> $patch */
    function hs_whm_config_save(array $patch): array
    {
        return ['ok' => false, 'error' => 'whm_api_missing'];
    }
}
if (!function_exists('hs_whm_test_connection')) {
    function hs_whm_test_connection(bool $force = true): array
    {
        return ['ok' => false, 'error' => 'whm_api_missing'];
    }
}
if (!function_exists('hs_whm_ensure_package')) {
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
        return ['ok' => false, 'error' => 'whm_api_missing'];
    }
}
if (!function_exists('hs_whm_createacct')) {
    function hs_whm_createacct(
        string $username,
        string $domain,
        string $password,
        string $package,
        string $email,
        int $diskGb
    ): array {
        return ['ok' => false, 'error' => 'whm_api_missing'];
    }
}
if (!function_exists('hs_whm_cpanel_sso_url')) {
    function hs_whm_cpanel_sso_url(string $cpanelUser): array
    {
        return ['ok' => false, 'error' => 'whm_api_missing'];
    }
}

/** @return array{max_accounts:int,max_disk_gb:int,reserved_disk_gb:int,warn_accounts_pct:int,warn_disk_pct:int,auto_provision:bool} */
function hs_cpanel_pool_limits(): array
{
    $cfg = hs_whm_config();

    return [
        'max_accounts' => max(1, (int) ($cfg['max_accounts'] ?? 25)),
        'max_disk_gb' => max(1, (int) ($cfg['max_disk_gb'] ?? 30)),
        'reserved_disk_gb' => max(0, (int) ($cfg['reserved_disk_gb'] ?? 0)),
        'warn_accounts_pct' => max(1, min(100, (int) ($cfg['warn_accounts_pct'] ?? 80))),
        'warn_disk_pct' => max(1, min(100, (int) ($cfg['warn_disk_pct'] ?? 80))),
        'auto_provision' => !array_key_exists('auto_provision', $cfg) || !empty($cfg['auto_provision']),
    ];
}

function hs_cpanel_auto_provision(): bool
{
    return !empty(hs_cpanel_pool_limits()['auto_provision']);
}

/** @return list<string> plan ids that appear in packages/disk maps or hosting catalog */
function hs_cpanel_pool_plan_ids(): array
{
    $cfg = hs_whm_config();
    $ids = [];
    foreach (['packages', 'disk_gb'] as $key) {
        if (!is_array($cfg[$key] ?? null)) {
            continue;
        }
        foreach (array_keys($cfg[$key]) as $id) {
            $id = (string) $id;
            if ($id !== '') {
                $ids[$id] = true;
            }
        }
    }
    if (function_exists('hs_plans')) {
        foreach (hs_plans() as $id => $plan) {
            if (!is_array($plan)) {
                continue;
            }
            if (function_exists('hs_plan_is_hosting') && !hs_plan_is_hosting((string) $id)) {
                continue;
            }
            $ids[(string) $id] = true;
        }
    }
    foreach (['test5', 'starter', 'plus', 'business'] as $fallback) {
        $ids[$fallback] = true;
    }

    return array_keys($ids);
}

/**
 * Package resource defaults for ensure_package / createacct.
 *
 * @return array{max_parked:int,max_addon:int,max_sql:int,max_pop:int,max_ftp:int,max_sub:int,hasshell:bool}
 */
function hs_cpanel_package_limits_for_plan(string $planId): array
{
    $planId = hs_plan_normalize_id($planId);
    $cfg = hs_whm_config();
    $base = is_array($cfg['package_limits'] ?? null) ? $cfg['package_limits'] : [];
    $out = [
        'max_parked' => max(0, (int) ($base['max_parked'] ?? 0)),
        'max_addon' => max(0, (int) ($base['max_addon'] ?? 2)),
        'max_sql' => max(0, (int) ($base['max_sql'] ?? 5)),
        'max_pop' => max(0, (int) ($base['max_pop'] ?? 10)),
        'max_ftp' => max(0, (int) ($base['max_ftp'] ?? 5)),
        'max_sub' => max(0, (int) ($base['max_sub'] ?? 10)),
        'hasshell' => !empty($base['hasshell']),
    ];
    // Plan-scaled defaults when admin left base at zero-ish starter values
    if ($planId === 'plus') {
        $out['max_addon'] = max($out['max_addon'], 5);
        $out['max_sql'] = max($out['max_sql'], 15);
        $out['max_pop'] = max($out['max_pop'], 25);
    } elseif ($planId === 'business') {
        $out['max_addon'] = max($out['max_addon'], 10);
        $out['max_sql'] = max($out['max_sql'], 25);
        $out['max_pop'] = max($out['max_pop'], 50);
        $out['max_parked'] = max($out['max_parked'], 2);
    }

    return $out;
}

/** Disk GB for a Solaskinner plan (catalog + WHM config override). */
function hs_cpanel_plan_disk_gb(string $planId): int
{
    $planId = hs_plan_normalize_id($planId);
    $cfg = hs_whm_config();
    $map = is_array($cfg['disk_gb'] ?? null) ? $cfg['disk_gb'] : [];
    if (isset($map[$planId]) && (int) $map[$planId] > 0) {
        return (int) $map[$planId];
    }
    $plan = hs_plan($planId);
    $gb = (int) ($plan['disk_gb'] ?? 0);
    if ($gb > 0) {
        return $gb;
    }
    $mb = (int) ($plan['storage_mb'] ?? 0);

    return $mb > 0 ? max(1, (int) ceil($mb / 1024)) : 5;
}

function hs_cpanel_plan_package(string $planId): string
{
    $planId = hs_plan_normalize_id($planId);
    $cfg = hs_whm_config();
    $map = is_array($cfg['packages'] ?? null) ? $cfg['packages'] : [];
    if (!empty($map[$planId])) {
        return (string) $map[$planId];
    }

    return 'sola_' . preg_replace('/[^a-z0-9_]/', '', $planId);
}

/** @return array<string, mixed>|null */
function hs_cpanel_account_for_user(string $userId): ?array
{
    if ($userId === '') {
        return null;
    }
    $s = hs_user_settings_get($userId);
    $acc = $s['cpanel_account'] ?? null;

    return is_array($acc) ? $acc : null;
}

/**
 * Sum disk_gb of all provisioned cPanel accounts in CMS + count.
 *
 * @return array{accounts:int,disk_gb:int,rows:list<array<string,mixed>>}
 */
function hs_cpanel_pool_usage(): array
{
    $rows = [];
    $disk = 0;
    $n = 0;
    foreach (hs_users() as $u) {
        if (!is_array($u)) {
            continue;
        }
        $id = (string) ($u['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $acc = hs_cpanel_account_for_user($id);
        if ($acc === null || empty($acc['provisioned'])) {
            continue;
        }
        $gb = (int) ($acc['disk_gb'] ?? hs_cpanel_plan_disk_gb((string) ($u['plan'] ?? 'starter')));
        $disk += max(0, $gb);
        $n++;
        $rows[] = [
            'user_id' => $id,
            'username' => (string) ($u['username'] ?? ''),
            'plan' => (string) ($u['plan'] ?? ''),
            'cpanel_user' => (string) ($acc['user'] ?? ''),
            'domain' => (string) ($acc['domain'] ?? ''),
            'disk_gb' => $gb,
            'created_at' => (string) ($acc['created_at'] ?? ''),
        ];
    }

    return ['accounts' => $n, 'disk_gb' => $disk, 'rows' => $rows];
}

/**
 * @return array{ok:bool,error?:string,remaining_accounts?:int,remaining_disk_gb?:int}
 */
function hs_cpanel_pool_can_allocate(int $diskGb, ?string $excludeUserId = null): array
{
    $limits = hs_cpanel_pool_limits();
    $usage = hs_cpanel_pool_usage();
    $accounts = $usage['accounts'];
    $disk = $usage['disk_gb'];
    if ($excludeUserId !== null) {
        $acc = hs_cpanel_account_for_user($excludeUserId);
        if ($acc !== null && !empty($acc['provisioned'])) {
            $accounts = max(0, $accounts - 1);
            $disk = max(0, $disk - (int) ($acc['disk_gb'] ?? 0));
        }
    }
    $usableDisk = max(0, $limits['max_disk_gb'] - (int) ($limits['reserved_disk_gb'] ?? 0));
    if ($accounts >= $limits['max_accounts']) {
        return ['ok' => false, 'error' => 'pool_accounts_full', 'remaining_accounts' => 0, 'remaining_disk_gb' => max(0, $usableDisk - $disk)];
    }
    if ($disk + $diskGb > $usableDisk) {
        return [
            'ok' => false,
            'error' => 'pool_disk_full',
            'remaining_accounts' => $limits['max_accounts'] - $accounts,
            'remaining_disk_gb' => max(0, $usableDisk - $disk),
        ];
    }

    return [
        'ok' => true,
        'remaining_accounts' => $limits['max_accounts'] - $accounts - 1,
        'remaining_disk_gb' => $usableDisk - $disk - $diskGb,
    ];
}

function hs_cpanel_make_username(array $user): string
{
    $cfg = hs_whm_config();
    $prefix = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) ($cfg['username_prefix'] ?? '')))) ?? '';
    $base = strtolower(preg_replace('/[^a-z0-9]/', '', (string) ($user['username'] ?? 'user')) ?? 'user');
    if ($base === '') {
        $base = 'user';
    }
    if ($prefix !== '') {
        $base = $prefix . $base;
    }
    // cPanel max 16 chars; leave room for uniqueness suffix if needed
    $base = substr($base, 0, 12);
    // Avoid reserved / main reseller collisions
    if (in_array($base, ['root', 'admin', 'cpanel', 'whm'], true)) {
        $base = 'c' . $base;
    }

    return $base;
}

function hs_cpanel_primary_domain(array $user): string
{
    $userId = (string) ($user['id'] ?? '');
    $settings = $userId !== '' ? hs_user_settings_get($userId) : [];
    $domain = trim((string) ($settings['primary_domain'] ?? $settings['active_domain'] ?? ''));
    if ($domain !== '' && str_contains($domain, '.')) {
        return strtolower($domain);
    }
    $pending = hs_user_pending_domains($user);
    if ($pending !== [] && str_contains($pending[0], '.')) {
        return strtolower($pending[0]);
    }
    // Temporary domain until client attaches a real one
    $slug = hs_cpanel_make_username($user);
    $cfg = hs_whm_config();
    $suffix = trim((string) ($cfg['client_domain_suffix'] ?? ''), '.');
    if ($suffix === '') {
        $brand = function_exists('hs_host_profile_value')
            ? (string) (hs_host_profile_value('primary_domain') ?? 'solaskinner.com')
            : 'solaskinner.com';
        $suffix = 'clients.' . $brand;
    }

    return strtolower($slug . '.' . $suffix);
}

/**
 * Create (or ensure) a dedicated cPanel for this client after hosting payment.
 *
 * @return array{ok:bool,skipped?:bool,error?:string,entry?:array<string,mixed>}
 */
function hs_cpanel_provision_for_user(array $user): array
{
    if (!hs_whm_enabled()) {
        return ['ok' => true, 'skipped' => true, 'error' => 'whm_disabled'];
    }
    // Manual provision from admin always allowed; payment hook checks auto_provision separately
    if (($user['subscription_status'] ?? '') !== 'active') {
        return ['ok' => false, 'error' => 'inactive'];
    }
    if (function_exists('hs_is_demo_panel_user') && hs_is_demo_panel_user($user)) {
        return ['ok' => true, 'skipped' => true, 'error' => 'demo'];
    }
    if (!hs_plan_is_hosting((string) ($user['plan'] ?? 'starter'))) {
        return ['ok' => true, 'skipped' => true, 'error' => 'not_hosting'];
    }

    $userId = (string) ($user['id'] ?? '');
    $planId = hs_plan_normalize_id((string) ($user['plan'] ?? 'starter'));
    $existing = hs_cpanel_account_for_user($userId);
    if ($existing !== null && !empty($existing['provisioned'])) {
        return ['ok' => true, 'skipped' => true, 'entry' => $existing];
    }

    $diskGb = hs_cpanel_plan_disk_gb($planId);
    $pool = hs_cpanel_pool_can_allocate($diskGb, $userId);
    if (empty($pool['ok'])) {
        return ['ok' => false, 'error' => (string) ($pool['error'] ?? 'pool_full')];
    }

    $package = hs_cpanel_plan_package($planId);
    $plim = hs_cpanel_package_limits_for_plan($planId);
    // Best-effort: create package if missing (idempotent)
    hs_whm_ensure_package(
        $package,
        $diskGb,
        (int) $plim['max_parked'],
        (int) $plim['max_addon'],
        (int) $plim['max_sql'],
        (int) $plim['max_pop'],
        (int) $plim['max_ftp'],
        (int) $plim['max_sub'],
        (bool) $plim['hasshell']
    );

    $cpUser = hs_cpanel_make_username($user);
    // Ensure unique among provisioned
    $suffix = 0;
    $tryUser = $cpUser;
    $usageRows = hs_cpanel_pool_usage()['rows'];
    $taken = [];
    foreach ($usageRows as $row) {
        $taken[strtolower((string) ($row['cpanel_user'] ?? ''))] = true;
    }
    while (isset($taken[$tryUser]) && $suffix < 99) {
        $suffix++;
        $tryUser = substr($cpUser, 0, 14) . $suffix;
    }
    $cpUser = $tryUser;

    $domain = hs_cpanel_primary_domain($user);
    $pass = hs_master_password_plain($userId);
    if ($pass === '' || strlen($pass) < 8) {
        $pass = hs_generate_secure_password(14);
        hs_master_password_sync($userId, $pass);
    }

    $email = (string) ($user['email'] ?? '');
    $create = hs_whm_createacct($cpUser, $domain, $pass, $package, $email, $diskGb);
    if (!$create['ok']) {
        hs_user_settings_save($userId, [
            'cpanel_provision_failed' => true,
            'cpanel_provision_error' => $create['error'],
        ]);

        return ['ok' => false, 'error' => $create['error']];
    }

    $entry = [
        'provisioned' => true,
        'user' => (string) ($create['username'] ?? $cpUser),
        'domain' => (string) ($create['domain'] ?? $domain),
        'package' => $package,
        'disk_gb' => $diskGb,
        'plan_id' => $planId,
        'password_synced' => true,
        'created_at' => gmdate('c'),
        'login_url' => 'https://' . (hs_whm_config()['host'] ?? 'localhost') . ':'
            . (int) (hs_whm_config()['cpanel_port'] ?? 2083),
    ];
    hs_user_settings_save($userId, [
        'cpanel_account' => $entry,
        'cpanel_provision_failed' => false,
        'cpanel_provision_error' => '',
    ]);

    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'cpanel_provision', $entry['user'] . '@' . $entry['domain']);
    }

    return ['ok' => true, 'entry' => $entry];
}

/**
 * @return array{ok:bool,url?:string,error?:string}
 */
function hs_cpanel_sso_for_user(array $user): array
{
    $userId = (string) ($user['id'] ?? '');
    $acc = hs_cpanel_account_for_user($userId);
    if ($acc === null || empty($acc['provisioned']) || empty($acc['user'])) {
        return ['ok' => false, 'error' => 'no_account'];
    }

    return hs_whm_cpanel_sso_url((string) $acc['user']);
}
