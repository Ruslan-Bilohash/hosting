<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/domain-store.php';

header('Content-Type: application/json; charset=utf-8');

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
        $results[] = [
            'domain' => $row['domain'],
            'tld' => $row['tld'],
            'available' => $row['available'],
            'price' => $row['price'],
            'price_label' => hs_domain_format_price((float) ($row['price'] ?? 0), $lang),
            'source' => $row['source'] ?? 'registry',
        ];
    }
    echo json_encode([
        'ok' => true,
        'sld' => $batch['sld'],
        'results' => $results,
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

echo json_encode([
    'ok' => true,
    'domain' => $result['domain'],
    'available' => $result['available'],
    'price' => $result['price'],
    'price_label' => hs_domain_format_price((float) ($result['price'] ?? 0), $lang),
    'source' => $result['source'] ?? 'registry',
], JSON_UNESCAPED_UNICODE);