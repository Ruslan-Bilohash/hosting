<?php
declare(strict_types=1);

require_once __DIR__ . '/plan-catalog.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/user-settings.php';

/** @param array<string, mixed> $plan */
function hs_plan_managed_as_addon_row(array $plan): array
{
    $id = (string) ($plan['id'] ?? '');
    $icon = match ($id) {
        'maintenance' => 'fa-screwdriver-wrench',
        'seo_specialist' => 'fa-magnifying-glass-chart',
        default => 'fa-puzzle-piece',
    };
    $labels = is_array($plan['labels'] ?? null) ? $plan['labels'] : [];
    $desc = is_array($plan['desc'] ?? null) ? $plan['desc'] : [];

    return [
        'id' => $id,
        'active' => !empty($plan['active']),
        'sort' => (int) ($plan['sort'] ?? 50),
        'icon' => $icon,
        'price_eur' => (float) ($plan['price_eur'] ?? 0),
        'price_nok' => (float) ($plan['price_nok'] ?? 0),
        'billing_period' => (string) ($plan['billing_period'] ?? 'month'),
        'labels' => $labels,
        'desc' => $desc,
        'from_plan' => true,
    ];
}

/** Catalog add-ons: services + managed-service tariffs (maintenance, SEO…). Domains excluded. */
/** @return array<string, array<string, mixed>> */
function hs_plan_addon_catalog_map(bool $activeOnly = true): array
{
    $map = [];
    foreach (hs_plan_catalog_services($activeOnly) as $svc) {
        $id = (string) ($svc['id'] ?? '');
        if ($id === '' || hs_plan_catalog_service_is_domain($id, $svc)) {
            continue;
        }
        $row = $svc;
        $row['source'] = 'service';
        $map[$id] = $row;
    }
    foreach (hs_plan_catalog_plans($activeOnly) as $id => $plan) {
        if (($plan['type'] ?? '') !== 'managed_service') {
            continue;
        }
        // Never treat domain_only plans as add-ons
        if ($id === 'domain' || ($plan['type'] ?? '') === 'domain_only') {
            continue;
        }
        // Prefer pure services[] row when both exist (admin edits live there)
        if (isset($map[$id]) && empty($map[$id]['from_plan'])) {
            continue;
        }
        $row = hs_plan_managed_as_addon_row($plan);
        $row['source'] = 'plan';
        $map[$id] = $row;
    }
    return $map;
}

/**
 * Whether an add-on id is stored as managed_service plan (not services[]).
 */
function hs_plan_addon_is_managed_plan(string $id): bool
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower($id)) ?? '';
    if ($id === '') {
        return false;
    }
    $catalog = hs_plan_catalog_load();
    $plan = $catalog['plans'][$id] ?? null;

    return is_array($plan) && ($plan['type'] ?? '') === 'managed_service';
}

/**
 * Save add-on into services[] or managed_service plan (same form fields).
 *
 * @param array<string, mixed> $row normalized service row
 * @return array{ok:bool,error?:string,source?:string}
 */
function hs_plan_addon_admin_save(array $row): array
{
    $id = (string) ($row['id'] ?? '');
    if ($id === '') {
        return ['ok' => false, 'error' => 'invalid_id'];
    }
    $catalog = hs_plan_catalog_load();

    if (hs_plan_addon_is_managed_plan($id)) {
        $plan = is_array($catalog['plans'][$id] ?? null) ? $catalog['plans'][$id] : ['id' => $id, 'type' => 'managed_service'];
        $plan['id'] = $id;
        $plan['type'] = 'managed_service';
        $plan['active'] = !empty($row['active']);
        $plan['sort'] = max(0, (int) ($row['sort'] ?? ($plan['sort'] ?? 40)));
        $plan['billing_period'] = (($row['billing_period'] ?? 'month') === 'year') ? 'year' : 'month';
        $plan['price_nok'] = max(0.0, (float) ($row['price_nok'] ?? 0));
        $plan['price_eur'] = max(0.0, (float) ($row['price_eur'] ?? 0));
        if ($plan['price_nok'] <= 0 && $plan['price_eur'] > 0 && function_exists('hs_eur_to_nok')) {
            $plan['price_nok'] = (float) hs_eur_to_nok($plan['price_eur']);
        }
        $plan['labels'] = is_array($row['labels'] ?? null) ? $row['labels'] : [];
        $plan['desc'] = is_array($row['desc'] ?? null) ? $row['desc'] : [];
        $plan['badge'] = (string) ($plan['badge'] ?? 'service');
        $catalog['plans'][$id] = array_merge($plan, [
            'sites' => 0,
            'storage_mb' => 0,
            'databases' => 0,
            'ecosystem_apps' => false,
            'webapp_details' => false,
            'disk_gb' => 0,
        ]);
        if (!hs_plan_catalog_save($catalog)) {
            return ['ok' => false, 'error' => 'save'];
        }

        return ['ok' => true, 'source' => 'plan'];
    }

    $found = false;
    foreach ($catalog['services'] as $i => $svc) {
        if (!is_array($svc) || (string) ($svc['id'] ?? '') !== $id) {
            continue;
        }
        $catalog['services'][$i] = array_merge($svc, $row);
        $found = true;
        break;
    }
    if (!$found) {
        $catalog['services'][] = $row;
    }
    if (!hs_plan_catalog_save($catalog)) {
        return ['ok' => false, 'error' => 'save'];
    }

    return ['ok' => true, 'source' => 'service'];
}

