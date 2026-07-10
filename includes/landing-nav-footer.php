<?php
declare(strict_types=1);

/** @return array<string, string> */
function hs_landing_nav_styles(array $t): array
{
    return [
        'classic' => $t['landing_nav_classic'] ?? 'Classic sticky',
        'minimal' => $t['landing_nav_minimal'] ?? 'Minimal compact',
        'centered' => $t['landing_nav_centered'] ?? 'Centered logo',
        'dark' => $t['landing_nav_dark'] ?? 'Dark bar',
        'gradient' => $t['landing_nav_gradient'] ?? 'Brand gradient',
        'glass' => $t['landing_nav_glass'] ?? 'Glass blur',
        'pill' => $t['landing_nav_pill'] ?? 'Pill links',
        'cta' => $t['landing_nav_cta'] ?? 'With CTA button',
        'stacked' => $t['landing_nav_stacked'] ?? 'Stacked two rows',
        'bordered' => $t['landing_nav_bordered'] ?? 'Bold border',
        'micro' => $t['landing_nav_micro'] ?? 'Micro (tiny)',
        'tall' => $t['landing_nav_tall'] ?? 'Tall / large',
        'floating' => $t['landing_nav_floating'] ?? 'Floating card',
        'transparent' => $t['landing_nav_transparent'] ?? 'Transparent',
        'underline' => $t['landing_nav_underline'] ?? 'Underline links',
        'split' => $t['landing_nav_split'] ?? 'Split layout',
        'announce' => $t['landing_nav_announce'] ?? 'With top strip',
        'mega' => $t['landing_nav_mega'] ?? 'Mega spacious',
        'tall_xl' => $t['landing_nav_tall_xl'] ?? 'Extra tall (XL)',
        'hero_bar' => $t['landing_nav_hero_bar'] ?? 'Hero banner bar',
        'boxed' => $t['landing_nav_boxed'] ?? 'Boxed card',
        'ribbon' => $t['landing_nav_ribbon'] ?? 'Ribbon accent',
        'neon' => $t['landing_nav_neon'] ?? 'Neon glow',
        'corporate' => $t['landing_nav_corporate'] ?? 'Corporate formal',
        'icon_focus' => $t['landing_nav_icon_focus'] ?? 'Large logo icon',
        'wide' => $t['landing_nav_wide'] ?? 'Wide spacious',
    ];
}

/** @return list<string> */
function hs_landing_logo_icon_choices(): array
{
    return [
        'fa-store', 'fa-globe', 'fa-rocket', 'fa-bolt', 'fa-star', 'fa-heart',
        'fa-building', 'fa-briefcase', 'fa-chart-line', 'fa-handshake', 'fa-award',
        'fa-lightbulb', 'fa-palette', 'fa-camera', 'fa-cart-shopping', 'fa-leaf',
        'fa-plane', 'fa-code', 'fa-cloud', 'fa-shield-halved', 'fa-gem', 'fa-crown',
        'fa-fire', 'fa-mountain-sun',
    ];
}

/** @param array<string, mixed> $data */
function hs_landing_brand_html(array $data, callable $h, bool $forceTagline = false): string
{
    $name = (string) ($data['business_name'] ?? 'Business');
    $tagline = (string) ($data['tagline'] ?? '');
    $style = (string) ($data['nav_style'] ?? 'classic');
    $icon = preg_replace('/[^a-z0-9-]/i', '', (string) ($data['logo_icon'] ?? ''));
    $iconStyle = (string) ($data['icon_style'] ?? 'solid');
    $prefix = $iconStyle === 'regular' ? 'fa-regular' : 'fa-solid';
    $iconHtml = $icon !== ''
        ? '<span class="brand-icon"><i class="' . $h($prefix . ' ' . $icon) . '" aria-hidden="true"></i></span>'
        : '';
    $showTagline = $forceTagline || !in_array($style, ['minimal', 'micro'], true);
    $taglineHtml = $showTagline && $tagline !== '' ? '<span>' . $h($tagline) . '</span>' : '';

    return '<div class="brand">' . $iconHtml . $h($name) . $taglineHtml . '</div>';
}

/** @return array<string, string> */
function hs_landing_burger_modes(array $t): array
{
    return [
        'mobile' => $t['landing_nav_burger_mobile'] ?? 'Mobile only',
        'always' => $t['landing_nav_burger_always'] ?? 'Always (hamburger)',
        'off' => $t['landing_nav_burger_off'] ?? 'Off (inline links)',
    ];
}

