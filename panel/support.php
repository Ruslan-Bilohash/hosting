<?php
declare(strict_types=1);

$panel_active = 'site-support';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/support.php';

$page_title = $t['nav_support'] ?? $t['tab_site_support'] ?? 'Support';
$panel_tip_key = 'support';
$panel_hide_tip = true;

ob_start();
if (!hs_ecosystem_messages_ready()) {
    echo '<div class="hs-alert hs-alert-error">' . hs_h($t['support_module_missing'] ?? 'Messaging module is not available on this server.') . '</div>';
} else {
    echo hs_render_support_panel($user, $hs_sites, $t, $lang);
}
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';