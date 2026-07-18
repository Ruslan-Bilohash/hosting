<?php
declare(strict_types=1);

/**
 * Favicon + app icons for public / panel / admin layouts.
 */
function hs_render_favicon_links(): string
{
    $svg = hs_asset('favicon.svg');
    $ico = hs_asset('favicon.ico');
    $png32 = hs_asset('favicon-32x32.png');
    $png16 = hs_asset('favicon-16x16.png');
    $apple = hs_asset('apple-touch-icon.png');
    $manifest = hs_url('site.webmanifest');

    return ''
        . '<link rel="icon" href="' . hs_h($svg) . '" type="image/svg+xml">' . "\n"
        . '<link rel="icon" href="' . hs_h($png32) . '" type="image/png" sizes="32x32">' . "\n"
        . '<link rel="icon" href="' . hs_h($png16) . '" type="image/png" sizes="16x16">' . "\n"
        . '<link rel="shortcut icon" href="' . hs_h($ico) . '">' . "\n"
        . '<link rel="apple-touch-icon" href="' . hs_h($apple) . '" sizes="180x180">' . "\n"
        . '<link rel="manifest" href="' . hs_h($manifest) . '">' . "\n";
}

/** Inline SVG: sun + fiber optic strands with animated data pulses (Solaskinner brand). */
function hs_brand_sun_fiber_svg(string $idSuffix = ''): string
{
    $s = preg_replace('/[^a-zA-Z0-9_-]/', '', $idSuffix) ?? '';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" role="img" aria-hidden="true" class="hs-brand-sun-fiber-svg">'
        . '<circle class="hs-sun-core" cx="17" cy="24" r="7.5" fill="url(#hsSunCore' . $s . ')"/>'
        . '<g class="hs-sun-rays" stroke="url(#hsSunRay' . $s . ')" stroke-width="2" stroke-linecap="round">'
        . '<line x1="17" y1="10" x2="17" y2="13"/><line x1="17" y1="35" x2="17" y2="38"/>'
        . '<line x1="3" y1="24" x2="6" y2="24"/><line x1="28" y1="24" x2="31" y2="24"/>'
        . '<line x1="7.1" y1="14.1" x2="9.2" y2="16.2"/><line x1="24.8" y1="31.8" x2="26.9" y2="33.9"/>'
        . '<line x1="7.1" y1="33.9" x2="9.2" y2="31.8"/><line x1="24.8" y1="16.2" x2="26.9" y2="14.1"/>'
        . '</g>'
        . '<path class="hs-cable-base" d="M26 20.5C30 19 34 18 38 17.5" stroke="url(#hsFiberA' . $s . ')" stroke-width="2.2" stroke-linecap="round"/>'
        . '<path class="hs-cable-base" d="M26 24C31 24 36 24 40 24" stroke="url(#hsFiberB' . $s . ')" stroke-width="2.4" stroke-linecap="round"/>'
        . '<path class="hs-cable-base" d="M26 27.5C30 28.5 34 29.5 38 30.5" stroke="url(#hsFiberC' . $s . ')" stroke-width="2.2" stroke-linecap="round"/>'
        . '<path class="hs-cable-volt hs-cable-volt--a" d="M26 20.5C30 19 34 18 38 17.5" stroke="#67e8f9" stroke-width="1.4" stroke-linecap="round" pathLength="100"/>'
        . '<path class="hs-cable-volt hs-cable-volt--b" d="M26 24C31 24 36 24 40 24" stroke="#6ee7b7" stroke-width="1.6" stroke-linecap="round" pathLength="100"/>'
        . '<path class="hs-cable-volt hs-cable-volt--c" d="M26 27.5C30 28.5 34 29.5 38 30.5" stroke="#93c5fd" stroke-width="1.4" stroke-linecap="round" pathLength="100"/>'
        . '<circle class="hs-cable-node hs-cable-node--a" cx="38" cy="17.5" r="2.2" fill="#22d3ee"/>'
        . '<circle class="hs-cable-node hs-cable-node--b" cx="40" cy="24" r="2.4" fill="#34d399"/>'
        . '<circle class="hs-cable-node hs-cable-node--c" cx="38" cy="30.5" r="2.2" fill="#60a5fa"/>'
        . '<defs>'
        . '<radialGradient id="hsSunCore' . $s . '" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(14 20) rotate(45) scale(12)">'
        . '<stop stop-color="#fde68a"/><stop offset="1" stop-color="#f59e0b"/>'
        . '</radialGradient>'
        . '<linearGradient id="hsSunRay' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fcd34d"/><stop offset="1" stop-color="#f97316"/></linearGradient>'
        . '<linearGradient id="hsFiberA' . $s . '" x1="26" y1="20" x2="40" y2="17"><stop stop-color="#fbbf24"/><stop offset="1" stop-color="#22d3ee"/></linearGradient>'
        . '<linearGradient id="hsFiberB' . $s . '" x1="26" y1="24" x2="42" y2="24"><stop stop-color="#f59e0b"/><stop offset="1" stop-color="#34d399"/></linearGradient>'
        . '<linearGradient id="hsFiberC' . $s . '" x1="26" y1="28" x2="40" y2="31"><stop stop-color="#fbbf24"/><stop offset="1" stop-color="#60a5fa"/></linearGradient>'
        . '</defs></svg>';
}

