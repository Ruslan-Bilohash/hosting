<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/legal.php';
require_once __DIR__ . '/includes/public-footer.php';

$doc = hs_legal_domains_doc($lang);
$page_title = $doc['title'] ?? 'Domain Registration Policy';
$page_seo = [
    'type' => 'page',
    'path' => 'domain-registration.php',
    'title' => (string) $page_title,
    'description' => (string) ($t['domain_reg_meta'] ?? 'Domain registration via Namecheap: ICANN rules, registrant duties, GDPR, renewals and official Namecheap legal links.'),
    'breadcrumb' => [
        ['name' => (string) ($t['nav_home'] ?? 'Home'), 'item' => hs_canonical_url('')],
        ['name' => (string) $page_title, 'item' => hs_canonical_url('domain-registration.php')],
    ],
];
ob_start();
echo hs_legal_render_page($doc, $lang, 'domain-registration.php', $t);
$content = ob_get_clean();
$extra_footer_scripts = ['js/cookie-consent.js'];
require __DIR__ . '/includes/layout-public.php';
