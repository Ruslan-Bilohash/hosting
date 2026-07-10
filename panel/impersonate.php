<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';

hs_seed_demo_data();
$admin = hs_client_require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hs_csrf_verify($_POST['csrf'] ?? null)) {
    hs_redirect('panel/clients.php');
}

$targetId = (string) ($_POST['user_id'] ?? '');
if (hs_start_impersonation($admin, $targetId)) {
    hs_redirect('panel/');
}
hs_redirect('panel/clients.php');