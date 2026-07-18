<?php
declare(strict_types=1);

require_once __DIR__ . '/domain-orders.php';

if (!defined('HS_ACTIVE_DOMAIN_COOKIE')) {
    define('HS_ACTIVE_DOMAIN_COOKIE', 'hs_active_domain');
}

/**
 * Fallback host helpers — keep panel bootable even if helpers.php is stale in opcache.
 * Prefer definitions from includes/helpers.php when already loaded.
 */
if (!function_exists('hs_normalize_public_host')) {
    function hs_normalize_public_host(string $domain): string
    {
        $domain = strtolower(trim((string) preg_replace('#^https?://#i', '', $domain)));
        $domain = (string) preg_replace('#/.*$#', '', $domain);
        $domain = (string) preg_replace('#:\d+$#', '', $domain);
        $domain = (string) preg_replace('#^www\.#', '', $domain);

        return trim($domain, ". \t\n\r\0\x0B");
    }
}

if (!function_exists('hs_platform_hosts')) {
    /** @return list<string> */
    function hs_platform_hosts(): array
    {
        global $site_url;
        $hosts = [];
        $candidates = [
            strtolower((string) parse_url((string) ($site_url ?? ''), PHP_URL_HOST)),
            function_exists('hs_default_primary_domain') ? strtolower(hs_default_primary_domain()) : '',
            defined('HS_PRIMARY_DOMAIN') ? strtolower((string) HS_PRIMARY_DOMAIN) : '',
            defined('HS_BRAND_DOMAIN') ? strtolower((string) HS_BRAND_DOMAIN) : '',
            'localhost',
        ];
        if (function_exists('hs_host_profile_value')) {
            $server = (string) (hs_host_profile_value('server_hostname') ?? '');
            if ($server !== '') {
                $candidates[] = strtolower($server);
            }
        }
        foreach ($candidates as $host) {
            $host = hs_normalize_public_host((string) $host);
            if ($host === '') {
                continue;
            }
            $hosts[] = $host;
            $hosts[] = 'www.' . $host;
        }

        return array_values(array_unique($hosts));
    }
}

if (!function_exists('hs_is_platform_host')) {
    function hs_is_platform_host(string $host): bool
    {
        $host = hs_normalize_public_host($host);
        if ($host === '') {
            return true;
        }
        $list = hs_platform_hosts();

        return in_array($host, $list, true) || in_array('www.' . $host, $list, true);
    }
}

/**
 * True when $domain is the hosting brand hostname (solaskinner.com etc.),
 * not a client site. Subdomains like user.clients.brand.com are allowed.
 */
function hs_domain_is_host_brand(string $domain): bool
{
    $domain = strtolower(trim($domain));
    if ($domain === '') {
        return false;
    }

    return hs_is_platform_host($domain);
}

/** Strip host-brand hostnames so clients never see the panel domain as "their" site. */
function hs_domain_client_safe(?string $domain): string
{
    $domain = strtolower(trim((string) $domain));
    if ($domain === '' || hs_domain_is_host_brand($domain)) {
        return '';
    }

    return $domain;
}

/**
 * Clear primary/active if they were wrongly set to the host brand domain.
 *
 * @return array{settings: array, patch: array<string, string>}
 */
function hs_user_settings_sanitize_host_brand_domains(array $settings): array
{
    $patch = [];
    foreach (['primary_domain', 'active_domain'] as $key) {
        $val = strtolower(trim((string) ($settings[$key] ?? '')));
        if ($val !== '' && hs_domain_is_host_brand($val)) {
            $patch[$key] = '';
            $settings[$key] = '';
        }
    }

    return ['settings' => $settings, 'patch' => $patch];
}

/** @return list<string> */
function hs_user_domain_choices(array $settings): array
{
    $primary = hs_domain_client_safe((string) ($settings['primary_domain'] ?? ''));
    $out = [];
    if ($primary !== '') {
        $out[] = $primary;
    }
    // Active domain must route even if not yet set as primary
    $active = hs_domain_client_safe((string) ($settings['active_domain'] ?? ''));
    if ($active !== '') {
        $out[] = $active;
    }
    foreach ($settings['extra_domains'] ?? [] as $d) {
        if (!is_string($d) || $d === '') {
            continue;
        }
        $dom = hs_domain_client_safe($d);
        if ($dom !== '') {
            $out[] = $dom;
        }
    }
    // Domains in registry (paid / pending) also need HTTP_HOST routing + DNS defaults
    foreach (is_array($settings['domain_registry'] ?? null) ? $settings['domain_registry'] : [] as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $dom = hs_domain_client_safe((string) ($entry['domain'] ?? ''));
        if ($dom !== '') {
            $out[] = $dom;
        }
    }
    foreach ($settings['domains'] ?? [] as $sub) {
        if (!is_array($sub)) {
            continue;
        }
        $name = (string) ($sub['name'] ?? '');
        if ($name !== '' && $primary !== '') {
            $out[] = $name . '.' . $primary;
        }
    }
    // Free-tier platform subdomain (username.site.*) is a valid client host.
    $freeHost = hs_domain_client_safe((string) ($settings['platform_free_host'] ?? ''));
    if ($freeHost !== '') {
        $out[] = $freeHost;
    }

    return array_values(array_unique(array_filter($out)));
}

