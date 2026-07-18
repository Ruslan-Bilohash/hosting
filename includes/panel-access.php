<?php
declare(strict_types=1);

/**
 * Client panel access rules: active hosting vs pending payment vs no plan.
 * Namecheap / Stellar / Nebula provision only when hosting is active.
 */

require_once __DIR__ . '/panel-ui.php';
require_once __DIR__ . '/plans.php';

/** Hosting paid and active. */
function hs_user_hosting_active(?array $user): bool
{
    if (!is_array($user) || $user === []) {
        return false;
    }
    $status = (string) ($user['subscription_status'] ?? '');

    return $status === 'active';
}

/** Suspended / cancelled / empty (not active and not pending payment). */
function hs_user_subscription_blocked(?array $user): bool
{
    if (!is_array($user)) {
        return true;
    }
    if (hs_user_hosting_active($user) || hs_user_subscription_pending($user)) {
        return false;
    }

    return true;
}

/** Has a plan id that is a real hosting plan (not only domain). */
function hs_user_has_hosting_plan(?array $user): bool
{
    if (!is_array($user)) {
        return false;
    }
    $planId = hs_plan_normalize_id((string) ($user['plan'] ?? ''));
    if ($planId === '' || $planId === 'domain') {
        return false;
    }

    return hs_plan_is_hosting($planId) || hs_plan_id_valid($planId);
}

/**
 * Features allowed without active hosting (browse + pay + account).
 *
 * @return list<string> panel_active keys / page slugs
 */
function hs_panel_allowed_without_hosting(): array
{
    return [
        'dashboard',
        'plan',
        'plan-renew',
        'activate',
        'account',
        'support',
        'site-support',
        'invoices',
        'domains', // search/buy still ok; bind needs pay
        'dom-register',
        'order',
    ];
}

/**
 * Whether this panel page should be fully usable.
 *
 * @param string $panelActive key from $panel_active (dashboard, plan, adv-ssh, …)
 */
function hs_panel_feature_unlocked(?array $user, string $panelActive): bool
{
    if (hs_user_hosting_active($user)) {
        return true;
    }
    $key = strtolower(trim($panelActive));
    // Domain tools (search / DNS view) stay open before pay
    if ($key === 'domains' || str_starts_with($key, 'dom-')) {
        return true;
    }
    $allowed = hs_panel_allowed_without_hosting();
    foreach ($allowed as $a) {
        if ($key === $a || str_starts_with($key, $a)) {
            return true;
        }
    }
    if (in_array($key, ['plan', 'account', 'support', 'site-support', 'invoices'], true)) {
        return true;
    }

    return false;
}

/**
 * HTML banner / full-page gate when plan is missing or unpaid.
 *
 * @param 'pending'|'blocked'|'none' $mode
 */
