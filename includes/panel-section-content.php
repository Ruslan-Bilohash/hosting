<?php
declare(strict_types=1);

require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/panel-features.php';
require_once __DIR__ . '/performance.php';
require_once __DIR__ . '/wordpress-install.php';
require_once __DIR__ . '/domain-store.php';
require_once __DIR__ . '/domain-orders.php';
require_once __DIR__ . '/panel-domains.php';

/**
 * @param array{user:array,t:array,hs_user_settings:array,hs_sites:array,hs_resources:array,hs_plan:array,lang:string,error?:string,success?:string} $ctx
 */
function hs_panel_section_content(string $section, string $tab, array $ctx): string
{
    return match ($section) {
        'performance' => hs_panel_perf_content($tab, $ctx),
        'security' => hs_panel_security_content($tab, $ctx),
        'domains' => hs_panel_domains_content($tab, $ctx),
        'websites' => hs_panel_websites_content($tab, $ctx),
        'files' => hs_panel_files_content($tab, $ctx),
        'databases' => hs_panel_databases_content($tab, $ctx),
        'advanced' => hs_panel_advanced_content($tab, $ctx),
        'api' => hs_panel_api_content($tab, $ctx),
        'wordpress' => hs_panel_wordpress_content($tab, $ctx),
        default => '',
    };
}

function hs_panel_extra_domains_list(array $settings, array $t, string $userId): string
{
    $extra = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
    if ($extra === []) {
        return '<p class="hp-muted">' . hs_h($t['dom_parked_empty'] ?? ('No parked domains yet. Add ' . hs_default_primary_domain() . ' below.')) . '</p>';
    }
    $html = '<div class="hs-table-wrap"><table class="hs-table hs-dom-parked-table"><thead><tr>'
        . '<th>' . hs_h($t['dom_registry_col_domain'] ?? 'Domain') . '</th><th></th></tr></thead><tbody>';
    foreach ($extra as $d) {
        $dom = (string) $d;
        $entry = hs_domain_registry_entry($userId, $dom, $settings) ?? ['domain' => $dom, 'role' => 'parked'];
        $html .= '<tr><td><code>' . hs_h($dom) . '</code></td><td class="hs-dom-actions">'
            . hs_domain_delete_action_html($entry, $userId, $t) . '</td></tr>';
    }
    return $html . '</tbody></table></div>';
}

function hs_panel_info_block(string $title, array $rows): string
{
    return hs_render_card($title, hs_render_kv_table($rows));
}

function hs_panel_status_card(string $title, array $lines, ?string $footer = null): string
{
    $body = '';
    foreach ($lines as $line) {
        $val = $line[1];
        $cls = 'hp-status-val';
        if (array_key_exists(2, $line)) {
            $cls .= !empty($line[2]) ? ' hp-status-ok' : ' hp-status-off';
        }
        $body .= '<div class="hp-status-row"><span>' . hs_h($line[0]) . '</span><span class="' . $cls . '">' . $val . '</span></div>';
    }
    return hs_render_card($title, $body, $footer);
}

function hs_panel_table_from_list(array $rows, array $headers): string
{
    if ($rows === []) {
        return '<p class="hp-muted">—</p>';
    }
    $html = '<div class="hs-table-wrap"><table class="hs-table"><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . hs_h($h) . '</th>';
    }
    return $html . '</tr></thead><tbody>' . implode('', $rows) . '</tbody></table></div>';
}

/** @param array<string, mixed> $ctx */
function hs_panel_perf_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $r = $ctx['hs_resources'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $alerts = hs_panel_alerts($ctx);
    $desktop = (int) ($s['speed_desktop'] ?? $r['perf_desktop'] ?? 0);
    $mobile = (int) ($s['speed_mobile'] ?? $r['perf_mobile'] ?? 0);
    $probe = is_array($s['speed_probe'] ?? null) ? $s['speed_probe'] : [];
    $probeMeta = '';
    if ($probe !== []) {
        $probeMeta = '<p class="hp-muted">' . hs_h($t['perf_probe_url'] ?? 'URL') . ': <code>' . hs_h((string) ($probe['url'] ?? '')) . '</code>'
            . ' · TTFB: ' . (int) round((float) ($probe['ttfb'] ?? 0)) . ' ms'
            . ' · ' . (int) round((float) ($probe['total'] ?? 0)) . ' ms'
            . ' · ' . (int) round(((int) ($probe['size'] ?? 0)) / 1024) . ' KB</p>';
    }
    $findings = is_array($s['perf_ai_findings'] ?? null) ? $s['perf_ai_findings'] : [];
    $advice = hs_perf_build_advice($findings, $user, $t, $s);
    $adviceHtml = hs_perf_render_advice_cards($advice, $t);
    $perfBoot = hs_perf_render_panel_boot($ctx);
    $scanStatus = '<p class="hp-muted hs-perf-scan-status" data-hs-perf-scan-status hidden></p>';
    $htaccess = hs_perf_user_htaccess((string) ($user['username'] ?? 'user'));
    $cacheLive = is_file($htaccess) && strpos((string) file_get_contents($htaccess), HS_PERF_MARKER_CACHE) !== false;

    return match ($tab) {
        'ai' => $alerts . hs_render_card(
            $t['tab_perf_ai'] ?? '',
            '<p class="hp-muted">' . hs_h($t['perf_ai_hint'] ?? '') . '</p>'
            . (!empty($s['perf_ai_last_scan']) ? '<p class="hp-muted">' . hs_h($t['dash_perf_last_scan'] ?? '') . ' ' . hs_h(hs_format_date((string) $s['perf_ai_last_scan'])) . '</p>' : '')
            . $scanStatus
            . '<div data-hs-perf-findings>' . hs_perf_render_findings($findings, $t) . '</div>'
            . '<div data-hs-perf-advice>' . $adviceHtml . '</div>',
            '<form method="post" class="hp-actions">' . hs_csrf_field()
            . '<button type="submit" name="run_perf_scan" value="1" class="hs-btn hs-btn-primary" data-hs-perf-scan-run><i class="fa-solid fa-stethoscope"></i> '
            . hs_h($t['perf_ai_run_scan'] ?? 'Run diagnostics') . '</button>'
            . hs_panel_toggle_form('toggle_perf_ai', !empty($s['perf_ai_enabled']), $t, $t['perf_ai_enable'] ?? 'Enable', $t['perf_ai_disable'] ?? 'Disable')
            . '</form>'
        ) . $perfBoot,
        'cache' => $alerts . hs_panel_status_card($t['tab_perf_cache'] ?? '', [
            [$t['perf_cache'] ?? '', !empty($s['cache_enabled']) ? ($t['perf_cache_on'] ?? 'On') : ($t['perf_cache_off'] ?? 'Off'), !empty($s['cache_enabled'])],
            [$t['perf_cache_htaccess'] ?? '.htaccess', $cacheLive ? ($t['perf_cache_live'] ?? 'Active') : ($t['perf_cache_off'] ?? 'Off'), $cacheLive],
            [$t['perf_cache_cleared'] ?? 'Last cleared', !empty($s['cache_cleared_at']) ? hs_h(hs_format_date((string) $s['cache_cleared_at'])) : '—'],
        ], '<div class="hp-actions">'
            . hs_panel_toggle_form('toggle_cache', !empty($s['cache_enabled']), $t, $t['perf_cache_disable'] ?? 'Disable', $t['perf_cache_enable'] ?? 'Enable')
            . '<form method="post" style="display:inline">' . hs_csrf_field()
            . '<button type="submit" name="clear_object_cache" value="1" class="hs-btn hs-btn-ghost">' . hs_h($t['perf_cache_clear_btn'] ?? 'Clear cache') . '</button></form></div>'),
        'speed' => $alerts . hs_perf_render_speed_tab($ctx),
        'cdn' => $alerts . hs_panel_status_card($t['tab_perf_cdn'] ?? '', [
            [$t['perf_cdn'] ?? 'CDN', !empty($s['cdn_enabled']) ? ($t['perf_cdn_on'] ?? 'On') : ($t['perf_cdn_off'] ?? 'Off'), !empty($s['cdn_enabled'])],
            [$t['perf_cdn_headers'] ?? 'Static cache headers', !empty($s['cdn_enabled']) ? ($t['perf_cache_live'] ?? 'Active') : ($t['perf_cache_off'] ?? 'Off'), !empty($s['cdn_enabled'])],
        ], '<p class="hp-muted">' . hs_h($t['perf_cdn_hint'] ?? '') . '</p>'
            . hs_panel_toggle_form('toggle_cdn', !empty($s['cdn_enabled']), $t, $t['perf_cdn_enable'] ?? 'Enable CDN', $t['perf_cdn_disable'] ?? 'Disable CDN')),
        default => $alerts . hs_render_card(
            $t['tab_perf_overview'] ?? $t['nav_performance'] ?? '',
            '<p class="hp-muted">' . hs_h($t['perf_overview_hint'] ?? '') . '</p>'
            . '<div class="hp-status-grid">'
            . '<div class="hp-status-row"><span>' . hs_h($t['perf_cache'] ?? '') . '</span><span class="hp-status-val' . (!empty($s['cache_enabled']) ? ' hp-status-ok' : ' hp-status-off') . '">'
            . hs_h(!empty($s['cache_enabled']) ? ($t['perf_cache_on'] ?? '') : ($t['perf_cache_off'] ?? '')) . '</span></div>'
            . '<div class="hp-status-row"><span>' . hs_h($t['tab_perf_speed'] ?? '') . '</span><span class="hp-status-val' . ($desktop > 0 ? ' hp-status-ok' : '') . '">' . $desktop . ' / ' . $mobile . '</span></div>'
            . '<div class="hp-status-row"><span>' . hs_h($t['perf_cdn'] ?? '') . '</span><span class="hp-status-val' . (!empty($s['cdn_enabled']) ? ' hp-status-ok' : ' hp-status-off') . '">'
            . hs_h(!empty($s['cdn_enabled']) ? ($t['perf_cdn_on'] ?? 'On') : ($t['perf_cdn_off'] ?? 'Off')) . '</span></div>'
            . '<div class="hp-status-row"><span>' . hs_h($t['dash_perf_last_scan'] ?? '') . '</span><span class="hp-status-val">'
            . hs_h(!empty($s['perf_ai_last_scan']) ? hs_format_date((string) $s['perf_ai_last_scan']) : '—') . '</span></div></div>'
            . $scanStatus
            . '<h4 style="margin:1.25rem 0 .5rem">' . hs_h($t['perf_overview_checks'] ?? 'Health checks') . '</h4>'
            . '<div data-hs-perf-findings>' . hs_perf_render_findings($findings, $t) . '</div>'
            . '<div data-hs-perf-advice>' . $adviceHtml . '</div>',
            '<form method="post" class="hp-actions">' . hs_csrf_field()
            . '<button type="submit" name="run_perf_scan" value="1" class="hs-btn hs-btn-primary" data-hs-perf-scan-run><i class="fa-solid fa-stethoscope"></i> '
            . hs_h($t['perf_ai_run_scan'] ?? 'Run diagnostics') . '</button>'
            . '<button type="submit" name="run_speed_test" value="1" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-gauge-high"></i> '
            . hs_h($t['perf_run_test'] ?? 'Speed test') . '</button></form>'
        ) . $perfBoot,
    };
}

