<?php
declare(strict_types=1);

/**
 * Client cPanel entry — menu only after hosting payment (active).
 * - WHM pool on + dedicated account → SSO to real cPanel
 * - Shared multi-tenant (WHM off) → this panel IS the control panel (no external cPanel)
 */
$panel_active = 'cpanel';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/cpanel-provision.php';
require_once dirname(__DIR__) . '/includes/master-password.php';

$page_title = $t['nav_cpanel'] ?? 'cPanel';
$panel_tip_key = 'cpanel';
$userId = (string) ($user['id'] ?? '');
$active = hs_user_hosting_active($user);
$whmOn = function_exists('hs_whm_enabled') && hs_whm_enabled();

// Unpaid: hard gate
if (!$active) {
    $content = hs_render_card(
        $t['nav_cpanel'] ?? 'cPanel',
        '<div class="hs-alert hs-alert-warn"><p style="margin:0">'
        . hs_h($t['cpanel_need_pay'] ?? 'cPanel opens after you pay for a hosting plan.')
        . '</p><p style="margin:.75rem 0 0"><a class="hs-btn hs-btn-primary" href="'
        . hs_h(hs_url(hs_panel_path('activate.php'))) . '">'
        . hs_h($t['btn_activate'] ?? 'Activate hosting') . '</a></p></div>'
    );
    require dirname(__DIR__) . '/includes/layout-panel.php';
    exit;
}

// Auto-provision only when WHM pool is fully configured
$provisionNote = '';
if ($whmOn && function_exists('hs_cpanel_auto_provision') && hs_cpanel_auto_provision()) {
    $acc0 = hs_cpanel_account_for_user($userId);
    if ($acc0 === null || empty($acc0['provisioned'])) {
        $pr = hs_cpanel_provision_for_user($user);
        if (!empty($pr['ok']) && empty($pr['skipped'])) {
            $provisionNote = (string) ($t['cpanel_provisioned_now'] ?? 'Your cPanel account was created.');
        } elseif (empty($pr['ok']) && empty($pr['skipped'])) {
            $provisionNote = (string) ($t['cpanel_provision_wait'] ?? 'cPanel is being prepared. Try again in a few minutes or contact support.');
        }
    }
}

$acc = hs_cpanel_account_for_user($userId);
$hasCp = $acc !== null && !empty($acc['provisioned']) && !empty($acc['user']);

// SSO open — only meaningful with a real dedicated cPanel
$openErr = '';
if (isset($_GET['open']) && $_GET['open'] === '1') {
    if (!$hasCp || !$whmOn) {
        // Shared mode or not provisioned: never show technical whm_disabled
        header('Location: ' . hs_url(hs_panel_path('cpanel.php')), true, 302);
        exit;
    }
    $sso = hs_cpanel_sso_for_user($user);
    if (!empty($sso['ok']) && !empty($sso['url'])) {
        header('Location: ' . (string) $sso['url'], true, 302);
        exit;
    }
    // Friendly message only (no raw API codes like whm_disabled)
    $code = (string) ($sso['error'] ?? 'sso');
    if (in_array($code, ['whm_disabled', 'no_account', 'whm_api_missing'], true)) {
        header('Location: ' . hs_url(hs_panel_path('cpanel.php')), true, 302);
        exit;
    }
    $openErr = (string) ($t['cpanel_sso_fail'] ?? 'Could not open cPanel automatically. Try again or use the login page.');
}

$loginUrl = $hasCp ? (string) ($acc['login_url'] ?? '') : '';
if ($loginUrl === '' && $whmOn && $hasCp) {
    $cfg = hs_whm_config();
    $host = trim((string) ($cfg['host'] ?? ''));
    $port = (int) ($cfg['cpanel_port'] ?? 2083);
    if ($host !== '') {
        $loginUrl = 'https://' . $host . ':' . $port;
    }
}
$cpUser = $hasCp ? (string) ($acc['user'] ?? '') : '';
$cpDomain = $hasCp ? (string) ($acc['domain'] ?? '') : '';
$pass = hs_master_password_plain($userId);

$alerts = '';
if ($provisionNote !== '') {
    $alerts .= '<div class="hs-alert hs-alert-success" style="margin-bottom:1rem">' . hs_h($provisionNote) . '</div>';
}
if ($openErr !== '') {
    $alerts .= '<div class="hs-alert hs-alert-error" style="margin-bottom:1rem">' . hs_h($openErr) . '</div>';
}