function hs_active_domain(?array $settings): string
{
    if ($settings === null || $settings === []) {
        $settings = hs_user_settings_defaults();
    }
    $choices = hs_user_domain_choices($settings);
    if (isset($_GET['domain']) && is_string($_GET['domain'])) {
        $d = strtolower(trim($_GET['domain']));
        if (in_array($d, $choices, true)) {
            setcookie(HS_ACTIVE_DOMAIN_COOKIE, $d, [
                'expires' => time() + 86400 * 90,
                'path' => hs_cookie_path(),
                'secure' => function_exists('hs_cookie_secure') ? hs_cookie_secure() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'samesite' => 'Lax',
            ]);
            return $d;
        }
    }
    if (!empty($_COOKIE[HS_ACTIVE_DOMAIN_COOKIE])) {
        $c = strtolower((string) $_COOKIE[HS_ACTIVE_DOMAIN_COOKIE]);
        if (in_array($c, $choices, true)) {
            return $c;
        }
    }
    $saved = hs_domain_client_safe((string) ($settings['active_domain'] ?? ''));
    if ($saved !== '' && in_array($saved, $choices, true)) {
        return $saved;
    }
    // Empty = no client domain yet. Never fall back to host brand (solaskinner.com).
    return $choices[0] ?? '';
}

function hs_domain_switch_url(string $domain): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    parse_str($parts['query'] ?? '', $q);
    $q['domain'] = $domain;
    return ($parts['path'] ?? '/') . '?' . http_build_query($q);
}

/** @return list<array{domain:string,role:string,registered_at:string,expires_at:string}> */
function hs_user_domain_registry_build_from_settings(array $settings): array
{
    $registry = [];
    $now = date('c');
    $expires = date('c', strtotime('+1 year'));
    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? '')));
    if ($primary !== '') {
        $registry[] = [
            'domain' => $primary,
            'role' => 'primary',
            'registered_at' => $now,
            'expires_at' => $expires,
        ];
    }
    foreach ($settings['extra_domains'] ?? [] as $d) {
        if (!is_string($d) || $d === '') {
            continue;
        }
        $dom = strtolower(trim($d));
        if ($dom === $primary) {
            continue;
        }
        $registry[] = [
            'domain' => $dom,
            'role' => 'parked',
            'registered_at' => $now,
            'expires_at' => $expires,
        ];
    }
    return $registry;
}

/** @return list<array{domain:string,role:string,registered_at:string,expires_at:string}> */
function hs_user_domain_registry_ensure(string $userId, array $settings): array
{
    $stored = is_array($settings['domain_registry'] ?? null) ? $settings['domain_registry'] : [];
    if ($stored !== []) {
        return hs_domain_registry_normalize_unpaid($userId, $stored);
    }
    $migrated = hs_user_domain_registry_build_from_settings($settings);
    if ($migrated !== []) {
        $migrated = hs_domain_registry_normalize_unpaid($userId, $migrated);
        if ($migrated !== []) {
            hs_user_settings_save($userId, ['domain_registry' => $migrated]);
        }
    }
    return $migrated;
}