/** Hero background: horizontal fiber cables with traveling voltage pulses. */
function hs_brand_hero_cable_mesh_svg(): string
{
    return <<<'SVG'
<svg class="hs-hero-cable-mesh-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 140" fill="none" preserveAspectRatio="none" aria-hidden="true">
  <path class="hs-mesh-cable" d="M0 28 H800" stroke="url(#hsMeshG1)" stroke-width="2" pathLength="100"/>
  <path class="hs-mesh-cable" d="M0 52 H800" stroke="url(#hsMeshG2)" stroke-width="2.2" pathLength="100"/>
  <path class="hs-mesh-cable" d="M0 76 H800" stroke="url(#hsMeshG3)" stroke-width="2" pathLength="100"/>
  <path class="hs-mesh-cable" d="M0 100 H800" stroke="url(#hsMeshG4)" stroke-width="1.8" pathLength="100"/>
  <path class="hs-mesh-volt hs-mesh-volt--1" d="M0 28 H800" stroke="#22d3ee" stroke-width="1.2" pathLength="100"/>
  <path class="hs-mesh-volt hs-mesh-volt--2" d="M0 52 H800" stroke="#34d399" stroke-width="1.3" pathLength="100"/>
  <path class="hs-mesh-volt hs-mesh-volt--3" d="M0 76 H800" stroke="#60a5fa" stroke-width="1.2" pathLength="100"/>
  <path class="hs-mesh-volt hs-mesh-volt--4" d="M0 100 H800" stroke="#a78bfa" stroke-width="1.1" pathLength="100"/>
  <defs>
    <linearGradient id="hsMeshG1" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#0ea5e9" stop-opacity=".15"/><stop offset=".5" stop-color="#0ea5e9" stop-opacity=".35"/><stop offset="1" stop-color="#22d3ee" stop-opacity=".2"/></linearGradient>
    <linearGradient id="hsMeshG2" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#059669" stop-opacity=".12"/><stop offset=".5" stop-color="#34d399" stop-opacity=".3"/><stop offset="1" stop-color="#6ee7b7" stop-opacity=".18"/></linearGradient>
    <linearGradient id="hsMeshG3" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#2563eb" stop-opacity=".12"/><stop offset=".5" stop-color="#60a5fa" stop-opacity=".28"/><stop offset="1" stop-color="#93c5fd" stop-opacity=".16"/></linearGradient>
    <linearGradient id="hsMeshG4" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#7c3aed" stop-opacity=".1"/><stop offset=".5" stop-color="#a78bfa" stop-opacity=".25"/><stop offset="1" stop-color="#c4b5fd" stop-opacity=".14"/></linearGradient>
  </defs>
</svg>
SVG;
}

function hs_brand_logo_mark(string $extraClass = ''): string
{
    $cls = trim('hs-brand-mark ' . $extraClass);

    return '<span class="' . hs_h($cls) . '">' . hs_brand_sun_fiber_svg() . '</span>';
}

