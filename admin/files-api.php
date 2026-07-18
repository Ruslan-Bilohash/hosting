<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-file-manager.php';
require_once dirname(__DIR__) . '/includes/installer.php';

hs_admin_require();

$scope = hs_afm_norm_scope((string) ($_GET['scope'] ?? $_POST['scope'] ?? 'cms'));
$user = hs_afm_begin($scope);

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'download') {
    $rel = hs_fm_norm_rel((string) ($_GET['path'] ?? ''));
    $file = hs_fm_download_path($user, $rel);
    hs_afm_end();
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
    hs_afm_end();
    echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rel = hs_fm_norm_rel((string) ($_GET['path'] ?? $_POST['path'] ?? ''));

// Full server tree is expensive — shallow for broad roots
$treeDepth = ($scope === 'server') ? 1 : 5;

$result = match ($action) {
    'list' => hs_fm_list($user, $rel),
    'read' => hs_fm_read($user, $rel),
    'tree' => ['ok' => true, 'tree' => hs_fm_tree($user, $treeDepth)],
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

if ($action === 'list' && !empty($result['ok'])) {
    $root = hs_fm_scope_root($user);
    $result['root_label'] = hs_afm_scope_label($scope);
    $result['scope'] = $scope;
    // Never recursive-scan entire server disk
    if ($scope === 'server' || hs_afm_is_broad_root($root)) {
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        $result['disk_used_mb'] = ($total !== false && $free !== false)
            ? round(($total - $free) / 1024 / 1024, 1)
            : 0.0;
    } else {
        $result['disk_used_mb'] = hs_folder_size_mb($root);
    }
}

hs_afm_end();

echo json_encode($result, JSON_UNESCAPED_UNICODE);