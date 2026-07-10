<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';

hs_session_start();
if (hs_impersonation_active()) {
    $fromAdmin = hs_stop_impersonation();
    hs_redirect($fromAdmin ? 'admin/' : 'panel/clients.php');
}
hs_redirect('panel/');