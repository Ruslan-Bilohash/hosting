<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Public pages skip live Namecheap by default; domain-check + admin keep it.
if (!defined('HS_ALLOW_NAMECHEAP_LIVE_API')) {
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_NAME'] ?? ''));
    define(
        'HS_ALLOW_NAMECHEAP_LIVE_API',
        str_contains($scriptPath, '/admin/')
            || str_contains($scriptPath, 'domain-check.php')
            || str_contains($scriptPath, 'namecheap-test-once.php')
            || str_contains($scriptPath, 'test-namecheap-cli.php')
    );
}

require_once __DIR__ . '/includes/helpers.php';
if (is_file(__DIR__ . '/includes/brand-mark.php')) {
    require_once __DIR__ . '/includes/brand-mark.php';
}
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/db-migrate.php';
if (
    (!defined('HS_LIGHT_BOOT') || !HS_LIGHT_BOOT)
    && function_exists('hs_is_mysql_installed')
    && hs_is_mysql_installed()
) {
    hs_db_ensure_schema();
}
require_once __DIR__ . '/includes/storage.php';

if (function_exists('hs_enforce_https')) {
    hs_enforce_https();
}
hs_security_headers();
hs_ensure_dirs();
hs_install_redirect_if_needed();
