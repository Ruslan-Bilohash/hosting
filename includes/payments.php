<?php
declare(strict_types=1);

require_once __DIR__ . '/payment-settings.php';
require_once __DIR__ . '/currency.php';

/** Demo checkout when no gateway configured or admin allows simulation. */
function hs_simulated_payment_allowed(): bool
{
    $settings = hs_payment_settings_load();
    if (array_key_exists('simulated_enabled', $settings)) {
        return !empty($settings['simulated_enabled']);
    }
    if (defined('HS_DEMO_MODE') && HS_DEMO_MODE) {
        return true;
    }
    return defined('HS_ALLOW_SIMULATED_PAYMENT') && HS_ALLOW_SIMULATED_PAYMENT;
}

function hs_payment_gateway_enabled(): bool
{
    if (hs_payment_stripe_enabled() || hs_payment_paypal_enabled()) {
        return true;
    }
    return (defined('HS_STRIPE_SECRET') && HS_STRIPE_SECRET !== '')
        || (defined('HS_VIPPS_ENABLED') && HS_VIPPS_ENABLED);
}

/** Stripe Checkout minimum for EUR (and similar) card charges. */
function hs_payment_stripe_min_amount_eur(): float
{
    return 0.5;
}

/**
 * Convert NOK catalog/base amount into the gateway charge currency.
 * Rates from hs_exchange_rates() are NOK → target (1 NOK = X).
 */
function hs_payment_nok_to_charge(float $nok, ?string $currency = null): float
{
    $currency = strtoupper($currency ?? hs_payment_charge_currency());
    $nok = max(0.0, $nok);
    if ($nok <= 0) {
        return 0.0;
    }
    $rates = hs_exchange_rates();
    $amount = match ($currency) {
        'NOK' => $nok,
        'EUR' => $nok * max(0.001, (float) ($rates['EUR'] ?? 0.088)),
        'USD' => $nok * max(0.001, (float) ($rates['USD'] ?? 0.095)),
        'UAH' => $nok * max(0.001, (float) ($rates['UAH'] ?? 3.85)),
        default => $nok * max(0.001, (float) ($rates['EUR'] ?? 0.088)),
    };

    return $currency === 'UAH' ? (float) round($amount, 0) : round($amount, 2);
}

/** Convert fixed EUR amount (domains) into the gateway charge currency. */
function hs_payment_eur_to_charge(float $eur, ?string $currency = null): float
{
    $currency = strtoupper($currency ?? hs_payment_charge_currency());
    $eur = max(0.0, $eur);
    if ($eur <= 0) {
        return 0.0;
    }
    if ($currency === 'EUR') {
        return round($eur, 2);
    }
    $rates = hs_exchange_rates();
    $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));

    return hs_payment_nok_to_charge($eur / $eurRate, $currency);
}

/**
 * Enforce Stripe-style minimum (~€0.50 or equivalent) in the charge currency.
 */
function hs_payment_apply_charge_minimum(float $amount, ?string $currency = null): float
{
    $currency = strtoupper($currency ?? hs_payment_charge_currency());
    $amount = max(0.0, $amount);
    if ($amount <= 0) {
        return 0.0;
    }
    $minEur = hs_payment_stripe_min_amount_eur();
    if ($currency === 'EUR') {
        return $amount < $minEur ? $minEur : round($amount, 2);
    }
    $minCharge = hs_payment_eur_to_charge($minEur, $currency);
    if ($amount < $minCharge) {
        return $minCharge;
    }

    return $currency === 'UAH' ? (float) round($amount, 0) : round($amount, 2);
}

/** @deprecated Use hs_payment_apply_charge_minimum — kept for callers expecting EUR-only min. */
function hs_payment_apply_stripe_min_eur(float $amountEur): float
{
    return hs_payment_apply_charge_minimum($amountEur, 'EUR');
}

/** Human-readable charge amount with currency symbol (gateway currency). */
function hs_payment_format_charge(float $amount, ?string $currency = null): string
{
    $currency = strtoupper($currency ?? hs_payment_charge_currency());
    $amount = max(0.0, $amount);

    return match ($currency) {
        'NOK' => number_format((float) round($amount), 0, ',', ' ') . ' kr',
        'UAH' => number_format((float) round($amount), 0, '.', ' ') . ' ₴',
        'USD' => '$' . number_format($amount, 2, '.', ' '),
        'EUR' => '€' . number_format($amount, 2, '.', ' '),
        default => $currency . ' ' . number_format($amount, 2, '.', ' '),
    };
}

