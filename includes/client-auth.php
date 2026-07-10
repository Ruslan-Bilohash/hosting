<?php
declare(strict_types=1);

require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/countries.php';

define('HS_CLIENT_SESSION', 'hs_client_id');
define('HS_CLIENT_USER', 'hs_client_username');

function hs_client_login(string $login, string $pass): bool
{
    if (!hs_rate_limit('login_' . md5($login), 8, 600)) {
        return false;
    }
    $user = hs_user_by_login($login);
    if ($user === null || empty($user['active'])) {
        return false;
    }
    if (!password_verify($pass, (string) ($user['password_hash'] ?? ''))) {
        return false;
    }
    hs_session_start();
    session_regenerate_id(true);
    $_SESSION[HS_CLIENT_SESSION] = $user['id'];
    $_SESSION[HS_CLIENT_USER] = $user['username'] ?? $user['email'];
    $_SESSION['hs_panel_session_start'] = time();
    require_once __DIR__ . '/activity-log.php';
    hs_activity_log_append((string) $user['id'], [
        'type' => 'login',
        'action' => 'login',
        'detail' => (string) ($user['username'] ?? ''),
    ]);
    return true;
}

function hs_client_logout(): void
{
    hs_session_start();
    $userId = is_string($_SESSION[HS_CLIENT_SESSION] ?? null) ? (string) $_SESSION[HS_CLIENT_SESSION] : '';
    $start = (int) ($_SESSION['hs_panel_session_start'] ?? 0);
    if ($userId !== '') {
        require_once __DIR__ . '/activity-log.php';
        $duration = $start > 0 ? max(0, time() - $start) : 0;
        hs_activity_log_append($userId, [
            'type' => 'logout',
            'action' => 'logout',
            'detail' => '',
            'duration_sec' => $duration,
        ]);
    }
    unset($_SESSION[HS_CLIENT_SESSION], $_SESSION[HS_CLIENT_USER], $_SESSION['hs_panel_session_start'], $_SESSION['panel_visits']);
}

function hs_client_id(): ?string
{
    hs_session_start();
    $id = $_SESSION[HS_CLIENT_SESSION] ?? null;
    return is_string($id) && $id !== '' ? $id : null;
}

function hs_client_user(): ?array
{
    $id = hs_client_id();
    return $id ? hs_user_by_id($id) : null;
}

function hs_client_require(): array
{
    $user = hs_client_user();
    if ($user === null) {
        hs_redirect('login.php');
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptPath = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    if (($user['subscription_status'] ?? 'active') !== 'active'
        && !in_array($script, ['checkout.php', 'logout.php'], true)
        && strpos($scriptPath, '/panel/stop-impersonate') === false) {
        hs_redirect('checkout.php');
    }
    return $user;
}

function hs_client_register(array $data): array
{
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $username = strtolower(trim((string) ($data['username'] ?? '')));
    $pass = (string) ($data['password'] ?? '');
    $firstName = trim((string) ($data['first_name'] ?? ''));
    $lastName = trim((string) ($data['last_name'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '' && ($firstName !== '' || $lastName !== '')) {
        $name = trim($firstName . ' ' . $lastName);
    }
    $phone = trim((string) ($data['phone'] ?? ''));
    $plan = (string) ($data['plan'] ?? 'starter');

    if (empty($data['consent_terms']) || empty($data['consent_privacy'])) {
        return ['ok' => false, 'error' => 'consent'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email'];
    }
    if ($phone === '' || strlen(preg_replace('/\D/', '', $phone) ?? '') < 8) {
        return ['ok' => false, 'error' => 'invalid_phone'];
    }
    if ($firstName === '' || $lastName === '') {
        return ['ok' => false, 'error' => 'invalid_name'];
    }
    $accountType = (string) ($data['account_type'] ?? 'personal');
    if ($accountType === 'business') {
        if (trim((string) ($data['company'] ?? '')) === '') {
            return ['ok' => false, 'error' => 'invalid_company'];
        }
        if (trim((string) ($data['vat'] ?? '')) === '') {
            return ['ok' => false, 'error' => 'invalid_vat'];
        }
    }
    if (trim((string) ($data['address'] ?? '')) === '' || trim((string) ($data['city'] ?? '')) === '' || trim((string) ($data['postal'] ?? '')) === '') {
        return ['ok' => false, 'error' => 'invalid_address'];
    }
    $country = strtoupper(trim((string) ($data['country'] ?? '')));
    if (!hs_country_valid($country)) {
        return ['ok' => false, 'error' => 'invalid_country'];
    }
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{2,31}$/', $username)) {
        return ['ok' => false, 'error' => 'invalid_username'];
    }
    $minPassLen = (defined('HS_DEMO_MODE') && HS_DEMO_MODE) ? 4 : 8;
    if (strlen($pass) < $minPassLen) {
        return ['ok' => false, 'error' => 'weak_password'];
    }
    if (!hs_plan_id_valid($plan)) {
        $plan = 'starter';
    }
    if (hs_user_by_login($email) !== null || hs_user_by_login($username) !== null) {
        return ['ok' => false, 'error' => 'exists'];
    }

    $domainWish = trim((string) ($data['domain_wish'] ?? ''));
    $normalizedDomain = $domainWish !== '' ? hs_domain_normalize($domainWish) : null;

    $users = hs_users();
    $user = [
        'id' => hs_new_id('u'),
        'email' => $email,
        'username' => $username,
        'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
        'name' => $name !== '' ? $name : $username,
        'plan' => $plan,
        'subscription_status' => 'pending',
        'paid_until' => null,
        'pending_domain' => null,
        'created_at' => gmdate('c'),
        'active' => true,
        'profile' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'company' => $accountType === 'business' ? trim((string) ($data['company'] ?? '')) : '',
            'vat' => $accountType === 'business' ? trim((string) ($data['vat'] ?? '')) : '',
            'address' => trim((string) ($data['address'] ?? '')),
            'city' => trim((string) ($data['city'] ?? '')),
            'postal' => trim((string) ($data['postal'] ?? '')),
            'country' => $country,
            'account_type' => $accountType,
        ],
        'consents' => [
            'terms_at' => gmdate('c'),
            'privacy_at' => gmdate('c'),
            'marketing' => !empty($data['consent_marketing']),
        ],
    ];
    hs_session_start();
    $pending = $_SESSION['hs_pending_domain'] ?? null;
    if (is_string($pending) && $pending !== '') {
        $user['pending_domain'] = $pending;
    } elseif ($normalizedDomain !== null) {
        $user['pending_domain'] = $normalizedDomain;
        $_SESSION['hs_pending_domain'] = $normalizedDomain;
    }
    require_once __DIR__ . '/client-identity.php';
    $user = hs_client_assign_identity_fields($user);
    $users[] = $user;
    if (!hs_save_users($users)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }

    hs_user_settings_save((string) $user['id'], [
        'registrant' => $user['profile'],
        'primary_domain' => $normalizedDomain ?? (is_string($pending) ? $pending : ''),
        'ftp_password_token' => $pass,
        'ssh_password_token' => $pass,
    ]);

    require_once __DIR__ . '/installer.php';
    hs_ensure_user_workspace($user);

    hs_client_login($username, $pass);
    return ['ok' => true, 'user' => $user];
}