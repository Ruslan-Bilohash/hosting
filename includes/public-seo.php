<?php
declare(strict_types=1);

/** @return array<string, string> */
function hs_seo_og_locales(): array
{
    return [
        'en' => 'en_GB',
        'uk' => 'uk_UA',
        'no' => 'nb_NO',
        'lt' => 'lt_LT',
        'pl' => 'pl_PL',
        'sv' => 'sv_SE',
    ];
}

function hs_seo_default_image(): string
{
    return rtrim(HS_CANONICAL_URL, '/') . '/assets/speedtest.jpg';
}

/** @param array<string, mixed> $seo */
function hs_seo_resolve(array $seo, array $t, string $lang): array
{
    $type = (string) ($seo['type'] ?? 'page');
    $path = trim((string) ($seo['path'] ?? ''), '/');
    $title = trim((string) ($seo['title'] ?? ''));
    if ($title === '') {
        $title = match ($type) {
            'domain' => (string) ($t['domain_page_meta_title'] ?? $t['meta_title'] ?? HS_SITE_NAME),
            'domains_catalog' => (string) ($t['domain_catalog_page_title'] ?? $t['meta_title'] ?? HS_SITE_NAME),
            default => (string) ($t['meta_title'] ?? HS_SITE_NAME),
        };
    }
    $description = trim((string) ($seo['description'] ?? ''));
    if ($description === '') {
        $description = match ($type) {
            'domain' => (string) ($t['domain_page_meta_description'] ?? $t['meta_description'] ?? ''),
            'domains_catalog' => (string) ($t['domain_catalog_meta_description'] ?? $t['domain_catalog_lead'] ?? $t['meta_description'] ?? ''),
            default => (string) ($t['meta_description'] ?? ''),
        };
    }
    $canonical = (string) ($seo['canonical'] ?? hs_canonical_url($path));
    $image = (string) ($seo['image'] ?? hs_seo_default_image());
    $ogType = (string) ($seo['og_type'] ?? ($type === 'home' ? 'website' : 'article'));

    $keywords = trim((string) ($seo['keywords'] ?? ($t['meta_keywords'] ?? '')));

    return [
        'type' => $type,
        'path' => $path,
        'title' => $title,
        'description' => $description,
        'keywords' => $keywords,
        'canonical' => $canonical,
        'image' => $image,
        'og_type' => $ogType,
        'lang' => $lang,
        'noindex' => hs_prelaunch_mode() || !empty($seo['noindex']) || in_array($type, ['login', 'checkout', '404'], true),
    ];
}

