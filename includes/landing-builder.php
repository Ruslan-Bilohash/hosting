<?php
declare(strict_types=1);

require_once __DIR__ . '/installer.php';
require_once __DIR__ . '/file-manager.php';
require_once __DIR__ . '/landing-ext.php';
require_once __DIR__ . '/landing-templates-ext.php';
require_once __DIR__ . '/landing-nav-footer.php';
require_once __DIR__ . '/landing-messengers.php';
require_once __DIR__ . '/landing-slider.php';

/** @return array<string, array{color:string,label:string,hue:int}> */
function hs_landing_themes(array $t): array
{
    return [
        'emerald' => ['color' => '#059669', 'label' => $t['landing_theme_emerald'] ?? 'Emerald', 'hue' => 160],
        'ocean' => ['color' => '#0284c7', 'label' => $t['landing_theme_ocean'] ?? 'Ocean', 'hue' => 200],
        'sunset' => ['color' => '#ea580c', 'label' => $t['landing_theme_sunset'] ?? 'Sunset', 'hue' => 25],
        'violet' => ['color' => '#7c3aed', 'label' => $t['landing_theme_violet'] ?? 'Violet', 'hue' => 265],
        'slate' => ['color' => '#475569', 'label' => $t['landing_theme_slate'] ?? 'Slate', 'hue' => 215],
        'rose' => ['color' => '#e11d48', 'label' => $t['landing_theme_rose'] ?? 'Rose', 'hue' => 345],
        'gold' => ['color' => '#ca8a04', 'label' => $t['landing_theme_gold'] ?? 'Gold', 'hue' => 45],
        'teal' => ['color' => '#0d9488', 'label' => $t['landing_theme_teal'] ?? 'Teal', 'hue' => 175],
        'indigo' => ['color' => '#4f46e5', 'label' => $t['landing_theme_indigo'] ?? 'Indigo', 'hue' => 245],
        'lime' => ['color' => '#65a30d', 'label' => $t['landing_theme_lime'] ?? 'Lime', 'hue' => 85],
        'coral' => ['color' => '#f43f5e', 'label' => $t['landing_theme_coral'] ?? 'Coral', 'hue' => 350],
        'midnight' => ['color' => '#1e3a5f', 'label' => $t['landing_theme_midnight'] ?? 'Midnight', 'hue' => 210],
    ];
}

/** @return array<string, string> */
function hs_landing_icon_styles(array $t): array
{
    return [
        'solid' => $t['landing_icon_style_solid'] ?? 'Filled',
        'regular' => $t['landing_icon_style_regular'] ?? 'Outline',
    ];
}

/** @return array<string, array{label:string,icons:list<string>}> */
function hs_landing_icon_sets(array $t): array
{
    return [
        'business' => [
            'label' => $t['landing_icon_set_business'] ?? 'Business',
            'icons' => ['fa-briefcase', 'fa-chart-line', 'fa-handshake', 'fa-users', 'fa-award', 'fa-building', 'fa-bullseye', 'fa-rocket', 'fa-star', 'fa-trophy', 'fa-lightbulb', 'fa-gears'],
        ],
        'tech' => [
            'label' => $t['landing_icon_set_tech'] ?? 'Tech',
            'icons' => ['fa-microchip', 'fa-cloud', 'fa-code', 'fa-server', 'fa-wifi', 'fa-database', 'fa-laptop-code', 'fa-shield-halved', 'fa-bolt', 'fa-sitemap', 'fa-network-wired', 'fa-terminal'],
        ],
        'creative' => [
            'label' => $t['landing_icon_set_creative'] ?? 'Creative',
            'icons' => ['fa-palette', 'fa-wand-magic-sparkles', 'fa-camera', 'fa-pen-nib', 'fa-image', 'fa-film', 'fa-music', 'fa-brush', 'fa-compass-drafting', 'fa-eye', 'fa-heart', 'fa-star'],
        ],
        'shop' => [
            'label' => $t['landing_icon_set_shop'] ?? 'Shop',
            'icons' => ['fa-cart-shopping', 'fa-truck-fast', 'fa-credit-card', 'fa-tags', 'fa-store', 'fa-gift', 'fa-box', 'fa-percent', 'fa-receipt', 'fa-bag-shopping', 'fa-shop', 'fa-wallet'],
        ],
        'health' => [
            'label' => $t['landing_icon_set_health'] ?? 'Health',
            'icons' => ['fa-heart-pulse', 'fa-stethoscope', 'fa-leaf', 'fa-spa', 'fa-user-doctor', 'fa-pills', 'fa-hospital', 'fa-dumbbell', 'fa-apple-whole', 'fa-sun', 'fa-droplet', 'fa-seedling'],
        ],
        'travel' => [
            'label' => $t['landing_icon_set_travel'] ?? 'Travel',
            'icons' => ['fa-plane', 'fa-map-location-dot', 'fa-hotel', 'fa-umbrella-beach', 'fa-compass', 'fa-car', 'fa-train', 'fa-ship', 'fa-mountain-sun', 'fa-camera-retro', 'fa-passport', 'fa-globe'],
        ],
    ];
}

/** @return array<string, array{label:string,hues:list<int>}> */
function hs_landing_gallery_palettes(array $t): array
{
    return [
        'brand' => ['label' => $t['landing_gal_palette_brand'] ?? 'Match brand', 'hues' => []],
        'warm' => ['label' => $t['landing_gal_palette_warm'] ?? 'Warm', 'hues' => [15, 30, 45, 20, 5, 35]],
        'cool' => ['label' => $t['landing_gal_palette_cool'] ?? 'Cool', 'hues' => [200, 220, 240, 190, 210, 230]],
        'ocean' => ['label' => $t['landing_gal_palette_ocean'] ?? 'Ocean', 'hues' => [195, 205, 185, 215, 175, 225]],
        'sunset' => ['label' => $t['landing_gal_palette_sunset'] ?? 'Sunset', 'hues' => [10, 25, 40, 350, 15, 30]],
        'pastel' => ['label' => $t['landing_gal_palette_pastel'] ?? 'Pastel', 'hues' => [280, 160, 200, 50, 320, 120]],
        'neon' => ['label' => $t['landing_gal_palette_neon'] ?? 'Neon', 'hues' => [320, 180, 60, 250, 140, 300]],
        'forest' => ['label' => $t['landing_gal_palette_forest'] ?? 'Forest', 'hues' => [140, 155, 120, 95, 165, 110]],
    ];
}

function hs_landing_hex_hue(string $hex): int
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return 160;
    }
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    if ($max === $min) {
        return 0;
    }
    $d = $max - $min;
    if ($max === $r) {
        $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
    } elseif ($max === $g) {
        $h = ($b - $r) / $d + 2;
    } else {
        $h = ($r - $g) / $d + 4;
    }

    return (int) round($h * 60) % 360;
}

/** @param array<string, mixed> $data */
function hs_landing_gallery_hues(array $data, int $count = 6): array
{
    $palettes = hs_landing_gallery_palettes([]);
    $key = (string) ($data['gallery_palette'] ?? 'brand');
    $palette = $palettes[$key] ?? $palettes['brand'];
    $hues = $palette['hues'];
    if ($hues === []) {
        $base = hs_landing_resolve_color($data);
        $h = hs_landing_hex_hue($base);
        $hues = [$h, $h + 25, $h + 50, $h + 75, $h + 100, $h + 125];
    }
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $out[] = ((int) ($hues[$i % count($hues)] ?? 160)) % 360;
    }

    return $out;
}

function hs_landing_fa_prefix(string $style): string
{
    return $style === 'regular' ? 'fa-regular' : 'fa-solid';
}

