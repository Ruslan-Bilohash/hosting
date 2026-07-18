<?php
declare(strict_types=1);

require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/payment-fulfill.php';
require_once __DIR__ . '/coupons.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/domain-store.php';
require_once __DIR__ . '/domain-cart.php';
require_once __DIR__ . '/order-types.php';
require_once __DIR__ . '/platform-free-hosting.php';
require_once __DIR__ . '/plan-services.php';
require_once __DIR__ . '/invoices.php';

/** @return array<string, mixed>|null */
function hs_panel_checkout_active_coupon_row(bool $wantHosting, bool $wantDomain, ?string $domainTld): ?array
{
    if (!function_exists('hs_coupons_enabled') || !hs_coupons_enabled()) {
        if (function_exists('hs_coupon_session_clear')) {
            hs_coupon_session_clear();
        }
        return null;
    }
    $sessionCoupon = hs_coupon_session_get();
    if ($sessionCoupon === null) {
        return null;
    }
    $valid = hs_coupon_validate((string) ($sessionCoupon['code'] ?? ''), $wantHosting, $wantDomain, $domainTld);
    return $valid['ok'] ? $valid['coupon'] : null;
}

/**
 * @param array<string, mixed> $user
 * @return array{
 *   orderType:string,planId:string,pendingDomain:string,wantHosting:bool,wantDomain:bool,
 *   domainTld:?string,planNok:float,domainEur:float,priced:array,amounts:array,
 *   stripeEnabled:bool,paypalEnabled:bool,simulatedAllowed:bool,paymentBlocked:bool,
 *   activeCoupon:?array,checkoutTitle:string,checkoutPayLabel:string,testMode:bool
 * }
 */