/** @param array<string, mixed> $resolved */
function hs_render_public_seo_head(array $resolved): string
{
    $title = (string) $resolved['title'];
    $description = (string) $resolved['description'];
    $canonical = (string) $resolved['canonical'];
    $image = (string) $resolved['image'];
    $ogType = (string) $resolved['og_type'];
    $lang = (string) ($resolved['lang'] ?? 'en');
    $locales = hs_seo_og_locales();
    $ogLocale = $locales[$lang] ?? 'en_GB';
    $siteName = trim((string) ($GLOBALS['t']['brand'] ?? ''));
    if ($siteName === '') {
        $siteName = defined('HS_SITE_NAME') ? HS_SITE_NAME : 'Solaskinner';
    }

    $html = '';
    // Google Search Console ownership verification
    $html .= '<meta name="google-site-verification" content="ab5W0CNsaeYNN_WgbF2nczolg6mimKG3VPZ8C36AeDU">' . "\n";
    if ($description !== '') {
        $html .= '<meta name="description" content="' . hs_h($description) . '">' . "\n";
    }
    $keywords = trim((string) ($resolved['keywords'] ?? ($GLOBALS['t']['meta_keywords'] ?? '')));
    if ($keywords !== '') {
        $html .= '<meta name="keywords" content="' . hs_h($keywords) . '">' . "\n";
    }
    $html .= '<meta name="geo.region" content="EU">' . "\n";
    $html .= '<meta name="geo.placename" content="Norway, Germany, Lithuania, European Union">' . "\n";
    $html .= '<meta name="author" content="' . hs_h($siteName) . '">' . "\n";
    if (empty($resolved['noindex'])) {
        $html .= '<meta name="googlebot" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">' . "\n";
        $html .= '<meta name="bingbot" content="index,follow">' . "\n";
    }
    // Open Graph image dimensions help share cards
    $html .= '<meta property="og:image:alt" content="' . hs_h($siteName . ' — hosting #1 in Europe') . '">' . "\n";
    $html .= '<link rel="canonical" href="' . hs_h($canonical) . '">' . "\n";

    foreach (hs_langs() as $code => $meta) {
        $path = (string) ($resolved['path'] ?? '');
        $altPath = $path === '' ? '' : $path;
        $altUrl = hs_canonical_url($altPath, ['lang' => $code]);
        $html .= '<link rel="alternate" hreflang="' . hs_h((string) ($meta['html'] ?? $code)) . '" href="' . hs_h($altUrl) . '">' . "\n";
    }
    $html .= '<link rel="alternate" hreflang="x-default" href="' . hs_h(hs_canonical_url((string) ($resolved['path'] ?? ''))) . '">' . "\n";

    $html .= '<meta property="og:type" content="' . hs_h($ogType) . '">' . "\n";
    $html .= '<meta property="og:site_name" content="' . hs_h($siteName) . '">' . "\n";
    $html .= '<meta property="og:title" content="' . hs_h($title) . '">' . "\n";
    if ($description !== '') {
        $html .= '<meta property="og:description" content="' . hs_h($description) . '">' . "\n";
    }
    $html .= '<meta property="og:url" content="' . hs_h($canonical) . '">' . "\n";
    $html .= '<meta property="og:image" content="' . hs_h($image) . '">' . "\n";
    $html .= '<meta property="og:image:width" content="1200">' . "\n";
    $html .= '<meta property="og:image:height" content="630">' . "\n";
    $html .= '<meta property="og:locale" content="' . hs_h($ogLocale) . '">' . "\n";
    foreach ($locales as $code => $locale) {
        if ($code === $lang) {
            continue;
        }
        $html .= '<meta property="og:locale:alternate" content="' . hs_h($locale) . '">' . "\n";
    }

    $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '<meta name="twitter:title" content="' . hs_h($title) . '">' . "\n";
    if ($description !== '') {
        $html .= '<meta name="twitter:description" content="' . hs_h($description) . '">' . "\n";
    }
    $html .= '<meta name="twitter:image" content="' . hs_h($image) . '">' . "\n";
    $html .= '<meta name="twitter:image:alt" content="' . hs_h($siteName . ' hosting') . '">' . "\n";
    // Ranking helpers
    $html .= '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . hs_h(rtrim(HS_CANONICAL_URL, '/') . '/sitemap.php') . '">' . "\n";
    $html .= '<meta name="format-detection" content="telephone=no">' . "\n";
    if (($resolved['type'] ?? '') === 'home') {
        $html .= '<meta name="rating" content="general">' . "\n";
        $html .= '<meta name="coverage" content="Europe">' . "\n";
        $html .= '<meta name="target" content="all">' . "\n";
        $html .= '<meta name="HandheldFriendly" content="true">' . "\n";
    }

    $html .= hs_render_public_seo_jsonld($resolved);

    return $html;
}