/**
 * Convert priced checkout (hosting NOK + domain EUR) into gateway charge amounts.
 *
 * @return array{
 *   hosting_eur:float,domain_eur:float,total_eur:float,amount:float,currency:string,
 *   hosting_charge:float,domain_charge:float
 * }
 */
function hs_payment_checkout_amounts(array $priced, bool $wantHosting, bool $wantDomain): array
{
    $currency = hs_payment_charge_currency();
    $hosting = $wantHosting
        ? hs_payment_nok_to_charge((float) ($priced['hosting_nok'] ?? 0), $currency)
        : 0.0;
    $domain = $wantDomain
        ? hs_payment_eur_to_charge((float) ($priced['domain_eur'] ?? 0), $currency)
        : 0.0;
    $total = $hosting + $domain;
    if ($currency === 'UAH') {
        $hosting = (float) round($hosting, 0);
        $domain = (float) round($domain, 0);
        $total = (float) round($total, 0);
    } else {
        $hosting = round($hosting, 2);
        $domain = round($domain, 2);
        $total = round($total, 2);
    }

    // Stripe rejects charges under ~€0.50 (or equivalent).
    if ($total > 0) {
        $minTotal = hs_payment_apply_charge_minimum($total, $currency);
        if ($minTotal > $total) {
            // Bump hosting share first when present.
            if ($wantHosting && $hosting > 0) {
                $hosting = round($hosting + ($minTotal - $total), $currency === 'UAH' ? 0 : 2);
            } elseif ($wantDomain && $domain > 0) {
                $domain = round($domain + ($minTotal - $total), $currency === 'UAH' ? 0 : 2);
            }
            $total = $minTotal;
        }
    }

    return [
        // Keys hosting_eur/domain_eur/total_eur kept for BC — values are in charge currency.
        'hosting_eur' => $hosting,
        'domain_eur' => $domain,
        'total_eur' => $total,
        'hosting_charge' => $hosting,
        'domain_charge' => $domain,
        'amount' => $total,
        'currency' => $currency,
    ];
}

function hs_payment_pending_file(): string
{
    return HS_DATA_DIR . '/payment-pending.json';
}

/** @return array<string, array<string, mixed>> */
function hs_payment_pending_all(): array
{
    $file = hs_payment_pending_file();
    if (!is_readable($file)) {
        return [];
    }
    $raw = json_decode((string) file_get_contents($file), true);
    return is_array($raw) ? $raw : [];
}

/** @param array<string, mixed> $row */
function hs_payment_pending_save(array $row): bool
{
    $id = (string) ($row['id'] ?? '');
    if ($id === '') {
        return false;
    }
    $all = hs_payment_pending_all();
    $all[$id] = $row;
    if (!is_dir(HS_DATA_DIR)) {
        @mkdir(HS_DATA_DIR, 0750, true);
    }
    return file_put_contents(
        hs_payment_pending_file(),
        json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    ) !== false;
}

/** @return array<string, mixed>|null */
function hs_payment_pending_get(string $id): ?array
{
    $all = hs_payment_pending_all();
    return is_array($all[$id] ?? null) ? $all[$id] : null;
}

function hs_payment_pending_mark(string $id, string $status, ?string $providerRef = null): bool
{
    $row = hs_payment_pending_get($id);
    if ($row === null) {
        return false;
    }
    $row['status'] = $status;
    $row['updated_at'] = gmdate('c');
    if ($providerRef !== null) {
        $row['provider_ref'] = $providerRef;
    }
    return hs_payment_pending_save($row);
}

function hs_payment_new_pending_id(): string
{
    return 'pay_' . bin2hex(random_bytes(12));
}

/** @param array<string, mixed> $user */
function hs_payment_checkout_description(array $user, string $orderType, string $pendingDomain): string
{
    require_once __DIR__ . '/domain-cart.php';
    $pendingDomains = hs_user_pending_domains($user);
    if ($pendingDomain === '' && $pendingDomains !== []) {
        $pendingDomain = $pendingDomains[0];
    }
    $parts = [];
    if ($orderType === 'domain') {
        $parts[] = count($pendingDomains) > 1
            ? 'Domains: ' . implode(', ', $pendingDomains)
            : 'Domain: ' . $pendingDomain;
    } elseif ($orderType === 'hosting') {
        $parts[] = 'Hosting: ' . (string) ($user['plan'] ?? 'starter');
    } else {
        $parts[] = 'Hosting + domain';
        if ($pendingDomain !== '') {
            $parts[] = $pendingDomain;
        }
    }
    return implode(' — ', $parts);
}