/** @param array<string, mixed> $data */
function hs_landing_icon_tag(string $icon, array $data): string
{
    $icon = preg_replace('/[^a-z0-9-]/i', '', $icon) ?: 'fa-star';
    $style = (string) ($data['icon_style'] ?? 'solid');
    $prefix = hs_landing_fa_prefix($style);

    return '<i class="' . $prefix . ' ' . htmlspecialchars($icon, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"></i>';
}

/** Adaptive form field with optional hint (landing builder settings). */
function hs_landing_panel_field(string $label, string $inputHtml, string $hint = ''): string
{
    $hintHtml = $hint !== ''
        ? '<p class="elb-field-hint">' . hs_h($hint) . '</p>'
        : '';
    return '<div class="elb-field">'
        . '<label class="elb-field-label">' . hs_h($label) . '</label>'
        . $inputHtml
        . $hintHtml
        . '</div>';
}

/** @return list<array{id:string,icon:string,label:string,hint:string}> */
function hs_landing_settings_sections(array $t): array
{
    return [
        ['id' => 'brand', 'icon' => 'fa-store', 'label' => $t['landing_sec_brand'] ?? 'Brand', 'hint' => $t['landing_sec_brand_hint'] ?? ''],
        ['id' => 'theme', 'icon' => 'fa-palette', 'label' => $t['landing_sec_theme'] ?? 'Color theme', 'hint' => $t['landing_sec_theme_hint'] ?? ''],
        ['id' => 'icons', 'icon' => 'fa-icons', 'label' => $t['landing_sec_icons'] ?? 'Icons', 'hint' => $t['landing_sec_icons_hint'] ?? ''],
        ['id' => 'nav', 'icon' => 'fa-bars', 'label' => $t['landing_sec_nav'] ?? 'Navigation', 'hint' => $t['landing_sec_nav_hint'] ?? ''],
        ['id' => 'messengers', 'icon' => 'fa-comments', 'label' => $t['landing_sec_messengers'] ?? 'Messengers', 'hint' => $t['landing_sec_messengers_hint'] ?? ''],
        ['id' => 'footer', 'icon' => 'fa-shoe-prints', 'label' => $t['landing_sec_footer'] ?? 'Footer', 'hint' => $t['landing_sec_footer_hint'] ?? ''],
    ];
}

/** @return array<string, string> */
function hs_landing_field_hints(array $t): array
{
    return [
        'variant' => $t['landing_hint_variant'] ?? 'Choose how this section looks on the page.',
        'title' => $t['landing_hint_title'] ?? 'Main heading visitors see first.',
        'subtitle' => $t['landing_hint_subtitle'] ?? 'Short line under the headline.',
        'text' => $t['landing_hint_text'] ?? 'Body copy — keep it clear and scannable.',
        'section_title' => $t['landing_hint_section_title'] ?? 'Heading above this group of items.',
        'cta_text' => $t['landing_hint_cta_text'] ?? 'Label on the button.',
        'cta_url' => $t['landing_hint_cta_url'] ?? 'Link target — use #contact for on-page anchors.',
        'phone' => $t['landing_hint_phone'] ?? 'Shown in the contact block.',
        'email' => $t['landing_hint_email'] ?? 'Public contact email.',
        'address' => $t['landing_hint_address'] ?? 'Street, city or service area.',
        'quote' => $t['landing_hint_quote'] ?? 'Customer quote or testimonial snippet.',
        'author' => $t['landing_hint_author'] ?? 'Who said the quote.',
        'caption' => $t['landing_hint_caption'] ?? 'Optional text under the image.',
        'business_name' => $t['landing_hint_business_name'] ?? 'Used in header, footer and browser title.',
        'tagline' => $t['landing_hint_tagline'] ?? 'One line describing what you do.',
        'gallery_palette' => $t['landing_hint_gallery_palette'] ?? 'Placeholder colors when no photo is uploaded.',
        'footer_text' => $t['landing_hint_footer_text'] ?? 'Small print or copyright line.',
        'nav_label' => $t['landing_hint_nav_label'] ?? 'Text shown in the menu.',
        'nav_url' => $t['landing_hint_nav_url'] ?? 'Where the menu link goes (#section or URL).',
        'block_color' => $t['landing_hint_block_color'] ?? 'Override the global theme color for this block only. Leave empty to use the page theme.',
        'bg_type' => $t['landing_hint_bg_type'] ?? 'Add a color or photo behind the whole block.',
        'bg_color' => $t['landing_hint_bg_color'] ?? 'Solid fill when background type is Color.',
        'bg_type_image' => $t['landing_hint_bg_image'] ?? 'Pick an image from your gallery.',
        'bg_overlay' => $t['landing_hint_bg_overlay'] ?? 'Dark overlay strip — higher value dims the background more.',
        'text_color' => $t['landing_hint_text_color'] ?? 'Paragraph and description text in this block.',
        'heading_color' => $t['landing_hint_heading_color'] ?? 'Headings (h1, h2) in this block.',
        'btn_color' => $t['landing_hint_btn_color'] ?? 'Background of CTA buttons.',
        'btn_text_color' => $t['landing_hint_btn_text_color'] ?? 'Text on CTA buttons.',
        'video_url' => $t['landing_hint_video_url'] ?? 'YouTube or Vimeo link — embedded in the page.',
        'embed_url' => $t['landing_hint_embed_url'] ?? 'Optional map embed URL (Google Maps → Share → Embed).',
        'countdown_date' => $t['landing_hint_countdown_date'] ?? 'Target date shown in the countdown (YYYY-MM-DD).',
        'file_url' => $t['landing_hint_file_url'] ?? 'Link to a PDF or file visitors can download.',
    ];
}

/** @return array<string, array{label:string,icon:string,variants:array<string,string>,anchor:string,desc?:string}> */
function hs_landing_block_registry(array $t): array
{
    $base = [
        'hero' => [
            'label' => $t['landing_block_hero'] ?? 'Hero',
            'desc' => $t['landing_block_hero_desc'] ?? 'Top banner with headline and call-to-action.',
            'icon' => 'fa-bullhorn',
            'anchor' => '',
            'variants' => [
                'split' => $t['landing_var_hero_split'] ?? 'Split + card',
                'centered' => $t['landing_var_hero_centered'] ?? 'Centered',
                'minimal' => $t['landing_var_hero_minimal'] ?? 'Minimal',
            ],
        ],
        'features' => [
            'label' => $t['landing_block_features'] ?? 'Features',
            'desc' => $t['landing_block_features_desc'] ?? 'Highlight key benefits with icons.',
            'icon' => 'fa-grip',
            'anchor' => 'features',
            'variants' => [
                'grid' => $t['landing_var_feat_grid'] ?? '3-column grid',
                'cards' => $t['landing_var_feat_cards'] ?? 'Elevated cards',
                'list' => $t['landing_var_feat_list'] ?? 'Icon list',
            ],
        ],
        'about' => [
            'label' => $t['landing_block_about'] ?? 'About',
            'desc' => $t['landing_block_about_desc'] ?? 'Tell visitors who you are.',
            'icon' => 'fa-circle-info',
            'anchor' => 'about',
            'variants' => [
                'split' => $t['landing_var_about_split'] ?? 'Split + card',
                'centered' => $t['landing_var_about_centered'] ?? 'Centered',
            ],
        ],
        'gallery' => [
            'label' => $t['landing_block_gallery'] ?? 'Gallery',
            'desc' => $t['landing_block_gallery_desc'] ?? 'Photos of your work or products.',
            'icon' => 'fa-images',
            'anchor' => 'gallery',
            'variants' => [
                'grid' => $t['landing_var_gal_grid'] ?? 'Photo grid',
                'masonry' => $t['landing_var_gal_masonry'] ?? 'Masonry',
                'row' => $t['landing_var_gal_row'] ?? 'Horizontal row',
            ],
        ],
        'info' => [
            'label' => $t['landing_block_info'] ?? 'Info',
            'desc' => $t['landing_block_info_desc'] ?? 'Stats, quotes or facts.',
            'icon' => 'fa-chart-simple',
            'anchor' => 'info',
            'variants' => [
                'stats' => $t['landing_var_info_stats'] ?? 'Stats counters',
                'text' => $t['landing_var_info_text'] ?? 'Text block',
                'quote' => $t['landing_var_info_quote'] ?? 'Quote',
            ],
        ],
        'contact' => [
            'label' => $t['landing_block_contact'] ?? 'Contact',
            'desc' => $t['landing_block_contact_desc'] ?? 'Phone, email and address.',
            'icon' => 'fa-address-book',
            'anchor' => 'contact',
            'variants' => [
                'card' => $t['landing_var_contact_card'] ?? 'Gradient card',
                'split' => $t['landing_var_contact_split'] ?? 'Split layout',
                'minimal' => $t['landing_var_contact_minimal'] ?? 'Minimal',
            ],
        ],
        'cta' => [
            'label' => $t['landing_block_cta'] ?? 'Call to action',
            'desc' => $t['landing_block_cta_desc'] ?? 'Banner urging visitors to act.',
            'icon' => 'fa-bullhorn',
            'anchor' => 'cta',
            'variants' => [
                'banner' => $t['landing_var_cta_banner'] ?? 'Full-width banner',
                'inline' => $t['landing_var_cta_inline'] ?? 'Inline strip',
                'split' => $t['landing_var_cta_split'] ?? 'Split + button',
            ],
        ],
        'testimonials' => [
            'label' => $t['landing_block_testimonials'] ?? 'Testimonials',
            'desc' => $t['landing_block_testimonials_desc'] ?? 'Reviews from happy clients.',
            'icon' => 'fa-quote-left',
            'anchor' => 'reviews',
            'variants' => [
                'cards' => $t['landing_var_test_cards'] ?? 'Review cards',
                'grid' => $t['landing_var_test_grid'] ?? '3-column grid',
                'quote' => $t['landing_var_test_quote'] ?? 'Featured quote',
            ],
        ],
        'pricing' => [
            'label' => $t['landing_block_pricing'] ?? 'Pricing',
            'desc' => $t['landing_block_pricing_desc'] ?? 'Plans and prices.',
            'icon' => 'fa-tags',
            'anchor' => 'pricing',
            'variants' => [
                'cards' => $t['landing_var_price_cards'] ?? 'Pricing cards',
                'table' => $t['landing_var_price_table'] ?? 'Comparison table',
                'compact' => $t['landing_var_price_compact'] ?? 'Compact row',
            ],
        ],
        'faq' => [
            'label' => $t['landing_block_faq'] ?? 'FAQ',
            'desc' => $t['landing_block_faq_desc'] ?? 'Common questions and answers.',
            'icon' => 'fa-circle-question',
            'anchor' => 'faq',
            'variants' => [
                'accordion' => $t['landing_var_faq_accordion'] ?? 'Accordion list',
                'list' => $t['landing_var_faq_list'] ?? 'Simple list',
                'twocol' => $t['landing_var_faq_twocol'] ?? 'Two columns',
            ],
        ],
        'team' => [
            'label' => $t['landing_block_team'] ?? 'Team',
            'desc' => $t['landing_block_team_desc'] ?? 'People behind your business.',
            'icon' => 'fa-people-group',
            'anchor' => 'team',
            'variants' => [
                'grid' => $t['landing_var_team_grid'] ?? 'Photo grid',
                'cards' => $t['landing_var_team_cards'] ?? 'Profile cards',
                'list' => $t['landing_var_team_list'] ?? 'Compact list',
            ],
        ],
        'logos' => [
            'label' => $t['landing_block_logos'] ?? 'Logos / partners',
            'desc' => $t['landing_block_logos_desc'] ?? 'Brands you work with.',
            'icon' => 'fa-building',
            'anchor' => 'partners',
            'variants' => [
                'row' => $t['landing_var_logos_row'] ?? 'Logo row',
                'grid' => $t['landing_var_logos_grid'] ?? 'Logo grid',
                'strip' => $t['landing_var_logos_strip'] ?? 'Muted strip',
            ],
        ],
        'video' => [
            'label' => $t['landing_block_video'] ?? 'Video',
            'desc' => $t['landing_block_video_desc'] ?? 'Embed a YouTube or Vimeo clip.',
            'icon' => 'fa-circle-play',
            'anchor' => 'video',
            'variants' => [
                'embed' => $t['landing_var_video_embed'] ?? 'Embedded player',
                'link' => $t['landing_var_video_link'] ?? 'Thumbnail + link',
            ],
        ],
        'newsletter' => [
            'label' => $t['landing_block_newsletter'] ?? 'Newsletter',
            'desc' => $t['landing_block_newsletter_desc'] ?? 'Email signup call-to-action.',
            'icon' => 'fa-envelope-open-text',
            'anchor' => 'newsletter',
            'variants' => [
                'card' => $t['landing_var_newsletter_card'] ?? 'Card',
                'inline' => $t['landing_var_newsletter_inline'] ?? 'Inline strip',
                'banner' => $t['landing_var_newsletter_banner'] ?? 'Full banner',
            ],
        ],
        'timeline' => [
            'label' => $t['landing_block_timeline'] ?? 'Timeline',
            'desc' => $t['landing_block_timeline_desc'] ?? 'Milestones or history.',
            'icon' => 'fa-timeline',
            'anchor' => 'timeline',
            'variants' => [
                'vertical' => $t['landing_var_timeline_vertical'] ?? 'Vertical line',
                'cards' => $t['landing_var_timeline_cards'] ?? 'Card steps',
            ],
        ],
        'services' => [
            'label' => $t['landing_block_services'] ?? 'Services',
            'desc' => $t['landing_block_services_desc'] ?? 'Service cards with optional price.',
            'icon' => 'fa-briefcase',
            'anchor' => 'services',
            'variants' => [
                'grid' => $t['landing_var_services_grid'] ?? '3-column grid',
                'list' => $t['landing_var_services_list'] ?? 'List',
                'cards' => $t['landing_var_services_cards'] ?? 'Elevated cards',
            ],
        ],
        'heading' => [
            'label' => $t['landing_block_heading'] ?? 'Heading',
            'desc' => $t['landing_block_heading_desc'] ?? 'Section title without extra content.',
            'icon' => 'fa-heading',
            'anchor' => '',
            'variants' => [
                'left' => $t['landing_var_heading_left'] ?? 'Left aligned',
                'center' => $t['landing_var_heading_center'] ?? 'Centered',
                'large' => $t['landing_var_heading_large'] ?? 'Large hero style',
            ],
        ],
        'text' => [
            'label' => $t['landing_block_text'] ?? 'Text',
            'desc' => $t['landing_block_text_desc'] ?? 'Paragraph or rich copy block.',
            'icon' => 'fa-align-left',
            'anchor' => '',
            'variants' => [
                'plain' => $t['landing_var_text_plain'] ?? 'Plain',
                'box' => $t['landing_var_text_box'] ?? 'Boxed',
                'narrow' => $t['landing_var_text_narrow'] ?? 'Narrow column',
            ],
        ],
        'image' => [
            'label' => $t['landing_block_image'] ?? 'Image',
            'desc' => $t['landing_block_image_desc'] ?? 'Single photo with caption.',
            'icon' => 'fa-image',
            'anchor' => '',
            'variants' => [
                'full' => $t['landing_var_image_full'] ?? 'Full width',
                'contained' => $t['landing_var_image_contained'] ?? 'Contained',
                'rounded' => $t['landing_var_image_rounded'] ?? 'Rounded card',
            ],
        ],
        'divider' => [
            'label' => $t['landing_block_divider'] ?? 'Divider',
            'desc' => $t['landing_block_divider_desc'] ?? 'Visual separator between sections.',
            'icon' => 'fa-minus',
            'anchor' => '',
            'variants' => [
                'line' => $t['landing_var_divider_line'] ?? 'Simple line',
                'dots' => $t['landing_var_divider_dots'] ?? 'Dots',
                'gradient' => $t['landing_var_divider_gradient'] ?? 'Gradient',
            ],
        ],
        'spacer' => [
            'label' => $t['landing_block_spacer'] ?? 'Spacer',
            'desc' => $t['landing_block_spacer_desc'] ?? 'Empty vertical space.',
            'icon' => 'fa-arrows-up-down',
            'anchor' => '',
            'variants' => [
                'sm' => $t['landing_var_spacer_sm'] ?? 'Small',
                'md' => $t['landing_var_spacer_md'] ?? 'Medium',
                'lg' => $t['landing_var_spacer_lg'] ?? 'Large',
            ],
        ],
        'hours' => [
            'label' => $t['landing_block_hours'] ?? 'Opening hours',
            'desc' => $t['landing_block_hours_desc'] ?? 'Business hours schedule.',
            'icon' => 'fa-clock',
            'anchor' => 'hours',
            'variants' => [
                'table' => $t['landing_var_hours_table'] ?? 'Table',
                'cards' => $t['landing_var_hours_cards'] ?? 'Cards',
            ],
        ],
        'map' => [
            'label' => $t['landing_block_map'] ?? 'Map',
            'desc' => $t['landing_block_map_desc'] ?? 'Location with optional embed.',
            'icon' => 'fa-map-location-dot',
            'anchor' => 'map',
            'variants' => [
                'embed' => $t['landing_var_map_embed'] ?? 'Embedded map',
                'card' => $t['landing_var_map_card'] ?? 'Address card',
            ],
        ],
        'banner' => [
            'label' => $t['landing_block_banner'] ?? 'Promo banner',
            'desc' => $t['landing_block_banner_desc'] ?? 'Slim announcement strip.',
            'icon' => 'fa-flag',
            'anchor' => '',
            'variants' => [
                'solid' => $t['landing_var_banner_solid'] ?? 'Solid strip',
                'outline' => $t['landing_var_banner_outline'] ?? 'Outline strip',
            ],
        ],
        'quote' => [
            'label' => $t['landing_block_quote'] ?? 'Quote',
            'desc' => $t['landing_block_quote_desc'] ?? 'Featured quotation.',
            'icon' => 'fa-quote-right',
            'anchor' => '',
            'variants' => [
                'centered' => $t['landing_var_quote_centered'] ?? 'Centered',
                'sidebar' => $t['landing_var_quote_sidebar'] ?? 'With accent bar',
            ],
        ],
        'download' => [
            'label' => $t['landing_block_download'] ?? 'Downloads',
            'desc' => $t['landing_block_download_desc'] ?? 'Files visitors can download.',
            'icon' => 'fa-download',
            'anchor' => 'downloads',
            'variants' => [
                'buttons' => $t['landing_var_download_buttons'] ?? 'Buttons',
                'list' => $t['landing_var_download_list'] ?? 'List',
            ],
        ],
        'alert' => [
            'label' => $t['landing_block_alert'] ?? 'Alert / notice',
            'desc' => $t['landing_block_alert_desc'] ?? 'Important message box.',
            'icon' => 'fa-circle-info',
            'anchor' => '',
            'variants' => [
                'info' => $t['landing_var_alert_info'] ?? 'Info',
                'success' => $t['landing_var_alert_success'] ?? 'Success',
                'warning' => $t['landing_var_alert_warning'] ?? 'Warning',
            ],
        ],
        'events' => [
            'label' => $t['landing_block_events'] ?? 'Events',
            'desc' => $t['landing_block_events_desc'] ?? 'Upcoming dates and locations.',
            'icon' => 'fa-calendar-days',
            'anchor' => 'events',
            'variants' => [
                'list' => $t['landing_var_events_list'] ?? 'List',
                'cards' => $t['landing_var_events_cards'] ?? 'Cards',
            ],
        ],
        'steps' => [
            'label' => $t['landing_block_steps'] ?? 'Steps',
            'desc' => $t['landing_block_steps_desc'] ?? 'Numbered process steps.',
            'icon' => 'fa-list-ol',
            'anchor' => 'steps',
            'variants' => [
                'numbered' => $t['landing_var_steps_numbered'] ?? 'Numbered',
                'horizontal' => $t['landing_var_steps_horizontal'] ?? 'Horizontal',
            ],
        ],
        'countdown' => [
            'label' => $t['landing_block_countdown'] ?? 'Countdown',
            'desc' => $t['landing_block_countdown_desc'] ?? 'Timer to a target date.',
            'icon' => 'fa-hourglass-half',
            'anchor' => 'countdown',
            'variants' => [
                'boxes' => $t['landing_var_countdown_boxes'] ?? 'Boxes',
                'inline' => $t['landing_var_countdown_inline'] ?? 'Inline',
            ],
        ],
        'columns' => [
            'label' => $t['landing_block_columns'] ?? 'Columns',
            'desc' => $t['landing_block_columns_desc'] ?? 'Multi-column text layout.',
            'icon' => 'fa-table-columns',
            'anchor' => '',
            'variants' => [
                'two' => $t['landing_var_columns_two'] ?? '2 columns',
                'three' => $t['landing_var_columns_three'] ?? '3 columns',
            ],
        ],
        'badges' => [
            'label' => $t['landing_block_badges'] ?? 'Badges / tags',
            'desc' => $t['landing_block_badges_desc'] ?? 'Skills, categories or labels.',
            'icon' => 'fa-tags',
            'anchor' => '',
            'variants' => [
                'pills' => $t['landing_var_badges_pills'] ?? 'Pills',
                'outline' => $t['landing_var_badges_outline'] ?? 'Outline',
            ],
        ],
    ];

    return array_merge($base, hs_landing_block_registry_ext($t));
}

/** @return array<string, string> */
function hs_landing_footer_styles(array $t): array
{
    return array_merge([
        'minimal' => $t['landing_footer_minimal'] ?? 'Minimal',
        'columns' => $t['landing_footer_columns'] ?? '3 columns',
        'centered' => $t['landing_footer_centered'] ?? 'Centered',
        'social' => $t['landing_footer_social'] ?? 'With social',
    ], hs_landing_footer_styles_extra($t));
}

/** @return array<string, mixed> */
function hs_landing_default_block(string $type, array $t): array
{
    $ext = hs_landing_default_block_ext($type, $t);
    if ($ext !== null) {
        return $ext;
    }
    $id = 'b_' . $type . '_' . substr(md5((string) microtime(true)), 0, 6);
    $base = ['id' => $id, 'type' => $type, 'variant' => '', 'on' => true, 'color' => '', 'text_color' => '', 'heading_color' => '', 'btn_color' => '', 'btn_text_color' => '', 'bg_type' => '', 'bg_color' => '', 'bg_image' => '', 'bg_overlay' => 40];

    return match ($type) {
        'hero' => array_merge($base, [
            'variant' => 'split',
            'title' => $t['landing_default_hero'] ?? 'Grow your business online',
            'subtitle' => $t['landing_default_hero_sub'] ?? 'A fast, mobile-friendly start page.',
            'cta_text' => $t['landing_default_cta'] ?? 'Contact us',
            'cta_url' => '#contact',
        ]),
        'features' => array_merge($base, [
            'variant' => 'grid',
            'section_title' => $t['landing_default_tagline'] ?? 'Why choose us',
            'items' => [
                ['icon' => 'fa-bolt', 'title' => $t['landing_feat_1_title'] ?? 'Fast setup', 'text' => $t['landing_feat_1_text'] ?? 'Launch quickly.'],
                ['icon' => 'fa-shield-halved', 'title' => $t['landing_feat_2_title'] ?? 'Secure', 'text' => $t['landing_feat_2_text'] ?? 'SSL and backups.'],
                ['icon' => 'fa-mobile-screen', 'title' => $t['landing_feat_3_title'] ?? 'Mobile', 'text' => $t['landing_feat_3_text'] ?? 'Works on every device.'],
            ],
        ]),
        'about' => array_merge($base, [
            'variant' => 'split',
            'title' => $t['landing_default_about_title'] ?? 'About us',
            'text' => $t['landing_default_about'] ?? 'Tell visitors who you are.',
        ]),
        'gallery' => array_merge($base, [
            'variant' => 'grid',
            'section_title' => $t['landing_gallery_default_title'] ?? 'Our work',
            'items' => [
                ['caption' => $t['landing_gal_1'] ?? 'Project 1', 'image' => '', 'hue' => '160'],
                ['caption' => $t['landing_gal_2'] ?? 'Project 2', 'image' => '', 'hue' => '200'],
                ['caption' => $t['landing_gal_3'] ?? 'Project 3', 'image' => '', 'hue' => '280'],
                ['caption' => $t['landing_gal_4'] ?? 'Project 4', 'image' => '', 'hue' => '30'],
            ],
        ]),
        'info' => array_merge($base, [
            'variant' => 'stats',
            'title' => $t['landing_info_default_title'] ?? 'By the numbers',
            'text' => '',
            'quote' => $t['landing_info_default_quote'] ?? 'Quality service you can trust.',
            'author' => $t['landing_info_default_author'] ?? 'Happy customer',
            'stats' => [
                ['value' => '10+', 'label' => $t['landing_stat_years'] ?? 'Years'],
                ['value' => '500+', 'label' => $t['landing_stat_clients'] ?? 'Clients'],
                ['value' => '24/7', 'label' => $t['landing_stat_support'] ?? 'Support'],
                ['value' => '99%', 'label' => $t['landing_stat_uptime'] ?? 'Uptime'],
            ],
        ]),
        'contact' => array_merge($base, [
            'variant' => 'card',
            'title' => $t['landing_default_cta'] ?? 'Contact us',
            'phone' => '',
            'email' => '',
            'address' => '',
            'cta_text' => $t['landing_default_cta'] ?? 'Contact us',
            'cta_url' => '#contact',
        ]),
        'cta' => array_merge($base, [
            'variant' => 'banner',
            'title' => $t['landing_cta_default_title'] ?? 'Ready to get started?',
            'text' => $t['landing_cta_default_text'] ?? 'Join hundreds of happy customers today.',
            'cta_text' => $t['landing_default_cta'] ?? 'Contact us',
            'cta_url' => '#contact',
        ]),
        'testimonials' => array_merge($base, [
            'variant' => 'cards',
            'section_title' => $t['landing_test_default_title'] ?? 'What clients say',
            'items' => [
                ['name' => $t['landing_test_1_name'] ?? 'Anna K.', 'role' => $t['landing_test_1_role'] ?? 'CEO', 'text' => $t['landing_test_1_text'] ?? 'Excellent service and fast support.'],
                ['name' => $t['landing_test_2_name'] ?? 'Mark P.', 'role' => $t['landing_test_2_role'] ?? 'Founder', 'text' => $t['landing_test_2_text'] ?? 'Professional team — highly recommend.'],
                ['name' => $t['landing_test_3_name'] ?? 'Sofia L.', 'role' => $t['landing_test_3_role'] ?? 'Manager', 'text' => $t['landing_test_3_text'] ?? 'Our go-to partner for years.'],
            ],
        ]),
        'pricing' => array_merge($base, [
            'variant' => 'cards',
            'section_title' => $t['landing_price_default_title'] ?? 'Plans & pricing',
            'items' => [
                ['name' => $t['landing_price_1_name'] ?? 'Starter', 'price' => '29', 'period' => $t['landing_price_period'] ?? '/mo', 'features' => [$t['landing_price_feat_1'] ?? '1 site', $t['landing_price_feat_2'] ?? 'SSL included'], 'cta_text' => $t['landing_price_cta'] ?? 'Choose', 'featured' => false],
                ['name' => $t['landing_price_2_name'] ?? 'Pro', 'price' => '59', 'period' => $t['landing_price_period'] ?? '/mo', 'features' => [$t['landing_price_feat_3'] ?? '5 sites', $t['landing_price_feat_4'] ?? 'Priority support'], 'cta_text' => $t['landing_price_cta'] ?? 'Choose', 'featured' => true],
                ['name' => $t['landing_price_3_name'] ?? 'Business', 'price' => '99', 'period' => $t['landing_price_period'] ?? '/mo', 'features' => [$t['landing_price_feat_5'] ?? 'Unlimited', $t['landing_price_feat_6'] ?? 'Dedicated support'], 'cta_text' => $t['landing_price_cta'] ?? 'Choose', 'featured' => false],
            ],
        ]),
        'faq' => array_merge($base, [
            'variant' => 'accordion',
            'section_title' => $t['landing_faq_default_title'] ?? 'Frequently asked questions',
            'items' => [
                ['q' => $t['landing_faq_1_q'] ?? 'How do I get started?', 'a' => $t['landing_faq_1_a'] ?? 'Sign up and publish your page in minutes.'],
                ['q' => $t['landing_faq_2_q'] ?? 'Can I change the design?', 'a' => $t['landing_faq_2_a'] ?? 'Yes — pick widgets, layouts and colors anytime.'],
                ['q' => $t['landing_faq_3_q'] ?? 'Is support included?', 'a' => $t['landing_faq_3_a'] ?? 'Yes, our team is here to help.'],
            ],
        ]),
        'team' => array_merge($base, [
            'variant' => 'grid',
            'section_title' => $t['landing_team_default_title'] ?? 'Meet the team',
            'items' => [
                ['name' => $t['landing_team_1_name'] ?? 'Alex Morgan', 'role' => $t['landing_team_1_role'] ?? 'CEO', 'bio' => $t['landing_team_1_bio'] ?? 'Leads strategy and growth.'],
                ['name' => $t['landing_team_2_name'] ?? 'Jamie Lee', 'role' => $t['landing_team_2_role'] ?? 'Designer', 'bio' => $t['landing_team_2_bio'] ?? 'Crafts beautiful experiences.'],
                ['name' => $t['landing_team_3_name'] ?? 'Chris Park', 'role' => $t['landing_team_3_role'] ?? 'Support', 'bio' => $t['landing_team_3_bio'] ?? 'Keeps customers happy.'],
            ],
        ]),
        'logos' => array_merge($base, [
            'variant' => 'row',
            'section_title' => $t['landing_logos_default_title'] ?? 'Trusted by',
            'items' => [
                ['name' => 'Acme'],
                ['name' => 'Globex'],
                ['name' => 'Initech'],
                ['name' => 'Umbrella'],
                ['name' => 'Stark'],
            ],
        ]),
        'video' => array_merge($base, [
            'variant' => 'embed',
            'section_title' => $t['landing_video_default_title'] ?? 'Watch our story',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]),
        'newsletter' => array_merge($base, [
            'variant' => 'card',
            'title' => $t['landing_newsletter_default_title'] ?? 'Stay in the loop',
            'text' => $t['landing_newsletter_default_text'] ?? 'Get updates — no spam.',
            'cta_text' => $t['landing_newsletter_cta'] ?? 'Subscribe',
        ]),
        'timeline' => array_merge($base, [
            'variant' => 'vertical',
            'section_title' => $t['landing_timeline_default_title'] ?? 'Our journey',
            'items' => [
                ['year' => '2020', 'title' => $t['landing_timeline_1_title'] ?? 'Founded', 'text' => $t['landing_timeline_1_text'] ?? 'Started with a vision.'],
                ['year' => '2022', 'title' => $t['landing_timeline_2_title'] ?? 'Growth', 'text' => $t['landing_timeline_2_text'] ?? 'Expanded our team.'],
                ['year' => '2024', 'title' => $t['landing_timeline_3_title'] ?? 'Today', 'text' => $t['landing_timeline_3_text'] ?? 'Serving clients worldwide.'],
            ],
        ]),
        'services' => array_merge($base, [
            'variant' => 'grid',
            'section_title' => $t['landing_services_default_title'] ?? 'Our services',
            'items' => [
                ['icon' => 'fa-wrench', 'title' => $t['landing_svc_1_title'] ?? 'Consulting', 'text' => $t['landing_svc_1_text'] ?? 'Expert advice.', 'price' => ''],
                ['icon' => 'fa-paintbrush', 'title' => $t['landing_svc_2_title'] ?? 'Design', 'text' => $t['landing_svc_2_text'] ?? 'Beautiful visuals.', 'price' => ''],
                ['icon' => 'fa-code', 'title' => $t['landing_svc_3_title'] ?? 'Development', 'text' => $t['landing_svc_3_text'] ?? 'Built to last.', 'price' => ''],
            ],
        ]),
        'heading' => array_merge($base, [
            'variant' => 'center',
            'title' => $t['landing_heading_default_title'] ?? 'Section title',
            'subtitle' => $t['landing_heading_default_sub'] ?? 'Optional subtitle',
        ]),
        'text' => array_merge($base, [
            'variant' => 'plain',
            'title' => '',
            'text' => $t['landing_text_default'] ?? 'Write your content here. Keep paragraphs short and easy to read.',
        ]),
        'image' => array_merge($base, [
            'variant' => 'contained',
            'image' => '',
            'caption' => $t['landing_image_default_caption'] ?? 'Image caption',
        ]),
        'divider' => array_merge($base, ['variant' => 'line']),
        'spacer' => array_merge($base, ['variant' => 'md']),
        'hours' => array_merge($base, [
            'variant' => 'table',
            'title' => $t['landing_hours_default_title'] ?? 'Opening hours',
            'items' => [
                ['day' => $t['landing_day_mon_fri'] ?? 'Mon–Fri', 'hours' => '9:00 – 18:00'],
                ['day' => $t['landing_day_sat'] ?? 'Saturday', 'hours' => '10:00 – 14:00'],
                ['day' => $t['landing_day_sun'] ?? 'Sunday', 'hours' => $t['landing_day_closed'] ?? 'Closed'],
            ],
        ]),
        'map' => array_merge($base, [
            'variant' => 'embed',
            'title' => $t['landing_map_default_title'] ?? 'Find us',
            'address' => '',
            'embed_url' => '',
        ]),
        'banner' => array_merge($base, [
            'variant' => 'solid',
            'text' => $t['landing_banner_default_text'] ?? 'Limited offer — act now!',
            'cta_text' => $t['landing_default_cta'] ?? 'Learn more',
            'cta_url' => '#contact',
        ]),
        'quote' => array_merge($base, [
            'variant' => 'centered',
            'quote' => $t['landing_quote_default'] ?? 'Quality is remembered long after price is forgotten.',
            'author' => $t['landing_quote_default_author'] ?? 'Happy client',
            'role' => '',
        ]),
        'download' => array_merge($base, [
            'variant' => 'buttons',
            'section_title' => $t['landing_download_default_title'] ?? 'Downloads',
            'items' => [
                ['label' => $t['landing_dl_1'] ?? 'Brochure (PDF)', 'url' => '#', 'size' => '2 MB'],
                ['label' => $t['landing_dl_2'] ?? 'Price list', 'url' => '#', 'size' => '500 KB'],
            ],
        ]),
        'alert' => array_merge($base, [
            'variant' => 'info',
            'title' => $t['landing_alert_default_title'] ?? 'Please note',
            'text' => $t['landing_alert_default_text'] ?? 'We are closed on public holidays.',
        ]),
        'events' => array_merge($base, [
            'variant' => 'list',
            'section_title' => $t['landing_events_default_title'] ?? 'Upcoming events',
            'items' => [
                ['date' => 'Mar 15', 'title' => $t['landing_event_1_title'] ?? 'Open day', 'location' => $t['landing_event_1_loc'] ?? 'Main office'],
                ['date' => 'Apr 22', 'title' => $t['landing_event_2_title'] ?? 'Workshop', 'location' => $t['landing_event_2_loc'] ?? 'Online'],
            ],
        ]),
        'steps' => array_merge($base, [
            'variant' => 'numbered',
            'section_title' => $t['landing_steps_default_title'] ?? 'How it works',
            'items' => [
                ['title' => $t['landing_step_1_title'] ?? 'Contact us', 'text' => $t['landing_step_1_text'] ?? 'Tell us what you need.'],
                ['title' => $t['landing_step_2_title'] ?? 'We plan', 'text' => $t['landing_step_2_text'] ?? 'Tailored proposal.'],
                ['title' => $t['landing_step_3_title'] ?? 'Delivery', 'text' => $t['landing_step_3_text'] ?? 'On time, every time.'],
            ],
        ]),
        'countdown' => array_merge($base, [
            'variant' => 'boxes',
            'title' => $t['landing_countdown_default_title'] ?? 'Launching soon',
            'text' => $t['landing_countdown_default_text'] ?? 'Mark your calendar!',
            'countdown_date' => date('Y-m-d', strtotime('+30 days')),
        ]),
        'columns' => array_merge($base, [
            'variant' => 'two',
            'section_title' => '',
            'items' => [
                ['title' => $t['landing_col_1_title'] ?? 'Column one', 'text' => $t['landing_col_1_text'] ?? 'First column content.'],
                ['title' => $t['landing_col_2_title'] ?? 'Column two', 'text' => $t['landing_col_2_text'] ?? 'Second column content.'],
            ],
        ]),
        'badges' => array_merge($base, [
            'variant' => 'pills',
            'section_title' => $t['landing_badges_default_title'] ?? 'We specialize in',
            'items' => [
                ['label' => 'Web'],
                ['label' => 'Design'],
                ['label' => 'SEO'],
                ['label' => 'Support'],
            ],
        ]),
        default => $base,
    };
}

/** @return list<array<string, mixed>> */
function hs_landing_default_blocks(array $t): array
{
    return [
        hs_landing_default_block('hero', $t),
        hs_landing_default_block('features', $t),
        hs_landing_default_block('about', $t),
        hs_landing_default_block('contact', $t),
    ];
}

/** @return list<array<string, mixed>> */
function hs_landing_template_blocks(string $templateId, array $t): array
{
    $mk = static function (string $type, array $overrides = []) use ($t): array {
        $block = hs_landing_default_block($type, $t);
        $block['id'] = 'b_' . $type . '_' . substr(bin2hex(random_bytes(4)), 0, 6);

        return array_merge($block, $overrides);
    };

    return match ($templateId) {
        'startup' => [
            $mk('hero', ['variant' => 'centered']),
            $mk('features', ['variant' => 'cards']),
            $mk('pricing', ['variant' => 'cards']),
            $mk('testimonials', ['variant' => 'cards']),
            $mk('cta', ['variant' => 'banner']),
            $mk('contact', ['variant' => 'split']),
        ],
        'agency' => [
            $mk('hero', ['variant' => 'minimal']),
            $mk('gallery', ['variant' => 'masonry']),
            $mk('features', ['variant' => 'list']),
            $mk('team', ['variant' => 'cards']),
            $mk('logos', ['variant' => 'row']),
            $mk('contact', ['variant' => 'card']),
        ],
        'portfolio' => [
            $mk('hero', ['variant' => 'centered']),
            $mk('gallery', ['variant' => 'grid']),
            $mk('about', ['variant' => 'centered']),
            $mk('testimonials', ['variant' => 'quote']),
            $mk('contact', ['variant' => 'minimal']),
        ],
        'restaurant' => [
            $mk('hero', ['variant' => 'split']),
            $mk('gallery', ['variant' => 'row']),
            $mk('features', ['variant' => 'grid']),
            $mk('info', ['variant' => 'quote']),
            $mk('contact', ['variant' => 'card']),
        ],
        'saas' => [
            $mk('hero', ['variant' => 'split']),
            $mk('features', ['variant' => 'cards']),
            $mk('pricing', ['variant' => 'table']),
            $mk('faq', ['variant' => 'accordion']),
            $mk('logos', ['variant' => 'grid']),
            $mk('cta', ['variant' => 'inline']),
            $mk('contact', ['variant' => 'split']),
        ],
        'personal' => [
            $mk('hero', ['variant' => 'minimal']),
            $mk('about', ['variant' => 'centered']),
            $mk('info', ['variant' => 'text']),
            $mk('testimonials', ['variant' => 'grid']),
            $mk('contact', ['variant' => 'minimal']),
        ],
        'local' => [
            $mk('hero', ['variant' => 'split']),
            $mk('features', ['variant' => 'list']),
            $mk('info', ['variant' => 'stats']),
            $mk('testimonials', ['variant' => 'cards']),
            $mk('faq', ['variant' => 'list']),
            $mk('contact', ['variant' => 'card']),
        ],
        'shop' => [
            $mk('hero', ['variant' => 'centered']),
            $mk('gallery', ['variant' => 'grid']),
            $mk('features', ['variant' => 'cards']),
            $mk('pricing', ['variant' => 'compact']),
            $mk('cta', ['variant' => 'split']),
            $mk('contact', ['variant' => 'card']),
        ],
        'full' => [
            $mk('hero', ['variant' => 'split']),
            $mk('features', ['variant' => 'grid']),
            $mk('about', ['variant' => 'split']),
            $mk('gallery', ['variant' => 'grid']),
            $mk('info', ['variant' => 'stats']),
            $mk('testimonials', ['variant' => 'cards']),
            $mk('pricing', ['variant' => 'cards']),
            $mk('faq', ['variant' => 'twocol']),
            $mk('team', ['variant' => 'grid']),
            $mk('logos', ['variant' => 'strip']),
            $mk('cta', ['variant' => 'banner']),
            $mk('contact', ['variant' => 'card']),
        ],
        default => [
            $mk('hero', ['variant' => 'split']),
            $mk('features', ['variant' => 'grid']),
            $mk('about', ['variant' => 'split']),
            $mk('info', ['variant' => 'stats']),
            $mk('contact', ['variant' => 'card']),
        ],
    };
}

/** @return array<string, array{label:string,desc:string,icon:string,blocks:list<array<string,mixed>>}> */
function hs_landing_page_templates(array $t): array
{
    $defs = [
        'business' => [
            'label' => $t['landing_tpl_business'] ?? 'Business classic',
            'desc' => $t['landing_tpl_business_desc'] ?? 'Hero, features, about, stats, contact',
            'icon' => 'fa-briefcase',
        ],
        'startup' => [
            'label' => $t['landing_tpl_startup'] ?? 'Startup',
            'desc' => $t['landing_tpl_startup_desc'] ?? 'Centered hero, pricing, reviews, CTA',
            'icon' => 'fa-rocket',
        ],
        'agency' => [
            'label' => $t['landing_tpl_agency'] ?? 'Creative agency',
            'desc' => $t['landing_tpl_agency_desc'] ?? 'Gallery, team, logos, list features',
            'icon' => 'fa-palette',
        ],
        'portfolio' => [
            'label' => $t['landing_tpl_portfolio'] ?? 'Portfolio',
            'desc' => $t['landing_tpl_portfolio_desc'] ?? 'Gallery-focused with quote review',
            'icon' => 'fa-images',
        ],
        'restaurant' => [
            'label' => $t['landing_tpl_restaurant'] ?? 'Restaurant / café',
            'desc' => $t['landing_tpl_restaurant_desc'] ?? 'Photo row, quote, warm layout',
            'icon' => 'fa-utensils',
        ],
        'saas' => [
            'label' => $t['landing_tpl_saas'] ?? 'SaaS / app',
            'desc' => $t['landing_tpl_saas_desc'] ?? 'Pricing table, FAQ, partner logos',
            'icon' => 'fa-cloud',
        ],
        'personal' => [
            'label' => $t['landing_tpl_personal'] ?? 'Personal / freelancer',
            'desc' => $t['landing_tpl_personal_desc'] ?? 'Minimal hero, about, testimonials',
            'icon' => 'fa-user',
        ],
        'local' => [
            'label' => $t['landing_tpl_local'] ?? 'Local services',
            'desc' => $t['landing_tpl_local_desc'] ?? 'Stats, FAQ, reviews for local biz',
            'icon' => 'fa-store',
        ],
        'shop' => [
            'label' => $t['landing_tpl_shop'] ?? 'Shop / product',
            'desc' => $t['landing_tpl_shop_desc'] ?? 'Gallery, pricing, product CTA',
            'icon' => 'fa-cart-shopping',
        ],
        'full' => [
            'label' => $t['landing_tpl_full'] ?? 'Full landing',
            'desc' => $t['landing_tpl_full_desc'] ?? 'All block types — maximum sections',
            'icon' => 'fa-layer-group',
        ],
    ];
    $out = [];
    foreach ($defs as $id => $meta) {
        $out[$id] = array_merge($meta, [
            'blocks' => hs_landing_template_blocks($id, $t),
            'meta' => hs_landing_template_meta($id, $t),
        ]);
    }

    return array_merge($out, hs_landing_page_templates_ext($t));
}

/** @return array<string, mixed> */
function hs_landing_template_meta(string $templateId, array $t): array
{
    $ext = hs_landing_template_meta_ext($templateId, $t);
    if ($ext !== null) {
        return $ext;
    }

    return [];
}

/** @return array<string, mixed> */
function hs_landing_builder_defaults(array $user, array $t): array
{
    $name = (string) ($user['name'] ?? $user['username'] ?? 'My Business');

    return [
        'schema' => 2,
        'business_name' => $name,
        'tagline' => $t['landing_default_tagline'] ?? 'Professional services for your customers',
        'theme' => 'emerald',
        'color' => '#059669',
        'icon_set' => 'business',
        'icon_style' => 'solid',
        'gallery_palette' => 'brand',
        'logo_icon' => 'fa-store',
        'nav_style' => 'classic',
        'nav_burger' => 'mobile',
        'nav_cta_text' => '',
        'nav_cta_url' => '#contact',
        'msg_whatsapp' => '',
        'msg_telegram' => '',
        'msg_viber' => '',
        'msg_messenger' => '',
        'msg_signal' => '',
        'msg_skype' => '',
        'msg_line' => '',
        'msg_floating' => '1',
        'msg_style' => 'stack',
        'msg_position' => 'right',
        'footer_style' => 'minimal',
        'footer_text' => '',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
        'nav_links' => [
            ['label' => $t['landing_nav_features'] ?? 'Features', 'url' => '#features', 'on' => true],
            ['label' => $t['landing_nav_about'] ?? 'About', 'url' => '#about', 'on' => true],
            ['label' => $t['landing_nav_gallery'] ?? 'Gallery', 'url' => '#gallery', 'on' => false],
            ['label' => $t['landing_nav_contact'] ?? 'Contact', 'url' => '#contact', 'on' => true],
        ],
        'blocks' => hs_landing_default_blocks($t),
        'published_at' => '',
        'published_url' => '',
        'domain_hint' => (string) ($user['pending_domain'] ?? ''),
    ];
}

/** @param array<string, mixed> $raw */
function hs_landing_migrate_legacy(array $raw, array $t): array
{
    $blocks = [];
    $blocks[] = array_merge(hs_landing_default_block('hero', $t), [
        'title' => (string) ($raw['hero_title'] ?? ''),
        'subtitle' => (string) ($raw['hero_subtitle'] ?? ''),
        'cta_text' => (string) ($raw['cta_text'] ?? ''),
        'cta_url' => (string) ($raw['cta_url'] ?? '#contact'),
        'on' => true,
    ]);
    if (!empty($raw['show_features'])) {
        $items = [];
        foreach ((array) ($raw['features'] ?? []) as $f) {
            if (!is_array($f)) {
                continue;
            }
            $items[] = [
                'icon' => (string) ($f['icon'] ?? 'fa-star'),
                'title' => (string) ($f['title'] ?? ''),
                'text' => (string) ($f['text'] ?? ''),
            ];
        }
        $blocks[] = array_merge(hs_landing_default_block('features', $t), [
            'section_title' => (string) ($raw['tagline'] ?? ''),
            'items' => $items,
            'on' => true,
        ]);
    }
    if (!empty($raw['show_about'])) {
        $blocks[] = array_merge(hs_landing_default_block('about', $t), [
            'title' => (string) ($raw['about_title'] ?? ''),
            'text' => (string) ($raw['about_text'] ?? ''),
            'on' => true,
        ]);
    }
    if (!empty($raw['show_contact'])) {
        $blocks[] = array_merge(hs_landing_default_block('contact', $t), [
            'title' => (string) ($raw['cta_text'] ?? ''),
            'phone' => (string) ($raw['phone'] ?? ''),
            'email' => (string) ($raw['email'] ?? ''),
            'address' => (string) ($raw['address'] ?? ''),
            'cta_text' => (string) ($raw['cta_text'] ?? ''),
            'cta_url' => (string) ($raw['cta_url'] ?? '#contact'),
            'on' => true,
        ]);
    }

    return [
        'schema' => 2,
        'business_name' => (string) ($raw['business_name'] ?? ''),
        'tagline' => (string) ($raw['tagline'] ?? ''),
        'theme' => 'emerald',
        'color' => (string) ($raw['color'] ?? '#059669'),
        'icon_set' => 'business',
        'icon_style' => 'solid',
        'gallery_palette' => 'brand',
        'logo_icon' => 'fa-store',
        'nav_style' => 'classic',
        'nav_burger' => 'mobile',
        'nav_cta_text' => '',
        'nav_cta_url' => '#contact',
        'msg_whatsapp' => '',
        'msg_telegram' => '',
        'msg_viber' => '',
        'msg_messenger' => '',
        'msg_signal' => '',
        'msg_skype' => '',
        'msg_line' => '',
        'msg_floating' => '1',
        'msg_style' => 'stack',
        'msg_position' => 'right',
        'footer_style' => 'minimal',
        'footer_text' => '',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
        'nav_links' => hs_landing_builder_defaults(['name' => $raw['business_name'] ?? ''], $t)['nav_links'],
        'blocks' => $blocks !== [] ? $blocks : hs_landing_default_blocks($t),
        'published_at' => (string) ($raw['published_at'] ?? ''),
        'published_url' => (string) ($raw['published_url'] ?? ''),
    ];
}

/** @param array<string, mixed> $block */
function hs_landing_normalize_block(array $block, array $t): array
{
    $registry = hs_landing_block_registry($t);
    $type = (string) ($block['type'] ?? 'hero');
    if (!isset($registry[$type])) {
        $type = 'hero';
    }
    $default = hs_landing_default_block($type, $t);
    $out = array_merge($default, $block);
    $out['type'] = $type;
    $out['id'] = preg_replace('/[^a-z0-9_-]/i', '', (string) ($out['id'] ?? '')) ?: $default['id'];
    $variants = array_keys($registry[$type]['variants']);
    if (!in_array((string) ($out['variant'] ?? ''), $variants, true)) {
        $out['variant'] = $variants[0] ?? 'split';
    }
    $out['on'] = !empty($out['on']);
    $blockColor = (string) ($out['color'] ?? '');
    $out['color'] = preg_match('/^#[0-9a-fA-F]{6}$/', $blockColor) ? $blockColor : '';
    $bgType = (string) ($out['bg_type'] ?? '');
    if (!in_array($bgType, ['', 'color', 'image'], true)) {
        $bgType = '';
    }
    $out['bg_type'] = $bgType;
    $bgColor = (string) ($out['bg_color'] ?? '');
    $out['bg_color'] = preg_match('/^#[0-9a-fA-F]{6}$/', $bgColor) ? $bgColor : '';
    $out['bg_image'] = hs_landing_sanitize_gallery_image((string) ($out['bg_image'] ?? ''));
    if ($out['bg_image'] !== '' && $out['bg_type'] === '') {
        $out['bg_type'] = 'image';
    }
    $out['bg_overlay'] = max(0, min(100, (int) ($out['bg_overlay'] ?? 40)));
    foreach (['text_color', 'heading_color', 'btn_color', 'btn_text_color'] as $colorKey) {
        $cv = (string) ($out[$colorKey] ?? '');
        $out[$colorKey] = preg_match('/^#[0-9a-fA-F]{6}$/', $cv) ? $cv : '';
    }

    if ($type === 'features') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'icon' => preg_replace('/[^a-z0-9-]/i', '', (string) ($item['icon'] ?? 'fa-star')) ?: 'fa-star',
                'title' => trim((string) ($item['title'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
            ];
            if (count($items) >= 6) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'gallery') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'caption' => trim((string) ($item['caption'] ?? '')),
                'image' => hs_landing_sanitize_gallery_image((string) ($item['image'] ?? '')),
                'hue' => (string) max(0, min(360, (int) ($item['hue'] ?? 160))),
            ];
            if (count($items) >= 6) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'info') {
        $stats = [];
        foreach ((array) ($out['stats'] ?? []) as $s) {
            if (!is_array($s)) {
                continue;
            }
            $stats[] = [
                'value' => trim((string) ($s['value'] ?? '')),
                'label' => trim((string) ($s['label'] ?? '')),
            ];
            if (count($stats) >= 4) {
                break;
            }
        }
        $out['stats'] = $stats !== [] ? $stats : $default['stats'];
        foreach (['title', 'text', 'quote', 'author'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'contact') {
        foreach (['title', 'phone', 'email', 'address', 'cta_text', 'cta_url'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'cta') {
        foreach (['title', 'text', 'cta_text', 'cta_url'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'testimonials') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
            ];
            if (count($items) >= 6) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'pricing') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $feats = [];
            foreach ((array) ($item['features'] ?? []) as $f) {
                $f = trim((string) $f);
                if ($f !== '') {
                    $feats[] = $f;
                }
                if (count($feats) >= 8) {
                    break;
                }
            }
            $items[] = [
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '')),
                'period' => trim((string) ($item['period'] ?? '')),
                'features' => $feats,
                'cta_text' => trim((string) ($item['cta_text'] ?? '')),
                'featured' => !empty($item['featured']),
            ];
            if (count($items) >= 4) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'faq') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'q' => trim((string) ($item['q'] ?? '')),
                'a' => trim((string) ($item['a'] ?? '')),
            ];
            if (count($items) >= 10) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'team') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'bio' => trim((string) ($item['bio'] ?? '')),
            ];
            if (count($items) >= 8) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'logos') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name !== '') {
                $items[] = ['name' => $name];
            }
            if (count($items) >= 12) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'video') {
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
        $out['video_url'] = trim((string) ($out['video_url'] ?? ''));
    } elseif ($type === 'newsletter') {
        foreach (['title', 'text', 'cta_text'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'timeline') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'year' => trim((string) ($item['year'] ?? '')),
                'title' => trim((string) ($item['title'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
            ];
            if (count($items) >= 8) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'services') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'icon' => preg_replace('/[^a-z0-9-]/i', '', (string) ($item['icon'] ?? 'fa-star')) ?: 'fa-star',
                'title' => trim((string) ($item['title'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '')),
            ];
            if (count($items) >= 8) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'heading') {
        $out['title'] = trim((string) ($out['title'] ?? ''));
        $out['subtitle'] = trim((string) ($out['subtitle'] ?? ''));
    } elseif ($type === 'text') {
        $out['title'] = trim((string) ($out['title'] ?? ''));
        $out['text'] = trim((string) ($out['text'] ?? ''));
    } elseif ($type === 'image') {
        $out['image'] = hs_landing_sanitize_gallery_image((string) ($out['image'] ?? ''));
        $out['caption'] = trim((string) ($out['caption'] ?? ''));
    } elseif ($type === 'hours') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'day' => trim((string) ($item['day'] ?? '')),
                'hours' => trim((string) ($item['hours'] ?? '')),
            ];
            if (count($items) >= 7) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['title'] = trim((string) ($out['title'] ?? ''));
    } elseif ($type === 'map') {
        foreach (['title', 'address', 'embed_url'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'banner') {
        foreach (['text', 'cta_text', 'cta_url'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'quote') {
        foreach (['quote', 'author', 'role'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'download') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $items[] = [
                'label' => $label,
                'url' => trim((string) ($item['url'] ?? '#')),
                'size' => trim((string) ($item['size'] ?? '')),
            ];
            if (count($items) >= 8) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'alert') {
        $out['title'] = trim((string) ($out['title'] ?? ''));
        $out['text'] = trim((string) ($out['text'] ?? ''));
    } elseif ($type === 'events') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'date' => trim((string) ($item['date'] ?? '')),
                'title' => trim((string) ($item['title'] ?? '')),
                'location' => trim((string) ($item['location'] ?? '')),
            ];
            if (count($items) >= 10) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'steps') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'title' => trim((string) ($item['title'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
            ];
            if (count($items) >= 6) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'countdown') {
        foreach (['title', 'text', 'countdown_date'] as $k) {
            $out[$k] = trim((string) ($out[$k] ?? ''));
        }
    } elseif ($type === 'columns') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'title' => trim((string) ($item['title'] ?? '')),
                'text' => trim((string) ($item['text'] ?? '')),
            ];
            if (count($items) >= 4) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } elseif ($type === 'badges') {
        $items = [];
        foreach ((array) ($out['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label !== '') {
                $items[] = ['label' => $label];
            }
            if (count($items) >= 20) {
                break;
            }
        }
        $out['items'] = $items !== [] ? $items : $default['items'];
        $out['section_title'] = trim((string) ($out['section_title'] ?? ''));
    } else {
        foreach (['title', 'subtitle', 'cta_text', 'cta_url', 'text', 'section_title'] as $k) {
            if (array_key_exists($k, $out)) {
                $out[$k] = trim((string) $out[$k]);
            }
        }
    }

    return $out;
}

/** @param array<string, mixed> $raw */
function hs_landing_builder_normalize(array $raw, array $user, array $t): array
{
    if (empty($raw['schema']) || (int) $raw['schema'] < 2 || !is_array($raw['blocks'] ?? null)) {
        $raw = hs_landing_migrate_legacy($raw, $t);
    }

    $base = hs_landing_builder_defaults($user, $t);
    $out = array_merge($base, $raw);
    $themes = hs_landing_themes($t);
    $theme = (string) ($out['theme'] ?? 'emerald');
    if (!isset($themes[$theme])) {
        $theme = 'emerald';
    }
    $out['theme'] = $theme;
    $color = (string) ($out['color'] ?? $themes[$theme]['color']);
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $color = $themes[$theme]['color'];
    }
    $out['color'] = $color;

    $iconSets = hs_landing_icon_sets($t);
    $iconSet = (string) ($out['icon_set'] ?? 'business');
    if (!isset($iconSets[$iconSet])) {
        $iconSet = 'business';
    }
    $out['icon_set'] = $iconSet;

    $iconStyles = hs_landing_icon_styles($t);
    $iconStyle = (string) ($out['icon_style'] ?? 'solid');
    if (!isset($iconStyles[$iconStyle])) {
        $iconStyle = 'solid';
    }
    $out['icon_style'] = $iconStyle;

    $galPalettes = hs_landing_gallery_palettes($t);
    $galPalette = (string) ($out['gallery_palette'] ?? 'brand');
    if (!isset($galPalettes[$galPalette])) {
        $galPalette = 'brand';
    }
    $out['gallery_palette'] = $galPalette;

    $navStyles = hs_landing_nav_styles($t);
    $navStyle = (string) ($out['nav_style'] ?? 'classic');
    if (!isset($navStyles[$navStyle])) {
        $navStyle = 'classic';
    }
    $out['nav_style'] = $navStyle;

    $burgerModes = hs_landing_burger_modes($t);
    $burger = (string) ($out['nav_burger'] ?? 'mobile');
    if (!isset($burgerModes[$burger])) {
        $burger = 'mobile';
    }
    $out['nav_burger'] = $burger;

    $msgStyles = hs_landing_msg_float_styles($t);
    $msgStyle = (string) ($out['msg_style'] ?? 'stack');
    if (!isset($msgStyles[$msgStyle])) {
        $msgStyle = 'stack';
    }
    $out['msg_style'] = $msgStyle;

    $msgPositions = hs_landing_msg_positions($t);
    $msgPos = (string) ($out['msg_position'] ?? 'right');
    if (!isset($msgPositions[$msgPos])) {
        $msgPos = 'right';
    }
    $out['msg_position'] = $msgPos;

    $out['msg_floating'] = !empty($out['msg_floating']) ? '1' : '';

    foreach (['msg_whatsapp', 'msg_telegram', 'msg_viber', 'msg_messenger', 'msg_signal', 'msg_skype', 'msg_line'] as $mk) {
        $out[$mk] = trim((string) ($out[$mk] ?? ''));
    }

    $footerStyles = hs_landing_footer_styles($t);
    $footer = (string) ($out['footer_style'] ?? 'minimal');
    if (!isset($footerStyles[$footer])) {
        $footer = 'minimal';
    }
    $out['footer_style'] = $footer;

    foreach (['business_name', 'tagline', 'footer_text', 'nav_cta_text', 'nav_cta_url', 'social_facebook', 'social_instagram', 'social_linkedin'] as $k) {
        $out[$k] = trim((string) ($out[$k] ?? ''));
    }
    $logoIcon = preg_replace('/[^a-z0-9-]/i', '', (string) ($out['logo_icon'] ?? ''));
    $out['logo_icon'] = $logoIcon === '' || in_array($logoIcon, hs_landing_logo_icon_choices(), true) ? $logoIcon : '';

    $nav = [];
    foreach ((array) ($out['nav_links'] ?? []) as $link) {
        if (!is_array($link)) {
            continue;
        }
        $nav[] = [
            'label' => trim((string) ($link['label'] ?? '')),
            'url' => trim((string) ($link['url'] ?? '#')),
            'on' => !empty($link['on']),
        ];
        if (count($nav) >= 8) {
            break;
        }
    }
    $out['nav_links'] = $nav !== [] ? $nav : $base['nav_links'];

    $blocks = [];
    foreach ((array) ($out['blocks'] ?? []) as $block) {
        if (!is_array($block)) {
            continue;
        }
        $blocks[] = hs_landing_normalize_block($block, $t);
        if (count($blocks) >= 40) {
            break;
        }
    }
    $out['blocks'] = $blocks !== [] ? $blocks : hs_landing_default_blocks($t);

    return $out;
}

/** @param array<string, mixed> $post */
function hs_landing_builder_from_post(array $post, array $user, array $t): array
{
    $blocksRaw = json_decode((string) ($post['blocks_json'] ?? '[]'), true);
    $navRaw = json_decode((string) ($post['nav_json'] ?? '[]'), true);

    return hs_landing_builder_normalize([
        'schema' => 2,
        'business_name' => $post['business_name'] ?? '',
        'tagline' => $post['tagline'] ?? '',
        'theme' => $post['theme'] ?? 'emerald',
        'color' => $post['color'] ?? '#059669',
        'icon_set' => $post['icon_set'] ?? 'business',
        'icon_style' => $post['icon_style'] ?? 'solid',
        'gallery_palette' => $post['gallery_palette'] ?? 'brand',
        'logo_icon' => $post['logo_icon'] ?? '',
        'nav_style' => $post['nav_style'] ?? 'classic',
        'nav_burger' => $post['nav_burger'] ?? 'mobile',
        'nav_cta_text' => $post['nav_cta_text'] ?? '',
        'nav_cta_url' => $post['nav_cta_url'] ?? '#contact',
        'msg_whatsapp' => $post['msg_whatsapp'] ?? '',
        'msg_telegram' => $post['msg_telegram'] ?? '',
        'msg_viber' => $post['msg_viber'] ?? '',
        'msg_messenger' => $post['msg_messenger'] ?? '',
        'msg_signal' => $post['msg_signal'] ?? '',
        'msg_skype' => $post['msg_skype'] ?? '',
        'msg_line' => $post['msg_line'] ?? '',
        'msg_floating' => $post['msg_floating'] ?? '',
        'msg_style' => $post['msg_style'] ?? 'stack',
        'msg_position' => $post['msg_position'] ?? 'right',
        'footer_style' => $post['footer_style'] ?? 'minimal',
        'footer_text' => $post['footer_text'] ?? '',
        'social_facebook' => $post['social_facebook'] ?? '',
        'social_instagram' => $post['social_instagram'] ?? '',
        'social_linkedin' => $post['social_linkedin'] ?? '',
        'nav_links' => is_array($navRaw) ? $navRaw : [],
        'blocks' => is_array($blocksRaw) ? $blocksRaw : [],
        'published_at' => $post['published_at'] ?? '',
        'published_url' => $post['published_url'] ?? '',
    ], $user, $t);
}

function hs_site_is_landing_builder(array $site): bool
{
    return (string) ($site['app'] ?? '') === 'landing';
}

function hs_landing_publish_dir(array $user): string
{
    return hs_public_path(hs_install_default_base($user));
}

function hs_landing_public_url(array $user): string
{
    global $site_url;

    return rtrim((string) $site_url, '/') . '/' . HS_PUBLIC_HTML . '/' . hs_install_default_base($user) . '/';
}

function hs_landing_gallery_rel(): string
{
    return 'images';
}

function hs_landing_sanitize_gallery_image(string $path): string
{
    $path = hs_fm_norm_rel($path);
    if ($path === '' || !preg_match('/^(?:gallery|images)\/[a-zA-Z0-9._-]+$/', $path)) {
        return '';
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return '';
    }

    return $path;
}

function hs_landing_gallery_public_url(array $user, string $imageRel): string
{
    $imageRel = hs_landing_sanitize_gallery_image($imageRel);
    if ($imageRel === '') {
        return '';
    }

    return hs_landing_public_url($user) . $imageRel;
}

function hs_landing_gallery_ensure_dir(array $user): string
{
    $rel = hs_landing_gallery_rel();
    $dir = hs_fm_resolve($user, $rel);
    if ($dir === null) {
        $root = hs_fm_user_root($user);
        $dir = rtrim($root, '/\\') . '/' . $rel;
    }
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return '';
    }

    return $dir;
}

