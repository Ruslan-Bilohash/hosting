<?php
declare(strict_types=1);

define('HS_LANG_COOKIE', 'hs_lang');
/** Set after user explicitly picks a language (hides floating widget). */
define('HS_LANG_PICKED_COOKIE', 'hs_lang_picked');

$HS_LANGS = [
    'en' => ['label' => 'EN', 'name' => 'English', 'flag' => '🇬🇧', 'html' => 'en'],
    'no' => ['label' => 'NO', 'name' => 'Norsk', 'flag' => '🇳🇴', 'html' => 'nb-NO'],
    'uk' => ['label' => 'UA', 'name' => 'Українська', 'flag' => '🇺🇦', 'html' => 'uk'],
    'lt' => ['label' => 'LT', 'name' => 'Lietuvių', 'flag' => '🇱🇹', 'html' => 'lt'],
    'pl' => ['label' => 'PL', 'name' => 'Polski', 'flag' => '🇵🇱', 'html' => 'pl'],
    'sv' => ['label' => 'SV', 'name' => 'Svenska', 'flag' => '🇸🇪', 'html' => 'sv'],
];

function hs_langs(): array
{
    global $HS_LANGS;
    return $HS_LANGS;
}

/** @param array<string, mixed> $opts */
function hs_lang_set_cookie(string $name, string $value, int $days = 365): void
{
    setcookie($name, $value, [
        'expires' => time() + max(1, $days) * 86400,
        'path' => function_exists('hs_cookie_path') ? hs_cookie_path() : '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'httponly' => false,
    ]);
    $_COOKIE[$name] = $value;
}

function hs_lang_user_has_picked(): bool
{
    return !empty($_COOKIE[HS_LANG_PICKED_COOKIE]) && (string) $_COOKIE[HS_LANG_PICKED_COOKIE] === '1';
}

function hs_lang_mark_picked(): void
{
    hs_lang_set_cookie(HS_LANG_PICKED_COOKIE, '1', 400);
}

function hs_detect_lang(): string
{
    global $HS_LANGS;
    $codes = array_keys($HS_LANGS);

    if (!empty($_GET['lang']) && in_array($_GET['lang'], $codes, true)) {
        $chosen = (string) $_GET['lang'];
        hs_lang_set_cookie(HS_LANG_COOKIE, $chosen, 365);
        // Explicit ?lang= = user choice → hide floating widget next time
        hs_lang_mark_picked();

        return $chosen;
    }
    if (!empty($_COOKIE[HS_LANG_COOKIE]) && in_array($_COOKIE[HS_LANG_COOKIE], $codes, true)) {
        return (string) $_COOKIE[HS_LANG_COOKIE];
    }

    return 'en';
}

function hs_lang_url(string $code): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    parse_str($parts['query'] ?? '', $q);
    $q['lang'] = $code;
    return ($parts['path'] ?? '/') . '?' . http_build_query($q);
}

function hs_render_lang_dropdown(string $lang): string
{
    $meta = hs_langs()[$lang] ?? hs_langs()['en'];
    $html = '<div class="hp-lang-drop" data-hp-lang>'
        . '<button type="button" class="hp-lang-btn" data-hp-lang-btn aria-haspopup="listbox" aria-expanded="false">'
        . '<span>' . hs_h($meta['flag'] ?? '') . '</span>'
        . '<span>' . hs_h($meta['label'] ?? strtoupper($lang)) . '</span>'
        . '<i class="fa-solid fa-chevron-down"></i></button>'
        . '<div class="hp-lang-menu" data-hp-lang-menu hidden>';
    foreach (hs_langs() as $code => $item) {
        $html .= '<a href="' . hs_h(hs_lang_url($code)) . '" class="' . ($code === $lang ? 'active' : '') . '" role="option" data-hs-lang-pick="' . hs_h($code) . '">'
            . '<span>' . hs_h($item['flag'] ?? '') . '</span> ' . hs_h($item['name'] ?? $code)
            . '</a>';
    }

    return $html . '</div></div>';
}

/**
 * Floating language picker (chat-style). Hidden after first explicit choice; reopen from footer.
 *
 * @param array<string, mixed> $t
 */
