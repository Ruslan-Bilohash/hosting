<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/domain-orders.php';

header('Content-Type: application/json; charset=utf-8');

$user = hs_client_require();
$userId = (string) ($user['id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$csrf = (string) ($payload['csrf'] ?? $_POST['csrf'] ?? '');
if (!hs_csrf_verify($csrf !== '' ? $csrf : null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    exit;
}

$activated = hs_domain_orders_poll_user($userId);
$pending = [];
foreach (hs_domain_orders_for_user($userId) as $order) {
    if (($order['status'] ?? '') !== 'pending') {
        continue;
    }
    $pending[] = [
        'id' => (string) ($order['id'] ?? ''),
        'domain' => (string) ($order['domain'] ?? ''),
        'status' => (string) ($order['status'] ?? 'pending'),
        'last_check_at' => (string) ($order['last_check_at'] ?? ''),
        'last_check_live' => !empty($order['last_check_live']),
    ];
}

echo json_encode([
    'ok' => true,
    'pending' => $pending,
    'activated' => array_map(static function (array $order): array {
        return [
            'id' => (string) ($order['id'] ?? ''),
            'domain' => (string) ($order['domain'] ?? ''),
            'status' => (string) ($order['status'] ?? 'active'),
        ];
    }, $activated),
], JSON_UNESCAPED_UNICODE);