function hs_user_domain_registry_sync(string $userId, array $settings): void
{
    $registry = hs_user_domain_registry_ensure($userId, $settings);
    $byDomain = [];
    foreach ($registry as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $dom = strtolower(trim((string) ($entry['domain'] ?? '')));
        if ($dom !== '') {
            $byDomain[$dom] = $entry;
        }
    }

    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? '')));
    $extras = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
    $now = date('c');
    $defaultExpires = date('c', strtotime('+1 year'));
    $allowed = [];

    if ($primary !== '') {
        $allowed[] = $primary;
        if (isset($byDomain[$primary])) {
            $byDomain[$primary]['role'] = 'primary';
        } else {
            $byDomain[$primary] = [
                'domain' => $primary,
                'role' => 'primary',
                'registered_at' => $now,
                'expires_at' => $defaultExpires,
            ];
            if (hs_domain_entry_awaiting_payment($userId, $byDomain[$primary])) {
                $byDomain[$primary]['pending_payment'] = true;
                unset($byDomain[$primary]['pending_registration']);
                $byDomain[$primary]['expires_at'] = '';
            }
        }
    }

    foreach ($extras as $d) {
        if (!is_string($d) || $d === '') {
            continue;
        }
        $dom = strtolower(trim($d));
        if ($dom === '' || $dom === $primary) {
            continue;
        }
        $allowed[] = $dom;
        if (isset($byDomain[$dom])) {
            $byDomain[$dom]['role'] = 'parked';
        } else {
            $byDomain[$dom] = [
                'domain' => $dom,
                'role' => 'parked',
                'registered_at' => $now,
                'expires_at' => $defaultExpires,
            ];
            if (hs_domain_entry_awaiting_payment($userId, $byDomain[$dom])) {
                $byDomain[$dom]['pending_payment'] = true;
                unset($byDomain[$dom]['pending_registration']);
                $byDomain[$dom]['expires_at'] = '';
            }
        }
    }

    $newRegistry = [];
    foreach ($allowed as $dom) {
        if (isset($byDomain[$dom])) {
            $newRegistry[] = $byDomain[$dom];
        }
    }

    foreach ($byDomain as $dom => $entry) {
        if (!is_array($entry) || empty($entry['pending_registration'])) {
            continue;
        }
        if (!in_array($dom, $allowed, true)) {
            $newRegistry[] = $entry;
        }
    }

    hs_user_settings_save($userId, ['domain_registry' => $newRegistry]);
}

function hs_domain_registry_days_left(string $expiresAt): ?int
{
    $ts = strtotime($expiresAt);
    if ($ts === false) {
        return null;
    }
    return (int) floor(($ts - time()) / 86400);
}

/** @return 'active'|'expiring'|'expired' */
function hs_domain_registry_status(string $expiresAt): string
{
    $days = hs_domain_registry_days_left($expiresAt);
    if ($days === null) {
        return 'active';
    }
    if ($days < 0) {
        return 'expired';
    }
    if ($days <= 30) {
        return 'expiring';
    }
    return 'active';
}

/** @return array<string,mixed>|null */
function hs_domain_registry_entry(string $userId, string $domain, ?array $settings = null): ?array
{
    $domain = strtolower(trim($domain));
    if ($domain === '') {
        return null;
    }
    $settings ??= hs_user_settings_get($userId);
    foreach (hs_user_domain_registry_ensure($userId, $settings) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (strtolower((string) ($entry['domain'] ?? '')) === $domain) {
            return $entry;
        }
    }
    return null;
}

/** @param array<string,mixed> $entry */
function hs_domain_entry_is_purchased(array $entry, string $userId): bool
{
    if (!empty($entry['purchased']) || !empty($entry['payment_confirmed']) || !empty($entry['registry_registered'])) {
        return true;
    }
    // Paid Namecheap path: pending_registration only after payment_confirmed.
    if (!empty($entry['pending_registration']) && empty($entry['pending_payment'])) {
        $domain = strtolower((string) ($entry['domain'] ?? ''));
        foreach (hs_domain_orders_for_user($userId) as $order) {
            if (strtolower((string) ($order['domain'] ?? '')) !== $domain) {
                continue;
            }
            if (!empty($order['payment_confirmed']) || ($order['status'] ?? '') === 'active') {
                return true;
            }
        }
    }
    $domain = strtolower((string) ($entry['domain'] ?? ''));
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if (strtolower((string) ($order['domain'] ?? '')) !== $domain) {
            continue;
        }
        if (($order['status'] ?? '') === 'active' || !empty($order['payment_confirmed'])) {
            return true;
        }
    }

    return false;
}

