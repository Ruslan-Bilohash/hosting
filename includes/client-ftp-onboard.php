<?php
declare(strict_types=1);

/**
 * Per-client FTP accounts via cPanel UAPI (jailed to client site tree).
 * Login: {panel_username}@{brand_domain}  e.g. braserver@solaskinner.com
 * Homedir: public_html/public_html/{username}/… (disk under CMS multi-tenant tree)
 */

require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/master-password.php';
require_once __DIR__ . '/domain-workspace.php';

/** Brand domain used as FTP virtual-host suffix (solaskinner.com). */
function hs_client_ftp_server_domain(): string
{
    return strtolower(hs_default_primary_domain());
}

/** Local FTP user part (cPanel subaccount name, no @). */
function hs_client_ftp_local_user(array $user): string
{
    $u = strtolower(preg_replace('/[^a-z0-9]/', '', (string) ($user['username'] ?? '')) ?? '');
    if ($u === '' || $u === 'root' || $u === 'admin' || $u === 'ftp') {
        $u = 'u' . substr(md5((string) ($user['id'] ?? 'x')), 0, 8);
    }
    // cPanel local part max ~16–18 practical
    return substr($u, 0, 16);
}

/** Full FTP login shown to client. */
function hs_client_ftp_login(array $user): string
{
    return hs_client_ftp_local_user($user) . '@' . hs_client_ftp_server_domain();
}

/**
 * Relative path from cPanel home for FTP jail.
 * Prefer active domain folder; else whole client tree.
 */
function hs_client_ftp_homedir_rel(array $user, ?array $settings = null): string
{
    $userId = (string) ($user['id'] ?? '');
    if ($settings === null && $userId !== '') {
        $settings = hs_user_settings_get($userId);
    }
    $settings = is_array($settings) ? $settings : [];
    $username = (string) ($user['username'] ?? 'user');

    // Absolute disk path → strip /home/{cpanel}/
    $home = rtrim((string) (getenv('HOME') ?: ('/home/' . (defined('HS_SSH_USER') ? HS_SSH_USER : 'user'))), '/');
    $domain = function_exists('hs_active_domain') ? hs_active_domain($settings) : '';
    $domain = strtolower(trim((string) $domain));
    if ($domain !== '' && str_contains($domain, '.') && function_exists('hs_domain_docroot_rel')) {
        $rel = hs_domain_docroot_rel($user, $domain, $settings);
        $abs = hs_public_path($rel);
        if (is_dir($abs) || @mkdir($abs, 0755, true)) {
            if (str_starts_with($abs, $home . '/')) {
                return substr($abs, strlen($home) + 1);
            }
        }
    }
    // Whole client account tree under multi-tenant public_html/
    $userSafe = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';
    $tree = hs_public_path($userSafe);
    if (!is_dir($tree)) {
        @mkdir($tree, 0755, true);
    }
    if (str_starts_with($tree, $home . '/')) {
        return substr($tree, strlen($home) + 1);
    }

    return 'public_html/public_html/' . $userSafe;
}

/** @return array{ok:bool,out:string,code:int} */
function hs_cpanel_uapi(string $module, string $func, array $args = []): array
{
    $parts = ['uapi', '--output=json', $module, $func];
    foreach ($args as $k => $v) {
        $parts[] = $k . '=' . escapeshellarg((string) $v);
    }
    $cmd = implode(' ', $parts) . ' 2>&1';
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    $raw = implode("\n", $out);
    $json = json_decode($raw, true);
    $ok = is_array($json) && (int) ($json['result']['status'] ?? 0) === 1;

    return ['ok' => $ok, 'out' => $raw, 'code' => $code, 'json' => $json];
}

/** @return list<array<string,mixed>> */
function hs_cpanel_ftp_list(): array
{
    $r = hs_cpanel_uapi('Ftp', 'list_ftp');
    $data = $r['json']['result']['data'] ?? null;

    return is_array($data) ? $data : [];
}

function hs_cpanel_ftp_exists(string $localUser): bool
{
    $localUser = strtolower($localUser);
    $suffix = '@' . hs_client_ftp_server_domain();
    foreach (hs_cpanel_ftp_list() as $row) {
        $u = strtolower((string) ($row['user'] ?? ''));
        if ($u === $localUser || $u === $localUser . $suffix || str_starts_with($u, $localUser . '@')) {
            return true;
        }
    }

    return false;
}