/**
 * @param array<string, mixed> $user
 * @param array{panel_domain_purchase?:bool,preserve_hosting?:bool,order_type?:?string} $extra
 */
function hs_payment_create_pending(
    array $user,
    string $provider,
    array $priced,
    ?array $couponRow,
    bool $wantHosting,
    bool $wantDomain,
    string $lang,
    array $extra = []
): array {
    $amounts = hs_payment_checkout_amounts($priced, $wantHosting, $wantDomain);
    if ($amounts['total_eur'] <= 0) {
        return ['ok' => false, 'error' => 'zero_amount'];
    }

    $orderType = hs_user_order_type($user);
    if (!empty($extra['order_type']) && is_string($extra['order_type'])) {
        $orderType = (string) $extra['order_type'];
    }
    $id = hs_payment_new_pending_id();
    $pendingDomains = function_exists('hs_user_pending_domains')
        ? hs_user_pending_domains($user)
        : [];
    $row = [
        'id' => $id,
        'user_id' => (string) ($user['id'] ?? ''),
        'provider' => $provider,
        'status' => 'pending',
        'amount_eur' => $amounts['total_eur'],
        'currency' => $amounts['currency'],
        'order_type' => $orderType,
        'want_hosting' => $wantHosting,
        'want_domain' => $wantDomain,
        'pending_domain' => (string) ($pendingDomains[0] ?? $user['pending_domain'] ?? ''),
        'pending_domains' => $pendingDomains,
        'coupon_code' => $couponRow !== null ? (string) ($couponRow['code'] ?? '') : '',
        'priced' => $priced,
        'lang' => $lang,
        'created_at' => gmdate('c'),
    ];
    if (!empty($extra['panel_domain_purchase'])) {
        $row['panel_domain_purchase'] = true;
        $row['preserve_hosting'] = true;
        $row['want_hosting'] = false;
        $row['want_domain'] = true;
        $row['order_type'] = 'domain';
    } elseif (!empty($extra['preserve_hosting'])) {
        $row['preserve_hosting'] = true;
    }
    if (!hs_payment_pending_save($row)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }
    return ['ok' => true, 'pending' => $row, 'amounts' => $amounts];
}

/** @param array<string, mixed> $user */
function hs_payment_create_pending_renew(array $user, string $provider, int $months, float $totalNok, float $totalEur, string $lang): array
{
    if ($totalEur <= 0 && $totalNok <= 0) {
        return ['ok' => false, 'error' => 'zero_amount'];
    }
    $currency = hs_payment_charge_currency();
    // Prefer NOK base (catalog); fall back to fixed EUR if provided.
    if ($totalNok > 0) {
        $amount = hs_payment_nok_to_charge($totalNok, $currency);
    } else {
        $amount = hs_payment_eur_to_charge($totalEur, $currency);
    }
    $amount = hs_payment_apply_charge_minimum($amount, $currency);
    if ($amount <= 0) {
        return ['ok' => false, 'error' => 'zero_amount'];
    }
    $months = max(1, min(36, $months));
    $id = hs_payment_new_pending_id();
    $row = [
        'id' => $id,
        'user_id' => (string) ($user['id'] ?? ''),
        'provider' => $provider,
        'status' => 'pending',
        'amount_eur' => $amount, // charge amount in $currency (legacy key name)
        'currency' => $currency,
        'order_type' => 'plan_renew',
        'renew_months' => $months,
        'want_hosting' => true,
        'want_domain' => false,
        'pending_domain' => '',
        'coupon_code' => '',
        'priced' => ['hosting_nok' => $totalNok, 'hosting_discount_nok' => 0, 'domain_eur' => 0, 'domain_discount_eur' => 0],
        'lang' => $lang,
        'created_at' => gmdate('c'),
    ];
    if (!hs_payment_pending_save($row)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }

    return ['ok' => true, 'pending' => $row];
}

/**
 * Create a payment session for a single pending invoice.
 *
 * @param array<string,mixed> $user
 * @param array<string,mixed> $invoice
 * @return array{ok:bool,error?:string,pending?:array<string,mixed>}
 */