function hs_render_panel_plan_gate(array $t, string $mode = 'pending'): string
{
    if ($mode === 'pending') {
        $title = (string) ($t['panel_gate_pending_title'] ?? 'Activate your hosting plan');
        $lead = (string) ($t['panel_gate_pending_lead'] ?? 'Pay for your plan to unlock sites, files, databases, SSL, statistics and all tools.');
        $btn = (string) ($t['panel_activate_pay_btn'] ?? 'Pay now');
        $href = hs_url(hs_panel_path('activate.php'));
        $icon = 'fa-credit-card';
        $cls = 'hs-alert-warn';
    } else {
        $title = (string) ($t['panel_gate_no_plan_title'] ?? 'No active hosting plan');
        $lead = (string) ($t['panel_gate_no_plan_lead'] ?? 'Buy a hosting plan to get your website folder, domains, statistics and settings. Domains can be linked to your account folder after purchase.');
        $btn = (string) ($t['panel_gate_buy_btn'] ?? 'View plans & buy');
        $href = hs_url(hs_panel_path('plan.php'));
        // Prefer public pricing if plan page has no package
        if ($mode === 'none') {
            $href = hs_url('register.php');
            $btn = (string) ($t['panel_gate_register_btn'] ?? 'Choose a plan');
        }
        $icon = 'fa-layer-group';
        $cls = 'hs-alert-warn';
    }

    return '<div class="hs-panel-plan-gate ' . hs_h($cls) . '">'
        . '<div class="hs-panel-plan-gate-inner">'
        . '<i class="fa-solid ' . hs_h($icon) . '"></i>'
        . '<h2>' . hs_h($title) . '</h2>'
        . '<p class="hp-muted">' . hs_h($lead) . '</p>'
        . '<div class="hs-panel-plan-gate-actions">'
        . '<a class="hs-btn hs-btn-primary" href="' . hs_h($href) . '"><i class="fa-solid ' . hs_h($icon) . '"></i> ' . hs_h($btn) . '</a>'
        . '<a class="hs-btn hs-btn-ghost" href="' . hs_h(hs_url(hs_panel_path('plan.php'))) . '">'
        . hs_h($t['nav_plan_details'] ?? 'Plan details') . '</a>'
        . '<a class="hs-btn hs-btn-ghost" href="' . hs_h(hs_url('#pricing')) . '" target="_blank" rel="noopener">'
        . hs_h($t['nav_pricing'] ?? 'Pricing') . '</a>'
        . '</div></div></div>';
}

/**
 * If feature locked, return gate HTML; otherwise null (page continues).
 */
function hs_panel_gate_or_null(?array $user, array $t, string $panelActive): ?string
{
    if (hs_panel_feature_unlocked($user, $panelActive)) {
        return null;
    }
    if (hs_user_subscription_pending($user)) {
        return hs_render_panel_plan_gate($t, 'pending');
    }
    if (!hs_user_has_hosting_plan($user)) {
        return hs_render_panel_plan_gate($t, 'none');
    }

    return hs_render_panel_plan_gate($t, 'blocked');
}

/**
 * Restrict sidebar for unpaid / no-plan users.
 *
 * @param list<array<string, mixed>> $groups
 * @return list<array<string, mixed>>
 */
function hs_panel_nav_filter_no_hosting(array $groups): array
{
    $allowedKeys = [
        'dashboard', 'account', 'plan', 'plan-renew', 'invoices',
        'domains', 'dom-dns', 'dom-register', 'dom-contacts', 'support', 'site-support', 'webmail',
    ];
    $allowedSlugs = ['plan', 'domains', 'tools'];
    $out = [];
    foreach ($groups as $group) {
        $type = (string) ($group['type'] ?? '');
        if ($type === 'item') {
            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                $key = (string) ($item['key'] ?? '');
                if (in_array($key, $allowedKeys, true)) {
                    $items[] = $item;
                }
            }
            if ($items !== []) {
                $group['items'] = $items;
                $out[] = $group;
            }
            continue;
        }
        if ($type !== 'group') {
            continue;
        }
        $slug = (string) ($group['slug'] ?? '');
        if (!in_array($slug, $allowedSlugs, true) && $slug !== 'plan') {
            // keep support-like under tools if only webmail
            if ($slug !== 'tools') {
                continue;
            }
        }
        $items = [];
        foreach ($group['items'] ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if (in_array($key, $allowedKeys, true) || str_starts_with($key, 'dom-') || $key === 'plan' || $key === 'plan-renew' || $key === 'invoices' || $key === 'webmail' || $key === 'support') {
                $items[] = $item;
            }
        }
        if ($items !== []) {
            $group['items'] = $items;
            $out[] = $group;
        }
    }

    return $out;
}

/** Ensure domain folder exists under client public_html when binding. */
function hs_domain_ensure_client_folder(array $user, string $domain): bool
{
    require_once __DIR__ . '/domain-workspace.php';
    if (function_exists('hs_domain_ensure_workspace')) {
        return (bool) hs_domain_ensure_workspace($user, $domain);
    }
    $path = hs_domain_docroot_path($user, $domain);
    if (!is_dir($path)) {
        return @mkdir($path, 0755, true);
    }

    return true;
}
