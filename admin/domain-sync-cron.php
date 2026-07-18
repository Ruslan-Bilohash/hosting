<?php
declare(strict_types=1);

/**
 * Domain registry sync job — cron token or logged-in admin.
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/providers/hostinger-domains.php';

hs_admin_or_token_require(['HS_DOMAIN_SYNC_TOKEN', 'HS_ONCE_TOKEN']);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

if (!function_exists('hs_domain_sync_registries')) {
    http_response_code(500);
    echo "ERR hs_domain_sync_registries missing\n";
    exit;
}

$res = hs_domain_sync_registries();
if (!($res['ok'] ?? false)) {
    http_response_code(500);
    echo 'ERR ' . ($res['error'] ?? 'sync_failed') . "\n";
    exit;
}

$count = (int) ($res['hi_domains'] ?? $res['nc_domains'] ?? 0);
echo 'OK domains=' . $count
    . ' checked=' . (int) ($res['checked'] ?? 0)
    . ' updated=' . (int) ($res['updated'] ?? 0) . "\n";
