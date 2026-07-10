<?php
declare(strict_types=1);

function hs_ensure_dirs(): void
{
    foreach ([HS_DATA_DIR, hs_public_path()] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }
    $ht = HS_DATA_DIR . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Require all denied\nDeny from all\n");
    }
    $pubHt = hs_public_path('.htaccess');
    if (!is_file($pubHt)) {
        file_put_contents($pubHt, "Options -Indexes\n");
    }
}

function hs_data_file(string $name): string
{
    return HS_DATA_DIR . '/' . preg_replace('/[^a-z0-9_-]/i', '', $name) . '.json';
}

function hs_read_json(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function hs_write_json(string $file, array $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, $file);
}

function hs_users(): array
{
    if (hs_is_mysql_installed()) {
        return hs_db_load_collection('users');
    }
    return hs_read_json(hs_data_file('users'));
}

function hs_save_users(array $users): bool
{
    if (hs_is_mysql_installed()) {
        return hs_db_save_collection('users', $users, 'id');
    }
    return hs_write_json(hs_data_file('users'), $users);
}

function hs_sites(): array
{
    if (hs_is_mysql_installed()) {
        return hs_db_load_collection('sites');
    }
    return hs_read_json(hs_data_file('sites'));
}

function hs_save_sites(array $sites): bool
{
    if (hs_is_mysql_installed()) {
        return hs_db_save_collection('sites', $sites, 'id');
    }
    return hs_write_json(hs_data_file('sites'), $sites);
}

function hs_user_by_id(string $id): ?array
{
    foreach (hs_users() as $u) {
        if (($u['id'] ?? '') === $id) {
            return $u;
        }
    }
    return null;
}

/** @param callable(array<string,mixed>):void $mutator */
function hs_user_update(string $id, callable $mutator): bool
{
    $users = hs_users();
    $found = false;
    foreach ($users as &$u) {
        if (($u['id'] ?? '') === $id) {
            $mutator($u);
            $found = true;
            break;
        }
    }
    unset($u);
    return $found && hs_save_users($users);
}

function hs_user_by_login(string $login): ?array
{
    $login = strtolower(trim($login));
    foreach (hs_users() as $u) {
        if (strtolower((string) ($u['email'] ?? '')) === $login
            || strtolower((string) ($u['username'] ?? '')) === $login) {
            return $u;
        }
    }
    return null;
}

function hs_sites_for_user(string $userId): array
{
    return array_values(array_filter(hs_sites(), static fn($s) => ($s['user_id'] ?? '') === $userId));
}

function hs_site_owned_by_user(string $siteId, string $userId): bool
{
    if ($siteId === '' || $userId === '') {
        return false;
    }
    foreach (hs_sites() as $site) {
        if (($site['id'] ?? '') === $siteId) {
            return (string) ($site['user_id'] ?? '') === $userId;
        }
    }
    return false;
}

/** @return array<string, mixed>|null */
function hs_site_by_id_for_user(string $siteId, string $userId): ?array
{
    if ($siteId === '' || $userId === '') {
        return null;
    }
    foreach (hs_sites() as $site) {
        if (($site['id'] ?? '') === $siteId && (string) ($site['user_id'] ?? '') === $userId) {
            return $site;
        }
    }
    return null;
}

function hs_user_public_rel_prefix(array $user): string
{
    return preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
}

/** Whether a relative path under public_html/ belongs to this account */
function hs_user_owns_public_rel(array $user, string $rel): bool
{
    $rel = trim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || str_contains($rel, '..')) {
        return false;
    }
    $prefix = hs_user_public_rel_prefix($user);
    return $rel === $prefix || str_starts_with($rel, $prefix . '/');
}

/** @param array<string, mixed> $siteRecord */
function hs_site_add_for_user(string $userId, array $siteRecord): bool
{
    $siteRecord['user_id'] = $userId;
    $sites = hs_sites();
    $sites[] = $siteRecord;
    return hs_save_sites($sites);
}

/**
 * @param callable(array<string, mixed>): array<string, mixed> $updater
 */