/** @param array<string, mixed> $ctx */
function hs_panel_security_content(string $tab, array $ctx): string
{
    require_once __DIR__ . '/security-panel.php';
    $t = $ctx['t'];
    $user = $ctx['user'];
    $s = $ctx['hs_user_settings'];
    $live = hs_sec_live_status($user, $s);
    $alerts = hs_panel_alerts($ctx);
    $findings = is_array($s['malware_findings'] ?? null) ? $s['malware_findings'] : [];
    $blockedIps = is_array($s['ip_blocklist'] ?? null) ? $s['ip_blocklist'] : [];

    $statusRow = static function (string $label, bool $on, string $onTxt, string $offTxt) use ($t): array {
        return [$label, $on ? $onTxt : $offTxt, $on];
    };

    return match ($tab) {
        'malware' => $alerts . hs_render_card(
            $t['tab_sec_malware'] ?? '',
            '<p class="hp-muted">' . hs_h($t['sec_malware_hint'] ?? '') . '</p>'
            . hs_panel_status_card('', [
                $statusRow($t['security_malware'] ?? '', ($s['malware_status'] ?? 'clean') === 'clean' && $findings === [], $t['security_scan_ok'] ?? 'Clean', $t['sec_malware_found'] ?? 'Issues'),
                [$t['sec_last_scan'] ?? 'Last scan', !empty($s['malware_last_scan']) ? hs_h(hs_format_date((string) $s['malware_last_scan'])) : '—', !empty($s['malware_last_scan'])],
                [$t['sec_files_scanned'] ?? 'Files scanned', (string) (int) ($s['malware_scanned'] ?? 0), (int) ($s['malware_scanned'] ?? 0) > 0],
            ])
            . hs_sec_render_findings($findings, $t),
            '<form method="post">' . hs_csrf_field() . '<button type="submit" name="run_malware_scan" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-shield-virus"></i> ' . hs_h($t['sec_run_scan'] ?? 'Run scan') . '</button></form>'
        ),
        'wpupdate' => $alerts . hs_panel_sec_card(
            $t['tab_sec_wpupdate'] ?? '',
            'fa-rotate',
            !empty($s['wp_auto_update']),
            'toggle_wp_update',
            [
                [$t['sec_wp_updates'] ?? 'WordPress updates', hs_h(!empty($s['wp_auto_update']) ? ($t['sec_wpupdate_on'] ?? 'Automatic') : ($t['sec_wpupdate_off'] ?? 'Manual')), !empty($s['wp_auto_update'])],
            ],
            $t,
            '<p class="hp-muted" style="margin:0 0 .75rem">' . hs_h($t['sec_wp_hint'] ?? '') . '</p>'
            . '<a href="' . hs_h(hs_url(hs_panel_path('wordpress.php'), ['tab' => 'security'])) . '" class="hs-btn hs-btn-ghost">' . hs_h($t['sec_wp_manage'] ?? 'Manage WP sites') . '</a>'
        ),
        'ssl' => $alerts . hs_panel_sec_card(
            $t['tab_sec_ssl'] ?? '',
            'fa-lock',
            !empty($s['ssl_enabled']),
            'toggle_ssl',
            [
                [$t['sec_ssl_autossl'] ?? 'Certificate', hs_h($t['security_ssl_active'] ?? 'AutoSSL'), !empty($s['ssl_enabled'])],
                [$t['sec_htaccess_live'] ?? '.htaccess HTTPS redirect', hs_h($live['ssl'] ? ($t['sec_live_on'] ?? 'Active') : ($t['sec_live_off'] ?? 'Off')), $live['ssl']],
            ],
            $t,
            '<p class="hp-muted" style="margin:0">' . hs_h($t['sec_ssl_hint'] ?? '') . '</p>'
        ),
        default => $alerts
            . '<div class="hp-grid-2 hp-sec-grid">'
            . hs_panel_sec_card(
                $t['security_ssl'] ?? 'SSL',
                'fa-lock',
                !empty($s['ssl_enabled']),
                'toggle_ssl',
                [
                    [$t['sec_ssl_autossl'] ?? 'Certificate', hs_h($t['security_ssl_active'] ?? 'AutoSSL'), !empty($s['ssl_enabled'])],
                    [$t['sec_htaccess_live'] ?? 'HTTPS rules', hs_h($live['ssl'] ? ($t['sec_live_on'] ?? 'Active') : ($t['sec_live_off'] ?? 'Off')), $live['ssl']],
                ],
                $t
            )
            . hs_panel_sec_card(
                $t['security_firewall'] ?? 'Firewall',
                'fa-shield-halved',
                !empty($s['firewall_enabled']),
                'toggle_firewall',
                [
                    [$t['sec_firewall_rules'] ?? 'Rules', hs_h(!empty($s['firewall_enabled']) ? ($t['sec_firewall_basic'] ?? 'Basic rules') : ($t['sec_firewall_none'] ?? 'Disabled')), !empty($s['firewall_enabled'])],
                    [$t['sec_headers'] ?? 'Security headers', hs_h($live['firewall'] ? ($t['sec_live_on'] ?? 'Active') : ($t['sec_live_off'] ?? 'Off')), $live['firewall']],
                ],
                $t
            )
            . hs_render_card(
                $t['security_malware'] ?? 'Malware',
                '<div class="hs-sec-card-head hs-sec-card-head--static">'
                . '<div class="hs-sec-card-title"><span class="hs-sec-card-icon"><i class="fa-solid fa-shield-virus"></i></span>'
                . '<h2 class="hp-card-title" style="margin:0;border:0;padding:0">' . hs_h($t['security_malware'] ?? '') . '</h2></div>'
                . hs_panel_status_badge(($s['malware_status'] ?? '') === 'clean' && $findings === [], $t)
                . '</div>'
                . '<div class="hp-status-row"><span>' . hs_h($t['sec_last_scan'] ?? 'Last scan') . '</span><span class="hp-status-val">'
                . (!empty($s['malware_last_scan']) ? hs_h(hs_format_date((string) $s['malware_last_scan'])) : '—') . '</span></div>'
                . '<div class="hp-status-row"><span>' . hs_h($t['sec_scan_result'] ?? 'Result') . '</span><span class="hp-status-val '
                . (($s['malware_status'] ?? '') === 'clean' && $findings === [] ? 'hp-status-ok' : 'hp-status-off') . '">'
                . hs_h(($s['malware_status'] ?? '') === 'clean' && $findings === [] ? ($t['security_scan_ok'] ?? 'Clean') : ($t['sec_malware_found'] ?? 'Issues'))
                . '</span></div>',
                '<a href="' . hs_h(hs_url(hs_panel_path('security.php'), ['tab' => 'malware'])) . '" class="hs-btn hs-btn-primary"><i class="fa-solid fa-shield-virus"></i> '
                . hs_h($t['sec_run_scan'] ?? 'Scan') . '</a>'
            )
            . hs_panel_sec_card(
                $t['adv_hotlink_hint'] ?? 'Hotlink',
                'fa-image',
                !empty($s['hotlink_protect']),
                'toggle_hotlink_sec',
                [
                    [$t['sec_hotlink_rules'] ?? 'Protection', hs_h(!empty($s['hotlink_protect']) ? ($t['sec_live_on'] ?? 'Active') : ($t['sec_live_off'] ?? 'Off')), !empty($s['hotlink_protect'])],
                ],
                $t
            )
            . '</div>'
            . hs_render_card($t['sec_ip_block'] ?? 'Block IP addresses', '<p class="hp-muted">' . hs_h($t['sec_ip_block_hint'] ?? '') . '</p>'
                . ($blockedIps === [] ? '<p class="hp-muted">—</p>' : '<ul class="hp-list">' . implode('', array_map(static fn($ip) => '<li><code>' . hs_h((string) $ip) . '</code></li>', $blockedIps)) . '</ul>')
                . '<form method="post" class="hp-inline-form">' . hs_csrf_field()
                . '<div class="hs-field"><label>' . hs_h($t['sec_ip_label'] ?? 'IP address') . '</label><input type="text" name="blocked_ip" placeholder="203.0.113.50" pattern="[0-9a-fA-F.:]+"></div>'
                . '<button type="submit" name="add_blocked_ip" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_add'] ?? 'Add') . '</button></form>'),
    };
}

function hs_panel_domain_search_card(array $t, string $lang): string
{
    $prices = hs_domain_tld_prices();
    $defaultTlds = ['lt', 'com', 'eu', 'net'];
    $tldHtml = '';
    foreach ($prices as $tld => $price) {
        $checked = in_array($tld, $defaultTlds, true) ? ' checked' : '';
        $tldHtml .= '<label class="hs-dom-tld-chk">'
            . '<input type="checkbox" name="tld[]" value="' . hs_h($tld) . '"' . $checked . ' data-hs-tld-chk>'
            . '<span class="hs-dom-tld-name">.' . hs_h($tld) . '</span>'
            . '<span class="hs-dom-tld-price">' . hs_h(hs_domain_format_price((float) $price, $lang)) . '</span>'
            . '</label>';
    }
    $checkUrl = hs_url('domain-check.php');
    return hs_render_card(
        $t['dom_check_title'] ?? 'Domain availability',
        '<form class="hp-stack hs-dom-check" data-hs-panel-domain-search'
        . ' data-check-url="' . hs_h($checkUrl) . '"'
        . ' data-msg-available="' . hs_h($t['domain_available'] ?? 'Available') . '"'
        . ' data-msg-taken="' . hs_h($t['domain_taken'] ?? 'Taken') . '"'
        . ' data-msg-invalid="' . hs_h($t['dom_check_invalid_sld'] ?? 'Enter a valid name (e.g. mysite)') . '"'
        . ' data-msg-error="' . hs_h($t['domain_lookup_error'] ?? 'Lookup failed') . '"'
        . ' data-msg-checking="' . hs_h($t['domain_checking'] ?? 'Checking…') . '"'
        . ' data-msg-cta="' . hs_h($t['domain_register_cta'] ?? 'Register') . '"'
        . ' data-msg-no-tlds="' . hs_h($t['dom_check_no_tlds'] ?? 'Select at least one zone') . '"'
        . ' data-col-domain="' . hs_h($t['dom_registry_col_domain'] ?? 'Domain') . '"'
        . ' data-col-status="' . hs_h($t['dom_registry_col_status'] ?? 'Status') . '"'
        . ' data-col-price="' . hs_h($t['dom_check_col_price'] ?? 'Price') . '"'
        . ' data-register-base="' . hs_h(hs_url(hs_panel_tab_href('domains', 'register'))) . '">'
        . '<div class="hs-field"><label>' . hs_h($t['dom_check_sld_label'] ?? 'Domain name') . '</label>'
        . '<input type="text" name="sld" placeholder="' . hs_h($t['dom_check_sld_placeholder'] ?? 'mysite') . '"'
        . ' pattern="[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?" required autocomplete="off" data-hs-domain-sld></div>'
        . '<div class="hs-dom-tld-section"><div class="hs-dom-tld-head">'
        . '<span>' . hs_h($t['dom_check_tlds_label'] ?? 'Zones to check') . '</span>'
        . '<label class="hs-dom-tld-all"><input type="checkbox" data-hs-tld-all checked>'
        . hs_h($t['dom_check_select_all'] ?? 'All') . '</label></div>'
        . '<div class="hs-dom-tld-grid">' . $tldHtml . '</div></div>'
        . '<button type="submit" class="hs-btn hs-btn-primary" data-hs-domain-btn data-label="' . hs_h($t['dom_check_btn'] ?? 'Check selected') . '">'
        . hs_h($t['dom_check_btn'] ?? 'Check selected') . '</button>'
        . '<div data-hs-domain-results class="hs-dom-results"></div></form>'
    );
}

/** @param array<string, mixed> $settings */
function hs_panel_domain_registry_card(array $settings, array $t, string $userId): string
{
    $registry = hs_user_domain_registry_ensure($userId, $settings);
    if ($registry === []) {
        return hs_render_card(
            $t['dom_registry_title'] ?? 'My domains',
            '<p class="hp-muted">' . hs_h($t['dom_registry_empty'] ?? 'No registered domains yet.') . '</p>'
        );
    }

    usort($registry, static function (array $a, array $b): int {
        $sa = hs_domain_registry_display_status($a);
        $sb = hs_domain_registry_display_status($b);
        $prio = ['expired' => 0, 'expiring' => 1, 'pending_registration' => 2, 'active' => 3];
        $pa = $prio[$sa] ?? 3;
        $pb = $prio[$sb] ?? 3;
        if ($pa !== $pb) {
            return $pa <=> $pb;
        }
        return strcmp((string) ($a['domain'] ?? ''), (string) ($b['domain'] ?? ''));
    });

    $hasPending = false;
    $rows = [];
    foreach ($registry as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $dom = (string) ($entry['domain'] ?? '');
        $expires = (string) ($entry['expires_at'] ?? '');
        $role = (string) ($entry['role'] ?? 'parked');
        $status = hs_domain_registry_display_status($entry);
        if ($status === 'pending_registration') {
            $hasPending = true;
        }
        $statusLabel = match ($status) {
            'pending_registration' => $t['dom_registry_status_pending'] ?? 'Awaiting registration',
            'expiring' => $t['dom_registry_status_expiring'] ?? 'Expiring soon',
            'expired' => $t['dom_registry_status_expired'] ?? 'Expired',
            default => $t['dom_registry_status_active'] ?? 'Active',
        };
        $statusCls = 'hs-dom-status-' . $status;
        $roleLabel = $role === 'primary'
            ? ($t['dom_registry_role_primary'] ?? 'Primary')
            : ($t['dom_registry_role_parked'] ?? 'Parked');
        $expiresFmt = $expires !== '' ? hs_format_date($expires) : '—';
        if ($status === 'expiring') {
            $days = hs_domain_registry_days_left($expires);
            if ($days !== null && $days >= 0) {
                $expiresFmt .= ' <span class="hp-muted">(' . (int) $days . ' ' . hs_h($t['dom_registry_days_left'] ?? 'days left') . ')</span>';
            }
        }
        $statusCell = '<span class="hs-dom-status ' . hs_h($statusCls) . '"'
            . ($status === 'pending_registration' ? ' data-hs-dom-pending-status data-domain="' . hs_h($dom) . '"' : '')
            . '>' . hs_h($statusLabel) . '</span>';
        $purchasedBadge = !empty($entry['purchased']) || hs_domain_entry_is_purchased($entry, $userId)
            ? ' <span class="hs-dom-purchased-badge" title="' . hs_h($t['dom_purchased_badge'] ?? 'Purchased') . '"><i class="fa-solid fa-cart-shopping"></i></span>'
            : '';
        $rows[] = '<tr' . ($status === 'pending_registration' ? ' data-hs-dom-pending-row data-domain="' . hs_h($dom) . '"' : '') . '>'
            . '<td><code>' . hs_h($dom) . '</code>' . $purchasedBadge . '</td><td>' . hs_h($roleLabel) . '</td>'
            . '<td>' . $expiresFmt . '</td><td>' . $statusCell . '</td>'
            . '<td class="hs-dom-actions">' . hs_domain_delete_action_html($entry, $userId, $t) . '</td></tr>';
    }

    $pendingHint = '';
    if ($hasPending) {
        $checkUrl = hs_url(hs_panel_path('domain-order-status-api.php'));
        $pendingHint = '<div class="hs-dom-pending-box" data-hs-dom-pending-poll'
            . ' data-check-url="' . hs_h($checkUrl) . '"'
            . ' data-csrf="' . hs_h(hs_csrf_token()) . '"'
            . ' data-msg-checking="' . hs_h($t['dom_registry_pending_checking'] ?? 'Checking site availability…') . '"'
            . ' data-msg-live="' . hs_h($t['dom_registry_pending_live'] ?? 'Domain is live! Reloading…') . '"'
            . ' data-msg-wait="' . hs_h($t['dom_registry_pending_hint'] ?? '') . '">'
            . '<p class="hp-muted">' . hs_h($t['dom_registry_pending_hint'] ?? '') . '</p>'
            . '<p class="hp-muted hs-dom-pending-demo">' . hs_h($t['dom_registry_pending_demo_hint'] ?? '') . '</p>'
            . '<p class="hp-muted" data-hs-dom-pending-msg></p></div>';
    }

    return hs_render_card(
        $t['dom_registry_title'] ?? 'My domains',
        $pendingHint . hs_panel_table_from_list($rows, [
            $t['dom_registry_col_domain'] ?? 'Domain',
            $t['dom_registry_col_role'] ?? 'Type',
            $t['dom_registry_col_expires'] ?? 'Expires',
            $t['dom_registry_col_status'] ?? 'Status',
            $t['dom_registry_col_actions'] ?? '',
        ])
    );
}