/** @return array{ok:bool,error?:string,days?:int,expires_at?:string} */
function hs_domain_delete_allowed(string $userId, string $domain): array
{
    $domain = strtolower(trim($domain));
    if ($domain === '' || hs_domain_normalize($domain) === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }

    $settings = hs_user_settings_get($userId);
    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? '')));
    $extras = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
    $inExtras = in_array($domain, array_map('strtolower', array_map('strval', $extras)), true);
    $entry = hs_domain_registry_entry($userId, $domain, $settings);

    if ($entry === null && !$inExtras && $primary !== $domain) {
        return ['ok' => false, 'error' => 'not_found'];
    }

    // Unpaid wish/cart domains can always be removed by the client.
    if ($entry !== null && hs_domain_entry_awaiting_payment($userId, $entry)) {
        return ['ok' => true, 'unpaid' => true];
    }

    if ($entry !== null && !empty($entry['pending_registration']) && !hs_domain_entry_awaiting_payment($userId, $entry)) {
        return ['ok' => false, 'error' => 'pending'];
    }

    $isPrimary = $domain === $primary || (($entry['role'] ?? '') === 'primary');
    if ($isPrimary) {
        if ($entry === null || hs_domain_registry_display_status($entry, $userId) !== 'expired') {
            return [
                'ok' => false,
                'error' => 'primary_active',
                'days' => $entry !== null ? hs_domain_registry_days_left((string) ($entry['expires_at'] ?? '')) : null,
                'expires_at' => $entry !== null ? (string) ($entry['expires_at'] ?? '') : '',
            ];
        }
    }

    if ($entry !== null && hs_domain_entry_is_purchased($entry, $userId)) {
        $status = hs_domain_registry_display_status($entry, $userId);
        if ($status !== 'expired') {
            return [
                'ok' => false,
                'error' => 'purchased_active',
                'days' => hs_domain_registry_days_left((string) ($entry['expires_at'] ?? '')),
                'expires_at' => (string) ($entry['expires_at'] ?? ''),
            ];
        }
    }

    return ['ok' => true];
}

/** @return array{ok:bool,error?:string} */
function hs_domain_remove(string $userId, string $domain): array
{
    $check = hs_domain_delete_allowed($userId, $domain);
    if (!$check['ok']) {
        return $check;
    }

    $domain = strtolower(trim($domain));
    $settings = hs_user_settings_get($userId);
    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? '')));
    $patch = [];

    $extras = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
    $newExtras = [];
    foreach ($extras as $d) {
        if (strtolower(trim((string) $d)) !== $domain) {
            $newExtras[] = $d;
        }
    }
    if ($newExtras !== $extras) {
        $patch['extra_domains'] = $newExtras;
    }

    $registry = [];
    foreach (hs_user_domain_registry_ensure($userId, $settings) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (strtolower((string) ($entry['domain'] ?? '')) !== $domain) {
            $registry[] = $entry;
        }
    }
    $patch['domain_registry'] = $registry;

    // Drop unpaid domain from checkout cart / pending list.
    if (function_exists('hs_user_by_id') && function_exists('hs_user_update')) {
        $user = hs_user_by_id($userId);
        if (is_array($user)) {
            require_once __DIR__ . '/domain-cart.php';
            $pending = hs_user_pending_domains($user);
            $left = array_values(array_filter(
                $pending,
                static fn(string $d): bool => strtolower($d) !== $domain
            ));
            if ($left !== $pending) {
                hs_user_update($userId, static function (array &$u) use ($left): void {
                    $u['pending_domains'] = $left;
                    $u['pending_domain'] = $left[0] ?? '';
                });
            }
            $sess = hs_domain_cart_list();
            $sessLeft = array_values(array_filter($sess, static fn(string $d): bool => strtolower($d) !== $domain));
            if ($sessLeft !== $sess) {
                hs_domain_cart_set($sessLeft);
            }
        }
    }
    // Cancel unpaid domain-orders for this domain (not paid / not registered).
    if (function_exists('hs_domain_orders') && function_exists('hs_domain_orders_save')) {
        $orders = hs_domain_orders();
        $changed = false;
        foreach ($orders as $i => $ord) {
            if (!is_array($ord)) {
                continue;
            }
            if ((string) ($ord['user_id'] ?? '') !== $userId) {
                continue;
            }
            if (strtolower((string) ($ord['domain'] ?? '')) !== $domain) {
                continue;
            }
            if (($ord['status'] ?? '') !== 'pending' || !empty($ord['payment_confirmed'])) {
                continue;
            }
            $orders[$i]['status'] = 'cancelled';
            $orders[$i]['cancelled_at'] = gmdate('c');
            $changed = true;
        }
        if ($changed) {
            hs_domain_orders_save($orders);
        }
    }

    if ($primary === $domain) {
        $newPrimary = '';
        foreach ($registry as $entry) {
            if (!is_array($entry) || !empty($entry['pending_registration'])) {
                continue;
            }
            $candidate = strtolower(trim((string) ($entry['domain'] ?? '')));
            if ($candidate !== '') {
                $newPrimary = $candidate;
                break;
            }
        }
        if ($newPrimary === '') {
            foreach ($newExtras as $d) {
                $candidate = strtolower(trim((string) $d));
                if ($candidate !== '') {
                    $newPrimary = $candidate;
                    break;
                }
            }
        }
        // Leave empty when no other client domain remains — do not assign host brand.
        $patch['primary_domain'] = $newPrimary;
        $patch['active_domain'] = $newPrimary;
    } elseif (strtolower(trim((string) ($settings['active_domain'] ?? ''))) === $domain) {
        $patch['active_domain'] = ($primary !== '' && $primary !== $domain && !hs_domain_is_host_brand($primary))
            ? $primary
            : '';
    }

    if (!hs_user_settings_save($userId, $patch)) {
        return ['ok' => false, 'error' => 'save'];
    }

    return ['ok' => true];
}

