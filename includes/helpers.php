<?php
declare(strict_types=1);

function hs_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hs_redirect(string $path, int $code = 302): never
{
    $target = (function_exists('hs_is_production_host') && hs_is_production_host())
        ? hs_absolute_url($path)
        : hs_url($path);
    header('Location: ' . $target, true, $code);
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

/**
 * Normalize a hostname: strip scheme, path, port, leading www.
 */
function hs_normalize_public_host(string $domain): string
{
    $domain = strtolower(trim((string) preg_replace('#^https?://#i', '', $domain)));
    $domain = (string) preg_replace('#/.*$#', '', $domain);
    $domain = (string) preg_replace('#:\d+$#', '', $domain);
    $domain = (string) preg_replace('#^www\.#', '', $domain);

    return trim($domain, ". \t\n\r\0\x0B");
}

/**
 * Panel / brand hostnames — never treated as a client's public site.
 *
 * @return list<string>
 */
function hs_platform_hosts(): array
{
    global $site_url;
    $hosts = [];
    $candidates = [
        strtolower((string) parse_url((string) ($site_url ?? ''), PHP_URL_HOST)),
        function_exists('hs_default_primary_domain') ? strtolower(hs_default_primary_domain()) : '',
        defined('HS_PRIMARY_DOMAIN') ? strtolower((string) HS_PRIMARY_DOMAIN) : '',
        defined('HS_BRAND_DOMAIN') ? strtolower((string) HS_BRAND_DOMAIN) : '',
        'localhost',
    ];
    if (function_exists('hs_host_profile_value')) {
        foreach (['host_platform_url', 'canonical_url'] as $key) {
            $url = (string) (hs_host_profile_value($key) ?? '');
            if ($url !== '') {
                $h = strtolower((string) parse_url($url, PHP_URL_HOST));
                if ($h !== '') {
                    $candidates[] = $h;
                }
            }
        }
        $server = (string) (hs_host_profile_value('server_hostname') ?? '');
        if ($server !== '') {
            $candidates[] = strtolower($server);
        }
    }
    foreach ($candidates as $host) {
        $host = hs_normalize_public_host((string) $host);
        if ($host === '') {
            continue;
        }
        $hosts[] = $host;
        $hosts[] = 'www.' . $host;
    }

    return array_values(array_unique($hosts));
}

/**
 * True when $host is the hosting brand/panel hostname (e.g. solaskinner.com),
 * not a client site. Client free subdomains like user.site.brand.com return false.
 */
function hs_is_platform_host(string $host): bool
{
    $host = hs_normalize_public_host($host);
    if ($host === '') {
        return true;
    }
    $list = hs_platform_hosts();

    return in_array($host, $list, true) || in_array('www.' . $host, $list, true);
}