function hs_panel_domain_register_card(array $t, string $lang, string $userId, string $domainInput): string
{
    $domain = hs_domain_normalize($domainInput);
    $body = '';

    if ($domain === null) {
        $body = '<p class="hp-muted">' . hs_h($t['dom_register_pick_domain'] ?? 'Search for an available domain on the Overview tab first.') . '</p>'
            . '<p><a href="' . hs_h(hs_url(hs_panel_tab_href('domains', 'overview'))) . '" class="hs-btn hs-btn-primary">'
            . hs_h($t['tab_dom_overview'] ?? 'Overview') . '</a></p>';
        return hs_render_card($t['dom_register_title'] ?? 'Register domain', $body);
    }

    $pendingOrder = hs_domain_order_pending_for_domain($domain);
    foreach (hs_domain_orders_for_user($userId) as $order) {
        if (strtolower((string) ($order['domain'] ?? '')) === $domain && ($order['status'] ?? '') === 'pending') {
            $pendingOrder = $order;
            break;
        }
    }

    if ($pendingOrder !== null) {
        $body = '<p class="hs-alert hs-alert-info">' . hs_h($t['dom_register_already_pending'] ?? 'This domain is already in your order queue.') . '</p>'
            . '<p><code>' . hs_h($domain) . '</code></p>'
            . '<p class="hp-muted">' . hs_h($t['dom_registry_pending_hint'] ?? '') . '</p>'
            . '<p><a href="' . hs_h(hs_url(hs_panel_tab_href('domains', 'overview'))) . '" class="hs-btn hs-btn-ghost">'
            . hs_h($t['tab_dom_overview'] ?? 'Overview') . '</a></p>';
        return hs_render_card($t['dom_register_title'] ?? 'Register domain', $body);
    }

    $check = hs_domain_check_availability($domain);
    if (!$check['ok'] || empty($check['available'])) {
        $body = '<p class="hs-alert hs-alert-warn">' . hs_h($t['dom_register_error_taken'] ?? 'Domain is not available') . '</p>'
            . '<p><code>' . hs_h($domain) . '</code></p>'
            . '<p><a href="' . hs_h(hs_url(hs_panel_tab_href('domains', 'overview'))) . '" class="hs-btn hs-btn-ghost">'
            . hs_h($t['dom_check_btn'] ?? 'Check again') . '</a></p>';
        return hs_render_card($t['dom_register_title'] ?? 'Register domain', $body);
    }

    $price = hs_domain_format_price((float) ($check['price'] ?? 0), $lang);
    $body = '<p class="hp-muted">' . hs_h($t['dom_register_intro'] ?? '') . '</p>'
        . '<dl class="hs-kv"><dt>' . hs_h($t['dom_register_domain_label'] ?? 'Domain') . '</dt><dd><code>' . hs_h($domain) . '</code></dd>'
        . '<dt>' . hs_h($t['dom_register_price_label'] ?? 'Price') . '</dt><dd>' . hs_h($price) . '</dd></dl>'
        . '<form method="post" class="hp-stack">' . hs_csrf_field()
        . '<input type="hidden" name="domain" value="' . hs_h($domain) . '">'
        . '<button type="submit" name="order_domain" value="1" class="hs-btn hs-btn-primary">'
        . hs_h($t['dom_register_submit'] ?? 'Submit order') . '</button></form>';

    return hs_render_card($t['dom_register_title'] ?? 'Register domain', $body);
}

/** @param array<string, mixed> $ctx */
function hs_panel_domains_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $settings = $ctx['hs_user_settings'];
    $lang = (string) ($ctx['lang'] ?? 'en');
    $user = $ctx['user'];
    $userId = (string) ($user['id'] ?? '');
    $domain = hs_plan_display_domain($user, $settings);
    $subdomains = is_array($settings['domains'] ?? null) ? $settings['domains'] : [];
    $alerts = hs_panel_alerts($ctx);

    return match ($tab) {
        'subdomains' => $alerts . hs_panel_subdomains_tab($subdomains, $domain, $t),
        'parked' => $alerts . hs_render_card(
            $t['tab_dom_parked'] ?? '',
            '<p class="hp-muted">' . hs_h($t['dom_parked_hint'] ?? '') . '</p>'
            . hs_panel_extra_domains_list($settings, $t, $userId)
            . '<form method="post" class="hp-inline-form">' . hs_csrf_field()
            . '<div class="hs-field"><label>' . hs_h($t['dom_add_extra'] ?? '') . '</label>'
            . '<input type="text" name="extra_domain" placeholder="' . hs_h(hs_default_primary_domain()) . '" pattern="[a-z0-9]([a-z0-9.-]*[a-z0-9])?" required></div>'
            . '<button type="submit" name="add_extra_domain" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_add_domain'] ?? '') . '</button></form>'
        ),
        'redirect' => $alerts . hs_render_card(
            $t['tab_dom_redirect'] ?? '',
            hs_panel_redirects_table($settings)
            . '<form method="post" class="hp-stack">' . hs_csrf_field()
            . '<div class="hp-grid-2"><div class="hs-field"><label>From</label><input type="text" name="redirect_from" placeholder="/old" required></div>'
            . '<div class="hs-field"><label>To</label><input type="text" name="redirect_to" placeholder="/new" required></div></div>'
            . '<button type="submit" name="add_redirect" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_add_domain'] ?? 'Add') . '</button></form>'
        ),
        'register' => $alerts . hs_panel_domain_register_card($t, $lang, $userId, (string) ($_GET['domain'] ?? ''))
            . hs_panel_domain_registry_card($settings, $t, $userId),
        default => $alerts
            . hs_panel_domain_search_card($t, $lang)
            . hs_render_card(
                $t['domains_primary'] ?? '',
                '<form method="post" class="hp-stack">' . hs_csrf_field()
                . '<div class="hs-field"><label>' . hs_h($t['domains_primary'] ?? '') . '</label>'
                . '<input type="text" name="primary_domain" value="' . hs_h($domain) . '" required></div>'
                . '<button type="submit" name="save_primary" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_save'] ?? '') . '</button></form>'
            )
            . hs_panel_domain_registry_card($settings, $t, $userId),
        'dns' => $alerts . hs_panel_dns_zone($settings, $t, $domain, $user),
    };
}

function hs_panel_redirects_table(array $settings): string
{
    $rows = is_array($settings['redirects'] ?? null) ? $settings['redirects'] : [];
    if ($rows === []) {
        return '<p class="hp-muted">No redirects</p>';
    }
    $html = '<div class="hs-table-wrap"><table class="hs-table"><thead><tr><th>From</th><th>To</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $html .= '<tr><td><code>' . hs_h((string) ($r['from'] ?? '')) . '</code></td><td><code>' . hs_h((string) ($r['to'] ?? '')) . '</code></td></tr>';
    }
    return $html . '</tbody></table></div>';
}

/** @param array<string, mixed> $ctx */
function hs_panel_websites_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $user = $ctx['user'];
    $sites = $ctx['hs_sites'];
    $s = $ctx['hs_user_settings'];
    $alerts = hs_panel_alerts($ctx);

    return match ($tab) {
        'installer' => $alerts . '<div class="hp-actions"><a href="' . hs_h(hs_url(hs_panel_path('installer.php'))) . '" class="hs-btn hs-btn-primary"><i class="fa-solid fa-box-open"></i> ' . hs_h($t['tab_site_installer'] ?? '') . '</a></div>'
            . '<p class="hp-muted">' . hs_h($t['tip_installer'] ?? '') . '</p>',
        'migrate' => $alerts . hs_render_card(
            $t['tab_site_migrate'] ?? '',
            hs_panel_migrate_queue($s)
            . '<form method="post" class="hp-stack">' . hs_csrf_field()
            . '<div class="hs-field"><label>URL</label><input type="url" name="migrate_url" placeholder="https://old-site.com" required></div>'
            . '<button type="submit" name="queue_migrate" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['site_migrate_start'] ?? 'Queue migration') . '</button></form>'
        ),
        'copy' => $alerts . hs_panel_site_copy_tab($user, $sites, $t),
        'errors' => $alerts . hs_render_card($t['tab_site_errors'] ?? '', hs_panel_error_log($user, $s, $t)),
        default => $alerts . hs_panel_sites_table($user, $sites, $t)
            . '<div class="hp-actions" style="margin-top:1rem"><a href="' . hs_h(hs_url(hs_panel_path('installer.php'))) . '" class="hs-btn hs-btn-primary">' . hs_h($t['tab_site_installer'] ?? '') . '</a></div>',
    };
}

function hs_panel_site_copy_tab(array $user, array $sites, array $t): string
{
    require_once __DIR__ . '/installer.php';
    require_once __DIR__ . '/plans.php';

    $guide = '<aside class="hs-site-copy-guide"><h3><i class="fa-solid fa-lightbulb"></i> ' . hs_h($t['site_copy_guide_title'] ?? 'How to copy a site') . '</h3><ol>';
    for ($i = 1; $i <= 4; $i++) {
        $key = 'site_copy_guide_' . $i;
        if (!empty($t[$key])) {
            $guide .= '<li>' . hs_h($t[$key]) . '</li>';
        }
    }
    $guide .= '</ol></aside>';

    $count = count($sites);
    $limit = hs_user_site_limit($user);
    $canAdd = hs_user_can_add_site($user);
    $paths = hs_install_ui_paths($user);
    $formAction = hs_url(hs_panel_path('websites.php'), ['tab' => 'copy']);

    if ($sites === []) {
        $body = $guide
            . '<p class="hp-muted">' . hs_h($t['site_copy_no_sites'] ?? 'No sites yet — install one first.') . '</p>'
            . '<div class="hp-actions"><a href="' . hs_h(hs_url(hs_panel_path('installer.php'))) . '" class="hs-btn hs-btn-primary">'
            . '<i class="fa-solid fa-box-open"></i> ' . hs_h($t['tab_site_installer'] ?? 'Installer') . '</a></div>';
        return hs_render_card($t['tab_site_copy'] ?? 'Copy website', $body);
    }

    $siteOptions = '';
    $copyMeta = [];
    foreach ($sites as $si) {
        $slug = (string) ($si['slug'] ?? '');
        $pathLabel = 'public_html/' . hs_install_path_rel($user, $si);
        $base = hs_install_normalize_base($user, (string) ($si['install_base'] ?? $paths['default_base']));
        $copyMeta[$slug] = ['src' => $pathLabel, 'prefix' => 'public_html/' . $base . '/'];
        $siteOptions .= '<option value="' . hs_h($slug) . '">' . hs_h((string) ($si['title'] ?? $slug)) . ' — ' . hs_h($pathLabel) . '</option>';
    }
    $firstSlug = (string) ($sites[0]['slug'] ?? '');
    $firstBase = hs_install_normalize_base($user, (string) ($sites[0]['install_base'] ?? $paths['default_base']));
    $destPrefix = 'public_html/' . $firstBase . '/';

    $limitHint = '<p class="hp-muted hs-site-copy-meta"><i class="fa-solid fa-layer-group"></i> '
        . hs_h(sprintf($t['site_copy_sites_count'] ?? '%1$d of %2$d sites used', $count, $limit)) . '</p>';

    $form = '<form method="post" class="hp-stack hs-site-copy-form" action="' . hs_h($formAction) . '" data-hs-site-copy>'
        . hs_csrf_field()
        . $limitHint
        . '<div class="hs-field"><label for="site-copy-from">' . hs_h($t['site_copy_from'] ?? 'Copy from') . '</label>'
        . '<select name="copy_from" id="site-copy-from" required data-copy-from>' . $siteOptions . '</select>'
        . '<p class="hp-muted hs-site-copy-path"><i class="fa-solid fa-folder-open"></i> '
        . hs_h($t['site_copy_source_path'] ?? 'Source:') . ' <code data-copy-src-path>' . hs_h($copyMeta[$firstSlug]['src'] ?? '') . '</code></p></div>'
        . '<div class="hs-field"><label for="site-copy-to">' . hs_h($t['site_copy_dest_label'] ?? 'New folder name') . '</label>'
        . '<div class="hs-path-input"><span class="hs-path-prefix" data-copy-dest-prefix>' . hs_h($destPrefix) . '</span>'
        . '<input type="text" name="copy_to" id="site-copy-to" pattern="[a-z0-9][a-z0-9-]*" required placeholder="my-copy" data-copy-to autocomplete="off"></div>'
        . '<p class="hp-muted hs-path-hint">' . hs_h($t['site_copy_dest_hint'] ?? 'Lowercase letters, numbers and hyphens only.') . '</p>'
        . '<p class="hs-site-copy-preview"><strong>' . hs_h($t['site_copy_dest_preview'] ?? 'Destination:') . '</strong> '
        . '<code data-copy-dest-full>' . hs_h($destPrefix) . 'my-copy/</code></p></div>';

    if ($canAdd) {
        $form .= '<button type="submit" name="copy_site" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-copy"></i> '
            . hs_h($t['site_copy_btn'] ?? 'Copy site') . '</button>';
    } else {
        $form .= '<div class="hs-alert hs-alert-info">' . hs_h($t['site_copy_error_limit'] ?? 'Site limit reached.') . '</div>';
    }
    $form .= '</form>';

    $body = $guide . '<p class="hp-muted">' . hs_h($t['site_copy_hint'] ?? '') . '</p>' . $form
        . '<script>window.HS_SITE_COPY_META = ' . json_encode($copyMeta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>';

    return hs_render_card($t['tab_site_copy'] ?? 'Copy website', $body);
}

function hs_panel_migrate_queue(array $settings): string
{
    $q = is_array($settings['migrate_queue'] ?? null) ? $settings['migrate_queue'] : [];
    if ($q === []) {
        return '<p class="hp-muted">' . hs_h('No migrations queued') . '</p>';
    }
    $rows = array_map(static fn($m) => '<tr><td>' . hs_h((string) ($m['url'] ?? '')) . '</td><td>' . hs_h((string) ($m['status'] ?? '')) . '</td><td>'
        . hs_h(hs_format_date((string) ($m['at'] ?? ''))) . '</td></tr>', $q);
    return hs_panel_table_from_list($rows, ['URL', 'Status', 'Queued']);
}

function hs_panel_error_log(array $user, array $settings, array $t): string
{
    $username = (string) ($user['username'] ?? '');
    $logFile = hs_public_path($username . '/error.log');
    $lines = [];
    if (is_file($logFile)) {
        $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES) ?: [], -20);
    }
    if ($lines === []) {
        return '<p class="hp-muted">' . hs_h($t['site_errors_empty'] ?? 'No errors logged.') . '</p>';
    }
    return '<pre class="hp-log">' . hs_h(implode("\n", $lines)) . '</pre>';
}

/** @param array<string, mixed> $ctx */
function hs_panel_ftp_overview_tab(array $ctx): string
{
    $t = $ctx['t'];
    $ftp = hs_panel_ftp_connection_data($ctx);
    $passUrl = hs_url(hs_panel_path('account.php'));
    $body = '<p class="hp-muted">' . hs_h($t['ftp_overview_hint'] ?? '') . '</p>'
        . '<div class="hs-ftp-creds-grid">'
        . hs_panel_ftp_copy_row($t['plan_ftp_ip'] ?? 'IP', 'ftp-tab-host', $ftp['host'], $t)
        . hs_panel_ftp_copy_row($t['plan_ftp_host'] ?? 'Host', 'ftp-tab-hostname', $ftp['hostname'], $t)
        . hs_panel_ftp_copy_row($t['plan_ftp_user'] ?? 'User', 'ftp-tab-user', $ftp['user'], $t)
        . hs_panel_ftp_copy_row($t['plan_ftp_path'] ?? 'Path', 'ftp-tab-path', $ftp['path'], $t)
        . '</div>'
        . '<p><a href="' . hs_h($passUrl) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-key"></i> '
        . hs_h($t['account_manage_pass'] ?? 'Main password') . '</a></p>';
    return hs_render_card($t['tab_files_ftp'] ?? 'FTP', $body);
}

