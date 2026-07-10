<?php
declare(strict_types=1);

/** @return array<string, array{label:string,icon:string,color:string,placeholder:string}> */
function hs_landing_messenger_channels(array $t): array
{
    return [
        'whatsapp' => [
            'label' => $t['landing_msg_whatsapp'] ?? 'WhatsApp',
            'icon' => 'fa-whatsapp',
            'color' => '#25D366',
            'placeholder' => $t['landing_msg_whatsapp_ph'] ?? '+380501234567',
        ],
        'telegram' => [
            'label' => $t['landing_msg_telegram'] ?? 'Telegram',
            'icon' => 'fa-telegram',
            'color' => '#229ED9',
            'placeholder' => $t['landing_msg_telegram_ph'] ?? '@username',
        ],
        'viber' => [
            'label' => $t['landing_msg_viber'] ?? 'Viber',
            'icon' => 'fa-viber',
            'color' => '#7360F2',
            'placeholder' => $t['landing_msg_viber_ph'] ?? '+380501234567',
        ],
        'messenger' => [
            'label' => $t['landing_msg_messenger'] ?? 'Messenger',
            'icon' => 'fa-facebook-messenger',
            'color' => '#0084FF',
            'placeholder' => $t['landing_msg_messenger_ph'] ?? 'pagename',
        ],
        'signal' => [
            'label' => $t['landing_msg_signal'] ?? 'Signal',
            'icon' => 'fa-comment-dots',
            'color' => '#3A76F0',
            'placeholder' => $t['landing_msg_signal_ph'] ?? '+380501234567',
        ],
        'skype' => [
            'label' => $t['landing_msg_skype'] ?? 'Skype',
            'icon' => 'fa-skype',
            'color' => '#00AFF0',
            'placeholder' => $t['landing_msg_skype_ph'] ?? 'username',
        ],
        'line' => [
            'label' => $t['landing_msg_line'] ?? 'LINE',
            'icon' => 'fa-line',
            'color' => '#06C755',
            'placeholder' => $t['landing_msg_line_ph'] ?? '@lineid',
        ],
    ];
}

/** @return array<string, string> */
function hs_landing_msg_float_styles(array $t): array
{
    return [
        'stack' => $t['landing_msg_style_stack'] ?? 'Stacked buttons',
        'expand' => $t['landing_msg_style_expand'] ?? 'Expandable FAB',
        'bar' => $t['landing_msg_style_bar'] ?? 'Bottom bar',
    ];
}

/** @return array<string, string> */
function hs_landing_msg_positions(array $t): array
{
    return [
        'right' => $t['landing_msg_pos_right'] ?? 'Bottom right',
        'left' => $t['landing_msg_pos_left'] ?? 'Bottom left',
    ];
}

function hs_landing_messenger_icon(string $channel): string
{
    $channels = hs_landing_messenger_channels([]);

    return (string) ($channels[$channel]['icon'] ?? 'fa-comment');
}

function hs_landing_messenger_color(string $channel): string
{
    $channels = hs_landing_messenger_channels([]);

    return (string) ($channels[$channel]['color'] ?? 'var(--c)');
}

function hs_landing_messenger_href(string $channel, string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    return match ($channel) {
        'whatsapp' => 'https://wa.me/' . preg_replace('/\D/', '', $value),
        'telegram' => str_starts_with($value, '@')
            ? 'https://t.me/' . ltrim($value, '@')
            : (str_contains($value, 't.me/') ? 'https://' . ltrim($value, 'https://') : 'https://t.me/' . $value),
        'viber' => 'viber://chat?number=' . rawurlencode(preg_replace('/[^\d+]/', '', $value)),
        'messenger' => str_contains($value, 'm.me') || str_contains($value, 'facebook.com')
            ? (preg_match('#^https?://#i', $value) ? $value : 'https://' . ltrim($value, '/'))
            : 'https://m.me/' . rawurlencode(ltrim($value, '@')),
        'signal' => 'https://signal.me/#p/' . rawurlencode(preg_replace('/[^\d+]/', '', $value)),
        'skype' => 'skype:' . rawurlencode($value) . '?chat',
        'line' => str_contains($value, 'line.me')
            ? (preg_match('#^https?://#i', $value) ? $value : 'https://' . ltrim($value, '/'))
            : 'https://line.me/R/ti/p/@' . ltrim($value, '@'),
        default => $value,
    };
}