/**
 * Toggle add-on visibility on register (services[] or managed_service plan).
 *
 * @return array{ok:bool,active?:bool,error?:string}
 */
function hs_plan_addon_admin_toggle(string $id): array
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower($id)) ?? '';
    if ($id === '') {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $catalog = hs_plan_catalog_load();

    if (hs_plan_addon_is_managed_plan($id)) {
        $plan = $catalog['plans'][$id];
        $newActive = empty($plan['active']);
        $catalog['plans'][$id]['active'] = $newActive;
        if (!hs_plan_catalog_save($catalog)) {
            return ['ok' => false, 'error' => 'save'];
        }

        return ['ok' => true, 'active' => $newActive];
    }

    $found = false;
    $newActive = false;
    foreach ($catalog['services'] as $i => $svc) {
        if (!is_array($svc) || (string) ($svc['id'] ?? '') !== $id) {
            continue;
        }
        $newActive = empty($svc['active']);
        $catalog['services'][$i]['active'] = $newActive;
        $found = true;
        break;
    }
    if (!$found) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (!hs_plan_catalog_save($catalog)) {
        return ['ok' => false, 'error' => 'save'];
    }

    return ['ok' => true, 'active' => $newActive];
}

/** @return list<array<string, mixed>> */
function hs_plan_addon_catalog_list(bool $activeOnly = true): array
{
    $items = array_values(hs_plan_addon_catalog_map($activeOnly));
    usort($items, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));

    return $items;
}

/** @return array<string, array<string, mixed>> */
function hs_plan_services_catalog_map(bool $activeOnly = true): array
{
    return hs_plan_addon_catalog_map($activeOnly);
}

function hs_plan_addon_label(array $addon, string $lang, array $t): string
{
    $id = (string) ($addon['id'] ?? '');
    // 1) catalog labels (uk/en/no)
    $fromCatalog = hs_plan_catalog_service_label($addon, $lang);
    if ($fromCatalog !== '' && $fromCatalog !== $id) {
        return $fromCatalog;
    }
    // 2) i18n plan_* keys
    $fromT = trim((string) ($t['plan_' . $id] ?? ''));
    if ($fromT !== '') {
        return $fromT;
    }
    // 3) humanize id (never show raw snake_case if we can help it)
    if ($id !== '') {
        return ucwords(str_replace(['_', '-'], ' ', $id));
    }

    return $fromCatalog !== '' ? $fromCatalog : $id;
}

function hs_plan_addon_desc(array $addon, string $lang, array $t): string
{
    $id = (string) ($addon['id'] ?? '');
    $fromCatalog = hs_plan_catalog_service_desc($addon, $lang);
    if ($fromCatalog !== '') {
        return $fromCatalog;
    }
    $key = 'plan_' . $id . '_desc';

    return (string) ($t[$key] ?? '');
}

/** @param array<string, mixed> $addon */
function hs_plan_addon_unit_nok(array $addon): float
{
    $priceNok = (float) ($addon['price_nok'] ?? 0);
    if ($priceNok > 0) {
        return $priceNok;
    }
    $eur = (float) ($addon['price_eur'] ?? 0);
    if ($eur <= 0) {
        return 0.0;
    }
    $rates = hs_exchange_rates();
    $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));

    return round($eur / $eurRate, 2);
}

/**
 * NOK charge for one add-on over $months (matches renew invoice rules).
 *
 * @param array<string, mixed> $svc
 */
function hs_plan_service_period_nok(array $svc, int $months, bool $chargeYearlyOnActivate = false): float
{
    $months = max(1, min(36, $months));
    $unit = hs_plan_addon_unit_nok($svc);
    if ($unit <= 0) {
        return 0.0;
    }
    $isYearly = (($svc['billing_period'] ?? 'month') === 'year');
    if ($isYearly) {
        if ($months >= 12) {
            return round($unit * (int) floor($months / 12), 2);
        }

        return $chargeYearlyOnActivate ? $unit : 0.0;
    }

    return round($unit * $months, 2);
}

