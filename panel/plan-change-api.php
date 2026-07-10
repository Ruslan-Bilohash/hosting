<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/plan-change.php';

$user = hs_client_require();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'ok' => true,
        'current' => (string) ($user['plan'] ?? 'starter'),
        'plans' => hs_plan_change_catalog($user, $t, $lang),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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

$newPlan = trim((string) ($payload['plan'] ?? ''));
$res = hs_plan_change_apply($user, $newPlan, $lang);
if (!$res['ok']) {
    $msg = match ($res['error'] ?? '') {
        'downgrade_blocked' => str_replace(
            ['{used}', '{limit}'],
            [(string) ($res['sites_used'] ?? 0), (string) ($res['sites_limit'] ?? 0)],
            $t['plan_change_error_downgrade'] ?? 'Too many sites for this plan'
        ),
        'same_plan' => $t['plan_change_same'] ?? 'Already on this plan',
        default => $t['plan_change_error'] ?? 'Could not change plan',
    };
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $res['error'] ?? 'fail', 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'plan' => $res['plan'] ?? $newPlan,
    'plan_label' => $t['plan_' . ($res['plan'] ?? $newPlan)] ?? ($res['plan'] ?? $newPlan),
    'invoice_id' => $res['invoice_id'] ?? '',
    'invoice_number' => $res['invoice_number'] ?? '',
    'message' => str_replace(
        '{plan}',
        (string) ($t['plan_' . ($res['plan'] ?? $newPlan)] ?? $res['plan'] ?? ''),
        $t['plan_change_success'] ?? 'Plan updated'
    ),
], JSON_UNESCAPED_UNICODE);