/** Flying satellite with downlink beam (hero decoration). */
function hs_brand_hero_satellite_svg(): string
{
    return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80" fill="none" class="hs-satellite-svg" aria-hidden="true">
  <path class="hs-sat-beam" d="M58 52 L58 78" stroke="url(#hsSatBeam)" stroke-width="2" stroke-dasharray="5 7" pathLength="100"/>
  <path class="hs-sat-beam-pulse" d="M58 52 L58 78" stroke="#22d3ee" stroke-width="1.2" stroke-linecap="round" pathLength="100"/>
  <g class="hs-sat-body">
    <rect x="44" y="30" width="28" height="14" rx="3" fill="url(#hsSatBody)"/>
    <rect x="38" y="34" width="8" height="6" rx="1" fill="#1e3a5f"/>
    <rect x="74" y="34" width="8" height="6" rx="1" fill="#1e3a5f"/>
    <path d="M36 37 H30 V40 H36 Z M84 37 H90 V40 H84 Z" fill="#38bdf8"/>
    <circle cx="58" cy="37" r="2.5" fill="#67e8f9"/>
    <path d="M54 44 L58 50 L62 44" stroke="#94a3b8" stroke-width="1.2" stroke-linecap="round"/>
  </g>
  <circle class="hs-sat-signal hs-sat-signal--1" cx="58" cy="62" r="1.8" fill="#34d399"/>
  <circle class="hs-sat-signal hs-sat-signal--2" cx="58" cy="68" r="1.4" fill="#60a5fa"/>
  <circle class="hs-sat-signal hs-sat-signal--3" cx="58" cy="74" r="1.1" fill="#a78bfa"/>
  <defs>
    <linearGradient id="hsSatBody" x1="44" y1="30" x2="72" y2="44"><stop stop-color="#e2e8f0"/><stop offset="1" stop-color="#94a3b8"/></linearGradient>
    <linearGradient id="hsSatBeam" x1="58" y1="52" x2="58" y2="78"><stop stop-color="#22d3ee" stop-opacity=".1"/><stop offset=".5" stop-color="#34d399" stop-opacity=".55"/><stop offset="1" stop-color="#059669" stop-opacity=".85"/></linearGradient>
  </defs>
</svg>
SVG;
}

