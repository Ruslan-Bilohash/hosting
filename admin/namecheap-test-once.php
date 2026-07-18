<?php
declare(strict_types=1);

/**
 * Namecheap API diagnostics (admin tools / optional secret token).
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/providers/namecheap-api.php';

hs_admin_or_token_require(['HS_ONCE_TOKEN', 'HS_ONE_SHOT_TOKEN']);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

if (!empty($_GET['reset_circuit']) && function_exists('hs_namecheap_circuit_reset')) {
    hs_namecheap_circuit_reset();
    echo "circuit_reset=yes\n";
}

$status = function_exists('hs_namecheap_status') ? hs_namecheap_status() : [];
$circuit = is_array($status['circuit'] ?? null)
    ? $status['circuit']
    : (function_exists('hs_namecheap_circuit_info') ? hs_namecheap_circuit_info() : []);

echo 'NC_CLIENT_IP=' . ($status['client_ip'] ?? '') . "\n";
echo 'outbound=' . ($status['outbound_ip'] ?? '') . "\n";
echo 'server_ip=' . ($status['server_ip'] ?? '') . "\n";
echo 'ip_match=' . ((!empty($status['ip_match'])) ? 'yes' : 'no') . "\n";
echo 'circuit_open=' . (!empty($circuit['open']) ? 'yes' : 'no') . "\n";
if (!empty($circuit['last_error'])) {
    echo 'circuit_last_error=' . $circuit['last_error'] . "\n";
}

if (function_exists('hs_namecheap_test_connection')) {
    $test = hs_namecheap_test_connection(false, !empty($_GET['reset_circuit']));
    echo 'test_ok=' . ((!empty($test['ok'])) ? 'yes' : 'no') . "\n";
    if (!empty($test['detail'])) {
        echo 'detail=' . $test['detail'] . "\n";
    }
    if (!empty($test['error'])) {
        echo 'error=' . $test['error'] . "\n";
    }
    if (isset($test['balance'])) {
        echo 'balance_usd=' . $test['balance'] . "\n";
    }
} else {
    echo "error=namecheap_api_unavailable\n";
}
echo "DONE\n";
