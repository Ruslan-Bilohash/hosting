<?php
declare(strict_types=1);

require_once __DIR__ . '/panel-tabs.php';

/** @return list<string> relative URLs under /hosting/ for smoke tests */
function hs_menu_all_urls(): array
{
    $urls = [
        hs_panel_path(''),
        hs_panel_path('plan.php'),
        hs_panel_path('resources.php'),
        hs_panel_path('plan-renew.php'),
        hs_panel_path('installer.php'),
        hs_panel_path('landing-builder.php'),
        hs_panel_path('landing-gallery-api.php'),
        hs_panel_path('performance-speed-api.php'),
        hs_panel_path('ssh.php'),
        hs_panel_path('php.php'),
        hs_panel_path('phpinfo.php'),
        hs_panel_path('analytics.php'),
        hs_panel_path('email.php'),
        hs_panel_path('backups.php'),
        hs_panel_path('account.php'),
        hs_panel_path('clients.php'),
        hs_panel_path('support.php'),
    ];
    $urls[] = hs_panel_path('api.php');
    foreach (['performance', 'security', 'domains', 'websites', 'files', 'databases', 'advanced', 'api', 'wordpress'] as $sec) {
        foreach (hs_panel_section_tabs($sec) as $tab) {
            $urls[] = hs_panel_tab_href($sec, $tab['id']);
        }
    }
    return array_values(array_unique($urls));
}