<?php
/**
 * Production / server overrides — copy to config.local.php (never commit).
 *
 * Used for SSH terminal, FTP hints in the panel, and DNS A-record display.
 */
declare(strict_types=1);

define('HS_SSH_HOST', '203.0.113.10');
define('HS_SSH_PORT', 22);
define('HS_SSH_USER', 'ssh_user');

/** Shown in panel (plan details, DNS, FTP) */
define('HS_SERVER_IP', '203.0.113.10');
define('HS_FTP_USER_PREFIX', 'hosting_account');

/** Optional: override canonical URL on production */
// define('HS_CANONICAL_URL', 'https://example.com/hosting');