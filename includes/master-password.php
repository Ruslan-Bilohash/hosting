<?php
declare(strict_types=1);

require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/security.php';

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
    $ok = hs_user_settings_save($userId, [
        'ftp_password_token' => $plain,
        'ssh_password_token' => $plain,
    ]);
    // Push to real cPanel FTP account (jailed per client) when available
    if ($ok && $plain !== '' && is_file(__DIR__ . '/client-ftp-onboard.php')) {
        require_once __DIR__ . '/client-ftp-onboard.php';
        $user = function_exists('hs_user_by_id') ? hs_user_by_id($userId) : null;
        if (is_array($user) && function_exists('hs_client_ftp_ensure')) {
            hs_client_ftp_ensure($user, true);
        }
    }

    return $ok;
}

function hs_master_password_set_flash(string $userId, string $plain): void
{
    hs_session_start();
    $_SESSION['hs_pass_flash'][$userId] = [
        'pass' => $plain,
        't' => time(),
    ];
}

function hs_master_password_consume_flash(string $userId): ?string
{
    hs_session_start();
    $flash = $_SESSION['hs_pass_flash'][$userId] ?? null;
    unset($_SESSION['hs_pass_flash'][$userId]);
    if (!is_array($flash)) {
        return null;
    }
    if (time() - (int) ($flash['t'] ?? 0) > 120) {
        return null;
    }
    $pass = (string) ($flash['pass'] ?? '');
    return $pass !== '' ? $pass : null;
}

function hs_master_password_has_stored(string $userId): bool
{
    return hs_master_password_plain($userId) !== '';
}

/** @return array{ok:bool,password?:string,error?:string} */
function hs_master_password_reveal(array $user, string $current): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'save_failed'];
    }
    if (!hs_rate_limit('master_pass_reveal_' . $userId, 8, 300)) {
        return ['ok' => false, 'error' => 'rate_limit'];
    }
    if (!password_verify($current, (string) ($user['password_hash'] ?? ''))) {
        return ['ok' => false, 'error' => 'wrong_current'];
    }
    $pass = hs_master_password_plain($userId);
    if ($pass === '') {
        return ['ok' => false, 'error' => 'empty'];
    }
    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'master_password_reveal');
    }

    return ['ok' => true, 'password' => $pass];
}

/**
 * Masked secret UI — plaintext never embedded in HTML unless $flashPlain is set (one-time after change).
 *
 * @param array{toggle?:string,hidden?:string,copy?:string,class?:string} $opts
 */
function hs_master_password_secret_ui(string $elementId, array $t, ?string $flashPlain = null, array $opts = []): string
{
    $toggleAttr = (string) ($opts['toggle'] ?? 'data-secret-reveal');
    $hiddenAttr = (string) ($opts['hidden'] ?? 'data-secret-hidden');
    $copyTarget = (string) ($opts['copy'] ?? $elementId);
    $rowClass = (string) ($opts['class'] ?? 'hs-secret-pass-row');
    $revealUrl = hs_url(hs_panel_path('account-reveal-pass-api.php'));
    $showLabel = $t['account_master_pass_show'] ?? 'Show';
    $hideLabel = $t['account_master_pass_hide'] ?? 'Hide';
    $copiedLabel = $t['account_master_pass_copied'] ?? 'Copied';
    $prompt = $t['account_master_pass_reveal_prompt'] ?? 'Enter your current password to reveal';
    $flashHint = $t['account_master_pass_flash_hint'] ?? 'Copy this password now — it will be hidden on the next page load.';

    if ($flashPlain !== null && $flashPlain !== '') {
        return '<div class="' . hs_h($rowClass) . ' hs-secret-flash">'
            . '<p class="hs-alert hs-alert-success hs-secret-flash-hint"><i class="fa-solid fa-circle-info"></i> '
            . hs_h($flashHint) . '</p>'
            . '<code id="' . hs_h($elementId) . '" class="hs-secret-visible">' . hs_h($flashPlain) . '</code>'
            . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" ' . $toggleAttr
            . ' data-target="' . hs_h($elementId) . '" data-flash="1"'
            . ' data-label-show="' . hs_h($showLabel) . '" data-label-hide="' . hs_h($hideLabel) . '">'
            . hs_h($hideLabel) . '</button>'
            . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-secret="' . hs_h($copyTarget) . '"'
            . ' data-copied-label="' . hs_h($copiedLabel) . '"><i class="fa-solid fa-copy"></i></button>'
            . '</div>';
    }

    return '<div class="' . hs_h($rowClass) . '">'
        . '<code id="' . hs_h($elementId) . '" ' . $hiddenAttr . '="1">'
        . str_repeat("\u{2022}", 12) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" ' . $toggleAttr
        . ' data-target="' . hs_h($elementId) . '" data-reveal-url="' . hs_h($revealUrl) . '"'
        . ' data-prompt="' . hs_h($prompt) . '"'
        . ' data-label-show="' . hs_h($showLabel) . '" data-label-hide="' . hs_h($hideLabel) . '">'
        . hs_h($showLabel) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-secret="' . hs_h($copyTarget) . '"'
        . ' data-copied-label="' . hs_h($copiedLabel) . '" disabled><i class="fa-solid fa-copy"></i></button>'
        . '</div>';
}

/** @return array{ok:bool,error?:string} */
function hs_master_password_change(array $user, string $current, string $new, string $confirm): array
{
    $userId = (string) ($user['id'] ?? '');
    $minLen = (defined('HS_DEMO_MODE') && HS_DEMO_MODE) ? 4 : 8;

    if (!hs_rate_limit('master_pass_change_' . $userId, 6, 300)) {
        return ['ok' => false, 'error' => 'rate_limit'];
    }

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
    hs_master_password_set_flash($userId, $new);
    if (function_exists('hs_panel_log')) {
        hs_panel_log($userId, 'master_password_changed');
    }

    return ['ok' => true];
}

/** @return array{ok:bool,password?:string,error?:string} */
function hs_master_password_generate(array $user, string $current): array
{
    $userId = (string) ($user['id'] ?? '');
    if (!hs_rate_limit('master_pass_change_' . $userId, 6, 300)) {
        return ['ok' => false, 'error' => 'rate_limit'];
    }
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
    hs_master_password_set_flash($userId, $pass);
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
            'desc' => $t['account_service_panel_desc'] ?? ('Sign in at ' . (function_exists('hs_absolute_url') ? hs_absolute_url(hs_panel_path('login.php')) : hs_url('login.php'))),
        ],
        [
            'id' => 'ftp',
            'icon' => 'fa-server',
            'title' => $t['account_service_ftp'] ?? 'FTP / SFTP',
            'desc' => $t['account_service_ftp_desc'] ?? 'Your own login, jailed to site folder {path}',
        ],
        [
            'id' => 'ssh',
            'icon' => 'fa-terminal',
            'title' => $t['account_service_ssh'] ?? 'SFTP (SSH file transfer)',
            'desc' => $t['account_service_ssh_desc'] ?? 'Same login as FTP — FileZilla protocol SFTP · folder {folder}',
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
        'rate_limit' => $t['account_master_pass_error_rate'] ?? 'Too many attempts. Wait a few minutes.',
        'empty' => $t['account_master_pass_error_empty'] ?? 'No password stored yet.',
    ];

    return $map[$code] ?? ($t['account_master_pass_error_save'] ?? 'Could not update password.');
}