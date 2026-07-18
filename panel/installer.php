<?php
declare(strict_types=1);

/**
 * Legacy URL — some hosts return 403 for paths containing "installer".
 * Always use apps.php (or websites → installer tab).
 */
require dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Preserve POST by forwarding is not reliable across 403; send clients to safe URL.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Soft hand-off: if WAF allowed this request, still process via apps.php logic path.
    // Prefer GET landing on safe endpoint.
    hs_redirect(hs_panel_path('apps.php'));
}

hs_redirect(hs_panel_path('apps.php'));
