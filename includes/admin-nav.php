<?php
declare(strict_types=1);

/**
 * Full Solaskinner admin navigation.
 *
 * @param array<string, mixed> $t
 * @return list<array{id:string,href:string,icon:string,label:string,section?:string}>
 */
function hs_admin_nav_items(array $t): array
{
    return [
        // Main
        [
            'id' => 'dashboard',
            'href' => hs_admin_url(),
            'icon' => 'fa-gauge-high',
            'label' => (string) ($t['admin_title'] ?? 'Dashboard'),
            'section' => 'main',
        ],
        [
            'id' => 'clients',
            'href' => hs_admin_url('clients.php'),
            'icon' => 'fa-users',
            'label' => (string) ($t['admin_clients'] ?? 'Clients'),
            'section' => 'main',
        ],
        [
            'id' => 'support',
            'href' => hs_admin_url('support.php'),
            'icon' => 'fa-headset',
            'label' => (string) ($t['admin_support_title'] ?? 'Support'),
            'section' => 'main',
        ],
        [
            'id' => 'tools',
            'href' => hs_admin_url('tools.php'),
            'icon' => 'fa-screwdriver-wrench',
            'label' => (string) ($t['admin_tools_title'] ?? 'API & tools'),
            'section' => 'main',
        ],
        [
            'id' => 'debugger',
            'href' => hs_admin_url('debugger.php'),
            'icon' => 'fa-bug',
            'label' => (string) ($t['admin_debugger_title'] ?? 'Debugger'),
            'section' => 'main',
        ],
        // Catalog
        [
            'id' => 'plans',
            'href' => hs_admin_url('plans.php'),
            'icon' => 'fa-layer-group',
            'label' => (string) ($t['admin_plans_tab'] ?? $t['admin_plans_title'] ?? 'Plans'),
            'section' => 'catalog',
        ],
        [
            'id' => 'services',
            'href' => hs_admin_url('plans.php', ['tab' => 'services']),
            'icon' => 'fa-puzzle-piece',
            'label' => (string) ($t['admin_services_tab'] ?? 'Services'),
            'section' => 'catalog',
        ],
        [
            'id' => 'coupons',
            'href' => hs_admin_url('coupons.php'),
            'icon' => 'fa-ticket',
            'label' => (string) ($t['admin_coupons_title'] ?? 'Coupons'),
            'section' => 'catalog',
        ],
        // Billing
        [
            'id' => 'invoices',
            'href' => hs_admin_url('invoices.php'),
            'icon' => 'fa-file-invoice-dollar',
            'label' => (string) ($t['admin_invoices_title'] ?? 'Invoices'),
            'section' => 'billing',
        ],
        [
            'id' => 'payments',
            'href' => hs_admin_url('payments.php'),
            'icon' => 'fa-credit-card',
            'label' => (string) ($t['admin_payments_title'] ?? 'Payments'),
            'section' => 'billing',
        ],
        [
            'id' => 'namecheap',
            'href' => hs_admin_url('namecheap.php'),
            'icon' => 'fa-globe',
            'label' => (string) ($t['admin_namecheap_title'] ?? $t['admin_hostinger_title'] ?? 'Domains API'),
            'section' => 'billing',
        ],
        // Infrastructure
        [
            'id' => 'mysql',
            'href' => hs_admin_url('mysql.php'),
            'icon' => 'fa-database',
            'label' => (string) ($t['admin_mysql_title'] ?? 'MySQL'),
            'section' => 'infra',
        ],
        [
            'id' => 'pma',
            'href' => hs_admin_url('pma-tool.php'),
            'icon' => 'fa-table',
            'label' => (string) ($t['admin_pma_title'] ?? 'phpMyAdmin'),
            'section' => 'infra',
        ],
        [
            'id' => 'cpanel-pool',
            'href' => hs_admin_url('cpanel-pool.php'),
            'icon' => 'fa-server',
            'label' => (string) ($t['admin_cpanel_pool_title'] ?? 'cPanel pool'),
            'section' => 'infra',
        ],
        [
            'id' => 'files',
            'href' => hs_admin_url('files.php'),
            'icon' => 'fa-folder-open',
            'label' => (string) ($t['admin_files_title'] ?? 'Server files'),
            'section' => 'infra',
        ],
        // Settings
        [
            'id' => 'settings',
            'href' => hs_admin_url('settings.php'),
            'icon' => 'fa-gear',
            'label' => (string) ($t['admin_settings_title'] ?? 'Site settings'),
            'section' => 'settings',
        ],
        [
            'id' => 'ops-guide',
            'href' => hs_admin_url('ops-guide.php'),
            'icon' => 'fa-book',
            'label' => (string) ($t['admin_ops_guide_title'] ?? 'Ops guide'),
            'section' => 'settings',
        ],
        [
            'id' => 'panel',
            'href' => hs_url(hs_panel_path('clients.php')),
            'icon' => 'fa-user-gear',
            'label' => (string) ($t['admin_panel_link'] ?? $t['nav_clients'] ?? 'Panel clients'),
            'section' => 'settings',
        ],
    ];
}

