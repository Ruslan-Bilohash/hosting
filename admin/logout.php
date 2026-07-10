<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
hs_admin_logout();
header('Location: ' . hs_admin_url('login.php'), true, 302);
exit;