$body = '';
if ($hasCp && $whmOn) {
    $openHref = hs_url(hs_panel_path('cpanel.php'), ['open' => '1']);
    $body .= '<p class="hp-muted">' . hs_h($t['cpanel_ready_lead'] ?? 'Your dedicated cPanel is ready. Open it to manage DNS, email, SSL and files on the server.') . '</p>'
        . '<p style="margin:1rem 0"><a class="hs-btn hs-btn-primary" href="' . hs_h($openHref) . '">'
        . '<i class="fa-solid fa-arrow-up-right-from-square"></i> '
        . hs_h($t['cpanel_open_btn'] ?? 'Open cPanel') . '</a>';
    if ($loginUrl !== '') {
        $body .= ' <a class="hs-btn hs-btn-ghost" href="' . hs_h($loginUrl) . '" target="_blank" rel="noopener">'
            . hs_h($t['cpanel_login_url_btn'] ?? 'Login page') . '</a>';
    }
    $body .= '</p>'
        . '<div class="hs-table-wrap"><table class="hs-table"><tbody>'
        . '<tr><th>' . hs_h($t['cpanel_user_label'] ?? 'cPanel user') . '</th><td><code>' . hs_h($cpUser) . '</code></td></tr>'
        . '<tr><th>' . hs_h($t['cpanel_domain_label'] ?? 'Primary domain') . '</th><td><code>' . hs_h($cpDomain) . '</code></td></tr>';
    if ($pass !== '') {
        $body .= '<tr><th>' . hs_h($t['cpanel_pass_label'] ?? 'Password') . '</th><td><code>' . hs_h($pass) . '</code> '
            . '<span class="hp-muted">(' . hs_h($t['cpanel_pass_same'] ?? 'same as panel master password') . ')</span></td></tr>';
    }
    if ($loginUrl !== '') {
        $body .= '<tr><th>URL</th><td><code>' . hs_h($loginUrl) . '</code></td></tr>';
    }
    $body .= '</tbody></table></div>';
} else {
    // Shared multi-tenant: SolaSkinner panel is the control panel
    $body .= '<div class="hs-alert hs-alert-success" style="margin-bottom:1rem">'
        . '<strong><i class="fa-solid fa-circle-check"></i> '
        . hs_h($t['cpanel_shared_title'] ?? 'Hosting control panel is active') . '</strong>'
        . '<p class="hp-muted" style="margin:.4rem 0 0">'
        . hs_h($t['cpanel_shared_lead'] ?? 'You manage the site in this panel (not a separate cPanel login). Use Domains, Files, Email and Databases below.')
        . '</p></div>';

    $links = [
        [hs_panel_tab_href('domains', 'dns'), 'fa-network-wired', $t['domains_dns_title'] ?? 'DNS zone'],
        [hs_panel_tab_href('domains', 'overview'), 'fa-globe', $t['nav_domains'] ?? 'Domains'],
        [hs_panel_tab_href('files', 'manager'), 'fa-folder-open', $t['nav_files'] ?? 'Files'],
        [hs_panel_path('email.php'), 'fa-envelope', $t['dash_manage_email'] ?? 'Email'],
        [hs_panel_tab_href('databases', 'manage'), 'fa-database', $t['nav_databases'] ?? 'Databases'],
        [hs_panel_path('websites.php'), 'fa-window-maximize', $t['nav_website'] ?? 'Websites'],
    ];
    $body .= '<div class="hp-grid-3" style="gap:.75rem">';
    foreach ($links as $L) {
        $body .= '<a class="hs-btn hs-btn-ghost" style="justify-content:flex-start" href="' . hs_h(hs_url($L[0])) . '">'
            . '<i class="fa-solid ' . hs_h($L[1]) . '"></i> ' . hs_h((string) $L[2]) . '</a>';
    }
    $body .= '</div>';

    if ($whmOn && !$hasCp) {
        $body .= '<p class="hp-muted" style="margin-top:1.25rem">'
            . hs_h($t['cpanel_whm_pending'] ?? 'A dedicated cPanel account will appear here once provisioning finishes. Contact support if this takes longer than 15 minutes.')
            . '</p>';
    } else {
        $body .= '<p class="hp-muted" style="margin-top:1.25rem">'
            . hs_h($t['cpanel_shared_note'] ?? 'Separate cPanel logins are created only when the host enables the WHM reseller pool. Until then everything runs in this panel.')
            . '</p>';
    }
}

$content = $alerts . hs_render_card($t['nav_cpanel'] ?? 'cPanel', $body);
require dirname(__DIR__) . '/includes/layout-panel.php';
