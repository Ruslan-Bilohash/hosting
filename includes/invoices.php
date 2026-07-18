<?php
declare(strict_types=1);

require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/plan-catalog.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/domain-store.php';
require_once __DIR__ . '/plan-services.php';
require_once __DIR__ . '/user-settings.php';

function hs_invoices_file(): string
{
    return hs_data_file('invoices');
}

function hs_invoice_counter_file(): string
{
    return hs_data_file('invoice-counter');
}

/** @return list<array<string,mixed>> */
function hs_invoices_all(): array
{
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        hs_db_ensure_schema();
        try {
            $rows = hs_db_load_collection('invoices');
            if ($rows !== []) {
                return $rows;
            }
            $json = hs_read_json(hs_invoices_file());
            if (is_array($json) && $json !== []) {
                hs_db_save_collection('invoices', array_values($json), 'id');
                return $json;
            }
            return [];
        } catch (Throwable) {
            // JSON fallback if MySQL table missing or query fails.
        }
    }
    $rows = hs_read_json(hs_invoices_file());
    return is_array($rows) ? $rows : [];
}

function hs_invoices_save(array $invoices): bool
{
    if (hs_is_mysql_installed()) {
        return hs_db_save_collection('invoices', array_values($invoices), 'id');
    }
    return hs_write_json(hs_invoices_file(), array_values($invoices));
}

function hs_invoice_next_number(): string
{
    $year = gmdate('Y');
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        $counter = hs_db_meta_get_array(HS_DB_META_INVOICE_COUNTER, ['year' => $year, 'seq' => 0]);
        $seq = (int) ($counter['seq'] ?? 0) + 1;
        hs_db_meta_set_array(HS_DB_META_INVOICE_COUNTER, ['year' => $year, 'seq' => $seq]);
    } else {
        $counter = hs_read_json(hs_invoice_counter_file());
        $seq = (int) ($counter['seq'] ?? 0) + 1;
        hs_write_json(hs_invoice_counter_file(), ['year' => $year, 'seq' => $seq]);
    }
    return sprintf('BH-%s-%05d', $year, $seq);
}

/** @return array<string,mixed>|null */
function hs_invoice_by_id(string $id): ?array
{
    foreach (hs_invoices_all() as $inv) {
        if (($inv['id'] ?? '') === $id) {
            return $inv;
        }
    }
    return null;
}

