<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/client-auth.php';
hs_client_logout();
hs_redirect('login.php');