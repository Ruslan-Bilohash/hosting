<?php
declare(strict_types=1);

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/invoices.php';
require_once __DIR__ . '/hosting-orders.php';
require_once __DIR__ . '/domain-orders.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/resource-usage.php';
require_once __DIR__ . '/currency.php';

function hs_panel_admin_dashboard_block(array $t, string $lang): string
{
    $stats = hs_hosting_orders_stats();
    $users = hs_users();
    $sites = hs_sites();
    $totalDisk = 0.0;
    foreach ($users as $u) {
        $uid = (string) ($u['id'] ?? '');
        $res = hs_resource_usage($u, hs_sites_for_user($uid));
        $totalDisk += (float) ($res['storage_used_mb'] ?? 0);
    }

    $recent = hs_hosting_orders_recent(12);
    $domainPending = hs_domain_orders_pending();
    $rows = '';
    foreach ($recent as $o) {
        $type = (string) ($o['type'] ?? '');
        $label = $type === 'plan'
            ? ($t['notify_order_plan'] ?? 'Plan') . ': ' . ($t['plan_' . ($o['plan'] ?? 'starter')] ?? $o['plan'] ?? '')
            : ($t['notify_order_domain'] ?? 'Domain') . ': ' . (string) ($o['domain'] ?? '');
        $price = hs_format_nok_price((float) ($o['price_nok'] ?? 0), $lang);
        $status = (string) ($o['status'] ?? 'completed');
        $rows .= '<tr><td>' . hs_h(hs_format_date((string) ($o['created_at'] ?? ''))) . '</td>'
            . '<td><strong>' . hs_h((string) ($o['username'] ?? '')) . '</strong></td>'
            . '<td>' . hs_h($label) . '</td>'
            . '<td>' . hs_h($price) . '</td>'
            . '<td><span class="hs-plan-status hs-plan-status-' . hs_h($status === 'pending' ? 'pending' : 'active') . '">' . hs_h($status) . '</span></td></tr>';
    }
    foreach (array_slice($domainPending, 0, 5) as $d) {
        if (array_filter($recent, static fn(array $r): bool => ($r['domain'] ?? '') === ($d['domain'] ?? ''))) {
            continue;
        }
        $price = hs_format_nok_price((float) ($d['price'] ?? 0), $lang);
        $rows .= '<tr><td>' . hs_h(hs_format_date((string) ($d['ordered_at'] ?? ''))) . '</td>'
            . '<td><strong>' . hs_h((string) ($d['username'] ?? '')) . '</strong></td>'
            . '<td>' . hs_h(($t['notify_order_domain'] ?? 'Domain') . ': ' . (string) ($d['domain'] ?? '')) . '</td>'
            . '<td>' . hs_h($price) . '</td>'
            . '<td><span class="hs-plan-status hs-plan-status-pending">' . hs_h($t['admin_domain_orders_status_pending'] ?? 'Pending') . '</span></td></tr>';
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="5" class="hp-muted">' . hs_h($t['admin_orders_empty'] ?? 'No orders yet.') . '</td></tr>';
    }

    return '<section class="hs-admin-dash-panel">'
        . '<div class="hs-admin-dash-head"><h2><i class="fa-solid fa-shield-halved"></i> ' . hs_h($t['admin_dash_title'] ?? 'Administrator') . '</h2>'
        . '<div class="hs-admin-dash-links">'
        . '<a href="' . hs_h(hs_admin_url('clients.php')) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-users"></i> ' . hs_h($t['admin_clients'] ?? '') . '</a>'
        . '<a href="' . hs_h(hs_admin_url('plans.php')) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-layer-group"></i> ' . hs_h($t['admin_plans_title'] ?? '') . '</a>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('clients.php'))) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-user-gear"></i> ' . hs_h($t['nav_clients'] ?? '') . '</a>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('invoices.php'))) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-file-invoice-dollar"></i> ' . hs_h($t['nav_invoices'] ?? '') . '</a>'
        . '</div></div>'
        . '<div class="hs-grid hs-grid-4 hs-admin-dash-stats">'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['admin_clients'] ?? '') . '</div><div class="value">' . count($users) . '</div></div>'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['admin_sites'] ?? '') . '</div><div class="value">' . count($sites) . '</div></div>'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['admin_dash_orders_month'] ?? 'Plan orders (month)') . '</div><div class="value">' . (int) $stats['plan_month'] . '</div></div>'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['admin_dash_revenue'] ?? 'Revenue') . '</div><div class="value" style="font-size:1.1rem">' . hs_h(hs_format_nok_price((float) $stats['revenue_nok'], $lang)) . '</div></div>'
        . '</div>'
        . '<div class="hp-card" style="margin-top:1rem"><h3 class="hp-card-title">' . hs_h($t['admin_dash_recent_orders'] ?? 'Latest orders') . '</h3>'
        . '<div class="hp-card-body"><div class="hs-table-wrap"><table class="hs-table"><thead><tr>'
        . '<th>' . hs_h($t['admin_domain_orders_col_ordered'] ?? 'Date') . '</th><th>' . hs_h($t['admin_domain_orders_col_client'] ?? 'Client') . '</th>'
        . '<th>' . hs_h($t['checkout_title'] ?? 'Order') . '</th><th>' . hs_h($t['plan_price'] ?? 'Price') . '</th><th>' . hs_h($t['admin_client_status'] ?? 'Status') . '</th>'
        . '</tr></thead><tbody>' . $rows . '</tbody></table></div></div></section>';
}