/** @return list<array<string,mixed>> */
function hs_invoices_for_user(string $userId): array
{
    if ($userId !== '') {
        static $backfilled = [];
        if (!isset($backfilled[$userId])) {
            $backfilled[$userId] = true;
            hs_invoices_backfill_user($userId);
        }
    }
    $out = [];
    foreach (hs_invoices_all() as $inv) {
        if (($inv['user_id'] ?? '') === $userId) {
            $out[] = $inv;
        }
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    return $out;
}

/** @param array<string,bool> $seen */
function hs_invoices_backfill_key(string $event, string $createdAt, float $priceNok): string
{
    return strtolower(trim($event))
        . '|' . substr($createdAt, 0, 10)
        . '|' . round($priceNok, 2);
}

/** @param array<string,bool> $seen */
function hs_invoices_backfill_try(
    array &$seen,
    string $event,
    array $user,
    array $payload
): bool {
    $createdAt = (string) ($payload['created_at'] ?? gmdate('c'));
    $priceNok = (float) ($payload['price_nok'] ?? 0);
    $key = hs_invoices_backfill_key($event, $createdAt, $priceNok);
    if (isset($seen[$key])) {
        return false;
    }
    $inv = hs_invoice_from_event($event, $user, $payload);
    if ($inv === null) {
        return false;
    }
    $seen[$key] = true;
    return true;
}

/** Create missing invoices from hosting_orders log (legacy payments). */
function hs_invoices_backfill_user(string $userId): int
{
    if ($userId === '') {
        return 0;
    }

    require_once __DIR__ . '/hosting-orders.php';
    $user = hs_user_by_id($userId);
    if ($user === null) {
        return 0;
    }

    $seen = [];
    foreach (hs_invoices_for_user_raw($userId) as $inv) {
        $meta = is_array($inv['meta'] ?? null) ? $inv['meta'] : [];
        $seen[hs_invoices_backfill_key(
            (string) ($meta['event'] ?? ''),
            (string) ($inv['created_at'] ?? ''),
            (float) ($inv['total_nok'] ?? 0)
        )] = true;
    }

    $lang = (string) ($user['lang'] ?? 'uk');
    $created = 0;

    foreach (hs_hosting_orders() as $order) {
        if (!is_array($order) || (string) ($order['user_id'] ?? '') !== $userId) {
            continue;
        }
        $event = (string) ($order['event'] ?? '');
        if ($event === '') {
            continue;
        }
        if (hs_invoices_backfill_try($seen, $event, $user, [
            'lang' => $lang,
            'price_nok' => (float) ($order['price_nok'] ?? 0),
            'domain' => (string) ($order['domain'] ?? ''),
            'months' => (int) ($order['months'] ?? 1),
            'created_at' => (string) ($order['created_at'] ?? ''),
        ])) {
            $created++;
        }
    }

    require_once __DIR__ . '/domain-orders.php';
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if (empty($order['payment_confirmed']) && ($order['status'] ?? '') !== 'active') {
            continue;
        }
        $domain = (string) ($order['domain'] ?? '');
        if ($domain === '') {
            continue;
        }
        $event = ($order['status'] ?? '') === 'pending' ? 'domain_ordered' : 'domain_activated';
        $priceNok = (float) ($order['price_nok'] ?? 0);
        if ($priceNok <= 0) {
            $eur = (float) ($order['price'] ?? 0);
            if ($eur > 0) {
                require_once __DIR__ . '/currency.php';
                $rates = hs_exchange_rates();
                $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
                $priceNok = round($eur / $eurRate, 2);
            }
        }
        if (hs_invoices_backfill_try($seen, $event, $user, [
            'lang' => $lang,
            'domain' => $domain,
            'price_nok' => $priceNok,
            'created_at' => (string) ($order['ordered_at'] ?? $order['created_at'] ?? ''),
        ])) {
            $created++;
        }
    }

    if (hs_invoices_for_user_raw($userId) === []) {
        $lastPay = (string) ($user['last_payment_at'] ?? '');
        if ($lastPay !== '' && ($user['subscription_status'] ?? '') === 'active') {
            $plan = hs_plan((string) ($user['plan'] ?? 'starter'));
            $priceNok = (float) ($plan['price_nok'] ?? 0);
            if (hs_invoices_backfill_try($seen, 'plan_activated', $user, [
                'lang' => $lang,
                'price_nok' => $priceNok,
                'created_at' => $lastPay,
            ])) {
                $created++;
            }
        }
    }

    return $created;
}

/** @return list<array<string,mixed>> */
function hs_invoices_for_user_raw(string $userId): array
{
    $out = [];
    foreach (hs_invoices_all() as $inv) {
        if (($inv['user_id'] ?? '') === $userId) {
            $out[] = $inv;
        }
    }
    return $out;
}

/** Unpaid / open invoices may be deleted; paid ones are permanent. */
function hs_invoice_is_deletable(?array $invoice): bool
{
    if ($invoice === null) {
        return false;
    }
    $status = strtolower(trim((string) ($invoice['status'] ?? '')));

    // Only unpaid statuses can be removed.
    return in_array($status, ['pending', 'unpaid', 'open', 'draft', 'cancelled', 'canceled'], true);
}

/**
 * Delete invoice if it belongs to $userId (when non-empty) and is unpaid.
 *
 * @return array{ok:bool,error?:string}
 */
