<?php
declare(strict_types=1);

/** @return list<array{id:string,label_key:string,nav_key:string}> */
function hs_panel_section_tabs(string $section): array
{
    $tabs = [
        'performance' => [
            ['id' => 'overview', 'label_key' => 'tab_perf_overview', 'nav_key' => 'performance'],
            ['id' => 'ai', 'label_key' => 'tab_perf_ai', 'nav_key' => 'perf-ai'],
            ['id' => 'cache', 'label_key' => 'tab_perf_cache', 'nav_key' => 'perf-cache'],
            ['id' => 'speed', 'label_key' => 'tab_perf_speed', 'nav_key' => 'perf-speed'],
            ['id' => 'cdn', 'label_key' => 'tab_perf_cdn', 'nav_key' => 'perf-cdn'],
        ],
        'security' => [
            ['id' => 'overview', 'label_key' => 'tab_sec_overview', 'nav_key' => 'security'],
            ['id' => 'malware', 'label_key' => 'tab_sec_malware', 'nav_key' => 'sec-malware'],
            ['id' => 'wpupdate', 'label_key' => 'tab_sec_wpupdate', 'nav_key' => 'sec-wpupdate'],
            ['id' => 'ssl', 'label_key' => 'tab_sec_ssl', 'nav_key' => 'sec-ssl'],
        ],
        'domains' => [
            ['id' => 'overview', 'label_key' => 'tab_dom_overview', 'nav_key' => 'domains'],
            ['id' => 'contacts', 'label_key' => 'tab_dom_contacts', 'nav_key' => 'dom-contacts'],
            ['id' => 'dns', 'label_key' => 'tab_dom_dns', 'nav_key' => 'dom-dns'],
            ['id' => 'subdomains', 'label_key' => 'tab_dom_sub', 'nav_key' => 'dom-sub'],
            ['id' => 'parked', 'label_key' => 'tab_dom_parked', 'nav_key' => 'dom-parked'],
            ['id' => 'redirect', 'label_key' => 'tab_dom_redirect', 'nav_key' => 'dom-redirect'],
            ['id' => 'register', 'label_key' => 'tab_dom_register', 'nav_key' => 'dom-register', 'hidden' => true],
        ],
        'websites' => [
            ['id' => 'overview', 'label_key' => 'tab_site_overview', 'nav_key' => 'websites'],
            ['id' => 'installer', 'label_key' => 'tab_site_installer', 'nav_key' => 'installer'],
            ['id' => 'migrate', 'label_key' => 'tab_site_migrate', 'nav_key' => 'site-migrate'],
            ['id' => 'copy', 'label_key' => 'tab_site_copy', 'nav_key' => 'site-copy'],
            ['id' => 'errors', 'label_key' => 'tab_site_errors', 'nav_key' => 'site-errors'],
        ],
        'files' => [
            ['id' => 'manager', 'label_key' => 'tab_files_manager', 'nav_key' => 'files'],
            ['id' => 'backups', 'label_key' => 'tab_files_backups', 'nav_key' => 'files-backups'],
            ['id' => 'ftp', 'label_key' => 'tab_files_ftp', 'nav_key' => 'files-ftp'],
            ['id' => 'ftppass', 'label_key' => 'tab_files_ftppass', 'nav_key' => 'files-ftppass'],
        ],
        'databases' => [
            ['id' => 'manage', 'label_key' => 'tab_db_manage', 'nav_key' => 'databases'],
            ['id' => 'phpmyadmin', 'label_key' => 'tab_db_pma', 'nav_key' => 'db-pma'],
            ['id' => 'remote', 'label_key' => 'tab_db_remote', 'nav_key' => 'db-remote'],
        ],
        'advanced' => [
            ['id' => 'overview', 'label_key' => 'tab_adv_overview', 'nav_key' => 'advanced'],
            ['id' => 'ssh', 'label_key' => 'tab_adv_ssh', 'nav_key' => 'adv-ssh'],
            ['id' => 'php', 'label_key' => 'nav_php', 'nav_key' => 'php'],
            ['id' => 'dns', 'label_key' => 'tab_adv_dns', 'nav_key' => 'adv-dns'],
            ['id' => 'cron', 'label_key' => 'tab_adv_cron', 'nav_key' => 'adv-cron'],
            ['id' => 'phpinfo', 'label_key' => 'tab_adv_phpinfo', 'nav_key' => 'adv-phpinfo'],
            ['id' => 'cachemgr', 'label_key' => 'tab_adv_cachemgr', 'nav_key' => 'adv-cache'],
            ['id' => 'git', 'label_key' => 'tab_adv_git', 'nav_key' => 'adv-git'],
            ['id' => 'htpasswd', 'label_key' => 'tab_adv_htpasswd', 'nav_key' => 'adv-htpasswd'],
            ['id' => 'ip', 'label_key' => 'tab_adv_ip', 'nav_key' => 'adv-ip'],
            ['id' => 'hotlink', 'label_key' => 'tab_adv_hotlink', 'nav_key' => 'adv-hotlink'],
            ['id' => 'indexing', 'label_key' => 'tab_adv_indexing', 'nav_key' => 'adv-indexing'],
            ['id' => 'permissions', 'label_key' => 'tab_adv_permissions', 'nav_key' => 'adv-perms'],
            ['id' => 'history', 'label_key' => 'tab_adv_history', 'nav_key' => 'adv-history'],
        ],
        'api' => [
            ['id' => 'overview', 'label_key' => 'tab_api_overview', 'nav_key' => 'api'],
            ['id' => 'openai', 'label_key' => 'tab_api_openai', 'nav_key' => 'api-openai'],
            ['id' => 'grok', 'label_key' => 'tab_api_grok', 'nav_key' => 'api-grok'],
        ],
        'wordpress' => [
            ['id' => 'overview', 'label_key' => 'tab_wp_overview', 'nav_key' => 'wordpress'],
            ['id' => 'security', 'label_key' => 'tab_wp_security', 'nav_key' => 'wp-security'],
        ],
    ];
    return $tabs[$section] ?? [];
}