/** @param array<string,mixed> $entry */
function hs_domain_delete_action_html(array $entry, string $userId, array $t): string
{
    $dom = (string) ($entry['domain'] ?? '');
    $check = hs_domain_delete_allowed($userId, $dom);
    $awaitingPay = hs_domain_entry_awaiting_payment($userId, $entry);

    $payBtn = '';
    if ($awaitingPay) {
        $payHref = hs_url(hs_panel_path('activate.php'), ['order' => 'domain', 'domain' => $dom]);
        $payBtn = '<a href="' . hs_h($payHref) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm" title="'
            . hs_h($t['dom_delete_blocked_pending_payment'] ?? 'Pay to register domain') . '">'
            . '<i class="fa-solid fa-credit-card"></i> ' . hs_h($t['panel_activate_pay_btn'] ?? 'Pay') . '</a>';
    }

    if (!$check['ok']) {
        $msg = match ($check['error'] ?? '') {
            'pending' => $t['dom_delete_blocked_pending'] ?? 'Registration in progress',
            'primary_active' => str_replace(
                ['{date}', '{days}'],
                [
                    ($check['expires_at'] ?? '') !== '' ? hs_format_date((string) $check['expires_at']) : '—',
                    (string) max(0, (int) ($check['days'] ?? 0)),
                ],
                $t['dom_delete_blocked_primary'] ?? 'Primary domain — delete after {date} ({days} days left)'
            ),
            'purchased_active' => str_replace(
                ['{date}', '{days}'],
                [
                    ($check['expires_at'] ?? '') !== '' ? hs_format_date((string) $check['expires_at']) : '—',
                    (string) max(0, (int) ($check['days'] ?? 0)),
                ],
                $t['dom_delete_blocked_purchased'] ?? 'Purchased domain — delete after {date}'
            ),
            'not_found' => $t['dom_delete_not_found'] ?? 'Not found',
            default => $t['dom_delete_blocked'] ?? 'Cannot delete',
        };
        if ($payBtn !== '') {
            return '<span class="hs-dom-actions-inline" style="display:inline-flex;gap:.4rem;flex-wrap:wrap;align-items:center">'
                . $payBtn
                . '</span>';
        }

        return '<span class="hs-dom-delete-locked" title="' . hs_h($msg) . '"><i class="fa-solid fa-lock"></i> '
            . '<span class="hp-muted">' . hs_h($t['dom_delete_locked'] ?? 'Locked') . '</span></span>';
    }

    $confirm = str_replace(
        '{domain}',
        $dom,
        $t['dom_delete_confirm'] ?? ($awaitingPay
            ? 'Remove unpaid domain {domain} from your account?'
            : 'Delete {domain}?')
    );
    $deleteForm = '<form method="post" class="hs-dom-delete-form" data-hs-dom-delete style="display:inline"'
        . ' onsubmit="return confirm(' . json_encode($confirm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ');">'
        . hs_csrf_field()
        . '<input type="hidden" name="delete_domain" value="1">'
        . '<input type="hidden" name="domain_name" value="' . hs_h($dom) . '">'
        . '<button type="submit" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-dom-delete-btn" title="'
        . hs_h($t['btn_delete'] ?? 'Delete') . '">'
        . '<i class="fa-solid fa-trash"></i> ' . hs_h($t['btn_delete'] ?? 'Delete') . '</button></form>';

    if ($payBtn !== '') {
        return '<span class="hs-dom-actions-inline" style="display:inline-flex;gap:.4rem;flex-wrap:wrap;align-items:center">'
            . $payBtn . $deleteForm
            . '</span>';
    }

    return $deleteForm;
}