/** @param array<string, mixed> $user */
function hs_subscription_services_total_nok(array $user, int $months = 1, bool $chargeYearlyOnActivate = false): float
{
    $lang = (string) ($user['lang'] ?? 'uk');
    $total = 0.0;
    foreach (hs_user_plan_services($user, $lang) as $svc) {
        $total += hs_plan_service_period_nok($svc, $months, $chargeYearlyOnActivate);
    }

    return round($total, 2);
}

/** Hosting checkout subtotal: plan + selected add-ons (first billing period). */
function hs_checkout_hosting_subtotal_nok(array $user): float
{
    $planId = (string) ($user['plan'] ?? 'starter');
    $plan = hs_plan($planId);
    $planNok = (float) ($plan['price_nok'] ?? 0);
    if ($planNok <= 0) {
        $planNok = hs_plan_addon_unit_nok($plan);
    }

    return round($planNok + hs_subscription_services_total_nok($user, 1, true), 2);
}

/**
 * Annual subscription breakdown (plan + selected services) for panel display.
 *
 * @param array<string, mixed> $user
 * @return array{lines:list<array{label:string,eur:float,period:string}>,total_eur:float}
 */
function hs_plan_subscription_breakdown(array $user, string $lang, array $t): array
{
    $planId = (string) ($user['plan'] ?? 'starter');
    $plan = hs_plan($planId);
    $planEur = (float) ($plan['price_eur'] ?? 0);
    $lines = [[
        'label' => ($t['plan_billing_hosting_domain'] ?? 'Hosting + domain') . ' — ' . hs_plan_hosting_label($planId, $t),
        'eur' => $planEur,
        'period' => hs_plan_billing_period($plan),
    ]];
    foreach (hs_user_plan_services($user, $lang) as $svc) {
        $lines[] = [
            'label' => hs_plan_addon_label($svc, $lang, $t),
            'eur' => (float) ($svc['price_eur'] ?? 0),
            'period' => (string) (($svc['billing_period'] ?? 'month') === 'year' ? 'year' : 'month'),
        ];
    }
    $total = 0.0;
    foreach ($lines as $line) {
        $total += (float) ($line['eur'] ?? 0);
    }

    return ['lines' => $lines, 'total_eur' => round($total, 2)];
}

function hs_plan_service_price_label(array $svc, string $lang, array $t = []): string
{
    $period = (($svc['billing_period'] ?? 'month') === 'year')
        ? ('/' . ($t['plan_period_year_short'] ?? 'yr'))
        : ('/' . ($t['plan_period_month_short'] ?? 'mo'));
    $eur = (float) ($svc['price_eur'] ?? 0);
    if ($eur > 0) {
        return hs_format_eur_price($eur, $lang, $period);
    }
    $nok = (float) ($svc['price_nok'] ?? 0);
    if ($nok > 0) {
        return hs_format_nok_price($nok, $lang) . $period;
    }

    return '';
}

/** @param list<string> $requested */
function hs_plan_services_normalize_ids(array $requested): array
{
    $map = hs_plan_addon_catalog_map(true);
    $out = [];
    foreach ($requested as $id) {
        $sid = (string) $id;
        if ($sid !== '' && isset($map[$sid])) {
            $out[] = $sid;
        }
    }

    return array_values(array_unique($out));
}