/** @param array<string, mixed> $ctx */
function hs_panel_git_tab(array $ctx): string
{
    require_once __DIR__ . '/git-deploy.php';
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $username = (string) ($user['username'] ?? 'user');
    $url = (string) ($s['git_url'] ?? '');
    $branch = (string) ($s['git_branch'] ?? 'main');
    $subdir = (string) ($s['git_deploy_subdir'] ?? '');
    $parsed = hs_git_parse_repo($url);
    $deployDir = hs_git_deploy_dir($username, hs_git_sanitize_subdir($subdir));
    $cloneCmd = $url !== '' ? hs_git_clone_command($url, $branch, $username, hs_git_sanitize_subdir($subdir)) : '';
    $pullCmd = $url !== '' ? hs_git_pull_command($username, $branch, hs_git_sanitize_subdir($subdir)) : '';
    $webhookUrl = hs_absolute_url(hs_panel_path('git-webhook.php'), ['user' => $username]);

    $guide = '<aside class="hs-git-guide"><h3><i class="fa-solid fa-book-open"></i> ' . hs_h($t['git_guide_title'] ?? 'GitHub deploy') . '</h3><ol>';
    for ($i = 1; $i <= 4; $i++) {
        $key = 'git_guide_' . $i;
        if (!empty($t[$key])) {
            $guide .= '<li>' . hs_h($t[$key]) . '</li>';
        }
    }
    $guide .= '</ol></aside>';

    $status = $parsed
        ? '<span class="hp-status-ok"><i class="fa-brands fa-github"></i> ' . hs_h($parsed['owner'] . '/' . $parsed['repo']) . '</span>'
        : '<span class="hp-muted">' . hs_h($t['git_no_repo'] ?? 'Paste a GitHub URL') . '</span>';

    $body = $guide
        . '<form method="post" class="hp-stack hs-git-form">' . hs_csrf_field()
        . '<div class="hs-field"><label>' . hs_h($t['git_repo_url'] ?? 'Repository URL') . '</label>'
        . '<input type="url" name="git_url" id="git-url-input" placeholder="https://github.com/user/repo" value="' . hs_h($url) . '">'
        . '<p class="hs-field-hint">' . hs_h($t['git_repo_hint'] ?? '') . '</p></div>'
        . '<div class="hp-grid-2"><div class="hs-field"><label>' . hs_h($t['git_branch'] ?? 'Branch') . '</label>'
        . '<input type="text" name="git_branch" value="' . hs_h($branch) . '" placeholder="main"></div>'
        . '<div class="hs-field"><label>' . hs_h($t['git_subdir'] ?? 'Deploy subfolder') . '</label>'
        . '<input type="text" name="git_deploy_subdir" value="' . hs_h($subdir) . '" placeholder="www"></div></div>'
        . '<div class="hs-field"><label>' . hs_h($t['git_token'] ?? 'GitHub token (private repos)') . '</label>'
        . '<input type="password" name="git_token" placeholder="' . (($s['git_token'] ?? '') !== '' ? '••••••••' : '') . '" autocomplete="new-password">'
        . '<p class="hs-field-hint">' . hs_h($t['git_token_hint'] ?? '') . '</p></div>'
        . '<p class="hp-muted">' . hs_h($t['git_deploy_path'] ?? 'Deploy path') . ': <code id="git-deploy-path">' . hs_h($deployDir) . '</code></p>'
        . '<p>' . hs_h($t['git_status'] ?? 'Repository') . ': ' . $status . '</p>'
        . '<div class="hp-actions">'
        . '<button type="submit" name="save_git" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ' . hs_h($t['btn_save'] ?? '') . '</button>'
        . '<button type="submit" name="git_deploy" value="1" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-cloud-arrow-down"></i> ' . hs_h($t['git_deploy'] ?? 'Deploy now') . '</button>'
        . '</div></form>';

    if (!empty($s['git_last_deploy'])) {
        $body .= '<p class="hp-muted"><i class="fa-solid fa-clock"></i> ' . hs_h($t['git_last_deploy'] ?? 'Last deploy') . ': '
            . hs_h(hs_format_date((string) $s['git_last_deploy'])) . '</p>';
    }
    if (!empty($s['git_last_output'])) {
        $body .= '<pre class="hs-git-log">' . hs_h((string) $s['git_last_output']) . '</pre>';
    }

    if ($cloneCmd !== '') {
        $body .= '<section class="hs-git-cmd-block"><h4>' . hs_h($t['git_cmd_clone'] ?? 'SSH: clone') . '</h4>'
            . '<div class="hp-ssh-cmd"><code id="git-clone-cmd">' . hs_h($cloneCmd) . '</code>'
            . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="git-clone-cmd"><i class="fa-solid fa-copy"></i></button></div></section>';
    }
    if ($pullCmd !== '') {
        $body .= '<section class="hs-git-cmd-block"><h4>' . hs_h($t['git_cmd_pull'] ?? 'SSH: update') . '</h4>'
            . '<div class="hp-ssh-cmd"><code id="git-pull-cmd">' . hs_h($pullCmd) . '</code>'
            . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="git-pull-cmd"><i class="fa-solid fa-copy"></i></button></div></section>';
    }

    $body .= '<section class="hs-git-cmd-block"><h4>' . hs_h($t['git_webhook_title'] ?? 'Webhook (optional)') . '</h4>'
        . '<p class="hp-muted">' . hs_h($t['git_webhook_hint'] ?? '') . '</p>'
        . '<div class="hp-ssh-cmd"><code id="git-webhook-url">' . hs_h($webhookUrl) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="git-webhook-url"><i class="fa-solid fa-copy"></i></button></div></section>';

    return hs_render_card($t['tab_adv_git'] ?? 'GIT', $body);
}

function hs_ensure_ftp_password(string $userId): string
{
    require_once dirname(__DIR__) . '/includes/master-password.php';
    return hs_master_password_plain($userId);
}

function hs_panel_ftp_copy_row(string $label, string $id, string $value, array $t = []): string
{
    $copyLabel = $t['ftp_pass_copy'] ?? 'Copy';
    $copiedLabel = $t['ftp_pass_copied'] ?? 'Copied';
    return '<div class="hs-ftp-cred-card">'
        . '<span class="hs-ftp-cred-label">' . hs_h($label) . '</span>'
        . '<div class="hs-ftp-cred-value">'
        . '<code id="' . hs_h($id) . '">' . hs_h($value) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-ftp-copy-btn" data-copy-target="' . hs_h($id) . '"'
        . ' data-copied-label="' . hs_h($copiedLabel) . '" title="' . hs_h($copyLabel) . '">'
        . '<i class="fa-solid fa-copy" aria-hidden="true"></i><span class="hs-ftp-copy-text">' . hs_h($copyLabel) . '</span></button>'
        . '</div></div>';
}

/** @param array<string, mixed> $ctx */
function hs_panel_ftp_connection_data(array $ctx): array
{
    $user = $ctx['user'];
    $s = $ctx['hs_user_settings'];
    $domain = hs_plan_display_domain($user, $s);
    $srv = hs_server_constants($user);
    $username = (string) ($user['username'] ?? 'user');
    $ftpUser = hs_ftp_username($domain, $user);
    $ftpPath = hs_ftp_account_path($username, $user);
    return [
        'host' => hs_ftp_display_host($srv['ip'], $user),
        'hostname' => hs_ftp_display_host($domain, $user),
        'port' => '21',
        'user' => $ftpUser,
        'path' => $ftpPath,
        'url' => 'ftp://' . $ftpUser . '@' . $domain . '/' . $ftpPath,
    ];
}

/** @param array<string, mixed> $ctx */
function hs_panel_ftp_pass_tab(array $ctx): string
{
    $t = $ctx['t'];
    $userId = (string) ($ctx['user']['id'] ?? '');
    $pass = hs_ensure_ftp_password($userId);
    $ftp = hs_panel_ftp_connection_data($ctx);
    $copyLabel = $t['ftp_pass_copy'] ?? 'Copy';
    $copiedLabel = $t['ftp_pass_copied'] ?? 'Copied';

    $passBlock = $pass !== ''
        ? '<div class="hs-ftp-pass-row">'
            . '<code id="ftp-pass-value" class="hs-ftp-pass-visible">' . hs_h($pass) . '</code>'
            . '<div class="hs-ftp-pass-actions">'
            . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-ftp-pass-toggle data-target="ftp-pass-value"'
            . ' data-secret="' . hs_h($pass) . '" data-label-show="' . hs_h($t['ftp_pass_show'] ?? 'Show') . '"'
            . ' data-label-hide="' . hs_h($t['ftp_pass_hide'] ?? 'Hide') . '">'
            . hs_h($t['ftp_pass_hide'] ?? 'Hide') . '</button>'
            . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-ftp-copy-btn" data-copy-secret="ftp-pass-value" data-secret="' . hs_h($pass) . '"'
            . ' data-copied-label="' . hs_h($copiedLabel) . '"><i class="fa-solid fa-copy" aria-hidden="true"></i>'
            . '<span class="hs-ftp-copy-text">' . hs_h($copyLabel) . '</span></button>'
            . '</div></div>'
        : '<p class="hp-muted hs-ftp-pass-empty">' . hs_h($t['ftp_pass_empty'] ?? '') . '</p>';

    $guide = '<aside class="hs-ftp-guide"><h3><i class="fa-solid fa-book-open"></i> ' . hs_h($t['ftp_guide_title'] ?? 'How to connect') . '</h3><ol>';
    for ($i = 1; $i <= 3; $i++) {
        $key = 'ftp_guide_' . $i;
        if (!empty($t[$key])) {
            $guide .= '<li>' . hs_h($t[$key]) . '</li>';
        }
    }
    $guide .= '</ol></aside>';

    $body = '<div class="hs-ftp-pass-layout">'
        . $guide
        . '<div class="hs-ftp-pass-main">'
        . '<p class="hp-muted">' . hs_h($t['ftp_pass_hint'] ?? '') . '</p>'
        . '<div class="hs-ftp-creds-grid">'
        . hs_panel_ftp_copy_row($t['plan_ftp_ip'] ?? 'FTP IP', 'ftp-host', $ftp['host'], $t)
        . hs_panel_ftp_copy_row($t['plan_ftp_host'] ?? 'Hostname', 'ftp-hostname', $ftp['hostname'], $t)
        . hs_panel_ftp_copy_row($t['ftp_port'] ?? 'Port', 'ftp-port', $ftp['port'], $t)
        . hs_panel_ftp_copy_row($t['plan_ftp_user'] ?? 'FTP user', 'ftp-user', $ftp['user'], $t)
        . hs_panel_ftp_copy_row($t['plan_ftp_path'] ?? 'Path', 'ftp-path', $ftp['path'], $t)
        . '</div>'
        . '<section class="hs-ftp-pass-card">'
        . '<h3 class="hs-ftp-pass-card-title"><i class="fa-solid fa-key"></i> ' . hs_h($t['account_master_pass_label'] ?? 'Main password') . '</h3>'
        . $passBlock
        . '</section>'
        . ($pass !== ''
            ? '<div class="hs-ftp-copy-all"><button type="button" class="hs-btn hs-btn-primary hp-dash-btn-sm" id="ftp-copy-all" data-ftp-copy-all="1"'
                . ' data-copied-label="' . hs_h($copiedLabel) . '">'
                . '<i class="fa-solid fa-clipboard-list"></i> ' . hs_h($t['ftp_copy_all'] ?? 'Copy all credentials') . '</button></div>'
            : '')
        . '</div></div>';

    $accountUrl = hs_url(hs_panel_path('account.php'));
    $footer = '<a href="' . hs_h($accountUrl) . '" class="hs-btn hs-btn-primary"><i class="fa-solid fa-key"></i> '
        . hs_h($t['account_manage_pass'] ?? 'Change main password') . '</a>';

    return hs_render_card($t['tab_files_ftppass'] ?? '', $body, $footer);
}

/** @param array<string, mixed> $ctx */
function hs_panel_files_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $user = $ctx['user'];
    $s = $ctx['hs_user_settings'];
    $domain = hs_plan_display_domain($user, $s);
    $srv = hs_server_constants();
    $ftpUser = hs_ftp_username($domain, $user);
    $alerts = hs_panel_alerts($ctx);

    return match ($tab) {
        'backups' => $alerts . hs_panel_files_backups_tab($ctx),
        'ftp' => $alerts . hs_panel_ftp_overview_tab($ctx),
        'ftppass' => $alerts . hs_panel_ftp_pass_tab($ctx),
        default => $alerts . hs_panel_file_browser($user, $t, (string) ($_GET['path'] ?? '')),
    };
}

