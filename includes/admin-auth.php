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

function hs_admin_url(string $path = '', array $qs = []): string
{
    return hs_url('admin/' . ltrim($path, '/'), $qs);
}