/** @return list<array{path:string,name:string,url:string}> */
function hs_landing_gallery_list(array $user): array
{
    $out = [];
    foreach (['images', 'gallery'] as $rel) {
        $dir = hs_fm_resolve($user, $rel);
        if ($dir === null || !is_dir($dir)) {
            continue;
        }
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..' || !hs_fm_is_image($name)) {
                continue;
            }
            $path = $rel . '/' . $name;
            if (hs_landing_sanitize_gallery_image($path) === '') {
                continue;
            }
            $out[] = [
                'path' => $path,
                'name' => $name,
                'url' => hs_landing_gallery_public_url($user, $path),
            ];
        }
    }
    usort($out, static fn (array $a, array $b): int => strcmp($b['name'], $a['name']));

    return $out;
}

/** @return array{ok:bool,path?:string,url?:string,name?:string,error?:string} */
function hs_landing_gallery_upload(array $user, array $file): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'no_file'];
    }
    $orig = basename((string) ($file['name'] ?? 'image.jpg'));
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return ['ok' => false, 'error' => 'invalid_type'];
    }
    if (($file['size'] ?? 0) > 5242880) {
        return ['ok' => false, 'error' => 'too_large'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => 'invalid_type'];
    }
    $dir = hs_landing_gallery_ensure_dir($user);
    if ($dir === '') {
        return ['ok' => false, 'error' => 'mkdir_failed'];
    }
    $safeExt = $ext === 'jpeg' ? 'jpg' : $ext;
    $name = 'img_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $target = rtrim($dir, '/\\') . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'upload_failed'];
    }
    $path = hs_landing_gallery_rel() . '/' . $name;

    return [
        'ok' => true,
        'path' => $path,
        'name' => $name,
        'url' => hs_landing_gallery_public_url($user, $path),
    ];
}

