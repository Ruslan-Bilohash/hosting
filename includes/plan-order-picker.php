<?php
declare(strict_types=1);

require_once __DIR__ . '/order-types.php';
require_once __DIR__ . '/plan-services.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/panel-ui.php';
require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/domain-cart.php';
require_once __DIR__ . '/invoices.php';

/**
 * @param array<string, mixed> $user
 * @param array<string, mixed> $query
 * @param array<string, mixed>|null $post
 * @return array{orderType:string,planId:string,selectedAddons:list<string>}
 */
function hs_panel_order_form_state(array $user, array $query = [], ?array $post = null): array
{
    // Prefer domains already on the account + session cart (domain search → activate).
    $pendingDomains = function_exists('hs_user_pending_domains_with_session')
        ? hs_user_pending_domains_with_session($user)
        : hs_user_pending_domains($user);
    $orderType = hs_user_order_type($user);
    $planId = (string) ($user['plan'] ?? 'starter');
    $selectedAddons = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];

    if ($post !== null) {
        $orderType = hs_order_type_normalize((string) ($post['order_type'] ?? $orderType));
        $planId = hs_order_plan_for_type($orderType, (string) ($post['plan'] ?? $planId));
        $selectedAddons = hs_plan_services_normalize_ids(is_array($post['plan_services'] ?? null) ? $post['plan_services'] : []);
        // Domain typed on activate form (domain-only / bundle without leaving the page).
        $typed = trim((string) ($post['domain_to_add'] ?? ''));
        if ($typed !== '') {
            $norm = hs_domain_normalize($typed);
            if ($norm !== null && !in_array($norm, $pendingDomains, true)) {
                $pendingDomains[] = $norm;
            }
        }
    } else {
        $qPlan = trim((string) ($query['plan'] ?? ''));
        $qOrder = trim((string) ($query['order'] ?? $query['order_type'] ?? ''));
        if ($qOrder !== '') {
            $orderType = hs_order_type_normalize($qOrder);
        } elseif ($planId === 'domain') {
            $orderType = 'domain';
        } elseif ($pendingDomains !== []) {
            $orderType = 'bundle';
        }
        if ($qPlan !== '' && $qPlan !== 'free') {
            if (hs_plan_is_managed_service($qPlan)) {
                $selectedAddons[] = $qPlan;
                $plans = hs_plans_for_register();
                $planId = array_key_first($plans) !== null ? (string) array_key_first($plans) : 'starter';
            } elseif (hs_plan_id_valid($qPlan)) {
                $planId = $qPlan;
            }
        }
        $qAddon = trim((string) ($query['addon'] ?? ''));
        if ($qAddon !== '' && isset(hs_plan_addon_catalog_map(true)[$qAddon])) {
            $selectedAddons[] = $qAddon;
        }
        $selectedAddons = hs_plan_services_normalize_ids($selectedAddons);
        $planId = hs_order_plan_for_type($orderType, $planId);
        if ($orderType === 'domain') {
            $planId = 'domain';
        } elseif (!hs_plan_id_valid($planId) || $planId === 'domain') {
            $planId = 'starter';
        }
        if (hs_plan_normalize_id($planId) === 'free') {
            $planId = 'starter';
        }
    }

    return [
        'orderType' => $orderType,
        'planId' => $planId,
        'selectedAddons' => $selectedAddons,
    ];
}

/**
 * @param array<string, mixed> $user
 * @param array<string, mixed> $post
 * @return array{ok:bool,error?:string,user?:array}
 */