/**
 * Ensure FTP account exists, password synced, homedir jailed to client folder.
 *
 * @return array{ok:bool,login?:string,homedir?:string,host?:string,port?:int,error?:string,created?:bool}
 */
function hs_client_ftp_ensure(array $user, bool $syncPassword = true): array
{
    $userId = (string) ($user['id'] ?? '');
    $local = hs_client_ftp_local_user($user);
    $login = hs_client_ftp_login($user);
    $settings = $userId !== '' ? hs_user_settings_get($userId) : [];
    $homedir = hs_client_ftp_homedir_rel($user, $settings);
    $home = rtrim((string) (getenv('HOME') ?: ''), '/');
    $abs = ($home !== '' ? $home . '/' : '') . $homedir;
    if ($abs !== '' && !is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }

    $pass = $userId !== '' ? hs_master_password_plain($userId) : '';
    if ($pass === '' || strlen($pass) < 8) {
        $pass = function_exists('hs_generate_secure_password')
            ? hs_generate_secure_password(14)
            : ('Hs' . bin2hex(random_bytes(6)) . 'A1!');
        if ($userId !== '') {
            hs_master_password_sync($userId, $pass);
        }
    }

    $created = false;
    if (!hs_cpanel_ftp_exists($local)) {
        $add = hs_cpanel_uapi('Ftp', 'add_ftp', [
            'user' => $local,
            'pass' => $pass,
            'quota' => '0',
            'homedir' => $homedir,
        ]);
        if (!$add['ok']) {
            return [
                'ok' => false,
                'error' => 'add_ftp:' . substr($add['out'], 0, 200),
            ];
        }
        $created = true;
    } else {
        // Update jail path
        hs_cpanel_uapi('Ftp', 'set_homedir', [
            'user' => $local,
            'homedir' => $homedir,
        ]);
        if ($syncPassword) {
            $pw = hs_cpanel_uapi('Ftp', 'passwd', [
                'user' => $local,
                'pass' => $pass,
            ]);
            if (!$pw['ok']) {
                // non-fatal if passwd API shape differs
            }
        }
    }

    $host = 'ftp.' . hs_client_ftp_server_domain();
    $ip = function_exists('hs_server_ip') ? hs_server_ip() : '';
    $entry = [
        'provisioned' => true,
        'login' => $login,
        'user' => $local,
        'homedir' => $homedir,
        'host' => $host,
        'host_ip' => $ip,
        'port' => 21,
        'sftp_port' => defined('HS_SSH_PORT') ? (int) HS_SSH_PORT : 22,
        'updated_at' => gmdate('c'),
        'created' => $created,
    ];
    if ($userId !== '') {
        hs_user_settings_save($userId, ['ftp_account' => $entry]);
    }

    return ['ok' => true] + $entry;
}

/** Alias used by payment-fulfill. */
function hs_client_provision_ftp(array $user): array
{
    return hs_client_ftp_ensure($user, true);
}

/**
 * Credentials for UI — ensures account first.
 *
 * @return array{ok:bool,login:string,password:string,host:string,host_ip:string,port:int,sftp_port:int,homedir:string,path_display:string,error?:string}
 */
function hs_client_ftp_credentials(array $user): array
{
    $ens = hs_client_ftp_ensure($user, true);
    if (empty($ens['ok'])) {
        return [
            'ok' => false,
            'login' => hs_client_ftp_login($user),
            'password' => '',
            'host' => 'ftp.' . hs_client_ftp_server_domain(),
            'host_ip' => function_exists('hs_server_ip') ? hs_server_ip() : '',
            'port' => 21,
            'sftp_port' => defined('HS_SSH_PORT') ? (int) HS_SSH_PORT : 22,
            'homedir' => '',
            'path_display' => '',
            'error' => (string) ($ens['error'] ?? 'ftp'),
        ];
    }
    $userId = (string) ($user['id'] ?? '');
    $pass = $userId !== '' ? hs_master_password_plain($userId) : '';

    return [
        'ok' => true,
        'login' => (string) ($ens['login'] ?? hs_client_ftp_login($user)),
        'password' => $pass,
        'host' => (string) ($ens['host'] ?? ('ftp.' . hs_client_ftp_server_domain())),
        'host_ip' => (string) ($ens['host_ip'] ?? ''),
        'port' => (int) ($ens['port'] ?? 21),
        'sftp_port' => (int) ($ens['sftp_port'] ?? 22),
        'homedir' => (string) ($ens['homedir'] ?? ''),
        // After login client is already jailed — path is /
        'path_display' => '/',
    ];
}
