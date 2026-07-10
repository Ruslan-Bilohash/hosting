<?php
declare(strict_types=1);

require_once __DIR__ . '/plan-catalog.php';
require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/panel-ui.php';

/** @param array<string, mixed> $user */
function hs_plan_page_overview(array $ctx): string
{
    $t = $ctx['t'];
    $lang = $ctx['lang'];
    $user = $ctx['user'];
    $r = $ctx['resources'];
    $planId = (string) ($user['plan'] ?? 'starter');
    $plan = hs_plan($planId);
    $specs = hs_plan_hostinger_specs($planId);
    $status = (string) ($user['subscription_status'] ?? 'active');
    $paidUntil = (string) ($user['paid_until'] ?? '');
    $paidLabel = $paidUntil !== '' ? date('Y-m-d', strtotime($paidUntil)) : ($t['plan_no_paid_date'] ?? '—');
    $statusLabel = match ($status) {
        'pending' => $t['plan_status_pending'] ?? 'Pending',
        'suspended' => $t['plan_status_suspended'] ?? 'Suspended',
        default => $t['status_active'] ?? 'Active',
    };
    $statusClass = match ($status) {
        'pending' => 'hs-plan-status-pending',
        'suspended' => 'hs-plan-status-suspended',
        default => 'hs-plan-status-active',
    };
    $price = hs_format_plan_price($planId, $lang);
    $was = hs_format_plan_was_price($planId, $lang);
    $features = '';
    foreach (hs_plan_feature_lines($plan, $t) as $line) {
        $features .= '<li><i class="fa-solid fa-check"></i> ' . hs_h($line) . '</li>';
    }
    $services = hs_user_plan_services($user, $lang);
    $servicesHtml = '';
    if ($services === []) {
        $servicesHtml = '<p class="hp-muted">' . hs_h($t['plan_no_services'] ?? '') . '</p>';
    } else {
        $servicesHtml = '<ul class="hs-plan-services-list">';
        foreach ($services as $svc) {
            $servicesHtml .= '<li><i class="fa-solid ' . hs_h((string) ($svc['icon'] ?? 'fa-puzzle-piece')) . '"></i>'
                . '<div><strong>' . hs_h(hs_plan_catalog_service_label($svc, $lang)) . '</strong>'
                . '<span>' . hs_h(hs_plan_catalog_service_desc($svc, $lang)) . '</span></div></li>';
        }
        $servicesHtml .= '</ul>';
    }
    $priceHtml = '<div class="hs-plan-hero-price">';
    if ($was !== '') {
        $priceHtml .= '<span class="hs-plan-price-was">' . hs_h($was) . hs_h($t['per_month'] ?? '') . '</span>';
    }
    $priceHtml .= '<span class="hs-plan-price-now">' . hs_h($price) . '</span>'
        . '<span class="hs-plan-price-per">' . hs_h($t['per_month'] ?? '') . '</span></div>';

    $hero = '<section class="hs-plan-hero">'
        . '<div class="hs-plan-hero-main">'
        . '<span class="hs-plan-status ' . $statusClass . '">' . hs_h($statusLabel) . '</span>'
        . '<h2>' . hs_h(hs_plan_hosting_label($planId, $t)) . '</h2>'
        . '<p class="hp-muted">' . hs_h($t['plan_' . $planId . '_desc'] ?? $t['plan_' . $planId] ?? $planId) . '</p>'
        . $priceHtml
        . '</div>'
        . '<div class="hs-plan-hero-meta">'
        . '<div><span class="label">' . hs_h($t['plan_renews'] ?? '') . '</span><strong>' . hs_h($paidLabel) . '</strong></div>'
        . '<div><span class="label">' . hs_h($t['plan_websites_limit'] ?? '') . '</span><strong>'
        . hs_h((string) $r['sites_used']) . ' / ' . hs_h((string) $r['sites_max']) . '</strong></div>'
        . '<div><span class="label">' . hs_h($t['plan_storage_limit'] ?? '') . '</span><strong>'
        . hs_h((string) $r['disk_used_gb']) . ' / ' . hs_h((string) $r['disk_max_gb']) . ' GB</strong></div>'
        . '</div>'
        . '<div class="hs-plan-hero-actions">'
        . '<a href="' . hs_h(hs_url(hs_panel_path('plan-renew.php'))) . '" class="hs-btn hs-btn-primary"><i class="fa-solid fa-rotate"></i> '
        . hs_h($t['btn_renew'] ?? '') . '</a>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('invoices.php'))) . '" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-file-invoice-dollar"></i> '
        . hs_h($t['nav_invoices'] ?? '') . '</a>'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-plan-change-open><i class="fa-solid fa-arrow-up-right-dots"></i> '
        . hs_h($t['btn_change_plan'] ?? '') . '</button>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('resources.php'))) . '" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-chart-pie"></i> '
        . hs_h($t['nav_resources'] ?? '') . '</a>'
        . '</div></section>';

    $usage = '<section class="hp-card hs-plan-usage-card"><h2 class="hp-card-title">' . hs_h($t['plan_usage_title'] ?? 'Usage') . '</h2>'
        . '<div class="hp-card-body">'
        . hs_render_progress($t['dash_disk_usage'] ?? 'Disk', (float) $r['disk_used_gb'], (float) $r['disk_max_gb'], 'GB')
        . hs_render_progress($t['resources_inodes'] ?? 'Inodes', (float) ($r['inodes_used'] ?? 0), (float) ($r['inodes_max'] ?? 1), '')
        . hs_render_progress($t['plan_websites_limit'] ?? 'Sites', (float) $r['sites_used'], (float) $r['sites_max'], '')
        . '</div></section>';

    $included = '<section class="hp-card"><h2 class="hp-card-title">' . hs_h($t['plan_features'] ?? '') . '</h2>'
        . '<div class="hp-card-body"><ul class="hs-plan-features">' . $features . '</ul>'
        . '<table class="hs-table hs-plan-spec-mini"><tbody>'
        . '<tr><th>' . hs_h($t['plan_disk'] ?? '') . '</th><td>' . hs_h((string) $specs['disk_gb']) . ' GB</td></tr>'
        . '<tr><th>' . hs_h($t['plan_ram'] ?? '') . '</th><td>' . hs_h((string) $specs['ram_mb']) . ' MB</td></tr>'
        . '<tr><th>' . hs_h($t['plan_cpu_cores'] ?? '') . '</th><td>' . hs_h((string) $specs['cpu_cores']) . '</td></tr>'
        . '<tr><th>' . hs_h($t['db_title'] ?? 'Databases') . '</th><td>' . hs_h((string) hs_user_database_limit($user)) . '</td></tr>'
        . '</tbody></table></div></section>';

    $svcCard = '<section class="hp-card"><h2 class="hp-card-title">' . hs_h($t['plan_services_title'] ?? 'Add-on services') . '</h2>'
        . '<div class="hp-card-body">' . $servicesHtml
        . '<p class="hp-muted hs-plan-services-hint">' . hs_h($t['plan_services_hint'] ?? '') . '</p></div></section>';

    return $hero . '<div class="hs-plan-overview-grid">' . $usage . $included . $svcCard . '</div>'
        . hs_plan_page_upgrade_section($ctx);
}