/** Hero nucleus: animated sun + fiber hub + server rack (Solaskinner brand). */
function hs_brand_hero_nucleus_svg(string $idSuffix = ''): string
{
    $s = preg_replace('/[^a-zA-Z0-9_-]/', '', $idSuffix) ?? '';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none" role="img" aria-hidden="true" class="hs-nucleus-svg">'
        . '<g class="hs-nucleus-corona-spin">'
        . '<circle cx="64" cy="64" r="56" stroke="url(#hsCorona' . $s . ')" stroke-width=".8" stroke-dasharray="2 14" opacity=".35"/>'
        . '<circle cx="64" cy="64" r="52" stroke="url(#hsCorona' . $s . ')" stroke-width="1.2" stroke-dasharray="4 10" opacity=".55"/>'
        . '<g class="hs-nucleus-rays" stroke="url(#hsRay' . $s . ')" stroke-width="2.2" stroke-linecap="round">'
        . '<line x1="64" y1="4" x2="64" y2="22"/><line x1="64" y1="106" x2="64" y2="124"/>'
        . '<line x1="4" y1="64" x2="22" y2="64"/><line x1="106" y1="64" x2="124" y2="64"/>'
        . '<line x1="20" y1="20" x2="32" y2="32"/><line x1="96" y1="96" x2="108" y2="108"/>'
        . '<line x1="20" y1="108" x2="32" y2="96"/><line x1="96" y1="32" x2="108" y2="20"/>'
        . '<line x1="64" y1="14" x2="64" y2="26"/><line x1="90" y1="38" x2="80" y2="48"/>'
        . '<line x1="38" y1="90" x2="48" y2="80"/><line x1="90" y1="90" x2="80" y2="80"/>'
        . '<line x1="46" y1="18" x2="52" y2="28"/><line x1="82" y1="18" x2="76" y2="28"/>'
        . '<line x1="46" y1="110" x2="52" y2="100"/><line x1="82" y1="110" x2="76" y2="100"/>'
        . '</g>'
        . '<g class="hs-nucleus-rays hs-nucleus-rays--soft" stroke="url(#hsRaySoft' . $s . ')" stroke-width="1.4" stroke-linecap="round" opacity=".65">'
        . '<line x1="64" y1="10" x2="64" y2="18"/><line x1="64" y1="110" x2="64" y2="118"/>'
        . '<line x1="10" y1="64" x2="18" y2="64"/><line x1="110" y1="64" x2="118" y2="64"/>'
        . '<line x1="28" y1="28" x2="34" y2="34"/><line x1="94" y1="94" x2="100" y2="100"/>'
        . '<line x1="28" y1="100" x2="34" y2="94"/><line x1="94" y1="34" x2="100" y2="28"/>'
        . '</g></g>'
        . '<circle class="hs-nucleus-flare" cx="64" cy="64" r="34" fill="url(#hsFlare' . $s . ')" opacity=".45"/>'
        . '<circle class="hs-nucleus-core" cx="64" cy="64" r="22" fill="url(#hsCore' . $s . ')"/>'
        . '<circle class="hs-nucleus-core-shine" cx="58" cy="58" r="8" fill="rgba(255,255,255,.35)"/>'
        . '<g class="hs-nucleus-server">'
        . '<rect x="82" y="48" width="30" height="40" rx="4" fill="url(#hsRack' . $s . ')" stroke="#475569" stroke-width="1"/>'
        . '<rect x="86" y="54" width="22" height="7" rx="1.5" fill="#0f172a" opacity=".85"/>'
        . '<rect x="86" y="65" width="22" height="7" rx="1.5" fill="#0f172a" opacity=".85"/>'
        . '<rect x="86" y="76" width="22" height="7" rx="1.5" fill="#0f172a" opacity=".85"/>'
        . '<circle class="hs-nucleus-led hs-nucleus-led--1" cx="104" cy="57.5" r="1.6" fill="#34d399"/>'
        . '<circle class="hs-nucleus-led hs-nucleus-led--2" cx="104" cy="68.5" r="1.6" fill="#22d3ee"/>'
        . '<circle class="hs-nucleus-led hs-nucleus-led--3" cx="104" cy="79.5" r="1.6" fill="#fbbf24"/>'
        . '</g>'
        . '<path class="hs-nucleus-fiber" d="M78 58 C84 54 90 52 96 52" stroke="url(#hsFibA' . $s . ')" stroke-width="2" stroke-linecap="round"/>'
        . '<path class="hs-nucleus-fiber" d="M80 64 C88 64 94 64 100 64" stroke="url(#hsFibB' . $s . ')" stroke-width="2.2" stroke-linecap="round"/>'
        . '<path class="hs-nucleus-fiber" d="M78 70 C84 74 90 76 96 78" stroke="url(#hsFibC' . $s . ')" stroke-width="2" stroke-linecap="round"/>'
        . '<path class="hs-nucleus-volt hs-nucleus-volt--a" d="M78 58 C84 54 90 52 96 52" stroke="#22d3ee" stroke-width="1.4" stroke-linecap="round" pathLength="100"/>'
        . '<path class="hs-nucleus-volt hs-nucleus-volt--b" d="M80 64 C88 64 94 64 100 64" stroke="#34d399" stroke-width="1.6" stroke-linecap="round" pathLength="100"/>'
        . '<path class="hs-nucleus-volt hs-nucleus-volt--c" d="M78 70 C84 74 90 76 96 78" stroke="#fbbf24" stroke-width="1.4" stroke-linecap="round" pathLength="100"/>'
        . '<defs>'
        . '<radialGradient id="hsCore' . $s . '" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(56 56) rotate(45) scale(28)">'
        . '<stop stop-color="#fff7c2"/><stop offset=".45" stop-color="#fde047"/><stop offset="1" stop-color="#f97316"/></radialGradient>'
        . '<radialGradient id="hsFlare' . $s . '" cx=".5" cy=".5" r=".5"><stop stop-color="#fbbf24" stop-opacity=".5"/><stop offset="1" stop-color="#f97316" stop-opacity="0"/></radialGradient>'
        . '<linearGradient id="hsRay' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fde68a"/><stop offset="1" stop-color="#ea580c"/></linearGradient>'
        . '<linearGradient id="hsRaySoft' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fef3c7"/><stop offset="1" stop-color="#fb923c"/></linearGradient>'
        . '<linearGradient id="hsCorona' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fbbf24"/><stop offset="1" stop-color="#22d3ee"/></linearGradient>'
        . '<linearGradient id="hsRack' . $s . '" x1="82" y1="48" x2="112" y2="88"><stop stop-color="#e2e8f0"/><stop offset="1" stop-color="#94a3b8"/></linearGradient>'
        . '<linearGradient id="hsFibA' . $s . '" x1="78" y1="58" x2="96" y2="52"><stop stop-color="#fbbf24"/><stop offset="1" stop-color="#22d3ee"/></linearGradient>'
        . '<linearGradient id="hsFibB' . $s . '" x1="80" y1="64" x2="100" y2="64"><stop stop-color="#f59e0b"/><stop offset="1" stop-color="#34d399"/></linearGradient>'
        . '<linearGradient id="hsFibC' . $s . '" x1="78" y1="70" x2="96" y2="78"><stop stop-color="#fbbf24"/><stop offset="1" stop-color="#60a5fa"/></linearGradient>'
        . '</defs></svg>';
}