function hs_panel_order_save_from_post(array $user, array $post, string $lang): array
{
    if (!hs_user_subscription_pending($user)) {
        return ['ok' => false, 'error' => 'not_pending'];
    }
    if (!hs_csrf_verify($post['csrf'] ?? null)) {
        return ['ok' => false, 'error' => 'csrf'];
    }

    $state = hs_panel_order_form_state($user, [], $post);
    $orderType = $state['orderType'];
    $planId = $state['planId'];
    $planServices = $state['selectedAddons'];

    // Pull domains from session cart + optional form field into the user account.
    $extra = [];
    $typed = trim((string) ($post['domain_to_add'] ?? ''));
    if ($typed !== '') {
        $norm = hs_domain_normalize($typed);
        if ($norm === null) {
            return ['ok' => false, 'error' => 'invalid_domain'];
        }
        $extra[] = $norm;
    }
    if (function_exists('hs_user_sync_pending_domains')) {
        $user = hs_user_sync_pending_domains($user, $extra);
    }
    $pendingDomains = hs_user_pending_domains($user);

    if (($orderType === 'domain' || $orderType === 'bundle') && $pendingDomains === []) {
        return ['ok' => false, 'error' => 'domain_required'];
    }
    if ($orderType !== 'domain' && !hs_plan_id_valid($planId)) {
        return ['ok' => false, 'error' => 'invalid_plan'];
    }

    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'no_user'];
    }

    $oldServices = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];
    $saved = hs_user_update($userId, static function (array &$u) use ($orderType, $planId, $planServices, $pendingDomains): void {
        $u['order_type'] = $orderType;
        $u['plan'] = $planId;
        $u['plan_services'] = $orderType === 'domain' ? [] : $planServices;
        // Keep domain cart on the user so payment sees them.
        if ($pendingDomains !== []) {
            $u['pending_domains'] = $pendingDomains;
            $u['pending_domain'] = $pendingDomains[0];
        }
        if ($orderType === 'domain') {
            $u['plan'] = 'domain';
        }
    });
    if (!$saved) {
        return ['ok' => false, 'error' => 'save'];
    }

    hs_user_settings_save($userId, [
        'perf_ai_enabled' => in_array('api_ai', $planServices, true),
    ]);

    $fresh = hs_user_by_id($userId) ?? $user;
    // Rebuild pending invoices so totals match the new cart (plan/addons/domains).
    require_once __DIR__ . '/invoices.php';
    if (function_exists('hs_invoice_rebuild_pending_checkout')) {
        hs_invoice_rebuild_pending_checkout($fresh, $lang);
    } else {
        hs_invoice_ensure_pending_checkout($fresh, $lang);
    }
    unset($oldServices);

    return ['ok' => true, 'user' => hs_user_by_id($userId) ?? $fresh];
}

/**
 * Reset activate cart: domains, add-ons, session domain cart; plan → starter hosting.
 *
 * @param array<string, mixed> $user
 * @return array{ok:bool,error?:string,user?:array}
 */
function hs_panel_order_reset_cart(array $user, string $lang = 'en'): array
{
    if (!hs_user_subscription_pending($user)) {
        return ['ok' => false, 'error' => 'not_pending'];
    }
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'no_user'];
    }

    require_once __DIR__ . '/domain-cart.php';
    hs_domain_cart_clear();

    $saved = hs_user_update($userId, static function (array &$u): void {
        $u['order_type'] = 'hosting';
        $u['plan'] = 'starter';
        $u['plan_services'] = [];
        $u['pending_domain'] = '';
        $u['pending_domains'] = [];
    });
    if (!$saved) {
        return ['ok' => false, 'error' => 'save'];
    }

    hs_user_settings_save($userId, ['perf_ai_enabled' => false]);

    $fresh = hs_user_by_id($userId) ?? $user;
    require_once __DIR__ . '/invoices.php';
    if (function_exists('hs_invoice_rebuild_pending_checkout')) {
        hs_invoice_rebuild_pending_checkout($fresh, $lang);
    }

    return ['ok' => true, 'user' => hs_user_by_id($userId) ?? $fresh];
}

/**
 * Remove one domain from the pending cart and rebuild totals.
 *
 * @param array<string, mixed> $user
 * @return array{ok:bool,error?:string,user?:array}
 */
function hs_panel_order_remove_domain(array $user, string $domain, string $lang = 'en'): array
{
    if (!hs_user_subscription_pending($user)) {
        return ['ok' => false, 'error' => 'not_pending'];
    }
    $userId = (string) ($user['id'] ?? '');
    $domain = hs_domain_normalize($domain) ?? '';
    if ($userId === '' || $domain === '') {
        return ['ok' => false, 'error' => 'invalid'];
    }

    $current = hs_user_pending_domains($user);
    $left = array_values(array_filter($current, static fn(string $d): bool => $d !== $domain));
    if (count($left) === count($current)) {
        return ['ok' => false, 'error' => 'not_found'];
    }

    require_once __DIR__ . '/domain-cart.php';
    $sess = hs_domain_cart_list();
    $sessLeft = array_values(array_filter($sess, static fn(string $d): bool => $d !== $domain));
    hs_domain_cart_set($sessLeft);

    $orderType = hs_user_order_type($user);
    $planId = (string) ($user['plan'] ?? 'starter');
    if ($left === []) {
        if ($orderType === 'domain' || $orderType === 'bundle') {
            $orderType = 'hosting';
        }
        if ($planId === 'domain') {
            $planId = 'starter';
        }
    }

    $saved = hs_user_update($userId, static function (array &$u) use ($left, $orderType, $planId): void {
        $u['pending_domains'] = $left;
        $u['pending_domain'] = $left[0] ?? '';
        $u['order_type'] = $orderType;
        $u['plan'] = $planId;
    });
    if (!$saved) {
        return ['ok' => false, 'error' => 'save'];
    }

    $fresh = hs_user_by_id($userId) ?? $user;
    require_once __DIR__ . '/invoices.php';
    if (function_exists('hs_invoice_rebuild_pending_checkout')) {
        hs_invoice_rebuild_pending_checkout($fresh, $lang);
    }

    return ['ok' => true, 'user' => hs_user_by_id($userId) ?? $fresh];
}

