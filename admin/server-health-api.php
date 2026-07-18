<?php
declare(strict_types=1);

// Fail fast if something goes wrong — always JSON for the admin probe UI
@ini_set('display_errors', '0');
@set_time_limit(45);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-server-health.php';

hs_admin_require();

if (!function_exists('hs_perf_json_response')) {
    require_once dirname(__DIR__) . '/includes/performance.php';
}

// GET — lightweight snapshot for admin tools hub / monitoring link
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $snap = function_exists('hs_admin_server_health_snapshot')
            ? hs_admin_server_health_snapshot()
            : ['ok' => true, 'php' => PHP_VERSION, 'time' => gmdate('c')];
        hs_perf_json_response($snap);
    } catch (Throwable $e) {
        hs_perf_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_perf_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$csrf = (string) ($payload['csrf'] ?? $_POST['csrf'] ?? '');
if (!hs_csrf_verify($csrf !== '' ? $csrf : null)) {
    hs_perf_json_response(['ok' => false, 'error' => 'csrf'], 403);
}

$action = (string) ($payload['action'] ?? 'probe_all');
if ($action === 'probe_all') {
    try {
        $results = hs_admin_run_all_probes($t);
        hs_admin_save_probe_run($results);
        hs_perf_json_response([
            'ok' => true,
            'results' => $results,
            'tested_at' => gmdate('c'),
        ]);
    } catch (Throwable $e) {
        hs_perf_json_response([
            'ok' => false,
            'error' => 'probe_exception',
            'detail' => $e->getMessage(),
        ], 500);
    }
}

$url = trim((string) ($payload['url'] ?? ''));
if ($action === 'probe' && $url !== '') {
    $probe = hs_admin_probe_url($url);
    hs_perf_json_response(['ok' => true, 'probe' => $probe]);
}

hs_perf_json_response(['ok' => false, 'error' => 'unknown_action'], 400);