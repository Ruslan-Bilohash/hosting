<?php
declare(strict_types=1);

/** @return array<string, array{label:string,icon:string,variants:array<string,string>,anchor:string,desc?:string}> */
function hs_landing_block_registry_ext(array $t): array
{
    return [
        'buttons' => [
            'label' => $t['landing_block_buttons'] ?? 'Buttons',
            'desc' => $t['landing_block_buttons_desc'] ?? 'Row of styled action buttons.',
            'icon' => 'fa-hand-pointer',
            'anchor' => '',
            'variants' => [
                'row' => $t['landing_var_buttons_row'] ?? 'Horizontal row',
                'center' => $t['landing_var_buttons_center'] ?? 'Centered',
                'stacked' => $t['landing_var_buttons_stacked'] ?? 'Stacked',
            ],
        ],
        'cards' => [
            'label' => $t['landing_block_cards'] ?? 'Cards',
            'desc' => $t['landing_block_cards_desc'] ?? 'Flexible content cards with optional CTA.',
            'icon' => 'fa-id-card',
            'anchor' => '',
            'variants' => [
                'grid' => $t['landing_var_cards_grid'] ?? '3-column grid',
                'row' => $t['landing_var_cards_row'] ?? 'Horizontal cards',
                'featured' => $t['landing_var_cards_featured'] ?? 'Featured center',
            ],
        ],
        'social' => [
            'label' => $t['landing_block_social'] ?? 'Social links',
            'desc' => $t['landing_block_social_desc'] ?? 'Links to your social profiles.',
            'icon' => 'fa-share-nodes',
            'anchor' => 'social',
            'variants' => [
                'icons' => $t['landing_var_social_icons'] ?? 'Icon row',
                'bar' => $t['landing_var_social_bar'] ?? 'Full-width bar',
                'pills' => $t['landing_var_social_pills'] ?? 'Pills',
            ],
        ],
        'trust' => [
            'label' => $t['landing_block_trust'] ?? 'Trust badges',
            'desc' => $t['landing_block_trust_desc'] ?? 'Security, quality and guarantee indicators.',
            'icon' => 'fa-shield-halved',
            'anchor' => '',
            'variants' => [
                'row' => $t['landing_var_trust_row'] ?? 'Icon row',
                'grid' => $t['landing_var_trust_grid'] ?? 'Grid',
                'strip' => $t['landing_var_trust_strip'] ?? 'Muted strip',
            ],
        ],
        'callout' => [
            'label' => $t['landing_block_callout'] ?? 'Callout box',
            'desc' => $t['landing_block_callout_desc'] ?? 'Highlighted message with optional button.',
            'icon' => 'fa-bullhorn',
            'anchor' => '',
            'variants' => [
                'tip' => $t['landing_var_callout_tip'] ?? 'Tip',
                'info' => $t['landing_var_callout_info'] ?? 'Info',
                'success' => $t['landing_var_callout_success'] ?? 'Success',
                'promo' => $t['landing_var_callout_promo'] ?? 'Promo',
            ],
        ],
        'media_text' => [
            'label' => $t['landing_block_media_text'] ?? 'Image + text',
            'desc' => $t['landing_block_media_text_desc'] ?? 'Side-by-side image and copy.',
            'icon' => 'fa-photo-film',
            'anchor' => '',
            'variants' => [
                'split' => $t['landing_var_media_split'] ?? 'Split layout',
                'card' => $t['landing_var_media_card'] ?? 'Card',
                'overlap' => $t['landing_var_media_overlap'] ?? 'Overlap',
            ],
        ],
        'menu' => [
            'label' => $t['landing_block_menu'] ?? 'Menu / prices',
            'desc' => $t['landing_block_menu_desc'] ?? 'Pricelist or menu items.',
            'icon' => 'fa-utensils',
            'anchor' => 'menu',
            'variants' => [
                'list' => $t['landing_var_menu_list'] ?? 'Simple list',
                'grid' => $t['landing_var_menu_grid'] ?? 'Card grid',
                'elegant' => $t['landing_var_menu_elegant'] ?? 'Elegant',
            ],
        ],
        'stats_bar' => [
            'label' => $t['landing_block_stats_bar'] ?? 'Stats bar',
            'desc' => $t['landing_block_stats_bar_desc'] ?? 'Horizontal statistics strip.',
            'icon' => 'fa-chart-line',
            'anchor' => '',
            'variants' => [
                'inline' => $t['landing_var_stats_inline'] ?? 'Inline',
                'boxed' => $t['landing_var_stats_boxed'] ?? 'Boxed',
                'dark' => $t['landing_var_stats_dark'] ?? 'Dark strip',
            ],
        ],
        'comparison' => [
            'label' => $t['landing_block_comparison'] ?? 'Comparison',
            'desc' => $t['landing_block_comparison_desc'] ?? 'Compare two options side by side.',
            'icon' => 'fa-scale-balanced',
            'anchor' => '',
            'variants' => [
                'table' => $t['landing_var_comparison_table'] ?? 'Table',
                'side' => $t['landing_var_comparison_side'] ?? 'Side by side',
            ],
        ],
        'contact_bar' => [
            'label' => $t['landing_block_contact_bar'] ?? 'Quick contact bar',
            'desc' => $t['landing_block_contact_bar_desc'] ?? 'Phone, email and CTA strip.',
            'icon' => 'fa-phone',
            'anchor' => '',
            'variants' => [
                'strip' => $t['landing_var_contact_bar_strip'] ?? 'Strip',
                'card' => $t['landing_var_contact_bar_card'] ?? 'Card',
                'gradient' => $t['landing_var_contact_bar_gradient'] ?? 'Gradient',
            ],
        ],
        'icon_list' => [
            'label' => $t['landing_block_icon_list'] ?? 'Icon list',
            'desc' => $t['landing_block_icon_list_desc'] ?? 'List with icons and descriptions.',
            'icon' => 'fa-list-check',
            'anchor' => '',
            'variants' => [
                'vertical' => $t['landing_var_icon_list_vertical'] ?? 'Vertical',
                'horizontal' => $t['landing_var_icon_list_horizontal'] ?? 'Horizontal',
                'check' => $t['landing_var_icon_list_check'] ?? 'Checklist',
            ],
        ],
        'app_cta' => [
            'label' => $t['landing_block_app_cta'] ?? 'App download',
            'desc' => $t['landing_block_app_cta_desc'] ?? 'App Store and Google Play buttons.',
            'icon' => 'fa-mobile-screen-button',
            'anchor' => 'app',
            'variants' => [
                'badges' => $t['landing_var_app_badges'] ?? 'Store badges',
                'row' => $t['landing_var_app_row'] ?? 'Button row',
            ],
        ],
        'messengers' => [
            'label' => $t['landing_block_messengers'] ?? 'Messengers',
            'desc' => $t['landing_block_messengers_desc'] ?? 'WhatsApp, Telegram and other chat buttons.',
            'icon' => 'fa-comments',
            'anchor' => 'messengers',
            'variants' => [
                'icons' => $t['landing_var_messengers_icons'] ?? 'Icon buttons',
                'cards' => $t['landing_var_messengers_cards'] ?? 'Cards',
                'bar' => $t['landing_var_messengers_bar'] ?? 'Bar',
            ],
        ],
        'slider' => [
            'label' => $t['landing_block_slider'] ?? 'Slider / carousel',
            'desc' => $t['landing_block_slider_desc'] ?? 'Image slides with titles, autoplay and controls.',
            'icon' => 'fa-images',
            'anchor' => 'slider',
            'variants' => [
                'fade' => $t['landing_var_slider_fade'] ?? 'Fade',
                'slide' => $t['landing_var_slider_slide'] ?? 'Slide',
                'cards' => $t['landing_var_slider_cards'] ?? 'Cards scroll',
                'hero' => $t['landing_var_slider_hero'] ?? 'Hero overlay',
                'thumbnails' => $t['landing_var_slider_thumbnails'] ?? 'With thumbnails',
            ],
        ],
        'accordion' => [
            'label' => $t['landing_block_accordion'] ?? 'Accordion',
            'desc' => $t['landing_block_accordion_desc'] ?? 'Collapsible FAQ-style sections.',
            'icon' => 'fa-layer-group',
            'anchor' => '',
            'variants' => [
                'simple' => $t['landing_var_accordion_simple'] ?? 'Simple',
                'bordered' => $t['landing_var_accordion_bordered'] ?? 'Bordered',
                'flush' => $t['landing_var_accordion_flush'] ?? 'Flush',
            ],
        ],
        'tabs' => [
            'label' => $t['landing_block_tabs'] ?? 'Tabs',
            'desc' => $t['landing_block_tabs_desc'] ?? 'Tabbed content panels.',
            'icon' => 'fa-folder',
            'anchor' => '',
            'variants' => [
                'pills' => $t['landing_var_tabs_pills'] ?? 'Pills',
                'underline' => $t['landing_var_tabs_underline'] ?? 'Underline',
                'boxed' => $t['landing_var_tabs_boxed'] ?? 'Boxed',
            ],
        ],
        'marquee' => [
            'label' => $t['landing_block_marquee'] ?? 'Marquee strip',
            'desc' => $t['landing_block_marquee_desc'] ?? 'Scrolling text or badges.',
            'icon' => 'fa-align-left',
            'anchor' => '',
            'variants' => [
                'text' => $t['landing_var_marquee_text'] ?? 'Text scroll',
                'badges' => $t['landing_var_marquee_badges'] ?? 'Badge scroll',
            ],
        ],
    ];
}