/** @param array<string, mixed> $data */
function hs_landing_resolve_color(array $data): string
{
    $themes = hs_landing_themes([]);
    $theme = (string) ($data['theme'] ?? 'emerald');
    $custom = (string) ($data['color'] ?? '');
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $custom)) {
        return $custom;
    }

    return $themes[$theme]['color'] ?? '#059669';
}

/** @param array<string, mixed> $block */
function hs_landing_block_style_attr(array $block): string
{
    $parts = [];
    $color = (string) ($block['color'] ?? '');
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $safe = htmlspecialchars($color, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts[] = '--c:' . $safe;
        $parts[] = '--c2:color-mix(in srgb,' . $safe . ' 75%,#000)';
    }
    foreach (['text_color' => '--elb-text', 'heading_color' => '--elb-heading', 'btn_color' => '--elb-btn', 'btn_text_color' => '--elb-btn-text'] as $key => $var) {
        $val = (string) ($block[$key] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
            $parts[] = $var . ':' . htmlspecialchars($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    if ($parts === []) {
        return '';
    }

    return ' style="' . implode(';', $parts) . '"';
}

function hs_landing_block_has_element_colors(array $block): bool
{
    foreach (['text_color', 'heading_color', 'btn_color', 'btn_text_color'] as $key) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($block[$key] ?? ''))) {
            return true;
        }
    }

    return false;
}