function hs_payment_create_pending_invoice(array $user, array $invoice, string $provider, string $lang): array
{
    $invoiceId = (string) ($invoice['id'] ?? '');
    if ($invoiceId === '' || (string) ($invoice['status'] ?? '') === 'paid') {
        return ['ok' => false, 'error' => 'invoice'];
    }
    $totalNok = (float) ($invoice['total_nok'] ?? 0);
    if ($totalNok <= 0) {
        return ['ok' => false, 'error' => 'zero_amount'];
    }
    $currency = hs_payment_charge_currency();
    $amount = hs_payment_apply_charge_minimum(
        hs_payment_nok_to_charge($totalNok, $currency),
        $currency
    );
    if ($amount <= 0) {
        return ['ok' => false, 'error' => 'zero_amount'];
    }
    $id = hs_payment_new_pending_id();
    $row = [
        'id' => $id,
        'user_id' => (string) ($user['id'] ?? ''),
        'provider' => $provider,
        'status' => 'pending',
        'amount_eur' => $amount, // charge amount in $currency (legacy key name)
        'currency' => $currency,
        'order_type' => 'invoice',
        'invoice_id' => $invoiceId,
        'invoice_number' => (string) ($invoice['number'] ?? ''),
        'want_hosting' => in_array((string) ($invoice['type'] ?? ''), ['plan', 'plan_domain', 'renewal', 'services', 'plan_change'], true),
        'want_domain' => in_array((string) ($invoice['type'] ?? ''), ['domain', 'plan_domain'], true),
        'pending_domain' => (string) ((is_array($invoice['meta'] ?? null) ? ($invoice['meta']['domain'] ?? '') : '')),
        'coupon_code' => '',
        'priced' => [
            'hosting_nok' => $totalNok,
            'hosting_discount_nok' => 0,
            'domain_eur' => 0,
            'domain_discount_eur' => 0,
        ],
        'lang' => $lang,
        'created_at' => gmdate('c'),
        'success_path' => hs_panel_path('invoices.php') . '?paid=1',
        'cancel_path' => hs_panel_path('invoice-pay.php') . '?id=' . rawurlencode($invoiceId),
    ];
    if (!hs_payment_pending_save($row)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }

    return ['ok' => true, 'pending' => $row];
}

/** @param array<string, mixed> $pending */
function hs_payment_pending_cancel_url(array $pending): string
{
    if (($pending['order_type'] ?? '') === 'plan_renew') {
        return hs_absolute_url(hs_panel_path('plan-renew.php'), ['cancelled' => '1']);
    }
    if (($pending['order_type'] ?? '') === 'invoice') {
        $invId = (string) ($pending['invoice_id'] ?? '');
        if ($invId !== '') {
            return hs_absolute_url(hs_panel_path('invoice-pay.php'), ['id' => $invId, 'cancelled' => '1']);
        }

        return hs_absolute_url(hs_panel_path('invoices.php'), ['cancelled' => '1']);
    }
    if (!empty($pending['panel_domain_purchase'])
        || (($pending['order_type'] ?? '') === 'domain' && empty($pending['want_hosting']))) {
        return hs_absolute_url(hs_panel_path('domains.php'), ['tab' => 'register', 'cancelled' => '1']);
    }
    if (!empty($pending['cancel_path'])) {
        return hs_absolute_url((string) $pending['cancel_path'], ['cancelled' => '1']);
    }

    return hs_absolute_url(hs_panel_path('activate.php'), ['cancelled' => '1']);
}

/** @param array<string, mixed> $pending */
function hs_payment_pending_success_redirect(array $pending): string
{
    if (($pending['order_type'] ?? '') === 'plan_renew') {
        return hs_panel_path('plan-renew.php');
    }
    if (($pending['order_type'] ?? '') === 'invoice') {
        return hs_panel_path('invoices.php') . '?paid=1';
    }
    if (!empty($pending['panel_domain_purchase'])
        || (($pending['order_type'] ?? '') === 'domain' && empty($pending['want_hosting']))) {
        return hs_panel_path('domains.php') . '?tab=overview&paid=1';
    }
    if (!empty($pending['success_path'])) {
        return (string) $pending['success_path'];
    }

    return hs_panel_path('');
}

