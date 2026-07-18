<?php
declare(strict_types=1);

/**
 * Pricing markup audit for hosting plans.
 * Admin session or secret token.
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/plan-catalog.php';

hs_admin_or_token_require(['HS_ONCE_TOKEN', 'HS_ONE_SHOT_TOKEN']);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$hostM = function_exists('hs_nc_hosting_markup_pct') ? hs_nc_hosting_markup_pct() : -1;
$domM = function_exists('hs_namecheap_markup_pct') ? hs_namecheap_markup_pct() : -1;
echo "HOSTING_MARKUP={$hostM}%\n";
echo "DOMAIN_MARKUP={$domM}%\n";
echo 'NC_HOSTING_DEFINED=' . (defined('NC_HOSTING_MARKUP_PCT') ? (string) NC_HOSTING_MARKUP_PCT : 'no') . "\n";
echo 'NC_DOMAIN_DEFINED=' . (defined('NC_DOMAIN_MARKUP_PCT') ? (string) NC_DOMAIN_MARKUP_PCT : 'no') . "\n";

foreach (['starter', 'plus', 'business'] as $id) {
    $w = function_exists('hs_nc_hosting_wholesale_usd')
        ? (float) (hs_nc_hosting_wholesale_usd()[$id] ?? 0)
        : 0.0;
    $r = function_exists('hs_nc_hosting_retail_usd') ? hs_nc_hosting_retail_usd($id) : 0.0;
    $e = function_exists('hs_nc_hosting_retail_eur') ? hs_nc_hosting_retail_eur($id) : 0.0;
    $plan = function_exists('hs_plan') ? hs_plan($id) : [];
    echo "{$id}: wholesale \${$w} → retail \${$r} / €{$e} | plan_eur="
        . ($plan['price_eur'] ?? '?') . ' markup=' . ($plan['markup_pct'] ?? '?') . "\n";
}
echo "DONE\n";