/** @return array<string, string> */
function hs_landing_footer_styles_extra(array $t): array
{
    return [
        'dark' => $t['landing_footer_dark'] ?? 'Dark bar',
        'gradient' => $t['landing_footer_gradient'] ?? 'Brand gradient',
        'mega' => $t['landing_footer_mega'] ?? 'Mega (3 columns)',
        'split' => $t['landing_footer_split'] ?? 'Split layout',
        'compact' => $t['landing_footer_compact'] ?? 'Compact one line',
        'branded' => $t['landing_footer_branded'] ?? 'Branded watermark',
        'contact' => $t['landing_footer_contact'] ?? 'With contact info',
        'wave' => $t['landing_footer_wave'] ?? 'Wave top',
        'micro' => $t['landing_footer_micro'] ?? 'Micro (tiny)',
        'tall' => $t['landing_footer_tall'] ?? 'Tall / large',
        'newsletter' => $t['landing_footer_newsletter'] ?? 'With newsletter',
        'double' => $t['landing_footer_double'] ?? 'Two-tier',
        'ribbon' => $t['landing_footer_ribbon'] ?? 'Ribbon accent',
        'map_row' => $t['landing_footer_map_row'] ?? 'Map + info',
        'minimal_dark' => $t['landing_footer_minimal_dark'] ?? 'Minimal dark',
        'sticky_bar' => $t['landing_footer_sticky_bar'] ?? 'Sticky bottom bar',
        'tall_xl' => $t['landing_footer_tall_xl'] ?? 'Extra tall (XL)',
        'mega_tall' => $t['landing_footer_mega_tall'] ?? 'Mega tall (4 cols)',
        'hero' => $t['landing_footer_hero'] ?? 'Hero gradient',
        'columns4' => $t['landing_footer_columns4'] ?? '4 columns',
    ];
}

