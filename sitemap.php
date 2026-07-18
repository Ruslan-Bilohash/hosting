<?php
declare(strict_types=1);

/**
 * Public XML sitemap for solaskinner.com
 * Fixed: load i18n so hs_langs() is available for hreflang alternates.
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/public-seo.php';

if (function_exists('hs_prelaunch_mode') && hs_prelaunch_mode()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not available during pre-launch.';
    exit;
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo hs_render_sitemap_xml();
