<?php
declare(strict_types=1);

require_once __DIR__ . '/panel-tabs.php';

/** @return list<array{type:string,label?:string,items?:list<array{key:string,url:string,icon:string,label:string,icon_brand?:bool}>}> */
function hs_panel_nav_groups(array $t): array
{
    $wpIcon = static fn(string $id): string => match ($id) {
        'security' => 'fa-shield-halved',
        'ai' => 'fa-wand-magic-sparkles',
        'staging' => 'fa-flask',
        'copy' => 'fa-copy',
        'presets' => 'fa-sliders',
        'learn' => 'fa-graduation-cap',
        default => 'fa-wordpress',
    };

    return [
        [
            'type' => 'item',
            'items' => [[
                'key' => 'dashboard',
                'url' => hs_panel_path(),
                'icon' => 'fa-gauge-high',
                'label' => $t['nav_dashboard'] ?? 'Dashboard',
            ]],
        ],
        [
            'type' => 'group',
            'slug' => 'wordpress',
            'icon' => 'fa-wordpress',
            'icon_brand' => true,
            'label' => 'WordPress',
            'items' => array_map(static function (array $tab) use ($t, $wpIcon): array {
                $icon = $wpIcon($tab['id']);
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('wordpress', $tab['id']),
                    'icon' => $icon,
                    'icon_brand' => $icon === 'fa-wordpress',
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('wordpress')),
        ],
        [
            'type' => 'group',
            'slug' => 'plan',
            'icon' => 'fa-layer-group',
            'label' => $t['nav_group_plan'] ?? 'Hosting plan',
            'items' => [
                ['key' => 'plan', 'url' => hs_panel_path('plan.php'), 'icon' => 'fa-layer-group', 'label' => $t['nav_plan_details'] ?? 'Plan details'],
                ['key' => 'resources', 'url' => hs_panel_path('resources.php'), 'icon' => 'fa-chart-pie', 'label' => $t['nav_resources'] ?? 'Resource usage'],
                ['key' => 'plan-renew', 'url' => hs_panel_path('plan-renew.php'), 'icon' => 'fa-rotate', 'label' => $t['btn_renew'] ?? 'Renew'],
                ['key' => 'invoices', 'url' => hs_panel_path('invoices.php'), 'icon' => 'fa-file-invoice-dollar', 'label' => $t['nav_invoices'] ?? 'Invoices'],
            ],
        ],
        [
            'type' => 'group',
            'slug' => 'performance',
            'icon' => 'fa-gauge',
            'label' => $t['nav_performance'] ?? 'Performance',
            'items' => array_map(static function ($tab) use ($t) {
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('performance', $tab['id']),
                    'icon' => match ($tab['id']) {
                        'ai' => 'fa-wand-magic-sparkles',
                        'cache' => 'fa-bolt',
                        'speed' => 'fa-gauge-high',
                        'cdn' => 'fa-cloud',
                        default => 'fa-gauge',
                    },
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('performance')),
        ],
        [
            'type' => 'group',
            'slug' => 'security',
            'icon' => 'fa-shield-halved',
            'label' => $t['nav_security'] ?? 'Security',
            'items' => array_map(static function ($tab) use ($t) {
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('security', $tab['id']),
                    'icon' => match ($tab['id']) {
                        'malware' => 'fa-bug',
                        'wpupdate' => 'fa-wordpress',
                        'ssl' => 'fa-lock',
                        default => 'fa-shield-halved',
                    },
                    'icon_brand' => $tab['id'] === 'wpupdate',
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('security')),
        ],
        [
            'type' => 'group',
            'slug' => 'domains',
            'icon' => 'fa-globe',
            'label' => $t['nav_domains'] ?? 'Domains',
            'items' => array_map(static function ($tab) use ($t) {
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('domains', $tab['id']),
                    'icon' => 'fa-globe',
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('domains')),
        ],
        [
            'type' => 'group',
            'slug' => 'websites',
            'icon' => 'fa-window-maximize',
            'label' => $t['nav_website'] ?? 'Website',
            'items' => (static function () use ($t): array {
                $items = [];
                foreach (hs_panel_section_tabs('websites') as $tab) {
                    $items[] = [
                        'key' => $tab['nav_key'],
                        'url' => $tab['id'] === 'installer' ? hs_panel_path('apps.php') : hs_panel_tab_href('websites', $tab['id']),
                        'icon' => match ($tab['id']) {
                            'installer' => 'fa-box-open',
                            'migrate' => 'fa-truck',
                            'copy' => 'fa-copy',
                            'errors' => 'fa-triangle-exclamation',
                            'support' => 'fa-headset',
                            default => 'fa-window-maximize',
                        },
                        'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                    ];
                    if ($tab['id'] === 'installer') {
                        $items[] = [
                            'key' => 'landing-builder',
                            'url' => hs_panel_path('landing-builder.php'),
                            'icon' => 'fa-paintbrush',
                            'label' => $t['nav_landing_builder'] ?? 'Landing builder',
                        ];
                    }
                }
                return $items;
            })(),
        ],
        [
            'type' => 'group',
            'slug' => 'files',
            'icon' => 'fa-folder-open',
            'label' => $t['nav_files'] ?? 'Files',
            'items' => array_map(static function ($tab) use ($t) {
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('files', $tab['id']),
                    'icon' => match ($tab['id']) {
                        'backups' => 'fa-clock-rotate-left',
                        'ftp', 'ftppass' => 'fa-server',
                        default => 'fa-folder-open',
                    },
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('files')),
        ],
        [
            'type' => 'group',
            'slug' => 'databases',
            'icon' => 'fa-database',
            'label' => $t['nav_databases'] ?? 'Databases',
            'items' => array_map(static function ($tab) use ($t) {
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('databases', $tab['id']),
                    'icon' => 'fa-database',
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('databases')),
        ],
        [
            'type' => 'group',
            'slug' => 'api',
            'icon' => 'fa-plug',
            'label' => $t['nav_group_api'] ?? 'API',
            'items' => array_map(static function ($tab) use ($t) {
                return [
                    'key' => $tab['nav_key'],
                    'url' => hs_panel_tab_href('api', $tab['id']),
                    'icon' => match ($tab['id']) {
                        'ai' => 'fa-wand-magic-sparkles',
                        default => 'fa-plug',
                    },
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('api')),
        ],
        [
            'type' => 'group',
            'slug' => 'advanced',
            'icon' => 'fa-sliders',
            'label' => $t['nav_group_advanced'] ?? 'Advanced',
            'items' => array_map(static function ($tab) use ($t) {
                $url = match ($tab['id']) {
                    'php' => hs_panel_path('php.php'),
                    'ssh' => hs_panel_path('ssh.php'),
                    default => hs_panel_tab_href('advanced', $tab['id']),
                };
                return [
                    'key' => $tab['nav_key'],
                    'url' => $url,
                    'icon' => match ($tab['id']) {
                        'ssh' => 'fa-terminal',
                        'php' => 'fa-php',
                        'dns' => 'fa-network-wired',
                        'cron' => 'fa-clock',
                        'git' => 'fa-code-branch',
                        'history' => 'fa-list',
                        default => 'fa-sliders',
                    },
                    'icon_brand' => $tab['id'] === 'php',
                    'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                ];
            }, hs_panel_section_tabs('advanced')),
        ],
        [
            'type' => 'group',
            'slug' => 'tools',
            'icon' => 'fa-chart-line',
            'label' => $t['nav_tools'] ?? 'Tools',
            'items' => [
                // cPanel — only useful after payment; unpaid nav filter removes it
                ['key' => 'cpanel', 'url' => hs_panel_path('cpanel.php'), 'icon' => 'fa-server', 'label' => $t['nav_cpanel'] ?? 'cPanel'],
                ['key' => 'analytics', 'url' => hs_panel_path('analytics.php'), 'icon' => 'fa-chart-line', 'label' => $t['nav_analytics'] ?? 'Analytics'],
                ['key' => 'email', 'url' => hs_panel_path('email.php'), 'icon' => 'fa-envelope', 'label' => $t['dash_manage_email'] ?? 'Email'],
                ['key' => 'backups', 'url' => hs_panel_path('backups.php'), 'icon' => 'fa-clock-rotate-left', 'label' => $t['dash_backups'] ?? 'Backups'],
            ],
        ],
        [
            'type' => 'item',
            'items' => [[
                'key' => 'account',
                'url' => hs_panel_path('account.php'),
                'icon' => 'fa-user-gear',
                'label' => $t['nav_account'] ?? 'Account',
            ]],
        ],
    ];
}

function hs_panel_search_items(array $t): array
{
    return hs_panel_all_search_items($t);
}

function hs_panel_nav_open_slug(string $panelActive): string
{
    static $map = null;
    if ($map === null) {
        $map = [
            'dashboard' => '', 'account' => '', 'clients' => '', 'ssh' => 'advanced', 'analytics' => 'tools', 'email' => 'tools', 'backups' => 'tools', 'cpanel' => 'tools',
            'plan' => 'plan', 'resources' => 'plan', 'plan-renew' => 'plan',
            'installer' => 'websites', 'landing-builder' => 'websites', 'php' => 'advanced', 'site-support' => '',
        ];
        foreach (['wordpress', 'performance', 'security', 'domains', 'websites', 'files', 'databases', 'api', 'advanced'] as $sec) {
            foreach (hs_panel_section_tabs($sec) as $tab) {
                $map[$tab['nav_key']] = $sec;
            }
        }
    }
    return $map[$panelActive] ?? '';
}

/**
 * Sidebar groups for the logged-in client (filters unpaid users to allowed menus).
 *
 * @param array $t
 * @param array|null $user
 */
function hs_panel_nav_groups_for_user(array $t, ?array $user, bool $isAdmin): array
{
    require_once __DIR__ . '/panel-access.php';
    $groups = hs_panel_nav_groups($t);
    // Unpaid / no active hosting: dashboard, plan, domains, account, invoices, support
    if ($user !== null && !hs_user_hosting_active($user)) {
        $groups = hs_panel_nav_filter_no_hosting($groups);
    }
    if ($isAdmin) {
        $groups[] = [
            'type' => 'item',
            'items' => [[
                'key' => 'clients',
                'url' => hs_panel_path('clients.php'),
                'icon' => 'fa-users',
                'label' => $t['nav_clients'] ?? 'Clients',
            ]],
        ];
    }
    return $groups;
}

/** @param array $t */
function hs_panel_nav_groups_for_admin(array $t, bool $isAdmin): array
{
    return hs_panel_nav_groups_for_user($t, null, $isAdmin);
}