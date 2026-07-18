<?php
declare(strict_types=1);

if (!function_exists('hs_brand_logo_mark') && is_file(__DIR__ . '/brand-mark.php')) {
    require_once __DIR__ . '/brand-mark.php';
}
if (!function_exists('hs_brand_logo_mark')) {
    /** @param string $extraClass */
    function hs_brand_logo_mark(string $extraClass = ''): string
    {
        $cls = trim('hs-brand-mark ' . $extraClass);

        return '<span class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '"><i class="fa-solid fa-sun" aria-hidden="true"></i></span>';
    }
}

/**
 * Sticky public header: desktop nav + full-screen mobile drawer (burger / close).
 *
 * @param array<string, string> $t
 */
function hs_render_public_header(array $t, string $lang): string
{
    $menuOpen = (string) ($t['nav_menu'] ?? 'Menu');
    $menuClose = (string) ($t['nav_close'] ?? $t['btn_close'] ?? 'Close');

    /**
     * Home section anchors must go to "/" + hash (and keep ?lang=), not hs_url('#x') which becomes "/#x"
     * only when path handling is correct — and break when used as a path segment.
     */
    $homeHash = static function (string $hash) use ($lang): string {
        $hash = ltrim($hash, '#');
        $qs = ($lang !== '' && $lang !== 'en') ? ['lang' => $lang] : [];
        // Always land on homepage for section anchors
        return hs_url('', $qs) . '#' . $hash;
    };

    $nav = static function (string $href, string $labelKey, string $fallback, string $icon) use ($t): string {
        $label = (string) ($t[$labelKey] ?? $fallback);

        return '<a href="' . hs_h($href) . '" class="hs-header-nav-link">'
            . '<i class="fa-solid ' . hs_h($icon) . '" aria-hidden="true"></i>'
            . '<span>' . hs_h($label) . '</span></a>';
    };

    // Correct destinations (homepage anchors + domain page)
    $hrefDomains = hs_url('domain', ($lang !== '' && $lang !== 'en') ? ['lang' => $lang] : []);
    $hrefPricing = $homeHash('pricing');
    $hrefPanel = $homeHash('panel'); // control-panel block, not #features
    $hrefEco = $homeHash('ecosystem');
    $hrefSpeed = $homeHash('performance');
    $hrefFaq = $homeHash('faq');

    return '<header class="hs-site-header" data-hs-public-header>'
        . '<div class="hs-header-main">'
        . '<div class="hs-header-main-inner">'
        . '<a href="' . hs_h(hs_url('', ($lang !== '' && $lang !== 'en') ? ['lang' => $lang] : [])) . '" class="hs-logo" title="' . hs_h($t['brand'] ?? 'SolaSkinner') . '">'
        . '<span class="hs-logo-mark hs-logo-sun">' . hs_brand_logo_mark() . '</span>'
        . '<span class="hs-logo-text-wrap">'
        . '<span class="hs-logo-text">' . hs_h($t['brand'] ?? 'SolaSkinner') . '</span>'
        . '<span class="hs-logo-tagline">' . hs_h((string) ($t['logo_tagline'] ?? $t['brand'] ?? 'SolaSkinner')) . '</span>'
        . '</span>'
        . '</a>'
        . '<nav class="hs-header-nav" id="hs-public-nav" data-hs-public-nav aria-label="' . hs_h($t['nav_main'] ?? 'Main') . '">'
        . '<div class="hs-header-nav-panel">'
        . '<p class="hs-header-nav-kicker">' . hs_h($t['brand'] ?? 'SolaSkinner') . '</p>'
        . $nav($hrefDomains, 'nav_domains', 'Domains', 'fa-globe')
        . $nav($hrefPricing, 'nav_pricing', 'Plans', 'fa-server')
        . $nav($hrefPanel, 'nav_panel', 'Panel', 'fa-gauge-high')
        . $nav($hrefEco, 'nav_ecosystem', 'CMS', 'fa-puzzle-piece')
        . $nav($hrefSpeed, 'speed_badge', 'Speed', 'fa-bolt')
        . $nav($hrefFaq, 'nav_faq', 'FAQ', 'fa-circle-question')
        . '<div class="hs-header-nav-mobile-actions">'
        . '<a href="' . hs_h(hs_url('login.php')) . '" class="hs-btn hs-btn-ghost hs-header-nav-cta">'
        . hs_h($t['nav_login'] ?? 'Log in') . '</a>'
        . '<a href="' . hs_h(hs_url('register.php')) . '" class="hs-btn hs-btn-primary hs-header-nav-cta">'
        . hs_h($t['nav_register'] ?? 'Get started') . '</a>'
        . '</div>'
        . '</div>'
        . '</nav>'
        . '<div class="hs-header-actions">'
        // Language is chosen via floating widget + footer (no header dropdown).
        . '<a href="' . hs_h(hs_url('login.php')) . '" class="hs-btn hs-btn-ghost hs-header-btn-login">'
        . hs_h($t['nav_login'] ?? 'Log in') . '</a>'
        . '<a href="' . hs_h(hs_url('register.php')) . '" class="hs-btn hs-btn-primary hs-header-btn-register">'
        . '<span class="hs-header-btn-register-text">' . hs_h($t['nav_register'] ?? 'Get started') . '</span>'
        . '</a>'
        . '</div>'
        . '<button type="button" class="hs-header-burger" data-hs-public-burger'
        . ' aria-expanded="false" aria-controls="hs-public-nav"'
        . ' data-label-open="' . hs_h($menuOpen) . '" data-label-close="' . hs_h($menuClose) . '"'
        . ' aria-label="' . hs_h($menuOpen) . '">'
        . '<span class="hs-header-burger-icon hs-header-burger-icon--open" aria-hidden="true">'
        . '<i class="fa-solid fa-bars"></i></span>'
        . '<span class="hs-header-burger-icon hs-header-burger-icon--close" aria-hidden="true">'
        . '<i class="fa-solid fa-xmark"></i></span>'
        . '</button>'
        . '</div></div>'
        . '<div class="hs-header-mobile-backdrop" data-hs-public-backdrop hidden></div>'
        . '</header>';
}
