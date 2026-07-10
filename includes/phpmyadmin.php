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
    return hs_canonical_url(hs_panel_path('databases.php') . '?tab=phpmyadmin');
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

function hs_pma_start_signon_session(array $db): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    session_name(HS_PMA_SIGNON_SESSION);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => hs_cookie_path(),
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    session_regenerate_id(true);
    $_SESSION['PMA_single_signon_user'] = (string) ($db['user'] ?? '');
    $_SESSION['PMA_single_signon_password'] = (string) ($db['password'] ?? '');
    $_SESSION['PMA_single_signon_host'] = (string) ($db['host'] ?? 'localhost');
    $_SESSION['PMA_single_signon_auth_type'] = 'signon';
}

function hs_pma_render_open_form(string $dbId, array $t, string $label = ''): string
{
    $label = $label !== '' ? $label : ($t['db_pma_open'] ?? 'Open phpMyAdmin');
    return '<form method="post" action="' . hs_h(hs_url(hs_panel_path('pma-signon.php'))) . '" target="_blank" rel="noopener" style="display:inline">'
        . hs_csrf_field()
        . '<input type="hidden" name="db_id" value="' . hs_h($dbId) . '">'
        . '<button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-database"></i> ' . hs_h($label) . '</button></form>';
}