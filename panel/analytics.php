<?php
declare(strict_types=1);

$panel_active = 'analytics';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/analytics-ui.php';

$page_title = $t['analytics_title'] ?? 'Analytics';
$panel_tip_key = 'analytics';

$userId = (string) ($user['id'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$viewUserId = $userId;
$viewUser = $user;
$isAdminView = $hs_is_platform_admin && !hs_impersonation_active();

if ($isAdminView && isset($_GET['user'])) {
    $pick = trim((string) $_GET['user']);
    $picked = hs_user_by_id($pick);
    if ($picked !== null) {
        $viewUserId = (string) ($picked['id'] ?? $pick);
        $viewUser = $picked;
    }
}

$allUsers = $isAdminView ? hs_users() : [];

ob_start();
echo hs_analytics_render($viewUser, $viewUserId, $page, $t, $isAdminView, $allUsers);
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';