/** @param array<string, mixed> $ctx */
function hs_plan_page_upgrade_section(array $ctx): string
{
    $t = $ctx['t'];
    $lang = $ctx['lang'];
    $user = $ctx['user'];
    $current = (string) ($user['plan'] ?? 'starter');
    $html = '<section class="hs-plan-upgrade-section"><h2 class="hs-plan-upgrade-title">' . hs_h($t['plan_upgrade_title'] ?? 'Other plans') . '</h2>'
        . '<div class="hs-plans hs-plans-panel">';
    foreach (hs_plans() as $pid => $plan) {
        if ($pid === $current || $pid === 'pro') {
            continue;
        }
        $descKey = 'plan_' . $pid . '_desc';
        $features = '';
        foreach (hs_plan_feature_lines($plan, $t) as $line) {
            $features .= '<li><i class="fa-solid fa-check"></i> ' . hs_h($line) . '</li>';
        }
        $html .= '<article class="hs-plan-card' . (($plan['badge'] ?? '') === 'popular' ? ' is-popular' : '') . '">'
            . '<h3>' . hs_h($t['plan_' . $pid] ?? $pid) . '</h3>'
            . '<p class="hs-plan-desc">' . hs_h($t[$descKey] ?? '') . '</p>'
            . hs_render_plan_price_block($plan, $t, $lang)
            . '<ul class="hs-plan-features">' . $features . '</ul>'
            . '<button type="button" class="hs-btn hs-btn-primary hs-plan-cta" data-hs-plan-change-open data-plan-pref="' . hs_h($pid) . '">'
            . hs_h($t['plan_upgrade_cta'] ?? $t['btn_change_plan'] ?? '') . '</button></article>';
    }
    return $html . '</div></section>';
}