/** Server status strip for hero (business hosting look). */
function hs_brand_hero_server_strip(array $t = []): string
{
    $label = $t['hero_server_strip'] ?? 'EU · Norway SSD · 99.9% uptime · 24/7 support';

    return '<div class="hs-hero-server-strip" aria-hidden="true">'
        . '<span class="hs-srv-led hs-srv-led--ok"></span>'
        . '<span class="hs-srv-led hs-srv-led--ok"></span>'
        . '<span class="hs-srv-led hs-srv-led--act"></span>'
        . '<span class="hs-srv-led hs-srv-led--net"></span>'
        . '<span class="hs-hero-server-strip-text">' . hs_h($label) . '</span>'
        . '</div>';
}

/** @param array<string, string> $t */
function hs_brand_hero_sun(array $t = [], string $lang = 'en'): string
{
    require_once __DIR__ . '/ecosystem-catalog.php';
    $aria = $t['hero_eco_hub_aria'] ?? ($t['ecosystem_apps_label'] ?? 'CMS ecosystem');
    $planets = hs_hero_eco_planets($t);
    $orbits = hs_hero_eco_orbit_meta();
    $html = '<div class="hs-hero-sun-fiber hs-hero-sun-fiber--hero hs-hero-eco-hub-wrap" role="group" aria-label="' . hs_h($aria) . '">'
        . '<div class="hs-eco-hub">'
        . '<div class="hs-eco-rings" aria-hidden="true">'
        . '<div class="hs-eco-ring hs-eco-ring--outer"></div>'
        . '<div class="hs-eco-ring hs-eco-ring--mid"></div>'
        . '<div class="hs-eco-ring hs-eco-ring--inner"></div>'
        . '</div>';
    foreach ($planets as $i => $planet) {
        $om = $orbits[$i] ?? $orbits[0];
        $iconClass = !empty($planet['icon_brand']) ? 'fa-brands' : 'fa-solid';
        $featured = !empty($planet['featured']) ? ' hs-eco-planet--featured' : '';
        $pulseDelay = round($i * 0.42, 2);
        $tugDelay = round($i * 0.38, 2);
        $html .= '<div class="hs-eco-orbit hs-eco-orbit--' . hs_h((string) $om['ring']) . '"'
            . ' style="--eco-dur:' . (int) $om['dur'] . 's;--eco-phase:' . (int) $om['phase'] . 'deg;'
            . '--pulse-delay:' . $pulseDelay . 's;--tug-delay:' . $tugDelay . 's;">'
            . '<svg class="hs-eco-fiber-spoke" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">'
            . '<line class="hs-eco-fiber-base" x1="50" y1="50" x2="50" y2="7"/>'
            . '<line class="hs-eco-fiber-pulse hs-eco-fiber-pulse--out" x1="50" y1="50" x2="50" y2="7" pathLength="100"/>'
            . '<line class="hs-eco-fiber-pulse hs-eco-fiber-pulse--in" x1="50" y1="7" x2="50" y2="50" pathLength="100"/>'
            . '</svg>'
            . '<a href="' . hs_h((string) $planet['url']) . '" class="hs-eco-planet hs-eco-planet--'
            . hs_h((string) $planet['tone']) . $featured . '" target="_blank" rel="noopener"'
            . ' title="' . hs_h((string) $planet['label']) . '">'
            . '<span class="hs-eco-planet-inner">'
            . '<i class="' . hs_h($iconClass) . ' fa-' . hs_h((string) $planet['icon']) . '" aria-hidden="true"></i>'
            . '<span>' . hs_h((string) $planet['label']) . '</span>'
            . '</span></a></div>';
    }
    $html .= '<div class="hs-eco-hub-core" role="img" aria-label="' . hs_h($t['hero_sun_aria'] ?? 'Solaskinner — sun and fiber hosting') . '">'
        . '<div class="hs-eco-nucleus-wrap">' . hs_brand_hero_nucleus_svg('Hub') . '</div>'
        . '</div></div></div>';

    return $html;
}

