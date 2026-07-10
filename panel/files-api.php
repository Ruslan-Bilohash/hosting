<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/file-manager.php';
require_once dirname(__DIR__) . '/includes/installer.php';

$user = hs_client_require();
$userId = (string) ($user['id'] ?? '');

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'download') {
    $rel = hs_fm_norm_rel((string) ($_GET['path'] ?? ''));
    $file = hs_fm_download_path($user, $rel);
    if ($file === null) {
        http_response_code(404);
        exit('Not found');
    }
    $name = basename($file);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $name) . '"');
    header('Content-Length: ' . (string) filesize($file));
    readfile($file);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hs_csrf_verify($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rel = hs_fm_norm_rel((string) ($_GET['path'] ?? $_POST['path'] ?? ''));

$result = match ($action) {
    'list' => hs_fm_list($user, $rel),
    'read' => hs_fm_read($user, $rel),
    'tree' => ['ok' => true, 'tree' => hs_fm_tree($user, 5)],
    'search' => ['ok' => true, 'entries' => hs_fm_search($user, $rel, (string) ($_GET['q'] ?? ''))],
    'preview' => hs_fm_preview($user, $rel),
    'write' => hs_fm_write($user, $rel, (string) ($_POST['content'] ?? '')),
    'mkdir' => hs_fm_mkdir($user, $rel, (string) ($_POST['name'] ?? '')),
    'create' => hs_fm_create_file($user, $rel, (string) ($_POST['name'] ?? '')),
    'delete' => hs_fm_delete($user, $rel),
    'rename' => hs_fm_rename($user, $rel, (string) ($_POST['name'] ?? '')),
    'duplicate' => hs_fm_duplicate($user, $rel),
    'chmod' => hs_fm_chmod($user, $rel, (string) ($_POST['mode'] ?? '')),
    'upload' => hs_fm_upload($user, $rel, $_FILES['file'] ?? []),
    'archive' => hs_fm_archive_create($user, $rel, (string) ($_POST['name'] ?? '')),
    'extract' => hs_fm_archive_extract($user, $rel),
    default => ['ok' => false, 'error' => 'unknown_action'],
};

if ($result['ok'] && in_array($action, ['write', 'mkdir', 'create', 'delete', 'rename', 'upload', 'duplicate', 'chmod', 'archive', 'extract'], true)) {
    require_once dirname(__DIR__) . '/includes/panel-features.php';
    hs_panel_log($userId, 'fm_' . $action, $rel !== '' ? $rel : ($result['path'] ?? ''));
}

if ($action === 'list' && !empty($result['ok'])) {
    $root = hs_fm_user_root($user);
    $result['root_label'] = 'public_html/' . preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user'));
    $result['disk_used_mb'] = hs_folder_size_mb($root);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);