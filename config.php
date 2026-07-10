<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/version.php';

/** Panel directory name (avoid Hostinger reserved paths on some zones) */
define('HS_PANEL_DIR', 'panel');

define('HS_SITE_NAME', 'Hosting CMS');
/** Canonical production URL (current Hostinger; same path on future VPS) */
define('HS_CANONICAL_URL', 'https://bilohash.com/hosting');
define('HS_DEMO_MODE', true);
define('HS_PUBLIC_HTML', 'public_html');
define('HS_DATA_DIR', __DIR__ . '/data');

/** Hostinger SSH */
define('HS_SSH_HOST', '45.84.204.61');
define('HS_SSH_PORT', 65002);
define('HS_SSH_USER', 'u762384583');

$hs_ssh_config = __DIR__ . '/data/ssh.config.local.php';
if (is_file($hs_ssh_config)) {
    require $hs_ssh_config;
}
if (!defined('HS_SSH_PASSWORD_SET')) {
    define('HS_SSH_PASSWORD_SET', false);
}
if (!defined('HS_SSH_PASSWORD')) {
    define('HS_SSH_PASSWORD', '');
}

function hs_ssh_command(): string
{
    return 'ssh -p ' . HS_SSH_PORT . ' ' . HS_SSH_USER . '@' . HS_SSH_HOST;
}

function hs_ssh_password_available(): bool
{
    return HS_SSH_PASSWORD_SET && HS_SSH_PASSWORD !== '';
}

/** Per-host deploy profile */
function hs_host_profiles(): array
{
    return [
        'ilove.lt' => [
            'base_path' => '',
            'primary_domain' => 'ilove.lt',
            'brand_domain' => 'ilove.lt',
            'email_mx' => 'mx1.hostinger.com, mx2.hostinger.com',
            'webmail_host' => 'webmail.ilove.lt',
        ],
        'bilohash.com' => [
            'production' => true,
            'base_path' => '/hosting',
            'primary_domain' => 'bilohash.com',
            'brand_domain' => 'bilohash.com',
            'email_mx' => 'mx1.hostinger.com, mx2.hostinger.com',
            'webmail_host' => 'webmail.bilohash.com',
            'panel_url' => 'https://bilohash.com/hosting/panel/',
        ],
    ];
}

function hs_resolve_host_profile(): array
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    $profiles = hs_host_profiles();
    foreach ($profiles as $key => $profile) {
        if ($host === $key || str_ends_with($host, '.' . $key)) {
            return $profile + ['host' => $key];
        }
    }
    $detected = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return [
        'host' => $host,
        'base_path' => $detected !== '' && $detected !== '/' ? $detected : '',
        'primary_domain' => $host,
        'brand_domain' => $host,
    ];
}

$hs_profile = hs_resolve_host_profile();
$base_path = (string) ($hs_profile['base_path'] ?? '');
define('HS_PRIMARY_DOMAIN', (string) ($hs_profile['primary_domain'] ?? 'bilohash.com'));
define('HS_BRAND_DOMAIN', (string) ($hs_profile['brand_domain'] ?? HS_PRIMARY_DOMAIN));

$protocol = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
) ? 'https' : 'http';

$host = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? HS_PRIMARY_DOMAIN))) ?? HS_PRIMARY_DOMAIN;
$site_url = rtrim($protocol . '://' . $host . ($base_path !== '' ? $base_path : ''), '/');
$assets_url = ($base_path !== '' ? rtrim($base_path, '/') : '') . '/assets';
$public_html_root = __DIR__ . '/' . HS_PUBLIC_HTML;

function hs_default_primary_domain(): string
{
    return HS_PRIMARY_DOMAIN;
}

function hs_canonical_url(string $path = '', array $qs = []): string
{
    $base = rtrim(HS_CANONICAL_URL, '/');
    $url = $base . '/' . ltrim($path, '/');
    if ($qs !== []) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($qs);
    }
    return $url;
}

function hs_is_production_host(): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $profile = hs_resolve_host_profile();
    $cache = !empty($profile['production']);
    return $cache;
}

function hs_host_profile_value(string $key, ?string $fallback = null): ?string
{
    static $profile = null;
    if ($profile === null) {
        $profile = hs_resolve_host_profile();
    }
    $val = $profile[$key] ?? null;
    if (is_string($val) && $val !== '') {
        return $val;
    }
    return $fallback;
}

function hs_email_mx_label(?string $domain = null): string
{
    $mx = hs_host_profile_value('email_mx');
    if ($mx !== null) {
        return $mx;
    }
    $domain = $domain ?? hs_default_primary_domain();
    return 'mx1.hostinger.com (→ ' . $domain . ')';
}

function hs_webmail_url(?string $domain = null): string
{
    $host = hs_host_profile_value('webmail_host');
    if ($host === null) {
        $domain = $domain ?? hs_default_primary_domain();
        $host = 'webmail.' . $domain;
    }
    return 'https://' . $host;
}

function hs_panel_path(string $script = ''): string
{
    return HS_PANEL_DIR . ($script !== '' ? '/' . ltrim($script, '/') : '/');
}

function hs_url(string $path = '', array $qs = []): string
{
    global $base_path;
    $prefix = rtrim($base_path, '/');
    if ($prefix === '') {
        $url = '/' . ltrim($path, '/');
    } else {
        $url = $prefix . '/' . ltrim($path, '/');
    }
    if ($qs !== []) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($qs);
    }
    return $url;
}

function hs_asset(string $file): string
{
    global $assets_url;
    $prefix = $assets_url !== '' ? $assets_url : '/assets';
    return $prefix . '/' . ltrim($file, '/') . '?v=' . rawurlencode(hs_version());
}

function hs_absolute_url(string $path = '', array $qs = []): string
{
    global $site_url;
    $url = rtrim($site_url, '/') . '/' . ltrim($path, '/');
    if ($qs !== []) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($qs);
    }
    return $url;
}

function hs_public_path(string $relative = ''): string
{
    global $public_html_root;
    return rtrim($public_html_root, '/\\') . ($relative !== '' ? '/' . ltrim(str_replace(['..', '\\'], ['', '/'], $relative), '/') : '');
}

function hs_public_url(string $username, string $slug, ?string $installBase = null): string
{
    global $site_url;
    $s = preg_replace('/[^a-z0-9_-]/i', '', $slug) ?: 'www';
    $base = trim(str_replace('\\', '/', (string) ($installBase ?? preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'site')), '/');
    if ($base === '' || str_contains($base, '..')) {
        $base = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'site';
    }
    return rtrim($site_url, '/') . '/' . HS_PUBLIC_HTML . '/' . $base . '/' . $s . '/';
}