function hs_panel_checkout_context(array $user, array $t, string $lang): array
{
    // Session domain cart → pending user so domain-only totals/payment see the domain.
    if ((string) ($user['subscription_status'] ?? '') === 'pending' && function_exists('hs_user_sync_pending_domains')) {
        $user = hs_user_sync_pending_domains($user);
    }

    // Active client paying for domain(s) only — never re-seed hosting plan invoices.
    $panelDomainPurchase = function_exists('hs_panel_is_domain_purchase_mode')
        && hs_panel_is_domain_purchase_mode($user)
        && hs_user_pending_domains($user) !== [];

    if (!$panelDomainPurchase) {
        // Always refresh pending invoices so add-ons appear before payment
        try {
            require_once __DIR__ . '/invoices.php';
            if (function_exists('hs_invoice_ensure_pending_checkout')) {
                hs_invoice_ensure_pending_checkout($user, $lang);
            }
            $fresh = hs_user_by_id((string) ($user['id'] ?? ''));
            if (is_array($fresh)) {
                $user = $fresh;
            }
        } catch (Throwable) {
            // Never break checkout/plan UI if invoice seed fails
        }
    }

    $orderType = $panelDomainPurchase ? 'domain' : hs_user_order_type($user);
    $planId = (string) ($user['plan'] ?? 'starter');
    $pendingDomains = hs_user_pending_domains($user);
    $pendingDomain = $pendingDomains[0] ?? '';
    $wantHosting = $panelDomainPurchase ? false : hs_order_includes_hosting($orderType);
    $wantDomain = $panelDomainPurchase
        ? ($pendingDomains !== [])
        : ((hs_order_includes_domain($user) || $orderType === 'domain' || $orderType === 'bundle')
            && $pendingDomains !== []);
    $domainTld = null;
    if ($pendingDomain !== '') {
        $parsed = hs_domain_parse($pendingDomain);
        $domainTld = is_array($parsed) ? (string) ($parsed['tld'] ?? '') : null;
    }
    $planNok = $wantHosting ? hs_checkout_hosting_subtotal_nok($user) : 0.0;
    // Domain purchase on active hosting: always full catalog price (never "included with plan").
    $domainEur = $wantDomain
        ? ($panelDomainPurchase ? hs_domains_total_price_eur($pendingDomains) : hs_checkout_domain_price_eur($user))
        : 0.0;

    $activeCoupon = hs_panel_checkout_active_coupon_row($wantHosting, $wantDomain, $domainTld);
    $priced = hs_coupon_apply($activeCoupon, $planNok, $domainEur, $domainTld);

    // Align priced hosting total with sum of pending invoices (plan + services) when available
    $pendingHostNok = 0.0;
    $pendingDomNok = 0.0;
    if (!$panelDomainPurchase) {
        try {
            if (function_exists('hs_invoices_for_user')) {
                foreach (hs_invoices_for_user((string) ($user['id'] ?? '')) as $inv) {
                    if ((string) ($inv['status'] ?? '') !== 'pending') {
                        continue;
                    }
                    $type = (string) ($inv['type'] ?? '');
                    $amt = (float) ($inv['total_nok'] ?? 0);
                    if ($type === 'domain') {
                        $pendingDomNok += $amt;
                    } else {
                        $pendingHostNok += $amt;
                    }
                }
            }
        } catch (Throwable) {
            $pendingHostNok = 0.0;
            $pendingDomNok = 0.0;
        }
        if ($pendingHostNok > 0) {
            $priced['hosting_nok'] = round($pendingHostNok, 2);
            $planNok = $pendingHostNok;
        }
        // Domain invoices stored in NOK — convert back to EUR for domain_eur field when needed
        if ($pendingDomNok > 0 && function_exists('hs_exchange_rates')) {
            $rates = hs_exchange_rates();
            $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
            $priced['domain_eur'] = round($pendingDomNok * $eurRate, 2);
            $domainEur = (float) $priced['domain_eur'];
        }
    }

    $amounts = hs_payment_checkout_amounts($priced, $wantHosting, $wantDomain);
    $checkoutTitleKey = $panelDomainPurchase ? 'panel_domain_pay_title' : ('checkout_title_' . $orderType);
    $checkoutPayKey = $panelDomainPurchase ? 'panel_domain_pay_btn' : ('checkout_pay_' . $orderType);

    return [
        'orderType' => $orderType,
        'planId' => $planId,
        'pendingDomain' => $pendingDomain,
        'pendingDomains' => $pendingDomains,
        'wantHosting' => $wantHosting,
        'wantDomain' => $wantDomain,
        'domainTld' => $domainTld,
        'planNok' => $planNok,
        'domainEur' => $domainEur,
        'priced' => $priced,
        'amounts' => $amounts,
        'stripeEnabled' => hs_payment_stripe_enabled(),
        'paypalEnabled' => hs_payment_paypal_enabled(),
        'simulatedAllowed' => hs_simulated_payment_allowed(),
        'paymentBlocked' => !hs_simulated_payment_allowed() && !hs_payment_stripe_enabled() && !hs_payment_paypal_enabled(),
        'activeCoupon' => $activeCoupon,
        'checkoutTitle' => (string) ($t[$checkoutTitleKey] ?? $t['checkout_title_domain'] ?? $t['checkout_title'] ?? 'Complete payment'),
        'checkoutPayLabel' => (string) ($t[$checkoutPayKey] ?? $t['checkout_pay_domain'] ?? $t['checkout_pay'] ?? 'Pay & activate'),
        'testMode' => hs_payment_is_test_mode(),
        'panelDomainPurchase' => $panelDomainPurchase,
    ];
}

/**
 * @param array<string, mixed> $user
 * @return array{error:string,couponMsg:string,redirect?:string}
 */
