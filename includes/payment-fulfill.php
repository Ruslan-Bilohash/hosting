<?php
declare(strict_types=1);

require_once __DIR__ . '/coupons.php';
require_once __DIR__ . '/domain-orders.php';
require_once __DIR__ . '/domain-store.php';
require_once __DIR__ . '/domain-cart.php';
require_once __DIR__ . '/order-types.php';
require_once __DIR__ . '/storage.php';

/**
 * Activate subscription / domain after successful payment (demo or gateway).
 *
 * @param array<string, mixed> $user
 * @param array<string, mixed> $priced hs_coupon_apply() result
 * @param array<string, mixed>|null $couponRow
 * @param array{lang?:string,payment_provider?:string,payment_ref?:string} $meta
 * @return array{ok:bool,user?:array,error?:string}
 */
function hs_payment_fulfill_checkout(array $user, array $priced, ?array $couponRow, array $meta = []): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'user'];
    }

    $orderType = hs_user_order_type($user);
    $wantHosting = hs_order_includes_hosting($orderType);
    $wantDomain = hs_order_includes_domain($user);
    $pendingDomains = hs_user_pending_domains($user);
    $pendingDomain = $pendingDomains[0] ?? '';
    $lang = (string) ($meta['lang'] ?? 'en');
    $panelDomainPurchase = !empty($meta['panel_domain_purchase']);
    // Active client domain pay: never re-bill / re-provision hosting.
    if ($panelDomainPurchase || !empty($meta['preserve_hosting'])) {
        $wantHosting = false;
        $wantDomain = true;
        $orderType = 'domain';
        if ($pendingDomains === [] && $pendingDomain !== '') {
            $pendingDomains = [$pendingDomain];
        }
    }

    $activeUser = $user;
    $pd = '';
    $users = hs_users();
    $found = false;

    foreach ($users as &$u) {
        if (($u['id'] ?? '') !== $userId) {
            continue;
        }
        $found = true;
        require_once __DIR__ . '/plans.php';
        $preserveHosting = !empty($meta['preserve_hosting'])
            || ($panelDomainPurchase && hs_plan_is_hosting((string) ($u['plan'] ?? '')))
            || ($panelDomainPurchase && (string) ($u['subscription_status'] ?? '') === 'active');
        // Keep active subscription; only force active for first-time checkout.
        if ((string) ($u['subscription_status'] ?? '') !== 'active') {
            $u['subscription_status'] = 'active';
        }
        $userPlan = hs_plan((string) ($u['plan'] ?? 'starter'));
        require_once __DIR__ . '/platform-free-hosting.php';
        // Domain-only panel purchase on existing hosting: keep paid_until
        if (!$preserveHosting) {
            if (hs_plan_is_free((string) ($u['plan'] ?? ''))) {
                $u['paid_until'] = gmdate('c', strtotime('+10 years'));
            } elseif ($orderType === 'domain') {
                $u['paid_until'] = gmdate('c', strtotime('+1 year'));
            } elseif (hs_plan_billing_period($userPlan) === 'year') {
                $u['paid_until'] = gmdate('c', strtotime('+1 year'));
            } else {
                $u['paid_until'] = gmdate('c', strtotime('+1 month'));
            }
        }
        $userPendingDomains = $wantDomain ? hs_user_pending_domains($u) : [];
        if ($userPendingDomains === [] && $pendingDomains !== []) {
            $userPendingDomains = $pendingDomains;
        }
        if ($couponRow !== null) {
            $u['coupon_used'] = (string) ($couponRow['code'] ?? '');
        }
        if (!empty($meta['payment_provider'])) {
            $u['last_payment_provider'] = (string) $meta['payment_provider'];
            $u['last_payment_ref'] = (string) ($meta['payment_ref'] ?? '');
            $u['last_payment_at'] = gmdate('c');
        }
        if ($userPendingDomains !== [] && $wantDomain) {
            require_once __DIR__ . '/domain-orders.php';
            $catalogTotal = hs_domains_total_price_eur($userPendingDomains);
            $paidTotal = (float) ($priced['domain_eur'] ?? 0);
            $remainingPaid = $paidTotal;
            foreach ($userPendingDomains as $idx => $pd) {
                $catalogPrice = (float) (hs_domain_price($pd) ?? 0);
                if ($idx === count($userPendingDomains) - 1) {
                    $orderPrice = max(0, $remainingPaid);
                } elseif ($catalogTotal > 0 && $paidTotal > 0) {
                    $orderPrice = round($paidTotal * ($catalogPrice / $catalogTotal), 2);
                    $remainingPaid = max(0, $remainingPaid - $orderPrice);
                } else {
                    $orderPrice = $catalogPrice;
                }
                // Paid → Namecheap register + folder public_html/{user}/{domain}/ + bind
                hs_domain_fulfill_paid($userId, (string) $pd, $u, [
                    'price' => $orderPrice,
                    'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
                    'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
                    'as_primary' => $idx === 0 && !$preserveHosting,
                    'skip_notify' => true,
                ]);
            }
            $u['pending_domain'] = null;
            $u['pending_domains'] = [];
            // Refresh user after binds
            $fresh = hs_user_by_id($userId);
            if (is_array($fresh)) {
                $u = array_merge($u, [
                    'pending_domains' => [],
                    'pending_domain' => null,
                ]);
            }
        }
        $activeUser = $u;
        break;
    }
    unset($u);

    if (!$found) {
        return ['ok' => false, 'error' => 'user'];
    }

    hs_save_users($users);

    if ($couponRow !== null) {
        hs_coupon_redeem((string) ($couponRow['code'] ?? ''));
        hs_coupon_session_clear();
    }

    if ($wantHosting && $orderType !== 'domain' && hs_plan_is_hosting((string) ($activeUser['plan'] ?? ''))) {
        require_once __DIR__ . '/client-database-onboard.php';
        require_once __DIR__ . '/installer.php';
        hs_ensure_user_workspace($activeUser);
        // Reseller Nebula: dedicated cPanel account (quota = plan disk_gb, e.g. starter 5 GB)
        require_once __DIR__ . '/cpanel-provision.php';
        if (function_exists('hs_whm_enabled') && hs_whm_enabled()
            && (!function_exists('hs_cpanel_auto_provision') || hs_cpanel_auto_provision())) {
            hs_cpanel_provision_for_user($activeUser);
        } else {
            // Shared single-cPanel fallback: MySQL + FTP jails inside main account
            // (or WHM on but auto_provision off — admin creates cPanel manually)
            if (!(function_exists('hs_whm_enabled') && hs_whm_enabled())) {
                hs_client_provision_database_and_pma($activeUser);
                require_once __DIR__ . '/client-ftp-onboard.php';
                hs_client_provision_ftp($activeUser);
            }
        }
        require_once __DIR__ . '/host-platform-bridge.php';
        hs_host_platform_provision_client($activeUser);
        require_once __DIR__ . '/platform-free-hosting.php';
        hs_platform_free_subdomain_assign($userId, $activeUser);
    }

    require_once __DIR__ . '/order-notifications.php';
    require_once __DIR__ . '/invoices.php';
    $pt = function_exists('hs_support_panel_strings') ? hs_support_panel_strings($lang) : [];

    // Ensure separate pending invoices exist, then mark each paid (1 service = 1 invoice).
    // Domain-only panel purchase: do not seed/mark hosting plan invoices.
    $marked = 0;
    if (!$panelDomainPurchase) {
        hs_invoice_ensure_pending_checkout($activeUser, $lang);
        $marked = hs_invoice_mark_pending_checkout_paid($userId, [
            'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
            'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
        ]);
    }

    // Fallback: if nothing was pending, create separate paid invoices for plan + services
    $invoiceLines = [];
    if ($marked === 0 && $wantHosting && $orderType !== 'domain' && !$panelDomainPurchase) {
        $invoiceLines = hs_invoice_lines_subscription($activeUser, $pt, $lang, 1, [
            'charge_yearly_on_activate' => true,
        ]);
        hs_invoice_create_separate_paid($activeUser, $invoiceLines, $lang, [
            'event' => 'plan_activated',
            'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
            'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
        ]);
    }

    $notifyPayload = [
        'lang' => $lang,
        'order_type' => $orderType,
        'price_nok' => $priced['hosting_nok'] ?? 0,
        'lines' => $invoiceLines,
        'coupon_code' => $couponRow !== null ? (string) ($couponRow['code'] ?? '') : '',
        'coupon_discount_nok' => $priced['hosting_discount_nok'] ?? 0,
        'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
        'skip_invoice' => $marked > 0, // avoid second combined invoice in notify
    ];
    if ($pendingDomains !== []) {
        $notifyPayload['domain'] = implode(', ', $pendingDomains);
        $notifyPayload['domain_price_eur'] = $priced['domain_eur'] ?? 0;
        $notifyPayload['domain_price_nok'] = 0;
        $notifyPayload['coupon_discount_eur'] = $priced['domain_discount_eur'] ?? 0;
        require_once __DIR__ . '/currency.php';
        $rates = hs_exchange_rates();
        $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
        $notifyPayload['price_nok'] += round(($priced['domain_eur'] ?? 0) / $eurRate, 2);
    }
    // Always attach payment meta for operator receipts (support@ + CC)
    $notifyPayload['payment_provider'] = (string) ($meta['payment_provider'] ?? $notifyPayload['payment_provider'] ?? '');
    $notifyPayload['payment_ref'] = (string) ($meta['payment_ref'] ?? $notifyPayload['payment_ref'] ?? '');

    if ($orderType === 'domain' && $pendingDomains !== []) {
        hs_notify_order_event('domain_activated', $activeUser, $notifyPayload);
    } elseif ($wantHosting) {
        hs_notify_order_event('plan_activated', $activeUser, $notifyPayload);
    } elseif ($pendingDomains !== [] || (float) ($priced['domain_eur'] ?? 0) > 0 || (float) ($priced['hosting_nok'] ?? 0) > 0) {
        // Bundle / domain-only panel purchase without classic order_type flags
        hs_notify_order_event(
            $pendingDomains !== [] ? 'domain_activated' : 'payment_received',
            $activeUser,
            $notifyPayload
        );
    }

    hs_session_start();
    hs_domain_cart_clear();
    if ($panelDomainPurchase && function_exists('hs_panel_domain_purchase_clear')) {
        hs_panel_domain_purchase_clear();
    }

    return ['ok' => true, 'user' => $activeUser];
}

