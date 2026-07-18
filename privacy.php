<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/legal.php';
require_once __DIR__ . '/includes/public-footer.php';

$page_title = hs_legal_privacy_doc($lang)['title'] ?? 'Privacy';
$page_seo = [
    'type' => 'privacy',
    'path' => 'privacy.php',
    'title' => (string) $page_title,
    'description' => (string) ($t['privacy_meta_description'] ?? $t['meta_description'] ?? ''),
    'breadcrumb' => [
        ['name' => (string) ($t['nav_home'] ?? 'Home'), 'item' => hs_canonical_url('')],
        ['name' => (string) $page_title, 'item' => hs_canonical_url('privacy.php')],
    ],
];
ob_start();
echo hs_legal_render_page(hs_legal_privacy_doc($lang), $lang, 'privacy.php', $t);
$content = ob_get_clean();
$extra_footer_scripts = ['js/cookie-consent.js'];
require __DIR__ . '/includes/layout-public.php';
