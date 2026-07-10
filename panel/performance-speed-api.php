<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/user-settings.php';
require_once dirname(__DIR__) . '/includes/performance.php';
require_once dirname(__DIR__) . '/includes/panel-features.php';

$user = hs_client_require();
$userId = (string) ($user['id'] ?? '');
$sites = hs_sites_for_user($userId);

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

$url = trim((string) ($payload['url'] ?? ''));
$res = hs_perf_run_speed_test($user, $sites, $url);

if (!$res['ok']) {
    hs_perf_json_response([
        'ok' => false,
        'error' => $res['error'] ?? 'fetch',
    ], 400);
}

$report = is_array($res['report'] ?? null) ? $res['report'] : [];
hs_user_settings_save($userId, [
    'speed_desktop' => $res['desktop'],
    'speed_mobile' => $res['mobile'],
    'speed_tested_at' => gmdate('c'),
    'speed_probe' => $res['probe'],
    'speed_report' => $report,
]);
hs_panel_log($userId, 'speed_test', $res['desktop'] . '/' . $res['mobile']);

hs_perf_json_response([
    'ok' => true,
    'desktop' => $res['desktop'],
    'mobile' => $res['mobile'],
    'report' => $report,
]);