/** @param array<string, mixed> $resolved */
function hs_render_public_seo_jsonld(array $resolved): string
{
    $siteName = trim((string) ($GLOBALS['t']['brand'] ?? ''));
    if ($siteName === '') {
        $siteName = defined('HS_SITE_NAME') ? HS_SITE_NAME : 'Solaskinner';
    }
    $canonical = (string) $resolved['canonical'];
    $title = (string) $resolved['title'];
    $description = (string) $resolved['description'];
    $image = (string) $resolved['image'];
    $type = (string) ($resolved['type'] ?? 'page');
    $lang = (string) ($resolved['lang'] ?? 'en');

    $countries = ['Norway', 'Ukraine', 'Lithuania', 'Poland', 'Sweden', 'Belgium', 'Germany', 'European Union'];
    $areaServed = array_map(
        static fn(string $name): array => ['@type' => 'Country', 'name' => $name],
        $countries
    );

    $slogan = trim((string) ($GLOBALS['t']['tagline'] ?? $GLOBALS['t']['hero_kicker'] ?? ''));
    $org = [
        '@type' => ['Organization', 'OnlineBusiness'],
        '@id' => rtrim(HS_CANONICAL_URL, '/') . '/#organization',
        'name' => $siteName,
        'alternateName' => ['SolaSkinner', 'Solaskinner Hosting'],
        'url' => rtrim(HS_CANONICAL_URL, '/') . '/',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $image,
        ],
        'image' => $image,
        'email' => (string) ($GLOBALS['t']['footer_email'] ?? 'support@solaskinner.com'),
        'description' => $description,
        'slogan' => $slogan !== '' ? $slogan : 'Hosting #1 in Europe — best prices, domains, SSL, 15+ CMS',
        'areaServed' => $areaServed,
        'knowsAbout' => [
            'Web hosting',
            'Domain registration',
            'cPanel',
            'SSD hosting Norway',
            'CMS ecosystem',
            'WordPress hosting',
            'AI website tools',
        ],
        'sameAs' => ['https://github.com/Ruslan-Bilohash/hosting'],
        'contactPoint' => [[
            '@type' => 'ContactPoint',
            'contactType' => 'customer support',
            'email' => (string) ($GLOBALS['t']['footer_email'] ?? 'support@solaskinner.com'),
            'availableLanguage' => ['English', 'Ukrainian', 'Norwegian', 'German'],
            'areaServed' => $areaServed,
        ]],
    ];

    $website = [
        '@type' => 'WebSite',
        '@id' => rtrim(HS_CANONICAL_URL, '/') . '/#website',
        'url' => rtrim(HS_CANONICAL_URL, '/') . '/',
        'name' => $siteName,
        'alternateName' => 'Solaskinner — #1 hosting in Europe',
        'description' => $description,
        'publisher' => ['@id' => $org['@id']],
        'inLanguage' => [
            ...array_values(hs_seo_og_locales()),
        ],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => rtrim(HS_CANONICAL_URL, '/') . '/domain?sld={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $graph = [$org, $website];

    $hostingService = [
        '@type' => 'Service',
        '@id' => rtrim(HS_CANONICAL_URL, '/') . '/#hosting',
        'name' => $siteName . ' Web Hosting — #1 in Europe',
        'alternateName' => 'Best price European web hosting',
        'description' => $description,
        'provider' => ['@id' => $org['@id']],
        'areaServed' => $areaServed,
        'serviceType' => ['Web hosting', 'Domain registration', 'Managed CMS hosting'],
        'category' => 'WebHostingService',
        'termsOfService' => hs_canonical_url('terms.php'),
        'offers' => hs_seo_aggregate_offer_node($lang),
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => $siteName . ' hosting plans',
            'itemListElement' => hs_seo_plan_offer_nodes($lang),
        ],
    ];
    $graph[] = $hostingService;

    $domainService = [
        '@type' => 'Service',
        '@id' => rtrim(HS_CANONICAL_URL, '/') . '/domain#domain-registration',
        'name' => $siteName . ' Domain Registration',
        'description' => (string) ($GLOBALS['t']['domain_page_meta_description'] ?? $description),
        'provider' => ['@id' => $org['@id']],
        'areaServed' => $areaServed,
        'serviceType' => 'Domain name registration',
        'url' => hs_canonical_url('domain'),
    ];
    if ($type === 'domain') {
        $graph[] = $domainService;
        $graph[] = [
            '@type' => 'WebPage',
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => $website['@id']],
            'about' => ['@id' => $domainService['@id']],
            'inLanguage' => hs_seo_og_locales()[$lang] ?? 'en_GB',
        ];
        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => (string) ($GLOBALS['t']['nav_home'] ?? 'Home'),
                    'item' => rtrim(HS_CANONICAL_URL, '/') . '/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => (string) ($GLOBALS['t']['domain_page_breadcrumb'] ?? 'Domain search'),
                    'item' => $canonical,
                ],
            ],
        ];
    } elseif ($type === 'domains_catalog') {
        $catalogService = [
            '@type' => 'Service',
            '@id' => rtrim(HS_CANONICAL_URL, '/') . '/domains#tld-catalog',
            'name' => $siteName . ' Domain Price Catalog',
            'description' => $description,
            'provider' => ['@id' => $org['@id']],
            'areaServed' => $areaServed,
            'serviceType' => 'Domain pricing',
            'url' => $canonical,
        ];
        $graph[] = $catalogService;
        $graph[] = [
            '@type' => 'WebPage',
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => $website['@id']],
            'about' => ['@id' => $catalogService['@id']],
            'inLanguage' => hs_seo_og_locales()[$lang] ?? 'en_GB',
        ];
        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => (string) ($GLOBALS['t']['nav_home'] ?? 'Home'),
                    'item' => rtrim(HS_CANONICAL_URL, '/') . '/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => (string) ($GLOBALS['t']['domain_catalog_breadcrumb'] ?? 'Domain prices'),
                    'item' => $canonical,
                ],
            ],
        ];
    } elseif ($type === 'home') {
        // Keep home JSON-LD lean for mobile PageSpeed (large @graph hurts TBT/parse).
        $graph[] = hs_seo_webpage_node($canonical, $title, $description, $website['@id'], $hostingService['@id'], $lang);
        $faq = hs_seo_faq_nodes($lang);
        if ($faq !== []) {
            // Cap FAQ entities for payload size (visible FAQ still full on page).
            if (count($faq) > 6) {
                $faq = array_slice($faq, 0, 6);
            }
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical . '#faq',
                'mainEntity' => $faq,
                'url' => $canonical . '#faq',
                'inLanguage' => hs_seo_og_locales()[$lang] ?? 'en_GB',
            ];
        }
        $howto = hs_seo_howto_home_node($lang);
        if ($howto !== []) {
            $graph[] = $howto;
        }
        $graph[] = [
            '@type' => 'ItemList',
            '@id' => $canonical . '#seo-apps',
            'name' => 'Hosting for CMS apps',
            'numberOfItems' => 5,
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Hosting for Today news CMS', 'url' => hs_canonical_url('seo/hosting-for-today.php')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Hosting for Shop', 'url' => hs_canonical_url('seo/hosting-for-shop.php')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Hosting for Booking', 'url' => hs_canonical_url('seo/hosting-for-booking.php')],
                ['@type' => 'ListItem', 'position' => 4, 'name' => 'Hosting for WordPress', 'url' => hs_canonical_url('seo/hosting-for-wordpress.php')],
                ['@type' => 'ListItem', 'position' => 5, 'name' => 'All CMS hosting guides', 'url' => hs_canonical_url('seo/')],
            ],
        ];
    } elseif ($type === 'register') {
        $graph[] = hs_seo_webpage_node($canonical, $title, $description, $website['@id'], $hostingService['@id'], $lang);
        $graph[] = [
            '@type' => 'RegisterAction',
            '@id' => $canonical . '#register',
            'target' => $canonical,
            'name' => $title,
        ];
    } elseif (in_array($type, ['login', 'legal', 'cookies', 'privacy', 'terms', 'checkout', 'page'], true)) {
        $about = $type === 'login' ? $website['@id'] : $hostingService['@id'];
        $graph[] = hs_seo_webpage_node($canonical, $title, $description, $website['@id'], $about, $lang);
        if (!empty($resolved['breadcrumb'])) {
            $graph[] = hs_seo_breadcrumb_list((array) $resolved['breadcrumb'], $lang);
        }
    }

    $payload = [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter($graph)),
    ];

    return '<script type="application/ld+json">' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

