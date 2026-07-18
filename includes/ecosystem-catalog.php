<?php
declare(strict_types=1);

if (!function_exists('bh_ecosystem_catalog')) {
    function bh_ecosystem_catalog(): array
    {
        return [
            'shop' => ['icon' => 'bag-shopping', 'short' => 'Shop', 'color' => '#673de6'],
            'booking' => ['icon' => 'calendar-check', 'short' => 'Booking', 'color' => '#0ea5e9'],
            'auction' => ['icon' => 'gavel', 'short' => 'Auction', 'color' => '#f59e0b'],
            'freelance' => ['icon' => 'briefcase', 'short' => 'Freelance', 'color' => '#8b5cf6'],
            'pizza' => ['icon' => 'pizza-slice', 'short' => 'Pizza', 'color' => '#ef4444'],
            'today' => ['icon' => 'newspaper', 'short' => 'Today', 'color' => '#06b6d4'],
            'gamehub' => ['icon' => 'gamepad', 'short' => 'GameHub', 'color' => '#10b981'],
            'tavle' => ['icon' => 'car', 'short' => 'Bilen CMS', 'color' => '#6366f1'],
            'faktura' => ['icon' => 'file-invoice-dollar', 'short' => 'Faktura', 'color' => '#14b8a6'],
            'lending' => ['icon' => 'store', 'short' => 'Business Landing', 'color' => '#ec4899'],
            // Note: Hosting CMS is SEO-only (not offered in installer / homepage grid).
            'news' => ['icon' => 'bullhorn', 'short' => 'News', 'color' => '#f97316'],
            'wordpress' => ['icon' => 'wordpress', 'short' => 'WordPress', 'color' => '#21759b', 'icon_brand' => true],
            '3d' => ['icon' => 'cube', 'short' => '3D', 'color' => '#64748b'],
            'ai' => ['icon' => 'robot', 'short' => 'AI for SEO', 'color' => '#a855f7'],
        ];
    }
}

/** Marketing copy for homepage “planets” grid — keyed like bh_ecosystem_catalog(). */
function bh_ecosystem_planet_blurbs(): array
{
    return [
        'shop' => ['planet' => 'Mercure', 'tagline_key' => 'eco_planet_shop'],
        'booking' => ['planet' => 'Calendria', 'tagline_key' => 'eco_planet_booking'],
        'auction' => ['planet' => 'Gavelion', 'tagline_key' => 'eco_planet_auction'],
        'freelance' => ['planet' => 'Talentis', 'tagline_key' => 'eco_planet_freelance'],
        'pizza' => ['planet' => 'Crustara', 'tagline_key' => 'eco_planet_pizza'],
        'today' => ['planet' => 'Chronicle', 'tagline_key' => 'eco_planet_today'],
        'gamehub' => ['planet' => 'Arcadia', 'tagline_key' => 'eco_planet_gamehub'],
        'tavle' => ['planet' => 'Motoria', 'tagline_key' => 'eco_planet_tavle'],
        'faktura' => ['planet' => 'Ledgera', 'tagline_key' => 'eco_planet_faktura'],
        'lending' => ['planet' => 'Ventura', 'tagline_key' => 'eco_planet_lending'],
        'news' => ['planet' => 'Heraldia', 'tagline_key' => 'eco_planet_news'],
        'wordpress' => ['planet' => 'Pressworld', 'tagline_key' => 'eco_planet_wordpress'],
        '3d' => ['planet' => 'Dimension', 'tagline_key' => 'eco_planet_3d'],
        'ai' => ['planet' => 'Synthia', 'tagline_key' => 'eco_planet_ai'],
    ];
}

function hs_app_demo_url(string $slug): string
{
    $map = [
        'shop' => 'https://bilohash.com/shop/site/',
        'booking' => 'https://bilohash.com/booking/site/',
        'auction' => 'https://bilohash.com/auction/site/',
        'freelance' => 'https://bilohash.com/freelance/site/',
        'pizza' => 'https://bilohash.com/pizza/site/',
        'today' => 'https://bilohash.com/today/',
        'gamehub' => 'https://bilohash.com/gamehub/site/',
        'tavle' => 'https://bilohash.com/tavle/site/',
        'faktura' => 'https://bilohash.com/faktura/',
        'lending' => 'https://bilohash.com/lending/',
        'hosting' => 'https://bilohash.com/hosting/',
        'news' => 'https://bilohash.com/news/',
        'wordpress' => 'https://bilohash.com/wordpress/',
        '3d' => 'https://bilohash.com/3d/',
        'ai' => 'https://bilohash.com/ai/',
    ];
    return $map[$slug] ?? ('https://bilohash.com/' . rawurlencode($slug) . '/');
}

/** Hero orbit timing — three rings. */
function hs_hero_eco_orbit_meta(): array
{
    return [
        ['dur' => 22, 'ring' => 'inner', 'phase' => 0],
        ['dur' => 28, 'ring' => 'mid', 'phase' => 30],
        ['dur' => 34, 'ring' => 'outer', 'phase' => 60],
        ['dur' => 24, 'ring' => 'inner', 'phase' => 72],
        ['dur' => 30, 'ring' => 'mid', 'phase' => 120],
        ['dur' => 36, 'ring' => 'outer', 'phase' => 150],
        ['dur' => 26, 'ring' => 'inner', 'phase' => 144],
        ['dur' => 32, 'ring' => 'mid', 'phase' => 210],
        ['dur' => 38, 'ring' => 'outer', 'phase' => 240],
        ['dur' => 23, 'ring' => 'inner', 'phase' => 216],
        ['dur' => 29, 'ring' => 'mid', 'phase' => 300],
        ['dur' => 35, 'ring' => 'outer', 'phase' => 330],
        ['dur' => 25, 'ring' => 'inner', 'phase' => 288],
    ];
}

/**
 * CMS planets for hero sun orbit.
 *
 * @return list<array{slug: string, icon: string, icon_brand: bool, tone: string, url: string, label: string, featured?: bool}>
 */
function hs_hero_eco_planets(array $t = []): array
{
    $catalog = bh_ecosystem_catalog();
    $spec = [
        ['slug' => 'auction', 'tone' => 'sky'],
        ['slug' => 'booking', 'tone' => 'sky', 'featured' => true],
        ['slug' => 'freelance', 'tone' => 'teal'],
        ['slug' => 'shop', 'tone' => 'rose', 'featured' => true],
        ['slug' => 'lending', 'tone' => 'emerald', 'featured' => true],
        ['slug' => 'pizza', 'tone' => 'rose'],
        ['slug' => 'tavle', 'tone' => 'red'],
        ['slug' => 'faktura', 'tone' => 'violet', 'featured' => true],
        ['slug' => 'gamehub', 'tone' => 'cyan'],
        ['slug' => 'today', 'tone' => 'violet'],
        ['slug' => 'ai', 'tone' => 'cyan'],
        ['slug' => 'wordpress', 'tone' => 'indigo'],
    ];
    $out = [];
    foreach ($spec as $row) {
        $slug = (string) $row['slug'];
        $app = $catalog[$slug] ?? null;
        if (!is_array($app)) {
            continue;
        }
        $out[] = [
            'slug' => $slug,
            'icon' => (string) ($app['icon'] ?? 'cube'),
            'icon_brand' => !empty($app['icon_brand']),
            'tone' => (string) $row['tone'],
            'url' => hs_app_demo_url($slug),
            'label' => (string) ($t['eco_hero_' . $slug] ?? $app['short'] ?? $slug),
            'featured' => !empty($row['featured']),
        ];
    }

    return $out;
}