/** @return array<string, mixed>|null */
function hs_landing_default_block_ext(string $type, array $t): ?array
{
    $id = 'b_' . $type . '_' . substr(md5((string) microtime(true)), 0, 6);
    $base = ['id' => $id, 'type' => $type, 'variant' => '', 'on' => true, 'color' => ''];

    return match ($type) {
        'buttons' => array_merge($base, [
            'variant' => 'row',
            'section_title' => '',
            'items' => [
                ['text' => $t['landing_btn_primary'] ?? 'Get started', 'url' => '#contact', 'style' => 'primary'],
                ['text' => $t['landing_btn_secondary'] ?? 'Learn more', 'url' => '#about', 'style' => 'outline'],
                ['text' => $t['landing_btn_ghost'] ?? 'Call us', 'url' => 'tel:', 'style' => 'ghost'],
            ],
        ]),
        'cards' => array_merge($base, [
            'variant' => 'grid',
            'section_title' => $t['landing_cards_default_title'] ?? 'Why choose us',
            'items' => [
                ['icon' => 'fa-star', 'title' => $t['landing_card_1_title'] ?? 'Quality', 'text' => $t['landing_card_1_text'] ?? 'Top-notch service.', 'cta_text' => '', 'cta_url' => ''],
                ['icon' => 'fa-bolt', 'title' => $t['landing_card_2_title'] ?? 'Fast', 'text' => $t['landing_card_2_text'] ?? 'Quick turnaround.', 'cta_text' => '', 'cta_url' => ''],
                ['icon' => 'fa-heart', 'title' => $t['landing_card_3_title'] ?? 'Care', 'text' => $t['landing_card_3_text'] ?? 'We care about you.', 'cta_text' => '', 'cta_url' => ''],
            ],
        ]),
        'social' => array_merge($base, [
            'variant' => 'icons',
            'section_title' => $t['landing_social_default_title'] ?? 'Follow us',
            'items' => [
                ['network' => 'facebook', 'url' => 'https://facebook.com/', 'label' => 'Facebook'],
                ['network' => 'instagram', 'url' => 'https://instagram.com/', 'label' => 'Instagram'],
                ['network' => 'linkedin', 'url' => 'https://linkedin.com/', 'label' => 'LinkedIn'],
            ],
        ]),
        'trust' => array_merge($base, [
            'variant' => 'row',
            'section_title' => '',
            'items' => [
                ['icon' => 'fa-shield-halved', 'title' => $t['landing_trust_1'] ?? 'Secure', 'text' => $t['landing_trust_1_text'] ?? 'SSL protected'],
                ['icon' => 'fa-award', 'title' => $t['landing_trust_2'] ?? 'Certified', 'text' => $t['landing_trust_2_text'] ?? 'Licensed pros'],
                ['icon' => 'fa-clock', 'title' => $t['landing_trust_3'] ?? '24/7', 'text' => $t['landing_trust_3_text'] ?? 'Always available'],
            ],
        ]),
        'callout' => array_merge($base, [
            'variant' => 'tip',
            'title' => $t['landing_callout_default_title'] ?? 'Good to know',
            'text' => $t['landing_callout_default_text'] ?? 'Free consultation on your first visit.',
            'cta_text' => $t['landing_default_cta'] ?? 'Book now',
            'cta_url' => '#contact',
        ]),
        'media_text' => array_merge($base, [
            'variant' => 'split',
            'title' => $t['landing_media_default_title'] ?? 'See the difference',
            'text' => $t['landing_media_default_text'] ?? 'Professional results you can trust.',
            'image' => '',
            'side' => 'left',
            'cta_text' => $t['landing_default_cta'] ?? 'Contact us',
            'cta_url' => '#contact',
        ]),
        'menu' => array_merge($base, [
            'variant' => 'list',
            'section_title' => $t['landing_menu_default_title'] ?? 'Our menu',
            'items' => [
                ['name' => $t['landing_menu_1_name'] ?? 'House special', 'desc' => $t['landing_menu_1_desc'] ?? 'Chef recommendation', 'price' => '250 ₴'],
                ['name' => $t['landing_menu_2_name'] ?? 'Classic dish', 'desc' => $t['landing_menu_2_desc'] ?? 'Customer favorite', 'price' => '180 ₴'],
                ['name' => $t['landing_menu_3_name'] ?? 'Daily soup', 'desc' => $t['landing_menu_3_desc'] ?? 'Fresh ingredients', 'price' => '95 ₴'],
            ],
        ]),
        'stats_bar' => array_merge($base, [
            'variant' => 'inline',
            'items' => [
                ['value' => '500+', 'label' => $t['landing_stat_clients'] ?? 'Clients'],
                ['value' => '10+', 'label' => $t['landing_stat_years'] ?? 'Years'],
                ['value' => '4.9', 'label' => $t['landing_stat_rating'] ?? 'Rating'],
                ['value' => '24/7', 'label' => $t['landing_stat_support'] ?? 'Support'],
            ],
        ]),
        'comparison' => array_merge($base, [
            'variant' => 'table',
            'section_title' => $t['landing_comparison_default_title'] ?? 'Compare plans',
            'left_title' => $t['landing_comparison_basic'] ?? 'Basic',
            'right_title' => $t['landing_comparison_pro'] ?? 'Pro',
            'items' => [
                ['label' => $t['landing_comparison_feat_1'] ?? 'Support', 'a' => $t['landing_comparison_email'] ?? 'Email', 'b' => $t['landing_comparison_priority'] ?? 'Priority'],
                ['label' => $t['landing_comparison_feat_2'] ?? 'Storage', 'a' => '5 GB', 'b' => '50 GB'],
                ['label' => $t['landing_comparison_feat_3'] ?? 'Users', 'a' => '1', 'b' => '10'],
            ],
        ]),
        'contact_bar' => array_merge($base, [
            'variant' => 'strip',
            'phone' => '+380 00 000 00 00',
            'email' => 'hello@example.com',
            'whatsapp' => '',
            'cta_text' => $t['landing_default_cta'] ?? 'Contact us',
            'cta_url' => '#contact',
        ]),
        'icon_list' => array_merge($base, [
            'variant' => 'vertical',
            'section_title' => $t['landing_icon_list_default_title'] ?? 'What we offer',
            'items' => [
                ['icon' => 'fa-check', 'title' => $t['landing_il_1_title'] ?? 'Free estimate', 'text' => $t['landing_il_1_text'] ?? 'No obligation quote.'],
                ['icon' => 'fa-check', 'title' => $t['landing_il_2_title'] ?? 'Fast delivery', 'text' => $t['landing_il_2_text'] ?? 'On time, every time.'],
                ['icon' => 'fa-check', 'title' => $t['landing_il_3_title'] ?? 'Warranty', 'text' => $t['landing_il_3_text'] ?? '12-month guarantee.'],
            ],
        ]),
        'app_cta' => array_merge($base, [
            'variant' => 'badges',
            'title' => $t['landing_app_default_title'] ?? 'Download our app',
            'text' => $t['landing_app_default_text'] ?? 'Book and manage on the go.',
            'ios_url' => '#',
            'android_url' => '#',
        ]),
        'messengers' => array_merge($base, [
            'variant' => 'icons',
            'section_title' => $t['landing_messengers_default_title'] ?? 'Write to us',
            'items' => [
                ['channel' => 'whatsapp', 'value' => '+380501234567', 'label' => 'WhatsApp'],
                ['channel' => 'telegram', 'value' => '@username', 'label' => 'Telegram'],
            ],
        ]),
        'slider' => array_merge($base, [
            'variant' => 'fade',
            'height' => 'md',
            'autoplay' => true,
            'interval' => 5000,
            'arrows' => true,
            'dots' => true,
            'section_title' => '',
            'items' => [
                ['image' => '', 'hue' => 160, 'title' => $t['landing_slider_1_title'] ?? 'Welcome', 'subtitle' => $t['landing_slider_1_sub'] ?? 'Your headline here', 'cta_text' => $t['landing_default_cta'] ?? 'Contact', 'cta_url' => '#contact'],
                ['image' => '', 'hue' => 200, 'title' => $t['landing_slider_2_title'] ?? 'Quality service', 'subtitle' => $t['landing_slider_2_sub'] ?? 'Second slide text', 'cta_text' => '', 'cta_url' => '#'],
                ['image' => '', 'hue' => 280, 'title' => $t['landing_slider_3_title'] ?? 'Get started', 'subtitle' => $t['landing_slider_3_sub'] ?? 'Third slide text', 'cta_text' => $t['landing_default_cta'] ?? 'Contact', 'cta_url' => '#contact'],
            ],
        ]),
        'accordion' => array_merge($base, [
            'variant' => 'simple',
            'section_title' => $t['landing_accordion_default_title'] ?? 'FAQ',
            'items' => [
                ['title' => $t['landing_acc_1_q'] ?? 'How does it work?', 'text' => $t['landing_acc_1_a'] ?? 'Simple and fast process.'],
                ['title' => $t['landing_acc_2_q'] ?? 'What are the prices?', 'text' => $t['landing_acc_2_a'] ?? 'Transparent pricing, no hidden fees.'],
                ['title' => $t['landing_acc_3_q'] ?? 'How to contact?', 'text' => $t['landing_acc_3_a'] ?? 'Use the form below or call us.'],
            ],
        ]),
        'tabs' => array_merge($base, [
            'variant' => 'pills',
            'section_title' => $t['landing_tabs_default_title'] ?? 'Details',
            'items' => [
                ['title' => $t['landing_tab_1'] ?? 'Overview', 'text' => $t['landing_tab_1_text'] ?? 'General information about our services.'],
                ['title' => $t['landing_tab_2'] ?? 'Features', 'text' => $t['landing_tab_2_text'] ?? 'Key benefits and capabilities.'],
                ['title' => $t['landing_tab_3'] ?? 'Support', 'text' => $t['landing_tab_3_text'] ?? 'We are here to help you.'],
            ],
        ]),
        'marquee' => array_merge($base, [
            'variant' => 'text',
            'section_title' => '',
            'text' => $t['landing_marquee_default'] ?? 'Trusted by 500+ clients · Fast delivery · Quality guaranteed · ',
            'speed' => 'normal',
        ]),
        default => null,
    };
}

