<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/backups.php';

$user = hs_client_require();
$file = (string) ($_GET['file'] ?? '');
$path = hs_backup_download_path($user, $file);
if ($path === null) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($path)) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;