/** @param array<string, mixed> $ctx */
function hs_panel_files_backups_tab(array $ctx): string
{
    require_once dirname(__DIR__) . '/includes/backups.php';
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $schedule = (string) ($s['backup_schedule'] ?? 'day');
    $auto = !empty($s['backup_auto']);
    $freqMeta = hs_backup_frequencies()[$schedule] ?? hs_backup_frequencies()['day'];
    $scheduleLabel = hs_backup_schedule_label($schedule, $t);
    $opts = '';
    foreach (hs_backup_frequencies() as $key => $meta) {
        if ($key === 'off') {
            continue;
        }
        $opts .= '<option value="' . hs_h($key) . '"' . ($schedule === $key ? ' selected' : '') . '>'
            . hs_h(hs_backup_schedule_label($key, $t)) . '</option>';
    }
    $html = hs_panel_status_card($t['tab_files_backups'] ?? '', [
        [$t['dash_backups_schedule'] ?? '', $scheduleLabel],
        [$t['dash_backups_retention'] ?? '', (string) $freqMeta['retention'] . ' ' . ($t['backup_retention_copies'] ?? 'copies')],
        [$t['backup_auto_enable'] ?? 'Auto', $auto ? ($t['perf_cache_on'] ?? 'On') : ($t['perf_cache_off'] ?? 'Off')],
    ], '<form method="post" class="hp-stack" style="margin-bottom:.75rem">' . hs_csrf_field()
        . '<div class="hs-field"><label>' . hs_h($t['dash_backups_schedule'] ?? '') . '</label><select name="backup_schedule">' . $opts . '</select></div>'
        . '<label style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0"><input type="checkbox" name="backup_auto" value="1"' . ($auto ? ' checked' : '') . '> '
        . hs_h($t['backup_auto_enable'] ?? '') . '</label>'
        . '<button type="submit" name="save_backup_settings" value="1" class="hs-btn hs-btn-primary hp-dash-btn-sm">' . hs_h($t['btn_save'] ?? '') . '</button></form>'
        . '<form method="post" style="display:inline">' . hs_csrf_field()
        . '<button type="submit" name="create_backup" value="1" class="hs-btn hs-btn-primary hp-dash-btn-sm">' . hs_h($t['backup_create'] ?? '') . '</button></form>'
        . ' <a href="' . hs_h(hs_url(hs_panel_path('backups.php'))) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">' . hs_h($t['dash_btn_manage'] ?? '') . '</a>');
    return $html . hs_panel_backups_list($s, $t, $user);
}

function hs_panel_backups_list(array $settings, array $t = [], ?array $user = null): string
{
    $backups = is_array($settings['backups'] ?? null) ? array_reverse($settings['backups']) : [];
    if ($backups === []) {
        return '';
    }
    $rows = array_map(static function ($b) use ($t, $user) {
        $file = (string) ($b['file'] ?? '');
        $dl = ($user !== null && $file !== '' && str_ends_with(strtolower($file), '.zip'))
            ? ' <a href="' . hs_h(hs_url(hs_panel_path('backup-download.php') . '?file=' . rawurlencode($file))) . '"><i class="fa-solid fa-download"></i></a>'
            : '';
        return '<tr><td><code>' . hs_h((string) ($b['name'] ?? '')) . '</code></td><td>'
            . hs_h((string) ($b['size_mb'] ?? '')) . ' MB</td><td>' . hs_h(hs_format_date((string) ($b['created_at'] ?? ''))) . '</td><td>' . $dl . '</td></tr>';
    }, $backups);
    return hs_render_card($t['dash_backups'] ?? 'Backups', hs_panel_table_from_list($rows, [
        $t['backup_col_name'] ?? 'Name', $t['fm_size'] ?? 'Size', $t['fm_modified'] ?? 'Created', '',
    ]));
}

function hs_panel_pma_content(array $user, array $t, string $configHint): string
{
    require_once dirname(__DIR__) . '/includes/phpmyadmin.php';
    $userId = (string) ($user['id'] ?? '');
    $liveDbs = hs_pma_databases_for_user($userId);
    $pmaInstalled = is_file(dirname(__DIR__) . '/pma/index.php');

    $body = $configHint;
    if (!$pmaInstalled) {
        $body .= '<div class="hs-alert hs-alert-error">' . hs_h($t['db_pma_not_installed'] ?? '') . '</div>';
    }
    $body .= '<p class="hp-muted">' . hs_h($t['db_pma_isolated'] ?? '') . '</p>';

    if ($liveDbs === []) {
        return hs_render_card(
            $t['tab_db_pma'] ?? 'phpMyAdmin',
            $body . '<p class="hp-muted">' . hs_h($t['db_pma_empty'] ?? '') . '</p>',
            '<a href="' . hs_h(hs_panel_tab_href('databases', 'manage')) . '" class="hs-btn hs-btn-primary">' . hs_h($t['btn_create_db'] ?? '') . '</a>'
        );
    }

    $rows = [];
    foreach ($liveDbs as $db) {
        $dbId = (string) ($db['id'] ?? '');
        $rows[] = '<tr><td><code>' . hs_h((string) ($db['name'] ?? '')) . '</code></td>'
            . '<td><code>' . hs_h((string) ($db['user'] ?? '')) . '</code></td>'
            . '<td>' . hs_h((string) ($db['host'] ?? 'localhost')) . '</td>'
            . '<td>' . hs_pma_render_open_form($dbId, $t) . '</td></tr>';
    }

    return hs_render_card(
        $t['tab_db_pma'] ?? 'phpMyAdmin',
        $body . hs_panel_table_from_list($rows, [
            $t['db_name'] ?? 'Database',
            $t['db_user'] ?? 'User',
            $t['db_host'] ?? 'Host',
            '',
        ]),
        ''
    );
}

/** @param array<string, string> $t @param array<string, mixed> $s */
function hs_panel_db_remote_content(array $t, array $s): string
{
    require_once dirname(__DIR__) . '/includes/mysql-provision.php';
    $enabled = hs_mysql_provision_enabled();
    $shared = $enabled && hs_mysql_provision_shared_mode();
    $host = $enabled ? hs_mysql_provision_client_host() : 'localhost';
    $port = $enabled ? (string) hs_mysql_provision_port() : '3306';
    $ips = (string) ($s['db_remote_ips'] ?? '');
    $connExample = str_replace(
        ['{host}', '{port}'],
        [$host, $port],
        $t['db_remote_connection'] ?? 'Host: {host}, port: {port}'
    );

    $body = '<p class="hp-muted">' . hs_h($t['db_remote_hint'] ?? '') . '</p>';
    if ($shared) {
        $body .= '<div class="hs-alert hs-alert-error">' . hs_h($t['db_remote_shared'] ?? '') . '</div>';
    }
    $body .= hs_panel_status_card($t['tab_db_remote'] ?? '', [
        [$t['db_remote_access'] ?? 'Remote MySQL', !empty($s['db_remote']) ? ($t['perf_cache_on'] ?? 'On') : ($t['perf_cache_off'] ?? 'Off'), !empty($s['db_remote'])],
        [$t['db_host'] ?? 'Host', '<code>' . hs_h($host) . '</code>'],
        [$t['db_remote_port'] ?? 'Port', '<code>' . hs_h($port) . '</code>'],
    ], hs_panel_toggle_form('toggle_db_remote', !empty($s['db_remote']), $t, $t['db_remote_off'] ?? 'Disable', $t['db_remote_on'] ?? 'Enable'));

    $body .= '<form method="post" class="hp-stack" style="margin-top:1.25rem">' . hs_csrf_field()
        . '<div class="hs-field"><label>' . hs_h($t['db_remote_ips'] ?? 'Allowed IPs') . '</label>'
        . '<textarea name="db_remote_ips" rows="4" placeholder="203.0.113.10, 198.51.100.0/24"'
        . ($shared ? ' disabled' : '') . '>' . hs_h($ips) . '</textarea>'
        . '<p class="hp-muted" style="margin:.35rem 0 0;font-size:.85rem">' . hs_h($t['db_remote_ips_hint'] ?? '') . '</p></div>'
        . '<p class="hp-muted"><strong>' . hs_h($t['db_remote_connection'] ?? 'Connection') . ':</strong> ' . hs_h($connExample) . '</p>'
        . '<button type="submit" name="save_db_remote" value="1" class="hs-btn hs-btn-primary"'
        . ($shared ? ' disabled' : '') . '>' . hs_h($t['btn_save'] ?? 'Save') . '</button></form>';

    return $body;
}

/** @param array<string, mixed> $ctx */
function hs_panel_databases_content(string $tab, array $ctx): string
{
    require_once dirname(__DIR__) . '/includes/phpmyadmin.php';
    require_once dirname(__DIR__) . '/includes/panel-databases-ui.php';
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $alerts = hs_panel_alerts($ctx);
    $configHint = '';
    if (hs_is_mysql_installed()) {
        require_once dirname(__DIR__) . '/includes/mysql-provision.php';
        if (!hs_mysql_provision_enabled()) {
            $configHint = '<div class="hs-alert hs-alert-error" style="margin-bottom:1rem">'
                . hs_h($t['db_provision_config'] ?? '') . '</div>';
        } else {
            $provHost = hs_mysql_provision_client_host();
            $hintKey = hs_mysql_provision_shared_mode() ? 'db_server_hint_shared' : 'db_server_hint';
            $configHint = '<p class="hp-muted" style="margin-bottom:1rem">'
                . hs_h(str_replace('{host}', $provHost, $t[$hintKey] ?? $t['db_server_hint'] ?? ''))
                . '</p>';
        }
    }

    return match ($tab) {
        'phpmyadmin' => $alerts . hs_panel_pma_content($user, $t, $configHint),
        'remote' => $alerts . hs_panel_db_remote_content($t, $s),
        default => $alerts . '<div class="hs-db-panel">' . hs_panel_databases_manage_tab($ctx) . '</div>',
    };
}

/** @param array<string, mixed> $ctx */
function hs_panel_advanced_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $alerts = hs_panel_alerts($ctx);
    $isPro = ($user['plan'] ?? '') === 'pro';
    $pro = $isPro ? ($t['adv_available'] ?? 'Available') : ($t['adv_pro_only'] ?? 'Pro plan');

    return match ($tab) {
        'ssh' => $alerts . '<div class="hp-actions"><a href="' . hs_h(hs_url(hs_panel_path('ssh.php'))) . '" class="hs-btn hs-btn-primary">' . hs_h($t['tab_adv_ssh'] ?? 'SSH') . '</a></div>',
        'php' => $alerts . '<p class="hp-muted">' . hs_h($t['tip_php'] ?? '') . '</p><div class="hp-actions"><a href="' . hs_h(hs_url(hs_panel_path('php.php'))) . '" class="hs-btn hs-btn-primary">' . hs_h($t['nav_php'] ?? '') . '</a></div>',
        'dns' => $alerts . hs_panel_dns_zone($s, $t, hs_plan_display_domain($user, $s), $user),
        'cron' => $alerts . hs_render_card($t['tab_adv_cron'] ?? '', hs_panel_cron_table($s)
            . '<form method="post" class="hp-stack">' . hs_csrf_field()
            . '<div class="hp-grid-2"><div class="hs-field"><label>Schedule</label><input type="text" name="cron_schedule" placeholder="*/5 * * * *" required></div>'
            . '<div class="hs-field"><label>Command</label><input type="text" name="cron_cmd" required></div></div>'
            . '<button type="submit" name="add_cron" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['cron_added'] ?? 'Add cron') . '</button></form>'),
        'phpinfo' => $alerts . '<div class="hp-actions"><a href="' . hs_h(hs_url(hs_panel_path('phpinfo.php'))) . '" class="hs-btn hs-btn-primary">' . hs_h($t['tab_adv_phpinfo'] ?? 'PHP Info') . '</a>'
            . ' <a href="' . hs_h(hs_url(hs_panel_path('php.php'))) . '" class="hs-btn hs-btn-ghost">' . hs_h($t['nav_php'] ?? '') . '</a></div><p class="hp-muted">PHP ' . hs_h(PHP_VERSION) . '</p>',
        'cachemgr' => $alerts . hs_panel_status_card($t['tab_adv_cachemgr'] ?? '', [[$t['perf_cache'] ?? '', $t['perf_cache_on'] ?? '']]),
        'git' => $alerts . hs_panel_git_tab($ctx),
        'htpasswd' => $alerts . hs_render_card($t['tab_adv_htpasswd'] ?? '', '<form method="post" class="hp-stack">' . hs_csrf_field()
            . '<div class="hp-grid-2"><div class="hs-field"><label>User</label><input type="text" name="ht_user" value="' . hs_h((string) ($s['htpasswd_user'] ?? '')) . '"></div>'
            . '<div class="hs-field"><label>Password</label><input type="password" name="ht_pass" value="' . hs_h((string) ($s['htpasswd_pass'] ?? '')) . '"></div></div>'
            . '<button type="submit" name="save_htpasswd" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_save'] ?? '') . '</button></form>'),
        'ip' => $alerts . hs_render_card($t['tab_adv_ip'] ?? '', hs_panel_ip_list($s)
            . '<form method="post" class="hp-inline-form">' . hs_csrf_field()
            . '<div class="hs-field"><label>IP</label><input type="text" name="ip_addr" required></div>'
            . '<button type="submit" name="add_ip" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_add'] ?? 'Add') . '</button></form>'),
        'hotlink' => $alerts . hs_panel_status_card($t['tab_adv_hotlink'] ?? '', [
            [$t['tab_adv_hotlink'] ?? '', !empty($s['hotlink_protect']) ? ($t['perf_cache_on'] ?? 'On') : ($t['perf_cache_off'] ?? 'Off')],
        ], hs_panel_toggle_form('toggle_hotlink', !empty($s['hotlink_protect']), $t)),
        'indexing' => $alerts . hs_panel_status_card($t['tab_adv_indexing'] ?? '', [
            [$t['tab_adv_indexing'] ?? '', !empty($s['search_indexing']) ? ($t['perf_cache_on'] ?? 'On') : ($t['perf_cache_off'] ?? 'Off')],
        ], hs_panel_toggle_form('toggle_indexing', !empty($s['search_indexing']), $t)),
        'permissions' => $alerts . hs_panel_permissions_tab($ctx),
        'history' => $alerts . hs_panel_activity_log($s, $t, (string) ($user['id'] ?? '')),
        default => $alerts . hs_panel_status_card($t['nav_group_advanced'] ?? '', [
            [$t['advanced_ssh'] ?? '', $pro],
            [$t['advanced_cron'] ?? '', $pro],
            [$t['advanced_git'] ?? '', $pro],
        ]) . '<p class="hp-muted">' . hs_h($t['advanced_hint'] ?? '') . '</p>',
    };
}

/** @param list<array<string,mixed>> $subdomains */
function hs_panel_subdomains_tab(array $subdomains, string $primaryDomain, array $t): string
{
    require_once __DIR__ . '/subdomain-dns.php';
    $folderOpts = '<option value="">' . hs_h($t['subdomain_folder_root'] ?? 'public_html/ (default)') . '</option>';
    foreach (hs_public_html_folder_options() as $opt) {
        if (($opt['path'] ?? '') === '') {
            continue;
        }
        $folderOpts .= '<option value="' . hs_h((string) $opt['path']) . '">' . hs_h((string) $opt['label']) . '</option>';
    }
    $rows = '';
    if ($subdomains === []) {
        $rows = '<p class="hp-muted">' . hs_h($t['subdomain_empty'] ?? $t['domains_add'] ?? '') . '</p>';
    } else {
        $trs = '';
        foreach ($subdomains as $d) {
            $name = (string) ($d['name'] ?? '');
            $folder = (string) ($d['folder'] ?? '');
            $folderLabel = $folder === '' ? 'public_html/' : 'public_html/' . $folder . '/';
            $fullHost = $name !== '' ? $name . '.' . $primaryDomain : '';
            $trs .= '<tr><td><code>' . hs_h($fullHost) . '</code></td>'
                . '<td><code>' . hs_h($folderLabel) . '</code></td>'
                . '<td>' . hs_h(hs_format_date((string) ($d['created_at'] ?? ''))) . '</td></tr>';
        }
        $rows = '<div class="hs-table-wrap"><table class="hs-table"><thead><tr>'
            . '<th>' . hs_h($t['subdomain_col_host'] ?? 'Host') . '</th>'
            . '<th>' . hs_h($t['subdomain_col_folder'] ?? 'Folder') . '</th>'
            . '<th>' . hs_h($t['subdomain_col_created'] ?? 'Created') . '</th>'
            . '</tr></thead><tbody>' . $trs . '</tbody></table></div>';
    }
    $form = '<form method="post" class="hp-stack hs-subdomain-form">' . hs_csrf_field()
        . '<div class="hp-grid-2">'
        . '<div class="hs-field"><label>' . hs_h($t['subdomain_name_label'] ?? 'Subdomain') . '</label>'
        . '<div class="hs-subdomain-input"><input type="text" name="subdomain" pattern="[a-z0-9][a-z0-9-]*" placeholder="shop" required>'
        . '<span class="hs-subdomain-suffix">.' . hs_h($primaryDomain) . '</span></div></div>'
        . '<div class="hs-field"><label>' . hs_h($t['subdomain_folder_label'] ?? 'Document root') . '</label>'
        . '<select name="subdomain_folder">' . $folderOpts . '</select>'
        . '<p class="hs-field-hint">' . hs_h($t['subdomain_folder_hint'] ?? '') . '</p></div></div>'
        . '<button type="submit" name="add_subdomain" value="1" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-plus"></i> ' . hs_h($t['btn_add_domain'] ?? '') . '</button></form>';
    return hs_render_card($t['tab_dom_sub'] ?? '', '<p class="hp-muted">' . hs_h($t['subdomain_lead'] ?? '') . '</p>' . $rows, $form);
}

