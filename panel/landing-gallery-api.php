<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/landing-builder.php';

header('Content-Type: application/json; charset=utf-8');

$user = hs_client_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hs_csrf_verify($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'list');

$result = match ($action) {
    'list' => ['ok' => true, 'images' => hs_landing_gallery_list($user)],
    'upload' => hs_landing_gallery_upload($user, $_FILES['file'] ?? []),
    default => ['ok' => false, 'error' => 'unknown_action'],
};

if ($result['ok'] && $action === 'upload') {
    require_once dirname(__DIR__) . '/includes/panel-features.php';
    hs_panel_log((string) ($user['id'] ?? ''), 'landing_gallery_upload', (string) ($result['path'] ?? ''));
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);