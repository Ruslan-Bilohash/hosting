<?php
declare(strict_types=1);

// Light boot + always allow Namecheap for availability checks (public domain search).
define('HS_LIGHT_BOOT', true);
define('HS_ALLOW_NAMECHEAP_LIVE_API', true);
@set_time_limit(28);
@ini_set('max_execution_time', '28');

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/domain-store.php';
require_once __DIR__ . '/includes/domain-search-history.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$rateKey = 'domain_check_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? 'anon'));
if (!hs_rate_limit($rateKey, 40, 60)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'rate_limit'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sld = (string) ($_GET['sld'] ?? $_POST['sld'] ?? '');
$tldsRaw = (string) ($_GET['tlds'] ?? $_POST['tlds'] ?? '');

if ($sld !== '' && $tldsRaw !== '') {
    $tlds = array_values(array_filter(array_map('trim', explode(',', $tldsRaw))));
    $batch = hs_domain_check_batch($sld, $tlds);
    if (!$batch['ok']) {
        echo json_encode([
            'ok' => false,
            'error' => (string) ($batch['error'] ?? 'invalid'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $results = [];
    foreach ($batch['results'] as $row) {
        $premium = !empty($row['premium']);
        $price = (float) ($row['price'] ?? 0);
        $priceLabel = hs_domain_format_price($price, $lang);
        if ($premium) {
            $priceLabel = ($lang === 'uk' ? 'Premium · ' : ($lang === 'no' ? 'Premium · ' : 'Premium · '))
                . $priceLabel;
        }
        $results[] = [
            'domain' => $row['domain'],
            'tld' => $row['tld'],
            'available' => $row['available'],
            'price' => $price,
            'price_label' => $priceLabel,
            'premium' => $premium,
            'source' => $row['source'] ?? 'registry',
            'registry_manual' => !empty($row['registry_manual']),
        ];
    }
    $availableCount = 0;
    foreach ($results as $row) {
        if (!empty($row['available'])) {
            $availableCount++;
        }
    }
    hs_domain_search_history_add((string) $batch['sld'], ['available_count' => $availableCount]);
    echo json_encode([
        'ok' => true,
        'sld' => $batch['sld'],
        'results' => $results,
        'recent' => hs_domain_search_history_list(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$domain = (string) ($_GET['domain'] ?? $_POST['domain'] ?? '');
$result = hs_domain_check_availability($domain);

if (!$result['ok']) {
    echo json_encode([
        'ok' => false,
        'error' => (string) ($result['error'] ?? 'invalid'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

hs_domain_search_history_add((string) ($result['domain'] ?? $domain), [
    'available_count' => !empty($result['available']) ? 1 : 0,
]);
$premium = !empty($result['premium']);
$price = (float) ($result['price'] ?? 0);
$priceLabel = hs_domain_format_price($price, $lang);
if ($premium) {
    $priceLabel = 'Premium · ' . $priceLabel;
}
echo json_encode([
    'ok' => true,
    'domain' => $result['domain'],
    'available' => $result['available'],
    'price' => $price,
    'price_label' => $priceLabel,
    'premium' => $premium,
    'source' => $result['source'] ?? 'registry',
    'recent' => hs_domain_search_history_list(),
], JSON_UNESCAPED_UNICODE);