/**
 * @param array<string, mixed> $t
 */
function hs_admin_render_sidebar(array $t, string $active = 'dashboard'): string
{
    $sections = [
        'main' => (string) ($t['admin_nav_section_main'] ?? 'Main'),
        'catalog' => (string) ($t['admin_nav_section_catalog'] ?? 'Catalog'),
        'billing' => (string) ($t['admin_nav_section_billing'] ?? 'Billing & domains'),
        'infra' => (string) ($t['admin_nav_section_infra'] ?? 'Infrastructure'),
        'settings' => (string) ($t['admin_nav_section_settings'] ?? 'Settings'),
    ];

    $bySection = [];
    foreach (hs_admin_nav_items($t) as $item) {
        $sec = (string) ($item['section'] ?? 'main');
        $bySection[$sec][] = $item;
    }

    $brand = (string) ($t['brand'] ?? 'SolaSkinner');
    $html = '<aside class="hs-sidebar hp-sidebar hs-admin-sidebar" data-hs-sidebar data-admin-sidebar>'
        . '<a href="' . hs_h(hs_admin_url()) . '" class="hp-sidebar-brand">'
        . '<span class="hp-sidebar-brand-mark hs-logo-sun" aria-hidden="true"><i class="fa-solid fa-sun"></i></span>'
        . '<span class="hs-admin-brand-text">'
        . '<strong>' . hs_h($brand) . '</strong>'
        . '<span class="hp-muted">solaskinner.com</span>'
        . '</span>'
        . '</a>'
        . '<nav class="hp-nav hs-admin-side-nav" aria-label="' . hs_h((string) ($t['admin_title'] ?? 'Admin')) . '">';

    foreach ($sections as $secId => $secLabel) {
        $items = $bySection[$secId] ?? [];
        if ($items === []) {
            continue;
        }
        $html .= '<div class="hs-admin-side-section">'
            . '<div class="hs-admin-side-label">' . hs_h($secLabel) . '</div>'
            . '<ul class="hs-nav-list">';
        foreach ($items as $item) {
            $isActive = $item['id'] === $active;
            $html .= '<li><a href="' . hs_h($item['href']) . '" class="' . ($isActive ? 'active' : '') . '"'
                . ($isActive ? ' aria-current="page"' : '') . '>'
                . '<i class="fa-solid ' . hs_h($item['icon']) . '"></i> '
                . hs_h($item['label'])
                . '</a></li>';
        }
        $html .= '</ul></div>';
    }

    $html .= '</nav>'
        . '<div class="hs-sidebar-foot hs-admin-side-foot">'
        . '<a href="' . hs_h(hs_url(hs_panel_path(''))) . '"><i class="fa-solid fa-table-cells-large"></i> ' . hs_h($t['nav_panel'] ?? 'Panel') . '</a>'
        . '<a href="' . hs_h(hs_url()) . '"><i class="fa-solid fa-house"></i> ' . hs_h($t['breadcrumb_home'] ?? 'Home') . '</a>'
        . '<a href="' . hs_h(hs_admin_url('logout.php')) . '"><i class="fa-solid fa-right-from-bracket"></i> ' . hs_h($t['admin_logout'] ?? 'Logout') . '</a>'
        . '</div></aside>';

    return $html;
}

/** @param array<string, mixed> $t */
function hs_admin_render_nav(array $t, string $active = 'dashboard'): string
{
    return hs_admin_render_sidebar($t, $active);
}