function hs_landing_social_icon(string $network): string
{
    return match ($network) {
        'facebook' => 'fa-facebook-f',
        'instagram' => 'fa-instagram',
        'linkedin' => 'fa-linkedin-in',
        'twitter', 'x' => 'fa-x-twitter',
        'youtube' => 'fa-youtube',
        'tiktok' => 'fa-tiktok',
        'telegram' => 'fa-telegram',
        'whatsapp' => 'fa-whatsapp',
        'pinterest' => 'fa-pinterest-p',
        default => 'fa-link',
    };
}

function hs_landing_btn_class(string $style): string
{
    return match ($style) {
        'secondary' => 'btn secondary',
        'outline' => 'btn outline',
        'ghost' => 'btn ghost',
        'link' => 'btn link',
        default => 'btn primary',
    };
}

/**
 * @param array<string, mixed> $block
 * @param array<string, mixed> $data
 * @param callable(string): string $h
 */
function hs_landing_render_block_ext(array $block, array $data, callable $h): ?string
{
    if (empty($block['on'])) {
        return '';
    }
    $type = (string) ($block['type'] ?? '');
    $registry = hs_landing_block_registry_ext([]);
    if (!isset($registry[$type])) {
        return null;
    }
    $anchor = (string) ($registry[$type]['anchor'] ?? '');
    $idAttr = $anchor !== '' ? ' id="' . $h($anchor) . '"' : '';

    return match ($type) {
        'buttons' => hs_landing_render_buttons($block, $h),
        'cards' => hs_landing_render_cards($block, $data, $h),
        'social' => hs_landing_render_social($block, $h, $idAttr),
        'trust' => hs_landing_render_trust($block, $data, $h),
        'callout' => hs_landing_render_callout($block, $h),
        'media_text' => hs_landing_render_media_text($block, $h),
        'menu' => hs_landing_render_menu($block, $h, $idAttr),
        'stats_bar' => hs_landing_render_stats_bar($block, $h),
        'comparison' => hs_landing_render_comparison($block, $h),
        'contact_bar' => hs_landing_render_contact_bar($block, $h),
        'icon_list' => hs_landing_render_icon_list($block, $data, $h),
        'app_cta' => hs_landing_render_app_cta($block, $h, $idAttr),
        'messengers' => hs_landing_render_messengers_block($block, $h, $idAttr),
        'slider' => hs_landing_render_slider($block, $data, $h),
        'accordion' => hs_landing_render_accordion($block, $h),
        'tabs' => hs_landing_render_tabs($block, $h),
        'marquee' => hs_landing_render_marquee($block, $h),
        default => null,
    };
}

