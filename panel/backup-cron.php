<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/backups.php';

header('Content-Type: text/plain; charset=utf-8');

$user = trim((string) ($_GET['user'] ?? $_POST['user'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

$res = hs_backup_run_cron($user, $token);
if ($res['ok']) {
    echo 'OK backup created';
    exit;
}

http_response_code($res['error'] === 'token' || $res['error'] === 'auth' ? 403 : 400);
echo 'ERR ' . ($res['error'] ?? 'failed');