/** @param array<string, mixed> $data */
function hs_landing_nav_links_html(array $data, callable $h, string $wrapClass = 'nav-links'): string
{
    $links = '';
    foreach ((array) ($data['nav_links'] ?? []) as $link) {
        if (!is_array($link) || empty($link['on'])) {
            continue;
        }
        $label = trim((string) ($link['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        $links .= '<a href="' . $h((string) ($link['url'] ?? '#')) . '">' . $h($label) . '</a>';
    }
    if ($links === '') {
        return '';
    }

    $cls = trim($wrapClass . ' nav-links-desktop');

    return '<nav class="' . $h($cls) . '">' . $links . '</nav>';
}

/** @param array<string, mixed> $data */
function hs_landing_nav_cta_html(array $data, callable $h): string
{
    $text = trim((string) ($data['nav_cta_text'] ?? ''));
    if ($text === '') {
        return '';
    }
    $url = trim((string) ($data['nav_cta_url'] ?? '#contact'));

    return '<a class="nav-cta btn primary" href="' . $h($url) . '">' . $h($text) . '</a>';
}

/** @param array<string, mixed> $data */
function hs_landing_footer_social_html(array $data, callable $h): string
{
    $social = '';
    foreach (['social_facebook' => 'fa-facebook', 'social_instagram' => 'fa-instagram', 'social_linkedin' => 'fa-linkedin'] as $key => $icon) {
        $url = trim((string) ($data[$key] ?? ''));
        if ($url !== '') {
            $social .= '<a href="' . $h($url) . '" rel="noopener" target="_blank"><i class="fa-brands ' . $h($icon) . '"></i></a>';
        }
    }

    return $social;
}

/** @param array<string, mixed> $data */
function hs_landing_footer_nav_links(array $data, callable $h): string
{
    $html = '<div class="footer-nav">';
    foreach ((array) ($data['nav_links'] ?? []) as $link) {
        if (!is_array($link) || empty($link['on'])) {
            continue;
        }
        $label = trim((string) ($link['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        $html .= '<a href="' . $h((string) ($link['url'] ?? '#')) . '">' . $h($label) . '</a>';
    }

    return $html . '</div>';
}

/** @param array<string, mixed> $data */
function hs_landing_nav_burger_btn(callable $h): string
{
    return '<button type="button" class="nav-burger-btn" aria-label="' . $h('Menu') . '" aria-expanded="false" data-nav-burger>'
        . '<i class="fa-solid fa-bars" aria-hidden="true"></i></button>';
}

/** @param array<string, mixed> $data */
function hs_landing_nav_drawer_html(array $data, callable $h, string $ctaHtml): string
{
    $drawerLinks = hs_landing_nav_links_html($data, $h, 'nav-links nav-links-drawer');
    if ($drawerLinks === '' && $ctaHtml === '') {
        return '';
    }

    return '<div class="nav-drawer" hidden data-nav-drawer>' . $drawerLinks . $ctaHtml . '</div>'
        . '<div class="nav-overlay" hidden data-nav-overlay></div>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_nav(array $data, callable $h): string
{
    $styles = hs_landing_nav_styles([]);
    $style = (string) ($data['nav_style'] ?? 'classic');
    if (!isset($styles[$style])) {
        $style = 'classic';
    }
    $burgerModes = hs_landing_burger_modes([]);
    $burger = (string) ($data['nav_burger'] ?? 'mobile');
    if (!isset($burgerModes[$burger])) {
        $burger = 'mobile';
    }
    $linksHtml = hs_landing_nav_links_html($data, $h);
    $ctaHtml = hs_landing_nav_cta_html($data, $h);
    $burgerBtn = $burger !== 'off' ? hs_landing_nav_burger_btn($h) : '';
    $drawerHtml = $burger !== 'off' ? hs_landing_nav_drawer_html($data, $h, $ctaHtml) : '';

    $brand = hs_landing_brand_html($data, $h);
    $tagline = (string) ($data['tagline'] ?? '');

    $rowInner = '<div class="nav-inner">' . $brand . $linksHtml . $ctaHtml . $burgerBtn . '</div>';
    $announceBar = ($style === 'announce' && $tagline !== '')
        ? '<div class="nav-announce"><div class="wrap">' . $h($tagline) . '</div></div>' : '';

    $inner = match ($style) {
        'centered' => '<div class="nav-inner nav-inner-centered"><div class="nav-brand-center">' . $brand . '</div>'
            . ($linksHtml !== '' ? '<div class="nav-links-row">' . $linksHtml . '</div>' : '')
            . $burgerBtn . '</div>',
        'stacked' => '<div class="nav-inner nav-inner-stacked">' . $brand
            . ($linksHtml !== '' ? '<div class="nav-links-row">' . $linksHtml . '</div>' : '')
            . $burgerBtn . '</div>',
        'glass' => '<div class="nav-glass-wrap"><div class="nav-inner">' . $brand . $linksHtml . $ctaHtml . $burgerBtn . '</div></div>',
        'floating' => '<div class="nav-floating-wrap"><div class="nav-inner">' . $brand . $linksHtml . $ctaHtml . $burgerBtn . '</div></div>',
        'split' => '<div class="nav-inner nav-inner-split">' . $brand . '<div class="nav-split-links">' . $linksHtml . $ctaHtml . '</div>' . $burgerBtn . '</div>',
        'announce' => $announceBar . $rowInner,
        'boxed' => '<div class="nav-boxed-wrap"><div class="nav-inner">' . $brand . $linksHtml . $ctaHtml . $burgerBtn . '</div></div>',
        'ribbon' => '<div class="nav-ribbon-accent" aria-hidden="true"></div>' . $rowInner,
        'cta', 'bordered', 'pill', 'dark', 'gradient', 'minimal', 'classic', 'micro', 'tall', 'tall_xl', 'hero_bar', 'neon', 'corporate', 'icon_focus', 'wide', 'transparent', 'underline', 'mega' => $rowInner,
        default => $rowInner,
    };

    return '<header class="nav nav-' . $h($style) . ' nav-burger-' . $h($burger) . '">' . $inner . $drawerHtml . '</header>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_footer(array $data, callable $h): string
{
    $baseStyles = [
        'minimal' => true,
        'columns' => true,
        'centered' => true,
        'social' => true,
    ];
    $extra = hs_landing_footer_styles_extra([]);
    $style = (string) ($data['footer_style'] ?? 'minimal');
    if (!isset($baseStyles[$style]) && !isset($extra[$style])) {
        $style = 'minimal';
    }

    $name = (string) ($data['business_name'] ?? '');
    $tagline = (string) ($data['tagline'] ?? '');
    $extraText = (string) ($data['footer_text'] ?? '');
    $year = date('Y');
    $social = hs_landing_footer_social_html($data, $h);
    $navLinks = hs_landing_footer_nav_links($data, $h);
    $copy = '&copy; ' . $h($year) . ($extraText !== '' ? ' · ' . $h($extraText) : '');

    return match ($style) {
        'columns' => '<footer class="footer footer-columns"><div class="wrap footer-cols">'
            . '<div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . '<div><strong>Links</strong>' . $navLinks . '</div>'
            . '<div><strong>' . $h($year) . '</strong><p>' . $h($extraText !== '' ? $extraText : $name) . '</p></div></div></footer>',
        'centered' => '<footer class="footer footer-centered"><div class="wrap">'
            . '<strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($extraText !== '' ? '<p class="footer-extra">' . $h($extraText) . '</p>' : '')
            . '<span>' . $copy . '</span></div></footer>',
        'social' => '<footer class="footer footer-social"><div class="wrap">'
            . '<div class="footer-social-brand"><strong>' . $h($name) . '</strong>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>'
            . '<span>' . $copy . ' ' . $h($name) . '</span></div></footer>',
        'dark' => '<footer class="footer footer-dark"><div class="wrap footer-dark-inner">'
            . '<div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . $navLinks . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'gradient' => '<footer class="footer footer-gradient"><div class="wrap footer-gradient-inner">'
            . '<strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '')
            . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'mega' => '<footer class="footer footer-mega"><div class="wrap footer-mega-grid">'
            . '<div class="footer-mega-brand"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>'
            . '<div class="footer-mega-links"><strong>Menu</strong>' . $navLinks . '</div>'
            . '<div class="footer-mega-meta"><strong>' . $h($year) . '</strong><p>' . $h($extraText !== '' ? $extraText : $name) . '</p></div>'
            . '</div><div class="wrap footer-mega-bar"><span>' . $copy . '</span></div></footer>',
        'split' => '<footer class="footer footer-split"><div class="wrap footer-split-inner">'
            . '<div class="footer-split-left"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . '<div class="footer-split-right">' . $navLinks . '<span>' . $copy . '</span></div></div></footer>',
        'compact' => '<footer class="footer footer-compact"><div class="wrap footer-compact-inner">'
            . '<span class="footer-compact-brand">' . $h($name) . '</span>'
            . $navLinks . '<span class="footer-compact-copy">' . $copy . '</span></div></footer>',
        'branded' => '<footer class="footer footer-branded"><div class="wrap footer-branded-inner">'
            . '<div class="footer-watermark" aria-hidden="true">' . $h($name) . '</div>'
            . '<div class="footer-branded-content"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '')
            . '<span>' . $copy . '</span></div></div></footer>',
        'contact' => '<footer class="footer footer-contact"><div class="wrap footer-contact-inner">'
            . '<div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . '<div class="footer-contact-links">' . $navLinks
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>'
            . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'wave' => '<footer class="footer footer-wave"><div class="footer-wave-shape" aria-hidden="true"></div><div class="wrap footer-wave-inner">'
            . '<strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>' . $navLinks
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '')
            . '<span>' . $copy . '</span></div></footer>',
        'micro' => '<footer class="footer footer-micro"><div class="wrap footer-micro-inner"><span>' . $copy . '</span><span>' . $h($name) . '</span></div></footer>',
        'tall' => '<footer class="footer footer-tall"><div class="wrap footer-tall-inner"><div class="footer-tall-brand"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>' . $navLinks
            . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'newsletter' => '<footer class="footer footer-newsletter"><div class="wrap footer-newsletter-inner"><div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . '<form class="footer-newsletter-form" action="#" onsubmit="return false"><input type="email" placeholder="Email" aria-label="Email">'
            . '<button type="submit" class="btn primary">Subscribe</button></form>'
            . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'double' => '<footer class="footer footer-double"><div class="wrap footer-double-top"><div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . $navLinks . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>'
            . '<div class="wrap footer-double-bar"><span>' . $copy . '</span></div></footer>',
        'ribbon' => '<footer class="footer footer-ribbon"><div class="footer-ribbon-accent" aria-hidden="true"></div><div class="wrap footer-ribbon-inner"><strong>' . $h($name) . '</strong>'
            . $navLinks . '<span>' . $copy . '</span></div></footer>',
        'map_row' => '<footer class="footer footer-map-row"><div class="wrap footer-map-grid"><div class="footer-map-placeholder" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></div>'
            . '<div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>' . $navLinks . '<span class="footer-copy">' . $copy . '</span></div></div></footer>',
        'minimal_dark' => '<footer class="footer footer-minimal-dark"><div class="wrap">' . $copy . ' · ' . $h($name) . '</div></footer>',
        'sticky_bar' => '<footer class="footer footer-sticky-bar"><div class="wrap footer-sticky-inner"><span class="footer-sticky-brand">' . $h($name) . '</span>'
            . $navLinks . '<span>' . $copy . '</span></div></footer>',
        'tall_xl' => '<footer class="footer footer-tall-xl"><div class="wrap footer-tall-xl-inner"><div class="footer-tall-brand"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>' . $navLinks
            . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'mega_tall' => '<footer class="footer footer-mega-tall"><div class="wrap footer-mega-tall-grid">'
            . '<div class="footer-mega-brand"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '') . '</div>'
            . '<div class="footer-mega-links"><strong>Menu</strong>' . $navLinks . '</div>'
            . '<div><strong>' . $h($year) . '</strong><p>' . $h($extraText !== '' ? $extraText : $name) . '</p></div>'
            . '<div class="footer-mega-cta"><span class="footer-copy">' . $copy . '</span></div>'
            . '</div></footer>',
        'hero' => '<footer class="footer footer-hero"><div class="wrap footer-hero-inner"><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p>'
            . $navLinks . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '')
            . '<span class="footer-copy">' . $copy . '</span></div></footer>',
        'columns4' => '<footer class="footer footer-columns4"><div class="wrap footer-cols4">'
            . '<div><strong>' . $h($name) . '</strong><p>' . $h($tagline) . '</p></div>'
            . '<div><strong>Links</strong>' . $navLinks . '</div>'
            . '<div><strong>Social</strong>' . ($social !== '' ? '<div class="footer-social-icons">' . $social . '</div>' : '<p>—</p>') . '</div>'
            . '<div><strong>' . $h($year) . '</strong><p>' . $h($extraText !== '' ? $extraText : $name) . '</p></div>'
            . '</div><div class="wrap footer-cols4-bar"><span>' . $copy . '</span></div></footer>',
        default => '<footer class="footer footer-minimal"><div class="wrap">' . $copy . ' ' . $h($name) . '</div></footer>',
    };
}

function hs_landing_chrome_css(): string
{
    return '.nav-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;width:100%;max-width:68rem;margin:0 auto;padding:0 1.25rem}'
        . '.nav-glass-wrap{max-width:68rem;margin:0 auto;padding:.45rem 1rem}.nav-glass .nav-glass-wrap{background:rgba(255,255,255,.55);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.65);border-radius:1rem;box-shadow:0 8px 32px rgba(15,23,42,.08)}'
        . '.nav-centered,.nav-stacked{padding:.65rem 0}.nav-inner-centered,.nav-inner-stacked{flex-direction:column;align-items:center;text-align:center;gap:.55rem}'
        . '.nav-brand-center .brand span{display:block;margin:.2rem 0 0}.nav-links-row{width:100%;display:flex;justify-content:center}'
        . '.nav-inner-stacked .brand{width:100%;text-align:center}.nav-inner-stacked .nav-links{justify-content:center;width:100%}'
        . '.nav-minimal{padding:.55rem 1.25rem}.nav-minimal .brand span{display:none}.nav-minimal .nav-links a{font-size:.82rem}'
        . '.nav-dark{background:linear-gradient(135deg,#0f172a,#1e293b);border-bottom:none}.nav-dark .brand,.nav-dark .brand span{color:#fff}.nav-dark .nav-links a{color:#e2e8f0}.nav-dark .nav-links a:hover{color:#fff}'
        . '.nav-gradient{background:linear-gradient(135deg,var(--c),var(--c2));border-bottom:none}.nav-gradient .brand,.nav-gradient .brand span{color:#fff}.nav-gradient .nav-links a{color:rgba(255,255,255,.92)}.nav-gradient .nav-cta{background:#fff;color:var(--c)!important}'
        . '.nav-pill .nav-links a{padding:.35rem .75rem;border-radius:999px;background:color-mix(in srgb,var(--c) 10%,#fff);color:var(--c)}.nav-pill .nav-links a:hover{background:color-mix(in srgb,var(--c) 18%,#fff)}'
        . '.nav-bordered{border-bottom:3px solid var(--c);box-shadow:0 4px 20px rgba(15,23,42,.06)}.nav-bordered .brand{font-size:1.15rem}'
        . '.nav-cta.btn{padding:.5rem 1rem;font-size:.85rem;white-space:nowrap;flex-shrink:0}'
        . '.nav-cta{margin-left:auto}'
        . '.footer-dark{background:#0f172a;color:#e2e8f0;padding:2.5rem 0}.footer-dark strong{color:#fff}.footer-dark .footer-nav a{color:#94a3b8}.footer-dark-inner{display:grid;gap:1rem}'
        . '.footer-gradient{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;padding:2.5rem 0;text-align:center}.footer-gradient .footer-social-icons a{color:#fff}.footer-gradient-inner{display:flex;flex-direction:column;align-items:center;gap:.65rem}'
        . '.footer-mega{background:#f8fafc;border-top:1px solid #e2e8f0;padding:0}.footer-mega-grid{display:grid;grid-template-columns:1.2fr 1fr .8fr;gap:1.5rem;padding:2rem 1.25rem}.footer-mega-bar{border-top:1px solid #e2e8f0;padding:.75rem 1.25rem;text-align:center;color:#64748b;font-size:.82rem}'
        . '.footer-split{background:#fff;border-top:1px solid #e2e8f0;padding:2rem 0}.footer-split-inner{display:flex;flex-wrap:wrap;justify-content:space-between;gap:1.5rem;align-items:flex-start}.footer-split-right{text-align:right}'
        . '.footer-compact{background:#fff;border-top:1px solid #e2e8f0;padding:.85rem 0}.footer-compact-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;font-size:.82rem}.footer-compact-brand{font-weight:700;color:var(--c)}.footer-compact-copy{color:#94a3b8}'
        . '.footer-branded{position:relative;overflow:hidden;background:#0f172a;color:#e2e8f0;padding:3rem 0}.footer-watermark{position:absolute;inset:auto -5% -30% auto;font-size:clamp(4rem,18vw,12rem);font-weight:900;color:rgba(255,255,255,.04);line-height:1;pointer-events:none;white-space:nowrap}'
        . '.footer-branded-content{position:relative;z-index:1;text-align:center;display:flex;flex-direction:column;align-items:center;gap:.5rem}.footer-branded strong{color:#fff;font-size:1.1rem}'
        . '.footer-contact{background:#fff;border-top:3px solid var(--c);padding:2rem 0}.footer-contact-inner{display:grid;gap:1rem}.footer-contact-links{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}'
        . '.footer-wave{position:relative;background:color-mix(in srgb,var(--c) 8%,#fff);padding:2.5rem 0 2rem;margin-top:2rem}.footer-wave-shape{position:absolute;top:-28px;left:0;right:0;height:28px;background:color-mix(in srgb,var(--c) 8%,#fff);clip-path:ellipse(55% 100% at 50% 100%)}'
        . '.footer-wave-inner{text-align:center;display:flex;flex-direction:column;align-items:center;gap:.5rem}'
        . '.footer-copy{color:#94a3b8;font-size:.82rem}'
        . '.nav-burger-btn{display:none;border:0;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);width:2.5rem;height:2.5rem;border-radius:.65rem;cursor:pointer;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;padding:0}'
        . '.nav-burger-mobile .nav-burger-btn,.nav-burger-always .nav-burger-btn{display:inline-flex}'
        . '.nav-burger-always .nav-links-desktop,.nav-burger-always .nav-inner>.nav-cta,.nav-burger-always .nav-links-row .nav-links{display:none}'
        . '.nav-drawer{position:fixed;top:0;right:0;width:min(18rem,88vw);height:100vh;background:#fff;box-shadow:-8px 0 32px rgba(15,23,42,.12);z-index:50;padding:4.5rem 1.25rem 1.5rem;transform:translateX(100%);transition:transform .25s ease;overflow-y:auto}'
        . '.nav-drawer:not([hidden]){transform:translateX(0)}.nav-drawer .nav-links{flex-direction:column;gap:.25rem;width:100%}'
        . '.nav-drawer .nav-links a{display:block;padding:.7rem .85rem;border-radius:.55rem;font-size:1rem}.nav-drawer .nav-cta{margin:1rem 0 0;width:100%;justify-content:center}'
        . '.nav-overlay{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:40}body.nav-open{overflow:hidden}'
        . '.nav-dark .nav-burger-btn{background:rgba(255,255,255,.12);color:#fff}.nav-gradient .nav-burger-btn{background:rgba(255,255,255,.2);color:#fff}'
        . '.nav-micro{padding:.35rem 1rem;min-height:auto}.nav-micro .brand{font-size:.88rem}.nav-micro .nav-links a{font-size:.78rem;padding:.2rem 0}'
        . '.nav-tall{padding:1.35rem 0}.nav-tall .brand{font-size:1.35rem}.nav-tall .brand span{font-size:1rem;display:block;margin:.35rem 0 0}'
        . '.nav-floating-wrap{max-width:68rem;margin:.65rem auto;padding:0 1rem}.nav-floating .nav-floating-wrap .nav-inner{background:#fff;border-radius:1rem;box-shadow:0 12px 40px rgba(15,23,42,.1);border:1px solid #e2e8f0;padding:.75rem 1.25rem}'
        . '.nav-transparent{background:transparent;border-bottom:none;backdrop-filter:none}.nav-transparent .brand{color:var(--c)}'
        . '.nav-underline .nav-links a{border-bottom:2px solid transparent;padding-bottom:.15rem}.nav-underline .nav-links a:hover{border-bottom-color:var(--c);color:var(--c)}'
        . '.nav-inner-split .nav-split-links{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;border-left:1px solid #e2e8f0;padding-left:1rem;margin-left:auto}'
        . '.nav-announce{background:linear-gradient(90deg,var(--c),var(--c2));color:#fff;text-align:center;font-size:.78rem;font-weight:600;padding:.4rem 1rem}'
        . '.nav-mega{padding:1.1rem 0}.nav-mega .brand{font-size:1.2rem}.nav-mega .nav-links a{font-size:.95rem}'
        . '.brand{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap}.brand-icon{width:2.25rem;height:2.25rem;border-radius:.65rem;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-size:1.05rem;flex-shrink:0}'
        . '.nav-dark .brand-icon,.nav-gradient .brand-icon{background:rgba(255,255,255,.15);color:#fff}'
        . '.nav-tall_xl{padding:2rem 0}.nav-tall_xl .brand{font-size:1.55rem}.nav-tall_xl .brand-icon{width:3rem;height:3rem;font-size:1.35rem;border-radius:.85rem}'
        . '.nav-hero_bar{background:linear-gradient(135deg,var(--c),var(--c2));border-bottom:none;padding:1.5rem 0}.nav-hero_bar .brand,.nav-hero_bar .brand span{color:#fff}.nav-hero_bar .nav-links a{color:rgba(255,255,255,.92)}.nav-hero_bar .brand-icon{background:rgba(255,255,255,.18);color:#fff}'
        . '.nav-boxed-wrap{max-width:68rem;margin:.65rem auto;padding:0 1rem}.nav-boxed .nav-boxed-wrap .nav-inner{background:#fff;border-radius:1rem;box-shadow:0 12px 40px rgba(15,23,42,.1);border:1px solid #e2e8f0;padding:1rem 1.35rem}'
        . '.nav-ribbon{position:relative}.nav-ribbon-accent{position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--c),var(--c2))}'
        . '.nav-neon{box-shadow:0 0 0 1px color-mix(in srgb,var(--c) 35%,#fff),0 0 24px color-mix(in srgb,var(--c) 25%,transparent)}'
        . '.nav-corporate{border-bottom:4px double #e2e8f0;background:#fff}.nav-corporate .brand{font-size:1.1rem;letter-spacing:.02em}'
        . '.nav-icon_focus .brand-icon{width:3.25rem;height:3.25rem;font-size:1.5rem;border-radius:1rem}.nav-icon_focus .brand{font-size:1rem}'
        . '.nav-wide{padding:1.25rem 0}.nav-wide .nav-inner{max-width:80rem}.nav-wide .nav-links{gap:1.35rem}.nav-wide .nav-links a{font-size:.95rem}'
        . '.footer-micro{padding:.45rem 0;font-size:.72rem;color:#94a3b8}.footer-micro-inner{display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap}'
        . '.footer-tall-xl{padding:5.5rem 0;background:#f8fafc}.footer-tall-xl-inner{display:grid;gap:2rem}.footer-tall-xl .footer-tall-brand strong{font-size:1.45rem}'
        . '.footer-mega-tall{background:#0f172a;color:#e2e8f0;padding:0}.footer-mega-tall-grid{display:grid;grid-template-columns:1.3fr 1fr .9fr .8fr;gap:2rem;padding:4rem 1.25rem}.footer-mega-tall strong{color:#fff}'
        . '.footer-hero{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;padding:4rem 0;text-align:center}.footer-hero-inner{display:flex;flex-direction:column;align-items:center;gap:.75rem}.footer-hero .footer-social-icons a{color:#fff}'
        . '.footer-columns4{background:#f8fafc;border-top:1px solid #e2e8f0;padding:0}.footer-cols4{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;padding:3rem 1.25rem}.footer-cols4-bar{border-top:1px solid #e2e8f0;padding:.85rem 1.25rem;text-align:center;color:#64748b;font-size:.82rem}'
        . '.footer-tall{padding:4rem 0;background:#f8fafc}.footer-tall-inner{display:grid;gap:1.5rem}.footer-tall-brand strong{font-size:1.25rem}'
        . '.footer-newsletter{padding:2.5rem 0;background:#fff;border-top:1px solid #e2e8f0}.footer-newsletter-inner{display:grid;gap:1rem}'
        . '.footer-newsletter-form{display:flex;flex-wrap:wrap;gap:.5rem}.footer-newsletter-form input{flex:1;min-width:12rem;padding:.65rem 1rem;border:1px solid #e2e8f0;border-radius:.65rem}'
        . '.footer-double{padding:0;background:#f8fafc}.footer-double-top{display:grid;grid-template-columns:1.2fr 1fr auto;gap:1.5rem;padding:2rem 1.25rem;align-items:start}'
        . '.footer-double-bar{border-top:1px solid #e2e8f0;padding:.75rem 1.25rem;text-align:center;color:#64748b;font-size:.82rem}'
        . '.footer-ribbon{position:relative;padding:2rem 0;background:#fff}.footer-ribbon-accent{position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--c),var(--c2))}'
        . '.footer-ribbon-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}'
        . '.footer-map-row{padding:2rem 0;background:#fff}.footer-map-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:center}'
        . '.footer-map-placeholder{aspect-ratio:16/10;border-radius:1rem;background:color-mix(in srgb,var(--c) 10%,#fff);display:grid;place-items:center;color:var(--c);font-size:2.5rem;border:1px solid #e2e8f0}'
        . '.footer-minimal-dark{background:#0f172a;color:#94a3b8;padding:.85rem 0;text-align:center;font-size:.82rem}'
        . '.footer-sticky-bar{position:sticky;bottom:0;z-index:5;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-top:1px solid #e2e8f0;padding:.55rem 0;box-shadow:0 -4px 20px rgba(15,23,42,.06)}'
        . '.footer-sticky-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;font-size:.8rem}.footer-sticky-brand{font-weight:700;color:var(--c)}'
        . '@media(max-width:800px){.footer-mega-grid,.footer-double-top,.footer-map-grid,.footer-mega-tall-grid,.footer-cols4{grid-template-columns:1fr}.footer-split-inner,.footer-contact-links{flex-direction:column;text-align:center}.footer-split-right{text-align:center}.nav-inner-split{flex-wrap:wrap}.nav-inner-split .nav-split-links{border-left:none;padding-left:0;margin-left:0;width:100%}'
        . '.nav-burger-mobile .nav-links-desktop,.nav-burger-mobile .nav-inner>.nav-cta,.nav-burger-mobile .nav-links-row .nav-links,.nav-burger-mobile .nav-split-links .nav-links{display:none}.nav-burger-mobile .nav-burger-btn{display:inline-flex}.nav-cta{margin-left:0;width:100%;justify-content:center}}';
}

function hs_landing_chrome_js(): string
{
    return '(function(){document.querySelectorAll("[data-nav-burger]").forEach(function(btn){var header=btn.closest(".nav");if(!header)return;var drawer=header.querySelector("[data-nav-drawer]");var overlay=header.querySelector("[data-nav-overlay]");if(!drawer||!overlay)return;function close(){drawer.hidden=true;overlay.hidden=true;btn.setAttribute("aria-expanded","false");document.body.classList.remove("nav-open");}function open(){drawer.hidden=false;overlay.hidden=false;btn.setAttribute("aria-expanded","true");document.body.classList.add("nav-open");}btn.addEventListener("click",function(){drawer.hidden?open():close();});overlay.addEventListener("click",close);drawer.querySelectorAll("a").forEach(function(a){a.addEventListener("click",close);});});})();';
}