/** @return array<string, mixed> */
function hs_seo_webpage_node(string $canonical, string $title, string $description, string $websiteId, string $aboutId, string $lang): array
{
    return [
        '@type' => 'WebPage',
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => $title,
        'description' => $description,
        'isPartOf' => ['@id' => $websiteId],
        'about' => ['@id' => $aboutId],
        'inLanguage' => hs_seo_og_locales()[$lang] ?? 'en_GB',
        'isAccessibleForFree' => true,
        'primaryImageOfPage' => [
            '@type' => 'ImageObject',
            'url' => hs_seo_default_image(),
        ],
    ];
}

/** @return array<string, mixed> */
function hs_seo_aggregate_offer_node(string $lang): array
{
    $offers = hs_seo_plan_offer_nodes($lang);
    $prices = [];
    $currency = 'EUR';
    foreach ($offers as $o) {
        if (isset($o['price'])) {
            $prices[] = (float) $o['price'];
        }
        if (!empty($o['priceCurrency'])) {
            $currency = (string) $o['priceCurrency'];
        }
    }
    $node = [
        '@type' => 'AggregateOffer',
        'url' => hs_canonical_url('register.php'),
        'priceCurrency' => $currency,
        'offerCount' => (string) max(1, count($offers)),
        'availability' => 'https://schema.org/InStock',
    ];
    if ($prices !== []) {
        $node['lowPrice'] = number_format(min($prices), 2, '.', '');
        $node['highPrice'] = number_format(max($prices), 2, '.', '');
    }

    return $node;
}