function hs_invoice_delete_for_user(string $invoiceId, string $userId = ''): array
{
    $invoiceId = trim($invoiceId);
    if ($invoiceId === '') {
        return ['ok' => false, 'error' => 'missing_id'];
    }
    $inv = hs_invoice_by_id($invoiceId);
    if ($inv === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if ($userId !== '' && (string) ($inv['user_id'] ?? '') !== $userId) {
        return ['ok' => false, 'error' => 'forbidden'];
    }
    if (!hs_invoice_is_deletable($inv)) {
        return ['ok' => false, 'error' => 'paid_locked'];
    }
    if (!hs_invoice_delete($invoiceId)) {
        return ['ok' => false, 'error' => 'delete_failed'];
    }

    return ['ok' => true];
}

function hs_invoice_delete(string $invoiceId): bool
{
    $invoiceId = trim($invoiceId);
    if ($invoiceId === '') {
        return false;
    }
    $all = hs_invoices_all();
    $next = [];
    $found = false;
    foreach ($all as $inv) {
        if (!is_array($inv)) {
            continue;
        }
        if ((string) ($inv['id'] ?? '') === $invoiceId) {
            $found = true;
            continue;
        }
        $next[] = $inv;
    }
    if (!$found) {
        return false;
    }

    return hs_invoices_save($next);
}

/**
 * Build invoice line items for plan + add-on services.
 *
 * @param array<string, mixed> $user
 * @param array<string, string> $t
 * @return list<array{desc:string,qty:float,unit_nok:float}>
 */
function hs_invoice_lines_subscription(
    array $user,
    array $t,
    string $lang,
    int $months = 1,
    array $opts = []
): array {
    $months = max(1, min(36, $months));
    $planId = (string) ($opts['plan_id'] ?? $user['plan'] ?? 'starter');
    $isRenew = !empty($opts['renew']);
    $chargeYearlyOnActivate = !empty($opts['charge_yearly_on_activate']);
    $plan = hs_plan($planId);
    $unitNok = hs_plan_addon_unit_nok($plan);
    $hostingNok = round($unitNok * $months, 2);
    $renewSuffix = $isRenew
        ? ' (' . ($t['btn_renew'] ?? 'Renew') . ($months > 1 ? ' × ' . $months : '') . ')'
        : '';

    $lines = [[
        'desc' => ($t['invoice_line_plan'] ?? 'Hosting plan') . ': ' . hs_plan_hosting_label($planId, $t) . $renewSuffix,
        'qty' => 1,
        'unit_nok' => $hostingNok,
    ]];

    foreach (hs_user_plan_services($user, $lang) as $svc) {
        $amountNok = hs_plan_service_period_nok($svc, $months, $chargeYearlyOnActivate && !$isRenew);
        if ($amountNok <= 0) {
            continue;
        }
        $lines[] = [
            'desc' => ($t['invoice_line_service'] ?? 'Add-on service') . ': ' . hs_plan_addon_label($svc, $lang, $t),
            'qty' => 1,
            'unit_nok' => $amountNok,
        ];
    }

    $discountNok = (float) ($opts['discount_nok'] ?? 0);
    if ($discountNok > 0) {
        $lines[] = [
            'desc' => (string) ($t['invoice_line_discount'] ?? 'Discount'),
            'qty' => 1,
            'unit_nok' => -round($discountNok, 2),
        ];
    }

    return $lines;
}

/** @param list<array{desc:string,qty:float,unit_nok:float}> $lines */
function hs_invoice_lines_total_nok(array $lines): float
{
    $total = 0.0;
    foreach ($lines as $line) {
        $qty = max(1, (float) ($line['qty'] ?? 1));
        $unit = (float) ($line['unit_nok'] ?? 0);
        $total += round($qty * $unit, 2);
    }

    return round($total, 2);
}

/**
 * Legal seller on every invoice (not the client / admin personal name).
 *
 * @return array{company:string,tagline:string,domain:string,email:string,web:string}
 */
function hs_invoice_seller(): array
{
    $domain = function_exists('hs_default_primary_domain')
        ? hs_default_primary_domain()
        : 'solaskinner.com';
    $email = 'billing@' . $domain;
    $siteName = '';
    if (function_exists('hs_host_profile_value')) {
        $support = trim((string) (hs_host_profile_value('support_inbox_email') ?? ''));
        if ($support !== '' && filter_var($support, FILTER_VALIDATE_EMAIL)) {
            $email = $support;
        }
        $siteName = trim((string) (hs_host_profile_value('site_name') ?? ''));
    }
    // Production Solaskinner invoices — seller brand (not BILOHASH)
    $isSolaskinner = str_contains(strtolower($domain), 'solaskinner')
        || (function_exists('hs_is_production_host') && hs_is_production_host()
            && str_contains(strtolower((string) (hs_host_profile_value('host') ?? '')), 'solaskinner'));
    if ($isSolaskinner || $siteName === 'Solaskinner') {
        $company = 'SolaSkinner Hosting';
        $tagline = 'Hosting and domain services — solaskinner.com';
    } else {
        $company = $siteName !== '' ? $siteName . ' Hosting' : 'BILOHASH Hosting';
        $tagline = 'Hosting and domain services';
    }

    return [
        'company' => $company,
        'tagline' => $tagline,
        'domain' => $domain,
        'email' => $email,
        'web' => 'https://' . $domain,
    ];
}

/** @param array<string,mixed> $user */
function hs_invoice_billing_from_user(array $user): array
{
    $profile = is_array($user['profile'] ?? null) ? $user['profile'] : [];
    $name = trim((string) ($user['name'] ?? ''));
    if ($name === '') {
        $name = trim(((string) ($profile['first_name'] ?? '')) . ' ' . ((string) ($profile['last_name'] ?? '')));
    }
    return [
        'name' => $name !== '' ? $name : (string) ($user['username'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'company' => (string) ($profile['company'] ?? ''),
        'vat' => (string) ($profile['vat'] ?? ''),
        'address' => (string) ($profile['address'] ?? ''),
        'city' => (string) ($profile['city'] ?? ''),
        'postal' => (string) ($profile['postal'] ?? ''),
        'country' => (string) ($profile['country'] ?? ''),
    ];
}

/**
 * @param list<array{desc:string,qty:float,unit_nok:float}> $lines
 * @return array<string,mixed>|null
 */
function hs_invoice_create(
    array $user,
    string $type,
    array $lines,
    string $lang = 'uk',
    string $status = 'paid',
    array $meta = []
): ?array {
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '' || $lines === []) {
        return null;
    }
    $totalNok = 0.0;
    $normLines = [];
    foreach ($lines as $line) {
        $qty = max(1, (float) ($line['qty'] ?? 1));
        $unit = max(0, (float) ($line['unit_nok'] ?? 0));
        $lineTotal = round($qty * $unit, 2);
        $totalNok += $lineTotal;
        $normLines[] = [
            'desc' => (string) ($line['desc'] ?? ''),
            'qty' => $qty,
            'unit_nok' => $unit,
            'total_nok' => $lineTotal,
        ];
    }
    $invoice = [
        'id' => hs_new_id('inv'),
        'number' => hs_invoice_next_number(),
        'user_id' => $userId,
        'username' => (string) ($user['username'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'type' => $type,
        'status' => $status,
        'created_at' => (string) ($meta['created_at'] ?? gmdate('c')),
        'due_at' => gmdate('c', strtotime('+14 days')),
        'paid_at' => $status === 'paid' ? gmdate('c') : null,
        'lang' => $lang,
        'currency' => hs_currency_for_lang($lang),
        'subtotal_nok' => $totalNok,
        'total_nok' => $totalNok,
        'lines' => $normLines,
        'meta' => $meta,
        'seller' => hs_invoice_seller(),
        'billing' => hs_invoice_billing_from_user($user),
    ];
    $all = hs_invoices_all();
    $all[] = $invoice;
    if (!hs_invoices_save($all)) {
        return null;
    }
    return $invoice;
}

/** @param array<string,mixed> $user */
function hs_invoice_from_event(string $event, array $user, array $payload = []): ?array
{
    $lang = (string) ($payload['lang'] ?? 'uk');
    $pt = function_exists('hs_support_panel_strings') ? hs_support_panel_strings($lang) : [];
    $planId = (string) ($user['plan'] ?? 'starter');
    $status = $event === 'domain_ordered' ? 'pending' : 'paid';

    $lines = [];
    $type = 'plan';
    $meta = ['event' => $event, 'plan' => $planId];
    if (!empty($payload['created_at'])) {
        $meta['created_at'] = (string) $payload['created_at'];
    }

    if ($event === 'plan_activated' || $event === 'plan_renewed' || $event === 'plan_renew') {
        if ($event === 'plan_renew') {
            $event = 'plan_renewed';
        }
        $months = max(1, (int) ($payload['months'] ?? 1));
        if (!empty($payload['lines']) && is_array($payload['lines'])) {
            foreach ($payload['lines'] as $invLine) {
                if (!is_array($invLine)) {
                    continue;
                }
                $lines[] = [
                    'desc' => (string) ($invLine['desc'] ?? ''),
                    'qty' => max(1, (float) ($invLine['qty'] ?? 1)),
                    'unit_nok' => (float) ($invLine['unit_nok'] ?? 0),
                ];
            }
        } else {
            $built = hs_invoice_lines_subscription($user, $pt, $lang, $months, [
                'plan_id' => $planId,
                'renew' => $event === 'plan_renewed',
                'charge_yearly_on_activate' => $event === 'plan_activated',
            ]);
            $paidNok = (float) ($payload['price_nok'] ?? 0);
            $builtTotal = hs_invoice_lines_total_nok($built);
            if ($paidNok > 0 && $builtTotal > $paidNok + 0.009) {
                $built = hs_invoice_lines_subscription($user, $pt, $lang, $months, [
                    'plan_id' => $planId,
                    'renew' => $event === 'plan_renewed',
                    'charge_yearly_on_activate' => $event === 'plan_activated',
                    'discount_nok' => round($builtTotal - $paidNok, 2),
                ]);
            }
            $lines = $built;
        }
        if (!empty($payload['domain'])) {
            $dom = (string) $payload['domain'];
            $domPrice = (float) ($payload['domain_price_nok'] ?? 0);
            if ($domPrice <= 0) {
                require_once __DIR__ . '/currency.php';
                $eurPaid = (float) ($payload['domain_price_eur'] ?? 0);
                if ($eurPaid <= 0) {
                    $domPrice = 0.0;
                } else {
                    $rates = hs_exchange_rates();
                    $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
                    $domPrice = round($eurPaid / $eurRate, 2);
                }
            }
            $desc = ($pt['invoice_line_domain'] ?? 'Domain') . ': ' . $dom;
            if ($domPrice <= 0) {
                $desc .= ' (' . ($pt['invoice_line_domain_included'] ?? 'Included with hosting') . ')';
            }
            $lines[] = [
                'desc' => $desc,
                'qty' => 1,
                'unit_nok' => $domPrice,
            ];
            $type = 'plan_domain';
            $meta['domain'] = $dom;
        }
    } elseif ($event === 'domain_ordered' || $event === 'domain_activated') {
        $type = 'domain';
        $dom = (string) ($payload['domain'] ?? '');
        $price = (float) ($payload['price_nok'] ?? ($dom !== '' ? hs_domain_price($dom) : 0));
        $lines[] = [
            'desc' => ($pt['invoice_line_domain'] ?? 'Domain registration') . ': ' . $dom,
            'qty' => 1,
            'unit_nok' => $price,
        ];
        $meta['domain'] = $dom;
    } elseif ($event === 'plan_changed') {
        $type = 'plan_change';
        $newPlan = (string) ($payload['new_plan'] ?? $planId);
        $oldPlan = (string) ($payload['old_plan'] ?? '');
        $newP = hs_plan($newPlan);
        $oldP = hs_plan($oldPlan !== '' ? $oldPlan : 'starter');
        $diff = max(0, (float) ($newP['price_nok'] ?? 0) - (float) ($oldP['price_nok'] ?? 0));
        if ($diff <= 0) {
            $diff = (float) ($newP['price_nok'] ?? 0);
        }
        $lines[] = [
            'desc' => ($pt['invoice_line_plan_change'] ?? 'Plan change')
                . ': ' . ($pt['plan_' . $oldPlan] ?? $oldPlan) . ' → ' . ($pt['plan_' . $newPlan] ?? $newPlan),
            'qty' => 1,
            'unit_nok' => $diff,
        ];
        $meta['old_plan'] = $oldPlan;
        $meta['plan'] = $newPlan;
    } else {
        return null;
    }

    return hs_invoice_create($user, $type, $lines, $lang, $status, $meta);
}

/** @param array<string,mixed> $invoice */
function hs_invoice_format_total(array $invoice, string $lang): string
{
    return hs_format_nok_price((float) ($invoice['total_nok'] ?? 0), $lang);
}

/**
 * Domains listed on an invoice (meta + line descriptions).
 *
 * @param array<string,mixed> $invoice
 * @return list<string>
 */
function hs_invoice_domain_names(array $invoice): array
{
    $found = [];
    $meta = is_array($invoice['meta'] ?? null) ? $invoice['meta'] : [];
    foreach (['domain', 'domains'] as $key) {
        $raw = $meta[$key] ?? null;
        if (is_string($raw) && trim($raw) !== '') {
            foreach (preg_split('/\s*,\s*/', $raw) ?: [] as $part) {
                $part = strtolower(trim($part));
                if ($part !== '' && str_contains($part, '.')) {
                    $found[$part] = true;
                }
            }
        } elseif (is_array($raw)) {
            foreach ($raw as $d) {
                $d = strtolower(trim((string) $d));
                if ($d !== '' && str_contains($d, '.')) {
                    $found[$d] = true;
                }
            }
        }
    }
    foreach (is_array($invoice['lines'] ?? null) ? $invoice['lines'] : [] as $line) {
        if (!is_array($line)) {
            continue;
        }
        $desc = (string) ($line['desc'] ?? '');
        // "Domain registration: example.com" or bare domain in desc
        if (preg_match_all('/\b([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+)\b/i', $desc, $m)) {
            foreach ($m[1] as $dom) {
                $dom = strtolower($dom);
                // skip platform brand domains accidentally matched from seller text
                if (in_array($dom, ['solaskinner.com', 'bilohash.com'], true)) {
                    continue;
                }
                if (str_contains($dom, '.')) {
                    $found[$dom] = true;
                }
            }
        }
    }

    return array_keys($found);
}

/** @param array<string,mixed> $invoice */
function hs_invoice_type_label(array $invoice, array $t): string
{
    $base = match ((string) ($invoice['type'] ?? '')) {
        'domain' => $t['invoice_type_domain'] ?? 'Domain',
        'plan_change' => $t['invoice_type_plan_change'] ?? 'Plan change',
        'plan_domain' => $t['invoice_type_plan_domain'] ?? 'Plan + domain',
        'services' => $t['invoice_type_services'] ?? 'Add-on services',
        'renewal', 'plan' => $t['invoice_type_plan'] ?? 'Hosting',
        default => $t['invoice_type_other'] ?? 'Invoice',
    };
    $domains = hs_invoice_domain_names($invoice);
    if ($domains === []) {
        return $base;
    }
    $list = implode(', ', $domains);
    // Domain-only invoices: "Домен: example.com"
    if ((string) ($invoice['type'] ?? '') === 'domain') {
        return $base . ': ' . $list;
    }
    // Plan + domain or mixed: append domain for clarity
    if ((string) ($invoice['type'] ?? '') === 'plan_domain' || count($domains) > 0) {
        $suffix = ($t['invoice_type_with_domain'] ?? '· {domain}');
        return $base . ' ' . str_replace('{domain}', $list, $suffix);
    }

    return $base;
}

function hs_invoice_status_label(string $status, array $t): string
{
    return match ($status) {
        'pending' => $t['invoice_status_pending'] ?? 'Pending',
        'paid' => $t['invoice_status_paid'] ?? 'Paid',
        default => $status,
    };
}

/**
 * Find open pending invoice for user + type (+ optional service_id).
 *
 * @return array<string,mixed>|null
 */
function hs_invoice_find_pending(string $userId, string $type, ?string $serviceId = null): ?array
{
    if ($userId === '') {
        return null;
    }
    foreach (hs_invoices_for_user($userId) as $inv) {
        if ((string) ($inv['status'] ?? '') !== 'pending') {
            continue;
        }
        if ((string) ($inv['type'] ?? '') !== $type) {
            continue;
        }
        $meta = is_array($inv['meta'] ?? null) ? $inv['meta'] : [];
        if ($serviceId !== null) {
            if ((string) ($meta['service_id'] ?? '') !== $serviceId) {
                continue;
            }
        }

        return $inv;
    }

    return null;
}

/**
 * Create separate pending invoices: hosting plan + each add-on + domains.
 * Idempotent — skips items that already have a pending invoice.
 *
 * @param array<string,mixed> $user
 * @return list<array<string,mixed>> newly created invoices
 */
function hs_invoice_ensure_pending_checkout(array $user, string $lang = 'uk'): array
{
    require_once __DIR__ . '/order-types.php';
    require_once __DIR__ . '/plan-services.php';
    require_once __DIR__ . '/domain-cart.php';

    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return [];
    }
    $t = function_exists('hs_support_panel_strings') ? hs_support_panel_strings($lang) : [];
    $orderType = hs_user_order_type($user);
    $wantHosting = hs_order_includes_hosting($orderType);
    $created = [];

    // 1) Hosting plan — separate invoice
    if ($wantHosting) {
        $planId = (string) ($user['plan'] ?? 'starter');
        if ($planId !== '' && $planId !== 'domain' && !hs_plan_is_managed_service($planId)) {
            $existing = hs_invoice_find_pending($userId, 'plan');
            if ($existing === null) {
                $plan = hs_plan($planId);
                $unit = hs_plan_addon_unit_nok($plan);
                if ($unit > 0) {
                    $inv = hs_invoice_create($user, 'plan', [[
                        'desc' => ($t['invoice_line_plan'] ?? 'Hosting plan') . ': ' . hs_plan_hosting_label($planId, $t),
                        'qty' => 1,
                        'unit_nok' => $unit,
                    ]], $lang, 'pending', [
                        'event' => 'checkout_seed',
                        'plan' => $planId,
                        'billing_period' => hs_plan_billing_period($plan),
                    ]);
                    if (is_array($inv)) {
                        $created[] = $inv;
                    }
                }
            }
        }
    }

    // 2) Each add-on service — one invoice each
    foreach (hs_user_plan_services($user, $lang) as $svc) {
        $sid = (string) ($svc['id'] ?? '');
        if ($sid === '') {
            continue;
        }
        if (hs_invoice_find_pending($userId, 'services', $sid) !== null) {
            continue;
        }
        $amount = hs_plan_service_period_nok($svc, 1, true);
        if ($amount <= 0) {
            continue;
        }
        $label = hs_plan_addon_label($svc, $lang, $t);
        $inv = hs_invoice_create($user, 'services', [[
            'desc' => ($t['invoice_line_service'] ?? 'Add-on service') . ': ' . $label,
            'qty' => 1,
            'unit_nok' => $amount,
        ]], $lang, 'pending', [
            'event' => 'checkout_seed',
            'service_id' => $sid,
            'service_label' => $label,
            'billing_period' => (string) ($svc['billing_period'] ?? 'month'),
        ]);
        if (is_array($inv)) {
            $created[] = $inv;
        }
    }

    // 3) Domains — one invoice per domain
    if (hs_order_includes_domain($user)) {
        require_once __DIR__ . '/currency.php';
        $rates = hs_exchange_rates();
        $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
        foreach (hs_user_pending_domains($user) as $dom) {
            $dom = (string) $dom;
            if ($dom === '') {
                continue;
            }
            // Skip if already pending domain invoice for this domain
            $hasDom = false;
            foreach (hs_invoices_for_user($userId) as $inv) {
                if ((string) ($inv['status'] ?? '') !== 'pending') {
                    continue;
                }
                if ((string) ($inv['type'] ?? '') !== 'domain') {
                    continue;
                }
                $meta = is_array($inv['meta'] ?? null) ? $inv['meta'] : [];
                if ((string) ($meta['domain'] ?? '') === $dom) {
                    $hasDom = true;
                    break;
                }
            }
            if ($hasDom) {
                continue;
            }
            $eur = (float) (hs_domain_price($dom) ?? 0);
            // Domain with hosting bundle may be paid separately; still list catalog price if > 0
            $nok = $eur > 0 ? round($eur / $eurRate, 2) : 0.0;
            if ($nok <= 0 && $wantHosting) {
                // Free-with-hosting still creates a 0-line skipped — skip zero domain invoices
                continue;
            }
            if ($nok <= 0) {
                continue;
            }
            $inv = hs_invoice_create($user, 'domain', [[
                'desc' => ($t['invoice_line_domain'] ?? 'Domain registration') . ': ' . $dom,
                'qty' => 1,
                'unit_nok' => $nok,
            ]], $lang, 'pending', [
                'event' => 'checkout_seed',
                'domain' => $dom,
            ]);
            if (is_array($inv)) {
                $created[] = $inv;
            }
        }
    }

    return $created;
}

/**
 * Cancel all pending checkout-seed invoices for a user so totals can be rebuilt.
 *
 * @return int cancelled count
 */
function hs_invoice_clear_pending_checkout(string $userId): int
{
    $userId = trim($userId);
    if ($userId === '') {
        return 0;
    }
    $all = hs_invoices_all();
    $n = 0;
    $now = gmdate('c');
    foreach ($all as $i => $inv) {
        if ((string) ($inv['user_id'] ?? '') !== $userId) {
            continue;
        }
        if ((string) ($inv['status'] ?? '') !== 'pending') {
            continue;
        }
        $meta = is_array($inv['meta'] ?? null) ? $inv['meta'] : [];
        $event = (string) ($meta['event'] ?? '');
        $type = (string) ($inv['type'] ?? '');
        // Wipe pending checkout-related invoices so totals can be recalculated.
        $isCheckout = $event === 'checkout_seed'
            || $event === 'domain_ordered'
            || in_array($type, ['plan', 'services', 'domain'], true);
        if (!$isCheckout) {
            continue;
        }
        $all[$i]['status'] = 'cancelled';
        $all[$i]['cancelled_at'] = $now;
        $invMeta = $meta;
        $invMeta['cancelled_reason'] = 'checkout_rebuild';
        $all[$i]['meta'] = $invMeta;
        $n++;
    }
    if ($n > 0) {
        hs_invoices_save($all);
    }

    return $n;
}

/**
 * Drop stale pending checkout invoices and create a fresh set matching current order.
 *
 * @param array<string,mixed> $user
 * @return list<array<string,mixed>>
 */
function hs_invoice_rebuild_pending_checkout(array $user, string $lang = 'uk'): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return [];
    }
    hs_invoice_clear_pending_checkout($userId);

    return hs_invoice_ensure_pending_checkout($user, $lang);
}

/**
 * Mark a single invoice as paid (ownership already verified by caller).
 *
 * @param array<string,mixed> $meta
 */
function hs_invoice_mark_paid_by_id(string $invoiceId, array $meta = []): bool
{
    $invoiceId = trim($invoiceId);
    if ($invoiceId === '') {
        return false;
    }
    $all = hs_invoices_all();
    $found = false;
    $now = gmdate('c');
    foreach ($all as $i => $inv) {
        if ((string) ($inv['id'] ?? '') !== $invoiceId) {
            continue;
        }
        if ((string) ($inv['status'] ?? '') === 'paid') {
            return true;
        }
        $invMeta = is_array($inv['meta'] ?? null) ? $inv['meta'] : [];
        $all[$i]['status'] = 'paid';
        $all[$i]['paid_at'] = $now;
        $all[$i]['meta'] = array_merge($invMeta, array_filter([
            'paid_event' => (string) ($meta['paid_event'] ?? 'invoice_pay'),
            'payment_provider' => (string) ($meta['payment_provider'] ?? ''),
            'payment_ref' => (string) ($meta['payment_ref'] ?? ''),
        ], static fn($v) => $v !== ''));
        $found = true;
        break;
    }
    if (!$found) {
        return false;
    }

    return hs_invoices_save($all);
}

/**
 * Mark pending checkout invoices as paid after successful payment.
 *
 * @return int number of invoices updated
 */
function hs_invoice_mark_pending_checkout_paid(string $userId, array $meta = []): int
{
    if ($userId === '') {
        return 0;
    }
    $all = hs_invoices_all();
    $n = 0;
    $now = gmdate('c');
    $provider = (string) ($meta['payment_provider'] ?? '');
    $ref = (string) ($meta['payment_ref'] ?? '');
    foreach ($all as $i => $inv) {
        if ((string) ($inv['user_id'] ?? '') !== $userId) {
            continue;
        }
        if ((string) ($inv['status'] ?? '') !== 'pending') {
            continue;
        }
        $type = (string) ($inv['type'] ?? '');
        if (!in_array($type, ['plan', 'services', 'domain', 'plan_domain'], true)) {
            continue;
        }
        $invMeta = is_array($inv['meta'] ?? null) ? $inv['meta'] : [];
        // Only auto-pay seed/checkout invoices (not arbitrary pending service_added if already separate)
        $event = (string) ($invMeta['event'] ?? '');
        if ($event !== '' && !in_array($event, ['checkout_seed', 'service_added', 'domain_ordered'], true)) {
            continue;
        }
        $all[$i]['status'] = 'paid';
        $all[$i]['paid_at'] = $now;
        $all[$i]['meta'] = array_merge($invMeta, [
            'paid_event' => 'checkout',
            'payment_provider' => $provider,
            'payment_ref' => $ref,
        ]);
        $n++;
    }
    if ($n > 0) {
        hs_invoices_save($all);
    }

    return $n;
}

/**
 * After payment: ensure each line item has its own paid invoice (split if needed).
 *
 * @param array<string,mixed> $user
 * @param list<array{desc:string,qty:float,unit_nok:float}> $lines
 * @return list<array<string,mixed>>
 */
function hs_invoice_create_separate_paid(array $user, array $lines, string $lang, array $meta = []): array
{
    $out = [];
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $unit = (float) ($line['unit_nok'] ?? 0);
        if ($unit <= 0) {
            continue;
        }
        $desc = (string) ($line['desc'] ?? '');
        $type = 'plan';
        $itemMeta = $meta;
        if (str_contains(strtolower($desc), 'domain') || !empty($meta['domain_only_line'])) {
            $type = 'domain';
        } elseif (str_contains(strtolower($desc), 'add-on') || str_contains(strtolower($desc), 'service') || str_contains($desc, 'послуг')) {
            $type = 'services';
        }
        $inv = hs_invoice_create($user, $type, [[
            'desc' => $desc,
            'qty' => max(1, (float) ($line['qty'] ?? 1)),
            'unit_nok' => $unit,
        ]], $lang, 'paid', $itemMeta);
        if (is_array($inv)) {
            $out[] = $inv;
        }
    }

    return $out;
}