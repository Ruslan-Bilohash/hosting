<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/client-identity.php';
require_once dirname(__DIR__) . '/includes/plans.php';
require_once dirname(__DIR__) . '/includes/user-settings.php';
require_once dirname(__DIR__) . '/includes/panel-nav.php';
require_once dirname(__DIR__) . '/includes/panel-ui.php';
require_once dirname(__DIR__) . '/includes/panel-access.php';
require_once dirname(__DIR__) . '/includes/plan-specs.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';
require_once dirname(__DIR__) . '/includes/panel-domains.php';

hs_seed_demo_data();
$user = hs_client_ensure_identity(hs_client_require());
require_once __DIR__ . '/installer.php';
hs_ensure_user_workspace($user);
$userId = (string) ($user['id'] ?? '');
$hs_hosting_active = hs_user_hosting_active($user);
$hs_subscription_pending = hs_user_subscription_pending($user);
// Provision hosting resources only after payment (active subscription).
if ($hs_hosting_active) {
    require_once __DIR__ . '/mysql-provision.php';
    hs_remove_legacy_public_db_config((string) ($user['username'] ?? 'user'));
    hs_ensure_user_database($userId, (string) ($user['username'] ?? 'user'), $user);
    require_once __DIR__ . '/php-config.php';
    hs_ensure_php_config($userId, (string) ($user['username'] ?? 'user'));
    // Per-client FTP jail (cPanel subaccount) — once if not yet provisioned
    if (is_file(__DIR__ . '/client-ftp-onboard.php')) {
        require_once __DIR__ . '/client-ftp-onboard.php';
        $ftpSettingsEarly = hs_user_settings_get($userId);
        $ftpAcc = is_array($ftpSettingsEarly['ftp_account'] ?? null) ? $ftpSettingsEarly['ftp_account'] : [];
        if (empty($ftpAcc['provisioned']) && function_exists('hs_client_ftp_ensure')) {
            hs_client_ftp_ensure($user, true);
        }
    }
}
$hs_user_settings = hs_user_settings_get($userId);
$hs_active_domain = hs_active_domain($hs_user_settings);
$hs_domain_choices = hs_user_domain_choices($hs_user_settings);
$hs_is_platform_admin = hs_is_platform_admin($user);
if ($hs_active_domain !== ($hs_user_settings['active_domain'] ?? '')) {
    hs_user_settings_save((string) $user['id'], ['active_domain' => $hs_active_domain]);
    $hs_user_settings['active_domain'] = $hs_active_domain;
}
// Each domain → own folder (public_html/{user}/{domain}/); routes + DirectoryIndex ready
if ($hs_hosting_active && function_exists('hs_domain_auto_bind_all_for_user')) {
    require_once __DIR__ . '/domain-workspace.php';
    hs_domain_auto_bind_all_for_user($user, true);
    $hs_user_settings = hs_user_settings_get($userId);
    $hs_active_domain = hs_active_domain($hs_user_settings);
    $hs_domain_choices = hs_user_domain_choices($hs_user_settings);
}
$hs_sites = hs_sites_for_user((string) $user['id']);
$hs_plan = hs_plan((string) ($user['plan'] ?? 'starter'));
require_once __DIR__ . '/resource-usage.php';
$hs_resources = hs_resource_usage($user, $hs_sites);
hs_usage_track($userId, $user, $hs_sites);
$panel_active = $panel_active ?? 'dashboard';
$page_title = $page_title ?? ($t['nav_dashboard'] ?? '');
$panel_tip_key = $panel_tip_key ?? $panel_active;