function hs_panel_checkout_handle_post(array $user, array $t, string $lang): array
{
    $ctx = hs_panel_checkout_context($user, $t, $lang);
    $error = '';
    $couponMsg = '';

    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        return ['error' => $t['register_error_csrf'] ?? '', 'couponMsg' => ''];
    }
    if (!isset($_POST['pay_checkout'])) {
        return ['error' => '', 'couponMsg' => ''];
    }

    if (hs_panel_checkout_is_free($ctx)) {
        $fulfill = hs_free_plan_activate_user($user, $lang);
        if ($fulfill['ok']) {
            return ['error' => '', 'couponMsg' => '', 'redirect' => hs_panel_path('')];
        }
        return ['error' => $t['payment_activate_failed'] ?? '', 'couponMsg' => ''];
    }

    $payMethod = (string) ($_POST['pay_method'] ?? '');
    if ($ctx['paymentBlocked']) {
        return ['error' => $t['checkout_payment_unavailable'] ?? '', 'couponMsg' => ''];
    }
    if ($payMethod === 'demo' && !$ctx['simulatedAllowed']) {
        return ['error' => $t['checkout_payment_unavailable'] ?? '', 'couponMsg' => ''];
    }
    if ($payMethod === 'stripe' && !$ctx['stripeEnabled']) {
        return ['error' => $t['checkout_stripe_unavailable'] ?? '', 'couponMsg' => ''];
    }
    if ($payMethod === 'paypal' && !$ctx['paypalEnabled']) {
        return ['error' => $t['checkout_paypal_unavailable'] ?? '', 'couponMsg' => ''];
    }
    if (!in_array($payMethod, ['demo', 'stripe', 'paypal'], true)) {
        return ['error' => $t['checkout_choose_method'] ?? '', 'couponMsg' => ''];
    }

    $couponRow = hs_panel_checkout_active_coupon_row($ctx['wantHosting'], $ctx['wantDomain'], $ctx['domainTld']);
    $priced = hs_coupon_apply($couponRow, $ctx['planNok'], $ctx['domainEur'], $ctx['domainTld']);

    $isDomainPurchase = !empty($ctx['panelDomainPurchase']);
    $fulfillMeta = [
        'lang' => $lang,
        'panel_domain_purchase' => $isDomainPurchase,
        'preserve_hosting' => $isDomainPurchase,
    ];

    if ($payMethod === 'demo') {
        $fulfill = hs_payment_fulfill_checkout($user, $priced, $couponRow, array_merge($fulfillMeta, [
            'payment_provider' => 'demo',
            'payment_ref' => 'demo_' . gmdate('YmdHis'),
        ]));
        if ($fulfill['ok']) {
            if ($isDomainPurchase && function_exists('hs_panel_domain_purchase_clear')) {
                hs_panel_domain_purchase_clear();
            }
            $redirect = $isDomainPurchase
                ? hs_panel_path('domains.php') . '?tab=overview&paid=1'
                : hs_panel_path('');

            return ['error' => '', 'couponMsg' => '', 'redirect' => $redirect];
        }
        return ['error' => $t['payment_activate_failed'] ?? '', 'couponMsg' => ''];
    }

    $pendingRes = hs_payment_create_pending(
        $user,
        $payMethod,
        $priced,
        $couponRow,
        $ctx['wantHosting'],
        $ctx['wantDomain'],
        $lang,
        [
            'panel_domain_purchase' => $isDomainPurchase,
            'preserve_hosting' => $isDomainPurchase,
            'order_type' => $isDomainPurchase ? 'domain' : null,
        ]
    );
    if (!$pendingRes['ok']) {
        return ['error' => $t['payment_create_failed'] ?? '', 'couponMsg' => ''];
    }

    $pending = $pendingRes['pending'];
    $description = hs_payment_checkout_description($user, $ctx['orderType'], $ctx['pendingDomain']);
    if ($payMethod === 'stripe') {
        $session = hs_stripe_create_checkout_session($pending, $description);
        if ($session['ok'] && !empty($session['url'])) {
            return ['error' => '', 'couponMsg' => '', 'redirect' => (string) $session['url']];
        }
        return ['error' => $t['payment_stripe_failed'] ?? '', 'couponMsg' => ''];
    }

    $order = hs_paypal_create_order($pending, $description);
    if ($order['ok'] && !empty($order['url'])) {
        return ['error' => '', 'couponMsg' => '', 'redirect' => (string) $order['url']];
    }
    return ['error' => $t['payment_paypal_failed'] ?? '', 'couponMsg' => ''];
}