/** @param array<string, string> $t */
function hs_brand_hero_deco(array $t = []): string
{
    return '<div class="hs-hero-cable-mesh" aria-hidden="true">' . hs_brand_hero_cable_mesh_svg() . '</div>';
}

/** Wrap a homepage screenshot in a click-to-zoom trigger. */
function hs_brand_shot_zoom_wrap(string $innerHtml, string $ariaLabel): string
{
    return '<button type="button" class="hs-shot-zoom-trigger" data-hs-shot-zoom'
        . ' aria-label="' . hs_h($ariaLabel) . '">'
        . $innerHtml
        . '<span class="hs-shot-zoom-hint" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>'
        . '</button>';
}

/** @param array<string, string> $t */
function hs_brand_hero_visual(array $t = [], string $lang = 'en'): string
{
    $alt = $t['hero_visual_alt'] ?? 'Hosting panel — website speed test';
    $zoomLabel = $t['shot_zoom_label'] ?? ($alt . ' — ' . ($t['shot_zoom_action'] ?? 'enlarge'));

    $webp = hs_asset('speedtest.webp');
    $jpg = hs_asset('speedtest.jpg');

    $frame = '<div class="hs-hero-visual-frame">'
        . '<div class="hs-hero-visual-glow" aria-hidden="true"></div>'
        . '<picture>'
        . '<source srcset="' . hs_h($webp) . '" type="image/webp">'
        . '<img src="' . hs_h($jpg) . '" alt="' . hs_h($alt) . '" width="1280" height="604" loading="eager" fetchpriority="high" decoding="async" class="hs-hero-speedshot">'
        . '</picture></div>';

    return '<div class="hs-hero-visual-stack">'
        . hs_brand_shot_zoom_wrap($frame, $zoomLabel)
        . '</div>';
}

