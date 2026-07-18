<?php
declare(strict_types=1);

/** Wildcard zone for free tier, e.g. username.site.bilohash.com */
function hs_platform_free_zone(): string
{
    $zone = hs_host_profile_value('platform_free_zone');
    if ($zone !== null && $zone !== '') {
        return strtolower(trim((string) $zone));
    }
    return 'site.bilohash.com';
}

function hs_plan_is_free(string $planId): bool
{
    require_once __DIR__ . '/plans.php';
    return hs_plan_normalize_id($planId) === 'free';
}

function hs_platform_free_subdomain_for_user(array $user): string
{
    $username = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($user['username'] ?? '')));
    if ($username === '') {
        return '';
    }
    return $username . '.' . hs_platform_free_zone();
}

/** @param array<string, mixed> $settings */
function hs_user_platform_free_host(string $userId, ?array $settings = null): string
{
    if ($settings === null) {
        require_once __DIR__ . '/user-settings.php';
        $settings = hs_user_settings_get($userId);
    }
    require_once __DIR__ . '/installer.php';
    return hs_normalize_public_host((string) ($settings['platform_free_host'] ?? ''));
}

/** Assign {username}.site.bilohash.com after free plan activation. */
function hs_platform_free_subdomain_assign(string $userId, array $user): void
{
    if (!hs_plan_is_free((string) ($user['plan'] ?? ''))) {
        return;
    }
    $host = hs_platform_free_subdomain_for_user($user);
    if ($host === '') {
        return;
    }
    require_once __DIR__ . '/user-settings.php';
    hs_user_settings_save($userId, [
        'platform_free_host' => $host,
        'primary_domain' => $host,
        'active_domain' => $host,
    ]);
}

/** @param array<string, mixed> $ctx hs_panel_checkout_context() */
function hs_panel_checkout_is_free(array $ctx): bool
{
    if (empty($ctx['wantHosting']) || !empty($ctx['wantDomain'])) {
        return false;
    }
    $planId = (string) ($ctx['planId'] ?? '');
    if (hs_plan_is_free($planId)) {
        return true;
    }
    return (float) ($ctx['planNok'] ?? 0) <= 0 && (float) ($ctx['domainEur'] ?? 0) <= 0;
}

/**
 * Activate $0 hosting (free plan) — provision DB, VPS client, subdomain.
 *
 * @param array<string, mixed> $user
 * @return array{ok:bool,user?:array,error?:string}
 */
function hs_free_plan_activate_user(array $user, string $lang = 'en'): array
{
    require_once __DIR__ . '/payment-fulfill.php';
    $priced = [
        'hosting_nok' => 0,
        'domain_eur' => 0,
        'hosting_discount_nok' => 0,
        'domain_discount_eur' => 0,
    ];
    return hs_payment_fulfill_checkout($user, $priced, null, [
        'lang' => $lang,
        'payment_provider' => 'free',
        'payment_ref' => 'free_' . gmdate('YmdHis'),
    ]);
}

function hs_is_platform_free_client_host(string $host): bool
{
    require_once __DIR__ . '/installer.php';
    $host = hs_normalize_public_host($host);
    $zone = hs_platform_free_zone();
    if ($host === $zone) {
        return true;
    }
    $suffix = '.' . $zone;
    return $host !== '' && str_ends_with($host, $suffix) && $host !== $zone;
}