/** @param array<string, mixed> $ctx */
function hs_panel_checkout_render(array $user, array $ctx, array $t, string $lang, string $error = '', bool $cancelled = false): string
{
    $priced = $ctx['priced'];
    $amounts = $ctx['amounts'];
    $html = '';

    if ($ctx['testMode'] && ($ctx['stripeEnabled'] || $ctx['paypalEnabled'])) {
        $testHints = [];
        if ($ctx['stripeEnabled']) {
            $testHints[] = (string) ($t['panel_checkout_test_card_hint'] ?? 'Use test card 4242 4242 4242 4242');
        }
        if ($ctx['paypalEnabled']) {
            $testHints[] = (string) ($t['panel_checkout_test_paypal_hint'] ?? 'PayPal sandbox — use a PayPal Developer test buyer account.');
        }
        $html .= '<div class="hs-alert" style="font-size:.85rem;padding:.5rem .75rem;margin-bottom:.75rem">'
            . '<i class="fa-solid fa-flask"></i> ' . hs_h($t['checkout_test_mode'] ?? 'Test mode')
            . '<br><span class="hp-muted" style="font-size:.8rem">' . hs_h(implode(' · ', $testHints)) . '</span></div>';
    }
    if ($cancelled) {
        $html .= '<div class="hs-alert">' . hs_h($t['checkout_cancelled'] ?? '') . '</div>';
    }
    if ($error !== '') {
        $html .= '<div class="hs-alert hs-alert-error">' . hs_h($error) . '</div>';
    }
    $html .= '<div class="hs-checkout-lines hp-card-body" style="padding:0;margin:0 0 1rem">';
    $html .= '<p class="hp-muted">' . hs_h($t['checkout_order_type'] ?? 'Order') . ': <strong>' . hs_h($t['order_type_' . $ctx['orderType']] ?? $ctx['orderType']) . '</strong></p>';
    if ($ctx['wantHosting']) {
        $planOnlyNok = 0.0;
        $plan = hs_plan((string) $ctx['planId']);
        $planOnlyNok = hs_plan_addon_unit_nok($plan);
        $html .= '<p class="hp-muted">' . hs_h($t['checkout_plan'] ?? 'Hosting') . ': <strong>'
            . hs_h($t['plan_' . $ctx['planId']] ?? $ctx['planId']) . '</strong> — <strong>'
            . hs_h(hs_format_nok_price($planOnlyNok, $lang)) . '</strong>'
            . hs_h($t['per_month'] ?? '/mo') . '</p>';

        // Always list each selected add-on with its price (from user.plan_services)
        require_once __DIR__ . '/plan-services.php';
        foreach (hs_user_plan_services($user, $lang) as $svc) {
            $amount = hs_plan_service_period_nok($svc, 1, true);
            if ($amount <= 0) {
                continue;
            }
            $period = (($svc['billing_period'] ?? 'month') === 'year')
                ? ($t['per_year'] ?? '/year')
                : ($t['per_month'] ?? '/mo');
            $html .= '<p class="hp-muted hs-checkout-addon-line" style="font-size:.9rem;margin:.35rem 0 0">'
                . '<i class="fa-solid fa-puzzle-piece" style="opacity:.7;margin-right:.35rem"></i>'
                . hs_h(hs_plan_addon_label($svc, $lang, $t))
                . ' — <strong>' . hs_h(hs_format_nok_price($amount, $lang)) . '</strong>'
                . '<span class="hp-muted" style="font-weight:500">' . hs_h($period) . '</span></p>';
        }

        // Pending invoices list (each service separate)
        try {
            if (function_exists('hs_invoices_for_user')) {
                $pendingInvs = array_values(array_filter(
                    hs_invoices_for_user((string) ($user['id'] ?? '')),
                    static fn(array $inv): bool => (string) ($inv['status'] ?? '') === 'pending'
                ));
                if ($pendingInvs !== []) {
                    $html .= '<div class="hs-checkout-pending-invoices" style="margin-top:.85rem;padding-top:.75rem;border-top:1px dashed var(--hs-border)">'
                        . '<p class="hp-muted" style="font-size:.82rem;margin:0 0 .4rem"><i class="fa-solid fa-file-invoice"></i> '
                        . hs_h($t['checkout_pending_invoices'] ?? 'Invoices (each item separate)') . '</p>';
                    foreach ($pendingInvs as $inv) {
                        $html .= '<p class="hp-muted" style="font-size:.85rem;margin:.2rem 0;display:flex;justify-content:space-between;gap:.75rem">'
                            . '<span>' . hs_h((string) ($inv['number'] ?? '')) . ' · '
                            . hs_h(function_exists('hs_invoice_type_label') ? hs_invoice_type_label($inv, $t) : (string) ($inv['type'] ?? '')) . '</span>'
                            . '<strong>' . hs_h(function_exists('hs_invoice_format_total') ? hs_invoice_format_total($inv, $lang) : '') . '</strong></p>';
                    }
                    $html .= '</div>';
                }
            }
        } catch (Throwable) {
            // ignore invoice list errors in checkout UI
        }
    }
    if ($ctx['wantDomain'] && !empty($ctx['pendingDomains'])) {
        $domains = (array) $ctx['pendingDomains'];
        $catalogTotal = hs_domains_total_price_eur($domains);
        $paidTotal = (float) ($priced['domain_eur'] ?? 0);
        $pickLabel = count($domains) > 1
            ? ($t['checkout_domains'] ?? 'Domains')
            : ($t['checkout_domain'] ?? 'Domain');
        $domainIncluded = $ctx['wantHosting'] && $paidTotal <= 0 && $catalogTotal > 0;
        foreach ($domains as $dom) {
            $catalogPrice = (float) (hs_domain_price((string) $dom) ?? 0);
            $domainPriceLabel = ($domainIncluded && $catalogPrice > 0)
                ? (string) ($t['checkout_domain_included'] ?? 'Included — 0')
                : hs_domain_format_price($catalogPrice, $lang);
            $html .= hs_render_domain_picked((string) $dom, $t, [
                'label' => $pickLabel,
                'price' => $domainPriceLabel,
                'glow' => false,
                'class' => 'hs-domain-picked-checkout',
            ]);
        }
        if (count($domains) > 1 && $priced['domain_discount_eur'] > 0) {
            $html .= '<p class="hp-muted" style="font-size:.85rem;margin:.35rem 0 0">'
                . hs_h($t['checkout_domains_discount_note'] ?? 'Promo applied to domain total.')
                . '</p>';
        }
        unset($catalogTotal, $paidTotal);
    }
    $html .= '</div>';

    // Prefer sum of pending invoices (plan + each service + domains) when seeded
    // Domain-only panel purchase: never mix in old plan/service invoices.
    $pendingSumNok = 0.0;
    $pendingCount = 0;
    if (empty($ctx['panelDomainPurchase'])) {
        try {
            if (function_exists('hs_invoices_for_user')) {
                foreach (hs_invoices_for_user((string) ($user['id'] ?? '')) as $inv) {
                    if ((string) ($inv['status'] ?? '') !== 'pending') {
                        continue;
                    }
                    $pendingSumNok += (float) ($inv['total_nok'] ?? 0);
                    $pendingCount++;
                }
            }
        } catch (Throwable) {
            $pendingSumNok = 0.0;
            $pendingCount = 0;
        }
    }

    $html .= '<p class="hp-muted" style="font-size:1.15rem;font-weight:700;color:var(--hs-accent);margin-bottom:1rem">'
        . hs_h($t['checkout_total'] ?? 'Total') . ': ';
    if ($pendingCount > 0 && $pendingSumNok > 0) {
        $html .= hs_h(hs_format_nok_price($pendingSumNok, $lang));
        if ($pendingCount > 1) {
            $html .= '<span class="hp-muted" style="font-size:.75rem;font-weight:500;display:block;margin-top:.25rem">'
                . hs_h(str_replace(
                    '{n}',
                    (string) $pendingCount,
                    $t['checkout_total_from_invoices'] ?? 'Sum of {n} invoices (plan, services, domains)'
                ))
                . '</span>';
        }
    } else {
        if ($ctx['wantHosting']) {
            // hosting_nok already includes plan + all selected services
            $html .= hs_h(hs_format_nok_price((float) ($priced['hosting_nok'] ?? 0), $lang));
        }
        if ($ctx['wantHosting'] && $ctx['wantDomain']) {
            $html .= ' + ';
        }
        if ($ctx['wantDomain']) {
            $html .= hs_h(hs_domain_format_price((float) ($priced['domain_eur'] ?? 0), $lang));
        }
    }
    if ($ctx['stripeEnabled'] || $ctx['paypalEnabled']) {
        $chargeAmt = (float) ($amounts['amount'] ?? $amounts['total_eur'] ?? 0);
        // Align charge with pending invoice total when seeded (plan + services + domains)
        if ($pendingCount > 0 && $pendingSumNok > 0 && function_exists('hs_payment_nok_to_charge')) {
            try {
                $fromInv = (float) hs_payment_nok_to_charge($pendingSumNok);
                if ($fromInv > 0) {
                    $chargeAmt = $fromInv;
                }
            } catch (Throwable) {
                // keep gateway amount
            }
        }
        $chargeCur = (string) ($amounts['currency'] ?? hs_payment_charge_currency());
        $html .= '<span class="hp-muted" style="font-size:.75rem;font-weight:500;display:block;margin-top:.25rem">'
            . hs_h($t['checkout_charge_note'] ?? 'Charged as') . ': '
            . hs_h(hs_payment_format_charge($chargeAmt, $chargeCur)) . '</span>';
    }
    $html .= '</p>';

    $html .= '<form method="post" class="hs-checkout-pay">' . hs_csrf_field()
        . '<input type="hidden" name="pay_checkout" value="1">';
    if (!empty($ctx['panelDomainPurchase'])) {
        $html .= '<input type="hidden" name="panel_domain_purchase" value="1">'
            . '<input type="hidden" name="order" value="domain">';
        if (!empty($ctx['pendingDomain'])) {
            $html .= '<input type="hidden" name="domain" value="' . hs_h((string) $ctx['pendingDomain']) . '">';
        }
    }
    if (hs_panel_checkout_is_free($ctx)) {
        $freeHost = hs_platform_free_subdomain_for_user($user);
        if ($freeHost !== '') {
            $html .= '<p class="hp-muted" style="margin-bottom:1rem">'
                . hs_h(str_replace('{host}', $freeHost, $t['checkout_free_subdomain'] ?? 'Your site: https://{host}/'))
                . '</p>';
        }
        $html .= '<button type="submit" class="hs-btn hs-btn-primary hs-checkout-pay-btn">'
            . '<i class="fa-solid fa-gift"></i> ' . hs_h($t['checkout_activate_free'] ?? 'Activate free hosting') . '</button>';
    } elseif ($ctx['paymentBlocked']) {
        $html .= '<p class="hs-alert hs-alert-error">' . hs_h($t['checkout_payment_unavailable'] ?? '') . '</p>';
    } else {
        $html .= '<p class="hs-checkout-pay-label">' . hs_h($t['checkout_choose_method'] ?? '') . '</p>'
            . '<div class="hs-checkout-pay-methods">';
        if ($ctx['stripeEnabled']) {
            $html .= '<button type="submit" name="pay_method" value="stripe" class="hs-btn hs-btn-primary hs-checkout-pay-btn">'
                . '<i class="fa-brands fa-stripe"></i> ' . hs_h($t['checkout_pay_stripe'] ?? 'Card (Stripe)') . '</button>';
        }
        if ($ctx['paypalEnabled']) {
            $html .= '<button type="submit" name="pay_method" value="paypal" class="hs-btn hs-btn-primary hs-checkout-pay-btn hs-checkout-pay-paypal">'
                . '<i class="fa-brands fa-paypal"></i> ' . hs_h($t['checkout_pay_paypal'] ?? 'PayPal') . '</button>';
        }
        if ($ctx['simulatedAllowed']) {
            $html .= '<button type="submit" name="pay_method" value="demo" class="hs-btn hs-btn-ghost hs-checkout-pay-btn">'
                . '<i class="fa-solid fa-flask"></i> ' . hs_h($ctx['checkoutPayLabel']) . ' (' . hs_h($t['checkout_demo_short'] ?? 'demo') . ')</button>';
        }
        $html .= '</div>';
    }
    $html .= '</form>';

    return $html;
}