function hs_payment_renew_description(array $user, int $months): string
{
    $planId = (string) ($user['plan'] ?? 'starter');
    $brand = defined('HS_SITE_NAME') ? HS_SITE_NAME : 'Hosting';
    $label = function_exists('hs_plan_hosting_label')
        ? hs_plan_hosting_label($planId, ['plan_' . $planId => $planId])
        : $planId;

    return $brand . ' — ' . $label . ' (' . $months . ' mo)';
}

function hs_payment_http_json(string $method, string $url, array $headers, ?array $body = null): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init'];
    }
    $hdrs = $headers;
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $hdrs,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if (!is_string($raw)) {
        return ['ok' => false, 'error' => $err !== '' ? $err : 'empty_response', 'http_code' => $code];
    }
    $json = json_decode($raw, true);
    return [
        'ok' => $code >= 200 && $code < 300,
        'http_code' => $code,
        'body' => is_array($json) ? $json : null,
        'raw' => $raw,
        'error' => $code >= 400 ? ($raw) : '',
    ];
}

function hs_payment_stripe_form_body(array $data, string $prefix = ''): string
{
    $parts = [];
    foreach ($data as $key => $value) {
        $k = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
        if (is_array($value)) {
            $parts[] = hs_payment_stripe_form_body($value, $k);
        } else {
            $parts[] = rawurlencode($k) . '=' . rawurlencode((string) $value);
        }
    }
    return implode('&', $parts);
}

/** @param array<string, mixed> $pending */
function hs_stripe_create_checkout_session(array $pending, string $description): array
{
    $secret = hs_payment_stripe_secret_key();
    if ($secret === '') {
        return ['ok' => false, 'error' => 'stripe_not_configured'];
    }

    $pendingId = (string) ($pending['id'] ?? '');
    $currency = strtolower((string) ($pending['currency'] ?? 'eur'));
    $amount = (float) ($pending['amount_eur'] ?? 0);
    if ($currency === 'eur') {
        $amount = hs_payment_apply_stripe_min_eur($amount);
    }
    $minor = (int) round($amount * 100);
    $minMinor = $currency === 'eur' ? (int) round(hs_payment_stripe_min_amount_eur() * 100) : 50;
    if ($minor < $minMinor) {
        return ['ok' => false, 'error' => 'amount_too_small', 'amount' => $amount, 'minor' => $minor];
    }

    // {CHECKOUT_SESSION_ID} must stay literal — http_build_query would encode braces and break Stripe replace
    $successUrl = hs_absolute_url('payment-return.php', [
        'provider' => 'stripe',
        'pending' => $pendingId,
    ]);
    $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';
    $cancelUrl = hs_payment_pending_cancel_url($pending);

    $fields = [
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $pendingId,
        'metadata[pending_id]' => $pendingId,
        'metadata[user_id]' => (string) ($pending['user_id'] ?? ''),
        'line_items[0][quantity]' => 1,
        'line_items[0][price_data][currency]' => $currency,
        'line_items[0][price_data][unit_amount]' => (string) $minor,
        'line_items[0][price_data][product_data][name]' => $description,
    ];

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secret,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($raw) || $code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => 'stripe_api', 'raw' => is_string($raw) ? $raw : '', 'http' => $code];
    }
    $body = json_decode($raw, true);
    if (!is_array($body) || empty($body['id']) || empty($body['url'])) {
        return ['ok' => false, 'error' => 'stripe_invalid_response'];
    }

    // Persist charged amount if we bumped to Stripe min
    if (abs($amount - (float) ($pending['amount_eur'] ?? 0)) > 0.001) {
        $pending['amount_eur'] = $amount;
        hs_payment_pending_save($pending);
    }
    hs_payment_pending_mark($pendingId, 'pending', (string) $body['id']);

    return [
        'ok' => true,
        'session_id' => (string) $body['id'],
        'url' => (string) $body['url'],
        'amount_eur' => $amount,
    ];
}

function hs_stripe_retrieve_session(string $sessionId): array
{
    $secret = hs_payment_stripe_secret_key();
    if ($secret === '') {
        return ['ok' => false, 'error' => 'stripe_not_configured'];
    }
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId));
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret],
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($raw) || $code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => 'stripe_api'];
    }
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return ['ok' => false, 'error' => 'stripe_invalid_response'];
    }
    return ['ok' => true, 'session' => $body];
}

