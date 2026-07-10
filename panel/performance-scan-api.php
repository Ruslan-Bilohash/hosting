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

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$csrf = (string) ($payload['csrf'] ?? '');
if (!hs_csrf_verify($csrf !== '' ? $csrf : null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    exit;
}

$findings = hs_perf_run_health_scan($user, $sites);
$settings = hs_user_settings_get($userId);
hs_user_settings_save($userId, [
    'perf_ai_findings' => $findings,
    'perf_ai_last_scan' => gmdate('c'),
    'perf_ai_enabled' => true,
]);
hs_panel_log($userId, 'perf_ai_scan', (string) count($findings));

$advice = hs_perf_build_advice($findings, $user, $t, $settings);
$findingsHtml = hs_perf_render_findings($findings, $t);
$adviceHtml = hs_perf_render_advice_cards($advice, $t);

echo json_encode([
    'ok' => true,
    'scanned_at' => gmdate('c'),
    'findings_count' => count($findings),
    'findings_html' => $findingsHtml,
    'advice_html' => $adviceHtml,
    'findings' => $findings,
    'advice' => $advice,
], JSON_UNESCAPED_UNICODE);