/** @param array<string, mixed> $block */
function hs_landing_render_buttons(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'row');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $text = (string) ($item['text'] ?? '');
        if ($text === '') {
            continue;
        }
        $url = (string) ($item['url'] ?? '#');
        $style = (string) ($item['style'] ?? 'primary');
        $items .= '<a class="' . $h(hs_landing_btn_class($style)) . '" href="' . $h($url) . '">' . $h($text) . '</a>';
    }
    $wrapCls = 'buttons-block buttons-' . preg_replace('/[^a-z0-9_-]/i', '', $variant);
    $innerCls = 'btn-group btn-group-' . preg_replace('/[^a-z0-9_-]/i', '', $variant);
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="' . $wrapCls . '"><div class="wrap">' . $title
        . '<div class="' . $innerCls . '">' . $items . '</div></div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_cards(array $block, array $data, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'grid');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $card) {
        if (!is_array($card)) {
            continue;
        }
        $icon = preg_replace('/[^a-z0-9-]/i', '', (string) ($card['icon'] ?? 'fa-star')) ?: 'fa-star';
        $cta = '';
        $ctaText = (string) ($card['cta_text'] ?? '');
        if ($ctaText !== '') {
            $cta = '<a class="btn primary" href="' . $h((string) ($card['cta_url'] ?? '#')) . '">' . $h($ctaText) . '</a>';
        }
        $items .= '<article class="content-card"><div class="content-card-icon">' . hs_landing_icon_tag($icon, $data) . '</div>'
            . '<h3>' . $h((string) ($card['title'] ?? '')) . '</h3><p>' . $h((string) ($card['text'] ?? '')) . '</p>' . $cta . '</article>';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="cards-block cards-' . $h($variant) . '"><div class="wrap">' . $title
        . '<div class="content-cards content-cards-' . $h($variant) . '">' . $items . '</div></div></section>';
}

