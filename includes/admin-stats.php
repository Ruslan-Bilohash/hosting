<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/resource-usage.php';
require_once __DIR__ . '/hosting-orders.php';
require_once __DIR__ . '/plan-catalog.php';

/** @return array{
 *   clients:int,sites:int,disk_mb:float,
 *   status:array{all:int,active:int,pending:int,suspended:int},
 *   orders:array{plan_month:int,domain_pending:int,revenue_nok:float},
 *   plans:array{total:int,active:int,landing:int,services:int,services_active:int},
 *   catalog_updated:string
 * }
 */
function hs_admin_stats(): array
{
    $users = hs_users();
    $sites = hs_sites();
    $totalDisk = 0.0;
    $statusCounts = ['all' => 0, 'active' => 0, 'pending' => 0, 'suspended' => 0];

    foreach ($users as $u) {
        if (!is_array($u)) {
            continue;
        }
        $st = (string) ($u['subscription_status'] ?? 'active');
        $statusCounts['all']++;
        if (isset($statusCounts[$st])) {
            $statusCounts[$st]++;
        }
        $uid = (string) ($u['id'] ?? '');
        $res = hs_resource_usage($u, hs_sites_for_user($uid));
        $totalDisk += (float) ($res['storage_used_mb'] ?? 0);
    }

    $catalog = hs_plan_catalog_load();
    $planRows = 0;
    $activePlans = 0;
    foreach ($catalog['plans'] as $pid => $plan) {
        if ($pid === 'domain' || ($plan['type'] ?? '') === 'domain_only') {
            continue;
        }
        $planRows++;
        if (!empty($plan['active'])) {
            $activePlans++;
        }
    }
    $activeServices = 0;
    foreach ($catalog['services'] as $svc) {
        if (!empty($svc['active'])) {
            $activeServices++;
        }
    }

    return [
        'clients' => count($users),
        'sites' => count($sites),
        'disk_mb' => round($totalDisk, 2),
        'status' => $statusCounts,
        'orders' => hs_hosting_orders_stats(),
        'plans' => [
            'total' => $planRows,
            'active' => $activePlans,
            'landing' => count(hs_plan_catalog_public_plans()),
            'services' => count($catalog['services']),
            'services_active' => $activeServices,
        ],
        'catalog_updated' => (string) ($catalog['updated_at'] ?? ''),
    ];
}

/** Render four stat boxes (admin dashboard strip). */
function hs_admin_render_stat_grid(array $items): string
{
    $html = '<div class="hs-clients-stats hs-admin-dash-stats">';
    foreach ($items as $item) {
        $html .= '<div class="hs-stat"><div class="label">' . hs_h((string) ($item['label'] ?? '')) . '</div>'
            . '<div class="value"' . (!empty($item['value_style']) ? ' style="' . hs_h((string) $item['value_style']) . '"' : '') . '>'
            . ($item['raw'] ?? false ? (string) ($item['value'] ?? '') : hs_h((string) ($item['value'] ?? '')))
            . '</div></div>';
    }
    return $html . '</div>';
}