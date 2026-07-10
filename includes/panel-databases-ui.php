<?php
declare(strict_types=1);

require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/phpmyadmin.php';
require_once __DIR__ . '/plan-specs.php';

/** @param array<string, mixed> $ctx */
function hs_panel_databases_manage_tab(array $ctx): string
{
    $t = $ctx['t'];
    $s = $ctx['hs_user_settings'];
    $user = $ctx['user'];
    $dbs = is_array($s['databases'] ?? null) ? $s['databases'] : [];
    $dbLimit = hs_user_database_limit($user);
    $dbCount = count($dbs);
    $canCreate = $dbCount < $dbLimit;
    $isDemo = function_exists('hs_is_demo_panel_user') && hs_is_demo_panel_user($user);

    $configHint = '';
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/mysql-provision.php';
        if (!hs_mysql_provision_enabled()) {
            $configHint = '<div class="hs-alert hs-alert-error hs-db-alert">' . hs_h($t['db_provision_config'] ?? '') . '</div>';
        } else {
            $provHost = hs_mysql_provision_client_host();
            $hintKey = hs_mysql_provision_shared_mode() ? 'db_server_hint_shared' : 'db_server_hint';
            $configHint = '<p class="hp-muted hs-db-server-hint">'
                . hs_h(str_replace('{host}', $provHost, $t[$hintKey] ?? $t['db_server_hint'] ?? ''))
                . '</p>';
        }
    }

    $pct = $dbLimit > 0 ? min(100, (int) round(($dbCount / $dbLimit) * 100)) : 0;
    $usageBar = '<div class="hs-db-usage">'
        . '<div class="hs-db-usage-head"><span>' . hs_h($t['db_usage_title'] ?? 'Database usage') . '</span>'
        . '<strong>' . hs_h(str_replace(['{used}', '{max}'], [(string) $dbCount, (string) $dbLimit], $t['db_limit_hint'] ?? '')) . '</strong></div>'
        . '<div class="hs-db-usage-track"><div class="hs-db-usage-fill" style="width:' . $pct . '%"></div></div></div>';

    $domain = hs_plan_display_domain($user, $s);
    $createForm = '';
    if ($canCreate) {
        $createForm = '<section class="hs-db-create-card">'
            . '<div class="hs-db-create-icon"><i class="fa-solid fa-circle-plus"></i></div>'
            . '<div class="hs-db-create-body">'
            . '<h3>' . hs_h($t['db_create_title'] ?? 'Create a new MySQL database') . '</h3>'
            . '<p class="hp-muted">' . hs_h($t['db_create_lead'] ?? '') . '</p>'
            . '<form method="post" class="hs-db-create-form">' . hs_csrf_field()
            . '<div class="hp-grid-2 hs-db-create-fields">'
            . '<div class="hs-field"><label for="db-label-input">' . hs_h($t['db_create_name_label'] ?? 'Database name (optional)') . '</label>'
            . '<input type="text" id="db-label-input" name="db_label" pattern="[a-zA-Z0-9_]{1,32}"'
            . ' placeholder="' . hs_h($t['db_create_name_placeholder'] ?? 'myapp') . '" autocomplete="off">'
            . '<p class="hs-field-hint">' . hs_h($t['db_create_name_hint'] ?? '') . '</p></div>'
            . '<div class="hs-field"><label>' . hs_h($t['db_create_website_label'] ?? 'Website') . '</label>'
            . '<input type="text" value="' . hs_h($domain) . '" readonly class="hs-input-readonly">'
            . '<input type="hidden" name="db_website" value="' . hs_h($domain) . '"></div>'
            . '</div>'
            . '<button type="submit" name="create_db" value="1" class="hs-btn hs-btn-primary hs-db-create-btn">'
            . '<i class="fa-solid fa-plus"></i> ' . hs_h($t['btn_create_db'] ?? 'Create database') . '</button>'
            . '</form></div></section>';
    } else {
        $createForm = '<div class="hs-alert hs-alert-warn hs-db-alert">' . hs_h($t['db_limit'] ?? '') . '</div>';
    }

    $listHtml = '';
    if ($dbs === []) {
        $listHtml = '<div class="hs-db-empty">'
            . '<i class="fa-solid fa-database"></i>'
            . '<p>' . hs_h($t['db_empty'] ?? '') . '</p></div>';
    } else {
        foreach ($dbs as $db) {
            if (!is_array($db)) {
                continue;
            }
            $listHtml .= hs_panel_database_card($db, $t, $user, $isDemo);
        }
    }

    return $configHint . $usageBar . $createForm
        . '<section class="hs-db-list-section"><h3 class="hs-db-list-title">' . hs_h($t['db_list_title'] ?? 'MySQL databases') . '</h3>'
        . '<div class="hs-db-list">' . $listHtml . '</div></section>'
        . '<p class="hp-muted hs-db-footnote">' . hs_h($t['db_config_path'] ?? '') . '</p>';
}

