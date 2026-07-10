<?php
declare(strict_types=1);

function hs_bilohash_site_lang(string $lang): string
{
    return match ($lang) {
        'uk' => 'ua',
        'no' => 'no',
        default => 'en',
    };
}

function hs_bilohash_external_url(string $path, string $lang): string
{
    $siteLang = hs_bilohash_site_lang($lang);
    $base = 'https://bilohash.com';
    if ($path === '' || $path === '/') {
        return $base . '/?lang=' . rawurlencode($siteLang);
    }
    if (str_contains($path, '?')) {
        return $base . $path . '&lang=' . rawurlencode($siteLang);
    }
    return $base . $path . '?lang=' . rawurlencode($siteLang);
}

/** @param array<string, string> $t */
function hs_render_public_footer(array $t, string $lang): string
{
    $year = (string) date('Y');
    $copyright = str_replace('{year}', $year, (string) ($t['footer_copyright'] ?? '© {year} BILOHASH'));

    $link = static function (string $href, string $label, bool $external = false): string {
        $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
        return '<li><a href="' . hs_h($href) . '"' . $attrs . '>' . hs_h($label) . '</a></li>';
    };

    $hostingLinks = ''
        . $link(hs_url('#pricing'), (string) ($t['footer_plans'] ?? 'Plans'))
        . $link(hs_url('register.php'), (string) ($t['footer_register'] ?? 'Register'))
        . $link(hs_url('register.php', ['plan' => 'vps']), (string) ($t['footer_vps'] ?? 'VPS'))
        . $link(hs_url('login.php'), (string) ($t['footer_login'] ?? 'Login'))
        . $link(hs_url('login.php'), (string) ($t['footer_demo'] ?? 'Demo panel'))
        . $link(hs_url(''), (string) ($t['footer_domain'] ?? 'Domain search'));

    $ecoLinks = ''
        . $link(hs_bilohash_external_url('/', $lang), (string) ($t['footer_ecosystem_home'] ?? 'Ecosystem'), true)
        . $link('https://bilohash.com/shop/', (string) ($t['footer_shop'] ?? 'Shop CMS'), true)
        . $link('https://bilohash.com/booking/', (string) ($t['footer_booking'] ?? 'Booking CMS'), true)
        . $link('https://wordpress.org/plugins/bilohash-ai-chat-consultant/', (string) ($t['footer_wordpress'] ?? 'WordPress plugin'), true)
        . $link(hs_bilohash_external_url('/news/', $lang), (string) ($t['footer_all_products'] ?? 'All products'), true);

    $companyLinks = ''
        . $link(hs_bilohash_external_url('/about.php', $lang), (string) ($t['footer_about'] ?? 'About'), true)
        . $link('https://bilohash.com/contact.php', (string) ($t['footer_contact'] ?? 'Contact'), true)
        . $link(hs_bilohash_external_url('/news/', $lang), (string) ($t['footer_news'] ?? 'News'), true)
        . $link(hs_bilohash_external_url('/website/en.php', $lang), (string) ($t['footer_webdev'] ?? 'Web development'), true)
        . $link(hs_url('login.php'), (string) ($t['footer_panel'] ?? 'Client panel'));

    $legalLinks = ''
        . $link(hs_bilohash_external_url('/website/terms.php', $lang), (string) ($t['footer_terms'] ?? 'Terms'), true)
        . $link(hs_bilohash_external_url('/website/privacy-policy.php', $lang), (string) ($t['footer_privacy'] ?? 'Privacy'), true)
        . $link(hs_bilohash_external_url('/website/cookies.php', $lang), (string) ($t['footer_cookies'] ?? 'Cookies'), true);

    return '<footer class="hs-site-footer" role="contentinfo">'
        . '<div class="hs-footer-inner">'
        . '<div class="hs-footer-grid">'
        . '<div class="hs-footer-brand">'
        . '<a href="' . hs_h(hs_url()) . '" class="hs-footer-logo">'
        . '<span class="hs-logo-mark"><i class="fa-solid fa-cloud"></i></span>'
        . '<span>' . hs_h($t['brand'] ?? 'Hosting CMS') . '</span></a>'
        . '<p class="hs-footer-tagline">' . hs_h($t['footer_tagline'] ?? $t['tagline'] ?? '') . '</p>'
        . '<p class="hs-footer-meta"><i class="fa-solid fa-location-dot"></i> ' . hs_h($t['footer_made_in'] ?? '') . '</p>'
        . '<a href="mailto:info@bilohash.com" class="hs-footer-email"><i class="fa-solid fa-envelope"></i> info@bilohash.com</a>'
        . '</div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_hosting'] ?? 'Hosting') . '</h4><ul>' . $hostingLinks . '</ul></div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_ecosystem'] ?? 'Ecosystem') . '</h4><ul>' . $ecoLinks . '</ul></div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_company'] ?? 'Company') . '</h4><ul>' . $companyLinks . '</ul></div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_legal'] ?? 'Legal') . '</h4><ul>' . $legalLinks . '</ul></div>'
        . '</div>'
        . '<div class="hs-footer-bottom">'
        . '<span class="hs-footer-copy">' . hs_h($copyright) . '</span>'
        . '<span class="hs-footer-version">' . hs_h(hs_version_label()) . '</span>'
        . '<div class="hs-footer-bottom-links">'
        . '<a href="' . hs_h(hs_bilohash_external_url('/website/terms.php', $lang)) . '" target="_blank" rel="noopener noreferrer">' . hs_h($t['footer_terms'] ?? 'Terms') . '</a>'
        . '<a href="' . hs_h(hs_bilohash_external_url('/website/privacy-policy.php', $lang)) . '" target="_blank" rel="noopener noreferrer">' . hs_h($t['footer_privacy'] ?? 'Privacy') . '</a>'
        . '<a href="https://bilohash.com/contact.php" target="_blank" rel="noopener noreferrer">' . hs_h($t['footer_contact'] ?? 'Contact') . '</a>'
        . '</div></div></div></footer>';
}