/** @return list<array<string, mixed>> */
function hs_seo_plan_offer_nodes(string $lang): array
{
    if (!function_exists('hs_plan_catalog_public_plans')) {
        require_once __DIR__ . '/plan-catalog.php';
    }
    $out = [];
    $pos = 1;
    foreach (hs_plan_catalog_public_plans('hosting') as $id => $plan) {
        if (!is_array($plan) || empty($plan['active'])) {
            continue;
        }
        $name = (string) ($GLOBALS['t']['plan_' . $id] ?? $id);
        $eur = (float) ($plan['price_eur'] ?? 0);
        $nok = (float) ($plan['price_nok'] ?? 0);
        $price = $eur > 0 ? $eur : $nok;
        $currency = $eur > 0 ? 'EUR' : 'NOK';
        if ($price <= 0) {
            continue;
        }
        $desc = trim((string) ($GLOBALS['t']['plan_' . $id . '_desc'] ?? ''));
        if (function_exists('mb_strlen') && mb_strlen($desc) > 140) {
            $desc = rtrim(mb_substr($desc, 0, 137)) . '…';
        } elseif (strlen($desc) > 140) {
            $desc = rtrim(substr($desc, 0, 137)) . '…';
        }
        $offerUrl = hs_canonical_url('register.php', ['plan' => $id]);
        $priceStr = number_format($price, 2, '.', '');
        // Google Product rich results: offers + image are required; digital goods also want shipping + return policy.
        $priceValidUntil = date('Y-12-31', strtotime('+1 year'));
        $validFrom = date('Y-m-d');
        $imageUrl = hs_seo_default_image();
        $offerCore = [
            '@type' => 'Offer',
            'url' => $offerUrl,
            'price' => $priceStr,
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/InStock',
            'priceValidUntil' => $priceValidUntil,
            'validFrom' => $validFrom,
            // Digital service: no physical shipping (rate 0, same-day online activation).
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => '0',
                    'currency' => $currency,
                ],
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'addressCountry' => ['NO', 'SE', 'DK', 'FI', 'DE', 'NL', 'BE', 'LT', 'PL', 'LV', 'EE', 'UA', 'GB'],
                ],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 0,
                        'unitCode' => 'DAY',
                    ],
                    'transitTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 0,
                        'maxValue' => 0,
                        'unitCode' => 'DAY',
                    ],
                ],
            ],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => ['NO', 'SE', 'DK', 'FI', 'DE', 'NL', 'BE', 'LT', 'PL', 'LV', 'EE', 'UA', 'GB'],
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => 14,
                'returnMethod' => 'https://schema.org/ReturnByMail',
                'returnFees' => 'https://schema.org/FreeReturn',
                'url' => hs_canonical_url('terms.php'),
            ],
        ];
        $product = [
            '@type' => 'Product',
            'name' => $name . ' hosting',
            'description' => $desc !== '' ? $desc : ($name . ' hosting plan'),
            'sku' => 'hosting-plan-' . preg_replace('/[^a-z0-9_-]+/i', '', (string) $id),
            'image' => [
                $imageUrl,
            ],
            'brand' => [
                '@type' => 'Brand',
                'name' => 'SolaSkinner',
            ],
            'category' => 'WebHostingService',
            'offers' => $offerCore,
        ];
        $out[] = array_merge($offerCore, [
            'position' => $pos++,
            'name' => $name,
            'itemOffered' => $product,
        ]);
        if ($pos > 4) {
            break;
        }
    }

    return $out;
}

