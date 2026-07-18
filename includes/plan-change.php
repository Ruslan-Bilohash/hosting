<?php
declare(strict_types=1);

require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/invoices.php';

/** @return list<array<string,mixed>> */
function hs_plan_change_catalog(array $user, array $t, string $lang): array
{
    $current = (string) ($user['plan'] ?? 'starter');
    $userId = (string) ($user['id'] ?? '');
    $sitesUsed = count(hs_sites_for_user($userId));
    $currentPlan = hs_plan($current);
    $currentPrice = (float) ($currentPlan['price_nok'] ?? 0);
    $out = [];

    foreach (hs_plans() as $pid => $plan) {
        if ($pid === 'pro') {
            continue;
        }
        // Hide free / domain-only pseudo-plans from upgrade UI (keep current if stuck on one).
        if ($pid !== $current && function_exists('hs_plan_catalog_is_public') && !hs_plan_catalog_is_public($pid, $plan)) {
            continue;
        }
        if ($pid !== $current && (($plan['type'] ?? '') === 'domain_only' || $pid === 'domain' || $pid === 'free')) {
            continue;
        }
        $siteLimit = (int) ($plan['sites'] ?? 1);
        $price = (float) ($plan['price_nok'] ?? 0);
        $isCurrent = $pid === $current;
        $downgradeBlocked = !$isCurrent && $sitesUsed > $siteLimit;
        $diff = max(0.0, $price - $currentPrice);
        if (!$isCurrent && $diff <= 0) {
            $diff = $price;
        }
        $features = [];
        foreach (hs_plan_feature_lines($plan, $t) as $line) {
            $features[] = $line;
        }
        $specs = hs_plan_hostinger_specs($pid);
        $out[] = [
            'id' => $pid,
            'name' => (string) ($t['plan_' . $pid] ?? $pid),
            'desc' => (string) ($t['plan_' . $pid . '_desc'] ?? ''),
            'price_label' => hs_format_plan_price($pid, $lang),
            'price_was' => hs_format_plan_was_price($pid, $lang),
            'price_nok' => $price,
            'diff_nok' => $isCurrent ? 0.0 : $diff,
            'diff_label' => $isCurrent ? '' : hs_format_nok_price($diff, $lang),
            'is_current' => $isCurrent,
            'is_popular' => ($plan['badge'] ?? '') === 'popular',
            'sites' => $siteLimit,
            'disk_gb' => (int) ($specs['disk_gb'] ?? 0),
            'features' => $features,
            'can_select' => !$isCurrent && !$downgradeBlocked,
            'downgrade_blocked' => $downgradeBlocked,
            'sites_used' => $sitesUsed,
        ];
    }
    return $out;
}

/** @return array{ok:bool,error?:string,plan?:string,invoice_id?:string} */
function hs_plan_change_apply(array $user, string $newPlanId, string $lang = 'uk'): array
{
    $userId = (string) ($user['id'] ?? '');
    $current = (string) ($user['plan'] ?? 'starter');
    $newPlanId = trim($newPlanId);

    if ($userId === '' || $newPlanId === '' || !hs_plan_id_valid($newPlanId) || $newPlanId === 'pro') {
        return ['ok' => false, 'error' => 'invalid_plan'];
    }
    if ($newPlanId === 'domain' || $newPlanId === 'free') {
        return ['ok' => false, 'error' => 'invalid_plan'];
    }
    $newPlanCheck = hs_plan($newPlanId);
    if (($newPlanCheck['type'] ?? '') === 'domain_only'
        || (function_exists('hs_plan_catalog_is_public') && !hs_plan_catalog_is_public($newPlanId, $newPlanCheck))) {
        return ['ok' => false, 'error' => 'invalid_plan'];
    }
    if ($newPlanId === $current) {
        return ['ok' => false, 'error' => 'same_plan'];
    }

    $sitesUsed = count(hs_sites_for_user($userId));
    $newPlan = hs_plan($newPlanId);
    $siteLimit = (int) ($newPlan['sites'] ?? 1);
    if ($sitesUsed > $siteLimit) {
        return ['ok' => false, 'error' => 'downgrade_blocked', 'sites_used' => $sitesUsed, 'sites_limit' => $siteLimit];
    }

    $oldPlan = $current;
    $ok = hs_user_update($userId, static function (array &$u) use ($newPlanId): void {
        $u['plan'] = $newPlanId;
        if (($u['subscription_status'] ?? '') === 'pending') {
            $u['subscription_status'] = 'active';
            $u['active'] = true;
        }
    });
    if (!$ok) {
        return ['ok' => false, 'error' => 'save'];
    }

    $updated = hs_user_by_id($userId) ?? $user;
    $invoice = hs_invoice_from_event('plan_changed', $updated, [
        'old_plan' => $oldPlan,
        'new_plan' => $newPlanId,
        'lang' => $lang,
    ]);

    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'plan_change', $oldPlan . '→' . $newPlanId);
    }

    return [
        'ok' => true,
        'plan' => $newPlanId,
        'invoice_id' => $invoice !== null ? (string) ($invoice['id'] ?? '') : '',
        'invoice_number' => $invoice !== null ? (string) ($invoice['number'] ?? '') : '',
    ];
}