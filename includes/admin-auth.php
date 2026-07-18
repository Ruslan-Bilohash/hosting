<?php
declare(strict_types=1);

define('HS_ADMIN_SESSION', 'hs_admin_logged');
define('HS_ADMIN_USER_KEY', 'hs_admin_user');

/** Super-admin (platform operator) — separate from client "administrator" account */
function hs_admin_accounts(): array
{
    $file = HS_DATA_DIR . '/admin.config.php';
    if (is_readable($file)) {
        $cfg = require $file;
        if (is_array($cfg) && ($cfg['user'] ?? '') !== '') {
            return [[
                'user' => (string) $cfg['user'],
                'password_hash' => (string) ($cfg['password_hash'] ?? ''),
                'role' => (string) ($cfg['role'] ?? 'super'),
            ]];
        }
    }
    return [
        ['user' => 'admin', 'pass' => 'admin', 'role' => 'super'],
    ];
}

/** Reset super-admin password to admin/admin in demo mode (admin.config.php). */
function hs_sync_admin_config(): void
{
    if (!defined('HS_DEMO_MODE') || !HS_DEMO_MODE) {
        return;
    }
    if (!is_dir(HS_DATA_DIR) && !mkdir(HS_DATA_DIR, 0750, true)) {
        return;
    }
    $hash = password_hash('admin', PASSWORD_DEFAULT);
    $cfg = [
        'user' => 'admin',
        'password_hash' => $hash,
        'role' => 'super',
    ];
    $php = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($cfg, true) . ";\n";
    $file = HS_DATA_DIR . '/admin.config.php';
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        return;
    }
    @chmod($file, 0640);
}

/** Super-admin UI session or platform admin logged in via /login.php */
function hs_admin_or_platform_user(): ?array
{
    if (hs_admin_logged()) {
        return ['source' => 'super', 'user' => (string) ($_SESSION[HS_ADMIN_USER_KEY] ?? 'admin')];
    }
    require_once __DIR__ . '/client-auth.php';
    require_once __DIR__ . '/impersonation.php';
    $client = hs_client_user();
    if ($client !== null && hs_is_platform_admin($client)) {
        return ['source' => 'panel', 'user' => $client];
    }
    return null;
}

function hs_admin_or_platform_require(): void
{
    if (hs_admin_or_platform_user() !== null) {
        return;
    }
    header('Location: ' . hs_url('admin/login.php'), true, 302);
    exit;
}

/** Usernames that may open Clients / impersonation in the client panel */
function hs_platform_admin_usernames(): array
{
    $names = [];
    foreach (hs_admin_accounts() as $acc) {
        $u = (string) ($acc['user'] ?? '');
        if ($u !== '') {
            $names[] = $u;
        }
    }
    // Legacy production accounts (pre-v2.5 rename)
    if (!in_array('administrator', $names, true)) {
        $names[] = 'administrator';
    }
    return $names;
}

function hs_admin_logged(): bool
{
    hs_session_start();
    return !empty($_SESSION[HS_ADMIN_SESSION]);
}

function hs_admin_verify_credentials(string $user, string $pass): bool
{
    foreach (hs_admin_accounts() as $acc) {
        $ok = isset($acc['password_hash']) && $acc['password_hash'] !== ''
            ? password_verify($pass, (string) $acc['password_hash'])
            : ($user === ($acc['user'] ?? '') && $pass === ($acc['pass'] ?? ''));
        if ($user === ($acc['user'] ?? '') && $ok) {
            return true;
        }
    }

    return false;
}

function hs_admin_login(string $user, string $pass): bool
{
    if (!hs_rate_limit('admin_login', 5, 600)) {
        return false;
    }
    foreach (hs_admin_accounts() as $acc) {
        $ok = isset($acc['password_hash']) && $acc['password_hash'] !== ''
            ? password_verify($pass, (string) $acc['password_hash'])
            : ($user === ($acc['user'] ?? '') && $pass === ($acc['pass'] ?? ''));
        if ($user === ($acc['user'] ?? '') && $ok) {
            hs_session_start();
            session_regenerate_id(true);
            $_SESSION[HS_ADMIN_SESSION] = true;
            $_SESSION[HS_ADMIN_USER_KEY] = $user;
            $_SESSION['hs_admin_role'] = $acc['role'];
            return true;
        }
    }
    return false;
}

function hs_admin_logout(): void
{
    hs_session_start();
    unset($_SESSION[HS_ADMIN_SESSION], $_SESSION[HS_ADMIN_USER_KEY], $_SESSION['hs_admin_role']);
}

function hs_admin_require(): void
{
    if (!hs_admin_logged()) {
        header('Location: ' . hs_url('admin/login.php'), true, 302);
        exit;
    }
}

/**
 * Allow either super-admin session or a shared secret query/body token.
 * Used by cron URLs and admin tool one-shot diagnostics.
 *
 * @param list<string> $tokenConstants define() names to accept (first non-empty wins as expected value)
 */
function hs_admin_or_token_allow(array $tokenConstants = [], array $extraSecrets = []): bool
{
    if (hs_admin_logged()) {
        return true;
    }
    $provided = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
    if ($provided === '') {
        return false;
    }
    foreach ($tokenConstants as $name) {
        if (!is_string($name) || $name === '' || !defined($name)) {
            continue;
        }
        $expected = (string) constant($name);
        if ($expected !== '' && hash_equals($expected, $provided)) {
            return true;
        }
    }
    foreach ($extraSecrets as $secret) {
        $secret = (string) $secret;
        if ($secret !== '' && hash_equals($secret, $provided)) {
            return true;
        }
    }

    return false;
}

/** Exit 403 unless admin session or matching secret token. */
function hs_admin_or_token_require(array $tokenConstants = [], array $extraSecrets = []): void
{
    if (hs_admin_or_token_allow($tokenConstants, $extraSecrets)) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden — admin login or ?token= required\n";
    exit;
}

function hs_admin_url(string $path = '', array $qs = []): string
{
    return hs_url('admin/' . ltrim($path, '/'), $qs);
}