/** @return list<array<string, mixed>> */
function hs_seo_faq_nodes(string $lang): array
{
    $t = is_array($GLOBALS['t'] ?? null) ? $GLOBALS['t'] : [];
    $pairs = [];
    for ($i = 1; $i <= 8; $i++) {
        $q = trim((string) ($t['seo_faq_q' . $i] ?? ''));
        $a = trim((string) ($t['seo_faq_a' . $i] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $pairs[] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $a,
            ],
        ];
    }

    return $pairs;
}

/** HowTo JSON-LD for homepage install flow. @return array<string, mixed> */
function hs_seo_howto_home_node(string $lang): array
{
    $t = is_array($GLOBALS['t'] ?? null) ? $GLOBALS['t'] : [];
    $name = trim((string) ($t['seo_howto_name'] ?? ''));
    if ($name === '') {
        $name = (string) ($t['steps_title'] ?? 'How to launch a website');
    }
    $desc = trim((string) ($t['seo_howto_desc'] ?? $t['steps_sub'] ?? ''));
    $steps = [];
    for ($i = 1; $i <= 3; $i++) {
        $st = trim((string) ($t['step_' . $i . '_title'] ?? ''));
        $sd = trim((string) ($t['step_' . $i . '_desc'] ?? ''));
        if ($st === '') {
            continue;
        }
        $steps[] = [
            '@type' => 'HowToStep',
            'position' => $i,
            'name' => $st,
            'text' => $sd !== '' ? $sd : $st,
            'url' => rtrim(HS_CANONICAL_URL, '/') . '/#pricing',
        ];
    }
    if ($steps === []) {
        return [];
    }

    return [
        '@type' => 'HowTo',
        '@id' => rtrim(HS_CANONICAL_URL, '/') . '/#howto',
        'name' => $name,
        'description' => $desc,
        'totalTime' => 'PT10M',
        'estimatedCost' => [
            '@type' => 'MonetaryAmount',
            'currency' => 'EUR',
            'value' => '8.73',
        ],
        'tool' => [
            ['@type' => 'HowToTool', 'name' => 'SolaSkinner control panel'],
            ['@type' => 'HowToTool', 'name' => 'One-click CMS installer'],
        ],
        'step' => $steps,
        'inLanguage' => hs_seo_og_locales()[$lang] ?? 'en_GB',
    ];

}