function hs_landing_block_element_color_css(): string
{
    return 'section.elb-custom-colors .sec-title,section.elb-custom-colors h1,section.elb-custom-colors h2,section.elb-custom-colors h3,section.elb-custom-colors h4,section.elb-custom-colors .feat h3{color:var(--elb-heading)}'
        . 'section.elb-custom-colors p,section.elb-custom-colors li,section.elb-custom-colors blockquote,section.elb-custom-colors cite,section.elb-custom-colors .about-text,section.elb-custom-colors .text-inner p,section.elb-custom-colors .heading-sub,section.elb-custom-colors .feat p,section.elb-custom-colors .feat-list-item p,section.elb-custom-colors .test-card p,section.elb-custom-colors .test-card footer span,section.elb-custom-colors .price-feats,section.elb-custom-colors .price-feats li,section.elb-custom-colors .price-amt span,section.elb-custom-colors .faq-acc p,section.elb-custom-colors .faq-item p,section.elb-custom-colors .info-text-box p,section.elb-custom-colors .info-quote blockquote,section.elb-custom-colors .info-quote cite,section.elb-custom-colors .stat span,section.elb-custom-colors .col-item p,section.elb-custom-colors .event-loc,section.elb-custom-colors .cd-unit span,section.elb-custom-colors .image-figure figcaption,section.elb-custom-colors .quote-block cite,section.elb-custom-colors .acc-item p,section.elb-custom-colors .tabs-panel,section.elb-custom-colors .cta-banner-inner p,section.elb-custom-colors .cta-inline-inner p,section.elb-custom-colors .cta-split-grid p,section.elb-custom-colors .contact-box p,section.elb-custom-colors .contact-split-grid p,section.elb-custom-colors .hero p,section.elb-custom-colors .hero-card span,section.elb-custom-colors .promo-inner span,section.elb-custom-colors .svc-card p,section.elb-custom-colors .team-member p,section.elb-custom-colors .tl-item p{color:var(--elb-text)}'
        . 'section.elb-custom-colors .test-card footer strong,section.elb-custom-colors .col-item h3{color:var(--elb-heading)}'
        . 'section.elb-custom-colors .btn,section.elb-custom-colors a.btn{background:var(--elb-btn,var(--c));color:var(--elb-btn-text,#fff)}';
}

function hs_landing_inject_block_color(string $html, array $block): string
{
    if ($html === '') {
        return '';
    }
    $attr = hs_landing_block_style_attr($block);
    $hasColors = hs_landing_block_has_element_colors($block);
    if ($attr === '' && !$hasColors) {
        return $html;
    }
    $out = $html;
    if ($hasColors) {
        if (preg_match('/<section\s+class="/', $out)) {
            $out = preg_replace('/<section\s+class="/', '<section class="elb-custom-colors ', $out, 1) ?? $out;
        } else {
            $out = preg_replace('/<section/', '<section class="elb-custom-colors"', $out, 1) ?? $out;
        }
    }
    if ($attr !== '') {
        $out = preg_replace('/<section\s/', '<section' . $attr . ' ', $out, 1) ?? $out;
    }

    return $out;
}

/** @param array<string, mixed> $data */
function hs_landing_wrap_block_background(string $html, array $block, array $data): string
{
    $type = (string) ($block['bg_type'] ?? '');
    if ($type === '' || !preg_match('/^(<section[^>]*>)([\s\S]*)(<\/section>)$/i', $html, $m)) {
        return $html;
    }
    $open = $m[1];
    $inner = $m[2];
    $close = $m[3];
    $overlay = max(0, min(100, (int) ($block['bg_overlay'] ?? 40))) / 100;
    $bgDiv = '';
    if ($type === 'image') {
        $rel = (string) ($block['bg_image'] ?? '');
        if ($rel === '') {
            return $html;
        }
        $user = is_array($data['_render_user'] ?? null) ? $data['_render_user'] : null;
        $url = $user !== null ? hs_landing_gallery_public_url($user, $rel) : '';
        if ($url === '') {
            return $html;
        }
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $bgDiv = '<div class="elb-block-bg" style="background-image:url(\'' . $safeUrl . '\');background-size:cover;background-position:center;background-repeat:no-repeat"></div>';
    } elseif ($type === 'color') {
        $col = (string) ($block['bg_color'] ?? '');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $col)) {
            return $html;
        }
        $bgDiv = '<div class="elb-block-bg" style="background:' . htmlspecialchars($col, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ';background-size:cover;background-position:center"></div>';
    } else {
        return $html;
    }
    if (preg_match('/\bclass="/', $open)) {
        $open = preg_replace('/\bclass="/', 'class="elb-has-bg ', $open, 1);
    } else {
        $open = str_replace('<section', '<section class="elb-has-bg"', $open);
    }
    $overlayCss = '--elb-bg-overlay:' . $overlay;
    if (preg_match('/\bstyle="/', $open)) {
        $open = preg_replace('/\bstyle="/', 'style="' . $overlayCss . ';', $open, 1);
    } else {
        $open = str_replace('<section', '<section style="' . $overlayCss . '"', $open);
    }

    return $open . $bgDiv . '<div class="elb-block-bg-overlay" aria-hidden="true"></div><div class="elb-block-inner">' . $inner . '</div>' . $close;
}

/** @param array<string, mixed> $data */
function hs_landing_finalize_block_html(string $html, array $block, array $data): string
{
    return hs_landing_wrap_block_background(hs_landing_inject_block_color($html, $block), $block, $data);
}

function hs_landing_logo_icon_picker_html(string $current, array $t, string $iconStyle = 'solid'): string
{
    $icons = hs_landing_logo_icon_choices();
    $prefix = $iconStyle === 'regular' ? 'fa-regular' : 'fa-solid';
    $html = '<input type="hidden" name="logo_icon" value="' . hs_h($current) . '" data-landing-logo-icon-input data-landing-sync>';
    $html .= '<div class="hs-landing-logo-icon-picker" data-landing-logo-icon-grid>';
    $html .= '<button type="button" class="hs-landing-logo-icon-pick' . ($current === '' ? ' is-active' : '') . '" data-logo-icon-pick="" title="' . hs_h($t['landing_logo_icon_none'] ?? 'No icon') . '"><i class="fa-solid fa-ban"></i></button>';
    foreach ($icons as $ic) {
        $html .= '<button type="button" class="hs-landing-logo-icon-pick' . ($current === $ic ? ' is-active' : '') . '" data-logo-icon-pick="' . hs_h($ic) . '" title="' . hs_h($ic) . '">'
            . '<i class="' . hs_h($prefix . ' ' . $ic) . '"></i></button>';
    }

    return $html . '</div>';
}

function hs_landing_video_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([a-zA-Z0-9_-]{6,})#', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }

    return '';
}

/**
 * @param array<string, mixed> $block
 * @param array<string, mixed> $data
 * @param callable(string): string $h
 */
function hs_landing_render_block(array $block, array $data, callable $h): string
{
    if (empty($block['on'])) {
        return '';
    }
    $registry = hs_landing_block_registry([]);
    $type = (string) ($block['type'] ?? '');
    $anchor = (string) ($registry[$type]['anchor'] ?? '');
    $idAttr = $anchor !== '' ? ' id="' . $h($anchor) . '"' : '';
    $name = (string) ($data['business_name'] ?? '');

    $extHtml = hs_landing_render_block_ext($block, $data, $h);
    if ($extHtml !== null) {
        return hs_landing_finalize_block_html($extHtml, $block, $data);
    }

    $html = match ($type) {
        'hero' => hs_landing_render_hero($block, $data, $h, $name),
        'features' => hs_landing_render_features($block, $data, $h, $idAttr),
        'about' => hs_landing_render_about($block, $data, $h, $idAttr, $name),
        'gallery' => hs_landing_render_gallery($block, $data, $h, $idAttr),
        'info' => hs_landing_render_info($block, $h, $idAttr),
        'contact' => hs_landing_render_contact($block, $h, $idAttr),
        'cta' => hs_landing_render_cta($block, $h, $idAttr),
        'testimonials' => hs_landing_render_testimonials($block, $h, $idAttr),
        'pricing' => hs_landing_render_pricing($block, $h, $idAttr),
        'faq' => hs_landing_render_faq($block, $h, $idAttr),
        'team' => hs_landing_render_team($block, $h, $idAttr),
        'logos' => hs_landing_render_logos($block, $h, $idAttr),
        'video' => hs_landing_render_video($block, $h, $idAttr),
        'newsletter' => hs_landing_render_newsletter($block, $h, $idAttr),
        'timeline' => hs_landing_render_timeline($block, $h, $idAttr),
        'services' => hs_landing_render_services($block, $data, $h, $idAttr),
        'heading' => hs_landing_render_heading($block, $h, $idAttr),
        'text' => hs_landing_render_text_block($block, $h, $idAttr),
        'image' => hs_landing_render_image_block($block, $h, $idAttr),
        'divider' => hs_landing_render_divider($block, $h),
        'spacer' => hs_landing_render_spacer($block, $h),
        'hours' => hs_landing_render_hours($block, $h, $idAttr),
        'map' => hs_landing_render_map($block, $h, $idAttr),
        'banner' => hs_landing_render_banner($block, $h),
        'quote' => hs_landing_render_quote_block($block, $h),
        'download' => hs_landing_render_download($block, $h, $idAttr),
        'alert' => hs_landing_render_alert($block, $h),
        'events' => hs_landing_render_events($block, $h, $idAttr),
        'steps' => hs_landing_render_steps($block, $h, $idAttr),
        'countdown' => hs_landing_render_countdown($block, $h, $idAttr),
        'columns' => hs_landing_render_columns($block, $h),
        'badges' => hs_landing_render_badges($block, $h),
        default => '',
    };

    return hs_landing_finalize_block_html($html, $block, $data);
}

/** @param array<string, mixed> $block @param array<string, mixed> $data */
function hs_landing_render_hero(array $block, array $data, callable $h, string $name): string
{
    $variant = (string) ($block['variant'] ?? 'split');
    $title = (string) ($block['title'] ?? '');
    $sub = (string) ($block['subtitle'] ?? '');
    $cta = (string) ($block['cta_text'] ?? 'Contact');
    $ctaUrl = (string) ($block['cta_url'] ?? '#contact');
    $btn = '<a class="btn primary" href="' . $h($ctaUrl) . '"><i class="fa-solid fa-arrow-right"></i> ' . $h($cta) . '</a>';

    if ($variant === 'centered') {
        return '<section class="hero hero-centered"><div class="wrap hero-centered-inner">'
            . '<div class="badge"><i class="fa-solid fa-rocket"></i> ' . $h($name) . '</div>'
            . '<h1>' . $h($title) . '</h1><p>' . $h($sub) . '</p>' . $btn . '</div></section>';
    }
    if ($variant === 'minimal') {
        return '<section class="hero hero-minimal"><div class="wrap"><h1>' . $h($title) . '</h1>'
            . '<p>' . $h($sub) . '</p>' . $btn . '</div></section>';
    }

    return '<section class="hero"><div class="wrap hero-grid">'
        . '<div><div class="badge"><i class="fa-solid fa-rocket"></i> ' . $h($name) . '</div>'
        . '<h1>' . $h($title) . '</h1><p>' . $h($sub) . '</p>' . $btn . '</div>'
        . '<div class="hero-card"><div><span>Status</span><strong>Live</strong></div>'
        . '<div><span>Hosting</span><strong>BILOHASH</strong></div>'
        . '<div><span>SSL</span><strong>Active</strong></div></div></div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_features(array $block, array $data, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'grid');
    $secTitle = (string) ($block['section_title'] ?? 'Features');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $f) {
        if (!is_array($f)) {
            continue;
        }
        $icon = preg_replace('/[^a-z0-9-]/i', '', (string) ($f['icon'] ?? 'fa-star')) ?: 'fa-star';
        $iconHtml = hs_landing_icon_tag($icon, $data);
        $title = (string) ($f['title'] ?? '');
        $text = (string) ($f['text'] ?? '');
        if ($variant === 'list') {
            $items .= '<div class="feat-list-item"><div class="feat-icon">' . $iconHtml . '</div>'
                . '<div><h3>' . $h($title) . '</h3><p>' . $h($text) . '</p></div></div>';
        } else {
            $cls = $variant === 'cards' ? 'feat feat-card' : 'feat';
            $items .= '<article class="' . $cls . '"><div class="feat-icon">' . $iconHtml . '</div>'
                . '<h3>' . $h($title) . '</h3><p>' . $h($text) . '</p></article>';
        }
    }
    if ($items === '') {
        return '';
    }
    $inner = $variant === 'list'
        ? '<div class="feat-list">' . $items . '</div>'
        : '<div class="feat-grid">' . $items . '</div>';

    return '<section class="features"' . $idAttr . '><div class="wrap"><h2 class="sec-title">' . $h($secTitle) . '</h2>' . $inner . '</div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_about(array $block, array $data, callable $h, string $idAttr, string $name): string
{
    $variant = (string) ($block['variant'] ?? 'split');
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $tagline = (string) ($data['tagline'] ?? '');
    if ($title === '' && $text === '') {
        return '';
    }
    if ($variant === 'centered') {
        return '<section class="about about-centered"' . $idAttr . '><div class="wrap about-centered-inner">'
            . '<h2 class="sec-title">' . $h($title) . '</h2><p class="about-text">' . nl2br($h($text)) . '</p></div></section>';
    }

    return '<section class="about"' . $idAttr . '><div class="wrap about-grid">'
        . '<div><h2 class="sec-title">' . $h($title) . '</h2><p class="about-text">' . nl2br($h($text)) . '</p></div>'
        . '<div class="about-card"><i class="fa-solid fa-building"></i><strong>' . $h($name) . '</strong><span>' . $h($tagline) . '</span></div>'
        . '</div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_gallery(array $block, array $data, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'grid');
    $secTitle = (string) ($block['section_title'] ?? 'Gallery');
    $blockItems = (array) ($block['items'] ?? []);
    $paletteHues = hs_landing_gallery_hues($data, max(6, count($blockItems)));
    $items = '';
    $i = 0;
    foreach ($blockItems as $g) {
        if (!is_array($g)) {
            continue;
        }
        $cap = (string) ($g['caption'] ?? '');
        $image = hs_landing_sanitize_gallery_image((string) ($g['image'] ?? ''));
        if ($image !== '') {
            $capHtml = $cap !== '' ? '<figcaption>' . $h($cap) . '</figcaption>' : '';
            $items .= '<figure class="gal-item gal-item-photo"><img src="' . $h($image) . '" alt="' . $h($cap !== '' ? $cap : 'Gallery') . '" loading="lazy">'
                . $capHtml . '</figure>';
        } else {
            $hue = (int) ($g['hue'] ?? $paletteHues[$i] ?? 160);
            $hue2 = ($hue + 40) % 360;
            $style = 'background:linear-gradient(135deg,hsl(' . $hue . ',65%,45%),hsl(' . $hue2 . ',70%,55%))';
            $items .= '<figure class="gal-item" style="' . $h($style) . '"><figcaption>' . $h($cap) . '</figcaption></figure>';
        }
        $i++;
    }
    if ($items === '') {
        return '';
    }
    $cls = 'gal-' . ($variant === 'masonry' ? 'masonry' : ($variant === 'row' ? 'row' : 'grid'));

    return '<section class="gallery"' . $idAttr . '><div class="wrap"><h2 class="sec-title">' . $h($secTitle) . '</h2>'
        . '<div class="' . $h($cls) . '">' . $items . '</div></div></section>';
}

function hs_landing_render_info(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'stats');
    if ($variant === 'quote') {
        $quote = (string) ($block['quote'] ?? '');
        $author = (string) ($block['author'] ?? '');
        if ($quote === '') {
            return '';
        }

        return '<section class="info info-quote"' . $idAttr . '><div class="wrap"><blockquote>“' . $h($quote) . '”</blockquote>'
            . ($author !== '' ? '<cite>— ' . $h($author) . '</cite>' : '') . '</div></section>';
    }
    if ($variant === 'text') {
        $title = (string) ($block['title'] ?? '');
        $text = (string) ($block['text'] ?? '');
        if ($title === '' && $text === '') {
            return '';
        }

        return '<section class="info info-text"' . $idAttr . '><div class="wrap info-text-box">'
            . ($title !== '' ? '<h2 class="sec-title">' . $h($title) . '</h2>' : '')
            . '<p>' . nl2br($h($text)) . '</p></div></section>';
    }
    $stats = '';
    foreach ((array) ($block['stats'] ?? []) as $s) {
        if (!is_array($s)) {
            continue;
        }
        $stats .= '<div class="stat"><strong>' . $h((string) ($s['value'] ?? '')) . '</strong><span>' . $h((string) ($s['label'] ?? '')) . '</span></div>';
    }
    if ($stats === '') {
        return '';
    }
    $title = (string) ($block['title'] ?? '');

    return '<section class="info info-stats"' . $idAttr . '><div class="wrap">'
        . ($title !== '' ? '<h2 class="sec-title">' . $h($title) . '</h2>' : '')
        . '<div class="stat-grid">' . $stats . '</div></div></section>';
}