function hs_render_lang_float_widget(string $lang, array $t = []): string
{
    $meta = hs_langs()[$lang] ?? hs_langs()['en'];
    $picked = hs_lang_user_has_picked();
    $title = (string) ($t['lang_float_title'] ?? 'Choose language');
    $hint = (string) ($t['lang_float_hint'] ?? 'We will remember your choice.');
    $openLabel = (string) ($t['lang_float_open'] ?? $t['nav_lang'] ?? 'Language');
    $closeLabel = (string) ($t['lang_float_close'] ?? $t['nav_close'] ?? 'Close');

    $html = '<div class="hs-lang-float' . ($picked ? ' is-dismissed' : '') . '" data-hs-lang-float data-picked="' . ($picked ? '1' : '0') . '">'
        . '<div class="hs-lang-float-panel" data-hs-lang-float-panel' . ($picked ? ' hidden' : '') . ' role="dialog" aria-label="' . hs_h($title) . '">'
        . '<div class="hs-lang-float-head">'
        . '<div class="hs-lang-float-head-text">'
        . '<strong>' . hs_h($title) . '</strong>'
        . '<span>' . hs_h($hint) . '</span>'
        . '</div>'
        . '<button type="button" class="hs-lang-float-x" data-hs-lang-float-minimize aria-label="' . hs_h($closeLabel) . '">'
        . '<i class="fa-solid fa-xmark" aria-hidden="true"></i></button>'
        . '</div>'
        . '<div class="hs-lang-float-list" role="listbox">';
    foreach (hs_langs() as $code => $item) {
        $active = $code === $lang ? ' is-active' : '';
        $html .= '<a class="hs-lang-float-item' . $active . '" role="option" href="' . hs_h(hs_lang_url($code)) . '"'
            . ' data-hs-lang-pick="' . hs_h($code) . '"'
            . ' aria-selected="' . ($code === $lang ? 'true' : 'false') . '">'
            . '<span class="hs-lang-float-flag">' . hs_h($item['flag'] ?? '') . '</span>'
            . '<span class="hs-lang-float-name">' . hs_h($item['name'] ?? $code) . '</span>'
            . '<span class="hs-lang-float-code">' . hs_h($item['label'] ?? strtoupper($code)) . '</span>'
            . '</a>';
    }
    $html .= '</div></div>'
        . '<button type="button" class="hs-lang-float-fab" data-hs-lang-float-toggle'
        . ' aria-expanded="' . ($picked ? 'false' : 'true') . '"'
        . ' aria-label="' . hs_h($openLabel) . '">'
        . '<span class="hs-lang-float-fab-flag" aria-hidden="true">' . hs_h($meta['flag'] ?? '🌐') . '</span>'
        . '<span class="hs-lang-float-fab-label">' . hs_h($meta['label'] ?? strtoupper($lang)) . '</span>'
        . '</button>'
        . '</div>';

    return $html;
}

$lang = hs_detect_lang();
$lang_meta = $HS_LANGS[$lang] ?? $HS_LANGS['en'];
$lang_file = __DIR__ . '/../lang/' . $lang . '.php';
if (!is_file($lang_file)) {
    $lang_file = __DIR__ . '/../lang/en.php';
}
$t = require $lang_file;
if ($lang !== 'en') {
    $en = require __DIR__ . '/../lang/en.php';
    $t = array_replace_recursive($en, $t);
}
$panelLang = __DIR__ . '/../lang/panel-' . $lang . '.php';
if (!is_file($panelLang)) {
    $panelLang = __DIR__ . '/../lang/panel-en.php';
}
if (is_file($panelLang)) {
    $t = array_replace_recursive($t, require $panelLang);
}
// Per-host public marketing overrides (e.g. lang/host-en.solaskinner.com.php)
if (function_exists('hs_is_production_host') && hs_is_production_host()) {
    $profile = function_exists('hs_resolve_host_profile') ? hs_resolve_host_profile() : [];
    $hostKey = (string) ($profile['host'] ?? '');
    if ($hostKey !== '') {
        $hostLang = __DIR__ . '/../lang/host-' . $lang . '.' . $hostKey . '.php';
        if (is_file($hostLang)) {
            $t = array_replace_recursive($t, require $hostLang);
        }
    }
}