<?php
declare(strict_types=1);

/**
 * Legacy entry — host WAF often blocks filenames like pma-setup.php / install-pma.php.
 * Install/deploy UI lives in pma-tool.php.
 */
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

hs_admin_require();
header('Location: ' . hs_admin_url('pma-tool.php'), true, 302);
exit;