function hs_landing_render_contact(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'card');
    $title = (string) ($block['title'] ?? 'Contact');
    $phone = (string) ($block['phone'] ?? '');
    $email = (string) ($block['email'] ?? '');
    $address = (string) ($block['address'] ?? '');
    $cta = (string) ($block['cta_text'] ?? $title);
    $ctaUrl = (string) ($block['cta_url'] ?? '#contact');
    $lines = '';
    if ($phone !== '') {
        $lines .= '<a href="tel:' . $h(preg_replace('/\s+/', '', $phone) ?? '') . '"><i class="fa-solid fa-phone"></i> ' . $h($phone) . '</a>';
    }
    if ($email !== '') {
        $lines .= '<a href="mailto:' . $h($email) . '"><i class="fa-solid fa-envelope"></i> ' . $h($email) . '</a>';
    }
    if ($address !== '') {
        $lines .= '<p class="addr"><i class="fa-solid fa-location-dot"></i> ' . $h($address) . '</p>';
    }
    $btn = '<a class="btn primary" href="' . $h($ctaUrl) . '">' . $h($cta) . '</a>';
    $cls = 'contact contact-' . $h($variant);

    if ($variant === 'split') {
        return '<section class="' . $cls . '"' . $idAttr . '><div class="wrap contact-split-grid">'
            . '<div><h2 class="sec-title">' . $h($title) . '</h2><div class="contact-lines">' . $lines . '</div></div>'
            . '<div class="contact-split-cta">' . $btn . '</div></div></section>';
    }
    if ($variant === 'minimal') {
        return '<section class="' . $cls . '"' . $idAttr . '><div class="wrap contact-minimal-inner">'
            . '<h2>' . $h($title) . '</h2><div class="contact-lines">' . $lines . '</div>' . $btn . '</div></section>';
    }

    return '<section class="' . $cls . '"' . $idAttr . '><div class="wrap contact-box">'
        . '<h2 class="sec-title light">' . $h($title) . '</h2>'
        . '<div class="contact-lines">' . $lines . '</div>' . $btn . '</div></section>';
}

function hs_landing_render_cta(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'banner');
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $cta = (string) ($block['cta_text'] ?? 'Contact');
    $ctaUrl = (string) ($block['cta_url'] ?? '#contact');
    if ($title === '' && $text === '') {
        return '';
    }
    $btn = '<a class="btn primary" href="' . $h($ctaUrl) . '">' . $h($cta) . '</a>';
    $cls = 'cta cta-' . $variant;
    if ($variant === 'inline') {
        return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap cta-inline-inner">'
            . '<div><strong>' . $h($title) . '</strong><span>' . $h($text) . '</span></div>' . $btn . '</div></section>';
    }
    if ($variant === 'split') {
        return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap cta-split-grid">'
            . '<div><h2 class="sec-title">' . $h($title) . '</h2><p>' . $h($text) . '</p></div>'
            . '<div class="cta-split-btn">' . $btn . '</div></div></section>';
    }

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap cta-banner-inner">'
        . '<h2>' . $h($title) . '</h2><p>' . $h($text) . '</p>' . $btn . '</div></section>';
}

function hs_landing_render_testimonials(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'cards');
    $secTitle = (string) ($block['section_title'] ?? '');
    $items = (array) ($block['items'] ?? []);
    if ($items === []) {
        return '';
    }
    if ($variant === 'quote') {
        $first = is_array($items[0] ?? null) ? $items[0] : [];
        $text = (string) ($first['text'] ?? '');
        if ($text === '') {
            return '';
        }

        return '<section class="testimonials test-quote"' . $idAttr . '><div class="wrap test-quote-inner">'
            . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
            . '<blockquote>“' . $h($text) . '”</blockquote>'
            . '<cite>' . $h((string) ($first['name'] ?? '')) . '</cite>'
            . '<span class="test-role">' . $h((string) ($first['role'] ?? '')) . '</span></div></section>';
    }
    $html = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $html .= '<article class="test-card"><p>“' . $h((string) ($item['text'] ?? '')) . '”</p>'
            . '<footer><strong>' . $h((string) ($item['name'] ?? '')) . '</strong>'
            . '<span>' . $h((string) ($item['role'] ?? '')) . '</span></footer></article>';
    }
    $inner = $variant === 'grid' ? '<div class="test-grid">' . $html . '</div>' : '<div class="test-cards">' . $html . '</div>';

    return '<section class="testimonials"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '') . $inner . '</div></section>';
}

function hs_landing_render_pricing(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'cards');
    $secTitle = (string) ($block['section_title'] ?? '');
    $items = (array) ($block['items'] ?? []);
    if ($items === []) {
        return '';
    }
    if ($variant === 'table') {
        $rows = '';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $feats = implode(', ', array_map(static fn ($f) => (string) $f, (array) ($item['features'] ?? [])));
            $rows .= '<tr><td>' . $h((string) ($item['name'] ?? '')) . '</td><td><strong>' . $h((string) ($item['price'] ?? ''))
                . $h((string) ($item['period'] ?? '')) . '</strong></td><td>' . $h($feats) . '</td></tr>';
        }

        return '<section class="pricing pricing-table"' . $idAttr . '><div class="wrap">'
            . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
            . '<table class="price-table"><thead><tr><th>Plan</th><th>Price</th><th>Includes</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div></section>';
    }
    $html = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $featHtml = '';
        foreach ((array) ($item['features'] ?? []) as $f) {
            $featHtml .= '<li><i class="fa-solid fa-check"></i> ' . $h((string) $f) . '</li>';
        }
        $featured = !empty($item['featured']) ? ' price-card-featured' : '';
        $html .= '<article class="price-card' . $featured . '"><h3>' . $h((string) ($item['name'] ?? '')) . '</h3>'
            . '<div class="price-amt"><strong>' . $h((string) ($item['price'] ?? '')) . '</strong>'
            . '<span>' . $h((string) ($item['period'] ?? '')) . '</span></div>'
            . '<ul class="price-feats">' . $featHtml . '</ul>'
            . '<a class="btn primary" href="#contact">' . $h((string) ($item['cta_text'] ?? 'Choose')) . '</a></article>';
    }
    $cls = $variant === 'compact' ? 'price-compact' : 'price-cards';

    return '<section class="pricing"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_faq(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'accordion');
    $secTitle = (string) ($block['section_title'] ?? '');
    $items = (array) ($block['items'] ?? []);
    if ($items === []) {
        return '';
    }
    $html = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $q = (string) ($item['q'] ?? '');
        $a = (string) ($item['a'] ?? '');
        if ($q === '') {
            continue;
        }
        if ($variant === 'accordion') {
            $html .= '<details class="faq-acc"><summary>' . $h($q) . '</summary><p>' . $h($a) . '</p></details>';
        } else {
            $html .= '<div class="faq-item"><h3>' . $h($q) . '</h3><p>' . $h($a) . '</p></div>';
        }
    }
    $innerCls = $variant === 'twocol' ? 'faq-twocol' : 'faq-list';
    $inner = '<div class="' . $h($innerCls) . '">' . $html . '</div>';

    return '<section class="faq"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '') . $inner . '</div></section>';
}

function hs_landing_render_team(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'grid');
    $secTitle = (string) ($block['section_title'] ?? '');
    $items = (array) ($block['items'] ?? []);
    if ($items === []) {
        return '';
    }
    $html = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = (string) ($item['name'] ?? '');
        $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';
        $html .= '<article class="team-member">'
            . '<div class="team-avatar">' . $h($initial) . '</div>'
            . '<h3>' . $h($name) . '</h3><span class="team-role">' . $h((string) ($item['role'] ?? '')) . '</span>'
            . '<p>' . $h((string) ($item['bio'] ?? '')) . '</p></article>';
    }
    $cls = $variant === 'list' ? 'team-list' : ($variant === 'cards' ? 'team-cards' : 'team-grid');

    return '<section class="team"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_logos(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'row');
    $secTitle = (string) ($block['section_title'] ?? '');
    $items = (array) ($block['items'] ?? []);
    if ($items === []) {
        return '';
    }
    $html = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = (string) ($item['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $html .= '<span class="logo-pill">' . $h($name) . '</span>';
    }
    $cls = $variant === 'grid' ? 'logos-grid' : ($variant === 'strip' ? 'logos-strip' : 'logos-row');

    return '<section class="logos"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title logos-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_video(array $block, callable $h, string $idAttr): string
{
    $url = (string) ($block['video_url'] ?? '');
    $embed = hs_landing_video_embed_url($url);
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'embed');
    if ($embed === '' && $url === '') {
        return '';
    }
    $inner = '';
    if ($variant === 'link' && $url !== '') {
        $inner = '<a class="video-link-card" href="' . $h($url) . '" target="_blank" rel="noopener">'
            . '<i class="fa-solid fa-circle-play"></i><span>' . $h($secTitle !== '' ? $secTitle : 'Watch video') . '</span></a>';
    } elseif ($embed !== '') {
        $inner = '<div class="video-embed"><iframe src="' . $h($embed) . '" title="Video" loading="lazy" allowfullscreen></iframe></div>';
    } else {
        $inner = '<a class="btn primary" href="' . $h($url) . '" target="_blank" rel="noopener"><i class="fa-solid fa-circle-play"></i> ' . $h($secTitle !== '' ? $secTitle : 'Watch') . '</a>';
    }

    return '<section class="video-block"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' && $variant === 'embed' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . $inner . '</div></section>';
}

function hs_landing_render_newsletter(array $block, callable $h, string $idAttr): string
{
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $cta = (string) ($block['cta_text'] ?? 'Subscribe');
    $variant = (string) ($block['variant'] ?? 'card');
    if ($title === '' && $text === '') {
        return '';
    }
    $form = '<form class="newsletter-form" action="#" method="post" onsubmit="return false">'
        . '<input type="email" placeholder="email@example.com" aria-label="Email">'
        . '<button type="submit" class="btn primary">' . $h($cta) . '</button></form>';
    $cls = 'newsletter newsletter-' . $variant;
    if ($variant === 'banner') {
        return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap newsletter-banner-inner">'
            . '<div><strong>' . $h($title) . '</strong><span>' . $h($text) . '</span></div>' . $form . '</div></section>';
    }
    if ($variant === 'inline') {
        return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap newsletter-inline-inner">'
            . '<div><strong>' . $h($title) . '</strong></div>' . $form . '</div></section>';
    }

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap newsletter-card">'
        . '<h2 class="sec-title">' . $h($title) . '</h2><p>' . $h($text) . '</p>' . $form . '</div></section>';
}

function hs_landing_render_timeline(array $block, callable $h, string $idAttr): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'vertical');
    $html = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $html .= '<article class="tl-item"><span class="tl-year">' . $h((string) ($item['year'] ?? '')) . '</span>'
            . '<h3>' . $h((string) ($item['title'] ?? '')) . '</h3><p>' . $h((string) ($item['text'] ?? '')) . '</p></article>';
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'cards' ? 'timeline timeline-cards' : 'timeline timeline-vertical';

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="tl-list">' . $html . '</div></div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_services(array $block, array $data, callable $h, string $idAttr): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'grid');
    $html = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $icon = preg_replace('/[^a-z0-9-]/i', '', (string) ($item['icon'] ?? 'fa-star')) ?: 'fa-star';
        $price = (string) ($item['price'] ?? '');
        $html .= '<article class="svc-item"><div class="svc-icon">' . hs_landing_icon_tag($icon, $data) . '</div>'
            . '<h3>' . $h((string) ($item['title'] ?? '')) . '</h3>'
            . ($price !== '' ? '<span class="svc-price">' . $h($price) . '</span>' : '')
            . '<p>' . $h((string) ($item['text'] ?? '')) . '</p></article>';
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'list' ? 'svc-list' : ($variant === 'cards' ? 'svc-cards' : 'svc-grid');

    return '<section class="services"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_heading(array $block, callable $h, string $idAttr): string
{
    $title = (string) ($block['title'] ?? '');
    $sub = (string) ($block['subtitle'] ?? '');
    if ($title === '') {
        return '';
    }
    $variant = (string) ($block['variant'] ?? 'center');
    $cls = 'heading-block heading-' . $variant;

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap">'
        . '<h2 class="sec-title">' . $h($title) . '</h2>'
        . ($sub !== '' ? '<p class="heading-sub">' . $h($sub) . '</p>' : '') . '</div></section>';
}

function hs_landing_render_text_block(array $block, callable $h, string $idAttr): string
{
    $text = (string) ($block['text'] ?? '');
    if ($text === '') {
        return '';
    }
    $title = (string) ($block['title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'plain');
    $cls = 'text-block text-' . $variant;

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap text-inner">'
        . ($title !== '' ? '<h2 class="sec-title">' . $h($title) . '</h2>' : '')
        . '<p>' . nl2br($h($text)) . '</p></div></section>';
}

function hs_landing_render_image_block(array $block, callable $h, string $idAttr): string
{
    $image = hs_landing_sanitize_gallery_image((string) ($block['image'] ?? ''));
    $caption = (string) ($block['caption'] ?? '');
    $variant = (string) ($block['variant'] ?? 'contained');
    if ($image === '') {
        return '';
    }
    $cls = 'image-block image-' . $variant;
    $capHtml = $caption !== '' ? '<figcaption>' . $h($caption) . '</figcaption>' : '';

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap">'
        . '<figure class="image-figure"><img src="' . $h($image) . '" alt="' . $h($caption !== '' ? $caption : 'Image') . '" loading="lazy">' . $capHtml . '</figure></div></section>';
}

function hs_landing_render_divider(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'line');
    $cls = 'divider divider-' . $variant;

    return '<section class="' . $h($cls) . '"><div class="wrap"><hr aria-hidden="true"></div></section>';
}

function hs_landing_render_spacer(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'md');
    $cls = 'spacer spacer-' . $variant;

    return '<section class="' . $h($cls) . '" aria-hidden="true"></section>';
}

function hs_landing_render_hours(array $block, callable $h, string $idAttr): string
{
    $title = (string) ($block['title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'table');
    $rows = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $day = (string) ($item['day'] ?? '');
        $hours = (string) ($item['hours'] ?? '');
        if ($variant === 'cards') {
            $rows .= '<article class="hours-card"><strong>' . $h($day) . '</strong><span>' . $h($hours) . '</span></article>';
        } else {
            $rows .= '<div class="hours-row"><span>' . $h($day) . '</span><strong>' . $h($hours) . '</strong></div>';
        }
    }
    if ($rows === '') {
        return '';
    }
    $inner = $variant === 'cards' ? '<div class="hours-cards">' . $rows . '</div>' : '<div class="hours-table">' . $rows . '</div>';

    return '<section class="hours-block"' . $idAttr . '><div class="wrap">'
        . ($title !== '' ? '<h2 class="sec-title">' . $h($title) . '</h2>' : '') . $inner . '</div></section>';
}

function hs_landing_render_map(array $block, callable $h, string $idAttr): string
{
    $title = (string) ($block['title'] ?? '');
    $address = (string) ($block['address'] ?? '');
    $embed = (string) ($block['embed_url'] ?? '');
    $variant = (string) ($block['variant'] ?? 'embed');
    if ($address === '' && $embed === '') {
        return '';
    }
    $mapHtml = '';
    if ($embed !== '' && $variant === 'embed') {
        $mapHtml = '<div class="map-embed"><iframe src="' . $h($embed) . '" title="Map" loading="lazy"></iframe></div>';
    } elseif ($address !== '') {
        $mapHtml = '<div class="map-placeholder"><i class="fa-solid fa-map-location-dot"></i><p>' . $h($address) . '</p></div>';
    }

    return '<section class="map-block"' . $idAttr . '><div class="wrap">'
        . ($title !== '' ? '<h2 class="sec-title">' . $h($title) . '</h2>' : '') . $mapHtml . '</div></section>';
}

function hs_landing_render_banner(array $block, callable $h): string
{
    $text = (string) ($block['text'] ?? '');
    $cta = (string) ($block['cta_text'] ?? '');
    $ctaUrl = (string) ($block['cta_url'] ?? '#');
    if ($text === '') {
        return '';
    }
    $variant = (string) ($block['variant'] ?? 'solid');
    $btn = $cta !== '' ? '<a class="btn primary" href="' . $h($ctaUrl) . '">' . $h($cta) . '</a>' : '';
    $cls = 'promo-banner promo-' . $variant;

    return '<section class="' . $h($cls) . '"><div class="wrap promo-inner"><span>' . $h($text) . '</span>' . $btn . '</div></section>';
}

function hs_landing_render_quote_block(array $block, callable $h): string
{
    $quote = (string) ($block['quote'] ?? '');
    if ($quote === '') {
        return '';
    }
    $author = (string) ($block['author'] ?? '');
    $role = (string) ($block['role'] ?? '');
    $variant = (string) ($block['variant'] ?? 'centered');
    $cls = 'quote-block quote-' . $variant;

    return '<section class="' . $h($cls) . '"><div class="wrap"><blockquote>“' . $h($quote) . '”</blockquote>'
        . ($author !== '' ? '<cite>' . $h($author) . ($role !== '' ? ' · ' . $h($role) : '') . '</cite>' : '') . '</div></section>';
}

function hs_landing_render_download(array $block, callable $h, string $idAttr): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'buttons');
    $html = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = (string) ($item['label'] ?? '');
        $url = (string) ($item['url'] ?? '#');
        $size = (string) ($item['size'] ?? '');
        if ($label === '') {
            continue;
        }
        $sizeHtml = $size !== '' ? '<span class="dl-size">' . $h($size) . '</span>' : '';
        if ($variant === 'list') {
            $html .= '<a class="dl-item" href="' . $h($url) . '" download><i class="fa-solid fa-file-arrow-down"></i> '
                . $h($label) . $sizeHtml . '</a>';
        } else {
            $html .= '<a class="btn primary dl-btn" href="' . $h($url) . '" download><i class="fa-solid fa-download"></i> '
                . $h($label) . '</a>';
        }
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'list' ? 'download-list' : 'download-buttons';

    return '<section class="download-block"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_alert(array $block, callable $h): string
{
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    if ($title === '' && $text === '') {
        return '';
    }
    $variant = (string) ($block['variant'] ?? 'info');
    $cls = 'alert-block alert-' . $variant;

    return '<section class="' . $h($cls) . '"><div class="wrap alert-inner">'
        . '<i class="fa-solid fa-circle-info"></i><div>'
        . ($title !== '' ? '<strong>' . $h($title) . '</strong>' : '')
        . ($text !== '' ? '<p>' . $h($text) . '</p>' : '') . '</div></div></section>';
}