function hs_panel_dns_zone(array $settings, array $t, string $domain, ?array $user = null): string
{
    require_once __DIR__ . '/subdomain-dns.php';
    $dnsAction = hs_h(hs_url(hs_panel_tab_href('domains', 'dns')));
    $srv = hs_server_constants($user);
    $zone = hs_dns_all_records($settings, $user);
    $rows = '';
    foreach ($zone['system'] as $row) {
        $mxPri = isset($row['priority']) ? ' <span class="hp-muted">prio ' . (int) $row['priority'] . '</span>' : '';
        $rows .= '<tr class="hs-dns-row-system"><td><strong>' . hs_h((string) $row['type']) . '</strong></td>'
            . '<td><code>' . hs_h((string) $row['host']) . '</code></td>'
            . '<td><code>' . hs_h((string) $row['value']) . '</code>' . $mxPri . '</td>'
            . '<td>' . (int) ($row['ttl'] ?? 3600) . '</td>'
            . '<td><span class="hs-dns-badge">' . hs_h($t['dns_system'] ?? 'System') . '</span></td></tr>';
    }
    $recs = $zone['custom'];
    $editId = trim((string) ($_GET['edit_dns'] ?? ''));
    $dnsTypes = ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS'];
    foreach ($recs as $i => $r) {
        if (!is_array($r)) {
            continue;
        }
        $rid = (string) ($r['id'] ?? (string) $i);
        $isEdit = $editId !== '' && $editId === $rid;
        $mxPri = ($r['type'] ?? '') === 'MX' && isset($r['priority'])
            ? ' <span class="hp-muted">prio ' . (int) $r['priority'] . '</span>' : '';
        if ($isEdit) {
            $typeOpts = '';
            foreach ($dnsTypes as $dt) {
                $typeOpts .= '<option' . (($r['type'] ?? '') === $dt ? ' selected' : '') . '>' . hs_h($dt) . '</option>';
            }
            $rows .= '<tr class="hs-dns-row-edit"><td colspan="5">'
                . '<form method="post" class="hp-stack hs-dns-edit-form" action="' . $dnsAction . '">' . hs_csrf_field()
                . '<input type="hidden" name="dns_index" value="' . (int) $i . '">'
                . '<div class="hp-grid-3">'
                . '<div class="hs-field"><label>' . hs_h($t['dns_col_type'] ?? 'Type') . '</label><select name="dns_type">' . $typeOpts . '</select></div>'
                . '<div class="hs-field"><label>' . hs_h($t['dns_col_host'] ?? 'Host') . '</label>'
                . '<input type="text" name="dns_host" value="' . hs_h((string) ($r['host'] ?? '')) . '" required></div>'
                . '<div class="hs-field"><label>' . hs_h($t['dns_col_value'] ?? 'Value') . '</label>'
                . '<input type="text" name="dns_value" value="' . hs_h((string) ($r['value'] ?? '')) . '" required></div>'
                . '<div class="hs-field"><label>TTL</label><input type="number" name="dns_ttl" min="300" max="86400" value="' . (int) ($r['ttl'] ?? 3600) . '"></div>'
                . '<div class="hs-field"><label>' . hs_h($t['dns_col_priority'] ?? 'Priority') . '</label>'
                . '<input type="number" name="dns_priority" min="0" max="100" value="' . (int) ($r['priority'] ?? 10) . '"></div></div>'
                . '<button type="submit" name="edit_dns" value="1" class="hs-btn hs-btn-primary hp-dash-btn-sm">' . hs_h($t['admin_save'] ?? 'Save') . '</button>'
                . ' <a href="' . hs_h(hs_url(hs_panel_tab_href('domains', 'dns'))) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">' . hs_h($t['btn_cancel'] ?? 'Cancel') . '</a>'
                . '</form></td></tr>';
            continue;
        }
        $actions = '';
        if (empty($r['system'])) {
            $editUrl = hs_url(hs_panel_tab_href('domains', 'dns'), ['edit_dns' => $rid]);
            $actions = '<a href="' . hs_h($editUrl) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="' . hs_h($t['admin_edit'] ?? 'Edit') . '"><i class="fa-solid fa-pen"></i></a>'
                . '<form method="post" class="hs-dns-del-form" style="display:inline" action="' . $dnsAction . '" onsubmit="return confirm('
                . json_encode($t['dns_delete_confirm'] ?? 'Delete?', JSON_UNESCAPED_UNICODE) . ')">' . hs_csrf_field()
                . '<input type="hidden" name="dns_index" value="' . (int) $i . '">'
                . '<button type="submit" name="delete_dns" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-trash"></i></button></form>';
        } else {
            $actions = '<span class="hs-dns-badge">' . hs_h($t['dns_auto'] ?? 'Auto') . '</span>';
        }
        $rows .= '<tr><td><strong>' . hs_h((string) ($r['type'] ?? '')) . '</strong></td>'
            . '<td><code>' . hs_h((string) ($r['host'] ?? '')) . '</code></td>'
            . '<td><code>' . hs_h((string) ($r['value'] ?? '')) . '</code>' . $mxPri . '</td>'
            . '<td>' . (int) ($r['ttl'] ?? 3600) . '</td>'
            . '<td class="hs-dns-actions">' . $actions . '</td></tr>';
    }
    $empty = $recs === [] ? '<p class="hp-muted" style="margin:.75rem 0 0">' . hs_h($t['dns_custom_empty'] ?? '') . '</p>' : '';
    $addForm = '<form method="post" class="hp-stack hs-dns-add-form" action="' . $dnsAction . '">' . hs_csrf_field()
        . '<p class="hp-muted" style="margin:0 0 .5rem"><strong>' . hs_h($t['dns_add_custom'] ?? 'Add custom record') . '</strong></p>'
        . '<div class="hp-grid-3">'
        . '<div class="hs-field"><label>' . hs_h($t['dns_col_type'] ?? 'Type') . '</label>'
        . '<select name="dns_type"><option>A</option><option>AAAA</option><option>CNAME</option><option>TXT</option><option>MX</option><option>NS</option></select></div>'
        . '<div class="hs-field"><label>' . hs_h($t['dns_col_host'] ?? 'Host') . '</label>'
        . '<input type="text" name="dns_host" placeholder="@, www, mail" required></div>'
        . '<div class="hs-field"><label>' . hs_h($t['dns_col_value'] ?? 'Value') . '</label>'
        . '<input type="text" name="dns_value" placeholder="IP, domain or text" required></div>'
        . '<div class="hs-field"><label>TTL</label><input type="number" name="dns_ttl" min="300" max="86400" value="3600"></div>'
        . '<div class="hs-field"><label>' . hs_h($t['dns_col_priority'] ?? 'Priority (MX)') . '</label>'
        . '<input type="number" name="dns_priority" min="0" max="100" value="10"></div></div>'
        . '<button type="submit" name="add_dns" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['dns_add'] ?? 'Add DNS') . '</button></form>';

    return hs_render_card(
        $t['domains_dns_title'] ?? 'DNS zone',
        '<p class="hp-muted">' . hs_h($t['domains_dns_hint'] ?? '') . '</p>'
        . '<div class="hs-dns-zone-meta"><span><strong>' . hs_h($domain) . '</strong></span>'
        . '<span>IP <code>' . hs_h($srv['ip']) . '</code></span>'
        . '<span>NS <code>' . hs_h($srv['ns1']) . '</code> · <code>' . hs_h($srv['ns2']) . '</code></span></div>'
        . '<p class="hp-muted hs-dns-propagate">' . hs_h($t['dns_propagate_hint'] ?? '') . '</p>'
        . '<div class="hs-table-wrap"><table class="hs-table hs-dns-table"><thead><tr>'
        . '<th>' . hs_h($t['dns_col_type'] ?? 'Type') . '</th>'
        . '<th>' . hs_h($t['dns_col_host'] ?? 'Host') . '</th>'
        . '<th>' . hs_h($t['dns_col_value'] ?? 'Value') . '</th>'
        . '<th>TTL</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
        . $empty,
        $addForm
    );
}

function hs_panel_cron_table(array $settings): string
{
    $jobs = is_array($settings['cron_jobs'] ?? null) ? $settings['cron_jobs'] : [];
    if ($jobs === []) {
        return '<p class="hp-muted">No cron jobs</p>';
    }
    $rows = array_map(static fn($j) => '<tr><td><code>' . hs_h((string) ($j['schedule'] ?? '')) . '</code></td><td><code>'
        . hs_h((string) ($j['command'] ?? '')) . '</code></td></tr>', $jobs);
    return hs_panel_table_from_list($rows, ['Schedule', 'Command']);
}

function hs_panel_ip_list(array $settings): string
{
    $ips = is_array($settings['ip_allowlist'] ?? null) ? $settings['ip_allowlist'] : [];
    if ($ips === []) {
        return '<p class="hp-muted">No IPs whitelisted</p>';
    }
    return '<ul class="hp-list">' . implode('', array_map(static fn($ip) => '<li><code>' . hs_h((string) $ip) . '</code></li>', $ips)) . '</ul>';
}

/** @param array<string, mixed> $ctx */
function hs_panel_permissions_tab(array $ctx): string
{
    require_once __DIR__ . '/file-manager.php';
    $t = $ctx['t'];
    $user = $ctx['user'];
    $username = (string) ($user['username'] ?? 'user');
    $root = hs_fm_user_root($user);
    $relPath = 'public_html/' . $username;
    $current = is_dir($root) ? substr(sprintf('%o', fileperms($root)), -4) : '0755';
    $presets = ['755', '750', '775', '700'];
    $presetBtns = '';
    foreach ($presets as $p) {
        $active = $current === $p ? ' is-active' : '';
        $presetBtns .= '<button type="button" class="hs-perm-preset' . $active . '" data-perm-preset="' . hs_h($p) . '">' . hs_h($p) . '</button>';
    }
    $hint = str_replace('{path}', $relPath, $t['adv_perms_folder_hint'] ?? 'Folder: {path}');
    $body = '<p class="hp-muted">' . hs_h($t['adv_perms_hint'] ?? '') . '</p>'
        . '<div class="hs-perm-folder-card">'
        . '<div class="hs-perm-folder-head"><i class="fa-solid fa-folder-open"></i>'
        . '<div><strong>' . hs_h($relPath) . '</strong><span class="hp-muted">' . hs_h($hint) . '</span></div></div>'
        . '<div class="hs-perm-current"><span>' . hs_h($t['adv_perms_current'] ?? 'Current') . '</span>'
        . '<code id="perm-current-value">' . hs_h($current) . '</code></div>'
        . '<form method="post" class="hs-perm-form hp-stack">' . hs_csrf_field()
        . '<div class="hs-field"><label>' . hs_h($t['adv_perms_mode'] ?? 'Permissions (chmod)') . '</label>'
        . '<input type="text" name="chmod_mode" id="chmod-mode-input" value="' . hs_h($current) . '"'
        . ' pattern="[0-7]{3,4}" maxlength="4" inputmode="numeric" placeholder="0755" required></div>'
        . '<div class="hs-perm-presets"><span class="hp-muted">' . hs_h($t['adv_perms_presets'] ?? 'Quick presets') . ':</span>' . $presetBtns . '</div>'
        . '<button type="submit" name="save_folder_perms" value="1" class="hs-btn hs-btn-primary">'
        . hs_h($t['adv_perms_apply'] ?? 'Apply permissions') . '</button></form></div>'
        . '<p class="hp-muted hs-perm-note">' . hs_h($t['adv_perms_note'] ?? '') . '</p>';
    return hs_render_card($t['tab_adv_permissions'] ?? '', $body);
}

/** @param list<string> $stepKeys */
function hs_panel_api_steps_html(array $t, array $stepKeys): string
{
    $items = '';
    foreach ($stepKeys as $key) {
        $text = trim((string) ($t[$key] ?? ''));
        if ($text === '') {
            continue;
        }
        $items .= '<li>' . $text . '</li>';
    }
    if ($items === '') {
        return '';
    }
    return '<ol class="hs-api-steps">' . $items . '</ol>';
}

/** @param array<string, string> $t */
function hs_panel_api_general_form(array $s, array $t): string
{
    require_once __DIR__ . '/support-ai.php';
    $ai = hs_ai_normalize(is_array($s['ai'] ?? null) ? $s['ai'] : []);
    return '<form method="post" class="hp-stack">' . hs_csrf_field()
        . hs_render_card(
            $t['api_card_ai_title'] ?? 'General AI settings',
            '<p class="hp-muted">' . hs_h($t['api_card_ai_desc'] ?? $t['ai_settings_hint'] ?? '') . '</p>'
            . '<label class="hs-check"><input type="checkbox" name="ai_enabled" value="1"' . (!empty($ai['enabled']) ? ' checked' : '') . '> '
            . hs_h($t['ai_enabled_label'] ?? 'Enable AI assistants') . '</label>'
            . '<div class="hs-field"><label>' . hs_h($t['ai_default_provider'] ?? 'Default provider') . '</label>'
            . '<select name="ai_provider"><option value="openai"' . (($ai['provider'] ?? '') === 'openai' ? ' selected' : '') . '>OpenAI (ChatGPT)</option>'
            . '<option value="grok"' . (($ai['provider'] ?? '') === 'grok' ? ' selected' : '') . '>xAI Grok</option></select></div>'
            . '<button type="submit" name="save_ai_general" value="1" class="hs-btn hs-btn-primary">'
            . hs_h($t['btn_save'] ?? 'Save') . '</button>'
        )
        . '</form>';
}