function hs_site_update_for_user(string $siteId, string $userId, callable $updater): bool
{
    if (!hs_site_owned_by_user($siteId, $userId)) {
        return false;
    }
    $sites = hs_sites();
    $changed = false;
    foreach ($sites as &$site) {
        if (($site['id'] ?? '') === $siteId && (string) ($site['user_id'] ?? '') === $userId) {
            $site = $updater($site);
            $changed = true;
            break;
        }
    }
    unset($site);
    return $changed && hs_save_sites($sites);
}

function hs_site_delete_for_user(string $siteId, string $userId): bool
{
    if (!hs_site_owned_by_user($siteId, $userId)) {
        return false;
    }
    $sites = hs_sites();
    $before = count($sites);
    $sites = array_values(array_filter($sites, static fn(array $site): bool => ($site['id'] ?? '') !== $siteId));
    if (count($sites) === $before) {
        return false;
    }

    return hs_save_sites($sites);
}

function hs_new_id(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(8));
}

function hs_seed_demo_data(): void
{
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/plan-specs.php';
        hs_seed_demo_panel_settings();
        require_once __DIR__ . '/client-identity.php';
        hs_client_identity_migrate_all();
        return;
    }
    $existing = hs_users();
    if ($existing !== [] && defined('HS_DEMO_MODE') && HS_DEMO_MODE) {
        $changed = false;
        foreach ($existing as &$u) {
            $uname = (string) ($u['username'] ?? '');
            if ($uname === 'demo') {
                $u['password_hash'] = password_hash('demo', PASSWORD_DEFAULT);
                $u['subscription_status'] = 'active';
                $u['active'] = true;
                $changed = true;
            } elseif ($uname === 'admin' || $uname === 'administrator') {
                if ($uname === 'administrator') {
                    $u['username'] = 'admin';
                    $u['email'] = 'admin@' . hs_default_primary_domain();
                }
                $u['password_hash'] = password_hash('admin', PASSWORD_DEFAULT);
                $u['subscription_status'] = 'active';
                $u['active'] = true;
                $changed = true;
            }
        }
        unset($u);
        if ($changed) {
            hs_save_users($existing);
        }
        require_once __DIR__ . '/plan-specs.php';
        hs_seed_demo_panel_settings();
        require_once __DIR__ . '/client-identity.php';
        hs_client_identity_migrate_all();
        return;
    }
    if ($existing !== []) {
        require_once __DIR__ . '/client-identity.php';
        hs_client_identity_migrate_all();
        return;
    }
    $demoHash = password_hash('demo', PASSWORD_DEFAULT);
    $adminHash = password_hash('admin', PASSWORD_DEFAULT);
    $users = [
        [
            'id' => 'u_demo',
            'email' => 'demo@' . hs_default_primary_domain(),
            'username' => 'demo',
            'password_hash' => $demoHash,
            'name' => 'Demo Client',
            'plan' => 'business',
            'subscription_status' => 'active',
            'paid_until' => gmdate('c', strtotime('+1 year')),
            'created_at' => gmdate('c'),
            'active' => true,
        ],
        [
            'id' => 'u_admin',
            'email' => 'admin@' . hs_default_primary_domain(),
            'username' => 'admin',
            'password_hash' => $adminHash,
            'name' => 'Platform Admin',
            'plan' => 'business',
            'subscription_status' => 'active',
            'paid_until' => gmdate('c', strtotime('+1 year')),
            'created_at' => gmdate('c'),
            'active' => true,
        ],
    ];
    hs_save_users($users);
    require_once __DIR__ . '/client-identity.php';
    hs_client_identity_migrate_all();
    $sites = [
        [
            'id' => 's_demo_welcome',
            'user_id' => 'u_demo',
            'slug' => 'welcome',
            'install_base' => 'demo',
            'title' => 'Welcome Site',
            'domain' => '',
            'app' => 'empty',
            'status' => 'active',
            'created_at' => gmdate('c'),
        ],
        [
            'id' => 's_admin_welcome',
            'user_id' => 'u_admin',
            'slug' => 'welcome',
            'install_base' => 'admin',
            'title' => 'Welcome Site',
            'domain' => '',
            'app' => 'empty',
            'status' => 'active',
            'created_at' => gmdate('c'),
        ],
    ];
    hs_save_sites($sites);
    require_once __DIR__ . '/installer.php';
    hs_ensure_user_workspace($users[0]);
    hs_ensure_user_workspace($users[1]);
}