function hs_landing_render_social(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'icons');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $net = (string) ($item['network'] ?? 'link');
        $url = (string) ($item['url'] ?? '#');
        $label = (string) ($item['label'] ?? $net);
        $icon = hs_landing_social_icon($net);
        $items .= '<a class="social-link social-' . $h($net) . '" href="' . $h($url) . '" target="_blank" rel="noopener">'
            . '<i class="fa-brands ' . $h($icon) . '"></i><span>' . $h($label) . '</span></a>';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="social-block social-' . $h($variant) . '"' . $idAttr . '><div class="wrap">'
        . $title . '<div class="social-links social-links-' . $h($variant) . '">' . $items . '</div></div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_trust(array $block, array $data, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'row');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $icon = preg_replace('/[^a-z0-9-]/i', '', (string) ($item['icon'] ?? 'fa-shield')) ?: 'fa-shield';
        $items .= '<div class="trust-item"><div class="trust-icon">' . hs_landing_icon_tag($icon, $data) . '</div>'
            . '<strong>' . $h((string) ($item['title'] ?? '')) . '</strong><span>' . $h((string) ($item['text'] ?? '')) . '</span></div>';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="trust-block trust-' . $h($variant) . '"><div class="wrap">' . $title
        . '<div class="trust-items trust-items-' . $h($variant) . '">' . $items . '</div></div></section>';
}

function hs_landing_render_callout(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'tip');
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $ctaText = (string) ($block['cta_text'] ?? '');
    $cta = $ctaText !== '' ? '<a class="btn primary" href="' . $h((string) ($block['cta_url'] ?? '#')) . '">' . $h($ctaText) . '</a>' : '';
    $icon = match ($variant) {
        'success' => 'fa-circle-check',
        'promo' => 'fa-gift',
        'info' => 'fa-circle-info',
        default => 'fa-lightbulb',
    };

    return '<section class="callout-block callout-' . $h($variant) . '"><div class="wrap">'
        . '<div class="callout-inner"><i class="fa-solid ' . $h($icon) . '"></i><div>'
        . ($title !== '' ? '<h3>' . $h($title) . '</h3>' : '') . '<p>' . $h($text) . '</p>' . $cta
        . '</div></div></div></section>';
}

function hs_landing_render_media_text(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'split');
    $side = (string) ($block['side'] ?? 'left');
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $image = (string) ($block['image'] ?? '');
    $ctaText = (string) ($block['cta_text'] ?? '');
    $cta = $ctaText !== '' ? '<a class="btn primary" href="' . $h((string) ($block['cta_url'] ?? '#')) . '">' . $h($ctaText) . '</a>' : '';
    $imgHtml = $image !== ''
        ? '<img src="' . $h($image) . '" alt="">'
        : '<div class="media-placeholder"><i class="fa-solid fa-image"></i></div>';
    $sideCls = $side === 'right' ? ' media-right' : '';

    return '<section class="media-text-block media-' . $h($variant) . $sideCls . '"><div class="wrap">'
        . '<div class="media-text-grid"><div class="media-visual">' . $imgHtml . '</div>'
        . '<div class="media-copy"><h2>' . $h($title) . '</h2><p>' . $h($text) . '</p>' . $cta . '</div></div></div></section>';
}

function hs_landing_render_menu(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'list');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $items .= '<div class="menu-item"><div class="menu-item-head"><strong>' . $h((string) ($item['name'] ?? '')) . '</strong>'
            . '<span class="menu-price">' . $h((string) ($item['price'] ?? '')) . '</span></div>'
            . '<p>' . $h((string) ($item['desc'] ?? '')) . '</p></div>';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="menu-block menu-' . $h($variant) . '"' . $idAttr . '><div class="wrap">' . $title
        . '<div class="menu-items menu-items-' . $h($variant) . '">' . $items . '</div></div></section>';
}

