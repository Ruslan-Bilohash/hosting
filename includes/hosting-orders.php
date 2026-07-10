<?php
declare(strict_types=1);

require_once __DIR__ . '/domain-orders.php';

function hs_hosting_orders_file(): string
{
    return hs_data_file('hosting-orders');
}

/** @return list<array<string,mixed>> */
function hs_hosting_orders(): array
{
    $rows = hs_read_json(hs_hosting_orders_file());
    return is_array($rows) ? $rows : [];
}

/** @param array<string,mixed> $order */
function hs_hosting_order_log(array $order): bool
{
    $orders = hs_hosting_orders();
    $row = array_merge([
        'id' => hs_new_id('ho'),
        'created_at' => gmdate('c'),
    ], $order);
    array_unshift($orders, $row);
    if (count($orders) > 500) {
        $orders = array_slice($orders, 0, 500);
    }
    return hs_write_json(hs_hosting_orders_file(), $orders);
}

/** @return list<array<string,mixed>> */
function hs_hosting_orders_recent(int $limit = 15): array
{
    return array_slice(hs_hosting_orders(), 0, max(1, $limit));
}

/** @return array{plan_month:int,domain_pending:int,domain_total:int,revenue_nok:float} */
function hs_hosting_orders_stats(): array
{
    $planMonth = 0;
    $domainPending = 0;
    $domainTotal = 0;
    $revenue = 0.0;
    $monthStart = strtotime(gmdate('Y-m-01'));
    foreach (hs_hosting_orders() as $o) {
        $ts = strtotime((string) ($o['created_at'] ?? ''));
        if ($ts !== false && $ts >= $monthStart && ($o['type'] ?? '') === 'plan') {
            $planMonth++;
        }
        if (($o['type'] ?? '') === 'domain') {
            $domainTotal++;
            if (($o['status'] ?? '') === 'pending') {
                $domainPending++;
            }
        }
        if (($o['status'] ?? '') === 'completed') {
            $revenue += (float) ($o['price_nok'] ?? 0);
        }
    }
    $pendingDomains = hs_domain_orders_pending();
    $domainPending = max($domainPending, count($pendingDomains));
    return [
        'plan_month' => $planMonth,
        'domain_pending' => $domainPending,
        'domain_total' => $domainTotal,
        'revenue_nok' => round($revenue, 2),
    ];
}