/** @param array<string, array<string, mixed>> $plans */
function hs_render_panel_order_plan_cards(array $plans, array $t, string $lang, string $defaultPlan, string $defaultOrder): string
{
    $html = '';
    foreach ($plans as $pid => $plan) {
        $desc = (string) ($t[hs_plan_desc_key($pid)] ?? '');
        $badges = '';
        if (($plan['badge'] ?? '') === 'popular') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-popular">' . hs_h($t['plan_popular'] ?? '') . '</span>';
        }
        if ((int) ($plan['discount_pct'] ?? 0) > 0) {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-sale">' . hs_h($t['plan_discount_now'] ?? '') . '</span>';
        }
        if (($plan['badge'] ?? '') === 'vps') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-vps">' . hs_h($t['plan_vps_badge'] ?? 'VPS') . '</span>';
        }
        $planNok = 0.0;
        if (function_exists('hs_plan_addon_unit_nok')) {
            $planNok = hs_plan_addon_unit_nok($plan);
        } else {
            $planNok = (float) ($plan['price_nok'] ?? 0);
        }
        $html .= '<label class="hs-plan" data-hs-plan-card data-price-nok="' . hs_h((string) round($planNok, 2)) . '">'
            . '<input type="radio" name="plan" value="' . hs_h($pid) . '"'
            . ' data-price-nok="' . hs_h((string) round($planNok, 2)) . '"'
            . (($pid === $defaultPlan && $defaultOrder !== 'domain') ? ' checked' : '') . '>'
            . '<span class="hs-choice-check" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>';
        if ($badges !== '') {
            $html .= '<div class="hs-plan-badges">' . $badges . '</div>';
        }
        $html .= '<h3>' . hs_h($t['plan_' . $pid] ?? $pid) . '</h3>';
        if ($desc !== '') {
            $html .= '<p class="hs-plan-desc">' . hs_h($desc) . '</p>';
        }
        $html .= hs_render_plan_price_block($plan, $t, $lang)
            . '<ul class="hs-plan-features">';
        foreach (hs_plan_feature_lines($plan, $t) as $line) {
            $html .= '<li><i class="fa-solid fa-check"></i> ' . hs_h($line) . '</li>';
        }
        $html .= '</ul></label>';
    }

    return $html;
}

/**
 * @param array<string, mixed> $user
 * @param array{orderType:string,planId:string,selectedAddons:list<string>} $state
 */