function hs_stripe_verify_webhook(string $payload, string $sigHeader): array
{
    $secret = hs_payment_stripe_webhook_secret();
    if ($secret === '') {
        return ['ok' => false, 'error' => 'webhook_secret_missing'];
    }
    $parts = [];
    foreach (explode(',', $sigHeader) as $item) {
        $kv = explode('=', trim($item), 2);
        if (count($kv) === 2) {
            $parts[$kv[0]] = $kv[1];
        }
    }
    $timestamp = (string) ($parts['t'] ?? '');
    $signature = (string) ($parts['v1'] ?? '');
    if ($timestamp === '' || $signature === '') {
        return ['ok' => false, 'error' => 'invalid_signature_header'];
    }
    $signed = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signed, $secret);
    if (!hash_equals($expected, $signature)) {
        return ['ok' => false, 'error' => 'signature_mismatch'];
    }
    $event = json_decode($payload, true);
    if (!is_array($event)) {
        return ['ok' => false, 'error' => 'invalid_json'];
    }
    return ['ok' => true, 'event' => $event];
}

function hs_paypal_api_base(): string
{
    return hs_payment_is_test_mode()
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com';
}

function hs_paypal_access_token(): array
{
    $clientId = hs_payment_paypal_client_id();
    $secret = hs_payment_paypal_secret();
    if ($clientId === '' || $secret === '') {
        return ['ok' => false, 'error' => 'paypal_not_configured'];
    }
    $ch = curl_init(hs_paypal_api_base() . '/v1/oauth2/token');
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => $clientId . ':' . $secret,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($raw) || $code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => 'paypal_auth', 'http_code' => $code];
    }
    $body = json_decode($raw, true);
    if (!is_array($body) || empty($body['access_token'])) {
        return ['ok' => false, 'error' => 'paypal_invalid_token'];
    }
    return ['ok' => true, 'token' => (string) $body['access_token']];
}

/** @param array<string, mixed> $pending */
function hs_paypal_create_order(array $pending, string $description): array
{
    $auth = hs_paypal_access_token();
    if (!$auth['ok']) {
        return $auth;
    }
    $pendingId = (string) ($pending['id'] ?? '');
    $currency = strtoupper((string) ($pending['currency'] ?? 'EUR'));
    $amount = number_format((float) ($pending['amount_eur'] ?? 0), 2, '.', '');

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $pendingId,
            'description' => $description,
            'amount' => [
                'currency_code' => $currency,
                'value' => $amount,
            ],
        ]],
        'application_context' => [
            'brand_name' => HS_SITE_NAME,
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW',
            'return_url' => hs_absolute_url('payment-return.php', ['provider' => 'paypal', 'pending' => $pendingId]),
            'cancel_url' => hs_payment_pending_cancel_url($pending),
        ],
    ];

    $res = hs_payment_http_json(
        'POST',
        hs_paypal_api_base() . '/v2/checkout/orders',
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['token'],
        ],
        $payload
    );
    if (!$res['ok'] || !is_array($res['body'])) {
        return ['ok' => false, 'error' => 'paypal_create_order', 'detail' => $res['raw'] ?? ''];
    }
    $orderId = (string) ($res['body']['id'] ?? '');
    $approve = '';
    foreach ($res['body']['links'] ?? [] as $link) {
        if (is_array($link) && ($link['rel'] ?? '') === 'approve') {
            $approve = (string) ($link['href'] ?? '');
            break;
        }
    }
    if ($orderId === '' || $approve === '') {
        return ['ok' => false, 'error' => 'paypal_no_approve_url'];
    }
    hs_payment_pending_mark($pendingId, 'pending', $orderId);
    return ['ok' => true, 'order_id' => $orderId, 'url' => $approve];
}

function hs_paypal_capture_order(string $orderId): array
{
    $auth = hs_paypal_access_token();
    if (!$auth['ok']) {
        return $auth;
    }
    $ch = curl_init(hs_paypal_api_base() . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture');
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['token'],
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $res = [
        'ok' => is_string($raw) && $code >= 200 && $code < 300,
        'body' => is_string($raw) ? json_decode($raw, true) : null,
        'raw' => is_string($raw) ? $raw : '',
    ];
    if (!$res['ok'] || !is_array($res['body'])) {
        return ['ok' => false, 'error' => 'paypal_capture', 'detail' => $res['raw'] ?? ''];
    }
    $status = (string) ($res['body']['status'] ?? '');
    if ($status !== 'COMPLETED') {
        return ['ok' => false, 'error' => 'paypal_not_completed', 'status' => $status];
    }
    return ['ok' => true, 'order' => $res['body']];
}

/** @return array{ok:bool,error?:string,detail?:string} */
function hs_payment_test_stripe(): array
{
    $secret = hs_payment_stripe_secret_key();
    if ($secret === '') {
        return ['ok' => false, 'error' => 'missing_secret'];
    }
    $ch = curl_init('https://api.stripe.com/v1/balance');
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret],
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'stripe_api_' . $code, 'detail' => is_string($raw) ? $raw : ''];
}

