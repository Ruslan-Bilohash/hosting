<?php
declare(strict_types=1);

if (!defined('HS_LANG_COOKIE')) {
    define('HS_LANG_COOKIE', 'hs_lang');
}

if (!function_exists('hs_cookie_path')) {
    require_once __DIR__ . '/security.php';
}

/** @return array<string, string> */
function hs_install_langs(): array
{
    return [
        'uk' => 'Українська',
        'en' => 'English',
        'no' => 'Norsk',
    ];
}

function hs_install_detect_lang(): string
{
    $codes = array_keys(hs_install_langs());
    if (!empty($_GET['lang']) && in_array($_GET['lang'], $codes, true)) {
        $chosen = (string) $_GET['lang'];
        if (defined('HS_LANG_COOKIE')) {
            setcookie(HS_LANG_COOKIE, $chosen, [
                'expires' => time() + 365 * 86400,
                'path' => function_exists('hs_cookie_path') ? hs_cookie_path() : '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
        }
        return $chosen;
    }
    if (!empty($_COOKIE[HS_LANG_COOKIE]) && in_array($_COOKIE[HS_LANG_COOKIE], $codes, true)) {
        return (string) $_COOKIE[HS_LANG_COOKIE];
    }
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    if (str_starts_with($accept, 'no') || str_contains($accept, 'nb')) {
        return 'no';
    }
    if (str_starts_with($accept, 'uk') || str_contains($accept, 'ua')) {
        return 'uk';
    }
    return 'en';
}

/** @return array<string, string> */
function hs_install_strings(string $lang): array
{
    $file = __DIR__ . '/../lang/install-' . $lang . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/../lang/install-en.php';
    }
    $strings = require $file;
    if ($lang !== 'en') {
        $en = require __DIR__ . '/../lang/install-en.php';
        $strings = array_replace($en, is_array($strings) ? $strings : []);
    }
    return is_array($strings) ? $strings : [];
}

function hs_install_lang_url(string $code, string $baseScript = 'install.php'): string
{
    global $base_path;
    $prefix = rtrim((string) ($base_path ?? ''), '/');
    $path = ($prefix !== '' ? $prefix : '') . '/' . ltrim($baseScript, '/');
    return $path . '?lang=' . rawurlencode($code);
}

function hs_install_t(array $t, string $key, array $replace = []): string
{
    $text = $t[$key] ?? $key;
    foreach ($replace as $k => $v) {
        $text = str_replace('{' . $k . '}', (string) $v, $text);
    }
    return $text;
}