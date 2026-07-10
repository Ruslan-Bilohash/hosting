<?php
declare(strict_types=1);

require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/plan-specs.php';

/** Plain master password used for panel login, FTP and SSH (stored in user settings). */
function hs_master_password_plain(string $userId): string
{
    if (hs_ssh_password_available()) {
        return HS_SSH_PASSWORD;
    }
    $s = hs_user_settings_get($userId);
    $pass = (string) ($s['ftp_password_token'] ?? '');
    if ($pass !== '') {
        return $pass;
    }
    return (string) ($s['ssh_password_token'] ?? '');
}

function hs_master_password_sync(string $userId, string $plain): bool
{
    return hs_user_settings_save($userId, [
        'ftp_password_token' => $plain,
        'ssh_password_token' => $plain,
    ]);
}

/** @return array{ok:bool,error?:string} */
function hs_master_password_change(array $user, string $current, string $new, string $confirm): array
{
    $userId = (string) ($user['id'] ?? '');
    $minLen = (defined('HS_DEMO_MODE') && HS_DEMO_MODE) ? 4 : 8;

    if ($new !== $confirm) {
        return ['ok' => false, 'error' => 'mismatch'];
    }
    if (strlen($new) < $minLen) {
        return ['ok' => false, 'error' => 'weak'];
    }
    if (!password_verify($current, (string) ($user['password_hash'] ?? ''))) {
        return ['ok' => false, 'error' => 'wrong_current'];
    }

    $users = hs_users();
    $saved = false;
    foreach ($users as &$u) {
        if ((string) ($u['id'] ?? '') !== $userId) {
            continue;
        }
        $u['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        $saved = true;
        break;
    }
    unset($u);
    if (!$saved || !hs_save_users($users)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }
    if (!hs_master_password_sync($userId, $new)) {
        return ['ok' => false, 'error' => 'settings_failed'];
    }
    if (function_exists('hs_panel_log')) {
        hs_panel_log($userId, 'master_password_changed');
    }

    return ['ok' => true];
}

/** @return array{ok:bool,password?:string,error?:string} */
function hs_master_password_generate(array $user, string $current): array
{
    $userId = (string) ($user['id'] ?? '');
    if (!password_verify($current, (string) ($user['password_hash'] ?? ''))) {
        return ['ok' => false, 'error' => 'wrong_current'];
    }
    $pass = hs_generate_secure_password();
    $users = hs_users();
    $saved = false;
    foreach ($users as &$u) {
        if ((string) ($u['id'] ?? '') !== $userId) {
            continue;
        }
        $u['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        $saved = true;
        break;
    }
    unset($u);
    if (!$saved || !hs_save_users($users)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }
    if (!hs_master_password_sync($userId, $pass)) {
        return ['ok' => false, 'error' => 'settings_failed'];
    }
    if (function_exists('hs_panel_log')) {
        hs_panel_log($userId, 'master_password_generated');
    }

    return ['ok' => true, 'password' => $pass];
}

/** @return list<array{id:string,icon:string,title:string,desc:string}> */
function hs_master_password_services(array $t): array
{
    return [
        [
            'id' => 'panel',
            'icon' => 'fa-door-open',
            'title' => $t['account_service_panel'] ?? 'Control panel login',
            'desc' => $t['account_service_panel_desc'] ?? 'Sign in at hosting/login.php',
        ],
        [
            'id' => 'ftp',
            'icon' => 'fa-server',
            'title' => $t['account_service_ftp'] ?? 'FTP / SFTP',
            'desc' => $t['account_service_ftp_desc'] ?? 'FileZilla, WinSCP, Cyberduck',
        ],
        [
            'id' => 'ssh',
            'icon' => 'fa-terminal',
            'title' => $t['account_service_ssh'] ?? 'SSH access',
            'desc' => $t['account_service_ssh_desc'] ?? 'Terminal, PuTTY, deployment scripts',
        ],
    ];
}

function hs_master_password_error_message(string $code, array $t): string
{
    $map = [
        'wrong_current' => $t['account_master_pass_error_wrong'] ?? 'Current password is incorrect.',
        'weak' => $t['account_master_pass_error_weak'] ?? 'Password is too short.',
        'mismatch' => $t['account_master_pass_error_mismatch'] ?? 'New passwords do not match.',
        'save_failed' => $t['account_master_pass_error_save'] ?? 'Could not save password.',
        'settings_failed' => $t['account_master_pass_error_save'] ?? 'Could not save password.',
    ];

    return $map[$code] ?? ($t['account_master_pass_error_save'] ?? 'Could not update password.');
}