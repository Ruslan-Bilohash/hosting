<?php
declare(strict_types=1);

define('HS_ACTIVE_DOMAIN_COOKIE', 'hs_active_domain');

/** @return list<string> */
function hs_user_domain_choices(array $settings): array
{
    $primary = strtolower(trim((string) ($settings['primary_domain'] ?? hs_default_primary_domain())));
    $out = [];
    if ($primary !== '') {
        $out[] = $primary;
    }
    foreach ($settings['extra_domains'] ?? [] as $d) {
        if (is_string($d) && $d !== '') {
            $out[] = strtolower(trim($d));
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
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
    $saved = strtolower(trim((string) ($settings['active_domain'] ?? '')));
    if ($saved !== '' && in_array($saved, $choices, true)) {
        return $saved;
    }
    return $choices[0] ?? hs_default_primary_domain();
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
        return $stored;
    }
    $migrated = hs_user_domain_registry_build_from_settings($settings);
    if ($migrated !== []) {
        hs_user_settings_save($userId, ['domain_registry' => $migrated]);
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
    if (!empty($entry['purchased']) || !empty($entry['pending_registration']) || !empty($entry['order_id'])) {
        return true;
    }
    $domain = strtolower((string) ($entry['domain'] ?? ''));
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if (strtolower((string) ($order['domain'] ?? '')) !== $domain) {
            continue;
        }
        if (in_array((string) ($order['status'] ?? ''), ['pending', 'active'], true)) {
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

    if ($entry !== null && !empty($entry['pending_registration'])) {
        return ['ok' => false, 'error' => 'pending'];
    }

    if ($entry !== null && hs_domain_entry_is_purchased($entry, $userId)) {
        $status = hs_domain_registry_display_status($entry);
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
        if ($newPrimary === '') {
            $newPrimary = strtolower(hs_default_primary_domain());
        }
        $patch['primary_domain'] = $newPrimary;
        $patch['active_domain'] = $newPrimary;
    } elseif (strtolower(trim((string) ($settings['active_domain'] ?? ''))) === $domain) {
        $patch['active_domain'] = $primary !== '' && $primary !== $domain
            ? $primary
            : strtolower(hs_default_primary_domain());
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
    if (!$check['ok']) {
        $msg = match ($check['error'] ?? '') {
            'pending' => $t['dom_delete_blocked_pending'] ?? 'Registration in progress',
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
        return '<span class="hs-dom-delete-locked" title="' . hs_h($msg) . '"><i class="fa-solid fa-lock"></i> '
            . '<span class="hp-muted">' . hs_h($t['dom_delete_locked'] ?? 'Locked') . '</span></span>';
    }
    $confirm = str_replace('{domain}', $dom, $t['dom_delete_confirm'] ?? 'Delete {domain}?');
    return '<form method="post" class="hs-dom-delete-form" data-hs-dom-delete'
        . ' onsubmit="return confirm(' . json_encode($confirm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ');">'
        . hs_csrf_field()
        . '<input type="hidden" name="delete_domain" value="1">'
        . '<input type="hidden" name="domain_name" value="' . hs_h($dom) . '">'
        . '<button type="submit" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-dom-delete-btn" title="' . hs_h($t['btn_delete'] ?? 'Delete') . '">'
        . '<i class="fa-solid fa-trash"></i></button></form>';
}