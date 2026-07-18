<?php
declare(strict_types=1);

const HS_PMA_SIGNON_SESSION = 'HostingPmaSignon';

function hs_pma_blowfish_secret(): string
{
    $file = HS_DATA_DIR . '/pma.config.php';
    if (is_readable($file)) {
        $cfg = require $file;
        $secret = (string) ($cfg['blowfish_secret'] ?? '');
        if (strlen($secret) >= 32) {
            return $secret;
        }
    }
    return hash('sha256', HS_CANONICAL_URL . '|hosting-pma-blowfish-v1');
}

function hs_pma_temp_dir(): string
{
    $dir = HS_DATA_DIR . '/pma-tmp';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir;
}

function hs_pma_index_url(): string
{
    return hs_canonical_url('pma/index.php');
}

function hs_pma_signon_url(): string
{
    return hs_canonical_url(hs_panel_path('pma-signon.php'));
}

function hs_pma_logout_url(): string
{
    return hs_canonical_url('pma-logout.php');
}

/** MySQL credentials for super-admin phpMyAdmin (provision user, else CMS db.config). */
function hs_pma_admin_credentials(): ?array
{
    require_once __DIR__ . '/mysql-provision.php';
    require_once __DIR__ . '/database.php';

    $cfg = hs_mysql_provision_config();
    if (is_array($cfg)) {
        $user = trim((string) ($cfg['user'] ?? ''));
        $pass = (string) ($cfg['pass'] ?? '');
        if ($user !== '' && $pass !== '') {
            return [
                'user' => $user,
                'password' => $pass,
                'host' => hs_mysql_provision_client_host(),
            ];
        }
    }

    $dbCfg = hs_db_config();
    if (is_array($dbCfg)) {
        $user = trim((string) ($dbCfg['user'] ?? $dbCfg['username'] ?? ''));
        $pass = (string) ($dbCfg['pass'] ?? $dbCfg['password'] ?? '');
        $host = trim((string) ($dbCfg['host'] ?? 'localhost'));
        if ($user !== '') {
            return [
                'user' => $user,
                'password' => $pass,
                'host' => $host !== '' ? $host : 'localhost',
            ];
        }
    }

    return null;
}

/** @return list<array<string,mixed>> */
function hs_pma_databases_for_user(string $userId): array
{
    $settings = hs_user_settings_get($userId);
    $dbs = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    return array_values(array_filter($dbs, static fn($db) => is_array($db) && !empty($db['provisioned'])));
}

/** @return array<string,mixed>|null */
function hs_pma_database_for_user(string $userId, string $dbId): ?array
{
    $dbId = trim($dbId);
    if ($dbId === '') {
        return null;
    }
    foreach (hs_pma_databases_for_user($userId) as $db) {
        if ((string) ($db['id'] ?? '') === $dbId) {
            return $db;
        }
    }
    return null;
}

function hs_pma_session_cookie_secure(): bool
{
    return (function_exists('hs_request_is_https') && hs_request_is_https())
        || (function_exists('hs_is_production_host') && hs_is_production_host());
}

/**
 * Gate pma/ so only clients with a valid panel sign-on cookie can open it.
 * IMPORTANT: always session_write_close() before returning — phpMyAdmin will
 * call session_start() for its own session and for SignonSession. Leaving the
 * PHP session active causes:
 *   "session_start(): Ignoring session_start() because a session is already active"
 */
function hs_pma_require_signon_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $cookieId = (string) ($_COOKIE[HS_PMA_SIGNON_SESSION] ?? '');
    // No sign-on cookie yet → send user back to panel (do not create an empty session).
    if ($cookieId === '' || !preg_match('/^[a-zA-Z0-9,-]{1,128}$/', $cookieId)) {
        header('Location: ' . hs_pma_signon_url(), true, 302);
        exit;
    }

    if (PHP_VERSION_ID >= 70400) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => hs_cookie_path(),
            'secure' => hs_pma_session_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_name(HS_PMA_SIGNON_SESSION);
    session_id($cookieId);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $granted = (int) ($_SESSION['PMA_signon_granted'] ?? 0);
    $hasGrant = $granted > 0 && $granted >= time() - 7200;
    $hasBootstrap = trim((string) ($_SESSION['PMA_single_signon_user'] ?? '')) !== ''
        && array_key_exists('PMA_single_signon_password', $_SESSION);

    // Always release before phpMyAdmin boots.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (!$hasGrant && !$hasBootstrap) {
        header('Location: ' . hs_pma_signon_url(), true, 302);
        exit;
    }
}

function hs_pma_start_signon_session(array $db, string $from = 'panel'): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (PHP_VERSION_ID >= 70400) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => hs_cookie_path(),
            'secure' => hs_pma_session_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_name(HS_PMA_SIGNON_SESSION);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);

    $_SESSION['PMA_single_signon_user'] = (string) ($db['user'] ?? '');
    $_SESSION['PMA_single_signon_password'] = (string) ($db['password'] ?? '');
    $_SESSION['PMA_single_signon_host'] = (string) ($db['host'] ?? 'localhost');
    $_SESSION['PMA_single_signon_auth_type'] = 'signon';
    $_SESSION['PMA_signon_from'] = $from === 'admin' ? 'admin' : 'panel';
    $_SESSION['PMA_signon_granted'] = time();

    // Flush cookie + session file before redirect to /pma/index.php
    session_write_close();
}

function hs_pma_render_open_form(string $dbId, array $t, string $label = ''): string
{
    $label = $label !== '' ? $label : ($t['db_pma_open'] ?? 'Open phpMyAdmin');
    return '<form method="post" action="' . hs_h(hs_url(hs_panel_path('pma-signon.php'))) . '" target="_blank" rel="noopener" style="display:inline">'
        . hs_csrf_field()
        . '<input type="hidden" name="db_id" value="' . hs_h($dbId) . '">'
        . '<button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-database"></i> ' . hs_h($label) . '</button></form>';
}