function hs_render_panel_order_picker(array $user, array $t, string $lang, array $state, array $opts = []): string
{
    $pendingDomains = hs_user_pending_domains($user);
    $defaultOrder = $state['orderType'];
    $defaultPlan = $state['planId'];
    $selectedAddons = $state['selectedAddons'];
    $plans = hs_plans_for_register();
    $context = (string) ($opts['context'] ?? 'activate');
    $action = (string) ($opts['action'] ?? '');
    $showDomainOnly = $pendingDomains !== [] || $defaultOrder === 'domain';

    // Hidden actions form (reset / remove domain) — shares CSRF with main form via duplicate field
    $html = '<form method="post" id="hs-panel-order-actions" class="hs-panel-order-actions-form" hidden>'
        . hs_csrf_field()
        . '</form>';

    $html .= '<form method="post" class="hs-panel-order-form" data-hs-panel-order-form';
    if ($action !== '') {
        $html .= ' action="' . hs_h($action) . '"';
    }
    $html .= ' data-default-order="' . hs_h($defaultOrder) . '" data-lang="' . hs_h($lang) . '">'
        . hs_csrf_field()
        . '<input type="hidden" name="save_panel_order" value="1">';

    $html .= '<div class="hs-panel-order-head" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-start;justify-content:space-between;margin-bottom:1rem">'
        . '<div>'
        . '<h3 class="hs-reg-panel-title" style="margin:0 0 .35rem"><i class="fa-solid fa-cart-shopping"></i> '
        . hs_h($t['panel_order_picker_title'] ?? $t['register_step_order'] ?? 'Your order') . '</h3>'
        . '<p class="hp-muted hs-reg-panel-hint" style="margin:0">'
        . hs_h($t['panel_order_picker_hint'] ?? $t['register_step_order_hint'] ?? '') . '</p>'
        . '</div>'
        . '<div class="hs-panel-order-cart-actions" style="display:flex;flex-wrap:wrap;gap:.5rem">'
        . '<a class="hs-btn hs-btn-ghost hs-btn-sm" href="' . hs_h(hs_url('domain')) . '">'
        . '<i class="fa-solid fa-plus"></i> ' . hs_h($t['panel_order_add_domain'] ?? 'Add domain') . '</a>'
        . '<button type="submit" form="hs-panel-order-actions" name="reset_panel_order" value="1" class="hs-btn hs-btn-ghost hs-btn-sm" data-confirm="'
        . hs_h($t['panel_order_reset_confirm'] ?? 'Clear cart and start a new order?') . '">'
        . '<i class="fa-solid fa-trash-can"></i> ' . hs_h($t['panel_order_reset'] ?? 'Clear cart') . '</button>'
        . '</div></div>';

    $html .= '<div class="hs-order-type-cards" role="radiogroup" aria-label="'
        . hs_h($t['register_step_order'] ?? 'Order type') . '" style="margin-bottom:1.25rem">';
    $types = [
        'hosting' => ['fa-server', $t['order_type_hosting'] ?? 'Hosting only', $t['order_type_hosting_desc'] ?? ''],
        'domain' => ['fa-globe', $t['order_type_domain'] ?? 'Domain only', $t['order_type_domain_desc'] ?? ''],
        'bundle' => ['fa-box', $t['order_type_bundle'] ?? 'Hosting + domain', $t['order_type_bundle_desc'] ?? ''],
    ];
    foreach ($types as $typeId => $meta) {
        // Always allow picking order type; domain/bundle need domains (prompt via note if empty).
        $checked = $defaultOrder === $typeId ? ' checked' : '';
        $html .= '<label class="hs-order-choice" data-order-type-card>'
            . '<input type="radio" name="order_type" value="' . hs_h($typeId) . '"' . $checked . '>'
            . '<span class="hs-choice-check" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>'
            . '<span class="hs-order-choice-icon"><i class="fa-solid ' . hs_h($meta[0]) . '"></i></span>'
            . '<span class="hs-order-choice-name">' . hs_h($meta[1]) . '</span>'
            . '<span class="hs-order-choice-desc">' . hs_h($meta[2]) . '</span></label>';
    }
    $html .= '</div>';

    if ($pendingDomains === [] && ($defaultOrder === 'domain' || $defaultOrder === 'bundle')) {
        $html .= '<div class="hs-alert" style="margin-bottom:1rem">'
            . hs_h($t['panel_order_need_domain'] ?? 'Add a domain to the cart first, then update the order.')
            . ' <a href="' . hs_h(hs_url('domain')) . '">' . hs_h($t['panel_order_add_domain'] ?? 'Add domain') . '</a>'
            . '</div>';
    }

    $html .= '<input type="hidden" name="plan" id="plan-domain-only" value="domain" disabled>'
        . '<div class="hs-order-domain-only-note hs-alert hs-alert-success" data-order-domain-note'
        . ($defaultOrder === 'domain' ? '' : ' hidden') . '>'
        . hs_h($t['order_type_domain_note'] ?? '') . '</div>';

    // Always show domain attach field for domain-only / bundle (or when cart empty).
    $showDomainInput = $defaultOrder === 'domain' || $defaultOrder === 'bundle' || $pendingDomains === [];
    $html .= '<div class="hs-panel-order-domain-add" data-order-domain-add style="margin-bottom:1rem'
        . ($showDomainInput ? '' : ';display:none') . '">'
        . '<label class="hs-field" style="margin:0">'
        . '<span style="display:block;font-size:.82rem;font-weight:700;margin-bottom:.35rem">'
        . hs_h($t['panel_order_domain_input_label'] ?? 'Domain to register') . '</span>'
        . '<input type="text" name="domain_to_add" value="" placeholder="'
        . hs_h($t['panel_order_domain_input_ph'] ?? 'example.com') . '" '
        . 'autocomplete="off" spellcheck="false" autocapitalize="none" inputmode="url" '
        . 'style="width:100%;max-width:28rem;padding:.7rem .85rem;border:1px solid var(--hs-border,#d8ebe2);border-radius:10px;font:inherit">'
        . '<span class="hp-muted" style="display:block;margin-top:.35rem;font-size:.8rem">'
        . hs_h($t['panel_order_domain_input_hint'] ?? 'Enter a full domain (e.g. mybrand.shop). Or search domains and return here.')
        . '</span></label></div>';

    $html .= '<div class="hs-plans hs-plans-register" data-order-plans-wrap'
        . ($defaultOrder === 'domain' ? ' hidden' : '') . '>'
        . '<h4 class="hs-reg-subtitle" style="margin:0 0 .75rem"><i class="fa-solid fa-layer-group"></i> '
        . hs_h($t['register_step_plan'] ?? 'Plan') . '</h4>'
        . hs_render_panel_order_plan_cards($plans, $t, $lang, $defaultPlan, $defaultOrder)
        . '</div>';

    $html .= '<div data-reg-addons-wrap' . ($defaultOrder === 'domain' ? ' hidden' : '') . '>'
        . hs_render_register_addon_checkboxes($t, $lang, $selectedAddons)
        . '</div>';

    if ($pendingDomains !== []) {
        require_once __DIR__ . '/domain-cart.php';
        $html .= '<div class="hs-panel-order-domains" style="margin-top:1rem">'
            . hs_render_domain_cart_picks($t, $pendingDomains, [
                'lang' => $lang,
                'removable' => $context === 'activate',
            ])
            . '</div>';
    } else {
        $html .= '<div class="hs-panel-order-domains-empty" style="margin-top:1rem">'
            . '<p class="hp-muted" style="margin:0 0 .5rem">' . hs_h($t['panel_order_no_domains'] ?? 'No domains in cart yet.') . '</p>'
            . '<a class="hs-btn hs-btn-ghost hs-btn-sm" href="' . hs_h(hs_url('domain')) . '">'
            . '<i class="fa-solid fa-magnifying-glass"></i> ' . hs_h($t['panel_order_search_domain'] ?? 'Search domains') . '</a>'
            . '</div>';
    }

    // Live estimate box (updated by JS from plan/addon prices in the form)
    $html .= '<div class="hs-panel-order-estimate" data-hs-order-estimate style="margin-top:1.15rem;padding:1rem 1.1rem;border:1px solid var(--hs-border,#e2e8f0);border-radius:12px;background:var(--hs-accent-soft,#ecfdf5)">'
        . '<p class="hp-muted" style="margin:0 0 .35rem;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em">'
        . hs_h($t['panel_order_estimate'] ?? 'Estimated total') . '</p>'
        . '<p style="margin:0;font-size:1.25rem;font-weight:800;color:var(--hs-accent,#059669)" data-hs-order-estimate-total>—</p>'
        . '<p class="hp-muted" style="margin:.35rem 0 0;font-size:.8rem" data-hs-order-estimate-note>'
        . hs_h($t['panel_order_estimate_note'] ?? 'Click “Update order & total” to recalculate invoices and payment.')
        . '</p></div>';

    $btnLabel = $context === 'plan'
        ? ($t['panel_order_save_plan'] ?? $t['plan_services_save'] ?? 'Save')
        : ($t['panel_order_update_checkout'] ?? 'Update order & total');

    $html .= '<div class="hs-panel-order-foot" style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:.65rem;align-items:center">'
        . '<button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-calculator"></i> ' . hs_h($btnLabel) . '</button>';
    if ($context === 'plan') {
        $html .= '<a href="' . hs_h(hs_url(hs_panel_path('activate.php'))) . '" class="hs-btn hs-btn-ghost">'
            . '<i class="fa-solid fa-credit-card"></i> ' . hs_h($t['panel_activate_pay_btn'] ?? 'Pay now') . '</a>';
    }
    $html .= '</div></form>';

    return $html;
}