/** Fiery animated sun for /domain search panel. */
function hs_brand_domain_sun_svg(string $idSuffix = ''): string
{
    $s = preg_replace('/[^a-zA-Z0-9_-]/', '', $idSuffix) ?? '';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none" role="img" aria-hidden="true" class="hs-domain-sun-svg">'
        . '<g class="hs-domain-sun-heatfield">'
        . '<circle cx="64" cy="64" r="58" fill="url(#hsDomHeat' . $s . ')" opacity=".28"/>'
        . '<circle cx="64" cy="64" r="48" fill="url(#hsDomHeatInner' . $s . ')" opacity=".35"/>'
        . '</g>'
        . '<g class="hs-domain-sun-corona">'
        . '<circle cx="64" cy="64" r="56" stroke="url(#hsDomCorona' . $s . ')" stroke-width="1.1" stroke-dasharray="2 9" opacity=".55"/>'
        . '<g class="hs-domain-sun-rays" stroke="url(#hsDomRay' . $s . ')" stroke-width="2.8" stroke-linecap="round">'
        . '<line x1="64" y1="2" x2="64" y2="22"/><line x1="64" y1="106" x2="64" y2="126"/>'
        . '<line x1="2" y1="64" x2="22" y2="64"/><line x1="106" y1="64" x2="126" y2="64"/>'
        . '<line x1="18" y1="18" x2="32" y2="32"/><line x1="96" y1="96" x2="110" y2="110"/>'
        . '<line x1="18" y1="110" x2="32" y2="96"/><line x1="96" y1="32" x2="110" y2="18"/>'
        . '<line x1="64" y1="10" x2="64" y2="30"/><line x1="92" y1="36" x2="80" y2="48"/>'
        . '<line x1="36" y1="92" x2="48" y2="80"/><line x1="92" y1="92" x2="80" y2="80"/>'
        . '<line x1="36" y1="36" x2="48" y2="48"/>'
        . '</g>'
        . '<g class="hs-domain-sun-rays hs-domain-sun-rays--blaze" stroke="url(#hsDomRayBlaze' . $s . ')" stroke-width="1.8" stroke-linecap="round" opacity=".85">'
        . '<line x1="64" y1="8" x2="64" y2="18"/><line x1="64" y1="110" x2="64" y2="120"/>'
        . '<line x1="8" y1="64" x2="18" y2="64"/><line x1="110" y1="64" x2="120" y2="64"/>'
        . '<line x1="28" y1="28" x2="36" y2="36"/><line x1="92" y1="92" x2="100" y2="100"/>'
        . '<line x1="28" y1="100" x2="36" y2="92"/><line x1="92" y1="36" x2="100" y2="28"/>'
        . '</g></g>'
        . '<g class="hs-domain-sun-flames" fill="url(#hsDomFlame' . $s . ')">'
        . '<path class="hs-domain-sun-flame hs-domain-sun-flame--a" d="M64 38 C58 48 52 52 50 60 C48 68 54 74 64 78 C74 74 80 68 78 60 C76 52 70 48 64 38Z" opacity=".72"/>'
        . '<path class="hs-domain-sun-flame hs-domain-sun-flame--b" d="M38 64 C48 58 52 52 60 50 C68 48 74 54 78 64 C74 74 68 80 60 78 C52 76 48 70 38 64Z" opacity=".58"/>'
        . '<path class="hs-domain-sun-flame hs-domain-sun-flame--c" d="M64 90 C70 80 76 76 78 68 C80 60 74 54 64 50 C54 54 48 60 50 68 C52 76 58 80 64 90Z" opacity=".62"/>'
        . '<path class="hs-domain-sun-flame hs-domain-sun-flame--d" d="M90 64 C80 70 76 76 68 78 C60 80 54 74 50 64 C54 54 60 48 68 50 C76 52 80 58 90 64Z" opacity=".55"/>'
        . '</g>'
        . '<circle class="hs-domain-sun-flare" cx="64" cy="64" r="40" fill="url(#hsDomFlare' . $s . ')" opacity=".55"/>'
        . '<circle class="hs-domain-sun-flare hs-domain-sun-flare--hot" cx="64" cy="64" r="30" fill="url(#hsDomFlareHot' . $s . ')" opacity=".65"/>'
        . '<circle class="hs-domain-sun-core" cx="64" cy="64" r="20" fill="url(#hsDomCore' . $s . ')"/>'
        . '<circle class="hs-domain-sun-core hs-domain-sun-core--white" cx="64" cy="64" r="11" fill="url(#hsDomCoreWhite' . $s . ')" opacity=".92"/>'
        . '<ellipse class="hs-domain-sun-shine" cx="58" cy="56" rx="5" ry="4" fill="rgba(255,255,255,.55)"/>'
        . '<defs>'
        . '<radialGradient id="hsDomCoreWhite' . $s . '" cx=".42" cy=".38" r=".65"><stop stop-color="#fff"/><stop offset=".55" stop-color="#fff7c2"/><stop offset="1" stop-color="#fde047" stop-opacity="0"/></radialGradient>'
        . '<radialGradient id="hsDomCore' . $s . '" cx=".45" cy=".4" r=".6" gradientUnits="objectBoundingBox">'
        . '<stop stop-color="#fffef0"/><stop offset=".25" stop-color="#fef08a"/><stop offset=".55" stop-color="#fb923c"/><stop offset=".82" stop-color="#ea580c"/><stop offset="1" stop-color="#b91c1c"/></radialGradient>'
        . '<radialGradient id="hsDomFlareHot' . $s . '" cx=".5" cy=".5" r=".5"><stop stop-color="#fbbf24" stop-opacity=".75"/><stop offset=".45" stop-color="#f97316" stop-opacity=".45"/><stop offset="1" stop-color="#dc2626" stop-opacity="0"/></radialGradient>'
        . '<radialGradient id="hsDomFlare' . $s . '" cx=".5" cy=".5" r=".5"><stop stop-color="#fde047" stop-opacity=".55"/><stop offset=".5" stop-color="#f97316" stop-opacity=".35"/><stop offset="1" stop-color="#7f1d1d" stop-opacity="0"/></radialGradient>'
        . '<radialGradient id="hsDomHeat' . $s . '" cx=".5" cy=".5" r=".5"><stop stop-color="#ef4444" stop-opacity=".35"/><stop offset=".55" stop-color="#f97316" stop-opacity=".15"/><stop offset="1" stop-color="#fbbf24" stop-opacity="0"/></radialGradient>'
        . '<radialGradient id="hsDomHeatInner' . $s . '" cx=".5" cy=".5" r=".5"><stop stop-color="#f97316" stop-opacity=".4"/><stop offset="1" stop-color="#dc2626" stop-opacity="0"/></radialGradient>'
        . '<linearGradient id="hsDomRay' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fef08a"/><stop offset=".5" stop-color="#f97316"/><stop offset="1" stop-color="#dc2626"/></linearGradient>'
        . '<linearGradient id="hsDomRayBlaze' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fff7c2"/><stop offset="1" stop-color="#ea580c"/></linearGradient>'
        . '<linearGradient id="hsDomFlame' . $s . '" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#fde047"/><stop offset=".45" stop-color="#f97316"/><stop offset="1" stop-color="#b91c1c" stop-opacity=".2"/></linearGradient>'
        . '<linearGradient id="hsDomCorona' . $s . '" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fde047"/><stop offset="1" stop-color="#dc2626"/></linearGradient>'
        . '</defs></svg>';
}

