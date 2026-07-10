<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';

hs_admin_require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hs_csrf_verify($_POST['csrf'] ?? null)) {
    header('Location: ' . hs_admin_url(), true, 302);
    exit;
}

$targetId = (string) ($_POST['user_id'] ?? '');
if (hs_start_impersonation_from_admin($targetId)) {
    header('Location: ' . hs_url(hs_panel_path('')), true, 302);
    exit;
}

header('Location: ' . hs_admin_url(), true, 302);
exit;