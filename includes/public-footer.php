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
    if (is_file(__DIR__ . '/legal.php')) {
        require_once __DIR__ . '/legal.php';
    }
    $year = (string) date('Y');
    $copyright = str_replace('{year}', $year, (string) ($t['footer_copyright'] ?? '© {year} SolaSkinner'));
    $contactEmail = (string) ($t['footer_email'] ?? 'support@solaskinner.com');
    $localLegal = function_exists('hs_has_local_legal') && hs_has_local_legal();

    $link = static function (string $href, string $label, bool $external = false): string {
        $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
        return '<li><a href="' . hs_h($href) . '"' . $attrs . '>' . hs_h($label) . '</a></li>';
    };

    // SolaSkinner footer: only our product links (no bilohash.com ads).
    $langQs = ($lang !== '' && $lang !== 'en') ? ['lang' => $lang] : [];
    $home = static function (string $hash = '') use ($langQs): string {
        $base = hs_url('', $langQs);
        $hash = ltrim($hash, '#');

        return $hash === '' ? $base : ($base . '#' . $hash);
    };

    $hostingLinks = ''
        . $link($home('pricing'), (string) ($t['footer_plans'] ?? 'Plans'))
        . $link(hs_url('domain', $langQs), (string) ($t['footer_domain'] ?? 'Domain search'))
        . $link(hs_url('register.php', $langQs), (string) ($t['footer_register'] ?? 'Register'))
        . $link(hs_url('register.php', array_merge($langQs, ['plan' => 'vps'])), (string) ($t['footer_vps'] ?? 'VPS'))
        . $link(hs_url('login.php', $langQs), (string) ($t['footer_login'] ?? 'Login'))
        . $link(hs_url('panel/'), (string) ($t['footer_panel'] ?? 'Client panel'));

    $appsLinks = ''
        . $link($home('ecosystem'), (string) ($t['footer_cms_free'] ?? '15+ free CMS'))
        . $link(hs_url('seo/', $langQs), (string) ($t['footer_seo_apps'] ?? 'Hosting for apps'))
        . $link(hs_url('register.php', $langQs), (string) ($t['footer_try_hosting'] ?? 'Try hosting'))
        . $link(hs_url('domain', $langQs), (string) ($t['footer_test_domain'] ?? 'Test domain search'))
        . $link($home('panel'), (string) ($t['footer_features'] ?? 'Features'));

    $companyLinks = ''
        . $link($home(), (string) ($t['footer_about'] ?? 'About'))
        . $link('mailto:' . $contactEmail, (string) ($t['footer_contact'] ?? 'Contact'))
        . $link(hs_url('login.php', $langQs), (string) ($t['footer_panel'] ?? 'Client panel'))
        . $link(hs_url('register.php', $langQs), (string) ($t['footer_register'] ?? 'Register'));

    if ($localLegal) {
        $legalLinks = ''
            . $link(hs_legal_url('terms.php', $lang), (string) ($t['footer_terms'] ?? 'Terms'))
            . $link(hs_legal_url('privacy.php', $lang), (string) ($t['footer_privacy'] ?? 'Privacy'))
            . $link(hs_legal_url('cookies.php', $lang), (string) ($t['footer_cookies'] ?? 'Cookies'))
            . $link(hs_legal_url('domain-registration.php', $lang), (string) ($t['footer_domains_legal'] ?? 'Domains'));
        $bottomLegal = '<a href="' . hs_h(hs_legal_url('terms.php', $lang)) . '">' . hs_h($t['footer_terms'] ?? 'Terms') . '</a>'
            . '<a href="' . hs_h(hs_legal_url('privacy.php', $lang)) . '">' . hs_h($t['footer_privacy'] ?? 'Privacy') . '</a>'
            . '<a href="' . hs_h(hs_legal_url('cookies.php', $lang)) . '">' . hs_h($t['footer_cookies'] ?? 'Cookies') . '</a>'
            . '<a href="' . hs_h(hs_legal_url('domain-registration.php', $lang)) . '">' . hs_h($t['footer_domains_legal'] ?? 'Domains') . '</a>'
            . '<a href="mailto:' . hs_h($contactEmail) . '">' . hs_h($t['footer_contact'] ?? 'Contact') . '</a>';
    } else {
        $legalLinks = ''
            . $link(hs_url('terms.php'), (string) ($t['footer_terms'] ?? 'Terms'))
            . $link(hs_url('privacy.php'), (string) ($t['footer_privacy'] ?? 'Privacy'))
            . $link(hs_url('cookies.php'), (string) ($t['footer_cookies'] ?? 'Cookies'))
            . $link(hs_url('domain-registration.php'), (string) ($t['footer_domains_legal'] ?? 'Domains'));
        $bottomLegal = '<a href="' . hs_h(hs_url('terms.php')) . '">' . hs_h($t['footer_terms'] ?? 'Terms') . '</a>'
            . '<a href="' . hs_h(hs_url('privacy.php')) . '">' . hs_h($t['footer_privacy'] ?? 'Privacy') . '</a>'
            . '<a href="' . hs_h(hs_url('cookies.php')) . '">' . hs_h($t['footer_cookies'] ?? 'Cookies') . '</a>'
            . '<a href="' . hs_h(hs_url('domain-registration.php')) . '">' . hs_h($t['footer_domains_legal'] ?? 'Domains') . '</a>'
            . '<a href="mailto:' . hs_h($contactEmail) . '">' . hs_h($t['footer_contact'] ?? 'Contact') . '</a>';
    }

    $html = '<footer class="hs-site-footer" role="contentinfo">'
        . '<div class="hs-footer-inner">'
        . '<div class="hs-footer-grid">'
        . '<div class="hs-footer-brand">'
        . '<a href="' . hs_h(hs_url()) . '" class="hs-footer-logo">'
        . '<span class="hs-logo-mark hs-logo-sun" aria-hidden="true"><i class="fa-solid fa-sun"></i></span>'
        . '<span>' . hs_h($t['brand'] ?? 'SolaSkinner') . '</span></a>'
        . '<p class="hs-footer-tagline">' . hs_h($t['footer_tagline'] ?? $t['tagline'] ?? '') . '</p>'
        . '<p class="hs-footer-meta"><i class="fa-solid fa-location-dot"></i> ' . hs_h($t['footer_made_in'] ?? 'Drammen, Norway · EU/EEA') . '</p>'
        . '<a href="mailto:' . hs_h($contactEmail) . '" class="hs-footer-email"><i class="fa-solid fa-envelope"></i> ' . hs_h($contactEmail) . '</a>'
        . '</div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_hosting'] ?? 'Hosting') . '</h4><ul>' . $hostingLinks . '</ul></div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_apps'] ?? $t['footer_col_ecosystem'] ?? 'Apps & CMS') . '</h4><ul>' . $appsLinks . '</ul></div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_company'] ?? 'Company') . '</h4><ul>' . $companyLinks . '</ul></div>'
        . '<div class="hs-footer-col"><h4>' . hs_h($t['footer_col_legal'] ?? 'Legal') . '</h4><ul>' . $legalLinks . '</ul></div>'
        . '</div>'
        . '<div class="hs-footer-bottom">'
        . '<span class="hs-footer-copy">' . hs_h($copyright) . '</span>'
        . '<span class="hs-footer-version">' . hs_h(hs_version_label()) . '</span>'
        . '<div class="hs-footer-bottom-links">'
        . $bottomLegal
        . '<button type="button" class="hs-footer-lang-btn" data-hs-lang-float-open>'
        . '<i class="fa-solid fa-globe" aria-hidden="true"></i> '
        . hs_h($t['footer_language'] ?? $t['lang_float_open'] ?? 'Language')
        . '</button>'
        . '</div></div></div></footer>';

    if ($localLegal && function_exists('hs_render_cookie_consent')) {
        $html .= hs_render_cookie_consent($t, $lang);
    }

    if (function_exists('hs_render_lang_float_widget')) {
        $html .= hs_render_lang_float_widget($lang, $t);
    }

    return $html;
}