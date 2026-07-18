<?php
declare(strict_types=1);

/**
 * Clear Namecheap TLD price cache and resync hosting wholesale markups.
 * Admin session or secret token (cron).
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/plan-catalog.php';
require_once dirname(__DIR__) . '/includes/providers/namecheap-api.php';

hs_admin_or_token_require(['HS_ONCE_TOKEN', 'HS_ONE_SHOT_TOKEN', 'HS_DOMAIN_SYNC_TOKEN']);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

foreach ([
    HS_DATA_DIR . '/namecheap-tld-prices.json',
    HS_DATA_DIR . '/namecheap-tld-catalog.json',
] as $f) {
    if (is_file($f)) {
        @unlink($f);
        echo 'deleted ' . basename($f) . "\n";
    } else {
        echo 'missing ' . basename($f) . "\n";
    }
}

$sync = function_exists('hs_plan_catalog_sync_namecheap_prices')
    ? hs_plan_catalog_sync_namecheap_prices()
    : ['ok' => false, 'plans' => []];

echo 'HOSTING_MARKUP=' . (function_exists('hs_nc_hosting_markup_pct') ? hs_nc_hosting_markup_pct() : '?') . "%\n";
echo 'DOMAIN_MARKUP=' . (function_exists('hs_namecheap_markup_pct') ? hs_namecheap_markup_pct() : '?') . "%\n";
echo 'SYNC_OK=' . (!empty($sync['ok']) ? '1' : '0') . "\n";
foreach ($sync['plans'] ?? [] as $id => $row) {
    if (!is_array($row)) {
        continue;
    }
    echo 'plan ' . $id . ': wholesale $' . number_format((float) ($row['wholesale_usd'] ?? 0), 2)
        . ' → retail $' . number_format((float) ($row['retail_usd'] ?? 0), 2)
        . ' / €' . number_format((float) ($row['price_eur'] ?? 0), 2) . "\n";
}
echo "DONE (domain cache cleared — next /domains load rebuilds prices)\n";