/** @return array{ok:bool,error?:string} */
function hs_payment_test_paypal(): array
{
    $auth = hs_paypal_access_token();
    return $auth['ok'] ? ['ok' => true] : ['ok' => false, 'error' => (string) ($auth['error'] ?? 'paypal_auth')];
}

/** Complete pending payment and fulfill order. */
function hs_payment_complete_pending(string $pendingId, string $provider, string $providerRef): array
{
    $pending = hs_payment_pending_get($pendingId);
    if ($pending === null) {
        return ['ok' => false, 'error' => 'pending_not_found'];
    }
    if (($pending['status'] ?? '') === 'completed') {
        return ['ok' => true, 'already' => true];
    }
    if (($pending['provider'] ?? '') !== $provider) {
        return ['ok' => false, 'error' => 'provider_mismatch'];
    }

    $userId = (string) ($pending['user_id'] ?? '');
    $user = hs_user_by_id($userId);
    if ($user === null) {
        return ['ok' => false, 'error' => 'user_not_found'];
    }

    require_once __DIR__ . '/payment-fulfill.php';

    if (($pending['order_type'] ?? '') === 'plan_renew') {
        $priced = is_array($pending['priced'] ?? null) ? $pending['priced'] : [];
        $months = (int) ($pending['renew_months'] ?? 1);
        $renewLang = (string) ($pending['lang'] ?? 'en');
        require_once __DIR__ . '/plan-renew-ui.php';
        $renewT = function_exists('hs_support_panel_strings') ? hs_support_panel_strings($renewLang) : [];
        $renewQuote = hs_plan_renew_quote($user, $months, $renewT, $renewLang);
        $fulfill = hs_payment_fulfill_renew($user, [
            'lang' => $renewLang,
            'payment_provider' => $provider,
            'payment_ref' => $providerRef,
            'months' => $months,
            'price_nok' => (float) ($priced['hosting_nok'] ?? $renewQuote['totalNok']),
            'invoice_lines' => $renewQuote['invoiceLines'],
        ]);
    } elseif (($pending['order_type'] ?? '') === 'invoice') {
        require_once __DIR__ . '/invoices.php';
        $invoiceId = (string) ($pending['invoice_id'] ?? '');
        $invoice = $invoiceId !== '' ? hs_invoice_by_id($invoiceId) : null;
        if ($invoice === null || (string) ($invoice['user_id'] ?? '') !== $userId) {
            return ['ok' => false, 'error' => 'invoice_not_found'];
        }
        $fulfill = hs_payment_fulfill_invoice($user, $invoice, [
            'lang' => (string) ($pending['lang'] ?? 'uk'),
            'payment_provider' => $provider,
            'payment_ref' => $providerRef,
        ]);
    } else {
        $priced = is_array($pending['priced'] ?? null) ? $pending['priced'] : [];
        $couponRow = null;
        $couponCode = (string) ($pending['coupon_code'] ?? '');
        if ($couponCode !== '') {
            require_once __DIR__ . '/coupons.php';
            $valid = hs_coupon_validate(
                $couponCode,
                !empty($pending['want_hosting']),
                !empty($pending['want_domain']),
                null
            );
            if ($valid['ok']) {
                $couponRow = $valid['coupon'];
            }
        }

        $fulfill = hs_payment_fulfill_checkout($user, $priced, $couponRow, [
            'lang' => (string) ($pending['lang'] ?? 'en'),
            'payment_provider' => $provider,
            'payment_ref' => $providerRef,
            'panel_domain_purchase' => !empty($pending['panel_domain_purchase']),
            'preserve_hosting' => !empty($pending['preserve_hosting']),
        ]);
    }
    if (!$fulfill['ok']) {
        return $fulfill;
    }

    hs_payment_pending_mark($pendingId, 'completed', $providerRef);
    return ['ok' => true, 'user' => $fulfill['user'] ?? $user];
}