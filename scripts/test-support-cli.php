<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$_SERVER['HTTP_HOST'] = 'bilohash.com';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/hosting/panel/support.php';
$_SERVER['SCRIPT_NAME'] = '/hosting/panel/websites.php';
$_GET['tab'] = 'support';

$hs_section = 'websites';
$hs_section_script = 'panel/websites.php';

$root = dirname(__DIR__);
require_once $root . '/includes/panel-bootstrap.php';
require_once $root . '/includes/panel-tabs.php';
require_once $root . '/includes/panel-features.php';
require_once $root . '/includes/panel-section-content.php';

$tab = hs_panel_tab_id($hs_section, $_GET['tab'] ?? null);
$ctx = [
    'user' => $user,
    't' => $t,
    'hs_user_settings' => $hs_user_settings,
    'hs_sites' => $hs_sites,
    'hs_resources' => $hs_resources,
    'hs_plan' => $hs_plan,
    'lang' => $lang,
    'error' => '',
    'success' => '',
];

echo hs_panel_render_tabs($hs_section, $tab, $t, $hs_section_script);
echo hs_panel_section_content($hs_section, $tab, $ctx);
echo "\nDONE\n";