/** Animated sun decoration for /domain search panel (top-right). */
function hs_brand_domain_search_sun(): string
{
    return '<div class="hs-domain-search-sun" aria-hidden="true">'
        . '<div class="hs-domain-search-sun-heat"></div>'
        . '<div class="hs-domain-search-sun-glow"></div>'
        . '<div class="hs-domain-search-sun-glow hs-domain-search-sun-glow--fire"></div>'
        . '<div class="hs-domain-search-sun-inner">'
        . hs_brand_domain_sun_svg('DomainSearch')
        . '</div></div>';
}

/** @param array<string, string> $t */
function hs_brand_landing_builder_screenshot(array $t = []): string
{
    $alt = $t['landing_shot_alt'] ?? ($t['landing_title'] ?? 'Landing page builder');

    $jpg = hs_asset('page_builder.jpg');
    if (!is_readable(__DIR__ . '/../assets/page_builder.jpg')) {
        $jpg = rtrim((string) (defined('HS_CANONICAL_URL') ? HS_CANONICAL_URL : ''), '/') . '/page_builder.jpg';
    }

    $zoomLabel = $t['shot_zoom_label'] ?? ($alt . ' — ' . ($t['shot_zoom_action'] ?? 'enlarge'));
    $frame = '<div class="hs-landing-preview-frame">'
        . '<img src="' . hs_h($jpg) . '" alt="' . hs_h($alt) . '" width="1280" height="720" loading="lazy" decoding="async" class="hs-landing-preview-img">'
        . '</div>';

    return '<figure class="hs-landing-preview hs-landing-preview--shot">'
        . hs_brand_shot_zoom_wrap($frame, $zoomLabel)
        . '</figure>';
}

/** @param array<string, string> $t */
function hs_brand_panel_screenshot(array $t = []): string
{
    $alt = $t['panel_shot_alt'] ?? ($t['panel_title'] ?? 'Hosting control panel');

    $webp = hs_asset('panel-dashboard.webp');
    $jpg = hs_asset('panel-dashboard.jpg');

    $zoomLabel = $t['shot_zoom_label'] ?? ($alt . ' — ' . ($t['shot_zoom_action'] ?? 'enlarge'));
    $frame = '<div class="hs-panel-shot-frame">'
        . '<picture>'
        . '<source srcset="' . hs_h($webp) . '" type="image/webp">'
        . '<img src="' . hs_h($jpg) . '" alt="' . hs_h($alt) . '" width="1280" height="720" loading="lazy" decoding="async" class="hs-panel-shot-img">'
        . '</picture></div>';

    return '<figure class="hs-panel-shot">'
        . hs_brand_shot_zoom_wrap($frame, $zoomLabel)
        . '</figure>';
}