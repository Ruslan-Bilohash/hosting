<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/phpmyadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . hs_url(hs_panel_path('databases.php') . '?tab=phpmyadmin'));
    exit;
}

if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('CSRF');
}

$userId = (string) ($user['id'] ?? '');
$dbId = (string) ($_POST['db_id'] ?? '');
$db = hs_pma_database_for_user($userId, $dbId);

if ($db === null) {
    http_response_code(403);
    exit('Database not found or not provisioned');
}

$userName = (string) ($db['user'] ?? '');
$password = (string) ($db['password'] ?? '');
if ($userName === '' || $password === '') {
    http_response_code(403);
    exit('Invalid database credentials');
}

if (!is_file(dirname(__DIR__) . '/pma/index.php')) {
    http_response_code(503);
    exit('phpMyAdmin is not installed on this server. Contact the host administrator.');
}

hs_pma_start_signon_session($db);

if (function_exists('hs_panel_log')) {
    require_once dirname(__DIR__) . '/includes/panel-features.php';
    hs_panel_log($userId, 'pma_signon', (string) ($db['name'] ?? $dbId));
}

header('Location: ' . hs_pma_index_url());
exit;