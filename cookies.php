<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/legal.php';
require_once __DIR__ . '/includes/public-footer.php';

$page_title = hs_legal_cookies_doc($lang)['title'] ?? 'Cookies';
$page_seo = [
    'type' => 'cookies',
    'path' => 'cookies.php',
    'title' => (string) $page_title,
    'description' => (string) ($t['cookies_meta_description'] ?? $t['meta_description'] ?? ''),
    'breadcrumb' => [
        ['name' => (string) ($t['nav_home'] ?? 'Home'), 'item' => hs_canonical_url('')],
        ['name' => (string) $page_title, 'item' => hs_canonical_url('cookies.php')],
    ],
];
ob_start();
echo hs_legal_render_page(hs_legal_cookies_doc($lang), $lang, 'cookies.php', $t);
$content = ob_get_clean();
$extra_footer_scripts = ['js/cookie-consent.js'];
require __DIR__ . '/includes/layout-public.php';
