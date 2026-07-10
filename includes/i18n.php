<?php
declare(strict_types=1);

define('HS_LANG_COOKIE', 'hs_lang');

$HS_LANGS = [
    'en' => ['label' => 'EN', 'name' => 'English', 'flag' => '🇬🇧', 'html' => 'en'],
    'no' => ['label' => 'NO', 'name' => 'Norsk', 'flag' => '🇳🇴', 'html' => 'no'],
    'uk' => ['label' => 'UA', 'name' => 'Українська', 'flag' => '🇺🇦', 'html' => 'uk'],
];

function hs_langs(): array
{
    global $HS_LANGS;
    return $HS_LANGS;
}

function hs_detect_lang(): string
{
    global $base_path, $HS_LANGS;
    $codes = array_keys($HS_LANGS);

    if (!empty($_GET['lang']) && in_array($_GET['lang'], $codes, true)) {
        $chosen = $_GET['lang'];
        setcookie(HS_LANG_COOKIE, $chosen, [
            'expires' => time() + 365 * 86400,
            'path' => hs_cookie_path(),
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        return $chosen;
    }
    if (!empty($_COOKIE[HS_LANG_COOKIE]) && in_array($_COOKIE[HS_LANG_COOKIE], $codes, true)) {
        return $_COOKIE[HS_LANG_COOKIE];
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
        $html .= '<a href="' . hs_h(hs_lang_url($code)) . '" class="' . ($code === $lang ? 'active' : '') . '" role="option">'
            . '<span>' . hs_h($item['flag'] ?? '') . '</span> ' . hs_h($item['name'] ?? $code)
            . '</a>';
    }
    return $html . '</div></div>';
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