function hs_panel_tab_id(string $section, ?string $requested = null): string
{
    $tabs = hs_panel_section_tabs($section);
    if ($tabs === []) {
        return 'overview';
    }
    $ids = array_column($tabs, 'id');
    if ($requested !== null && $requested !== '' && in_array($requested, $ids, true)) {
        return $requested;
    }
    return $tabs[0]['id'];
}

function hs_panel_tab_nav_key(string $section, string $tabId): string
{
    foreach (hs_panel_section_tabs($section) as $tab) {
        if ($tab['id'] === $tabId) {
            return $tab['nav_key'];
        }
    }
    return $section;
}

function hs_panel_tab_url(string $script, string $tabId, array $extra = []): string
{
    $q = array_merge(['tab' => $tabId], $extra);
    return hs_url($script, $q);
}

/** Relative panel path with ?tab= for sidebar/search (layout wraps with hs_url). */
function hs_panel_tab_href(string $section, string $tabId): string
{
    $tabs = hs_panel_section_tabs($section);
    $rel = hs_panel_path($section . '.php');
    $first = $tabs[0]['id'] ?? 'overview';
    if ($tabId === $first) {
        return $rel;
    }
    return $rel . '?tab=' . rawurlencode($tabId);
}

function hs_panel_render_tabs(string $section, string $activeTab, array $t, string $script): string
{
    if (!empty($GLOBALS['panel_hide_tabs'])) {
        return '';
    }
    $tabs = hs_panel_section_tabs($section);
    if (count($tabs) < 2) {
        return '';
    }
    $html = '<nav class="hp-tabs" aria-label="Section tabs"><div class="hp-tabs-scroll">';
    foreach ($tabs as $tab) {
        if (!empty($tab['hidden']) && $tab['id'] !== $activeTab) {
            continue;
        }
        $label = $t[$tab['label_key']] ?? $tab['label_key'];
        $cls = $tab['id'] === $activeTab ? ' active' : '';
        $html .= '<a href="' . hs_h(hs_panel_tab_url($script, $tab['id'])) . '" class="hp-tab' . $cls . '">' . hs_h($label) . '</a>';
    }
    return $html . '</div></nav>';
}

function hs_panel_all_search_items(array $t): array
{
    $out = [];
    foreach (hs_panel_nav_groups($t) as $group) {
        foreach ($group['items'] ?? [] as $item) {
            $out[] = ['label' => $item['label'], 'url' => $item['url'], 'key' => $item['key']];
        }
    }
    foreach (['performance', 'security', 'domains', 'websites', 'files', 'databases', 'advanced', 'api', 'wordpress'] as $sec) {
        foreach (hs_panel_section_tabs($sec) as $tab) {
            $out[] = [
                'label' => $t[$tab['label_key']] ?? $tab['label_key'],
                'url' => hs_panel_tab_href($sec, $tab['id']),
                'key' => $tab['nav_key'],
            ];
        }
    }
    $out[] = [
        'label' => $t['nav_support'] ?? $t['tab_site_support'] ?? 'Support',
        'url' => hs_panel_path('support.php'),
        'key' => 'site-support',
    ];
    return $out;
}