/** Modal shell + JS config for AJAX plan change */
function hs_plan_change_modal_shell(array $t, string $lang): string
{
    $i18n = [
        'title' => $t['plan_change_modal_title'] ?? 'Choose a plan',
        'current' => $t['plan_change_current'] ?? 'Current plan',
        'popular' => $t['plan_popular'] ?? 'Popular',
        'per_month' => $t['per_month'] ?? '/mo',
        'diff' => $t['plan_change_diff'] ?? 'Due now',
        'downgrade' => $t['plan_change_error_downgrade'] ?? 'You have {used} sites — this plan allows {limit}',
        'loading' => $t['plan_change_loading'] ?? 'Loading plans…',
        'load_error' => $t['plan_change_load_error'] ?? 'Could not load plans',
        'confirm' => $t['plan_change_confirm'] ?? 'Switch plan',
        'changing' => $t['plan_change_changing'] ?? 'Switching…',
        'success' => $t['plan_change_success'] ?? 'Plan updated',
        'error' => $t['plan_change_error'] ?? 'Could not change plan',
        'cancel' => $t['btn_cancel'] ?? 'Cancel',
    ];
    $json = json_encode([
        'api' => hs_url(hs_panel_path('plan-change-api.php')),
        'csrf' => hs_csrf_token(),
        'i18n' => $i18n,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    return '<div class="hs-plan-modal-backdrop" data-hs-plan-modal-backdrop hidden></div>'
        . '<div class="hs-plan-modal" data-hs-plan-modal role="dialog" aria-modal="true" aria-labelledby="hs-plan-modal-title" hidden>'
        . '<header class="hs-plan-modal-head">'
        . '<h3 id="hs-plan-modal-title">' . hs_h($i18n['title']) . '</h3>'
        . '<button type="button" class="hs-plan-modal-close" data-hs-plan-modal-close aria-label="' . hs_h($t['btn_close'] ?? 'Close') . '"><i class="fa-solid fa-xmark"></i></button>'
        . '</header>'
        . '<div class="hs-plan-modal-body"><div class="hs-plan-modal-grid" data-hs-plan-modal-grid></div>'
        . '<p class="hs-plan-modal-status" data-hs-plan-modal-status></p></div>'
        . '<footer class="hs-plan-modal-foot">'
        . '<button type="button" class="hs-btn hs-btn-ghost" data-hs-plan-modal-close>' . hs_h($i18n['cancel']) . '</button>'
        . '<button type="button" class="hs-btn hs-btn-primary" data-hs-plan-modal-confirm disabled><i class="fa-solid fa-check"></i> '
        . hs_h($i18n['confirm']) . '</button>'
        . '</footer></div>'
        . '<script>window.HS_PLAN_CHANGE=' . $json . ';</script>';
}

/** @param array<string, mixed> $ctx */
function hs_plan_page_technical(array $ctx): string
{
    $t = $ctx['t'];
    $user = $ctx['user'];
    $hs_user_settings = $ctx['hs_user_settings'];
    $planId = (string) ($user['plan'] ?? 'starter');
    $specs = hs_plan_hostinger_specs($planId);
    $srv = hs_server_constants($user);
    $domain = hs_plan_display_domain($user, $hs_user_settings);
    $ftpUser = hs_ftp_username($domain, $user);
    $ftpPath = hs_ftp_plan_path();
    $traffic = ($specs['traffic'] ?? '') === 'unlimited' ? ($t['plan_traffic_unlimited'] ?? 'Unlimited') : (string) $specs['traffic'];

    return '<div class="hp-plan-sections">'
        . hs_render_card($t['plan_site_details'] ?? '', hs_render_kv_table([
            [$t['plan_site_url'] ?? '', '<a href="https://' . hs_h($domain) . '" target="_blank" rel="noopener">https://' . hs_h($domain) . '</a>'],
            [$t['plan_site_www'] ?? '', '<a href="https://www.' . hs_h($domain) . '" target="_blank" rel="noopener">https://www.' . hs_h($domain) . '</a>'],
            [$t['plan_site_ip'] ?? '', hs_h($srv['ip'])],
        ]))
        . hs_render_card($t['plan_hosting_details'] ?? '', hs_render_kv_table([
            [$t['plan_current'] ?? '', '<strong>' . hs_h(hs_plan_hosting_label($planId, $t)) . '</strong>'],
            [$t['plan_disk'] ?? '', hs_h((string) $specs['disk_gb']) . ' ' . ($t['plan_unit_gb'] ?? 'GB')],
            [$t['plan_ram'] ?? '', hs_h((string) $specs['ram_mb']) . ' ' . ($t['plan_unit_mb'] ?? 'MB')],
            [$t['plan_cpu_cores'] ?? '', hs_h((string) $specs['cpu_cores'])],
            [$t['plan_inodes'] ?? '', hs_h((string) $specs['inodes'])],
            [$t['plan_addons_sites'] ?? '', hs_h((string) $specs['sites'])],
            [$t['plan_max_processes'] ?? '', hs_h((string) $specs['max_processes'])],
            [$t['plan_php_workers'] ?? '', hs_h((string) $specs['php_workers'])],
            [$t['plan_traffic'] ?? '', hs_h($traffic)],
        ]))
        . hs_render_card($t['plan_ns_title'] ?? '', hs_render_kv_table([
            [$t['plan_ns_current1'] ?? '', hs_h($srv['ns1'])],
            [$t['plan_ns_current2'] ?? '', hs_h($srv['ns2'])],
            [$t['plan_ns_hostinger'] ?? '', hs_h($srv['ns1']) . '<br>' . hs_h($srv['ns2'])],
        ]))
        . hs_render_card($t['plan_server_details'] ?? '', hs_render_kv_table([
            [$t['plan_server_name'] ?? '', hs_h($srv['server_name'])],
            [$t['plan_server_location'] ?? '', hs_h($t['plan_server_eu'] ?? $srv['location'])],
            [$t['plan_backup_location'] ?? '', hs_h($t['plan_backup_fr'] ?? $srv['backup_location'])],
        ]))
        . hs_render_card($t['plan_ftp_details'] ?? '', hs_render_kv_table([
            [$t['plan_ftp_ip'] ?? '', hs_h(hs_ftp_display_host($srv['ip'], $user))],
            [$t['plan_ftp_host'] ?? '', hs_h(hs_ftp_display_host($domain, $user))],
            [$t['plan_ftp_user'] ?? '', '<code>' . hs_h($ftpUser) . '</code>'],
            [$t['plan_ftp_path'] ?? '', '<code>' . hs_h($ftpPath) . '</code>'],
        ]))
        . hs_render_card($t['plan_webapp_details'] ?? '',
            '<p class="hp-muted" style="margin-top:0"><strong>' . hs_h($t['plan_frontend_fw'] ?? '') . '</strong><br>'
            . hs_h($t['plan_frontend_list'] ?? '') . '</p>'
            . '<p class="hp-muted"><strong>' . hs_h($t['plan_backend_fw'] ?? '') . '</strong><br>'
            . hs_h($t['plan_backend_list'] ?? '') . '</p>'
            . hs_render_kv_table([
                [$t['plan_node_versions'] ?? '', hs_h($t['plan_node_list'] ?? '')],
                [$t['plan_package_managers'] ?? '', hs_h($t['plan_pkg_list'] ?? '')],
            ]))
        . '</div>';
}