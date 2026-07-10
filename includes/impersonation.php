<?php
declare(strict_types=1);

require_once __DIR__ . '/admin-auth.php';

define('HS_IMPERSONATOR_KEY', 'hs_impersonator_admin');
define('HS_IMPERSONATOR_NAME_KEY', 'hs_impersonator_label');
define('HS_IMPERSONATOR_FROM_ADMIN', 'hs_impersonator_from_admin');

function hs_is_platform_admin(?array $user): bool
{
    if ($user === null) {
        return false;
    }
    $uname = (string) ($user['username'] ?? '');
    foreach (hs_admin_accounts() as $acc) {
        if ($uname === ($acc['user'] ?? '')) {
            return true;
        }
    }
    return false;
}

function hs_impersonation_active(): bool
{
    hs_session_start();
    return !empty($_SESSION[HS_IMPERSONATOR_KEY]);
}

function hs_impersonator_label(): string
{
    hs_session_start();
    return (string) ($_SESSION[HS_IMPERSONATOR_NAME_KEY] ?? 'Administrator');
}

function hs_impersonation_from_admin(): bool
{
    hs_session_start();
    return !empty($_SESSION[HS_IMPERSONATOR_FROM_ADMIN]);
}

function hs_start_impersonation(array $adminUser, string $targetUserId): bool
{
    if (!hs_is_platform_admin($adminUser)) {
        return false;
    }
    $target = hs_user_by_id($targetUserId);
    if ($target === null || empty($target['active'])) {
        return false;
    }
    hs_session_start();
    $_SESSION[HS_IMPERSONATOR_KEY] = (string) ($adminUser['id'] ?? '');
    $_SESSION[HS_IMPERSONATOR_NAME_KEY] = (string) ($adminUser['name'] ?? $adminUser['username'] ?? 'Admin');
    $_SESSION[HS_IMPERSONATOR_FROM_ADMIN] = false;
    $_SESSION[HS_CLIENT_SESSION] = $target['id'];
    $_SESSION[HS_CLIENT_USER] = $target['username'] ?? $target['email'];
    return true;
}

/** Start editing a client from /admin/ (platform operator session). */
function hs_start_impersonation_from_admin(string $targetUserId): bool
{
    if (!hs_admin_logged()) {
        return false;
    }
    $target = hs_user_by_id($targetUserId);
    if ($target === null || empty($target['active'])) {
        return false;
    }
    $anchor = hs_user_by_login('admin') ?? hs_user_by_login('administrator');
    hs_session_start();
    $_SESSION[HS_IMPERSONATOR_KEY] = (string) ($anchor['id'] ?? 'admin');
    $_SESSION[HS_IMPERSONATOR_NAME_KEY] = (string) ($_SESSION[HS_ADMIN_USER_KEY] ?? 'Administrator');
    $_SESSION[HS_IMPERSONATOR_FROM_ADMIN] = true;
    $_SESSION[HS_CLIENT_SESSION] = $target['id'];
    $_SESSION[HS_CLIENT_USER] = $target['username'] ?? $target['email'];
    return true;
}

function hs_stop_impersonation(): bool
{
    hs_session_start();
    $fromAdmin = !empty($_SESSION[HS_IMPERSONATOR_FROM_ADMIN]);
    $adminId = $_SESSION[HS_IMPERSONATOR_KEY] ?? null;
    unset($_SESSION[HS_IMPERSONATOR_KEY], $_SESSION[HS_IMPERSONATOR_NAME_KEY], $_SESSION[HS_IMPERSONATOR_FROM_ADMIN]);
    if ($fromAdmin) {
        unset($_SESSION[HS_CLIENT_SESSION], $_SESSION[HS_CLIENT_USER]);
        return true;
    }
    if (is_string($adminId) && $adminId !== '') {
        $admin = hs_user_by_id($adminId);
        if ($admin !== null) {
            $_SESSION[HS_CLIENT_SESSION] = $admin['id'];
            $_SESSION[HS_CLIENT_USER] = $admin['username'] ?? $admin['email'];
            return false;
        }
    }
    unset($_SESSION[HS_CLIENT_SESSION], $_SESSION[HS_CLIENT_USER]);
    return false;
}

function hs_client_display_name(array $user): string
{
    $name = trim((string) ($user['name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return (string) ($user['username'] ?? $user['email'] ?? 'User');
}