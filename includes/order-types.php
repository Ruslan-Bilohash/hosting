<?php
declare(strict_types=1);

/** @return 'hosting'|'domain'|'bundle' */
function hs_order_type_normalize(string $type): string
{
    $type = strtolower(trim($type));
    return in_array($type, ['hosting', 'domain', 'bundle'], true) ? $type : 'bundle';
}

/** @param array<string, mixed> $user */
function hs_user_order_type(array $user): string
{
    return hs_order_type_normalize((string) ($user['order_type'] ?? 'bundle'));
}

function hs_order_includes_hosting(string $type): bool
{
    return in_array(hs_order_type_normalize($type), ['hosting', 'bundle'], true);
}

/** @param array<string, mixed> $user */
function hs_order_includes_domain(array $user): bool
{
    $type = hs_user_order_type($user);
    if ($type === 'domain') {
        return true;
    }
    // Any pending domain cart (bundle, hosting upgrade, panel purchase)
    if (function_exists('hs_user_pending_domains')) {
        return hs_user_pending_domains($user) !== [];
    }

    return trim((string) ($user['pending_domain'] ?? '')) !== '';
}

function hs_order_plan_for_type(string $type, string $selectedPlan): string
{
    if (hs_order_type_normalize($type) === 'domain') {
        return 'domain';
    }
    return $selectedPlan;
}

/** @return array<string, array<string, mixed>> */
function hs_plans_for_register(): array
{
    require_once __DIR__ . '/plan-catalog.php';
    return hs_plan_catalog_public_plans('hosting');
}