/** @param list<array{name:string,item?:string}> $crumbs */
function hs_seo_breadcrumb_list(array $crumbs, string $lang): array
{
    $elements = [];
    $pos = 1;
    foreach ($crumbs as $crumb) {
        $name = trim((string) ($crumb['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $entry = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $name,
        ];
        if (!empty($crumb['item'])) {
            $entry['item'] = (string) $crumb['item'];
        }
        $elements[] = $entry;
    }

    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];
}

function hs_render_sitemap_xml(): string
{
    $base = rtrim(HS_CANONICAL_URL, '/');
    $paths = [
        ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => $base . '/domain.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $base . '/domains.php', 'priority' => '0.85', 'changefreq' => 'weekly'],
        ['loc' => $base . '/register.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $base . '/login.php', 'priority' => '0.4', 'changefreq' => 'monthly'],
        ['loc' => $base . '/terms.php', 'priority' => '0.35', 'changefreq' => 'yearly'],
        ['loc' => $base . '/privacy.php', 'priority' => '0.35', 'changefreq' => 'yearly'],
        ['loc' => $base . '/cookies.php', 'priority' => '0.3', 'changefreq' => 'yearly'],
        // SEO landings: Hosting for each CMS (SolaSkinner install + bilohash demos)
        ['loc' => $base . '/seo/hosting-for-shop.php', 'priority' => '0.85', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-booking.php', 'priority' => '0.85', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-auction.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-freelance.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-pizza.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-today.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-gamehub.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-tavle.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-faktura.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-lending.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-wordpress.php', 'priority' => '0.85', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-3d.php', 'priority' => '0.75', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-news.php', 'priority' => '0.75', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-hosting.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/hosting-for-ai.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $base . '/seo/', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $base . '/llms.txt', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ];

    // Only keep URLs that exist as files when path is a local PHP/file (skip external)
    $paths = array_values(array_filter($paths, static function (array $row) use ($base): bool {
        $loc = (string) ($row['loc'] ?? '');
        $path = substr($loc, strlen($base));
        if ($path === '' || $path === '/') {
            return true;
        }
        $path = ltrim($path, '/');
        // pretty paths without extension — allow
        if (!str_contains($path, '.')) {
            return true;
        }
        $local = dirname(__DIR__) . '/' . $path;
        // seo pages may be generated after this file — keep if under /seo/
        if (str_starts_with($path, 'seo/')) {
            return true;
        }
        return is_file($local);
    }));

    $langs = function_exists('hs_langs') ? array_keys(hs_langs()) : ['en', 'uk', 'no'];
    if ($langs === []) {
        $langs = ['en', 'uk', 'no'];
    }
    $lastmod = gmdate('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach ($paths as $row) {
        $hreflangMap = [];
        if (function_exists('hs_langs')) {
            foreach (hs_langs() as $c => $meta) {
                $hreflangMap[$c] = (string) ($meta['html'] ?? $c);
            }
        }
        if ($hreflangMap === []) {
            $hreflangMap = [
                'en' => 'en',
                'uk' => 'uk',
                'no' => 'nb-NO',
                'lt' => 'lt',
                'pl' => 'pl',
                'sv' => 'sv',
            ];
        }
        // One <url> per path with xhtml:link alternates (cleaner for GSC)
        $defaultLoc = $row['loc'];
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($defaultLoc, ENT_XML1) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
        $xml .= '    <changefreq>' . htmlspecialchars((string) $row['changefreq'], ENT_XML1) . '</changefreq>' . "\n";
        $xml .= '    <priority>' . htmlspecialchars((string) $row['priority'], ENT_XML1) . '</priority>' . "\n";
        foreach ($langs as $code) {
            $altLoc = $row['loc'];
            if ($code !== 'en' && !str_ends_with($altLoc, '.txt')) {
                $altLoc .= (str_contains($altLoc, '?') ? '&' : '?') . 'lang=' . rawurlencode((string) $code);
            }
            $hl = $hreflangMap[$code] ?? $code;
            $xml .= '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars((string) $hl, ENT_XML1)
                . '" href="' . htmlspecialchars($altLoc, ENT_XML1) . '"/>' . "\n";
        }
        if (!str_ends_with((string) $row['loc'], '.txt')) {
            $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'
                . htmlspecialchars($defaultLoc, ENT_XML1) . '"/>' . "\n";
        }
        $xml .= '  </url>' . "\n";
    }
    $xml .= '</urlset>';

    return $xml;
}