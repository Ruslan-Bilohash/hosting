<?php
declare(strict_types=1);

function hs_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hs_redirect(string $path, int $code = 302): never
{
    header('Location: ' . hs_url($path), true, $code);
    exit;
}

function hs_flash_set(string $key, string $message, string $type = 'info'): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['hs_flash'][$key] = ['msg' => $message, 'type' => $type];
}

function hs_flash_get(string $key): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['hs_flash'][$key])) {
        return null;
    }
    $v = $_SESSION['hs_flash'][$key];
    unset($_SESSION['hs_flash'][$key]);
    return $v;
}

function hs_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'site';
}

function hs_format_date(string $iso): string
{
    $ts = strtotime($iso);
    return $ts ? date('Y-m-d H:i', $ts) : $iso;
}

function hs_icon(string $name): string
{
    static $map = [
        'dashboard' => 'fa-gauge-high',
        'websites' => 'fa-globe',
        'installer' => 'fa-box-open',
        'files' => 'fa-folder-open',
        'account' => 'fa-user-gear',
        'logout' => 'fa-right-from-bracket',
        'plus' => 'fa-plus',
        'check' => 'fa-circle-check',
        'menu' => 'fa-bars',
        'close' => 'fa-xmark',
        'shield' => 'fa-shield-halved',
        'server' => 'fa-server',
        'users' => 'fa-users',
    ];
    return $map[$name] ?? 'fa-circle';
}