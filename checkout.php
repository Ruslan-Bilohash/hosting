<?php
declare(strict_types=1);

/** Legacy URL — payment moved to client panel. */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/client-auth.php';

hs_session_start();
if (hs_client_user() !== null) {
    hs_redirect(hs_panel_path('activate.php'));
}
hs_redirect('register.php');