/** @param array<string, mixed> $db */
function hs_panel_database_card(array $db, array $t, array $user, bool $isDemo): string
{
    $displayName = (string) ($db['logical_name'] ?? $db['name'] ?? '');
    if (!empty($db['shared']) && !empty($db['table_prefix'])) {
        $displayName .= ' → ' . (string) $db['name'] . ' (' . (string) $db['table_prefix'] . '*)';
    }
    $host = (string) ($db['host'] ?? 'localhost');
    $dbUser = (string) ($db['user'] ?? '');
    $pass = (string) ($db['password'] ?? '');
    if ($isDemo) {
        $pass = (string) ($t['db_pass_hidden_demo'] ?? '••••••••');
    }
    $dbId = (string) ($db['id'] ?? '');
    $created = hs_format_date((string) ($db['created_at'] ?? ''));
    $website = (string) ($db['website'] ?? '');

    $badges = '';
    if (!empty($db['provisioned'])) {
        $badges .= '<span class="hs-db-badge hs-db-badge-live">' . hs_h($t['db_live'] ?? 'live') . '</span>';
    }
    if (!empty($db['shared'])) {
        $badges .= '<span class="hs-db-badge">' . hs_h($t['db_shared'] ?? 'shared') . '</span>';
    }
    if (!empty($db['primary'])) {
        $badges .= '<span class="hs-db-badge">' . hs_h($t['db_primary'] ?? 'Primary') . '</span>';
    }

    $pma = !empty($db['provisioned']) && $dbId !== ''
        ? hs_pma_render_open_form($dbId, $t, $t['db_pma_enter'] ?? $t['db_pma_open'] ?? 'Enter phpMyAdmin')
        : '';

    $canDelete = $dbId !== '' && empty($db['primary']) && empty($db['shared']) && !$isDemo;
    $deleteBtn = '';
    if ($canDelete) {
        $confirm = $t['db_delete_confirm'] ?? 'Delete this database?';
        $deleteBtn = '<form method="post" class="hs-db-del-form" onsubmit="return confirm('
            . json_encode($confirm, JSON_UNESCAPED_UNICODE) . ')">' . hs_csrf_field()
            . '<input type="hidden" name="db_id" value="' . hs_h($dbId) . '">'
            . '<button type="submit" name="delete_db" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-db-del-btn" title="'
            . hs_h($t['db_delete'] ?? 'Delete') . '"><i class="fa-solid fa-trash"></i></button></form>';
    }

    $passCopy = '';
    if (!$isDemo && $pass !== '') {
        $passCopy = '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-db-copy-btn" data-copy-secret="db-pass-' . hs_h($dbId) . '"'
            . ' data-secret="' . hs_h($pass) . '" data-copied-label="' . hs_h($t['db_copied'] ?? 'Copied') . '">'
            . '<i class="fa-solid fa-copy"></i></button>';
    }

    $websiteRow = $website !== ''
        ? '<div class="hs-db-meta-row"><span class="label">' . hs_h($t['db_create_website_label'] ?? 'Website') . '</span><span>' . hs_h($website) . '</span></div>'
        : '';

    return '<article class="hs-db-card">'
        . '<header class="hs-db-card-head"><div><strong class="hs-db-card-name"><code>' . hs_h($displayName) . '</code></strong>'
        . ($badges !== '' ? '<div class="hs-db-badges">' . $badges . '</div>' : '') . '</div>'
        . '<div class="hs-db-card-actions">' . $pma . $deleteBtn . '</div></header>'
        . '<div class="hs-db-card-grid">'
        . '<div class="hs-db-meta-row"><span class="label">' . hs_h($t['db_user'] ?? 'User') . '</span>'
        . '<span><code id="db-user-' . hs_h($dbId) . '">' . hs_h($dbUser) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm hs-db-copy-btn" data-copy-target="db-user-' . hs_h($dbId) . '"'
        . ' data-copied-label="' . hs_h($t['db_copied'] ?? 'Copied') . '"><i class="fa-solid fa-copy"></i></button></span></div>'
        . '<div class="hs-db-meta-row"><span class="label">' . hs_h($t['db_host'] ?? 'Host') . '</span><span><code>' . hs_h($host) . '</code></span></div>'
        . '<div class="hs-db-meta-row"><span class="label">' . hs_h($t['db_password'] ?? 'Password') . '</span>'
        . '<span><code id="db-pass-' . hs_h($dbId) . '">' . hs_h($pass) . '</code>' . $passCopy . '</span></div>'
        . '<div class="hs-db-meta-row"><span class="label">' . hs_h($t['db_created_at'] ?? 'Created') . '</span><span>' . hs_h($created) . '</span></div>'
        . $websiteRow
        . '</div></article>';
}