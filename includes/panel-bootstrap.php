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
require_once dirname(__DIR__) . '/includes/plan-specs.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';
require_once dirname(__DIR__) . '/includes/panel-domains.php';

hs_seed_demo_data();
$user = hs_client_ensure_identity(hs_client_require());
require_once __DIR__ . '/installer.php';
hs_ensure_user_workspace($user);
$userId = (string) ($user['id'] ?? '');
if (($user['subscription_status'] ?? '') === 'active') {
    require_once __DIR__ . '/mysql-provision.php';
    hs_remove_legacy_public_db_config((string) ($user['username'] ?? 'user'));
    hs_ensure_user_database($userId, (string) ($user['username'] ?? 'user'), $user);
    require_once __DIR__ . '/php-config.php';
    hs_ensure_php_config($userId, (string) ($user['username'] ?? 'user'));
}
$hs_user_settings = hs_user_settings_get($userId);
$hs_active_domain = hs_active_domain($hs_user_settings);
$hs_domain_choices = hs_user_domain_choices($hs_user_settings);
$hs_is_platform_admin = hs_is_platform_admin($user);
if ($hs_active_domain !== ($hs_user_settings['active_domain'] ?? '')) {
    hs_user_settings_save((string) $user['id'], ['active_domain' => $hs_active_domain]);
    $hs_user_settings['active_domain'] = $hs_active_domain;
}
$hs_sites = hs_sites_for_user((string) $user['id']);
$hs_plan = hs_plan((string) ($user['plan'] ?? 'starter'));
require_once __DIR__ . '/resource-usage.php';
$hs_resources = hs_resource_usage($user, $hs_sites);
hs_usage_track($userId, $user, $hs_sites);
$panel_active = $panel_active ?? 'dashboard';
$page_title = $page_title ?? ($t['nav_dashboard'] ?? '');
$panel_tip_key = $panel_tip_key ?? $panel_active;