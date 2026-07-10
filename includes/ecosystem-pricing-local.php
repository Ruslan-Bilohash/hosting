<?php
declare(strict_types=1);

require_once __DIR__ . '/currency.php';

if (!defined('ECOSYSTEM_SCRIPT_PRICE_NOK')) {
    define('ECOSYSTEM_SCRIPT_PRICE_NOK', 49);
}
if (!defined('ECOSYSTEM_FULL_PRICE_NOK')) {
    define('ECOSYSTEM_FULL_PRICE_NOK', 249);
}

if (!function_exists('ecosystem_format_price')) {
    function ecosystem_format_price(float $nok, string $currency, string $lang): string
    {
        unset($currency);
        return hs_format_nok_price($nok, $lang);
    }
}