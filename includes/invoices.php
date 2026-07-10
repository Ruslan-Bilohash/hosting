<?php
declare(strict_types=1);

require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/plan-catalog.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/domain-store.php';

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
        return hs_db_load_collection('invoices');
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
    $out = [];
    foreach (hs_invoices_all() as $inv) {
        if (($inv['user_id'] ?? '') === $userId) {
            $out[] = $inv;
        }
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    return $out;
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
        'created_at' => gmdate('c'),
        'due_at' => gmdate('c', strtotime('+14 days')),
        'paid_at' => $status === 'paid' ? gmdate('c') : null,
        'lang' => $lang,
        'currency' => hs_currency_for_lang($lang),
        'subtotal_nok' => $totalNok,
        'total_nok' => $totalNok,
        'lines' => $normLines,
        'meta' => $meta,
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

    if ($event === 'plan_activated' || $event === 'plan_renewed' || $event === 'plan_renew') {
        if ($event === 'plan_renew') {
            $event = 'plan_renewed';
        }
        $plan = hs_plan($planId);
        $price = (float) ($plan['price_nok'] ?? 0);
        $label = (string) ($pt['plan_' . $planId] ?? $planId);
        $lines[] = [
            'desc' => ($pt['invoice_line_plan'] ?? 'Hosting plan') . ': ' . $label . ($event === 'plan_renewed' ? ' (' . ($pt['btn_renew'] ?? 'Renew') . ')' : ''),
            'qty' => 1,
            'unit_nok' => $price,
        ];
        if (!empty($payload['domain'])) {
            $dom = (string) $payload['domain'];
            $domPrice = (float) ($payload['domain_price_nok'] ?? hs_domain_price($dom) ?? 0);
            if ($domPrice > 0) {
                $lines[] = [
                    'desc' => ($pt['invoice_line_domain'] ?? 'Domain') . ': ' . $dom,
                    'qty' => 1,
                    'unit_nok' => $domPrice,
                ];
                $type = 'plan_domain';
            }
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

/** @param array<string,mixed> $invoice */
function hs_invoice_type_label(array $invoice, array $t): string
{
    return match ((string) ($invoice['type'] ?? '')) {
        'domain' => $t['invoice_type_domain'] ?? 'Domain',
        'plan_change' => $t['invoice_type_plan_change'] ?? 'Plan change',
        'plan_domain' => $t['invoice_type_plan_domain'] ?? 'Plan + domain',
        'renewal', 'plan' => $t['invoice_type_plan'] ?? 'Hosting',
        default => $t['invoice_type_other'] ?? 'Invoice',
    };
}