/** @param array<string, mixed> $user */
function hs_plan_services_apply(array $user, array $requestedIds, string $lang): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'no_user'];
    }
    $t = function_exists('hs_support_panel_strings') ? hs_support_panel_strings($lang) : [];
    $newIds = hs_plan_services_normalize_ids($requestedIds);
    $oldIds = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];
    $oldIds = array_values(array_unique(array_map('strval', $oldIds)));
    sort($oldIds);
    $sortedNew = $newIds;
    sort($sortedNew);
    if ($oldIds === $sortedNew) {
        return ['ok' => true, 'unchanged' => true, 'services' => $newIds];
    }

    $added = array_values(array_diff($newIds, $oldIds));
    $removed = array_values(array_diff($oldIds, $newIds));
    $map = hs_plan_addon_catalog_map(true);

    $saved = hs_user_update($userId, static function (array &$u) use ($newIds): void {
        $u['plan_services'] = $newIds;
    });
    if (!$saved) {
        return ['ok' => false, 'error' => 'save'];
    }

    hs_user_settings_save($userId, [
        'perf_ai_enabled' => in_array('api_ai', $newIds, true),
    ]);

    // One pending invoice per newly added service (not a bulk multi-line invoice).
    $invoices = [];
    if ($added !== []) {
        require_once __DIR__ . '/invoices.php';
        $fresh = hs_user_by_id($userId) ?? $user;
        foreach ($added as $sid) {
            $svc = $map[$sid] ?? null;
            if (!is_array($svc)) {
                continue;
            }
            $priceNok = hs_plan_addon_unit_nok($svc);
            $label = hs_plan_addon_label($svc, $lang, $t);
            $lines = [[
                'desc' => ($t['invoice_line_service'] ?? 'Add-on service') . ': ' . $label,
                'qty' => 1,
                'unit_nok' => $priceNok,
            ]];
            $inv = hs_invoice_create($fresh, 'services', $lines, $lang, 'pending', [
                'event' => 'service_added',
                'service_id' => $sid,
                'service_label' => $label,
                'services_added' => [$sid],
                'services_removed' => $removed,
            ]);
            if (is_array($inv) && (string) ($inv['id'] ?? '') !== '') {
                $invoices[] = [
                    'id' => (string) ($inv['id'] ?? ''),
                    'number' => (string) ($inv['number'] ?? ''),
                    'service_id' => $sid,
                    'service_label' => $label,
                ];
            }
        }
    }

    $first = $invoices[0] ?? null;

    return [
        'ok' => true,
        'services' => $newIds,
        'added' => $added,
        'removed' => $removed,
        'invoices' => $invoices,
        // Legacy single fields (first invoice) for older clients
        'invoice_id' => is_array($first) ? (string) ($first['id'] ?? '') : '',
        'invoice_number' => is_array($first) ? (string) ($first['number'] ?? '') : '',
    ];
}

/** @param array<string, mixed> $user */
function hs_plan_services_catalog_for_panel(array $user, string $lang, array $t): array
{
    $selected = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];
    $selected = array_flip(array_map('strval', $selected));
    $items = [];
    foreach (hs_plan_addon_catalog_list(true) as $svc) {
        $id = (string) ($svc['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $items[] = [
            'id' => $id,
            'label' => hs_plan_addon_label($svc, $lang, $t),
            'desc' => hs_plan_addon_desc($svc, $lang, $t),
            'icon' => (string) ($svc['icon'] ?? 'fa-puzzle-piece'),
            'price' => hs_plan_service_price_label($svc, $lang, $t),
            'checked' => isset($selected[$id]),
        ];
    }

    return $items;
}

/** @param list<string> $selected */
function hs_render_register_addon_checkboxes(array $t, string $lang, array $selected = []): string
{
    $addons = hs_plan_addon_catalog_list(true);
    if ($addons === []) {
        return '';
    }
    $selectedFlip = array_flip(array_map('strval', $selected));
    $items = '';
    foreach ($addons as $addon) {
        $sid = (string) ($addon['id'] ?? '');
        if ($sid === '') {
            continue;
        }
        $checked = isset($selectedFlip[$sid]) ? ' checked' : '';
        $price = hs_plan_service_price_label($addon, $lang, $t);
        $addonNok = hs_plan_service_period_nok($addon, 1, true);
        $items .= '<label class="hs-plan-service-check hs-reg-addon-check">'
            . '<input type="checkbox" name="plan_services[]" value="' . hs_h($sid) . '"' . $checked
            . ' data-price-nok="' . hs_h((string) round($addonNok, 2)) . '">'
            . '<span class="hs-plan-service-check-box" aria-hidden="true"><i class="fa-solid fa-check"></i></span>'
            . '<span class="hs-plan-service-check-body">'
            . '<span class="hs-plan-service-check-head">'
            . '<i class="fa-solid ' . hs_h((string) ($addon['icon'] ?? 'fa-puzzle-piece')) . '"></i>'
            . '<strong>' . hs_h(hs_plan_addon_label($addon, $lang, $t)) . '</strong>'
            . ($price !== '' ? '<em>+' . hs_h($price) . '</em>' : '')
            . '</span>'
            . '<span class="hs-plan-service-check-desc">' . hs_h(hs_plan_addon_desc($addon, $lang, $t)) . '</span>'
            . '</span></label>';
    }

    return '<div class="hs-reg-addons" data-reg-addons>'
        . '<h3 class="hs-reg-subtitle"><i class="fa-solid fa-puzzle-piece"></i> '
        . hs_h($t['register_addons_title'] ?? 'Add to your plan') . '</h3>'
        . '<p class="hp-muted hs-reg-addons-hint">' . hs_h($t['register_addons_hint'] ?? '') . '</p>'
        . '<div class="hs-plan-services-checks">' . $items . '</div></div>';
}