/** @param array<string, string> $t */
function hs_panel_api_openai_form(array $s, array $t): string
{
    require_once __DIR__ . '/support-ai.php';
    $ai = hs_ai_normalize(is_array($s['ai'] ?? null) ? $s['ai'] : []);
    $providers = hs_ai_providers();
    $openaiModels = $providers['openai']['models'] ?? [];
    $openaiOpts = '';
    foreach ($openaiModels as $m) {
        $sel = ($ai['openai_model'] ?? '') === $m ? ' selected' : '';
        $openaiOpts .= '<option value="' . hs_h($m) . '"' . $sel . '>' . hs_h($m) . '</option>';
    }
    $hasOpenai = trim((string) ($ai['openai_api_key'] ?? '')) !== '';
    $status = $hasOpenai
        ? '<span class="hs-dom-status hs-dom-status-active">' . hs_h($t['api_key_configured'] ?? 'Key configured') . '</span>'
        : '<span class="hs-dom-status hs-dom-status-expiring">' . hs_h($t['api_key_missing'] ?? 'Key not set') . '</span>';
    $steps = hs_panel_api_steps_html($t, [
        'api_openai_step_1', 'api_openai_step_2', 'api_openai_step_3',
        'api_openai_step_4', 'api_openai_step_5', 'api_openai_step_6',
    ]);

    return '<form method="post" class="hp-stack">' . hs_csrf_field()
        . hs_render_card(
            $t['api_openai_instructions_title'] ?? 'How to connect ChatGPT',
            '<p class="hp-muted">' . hs_h($t['api_card_openai_desc'] ?? '') . '</p>' . $steps
        )
        . hs_render_card(
            $t['tab_api_openai'] ?? 'OpenAI (ChatGPT)',
            '<p class="hp-muted">' . hs_h($t['api_openai_lead'] ?? '') . '</p>'
            . '<p>' . $status . '</p>'
            . '<div class="hs-field"><label>' . hs_h($t['ai_openai_key'] ?? 'OpenAI API key') . '</label>'
            . '<input type="password" name="openai_api_key" autocomplete="new-password" placeholder="'
            . hs_h($hasOpenai ? ($t['ai_key_keep'] ?? 'Leave blank to keep') : 'sk-…') . '"></div>'
            . '<div class="hs-field"><label>' . hs_h($t['ai_openai_model'] ?? 'Model') . '</label><select name="openai_model">' . $openaiOpts . '</select></div>'
            . '<p class="hp-muted"><code>api.openai.com/v1</code> · ' . hs_h($t['api_openai_usage'] ?? '') . '</p>'
            . '<button type="submit" name="save_openai_settings" value="1" class="hs-btn hs-btn-primary">'
            . hs_h($t['btn_save'] ?? 'Save') . '</button>'
        )
        . '</form>';
}

/** @param array<string, string> $t */
function hs_panel_api_grok_form(array $s, array $t): string
{
    require_once __DIR__ . '/support-ai.php';
    $ai = hs_ai_normalize(is_array($s['ai'] ?? null) ? $s['ai'] : []);
    $providers = hs_ai_providers();
    $grokModels = $providers['grok']['models'] ?? [];
    $grokOpts = '';
    foreach ($grokModels as $m) {
        $sel = ($ai['grok_model'] ?? '') === $m ? ' selected' : '';
        $grokOpts .= '<option value="' . hs_h($m) . '"' . $sel . '>' . hs_h($m) . '</option>';
    }
    $hasGrok = trim((string) ($ai['grok_api_key'] ?? '')) !== '';
    $status = $hasGrok
        ? '<span class="hs-dom-status hs-dom-status-active">' . hs_h($t['api_key_configured'] ?? 'Key configured') . '</span>'
        : '<span class="hs-dom-status hs-dom-status-expiring">' . hs_h($t['api_key_missing'] ?? 'Key not set') . '</span>';
    $steps = hs_panel_api_steps_html($t, [
        'api_grok_step_1', 'api_grok_step_2', 'api_grok_step_3',
        'api_grok_step_4', 'api_grok_step_5', 'api_grok_step_6',
    ]);

    return '<form method="post" class="hp-stack">' . hs_csrf_field()
        . hs_render_card(
            $t['api_grok_instructions_title'] ?? 'How to connect Grok',
            '<p class="hp-muted">' . hs_h($t['api_card_grok_desc'] ?? '') . '</p>' . $steps
        )
        . hs_render_card(
            $t['tab_api_grok'] ?? 'xAI Grok',
            '<p class="hp-muted">' . hs_h($t['api_grok_lead'] ?? '') . '</p>'
            . '<p>' . $status . '</p>'
            . '<div class="hs-field"><label>' . hs_h($t['ai_grok_key'] ?? 'Grok API key') . '</label>'
            . '<input type="password" name="grok_api_key" autocomplete="new-password" placeholder="'
            . hs_h($hasGrok ? ($t['ai_key_keep'] ?? 'Leave blank to keep') : 'xai-…') . '"></div>'
            . '<div class="hs-field"><label>' . hs_h($t['ai_grok_model'] ?? 'Model') . '</label><select name="grok_model">' . $grokOpts . '</select></div>'
            . '<p class="hp-muted"><code>api.x.ai/v1</code> · ' . hs_h($t['api_grok_usage'] ?? '') . '</p>'
            . '<button type="submit" name="save_grok_settings" value="1" class="hs-btn hs-btn-primary">'
            . hs_h($t['btn_save'] ?? 'Save') . '</button>'
        )
        . '</form>';
}

/** @param array<string, string> $t */
function hs_panel_api_overview_content(array $t): string
{
    $openaiHref = hs_h(hs_url(hs_panel_tab_href('api', 'openai')));
    $grokHref = hs_h(hs_url(hs_panel_tab_href('api', 'grok')));
    return '<p class="hp-muted">' . hs_h($t['api_overview_lead'] ?? $t['api_section_hint'] ?? '') . '</p>'
        . '<div class="hp-stack">'
        . hs_render_card(
            $t['tab_api_openai'] ?? 'OpenAI (ChatGPT)',
            '<p class="hp-muted">' . hs_h($t['api_card_openai_desc'] ?? '') . '</p>',
            '<a href="' . $openaiHref . '" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-robot"></i> '
            . hs_h($t['api_configure'] ?? 'Configure') . '</a>'
        )
        . hs_render_card(
            $t['tab_api_grok'] ?? 'xAI Grok',
            '<p class="hp-muted">' . hs_h($t['api_card_grok_desc'] ?? '') . '</p>',
            '<a href="' . $grokHref . '" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-brain"></i> '
            . hs_h($t['api_configure'] ?? 'Configure') . '</a>'
        )
        . '</div>';
}

/** @param array<string, mixed> $ctx */
function hs_panel_api_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $alerts = hs_panel_alerts($ctx);
    return match ($tab) {
        'openai' => $alerts . hs_panel_api_openai_form($s, $t),
        'grok' => $alerts . hs_panel_api_grok_form($s, $t),
        default => $alerts
            . hs_panel_api_general_form($s, $t)
            . hs_render_card($t['tab_api_overview'] ?? $t['nav_group_api'] ?? 'API', hs_panel_api_overview_content($t)),
    };
}

function hs_panel_activity_log(array $settings, array $t = [], ?string $userId = null): string
{
    require_once __DIR__ . '/activity-log.php';
    $uid = $userId ?? '';
    if ($uid === '') {
        return hs_render_card($t['tab_adv_history'] ?? 'History', '<p class="hp-muted">' . hs_h($t['adv_history_empty'] ?? 'No activity yet') . '</p>');
    }
    $pager = hs_activity_log_page($uid, 1, 50);
    $title = $t['tab_adv_history'] ?? 'History';
    if ($pager['entries'] === []) {
        return hs_render_card($title, '<p class="hp-muted">' . hs_h($t['adv_history_empty'] ?? 'No activity yet') . '</p>');
    }
    $rows = array_map(static function ($e) use ($t) {
        $action = (string) ($e['action'] ?? '');
        $type = (string) ($e['type'] ?? 'change');
        $label = $action !== '' ? hs_panel_log_action_label($action, $t) : hs_activity_log_type_label($type, $t);
        return '<tr><td class="hs-log-when">' . hs_h(hs_format_date((string) ($e['at'] ?? ''))) . '</td>'
            . '<td class="hs-log-action"><span class="hs-log-action-label">' . hs_h($label) . '</span>'
            . ($action !== '' && $label !== $action ? '<code class="hs-log-action-code">' . hs_h($action) . '</code>' : '')
            . '</td><td class="hs-log-detail">' . hs_h((string) ($e['detail'] ?? '')) . '</td></tr>';
    }, $pager['entries']);
    $count = $pager['total'];
    $hint = str_replace('{count}', (string) $count, $t['adv_history_count'] ?? '{count} entries');
    $more = $count > 50
        ? '<p class="hp-muted"><a href="' . hs_h(hs_url(hs_panel_path('analytics.php'))) . '">' . hs_h($t['analytics_view_all'] ?? 'View full log') . '</a></p>'
        : '';
    return hs_render_card($title, '<p class="hp-muted">' . hs_h($hint) . '</p>'
        . '<div class="hs-activity-log-wrap">' . hs_panel_table_from_list($rows, [
            $t['adv_history_col_when'] ?? 'When',
            $t['adv_history_col_action'] ?? 'Action',
            $t['adv_history_col_detail'] ?? 'Detail',
        ]) . '</div>' . $more);
}

/** @param array<string, mixed> $ctx */
function hs_panel_wordpress_content(string $tab, array $ctx): string
{
    $t = $ctx['t'];
    $user = $ctx['user'];
    $can = hs_user_can_add_site($user);
    $s = $ctx['hs_user_settings'];
    $alerts = hs_panel_alerts($ctx);
    $wpSites = hs_wordpress_sites_for_user($user);

    if ($tab === 'security') {
        $body = '';
        if ($wpSites === []) {
            $body = '<p class="hp-muted">' . hs_h($t['wp_no_installs'] ?? 'No WordPress sites yet.') . '</p>';
        } else {
            foreach ($wpSites as $wp) {
                $siteId = (string) ($wp['id'] ?? '');
                $url = (string) ($wp['site_url'] ?? hs_public_url((string) ($user['username'] ?? ''), (string) ($wp['slug'] ?? '')));
                $adminUrl = rtrim($url, '/') . '/wp-admin/';
                $body .= '<form method="post" class="hp-stack hp-card" style="margin-bottom:1rem">'
                    . hs_csrf_field()
                    . '<input type="hidden" name="wp_site_id" value="' . hs_h($siteId) . '">'
                    . '<h3 class="hp-card-title"><i class="fa-brands fa-wordpress"></i> ' . hs_h((string) ($wp['title'] ?? $wp['slug'] ?? '')) . '</h3>'
                    . '<div class="hp-card-body">'
                    . '<p><code>' . hs_h((string) ($user['username'] ?? '') . '/' . ($wp['slug'] ?? '')) . '</code></p>'
                    . '<div class="hs-field"><label>' . hs_h($t['wp_site_title'] ?? 'Site title') . '</label>'
                    . '<input type="text" name="wp_site_title" value="' . hs_h((string) ($wp['title'] ?? '')) . '"></div>'
                    . '<div class="hs-field"><label>' . hs_h($t['wp_admin_email'] ?? 'Admin email') . '</label>'
                    . '<input type="email" name="wp_admin_email" value="' . hs_h((string) ($wp['admin_email'] ?? '')) . '"></div>'
                    . '<div class="hs-field"><label>' . hs_h($t['wp_db_name'] ?? 'Database') . '</label><input type="text" readonly value="' . hs_h((string) ($wp['db_name'] ?? '')) . '"></div>'
                    . '<div class="hs-field"><label>' . hs_h($t['wp_admin_user'] ?? 'Admin user') . '</label><input type="text" readonly value="' . hs_h((string) ($wp['admin_user'] ?? 'admin')) . '"></div>'
                    . '<label class="hs-check"><input type="checkbox" name="wp_auto_update" value="1"' . (!empty($wp['auto_update']) ? ' checked' : '') . '> '
                    . hs_h($t['wp_auto_update_label'] ?? 'Auto-update WordPress') . '</label>'
                    . '</div><div class="hp-card-foot hp-actions">'
                    . '<button type="submit" name="save_wp_security" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_save'] ?? 'Save') . '</button>'
                    . '<a href="' . hs_h($url) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost">' . hs_h($t['panel_open'] ?? 'Open') . '</a>'
                    . '<a href="' . hs_h($adminUrl) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost">' . hs_h($t['wp_open_admin'] ?? 'wp-admin') . '</a>'
                    . '</div></form>';
            }
        }
        return $alerts . hs_render_card($t['tab_wp_security'] ?? '', $body);
    }

    $listRows = [];
    foreach ($wpSites as $wp) {
        $url = (string) ($wp['site_url'] ?? hs_public_url((string) ($user['username'] ?? ''), (string) ($wp['slug'] ?? '')));
        $listRows[] = '<tr><td>' . hs_h((string) ($wp['title'] ?? '')) . '</td><td><code>' . hs_h((string) ($wp['slug'] ?? '')) . '</code></td>'
            . '<td>' . hs_h((string) ($wp['db_name'] ?? '')) . '</td><td>' . hs_h(hs_format_date((string) ($wp['installed_at'] ?? $wp['created_at'] ?? ''))) . '</td>'
            . '<td><a href="' . hs_h($url) . '" target="_blank" rel="noopener">' . hs_h($t['panel_open'] ?? '') . '</a></td></tr>';
    }
    $list = $listRows === []
        ? '<p class="hp-muted">' . hs_h($t['wp_no_installs'] ?? 'No WordPress sites yet.') . '</p>'
        : hs_panel_table_from_list($listRows, [$t['wp_site_title'] ?? 'Title', $t['installer_slug'] ?? 'Folder', $t['wp_db_name'] ?? 'Database', $t['panel_created'] ?? 'Created', '']);

    $installForm = '';
    if ($can) {
        $installForm = hs_render_card($t['wp_install'] ?? 'Install WordPress', '<form method="post" class="hp-stack">' . hs_csrf_field()
            . hs_install_path_fields_html($user, $t, 'wp_slug', 'wp-slug')
            . '<div class="hs-field"><label>' . hs_h($t['wp_site_title'] ?? 'Site title') . '</label>'
            . '<input type="text" name="wp_title" placeholder="My Blog"></div>'
            . '<div class="hs-field"><label>' . hs_h($t['wp_admin_user'] ?? 'Admin user') . '</label>'
            . '<input type="text" name="wp_admin_user" value="admin" pattern="[a-zA-Z0-9_-]+"></div>'
            . '<div class="hs-field"><label>' . hs_h($t['wp_admin_email'] ?? 'Admin email') . '</label>'
            . '<input type="email" name="wp_admin_email" required placeholder="admin@example.com"></div>'
            . '<div class="hs-field"><label>' . hs_h($t['wp_admin_pass'] ?? 'Admin password') . '</label>'
            . '<input type="password" name="wp_admin_pass" required minlength="8" autocomplete="new-password"></div>'
            . '<p class="hp-muted">' . hs_h($t['wp_install_hint'] ?? 'Downloads WordPress core, creates MySQL database and wp-config.php.') . '</p>'
            . '<button type="submit" name="install_wordpress" value="1" class="hs-btn hs-btn-primary"><i class="fa-brands fa-wordpress"></i> '
            . hs_h($t['wp_install'] ?? 'Install') . '</button></form>');
    } else {
        $installForm = '<p class="hp-muted">' . hs_h($t['installer_error_limit'] ?? '') . '</p>';
    }

    return $alerts . '<p class="hp-muted">' . hs_h($t['wp_desc'] ?? '') . '</p>'
        . $installForm
        . hs_render_card($t['wp_installed_list'] ?? 'Installed WordPress', $list);
}