/** @param array<string, mixed> $data @return list<array{channel:string,url:string,label:string,color:string,icon:string}> */
function hs_landing_messenger_items_from_data(array $data): array
{
    $channels = hs_landing_messenger_channels([]);
    $items = [];
    foreach ($channels as $key => $meta) {
        $raw = trim((string) ($data['msg_' . $key] ?? ''));
        if ($raw === '') {
            continue;
        }
        $url = hs_landing_messenger_href($key, $raw);
        if ($url === '') {
            continue;
        }
        $items[] = [
            'channel' => $key,
            'url' => $url,
            'label' => (string) ($meta['label'] ?? $key),
            'color' => (string) ($meta['color'] ?? 'var(--c)'),
            'icon' => (string) ($meta['icon'] ?? 'fa-comment'),
        ];
    }

    return $items;
}

/** @param array<string, mixed> $block @return list<array{channel:string,url:string,label:string,color:string,icon:string}> */
function hs_landing_messenger_items_from_block(array $block): array
{
    $items = [];
    foreach ((array) ($block['items'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $channel = (string) ($row['channel'] ?? '');
        $value = trim((string) ($row['value'] ?? ''));
        if ($channel === '' || $value === '') {
            continue;
        }
        $url = hs_landing_messenger_href($channel, $value);
        if ($url === '') {
            continue;
        }
        $label = trim((string) ($row['label'] ?? ''));
        if ($label === '') {
            $label = (string) (hs_landing_messenger_channels([])[$channel]['label'] ?? $channel);
        }
        $items[] = [
            'channel' => $channel,
            'url' => $url,
            'label' => $label,
            'color' => hs_landing_messenger_color($channel),
            'icon' => hs_landing_messenger_icon($channel),
        ];
    }

    return $items;
}

/** @param list<array{channel:string,url:string,label:string,color:string,icon:string}> $items */
function hs_landing_messenger_buttons_html(array $items, callable $h, string $wrapClass = 'msg-buttons'): string
{
    if ($items === []) {
        return '';
    }
    $html = '';
    foreach ($items as $item) {
        $html .= '<a class="msg-btn msg-' . $h($item['channel']) . '" href="' . $h($item['url']) . '" target="_blank" rel="noopener" style="--msg-color:' . $h($item['color']) . '">'
            . '<i class="fa-brands ' . $h($item['icon']) . '"></i><span>' . $h($item['label']) . '</span></a>';
    }

    return '<div class="' . $h($wrapClass) . '">' . $html . '</div>';
}

/** @param array<string, mixed> $data */
function hs_landing_render_floating_messengers(array $data, callable $h): string
{
    if (empty($data['msg_floating'])) {
        return '';
    }
    $items = hs_landing_messenger_items_from_data($data);
    if ($items === []) {
        return '';
    }
    $styles = hs_landing_msg_float_styles([]);
    $style = (string) ($data['msg_style'] ?? 'stack');
    if (!isset($styles[$style])) {
        $style = 'stack';
    }
    $positions = hs_landing_msg_positions([]);
    $pos = (string) ($data['msg_position'] ?? 'right');
    if (!isset($positions[$pos])) {
        $pos = 'right';
    }

    $buttons = '';
    foreach ($items as $item) {
        $buttons .= '<a class="msg-float-btn msg-' . $h($item['channel']) . '" href="' . $h($item['url']) . '" target="_blank" rel="noopener" title="' . $h($item['label']) . '" style="--msg-color:' . $h($item['color']) . '">'
            . '<i class="fa-brands ' . $h($item['icon']) . '"></i>'
            . ($style === 'bar' ? '<span>' . $h($item['label']) . '</span>' : '') . '</a>';
    }

    $toggle = $style === 'expand'
        ? '<button type="button" class="msg-float-toggle" aria-expanded="false" data-msg-toggle><i class="fa-solid fa-comments"></i><i class="fa-solid fa-xmark msg-float-close"></i></button>'
        : '';

    return '<div class="msg-float msg-float-' . $h($pos) . ' msg-float-' . $h($style) . '" data-msg-float>'
        . '<div class="msg-float-items">' . $buttons . '</div>' . $toggle . '</div>';
}

/** @param array<string, mixed> $block */
function hs_landing_render_messengers_block(array $block, callable $h, string $idAttr): string
{
    $variant = (string) ($block['variant'] ?? 'icons');
    $sec = (string) ($block['section_title'] ?? '');
    $items = hs_landing_messenger_items_from_block($block);
    if ($items === []) {
        return '';
    }
    $title = $sec !== '' ? '<h2 class="sec-title">' . $h($sec) . '</h2>' : '';
    $inner = hs_landing_messenger_buttons_html($items, $h, 'msg-buttons msg-buttons-' . preg_replace('/[^a-z0-9_-]/i', '', $variant));

    return '<section class="messengers-block messengers-' . $h($variant) . '"' . $idAttr . '><div class="wrap">'
        . $title . $inner . '</div></section>';
}

function hs_landing_messenger_css(): string
{
    return '.msg-buttons{display:flex;flex-wrap:wrap;gap:.65rem;align-items:center}'
        . '.msg-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1rem;border-radius:999px;background:var(--msg-color,#059669);color:#fff;text-decoration:none;font-weight:700;font-size:.88rem;box-shadow:0 8px 20px color-mix(in srgb,var(--msg-color,#059669) 35%,transparent)}'
        . '.msg-btn i{font-size:1.05rem}.msg-buttons-cards{flex-direction:column;align-items:stretch}'
        . '.msg-buttons-cards .msg-btn{border-radius:.85rem;justify-content:center;padding:.85rem 1rem}'
        . '.msg-buttons-bar{justify-content:center;padding:1rem;background:#f8fafc;border-radius:1rem;border:1px solid #e2e8f0}'
        . '.msg-float{position:fixed;z-index:90;display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;pointer-events:none}'
        . '.msg-float-left{left:1rem;right:auto;align-items:flex-start}.msg-float-right{right:1rem;bottom:1.25rem}'
        . '.msg-float-left{bottom:1.25rem}.msg-float-items{display:flex;flex-direction:column;gap:.45rem;pointer-events:auto}'
        . '.msg-float-bar{left:0;right:0;bottom:0;flex-direction:row;align-items:stretch;padding:.5rem 1rem;background:rgba(255,255,255,.96);border-top:1px solid #e2e8f0;backdrop-filter:blur(8px)}'
        . '.msg-float-bar .msg-float-items{flex-direction:row;flex-wrap:wrap;justify-content:center;gap:.35rem;width:100%}'
        . '.msg-float-btn{display:grid;place-items:center;width:3rem;height:3rem;border-radius:999px;background:var(--msg-color,var(--c));color:#fff;text-decoration:none;box-shadow:0 10px 28px color-mix(in srgb,var(--msg-color,var(--c)) 40%,transparent);font-size:1.25rem;transition:transform .15s ease}'
        . '.msg-float-btn:hover{transform:scale(1.06)}.msg-float-bar .msg-float-btn{width:auto;height:auto;padding:.45rem .75rem;border-radius:999px;font-size:.78rem;display:inline-flex;gap:.35rem;align-items:center}'
        . '.msg-float-toggle{pointer-events:auto;width:3.25rem;height:3.25rem;border:0;border-radius:999px;background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;font-size:1.2rem;cursor:pointer;box-shadow:0 12px 32px color-mix(in srgb,var(--c) 40%,transparent);display:grid;place-items:center;position:relative}'
        . '.msg-float-expand .msg-float-items{opacity:0;transform:translateY(.5rem);pointer-events:none;transition:opacity .2s ease,transform .2s ease}'
        . '.msg-float-expand.is-open .msg-float-items{opacity:1;transform:none;pointer-events:auto}'
        . '.msg-float-expand .msg-float-close{display:none}.msg-float-expand.is-open .msg-float-toggle .fa-comments{display:none}'
        . '.msg-float-expand.is-open .msg-float-close{display:block}'
        . '.messengers-block{padding:2.5rem 0}.messengers-icons .msg-buttons{justify-content:center}'
        . '.messengers-cards .msg-buttons-cards .msg-btn span{flex:1;text-align:left}';
}

function hs_landing_messenger_js(): string
{
    return '(function(){document.querySelectorAll("[data-msg-float]").forEach(function(wrap){var btn=wrap.querySelector("[data-msg-toggle]");if(!btn)return;btn.addEventListener("click",function(){var open=wrap.classList.toggle("is-open");btn.setAttribute("aria-expanded",open?"true":"false");});});})();';
}