function hs_landing_render_stats_bar(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'inline');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $items .= '<div class="stats-bar-item"><strong>' . $h((string) ($item['value'] ?? '')) . '</strong>'
            . '<span>' . $h((string) ($item['label'] ?? '')) . '</span></div>';
    }

    return '<section class="stats-bar-block stats-bar-' . $h($variant) . '"><div class="wrap">'
        . '<div class="stats-bar-inner">' . $items . '</div></div></section>';
}

function hs_landing_render_comparison(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'table');
    $sec = (string) ($block['section_title'] ?? '');
    $left = (string) ($block['left_title'] ?? 'A');
    $right = (string) ($block['right_title'] ?? 'B');
    $rows = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $rows .= '<tr><td>' . $h((string) ($item['label'] ?? '')) . '</td><td>' . $h((string) ($item['a'] ?? ''))
            . '</td><td>' . $h((string) ($item['b'] ?? '')) . '</td></tr>';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="comparison-block comparison-' . $h($variant) . '"><div class="wrap">' . $title
        . '<table class="comparison-table"><thead><tr><th></th><th>' . $h($left) . '</th><th>' . $h($right) . '</th></tr></thead>'
        . '<tbody>' . $rows . '</tbody></table></div></section>';
}

function hs_landing_render_contact_bar(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'strip');
    $phone = (string) ($block['phone'] ?? '');
    $email = (string) ($block['email'] ?? '');
    $wa = (string) ($block['whatsapp'] ?? '');
    $ctaText = (string) ($block['cta_text'] ?? '');
    $links = '';
    if ($phone !== '') {
        $links .= '<a href="tel:' . $h(preg_replace('/\s+/', '', $phone) ?? $phone) . '"><i class="fa-solid fa-phone"></i> ' . $h($phone) . '</a>';
    }
    if ($email !== '') {
        $links .= '<a href="mailto:' . $h($email) . '"><i class="fa-solid fa-envelope"></i> ' . $h($email) . '</a>';
    }
    if ($wa !== '') {
        $links .= '<a href="https://wa.me/' . $h(preg_replace('/\D/', '', $wa)) . '" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>';
    }
    $cta = $ctaText !== '' ? '<a class="btn primary" href="' . $h((string) ($block['cta_url'] ?? '#')) . '">' . $h($ctaText) . '</a>' : '';

    return '<section class="contact-bar-block contact-bar-' . $h($variant) . '"><div class="wrap">'
        . '<div class="contact-bar-inner"><div class="contact-bar-links">' . $links . '</div>' . $cta . '</div></div></section>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_icon_list(array $block, array $data, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'vertical');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $icon = preg_replace('/[^a-z0-9-]/i', '', (string) ($item['icon'] ?? 'fa-check')) ?: 'fa-check';
        $items .= '<div class="icon-list-item"><div class="icon-list-icon">' . hs_landing_icon_tag($icon, $data) . '</div>'
            . '<div><strong>' . $h((string) ($item['title'] ?? '')) . '</strong><p>' . $h((string) ($item['text'] ?? '')) . '</p></div></div>';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="icon-list-block icon-list-' . $h($variant) . '"><div class="wrap">' . $title
        . '<div class="icon-list-items icon-list-items-' . $h($variant) . '">' . $items . '</div></div></section>';
}

function hs_landing_render_app_cta(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'badges');
    $title = (string) ($block['title'] ?? '');
    $text = (string) ($block['text'] ?? '');
    $ios = (string) ($block['ios_url'] ?? '#');
    $android = (string) ($block['android_url'] ?? '#');
    $btns = '<a class="app-badge app-ios" href="' . $h($ios) . '"><i class="fa-brands fa-apple"></i> App Store</a>'
        . '<a class="app-badge app-android" href="' . $h($android) . '"><i class="fa-brands fa-google-play"></i> Google Play</a>';

    return '<section class="app-cta-block app-cta-' . $h($variant) . '"' . $idAttr . '><div class="wrap">'
        . '<div class="app-cta-inner"><div><h2>' . $h($title) . '</h2><p>' . $h($text) . '</p></div>'
        . '<div class="app-badges">' . $btns . '</div></div></div></section>';
}

function hs_landing_render_accordion(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'simple');
    $sec = (string) ($block['section_title'] ?? '');
    $items = '';
    foreach ((array) ($block['items'] ?? []) as $i => $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = (string) ($item['title'] ?? '');
        $text = (string) ($item['text'] ?? '');
        if ($title === '') {
            continue;
        }
        $open = $i === 0 ? ' is-open' : '';
        $items .= '<details class="acc-item' . $open . '"' . ($i === 0 ? ' open' : '') . '><summary>' . $h($title) . '</summary><p>' . nl2br($h($text)) . '</p></details>';
    }
    if ($items === '') {
        return '';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="accordion-block accordion-' . $h($variant) . '"><div class="wrap">' . $title
        . '<div class="accordion-items">' . $items . '</div></div></section>';
}

function hs_landing_render_tabs(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'pills');
    $sec = (string) ($block['section_title'] ?? '');
    $tabs = '';
    $panels = '';
    $i = 0;
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = (string) ($item['title'] ?? '');
        $text = (string) ($item['text'] ?? '');
        if ($title === '') {
            continue;
        }
        $active = $i === 0 ? ' is-active' : '';
        $tabs .= '<button type="button" class="tabs-btn' . $active . '" data-tab="' . $h((string) $i) . '">' . $h($title) . '</button>';
        $panels .= '<div class="tabs-panel' . $active . '" data-tab-panel="' . $h((string) $i) . '">' . nl2br($h($text)) . '</div>';
        ++$i;
    }
    if ($tabs === '') {
        return '';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';

    return '<section class="tabs-block tabs-' . $h($variant) . '" data-tabs><div class="wrap">' . $title
        . '<div class="tabs-nav">' . $tabs . '</div><div class="tabs-panels">' . $panels . '</div></div></section>';
}