/**
 * Pay a single pending invoice (Stripe/PayPal/demo return path).
 * Activates hosting when the plan invoice is paid; domain invoices bind that domain.
 *
 * @param array<string, mixed> $user
 * @param array<string, mixed> $invoice
 * @param array{lang?:string,payment_provider?:string,payment_ref?:string} $meta
 * @return array{ok:bool,user?:array,error?:string}
 */
function hs_payment_fulfill_invoice(array $user, array $invoice, array $meta = []): array
{
    $userId = (string) ($user['id'] ?? '');
    $invoiceId = (string) ($invoice['id'] ?? '');
    if ($userId === '' || $invoiceId === '') {
        return ['ok' => false, 'error' => 'user'];
    }
    if ((string) ($invoice['user_id'] ?? '') !== $userId) {
        return ['ok' => false, 'error' => 'forbidden'];
    }
    if ((string) ($invoice['status'] ?? '') === 'paid') {
        return ['ok' => true, 'user' => $user, 'already' => true];
    }

    require_once __DIR__ . '/invoices.php';
    require_once __DIR__ . '/plans.php';

    if (!hs_invoice_mark_paid_by_id($invoiceId, [
        'paid_event' => 'invoice_pay',
        'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
        'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
    ])) {
        return ['ok' => false, 'error' => 'invoice'];
    }

    $type = (string) ($invoice['type'] ?? '');
    $invMeta = is_array($invoice['meta'] ?? null) ? $invoice['meta'] : [];
    $lang = (string) ($meta['lang'] ?? $invoice['lang'] ?? 'uk');
    $activeUser = $user;

    // Plan / plan+domain invoice: activate hosting if still pending.
    if (in_array($type, ['plan', 'plan_domain', 'renewal'], true)
        || ($type === 'plan_change' && (string) ($user['subscription_status'] ?? '') === 'pending')) {
        if ((string) ($user['subscription_status'] ?? '') !== 'active' && hs_plan_is_hosting((string) ($user['plan'] ?? ''))) {
            $priced = [
                'hosting_nok' => (float) ($invoice['total_nok'] ?? 0),
                'hosting_discount_nok' => 0,
                'domain_eur' => 0,
                'domain_discount_eur' => 0,
            ];
            // Fulfill hosting only — do not mark other pending invoices.
            $users = hs_users();
            $found = false;
            foreach ($users as &$u) {
                if ((string) ($u['id'] ?? '') !== $userId) {
                    continue;
                }
                $found = true;
                $u['subscription_status'] = 'active';
                $userPlan = hs_plan((string) ($u['plan'] ?? 'starter'));
                if (hs_plan_is_free((string) ($u['plan'] ?? ''))) {
                    $u['paid_until'] = gmdate('c', strtotime('+10 years'));
                } elseif (hs_plan_billing_period($userPlan) === 'year') {
                    $u['paid_until'] = gmdate('c', strtotime('+1 year'));
                } else {
                    $u['paid_until'] = gmdate('c', strtotime('+1 month'));
                }
                if (!empty($meta['payment_provider'])) {
                    $u['last_payment_provider'] = (string) $meta['payment_provider'];
                    $u['last_payment_ref'] = (string) ($meta['payment_ref'] ?? '');
                    $u['last_payment_at'] = gmdate('c');
                }
                $activeUser = $u;
                break;
            }
            unset($u);
            if ($found) {
                hs_save_users($users);
                require_once __DIR__ . '/client-database-onboard.php';
                require_once __DIR__ . '/installer.php';
                hs_ensure_user_workspace($activeUser);
                require_once __DIR__ . '/cpanel-provision.php';
                if (function_exists('hs_whm_enabled') && hs_whm_enabled()
                    && (!function_exists('hs_cpanel_auto_provision') || hs_cpanel_auto_provision())) {
                    hs_cpanel_provision_for_user($activeUser);
                } elseif (!(function_exists('hs_whm_enabled') && hs_whm_enabled())) {
                    hs_client_provision_database_and_pma($activeUser);
                    require_once __DIR__ . '/client-ftp-onboard.php';
                    hs_client_provision_ftp($activeUser);
                }
                require_once __DIR__ . '/host-platform-bridge.php';
                hs_host_platform_provision_client($activeUser);
                require_once __DIR__ . '/order-notifications.php';
                hs_notify_order_event('plan_activated', $activeUser, [
                    'lang' => $lang,
                    'price_nok' => (float) ($invoice['total_nok'] ?? 0),
                    'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
                    'skip_invoice' => true,
                ]);
            }
            // Silence unused priced in case of future coupon hooks
            unset($priced);
        }
    }

    // Domain invoice: register / bind that domain when listed in meta or still pending.
    if ($type === 'domain' || $type === 'plan_domain') {
        $dom = strtolower(trim((string) ($invMeta['domain'] ?? '')));
        if ($dom === '') {
            foreach (is_array($invoice['lines'] ?? null) ? $invoice['lines'] : [] as $line) {
                $desc = (string) ($line['desc'] ?? '');
                if (preg_match('/:\s*([a-z0-9][a-z0-9.-]+\.[a-z]{2,})\s*$/i', $desc, $m)) {
                    $dom = strtolower($m[1]);
                    break;
                }
            }
        }
        if ($dom !== '') {
            require_once __DIR__ . '/domain-orders.php';
            $fresh = hs_user_by_id($userId) ?? $activeUser;
            hs_domain_fulfill_paid($userId, $dom, $fresh, [
                'price' => (float) ($invoice['total_nok'] ?? 0),
                'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
                'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
                'as_primary' => true,
                'skip_notify' => true,
            ]);
            // Drop from pending cart if present
            $fresh = hs_user_by_id($userId) ?? $fresh;
            $pending = hs_user_pending_domains($fresh);
            if ($pending !== []) {
                $left = array_values(array_filter($pending, static fn($d) => strtolower((string) $d) !== $dom));
                hs_user_update($userId, static function (array &$u) use ($left): void {
                    $u['pending_domains'] = $left;
                    $u['pending_domain'] = $left[0] ?? null;
                });
            }
            $activeUser = hs_user_by_id($userId) ?? $activeUser;
            require_once __DIR__ . '/order-notifications.php';
            hs_notify_order_event('domain_activated', $activeUser, [
                'lang' => $lang,
                'domain' => $dom,
                'price_nok' => (float) ($invoice['total_nok'] ?? 0),
                'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
                'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
                'invoice_number' => (string) ($invoice['number'] ?? ''),
                'skip_invoice' => true,
            ]);
        }
    }

    // Services / other invoices: operator notify (plan/domain already notified above)
    if (in_array($type, ['services', 'plan_change'], true)
        || ($type !== 'plan' && $type !== 'plan_domain' && $type !== 'renewal' && $type !== 'domain')) {
        require_once __DIR__ . '/order-notifications.php';
        $event = $type === 'services' ? 'service_paid' : 'invoice_paid';
        hs_notify_order_event($event, $activeUser, [
            'lang' => $lang,
            'price_nok' => (float) ($invoice['total_nok'] ?? 0),
            'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
            'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
            'invoice_number' => (string) ($invoice['number'] ?? ''),
            'invoice_type' => $type,
            'skip_invoice' => true,
        ]);
    }

    return ['ok' => true, 'user' => $activeUser];
}

