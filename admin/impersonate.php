<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';

hs_seed_demo_data();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . hs_admin_url('clients.php'), true, 302);
    exit;
}

if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
    header('Location: ' . hs_admin_url('clients.php'), true, 302);
    exit;
}

$targetId = trim((string) ($_POST['user_id'] ?? ''));
if ($targetId === '') {
    header('Location: ' . hs_admin_url('clients.php'), true, 302);
    exit;
}

$actor = hs_admin_or_platform_user();
$ok = false;
if ($actor !== null) {
    if (($actor['source'] ?? '') === 'super') {
        $ok = hs_start_impersonation_from_admin($targetId);
    } else {
        $ok = hs_start_impersonation((array) ($actor['user'] ?? []), $targetId);
    }
}

if ($ok) {
    header('Location: ' . hs_url(hs_panel_path('')), true, 302);
    exit;
}

header('Location: ' . hs_admin_url('clients.php'), true, 302);
exit;