function hs_panel_sites_table(array $user, array $sites, array $t): string
{
    require_once __DIR__ . '/landing-builder.php';
    $rows = '';
    if ($sites === []) {
        $rows = '<tr><td colspan="5" class="hp-muted">' . hs_h($t['panel_no_sites'] ?? '') . '</td></tr>';
    } else {
        foreach ($sites as $site) {
            $pathLabel = 'public_html/' . hs_install_path_rel($user, $site);
            $liveUrl = hs_public_url_for_site($user, $site);
            $isLanding = hs_site_is_landing_builder($site);
            $actions = '<div class="hs-site-actions">';
            if ($isLanding) {
                $actions .= '<a href="' . hs_h(hs_url(hs_panel_path('landing-builder.php'))) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm">'
                    . '<i class="fa-solid fa-paintbrush"></i> ' . hs_h($t['panel_edit_builder'] ?? 'Edit in builder') . '</a>';
            }
            $actions .= '<a href="' . hs_h($liveUrl) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> '
                . hs_h($t['panel_open'] ?? '') . '</a>';
            $siteId = (string) ($site['id'] ?? '');
            if ($siteId !== '') {
                $confirm = $t['site_delete_confirm'] ?? 'Delete this website permanently? Files will be removed.';
                $actions .= '<form method="post" class="hs-dns-del-form" style="display:inline" onsubmit="return confirm('
                    . json_encode($confirm, JSON_UNESCAPED_UNICODE) . ')">' . hs_csrf_field()
                    . '<input type="hidden" name="site_id" value="' . hs_h($siteId) . '">'
                    . '<button type="submit" name="delete_site" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="'
                    . hs_h($t['site_delete'] ?? 'Delete') . '"><i class="fa-solid fa-trash"></i></button></form>';
            }
            $actions .= '</div>';
            $appLabel = $isLanding
                ? ($t['panel_app_landing'] ?? 'Landing builder')
                : (string) ($site['app'] ?? '');
            $rows .= '<tr><td>' . hs_h($site['title'] ?? '') . '</td>'
                . '<td><code>' . hs_h($pathLabel) . '</code>'
                . '<div class="hp-muted hs-site-url-hint"><a href="' . hs_h($liveUrl) . '" target="_blank" rel="noopener">' . hs_h($liveUrl) . '</a></div></td>'
                . '<td>' . hs_h($appLabel) . '</td><td>' . hs_h(hs_format_date((string) ($site['created_at'] ?? ''))) . '</td>'
                . '<td>' . $actions . '</td></tr>';
        }
    }
    $thTitle = $t['sites_col_title'] ?? 'Title';
    $thFolder = $t['sites_col_folder'] ?? 'Folder';
    $thApp = $t['sites_col_app'] ?? 'App';
    $thCreated = $t['sites_col_created'] ?? 'Created';
    $thActions = $t['sites_col_actions'] ?? '';

    $folder = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    $tenantHint = '<p class="hp-muted hs-tenant-notice"><i class="fa-solid fa-lock"></i> '
        . hs_h(str_replace('{folder}', $folder, $t['tenant_folder_hint'] ?? 'Your sites and files are in public_html/{folder}/ — other accounts cannot access them.'))
        . '</p>';

    return $tenantHint . '<div class="hs-table-wrap"><table class="hs-table"><thead><tr><th>' . hs_h($thTitle) . '</th><th>' . hs_h($thFolder) . '</th><th>' . hs_h($thApp) . '</th><th>' . hs_h($thCreated) . '</th>'
        . ($thActions !== '' ? '<th>' . hs_h($thActions) . '</th>' : '<th></th>')
        . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
}

function hs_panel_file_browser(array $user, array $t, string $rel): string
{
    require_once __DIR__ . '/file-manager.php';
    require_once __DIR__ . '/security.php';
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    $rel = hs_fm_norm_rel($rel);
    $i18n = [
        'fm_name' => $t['fm_name'] ?? 'Name',
        'fm_size' => $t['fm_size'] ?? 'Size',
        'fm_modified' => $t['fm_modified'] ?? 'Modified',
        'fm_perms' => $t['fm_perms'] ?? 'Permissions',
        'fm_empty' => $t['fm_empty'] ?? 'This folder is empty',
        'fm_folder_name' => $t['fm_folder_name'] ?? 'Folder name',
        'fm_file_name' => $t['fm_file_name'] ?? 'File name',
        'fm_new_folder' => $t['fm_new_folder'] ?? 'New folder',
        'fm_new_file' => $t['fm_new_file'] ?? 'New file',
        'fm_upload' => $t['fm_upload'] ?? 'Upload',
        'fm_refresh' => $t['fm_refresh'] ?? 'Refresh',
        'fm_search' => $t['fm_search'] ?? 'Search in folder…',
        'fm_download' => $t['fm_download'] ?? 'Download',
        'fm_rename' => $t['fm_rename'] ?? 'Rename',
        'fm_delete' => $t['fm_delete'] ?? 'Delete',
        'fm_duplicate' => $t['fm_duplicate'] ?? 'Duplicate',
        'fm_chmod' => $t['fm_chmod'] ?? 'Permissions',
        'fm_save' => $t['fm_save'] ?? 'Save',
        'fm_close' => $t['fm_close'] ?? 'Close',
        'fm_close_tab' => $t['fm_close_tab'] ?? 'Close tab',
        'fm_saved' => $t['fm_saved'] ?? 'Saved',
        'fm_created' => $t['fm_created'] ?? 'Created',
        'fm_deleted' => $t['fm_deleted'] ?? 'Deleted',
        'fm_renamed' => $t['fm_renamed'] ?? 'Renamed',
        'fm_duplicated' => $t['fm_duplicated'] ?? 'Duplicated',
        'fm_uploaded' => $t['fm_uploaded'] ?? 'Upload complete',
        'fm_uploading' => $t['fm_uploading'] ?? 'Uploading…',
        'fm_error' => $t['fm_error'] ?? 'Action failed',
        'fm_binary' => $t['fm_binary'] ?? 'Binary file — download to edit',
        'fm_too_large' => $t['fm_too_large'] ?? 'File too large to edit (max 2 MB)',
        'fm_confirm_delete' => $t['fm_confirm_delete'] ?? 'Delete this item?',
        'fm_confirm_delete_title' => $t['fm_confirm_delete_title'] ?? 'Confirm delete',
        'fm_rename_title' => $t['fm_rename_title'] ?? 'Rename',
        'fm_new_folder_title' => $t['fm_new_folder_title'] ?? 'New folder',
        'fm_new_file_title' => $t['fm_new_file_title'] ?? 'New file',
        'fm_chmod_title' => $t['fm_chmod_title'] ?? 'Change permissions',
        'fm_rename_prompt' => $t['fm_rename_prompt'] ?? 'New name',
        'fm_unsaved' => $t['fm_unsaved'] ?? 'Discard unsaved changes?',
        'fm_disk' => $t['fm_disk'] ?? 'Disk used',
        'fm_drop' => $t['fm_drop'] ?? 'Drop files to upload',
        'fm_shortcuts' => $t['fm_shortcuts'] ?? 'Ctrl+S save · Ctrl+W close tab',
        'fm_cancel' => $t['fm_cancel'] ?? 'Cancel',
        'fm_ok' => $t['fm_ok'] ?? 'OK',
        'fm_create' => $t['fm_create'] ?? 'Create',
        'fm_preview' => $t['fm_preview'] ?? 'Preview',
        'fm_editor' => $t['fm_editor'] ?? 'Editor',
        'fm_no_preview' => $t['fm_no_preview'] ?? 'No preview for this file type',
        'fm_chmod_hint' => $t['fm_chmod_hint'] ?? 'e.g. 0644 for files, 0755 for folders',
        'fm_archive' => $t['fm_archive'] ?? 'Create ZIP archive',
        'fm_archive_title' => $t['fm_archive_title'] ?? 'Archive name',
        'fm_archived' => $t['fm_archived'] ?? 'Archive created',
        'fm_archive_too_large' => $t['fm_archive_too_large'] ?? 'Content too large (max 100 MB)',
        'fm_extract' => $t['fm_extract'] ?? 'Extract ZIP',
        'fm_extract_title' => $t['fm_extract_title'] ?? 'Extract archive',
        'fm_confirm_extract' => $t['fm_confirm_extract'] ?? 'Extract this ZIP archive to a new folder?',
        'fm_extracted' => $t['fm_extracted'] ?? 'Archive extracted',
        'fm_not_archive' => $t['fm_not_archive'] ?? 'Only .zip archives can be extracted',
    ];
    $GLOBALS['panel_fm_mode'] = true;
    $GLOBALS['panel_hide_tip'] = true;
    $GLOBALS['panel_hide_tabs'] = true;

    $filesScript = hs_panel_path('files.php');
    $sectionNav = '';
    foreach (hs_panel_section_tabs('files') as $ti) {
        if ($ti['id'] === 'manager') {
            continue;
        }
        $label = $t[$ti['label_key']] ?? $ti['label_key'];
        $sectionNav .= '<a href="' . hs_h(hs_panel_tab_url($filesScript, $ti['id'])) . '" class="hs-fm-sec-link">' . hs_h($label) . '</a>';
    }

    $tenantHint = '<p class="hp-muted hs-tenant-notice hs-fm-tenant-notice"><i class="fa-solid fa-lock"></i> '
        . hs_h(str_replace('{folder}', $username, $t['tenant_folder_hint'] ?? 'Your sites and files are in public_html/{folder}/ — other accounts cannot access them.'))
        . '</p>';

    return $tenantHint . '<div id="hs-file-manager" class="hs-fm" data-fm-drop>'
        . '<div class="hs-fm-toolbar">'
        . '<div class="hs-fm-toolbar-left">'
        . '<button type="button" class="hs-fm-tool" data-fm-action="mkdir" title="' . hs_h($i18n['fm_new_folder']) . '"><i class="fa-solid fa-folder-plus"></i><span>' . hs_h($i18n['fm_new_folder']) . '</span></button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="newfile" title="' . hs_h($i18n['fm_new_file']) . '"><i class="fa-solid fa-file-circle-plus"></i><span>' . hs_h($i18n['fm_new_file']) . '</span></button>'
        . '<button type="button" class="hs-fm-tool hs-fm-tool-primary" data-fm-action="upload"><i class="fa-solid fa-cloud-arrow-up"></i><span>' . hs_h($i18n['fm_upload']) . '</span></button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="refresh" title="' . hs_h($i18n['fm_refresh']) . '"><i class="fa-solid fa-rotate"></i></button>'
        . ($sectionNav !== '' ? '<span class="hs-fm-sec-nav">' . $sectionNav . '</span>' : '')
        . '<input type="file" data-fm-file-input multiple hidden>'
        . '</div>'
        . '<div class="hs-fm-toolbar-right">'
        . '<input type="search" class="hs-fm-search" data-fm-search placeholder="' . hs_h($i18n['fm_search']) . '" autocomplete="off">'
        . '<button type="button" class="hs-fm-view-btn" data-fm-action="view-list" data-fm-view-list title="List"><i class="fa-solid fa-list"></i></button>'
        . '<button type="button" class="hs-fm-view-btn" data-fm-action="view-grid" data-fm-view-grid title="Grid"><i class="fa-solid fa-grip"></i></button>'
        . '<span class="hs-fm-disk" data-fm-disk></span>'
        . '</div></div>'
        . '<div class="hs-fm-upload-bar" data-fm-upload-bar hidden><div class="hs-fm-upload-fill" data-fm-upload-fill></div><span data-fm-upload-label></span></div>'
        . '<div class="hs-fm-body">'
        . '<aside class="hs-fm-sidebar" data-fm-sidebar><div class="hs-fm-sidebar-title"><i class="fa-solid fa-sitemap"></i> ' . hs_h($t['fm_tree'] ?? 'Folders') . '</div><div class="hs-fm-tree-wrap" data-fm-tree></div></aside>'
        . '<div class="hs-fm-resizer" data-fm-resizer-sidebar aria-hidden="true"></div>'
        . '<div class="hs-fm-main" data-fm-drop>'
        . '<div class="hs-fm-bc" data-fm-bc></div>'
        . '<div class="hs-fm-drop-hint"><i class="fa-solid fa-cloud-arrow-up"></i> ' . hs_h($i18n['fm_drop']) . '</div>'
        . '<div class="hs-fm-workspace" data-fm-workspace>'
        . '<div class="hs-fm-list-pane" data-fm-list-pane><div class="hs-fm-list-wrap" data-fm-list></div></div>'
        . '<div class="hs-fm-split-resizer" data-fm-resizer-split hidden aria-hidden="true"></div>'
        . '<div class="hs-fm-pane" data-fm-pane hidden>'
        . '<div class="hs-fm-tabs" data-fm-tabs></div>'
        . '<div class="hs-fm-pane-toolbar">'
        . '<span class="hs-fm-hint" data-fm-pane-hint>' . hs_h($i18n['fm_shortcuts']) . '</span>'
        . '<button type="button" class="hs-fm-tool hs-fm-tool-save" data-fm-action="save" data-fm-save disabled><i class="fa-solid fa-floppy-disk"></i> ' . hs_h($i18n['fm_save']) . '</button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="chmod" data-fm-chmod-btn hidden><i class="fa-solid fa-lock"></i> ' . hs_h($i18n['fm_chmod']) . '</button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="close-pane"><i class="fa-solid fa-xmark"></i></button>'
        . '</div>'
        . '<div class="hs-fm-editor-mount" data-fm-editor></div>'
        . '<div class="hs-fm-preview" data-fm-preview hidden></div>'
        . '</div></div></div></div>'
        . '<div class="hs-fm-ctx" data-fm-ctx hidden></div>'
        . '<div class="hs-fm-modal-backdrop" data-fm-modal-backdrop hidden>'
        . '<div class="hs-fm-modal" role="dialog" aria-modal="true"><h3 data-fm-modal-title></h3>'
        . '<input type="text" class="hs-fm-modal-input" data-fm-modal-input autocomplete="off">'
        . '<p class="hs-fm-modal-msg" data-fm-modal-msg hidden></p>'
        . '<div class="hs-fm-modal-actions">'
        . '<button type="button" class="hs-fm-tool" data-fm-modal-cancel>' . hs_h($i18n['fm_cancel']) . '</button>'
        . '<button type="button" class="hs-fm-tool hs-fm-tool-primary" data-fm-modal-ok>' . hs_h($i18n['fm_ok']) . '</button>'
        . '</div></div></div>'
        . '<div class="hs-fm-toast" data-fm-toast></div>'
        . '</div>'
        . '<script>window.HS_FM=' . json_encode([
            'api' => hs_url(hs_panel_path('files-api.php')),
            'csrf' => hs_csrf_token(),
            'rootLabel' => 'public_html/' . $username,
            'startPath' => $rel,
            'i18n' => $i18n,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>';
}