function hs_landing_render_events(array $block, callable $h, string $idAttr): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'list');
    $html = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $html .= '<article class="event-item"><span class="event-date">' . $h((string) ($item['date'] ?? '')) . '</span>'
            . '<div><h3>' . $h((string) ($item['title'] ?? '')) . '</h3>'
            . '<span class="event-loc"><i class="fa-solid fa-location-dot"></i> ' . $h((string) ($item['location'] ?? '')) . '</span></div></article>';
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'cards' ? 'events-cards' : 'events-list';

    return '<section class="events-block"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_steps(array $block, callable $h, string $idAttr): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'numbered');
    $html = '';
    $n = 1;
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $html .= '<article class="step-item"><span class="step-num">' . $n . '</span>'
            . '<div><h3>' . $h((string) ($item['title'] ?? '')) . '</h3><p>' . $h((string) ($item['text'] ?? '')) . '</p></div></article>';
        $n++;
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'horizontal' ? 'steps-horizontal' : 'steps-numbered';

    return '<section class="steps-block"' . $idAttr . '><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_countdown(array $block, callable $h, string $idAttr): string
{
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $date = (string) ($block['countdown_date'] ?? '');
    if ($date === '') {
        return '';
    }
    $variant = (string) ($block['variant'] ?? 'boxes');
    $cls = $variant === 'inline' ? 'countdown countdown-inline' : 'countdown countdown-boxes';
    $units = '<div class="cd-units" data-countdown="' . $h($date) . '">'
        . '<div class="cd-unit"><strong data-cd-days>--</strong><span>Days</span></div>'
        . '<div class="cd-unit"><strong data-cd-hours>--</strong><span>Hours</span></div>'
        . '<div class="cd-unit"><strong data-cd-mins>--</strong><span>Min</span></div>'
        . '<div class="cd-unit"><strong data-cd-secs>--</strong><span>Sec</span></div></div>';

    return '<section class="' . $h($cls) . '"' . $idAttr . '><div class="wrap countdown-inner">'
        . ($title !== '' ? '<h2 class="sec-title">' . $h($title) . '</h2>' : '')
        . ($text !== '' ? '<p>' . $h($text) . '</p>' : '') . $units . '</div></section>';
}

function hs_landing_render_columns(array $block, callable $h): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'two');
    $html = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $html .= '<article class="col-item"><h3>' . $h((string) ($item['title'] ?? '')) . '</h3><p>' . $h((string) ($item['text'] ?? '')) . '</p></article>';
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'three' ? 'cols-three' : 'cols-two';

    return '<section class="columns-block"><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_render_badges(array $block, callable $h): string
{
    $secTitle = (string) ($block['section_title'] ?? '');
    $variant = (string) ($block['variant'] ?? 'pills');
    $html = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = (string) ($item['label'] ?? '');
        if ($label === '') {
            continue;
        }
        $html .= '<span class="badge-pill">' . $h($label) . '</span>';
    }
    if ($html === '') {
        return '';
    }
    $cls = $variant === 'outline' ? 'badges-outline' : 'badges-pills';

    return '<section class="badges-block"><div class="wrap">'
        . ($secTitle !== '' ? '<h2 class="sec-title">' . $h($secTitle) . '</h2>' : '')
        . '<div class="' . $h($cls) . '">' . $html . '</div></div></section>';
}

function hs_landing_page_css(): string
{
    return ':root{--c:BRAND;--c2:color-mix(in srgb,var(--c) 75%,#000)}'
        . '*{box-sizing:border-box}body{margin:0;font-family:"DM Sans",system-ui,sans-serif;color:#0f172a;background:#f8fafc}'
        . '.nav{position:sticky;top:0;z-index:10;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1.25rem;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-bottom:1px solid #e2e8f0;flex-wrap:wrap}'
        . '.brand{font-weight:700;font-size:1.05rem;color:var(--c)}.brand span{color:#64748b;font-weight:500;font-size:.85rem;margin-left:.5rem}'
        . '.nav-links{display:flex;flex-wrap:wrap;gap:.65rem 1rem}.nav-links a{color:#334155;text-decoration:none;font-weight:600;font-size:.88rem}'
        . '.hero{padding:3.5rem 1.25rem 3rem;background:linear-gradient(145deg,color-mix(in srgb,var(--c) 8%,#fff),#fff 55%,#eff6ff)}'
        . '.hero-centered-inner,.hero-minimal,.about-centered-inner{text-align:center;max-width:42rem;margin:0 auto}'
        . '.hero-minimal h1{font-size:clamp(1.5rem,3vw,2.2rem)}.wrap{max-width:68rem;margin:0 auto;padding:0 1.25rem}'
        . '.hero-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:2rem;align-items:center}'
        . '.badge{display:inline-flex;gap:.4rem;align-items:center;padding:.35rem .75rem;border-radius:999px;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-size:.78rem;font-weight:700;margin-bottom:1rem}'
        . 'h1{margin:0 0 .75rem;font-size:clamp(1.75rem,4vw,2.65rem);line-height:1.15}'
        . '.hero p{color:#64748b;font-size:1.05rem;line-height:1.65;margin:0 0 1.35rem;max-width:36rem}'
        . '.hero-centered .hero p,.hero-minimal p{margin-left:auto;margin-right:auto}'
        . '.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.2rem;border-radius:.7rem;font-weight:700;text-decoration:none;font-size:.92rem}'
        . '.btn.primary{background:var(--c);color:#fff;box-shadow:0 10px 24px color-mix(in srgb,var(--c) 35%,transparent)}'
        . '.hero-card{background:#fff;border:1px solid #e2e8f0;border-radius:1.1rem;padding:1.35rem;box-shadow:0 20px 50px rgba(15,23,42,.08)}'
        . '.hero-card div{display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid #f1f5f9;font-size:.88rem}'
        . '.hero-card div:last-child{border:none}.hero-card strong{color:var(--c)}'
        . '.features,.about,.gallery,.info,.contact{padding:2.5rem 0}.sec-title{margin:0 0 1.25rem;font-size:1.45rem}'
        . '.feat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}'
        . '.feat{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.2rem}'
        . '.feat-card{box-shadow:0 12px 30px rgba(15,23,42,.08);border:none}'
        . '.feat-icon{width:2.5rem;height:2.5rem;border-radius:.75rem;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);margin-bottom:.75rem}'
        . '.feat h3{margin:0 0 .4rem;font-size:1rem}.feat p{margin:0;color:#64748b;font-size:.88rem;line-height:1.5}'
        . '.feat-list{display:flex;flex-direction:column;gap:.75rem}.feat-list-item{display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem}'
        . '.about-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:1.5rem;align-items:center}'
        . '.about-text{color:#64748b;line-height:1.7;margin:0}.about-card{background:linear-gradient(145deg,var(--c),var(--c2));color:#fff;border-radius:1rem;padding:1.5rem;text-align:center}'
        . '.about-card i{font-size:2rem;opacity:.9;margin-bottom:.75rem}.about-card strong{display:block;font-size:1.15rem}.about-card span{opacity:.9;font-size:.88rem}'
        . '.gal-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}'
        . '.gal-masonry{columns:3;column-gap:.75rem}.gal-masonry .gal-item{display:block;margin-bottom:.75rem;break-inside:avoid}'
        . '.gal-row{display:flex;gap:.75rem;overflow-x:auto;padding-bottom:.5rem}'
        . '.gal-row .gal-item{min-width:11rem;flex:0 0 11rem}'
        . '.gal-item{aspect-ratio:4/3;border-radius:.85rem;display:grid;place-items:end start;padding:.65rem;color:#fff;font-weight:600;font-size:.82rem}'
        . '.gal-item-photo{padding:0;overflow:hidden;position:relative;display:block}'
        . '.gal-item-photo img{width:100%;height:100%;object-fit:cover;display:block;aspect-ratio:4/3}'
        . '.gal-item-photo figcaption{position:absolute;left:0;right:0;bottom:0;margin:0;padding:.65rem;background:linear-gradient(transparent,rgba(0,0,0,.55));text-shadow:none}'
        . '.gal-item figcaption{margin:0;text-shadow:0 1px 3px rgba(0,0,0,.35)}'
        . '.stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}'
        . '.stat{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.2rem;text-align:center}'
        . '.stat strong{display:block;font-size:1.5rem;color:var(--c)}.stat span{color:#64748b;font-size:.85rem}'
        . '.info-text-box{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem}.info-text-box p{color:#64748b;line-height:1.7;margin:0}'
        . '.info-quote blockquote{margin:0;font-size:1.35rem;font-style:italic;color:#334155;line-height:1.5}'
        . '.info-quote cite{display:block;margin-top:.75rem;color:#64748b;font-size:.9rem}'
        . '.contact-box{background:linear-gradient(135deg,var(--c),var(--c2));border-radius:1.25rem;padding:2rem;color:#fff;text-align:center}'
        . '.contact-split-grid{display:grid;grid-template-columns:1fr auto;gap:1.5rem;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem}'
        . '.contact-minimal-inner{text-align:center;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:2rem}'
        . '.sec-title.light{color:#fff}.contact-lines{display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin:1rem 0 1.25rem}'
        . '.contact-lines a,.contact-lines p{color:inherit;text-decoration:none;font-weight:600;font-size:.92rem}'
        . '.contact-box .contact-lines a,.contact-box .contact-lines p{color:#fff}'
        . '.contact .btn.primary{background:#fff;color:var(--c)}'
        . 'footer{padding:1.5rem;color:#94a3b8;font-size:.8rem}'
        . '.footer-minimal{text-align:center}.footer-centered{text-align:center}.footer-centered strong{display:block;color:#334155;font-size:1rem}'
        . '.footer-columns .footer-cols{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.footer-columns strong{color:#334155}'
        . '.footer-nav{display:flex;flex-direction:column;gap:.35rem;margin-top:.35rem}.footer-nav a{color:#64748b;text-decoration:none}'
        . '.footer-social .wrap{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}'
        . '.footer-social-icons{display:flex;gap:.65rem;margin-top:.35rem}.footer-social-icons a{color:var(--c);font-size:1.1rem}'
        . '.cta{padding:2rem 0}.cta-banner-inner,.cta-inline-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border-radius:1.25rem;padding:2rem;text-align:center}'
        . '.cta-inline-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;text-align:left;flex-wrap:wrap}'
        . '.cta-inline-inner span{display:block;opacity:.9;font-size:.9rem;margin-top:.25rem}'
        . '.cta-split-grid{display:grid;grid-template-columns:1fr auto;gap:1.5rem;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem}'
        . '.cta-banner-inner .btn,.cta-inline-inner .btn{background:#fff;color:var(--c)}'
        . '.testimonials,.pricing,.faq,.team,.logos{padding:2.5rem 0}'
        . '.test-cards,.test-grid,.price-cards,.price-compact,.team-grid,.team-cards,.logos-row,.logos-grid{display:grid;gap:1rem}'
        . '.test-cards,.price-cards,.team-cards{grid-template-columns:repeat(3,minmax(0,1fr))}'
        . '.test-grid,.team-grid,.logos-grid{grid-template-columns:repeat(3,minmax(0,1fr))}'
        . '.test-card,.price-card,.team-member{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.2rem}'
        . '.test-card p{color:#64748b;font-style:italic;line-height:1.55;margin:0 0 .75rem}'
        . '.test-card footer strong{display:block;color:#0f172a}.test-card footer span{color:#94a3b8;font-size:.85rem}'
        . '.test-quote-inner{text-align:center;max-width:40rem;margin:0 auto}.test-quote blockquote{font-size:1.35rem;font-style:italic;margin:0}'
        . '.test-quote cite{display:block;margin-top:.75rem;font-weight:700}.test-role{color:#94a3b8;font-size:.88rem}'
        . '.price-card-featured{border-color:var(--c);box-shadow:0 12px 30px color-mix(in srgb,var(--c) 20%,transparent)}'
        . '.price-amt strong{font-size:2rem;color:var(--c)}.price-amt span{color:#64748b;font-size:.9rem}'
        . '.price-feats{list-style:none;margin:1rem 0;padding:0;color:#64748b;font-size:.88rem}'
        . '.price-feats li{margin:.35rem 0}.price-feats i{color:var(--c);margin-right:.35rem}'
        . '.price-compact{grid-template-columns:repeat(3,minmax(0,1fr))}.price-table{width:100%;border-collapse:collapse;background:#fff;border-radius:1rem;overflow:hidden}'
        . '.price-table th,.price-table td{padding:.85rem 1rem;border-bottom:1px solid #e2e8f0;text-align:left;font-size:.9rem}'
        . '.price-table th{background:#f8fafc;color:#64748b;font-weight:700}'
        . '.faq-list,.faq-twocol{display:flex;flex-direction:column;gap:.65rem}'
        . '.faq-twocol{display:grid;grid-template-columns:1fr 1fr;gap:1rem}'
        . '.faq-acc,.faq-item{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:.85rem 1rem}'
        . '.faq-acc summary{cursor:pointer;font-weight:700}.faq-acc p,.faq-item p{color:#64748b;margin:.5rem 0 0;line-height:1.6}'
        . '.faq-item h3{margin:0 0 .35rem;font-size:1rem}'
        . '.team-avatar{width:3rem;height:3rem;border-radius:999px;background:color-mix(in srgb,var(--c) 15%,#fff);color:var(--c);display:grid;place-items:center;font-weight:700;margin-bottom:.65rem}'
        . '.team-role{display:block;color:#94a3b8;font-size:.85rem;margin-bottom:.35rem}'
        . '.team-list{display:flex;flex-direction:column;gap:.75rem}.team-list .team-member{display:flex;gap:1rem;align-items:flex-start}'
        . '.logos-title{text-align:center}.logos-row,.logos-strip{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center;align-items:center}'
        . '.logos-strip{background:#f8fafc;border-radius:1rem;padding:1.25rem}'
        . '.logo-pill{padding:.55rem 1rem;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#64748b;font-weight:700;font-size:.82rem}'
        . '.video-block,.newsletter,.timeline,.services,.heading-block,.text-block,.image-block,.hours-block,.map-block,.download-block,.events-block,.steps-block,.countdown,.columns-block,.badges-block{padding:2.5rem 0}'
        . '.video-embed{aspect-ratio:16/9;border-radius:1rem;overflow:hidden;background:#0f172a}.video-embed iframe{width:100%;height:100%;border:0}'
        . '.video-link-card{display:flex;align-items:center;gap:1rem;padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;text-decoration:none;color:#0f172a;font-weight:700}'
        . '.video-link-card i{font-size:2rem;color:var(--c)}'
        . '.newsletter-card,.newsletter-inline-inner,.newsletter-banner-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border-radius:1.25rem;padding:2rem;text-align:center}'
        . '.newsletter-inline-inner,.newsletter-banner-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;text-align:left}'
        . '.newsletter-form{display:flex;gap:.5rem;flex-wrap:wrap;justify-content:center;margin-top:1rem}'
        . '.newsletter-form input{flex:1;min-width:12rem;padding:.65rem .85rem;border:1px solid #e2e8f0;border-radius:.6rem}'
        . '.newsletter-card .btn,.newsletter-banner-inner .btn{background:#fff;color:var(--c)}'
        . '.tl-list{display:flex;flex-direction:column;gap:1rem}.tl-item{padding-left:1.25rem;border-left:3px solid var(--c)}.tl-year{display:block;font-size:.8rem;font-weight:700;color:var(--c)}'
        . '.timeline-cards .tl-list{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.timeline-cards .tl-item{border-left:none;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1rem}'
        . '.svc-grid,.svc-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.svc-list{display:flex;flex-direction:column;gap:.75rem}'
        . '.svc-item{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.2rem}.svc-icon{color:var(--c);margin-bottom:.5rem}.svc-price{display:block;font-weight:700;color:var(--c);margin:.25rem 0}'
        . '.heading-center,.heading-large{text-align:center}.heading-large .sec-title{font-size:clamp(1.75rem,4vw,2.5rem)}.heading-sub{color:#64748b;margin:0}'
        . '.text-box .text-inner{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.5rem}.text-narrow .text-inner{max-width:36rem;margin:0 auto}'
        . '.text-inner p{color:#64748b;line-height:1.7;margin:0}'
        . '.image-figure{margin:0}.image-figure img{width:100%;border-radius:.85rem;display:block}.image-rounded .image-figure img{border-radius:1.25rem;box-shadow:0 12px 30px rgba(15,23,42,.1)}'
        . '.image-figure figcaption{margin-top:.5rem;color:#64748b;font-size:.88rem}'
        . '.divider hr{border:none;height:1px;background:#e2e8f0;margin:1.5rem 0}.divider-dots hr{background:repeating-linear-gradient(90deg,var(--c),var(--c) 4px,transparent 4px,transparent 10px)}'
        . '.divider-gradient hr{height:3px;background:linear-gradient(90deg,transparent,var(--c),transparent)}'
        . '.spacer-sm{height:1.5rem}.spacer-md{height:3rem}.spacer-lg{height:5rem}'
        . '.hours-table{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1rem}.hours-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f1f5f9}'
        . '.hours-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem}.hours-card{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem;text-align:center}'
        . '.map-embed{aspect-ratio:16/9;border-radius:1rem;overflow:hidden;border:1px solid #e2e8f0}.map-embed iframe{width:100%;height:100%;border:0}'
        . '.map-placeholder{display:flex;align-items:center;gap:1rem;padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:1rem}.map-placeholder i{font-size:2rem;color:var(--c)}'
        . '.promo-banner{padding:0}.promo-inner{display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap;padding:.85rem 1.25rem}'
        . '.promo-solid .promo-inner{background:linear-gradient(90deg,var(--c),var(--c2));color:#fff}.promo-solid .btn{background:#fff;color:var(--c)}'
        . '.promo-outline .promo-inner{border-top:2px solid var(--c);border-bottom:2px solid var(--c);background:color-mix(in srgb,var(--c) 6%,#fff)}'
        . '.quote-block{padding:2rem 0}.quote-centered{text-align:center}.quote-centered blockquote{font-size:1.35rem;font-style:italic;margin:0}'
        . '.quote-sidebar blockquote{margin:0;padding-left:1rem;border-left:4px solid var(--c);font-size:1.2rem;font-style:italic}'
        . '.quote-block cite{display:block;margin-top:.75rem;color:#64748b}'
        . '.download-buttons{display:flex;flex-wrap:wrap;gap:.75rem}.download-list{display:flex;flex-direction:column;gap:.5rem}'
        . '.dl-item{display:flex;align-items:center;gap:.5rem;padding:.85rem 1rem;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;text-decoration:none;color:#0f172a;font-weight:600}'
        . '.dl-size{margin-left:auto;color:#94a3b8;font-size:.82rem}'
        . '.alert-block{padding:1rem 0}.alert-inner{display:flex;gap:1rem;align-items:flex-start;padding:1rem 1.25rem;border-radius:.85rem;border:1px solid #e2e8f0;background:#fff}'
        . '.alert-info .alert-inner{border-color:color-mix(in srgb,var(--c) 35%,#fff);background:color-mix(in srgb,var(--c) 8%,#fff)}.alert-info i{color:var(--c)}'
        . '.alert-success .alert-inner{border-color:#86efac;background:#f0fdf4}.alert-success i{color:#16a34a}'
        . '.alert-warning .alert-inner{border-color:#fcd34d;background:#fffbeb}.alert-warning i{color:#d97706}'
        . '.events-list,.events-cards{display:flex;flex-direction:column;gap:.75rem}.events-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}'
        . '.event-item{display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem}'
        . '.event-date{font-weight:700;color:var(--c);min-width:4rem}.event-loc{color:#64748b;font-size:.85rem}'
        . '.steps-numbered,.steps-horizontal{display:flex;flex-direction:column;gap:1rem}.steps-horizontal{flex-direction:row;flex-wrap:wrap}'
        . '.step-item{display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem;flex:1;min-width:10rem}'
        . '.step-num{width:2rem;height:2rem;border-radius:999px;background:var(--c);color:#fff;display:grid;place-items:center;font-weight:700;flex-shrink:0}'
        . '.countdown-inner{text-align:center}.cd-units{display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;margin-top:1rem}'
        . '.cd-unit{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem;min-width:4.5rem}.cd-unit strong{display:block;font-size:1.5rem;color:var(--c)}.cd-unit span{font-size:.75rem;color:#64748b}'
        . '.cols-two,.cols-three{display:grid;gap:1.5rem}.cols-two{grid-template-columns:1fr 1fr}.cols-three{grid-template-columns:repeat(3,1fr)}'
        . '.col-item h3{margin:0 0 .35rem}.col-item p{color:#64748b;margin:0;line-height:1.6}'
        . '.badges-pills,.badges-outline{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center}'
        . '.badge-pill{padding:.45rem .9rem;border-radius:999px;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-weight:700;font-size:.82rem}'
        . '.badges-outline .badge-pill{background:#fff;border:1px solid var(--c)}'
        . '@media(max-width:800px){.hero-grid,.about-grid,.feat-grid,.stat-grid,.gal-grid,.footer-columns .footer-cols,.test-cards,.test-grid,.price-cards,.price-compact,.team-grid,.team-cards,.logos-grid,.faq-twocol,.cta-split-grid,.svc-grid,.svc-cards,.timeline-cards .tl-list,.hours-cards,.events-cards,.cols-two,.cols-three,.steps-horizontal{grid-template-columns:1fr}.gal-masonry{columns:2}.cta-inline-inner,.newsletter-inline-inner,.newsletter-banner-inner{flex-direction:column;text-align:center}}'
        . '.elb-has-bg{position:relative;overflow:hidden;isolation:isolate;background:none!important}.elb-block-bg{position:absolute;inset:0;width:100%;height:100%;background-size:cover;background-position:center;background-repeat:no-repeat;z-index:0}.elb-block-bg-overlay{position:absolute;inset:0;background:rgba(15,23,42,var(--elb-bg-overlay,.4));z-index:1;pointer-events:none}.elb-block-inner{position:relative;z-index:2}'
        . hs_landing_block_element_color_css()
        . hs_landing_chrome_css()
        . hs_landing_messenger_css()
        . hs_landing_slider_css()
        . hs_landing_page_css_ext();
}

