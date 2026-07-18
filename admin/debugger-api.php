<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
@set_time_limit(120);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-debugger.php';

hs_admin_require();

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

function hs_dbg_json(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$raw = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}
$action = (string) ($body['action'] ?? $_GET['action'] ?? 'status');

if ($method === 'GET') {
    if ($action === 'report') {
        $id = (string) ($_GET['id'] ?? '');
        $rep = hs_debug_report_load($id);
        if ($rep === null) {
            hs_dbg_json(['ok' => false, 'error' => 'not_found'], 404);
        }
        hs_dbg_json(['ok' => true, 'report' => $rep]);
    }
    hs_dbg_json([
        'ok' => true,
        'errors_count' => count(hs_debug_errors_load()),
        'reports' => hs_debug_reports_list(),
        'version' => function_exists('hs_version') ? hs_version() : '',
    ]);
}

if ($method !== 'POST') {
    hs_dbg_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

// CSRF optional for same-site admin session tools; accept session admin as auth
if ($action === 'run') {
    $report = hs_debug_run_full(true);
    $saved = null;
    if (!empty($body['save'])) {
        $saved = hs_debug_report_save($report);
    }
    hs_dbg_json([
        'ok' => true,
        'report' => $report,
        'saved' => $saved,
    ]);
}

if ($action === 'import_logs') {
    hs_dbg_json(['ok' => true] + hs_debug_import_error_logs());
}

if ($action === 'clear_errors') {
    hs_dbg_json(['ok' => hs_debug_errors_clear()]);
}

if ($action === 'delete_report') {
    $id = (string) ($body['id'] ?? '');
    hs_dbg_json(['ok' => hs_debug_report_delete($id)]);
}

if ($action === 'list_reports') {
    hs_dbg_json(['ok' => true, 'reports' => hs_debug_reports_list()]);
}

hs_dbg_json(['ok' => false, 'error' => 'unknown_action'], 400);