/**
 * Extend hosting subscription after renewal payment.
 *
 * @param array<string, mixed> $user
 * @param array{lang?:string,payment_provider?:string,payment_ref?:string,months?:int,price_nok?:float} $meta
 * @return array{ok:bool,user?:array,error?:string}
 */
function hs_payment_fulfill_renew(array $user, array $meta = []): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'user'];
    }
    $months = max(1, min(36, (int) ($meta['months'] ?? 1)));
    $lang = (string) ($meta['lang'] ?? 'en');
    $priceNok = (float) ($meta['price_nok'] ?? 0);

    $activeUser = $user;
    $users = hs_users();
    $found = false;
    foreach ($users as &$u) {
        if (($u['id'] ?? '') !== $userId) {
            continue;
        }
        $found = true;
        $base = $u['paid_until'] ?? gmdate('c');
        $baseTs = strtotime((string) $base);
        if ($baseTs < time()) {
            $baseTs = time();
        }
        $u['paid_until'] = gmdate('c', strtotime('+' . $months . ' month', $baseTs));
        $u['subscription_status'] = 'active';
        $u['active'] = true;
        if (!empty($meta['payment_provider'])) {
            $u['last_payment_provider'] = (string) $meta['payment_provider'];
            $u['last_payment_ref'] = (string) ($meta['payment_ref'] ?? '');
            $u['last_payment_at'] = gmdate('c');
        }
        $activeUser = $u;
        break;
    }
    unset($u);

    if (!$found) {
        return ['ok' => false, 'error' => 'user'];
    }

    hs_save_users($users);

    require_once __DIR__ . '/order-notifications.php';
    if ($priceNok <= 0) {
        $planId = (string) ($activeUser['plan'] ?? 'starter');
        require_once __DIR__ . '/plans.php';
        $priceNok = (float) (hs_plan($planId)['price_nok'] ?? 0) * $months;
    }
    $notifyPayload = [
        'lang' => $lang,
        'months' => $months,
        'price_nok' => $priceNok,
        'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
        'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
    ];
    if (!empty($meta['invoice_lines']) && is_array($meta['invoice_lines'])) {
        $notifyPayload['lines'] = $meta['invoice_lines'];
    }
    hs_notify_order_event('plan_renew', $activeUser, $notifyPayload);

    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'plan_renew', (string) $months . 'm');
    }

    return ['ok' => true, 'user' => $activeUser];
}