/** @param array<string, mixed> $data */
function hs_landing_render_html(array $data, string $lang = 'en'): string
{
    $c = hs_landing_resolve_color($data);
    $name = (string) ($data['business_name'] ?? 'Business');
    $tagline = (string) ($data['tagline'] ?? '');
    $heroBlock = null;
    foreach ((array) ($data['blocks'] ?? []) as $b) {
        if (is_array($b) && ($b['type'] ?? '') === 'hero' && !empty($b['on'])) {
            $heroBlock = $b;
            break;
        }
    }
    $hero = (string) ($heroBlock['title'] ?? $name);
    $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $body = hs_landing_render_nav($data, $h);
    foreach ((array) ($data['blocks'] ?? []) as $block) {
        if (is_array($block)) {
            $body .= hs_landing_render_block($block, $data, $h);
        }
    }
    $body .= hs_landing_render_footer($data, $h);
    $body .= hs_landing_render_floating_messengers($data, $h);

    $css = str_replace('BRAND', $h($c), hs_landing_page_css());

    return '<!DOCTYPE html><html lang="' . $h($lang) . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="description" content="' . $h($tagline) . '">'
        . '<title>' . $h($name) . ' — ' . $h($hero) . '</title>'
        . '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">'
        . '<style>' . $css . '</style></head><body>' . $body
        . '<script>(function(){function tick(){document.querySelectorAll("[data-countdown]").forEach(function(el){var t=new Date(el.getAttribute("data-countdown")+"T00:00:00").getTime()-Date.now();if(t<0)t=0;var d=Math.floor(t/864e5),h=Math.floor(t%864e5/36e5),m=Math.floor(t%36e5/6e4),s=Math.floor(t%6e4/1e3);var ds=el.querySelector("[data-cd-days]");if(ds){ds.textContent=d;el.querySelector("[data-cd-hours]").textContent=h;el.querySelector("[data-cd-mins]").textContent=m;el.querySelector("[data-cd-secs]").textContent=s;}});}tick();setInterval(tick,1000);})();'
        . hs_landing_chrome_js() . hs_landing_messenger_js() . hs_landing_slider_js() . hs_landing_interactive_js() . '</script>'
        . '</body></html>';
}

/** @param array<string, mixed> $data */
function hs_landing_publish(array $user, array $data): array
{
    $dir = hs_landing_publish_dir($user);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['ok' => false, 'error' => 'mkdir'];
    }
    $renderData = $data;
    $renderData['_render_user'] = $user;
    $html = hs_landing_render_html($renderData, (string) ($GLOBALS['lang'] ?? 'en'));
    $file = $dir . '/index.html';
    if (file_put_contents($file, $html) === false) {
        return ['ok' => false, 'error' => 'write'];
    }

    $url = hs_landing_public_url($user);
    $userId = (string) ($user['id'] ?? '');
    $slug = 'www';
    $landingSiteId = null;
    foreach (hs_sites_for_user($userId) as $s) {
        if ((string) ($s['slug'] ?? '') === $slug || (string) ($s['app'] ?? '') === 'landing') {
            $landingSiteId = (string) ($s['id'] ?? '');
            break;
        }
    }
    $title = (string) ($data['business_name'] ?? 'Landing');
    if ($landingSiteId !== null && $landingSiteId !== '') {
        hs_site_update_for_user($landingSiteId, $userId, static function (array $site) use ($title): array {
            $site['app'] = 'landing';
            $site['title'] = $title;
            $site['status'] = 'active';
            return $site;
        });
    } else {
        hs_site_add_for_user($userId, [
            'id' => hs_new_id('s'),
            'slug' => $slug,
            'install_base' => hs_install_default_base($user),
            'title' => $title,
            'domain' => '',
            'app' => 'landing',
            'status' => 'active',
            'created_at' => gmdate('c'),
        ]);
    }

    if (function_exists('hs_panel_log')) {
        hs_panel_log($userId, 'landing_publish');
    }

    return ['ok' => true, 'url' => $url, 'path' => 'public_html/' . hs_install_default_base($user) . '/index.html'];
}

/** @return array<string, mixed> */
function hs_landing_editor_config(array $data, array $user, array $t): array
{
    return [
        'data' => $data,
        'publicBase' => hs_landing_public_url($user),
        'galleryApi' => hs_url(hs_panel_path('landing-gallery-api.php')),
        'csrf' => hs_csrf_token(),
        'themes' => hs_landing_themes($t),
        'iconSets' => hs_landing_icon_sets($t),
        'iconStyles' => hs_landing_icon_styles($t),
        'galleryPalettes' => hs_landing_gallery_palettes($t),
        'blockTypes' => hs_landing_block_registry($t),
        'pageTemplates' => hs_landing_page_templates($t),
        'navStyles' => hs_landing_nav_styles($t),
        'burgerModes' => hs_landing_burger_modes($t),
        'messengerChannels' => hs_landing_messenger_channels($t),
        'msgFloatStyles' => hs_landing_msg_float_styles($t),
        'msgPositions' => hs_landing_msg_positions($t),
        'footerStyles' => hs_landing_footer_styles($t),
        'logoIcons' => hs_landing_logo_icon_choices(),
        'sliderHeights' => hs_landing_slider_heights($t),
        'i18n' => [
            'icon_set' => $t['landing_icon_set_label'] ?? 'Icon set',
            'icon_style' => $t['landing_icon_style_label'] ?? 'Icon style',
            'gallery_palette' => $t['landing_gallery_palette_label'] ?? 'Gallery colors',
            'pick_icon' => $t['landing_pick_icon'] ?? 'Pick icon',
            'apply_icon_set' => $t['landing_apply_icon_set'] ?? 'Apply icon set to features',
            'apply_palette' => $t['landing_apply_gallery_palette'] ?? 'Apply palette to galleries',
            'drag_block' => $t['landing_drag_block'] ?? 'Drag to reorder',
            'block_order' => $t['landing_block_order'] ?? 'Order',
            'block_visible' => $t['landing_block_visible'] ?? 'Visible',
            'block_hidden' => $t['landing_block_hidden'] ?? 'Hidden',
            'add_block' => $t['landing_add_block'] ?? 'Add block',
            'move_up' => $t['landing_move_up'] ?? 'Up',
            'move_down' => $t['landing_move_down'] ?? 'Down',
            'remove' => $t['landing_remove_block'] ?? 'Remove',
            'variant' => $t['landing_block_variant'] ?? 'Layout',
            'enabled' => $t['landing_block_enabled'] ?? 'Show block',
            'section_title' => $t['landing_section_title'] ?? 'Section title',
            'title' => $t['landing_block_title'] ?? 'Title',
            'subtitle' => $t['landing_hero_sub'] ?? 'Subheadline',
            'text' => $t['landing_block_text'] ?? 'Text',
            'cta_text' => $t['landing_cta_text'] ?? 'Button text',
            'cta_url' => $t['landing_cta_url'] ?? 'Button link',
            'phone' => $t['landing_phone'] ?? 'Phone',
            'email' => $t['landing_email'] ?? 'Email',
            'address' => $t['landing_address'] ?? 'Address',
            'caption' => $t['landing_gallery_caption'] ?? 'Caption',
            'value' => $t['landing_stat_value'] ?? 'Value',
            'label' => $t['landing_stat_label'] ?? 'Label',
            'quote' => $t['landing_info_quote'] ?? 'Quote',
            'author' => $t['landing_info_author'] ?? 'Author',
            'nav_label' => $t['landing_nav_label'] ?? 'Link label',
            'nav_url' => $t['landing_nav_url'] ?? 'Link URL',
            'add_nav' => $t['landing_add_nav'] ?? 'Add link',
            'add_item' => $t['landing_add_item'] ?? 'Add item',
            'add_gallery' => $t['landing_add_gallery_item'] ?? 'Add image',
            'pick_photo' => $t['landing_gallery_pick_photo'] ?? 'Choose photo',
            'upload_photo' => $t['landing_gallery_upload_photo'] ?? 'Upload photo',
            'clear_photo' => $t['landing_gallery_clear_photo'] ?? 'Remove photo',
            'gallery_picker_title' => $t['landing_gallery_picker_title'] ?? 'Select photo',
            'gallery_no_photos' => $t['landing_gallery_no_photos'] ?? 'No photos yet — upload one below.',
            'gallery_uploading' => $t['landing_gallery_uploading'] ?? 'Uploading…',
            'gallery_upload_error' => $t['landing_gallery_upload_error'] ?? 'Upload failed',
            'widgets' => $t['landing_widgets'] ?? 'Widgets',
            'no_blocks' => $t['landing_no_blocks'] ?? 'No blocks yet — add a widget.',
            'edit_block' => $t['landing_edit_block'] ?? 'Edit',
            'remove_feat_item' => $t['landing_remove_feat_item'] ?? 'Remove item',
            'widget_search' => $t['landing_widget_search'] ?? 'Search widgets…',
            'widget_search_empty' => $t['landing_widget_search_empty'] ?? 'No widgets match your search.',
            'empty_blocks' => $t['landing_empty_blocks'] ?? 'No blocks yet — add a widget from the left panel.',
            'canvas_hint' => $t['landing_canvas_hint'] ?? 'Click a section in the preview to edit it',
            'pick_in_preview' => $t['landing_pick_in_preview'] ?? 'Click to edit',
            'page_templates' => $t['landing_page_templates'] ?? 'Page templates',
            'page_templates_hint' => $t['landing_page_templates_hint'] ?? 'Replace all blocks with a ready-made layout.',
            'apply_template' => $t['landing_apply_template'] ?? 'Apply template',
            'template_confirm' => $t['landing_template_confirm'] ?? 'Replace all current blocks with this template?',
            'role' => $t['landing_test_role'] ?? 'Role',
            'question' => $t['landing_faq_question'] ?? 'Question',
            'answer' => $t['landing_faq_answer'] ?? 'Answer',
            'price' => $t['landing_price_amount'] ?? 'Price',
            'period' => $t['landing_price_period_label'] ?? 'Period',
            'features' => $t['landing_price_features'] ?? 'Features (one per line)',
            'featured' => $t['landing_price_featured'] ?? 'Highlight plan',
            'bio' => $t['landing_team_bio'] ?? 'Bio',
            'logo_name' => $t['landing_logo_name'] ?? 'Brand name',
            'add_testimonial' => $t['landing_add_testimonial'] ?? 'Add review',
            'add_plan' => $t['landing_add_plan'] ?? 'Add plan',
            'add_faq' => $t['landing_add_faq'] ?? 'Add question',
            'add_member' => $t['landing_add_member'] ?? 'Add member',
            'add_logo' => $t['landing_add_logo'] ?? 'Add logo',
            'field_group_layout' => $t['landing_field_group_layout'] ?? 'Layout',
            'field_group_content' => $t['landing_field_group_content'] ?? 'Content',
            'field_group_items' => $t['landing_field_group_items'] ?? 'Items',
            'settings_accordion_hint' => $t['landing_settings_accordion_hint'] ?? 'One section open at a time — tap a header to switch.',
            'spoiler_expand' => $t['landing_spoiler_expand'] ?? 'Expand',
            'spoiler_collapse' => $t['landing_spoiler_collapse'] ?? 'Collapse',
            'block_color' => $t['landing_block_color'] ?? 'Block color',
            'block_color_reset' => $t['landing_block_color_reset'] ?? 'Use theme',
            'field_group_bg' => $t['landing_field_group_bg'] ?? 'Background',
            'field_group_bg_hint' => $t['landing_field_group_bg_hint'] ?? 'Color or photo behind this block. Use the overlay strip to dim it.',
            'bg_type' => $t['landing_bg_type'] ?? 'Background',
            'bg_type_none' => $t['landing_bg_type_none'] ?? 'None',
            'bg_type_color' => $t['landing_bg_type_color'] ?? 'Color',
            'bg_type_image' => $t['landing_bg_type_image'] ?? 'Image',
            'bg_color' => $t['landing_bg_color'] ?? 'Background color',
            'bg_overlay' => $t['landing_bg_overlay'] ?? 'Dim overlay (strip)',
            'logo_icon' => $t['landing_logo_icon'] ?? 'Logo icon',
            'logo_icon_none' => $t['landing_logo_icon_none'] ?? 'No icon',
            'logo_icon_hint' => $t['landing_hint_logo_icon'] ?? 'Icon shown next to your business name in the header.',
            'field_group_colors' => $t['landing_field_group_colors'] ?? 'Text & buttons',
            'field_group_colors_hint' => $t['landing_field_group_colors_hint'] ?? 'Optional colors for text, headings and buttons in this block.',
            'text_color' => $t['landing_text_color'] ?? 'Text color',
            'heading_color' => $t['landing_heading_color'] ?? 'Heading color',
            'btn_color' => $t['landing_btn_color'] ?? 'Button color',
            'btn_text_color' => $t['landing_btn_text_color'] ?? 'Button text color',
            'color_inherit' => $t['landing_color_inherit'] ?? 'Theme default',
            'nav_header' => $t['landing_nav_header'] ?? 'Header / menu',
            'messengers' => $t['landing_sec_messengers'] ?? 'Messengers',
            'video_url' => $t['landing_video_url'] ?? 'Video URL',
            'embed_url' => $t['landing_embed_url'] ?? 'Map embed URL',
            'countdown_date' => $t['landing_countdown_date'] ?? 'Target date',
            'day' => $t['landing_hours_day'] ?? 'Day',
            'hours' => $t['landing_hours_time'] ?? 'Hours',
            'location' => $t['landing_event_location'] ?? 'Location',
            'date' => $t['landing_event_date'] ?? 'Date',
            'year' => $t['landing_timeline_year'] ?? 'Year',
            'file_url' => $t['landing_file_url'] ?? 'File URL',
            'file_size' => $t['landing_file_size'] ?? 'File size',
            'add_service' => $t['landing_add_service'] ?? 'Add service',
            'add_timeline' => $t['landing_add_timeline'] ?? 'Add milestone',
            'add_event' => $t['landing_add_event'] ?? 'Add event',
            'add_step' => $t['landing_add_step'] ?? 'Add step',
            'add_download' => $t['landing_add_download'] ?? 'Add file',
            'add_badge' => $t['landing_add_badge'] ?? 'Add badge',
            'add_column' => $t['landing_add_column'] ?? 'Add column',
            'add_hours' => $t['landing_add_hours_row'] ?? 'Add row',
            'price_label' => $t['landing_service_price'] ?? 'Price (optional)',
            'slider_height' => $t['landing_slider_height'] ?? 'Slide height',
            'slider_autoplay' => $t['landing_slider_autoplay'] ?? 'Autoplay',
            'slider_interval' => $t['landing_slider_interval'] ?? 'Interval (ms)',
            'slider_arrows' => $t['landing_slider_arrows'] ?? 'Navigation arrows',
            'slider_dots' => $t['landing_slider_dots'] ?? 'Dot indicators',
            'add_slide' => $t['landing_add_slide'] ?? 'Add slide',
            'marquee_speed' => $t['landing_marquee_speed'] ?? 'Scroll speed',
            'marquee_speed_slow' => $t['landing_marquee_speed_slow'] ?? 'Slow',
            'marquee_speed_normal' => $t['landing_marquee_speed_normal'] ?? 'Normal',
            'marquee_speed_fast' => $t['landing_marquee_speed_fast'] ?? 'Fast',
        ],
        'fieldHints' => hs_landing_field_hints($t),
    ];
}