function hs_landing_render_marquee(array $block, callable $h): string
{
    $variant = (string) ($block['variant'] ?? 'text');
    $text = (string) ($block['text'] ?? '');
    $speed = (string) ($block['speed'] ?? 'normal');
    if (!in_array($speed, ['slow', 'normal', 'fast'], true)) {
        $speed = 'normal';
    }
    if ($variant === 'badges') {
        $inner = '';
        foreach ((array) ($block['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label !== '') {
                $inner .= '<span class="marquee-badge">' . $h($label) . '</span>';
            }
        }
        if ($inner === '') {
            return '';
        }
    } else {
        if ($text === '') {
            return '';
        }
        $inner = '<span>' . $h($text) . '</span><span aria-hidden="true">' . $h($text) . '</span>';
    }

    return '<section class="marquee-block marquee-' . $h($variant) . ' marquee-speed-' . $h($speed) . '"><div class="marquee-track">' . $inner . '</div></section>';
}

function hs_landing_interactive_js(): string
{
    return '(function(){document.querySelectorAll("[data-tabs]").forEach(function(root){root.querySelectorAll(".tabs-btn").forEach(function(btn){btn.addEventListener("click",function(){var id=btn.getAttribute("data-tab");root.querySelectorAll(".tabs-btn").forEach(function(b){b.classList.toggle("is-active",b===btn);});root.querySelectorAll(".tabs-panel").forEach(function(p){p.classList.toggle("is-active",p.getAttribute("data-tab-panel")===id);});});});});})();';
}

function hs_landing_page_css_ext(): string
{
    return '.buttons-block,.cards-block,.social-block,.trust-block,.callout-block,.media-text-block,.menu-block,.stats-bar-block,.comparison-block,.contact-bar-block,.icon-list-block,.app-cta-block,.messengers-block,.accordion-block,.tabs-block{padding:2.5rem 0}'
        . '.btn-group{display:flex;flex-wrap:wrap;gap:.65rem;align-items:center}.btn-group-center{justify-content:center}.btn-group-stacked{flex-direction:column;align-items:stretch}'
        . '.btn.secondary{background:color-mix(in srgb,var(--c) 18%,#fff);color:var(--c)}'
        . '.btn.outline{background:transparent;border:2px solid var(--c);color:var(--c)}'
        . '.btn.ghost{background:transparent;color:var(--c);box-shadow:none}.btn.link{background:transparent;color:var(--c);text-decoration:underline;box-shadow:none;padding:0}'
        . '.content-cards{display:grid;gap:1rem}.content-cards-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.content-cards-row{display:flex;gap:1rem;overflow-x:auto}'
        . '.content-cards-featured{grid-template-columns:1fr;max-width:28rem;margin:0 auto}'
        . '.content-card{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.25rem}.content-card-icon{color:var(--c);margin-bottom:.5rem;font-size:1.25rem}'
        . '.content-card h3{margin:0 0 .35rem}.content-card p{color:#64748b;font-size:.88rem;line-height:1.55;margin:0 0 .75rem}'
        . '.social-links{display:flex;flex-wrap:wrap;gap:.65rem;align-items:center}.social-links-icons .social-link span{display:none}'
        . '.social-links-bar{justify-content:center;padding:1rem;background:#f8fafc;border-radius:1rem}'
        . '.social-link{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .85rem;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-weight:600;font-size:.85rem}'
        . '.social-link i{font-size:1rem;color:var(--c)}.social-links-pills .social-link{background:color-mix(in srgb,var(--c) 10%,#fff);border-color:color-mix(in srgb,var(--c) 25%,#fff)}'
        . '.trust-items{display:flex;flex-wrap:wrap;gap:1rem}.trust-items-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}'
        . '.trust-items-strip{justify-content:center;background:#f8fafc;border-radius:1rem;padding:1.25rem}'
        . '.trust-item{text-align:center;flex:1;min-width:7rem}.trust-icon{color:var(--c);font-size:1.35rem;margin-bottom:.35rem}'
        . '.trust-item strong{display:block;font-size:.92rem}.trust-item span{color:#64748b;font-size:.8rem}'
        . '.callout-inner{display:flex;gap:1rem;align-items:flex-start;padding:1.25rem;border-radius:1rem;border:1px solid #e2e8f0;background:#fff}'
        . '.callout-tip .callout-inner{border-color:color-mix(in srgb,var(--c) 30%,#fff);background:color-mix(in srgb,var(--c) 6%,#fff)}.callout-tip i{color:var(--c)}'
        . '.callout-success .callout-inner{border-color:#86efac;background:#f0fdf4}.callout-success i{color:#16a34a}'
        . '.callout-promo .callout-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border:none}.callout-promo i,.callout-promo p{color:#fff}'
        . '.callout-inner h3{margin:0 0 .35rem}.callout-inner p{margin:0 0 .75rem;color:#64748b;line-height:1.6}'
        . '.media-text-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:center}.media-right .media-visual{order:2}'
        . '.media-placeholder,.media-visual img{border-radius:1rem;aspect-ratio:4/3;object-fit:cover;width:100%}'
        . '.media-placeholder{display:grid;place-items:center;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-size:2rem}'
        . '.media-card .media-text-grid{background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.5rem}'
        . '.menu-items-list{display:flex;flex-direction:column;gap:.65rem}.menu-items-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}'
        . '.menu-item{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem}.menu-item-head{display:flex;justify-content:space-between;gap:1rem;margin-bottom:.25rem}'
        . '.menu-price{color:var(--c);font-weight:700;white-space:nowrap}.menu-item p{margin:0;color:#64748b;font-size:.88rem}'
        . '.menu-elegant .menu-item{border:none;border-bottom:1px dashed #e2e8f0;border-radius:0;padding:.85rem 0}'
        . '.stats-bar-inner{display:flex;flex-wrap:wrap;gap:1rem;justify-content:center}.stats-bar-boxed .stats-bar-inner{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.25rem}'
        . '.stats-bar-dark .stats-bar-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border-radius:1rem;padding:1.5rem}'
        . '.stats-bar-dark .stats-bar-item span{color:rgba(255,255,255,.85)}.stats-bar-item{text-align:center;min-width:5rem}'
        . '.stats-bar-item strong{display:block;font-size:1.5rem;color:var(--c)}.stats-bar-dark .stats-bar-item strong{color:#fff}'
        . '.stats-bar-item span{color:#64748b;font-size:.82rem}'
        . '.comparison-table{width:100%;border-collapse:collapse;background:#fff;border-radius:1rem;overflow:hidden;border:1px solid #e2e8f0}'
        . '.comparison-table th,.comparison-table td{padding:.85rem 1rem;border-bottom:1px solid #e2e8f0;text-align:center;font-size:.9rem}'
        . '.comparison-table th{background:#f8fafc;color:#64748b}.comparison-table td:first-child{text-align:left;font-weight:600}'
        . '.contact-bar-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;background:#fff;border:1px solid #e2e8f0;border-radius:1rem}'
        . '.contact-bar-gradient .contact-bar-inner{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border:none}'
        . '.contact-bar-links{display:flex;flex-wrap:wrap;gap:1rem}.contact-bar-links a{color:inherit;text-decoration:none;font-weight:600;font-size:.88rem;display:inline-flex;align-items:center;gap:.35rem}'
        . '.icon-list-items-vertical{display:flex;flex-direction:column;gap:.75rem}.icon-list-items-horizontal{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}'
        . '.icon-list-items-check .icon-list-icon{color:#16a34a}'
        . '.icon-list-item{display:flex;gap:.75rem;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1rem}'
        . '.icon-list-icon{color:var(--c);margin-top:.15rem}.icon-list-item p{margin:.25rem 0 0;color:#64748b;font-size:.88rem;line-height:1.5}'
        . '.app-cta-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.5rem}'
        . '.app-badges{display:flex;flex-wrap:wrap;gap:.65rem}.app-badge{display:inline-flex;align-items:center;gap:.45rem;padding:.65rem 1rem;border-radius:.75rem;background:#0f172a;color:#fff;text-decoration:none;font-weight:700;font-size:.85rem}'
        . '.app-android{background:#1a472a}'
        . '.accordion-items{display:flex;flex-direction:column;gap:.5rem}.acc-item{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:0;overflow:hidden}'
        . '.acc-item summary{padding:1rem 1.15rem;font-weight:700;cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center}'
        . '.acc-item summary::-webkit-details-marker{display:none}.acc-item summary::after{content:"+";color:var(--c);font-weight:700}'
        . '.acc-item[open] summary::after{content:"−"}.acc-item p{margin:0;padding:0 1.15rem 1rem;color:#64748b;line-height:1.6;font-size:.9rem}'
        . '.accordion-bordered .acc-item{border-width:2px}.accordion-flush .acc-item{border:none;border-bottom:1px solid #e2e8f0;border-radius:0}'
        . '.tabs-nav{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}.tabs-btn{padding:.5rem 1rem;border:1px solid #e2e8f0;border-radius:999px;background:#fff;color:#334155;font-weight:600;font-size:.88rem;cursor:pointer}'
        . '.tabs-btn.is-active{background:var(--c);color:#fff;border-color:var(--c)}.tabs-underline .tabs-btn{border:none;border-radius:0;border-bottom:2px solid transparent}'
        . '.tabs-underline .tabs-btn.is-active{border-bottom-color:var(--c);background:transparent;color:var(--c)}'
        . '.tabs-boxed .tabs-nav{background:#f8fafc;padding:.5rem;border-radius:.85rem}.tabs-panel{display:none;color:#64748b;line-height:1.65;font-size:.92rem}'
        . '.tabs-panel.is-active{display:block}.tabs-boxed .tabs-panel{background:#fff;border:1px solid #e2e8f0;border-radius:.85rem;padding:1.15rem;margin-top:.5rem}'
        . '.marquee-block{overflow:hidden;padding:1rem 0;background:color-mix(in srgb,var(--c) 8%,#fff);border-block:1px solid #e2e8f0}'
        . '.marquee-track{display:flex;gap:2rem;white-space:nowrap;animation:marqueeScroll 25s linear infinite;width:max-content}'
        . '.marquee-speed-slow .marquee-track{animation-duration:40s}.marquee-speed-fast .marquee-track{animation-duration:15s}'
        . '.marquee-track span{font-weight:600;color:#334155;font-size:.92rem}.marquee-badge{padding:.35rem .85rem;border-radius:999px;background:#fff;border:1px solid #e2e8f0;font-weight:700;font-size:.82rem;color:var(--c)}'
        . '@keyframes marqueeScroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}'
        . '@media(max-width:800px){.content-cards-grid,.trust-items-grid,.menu-items-grid,.icon-list-items-horizontal,.media-text-grid{grid-template-columns:1fr}.media-right .media-visual{order:0}}';
}