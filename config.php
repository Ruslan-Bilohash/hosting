<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/direct-access-guard.php';
hs_block_direct_web_hit(__FILE__);

require_once __DIR__ . '/includes/version.php';

/**
 * Local overrides (production secrets, demo off) — before defaults below.
 * Load via sanitized runtime copy so a UTF-8 BOM or misplaced declare(strict_types)
 * in config.local.php cannot take the whole site down with HTTP 500.
 */
$hs_local_config = __DIR__ . '/config.local.php';
if (is_file($hs_local_config)) {
    $hsLocalRaw = @file_get_contents($hs_local_config);
    if (is_string($hsLocalRaw) && $hsLocalRaw !== '') {
        // Strip UTF-8 BOM
        if (str_starts_with($hsLocalRaw, "\xEF\xBB\xBF")) {
            $hsLocalRaw = substr($hsLocalRaw, 3);
        }
        // strict_types only valid as first statement — strip from overrides file
        $hsLocalRaw = preg_replace('/\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', '', $hsLocalRaw) ?? $hsLocalRaw;
        $hsLocalRuntime = __DIR__ . '/data/config.local.runtime.php';
        $hsLocalDir = dirname($hsLocalRuntime);
        if (!is_dir($hsLocalDir)) {
            @mkdir($hsLocalDir, 0750, true);
        }
        if (@file_put_contents($hsLocalRuntime, $hsLocalRaw, LOCK_EX) !== false) {
            require $hsLocalRuntime;
        } else {
            $hsLocalRuntime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                . 'hs_config_local_' . md5(__DIR__) . '.php';
            if (@file_put_contents($hsLocalRuntime, $hsLocalRaw, LOCK_EX) !== false) {
                require $hsLocalRuntime;
            }
        }
    }
}

/** Panel directory name (avoid Hostinger reserved paths on some zones) */
define('HS_PANEL_DIR', 'panel');

if (!defined('HS_SITE_NAME')) {
    define('HS_SITE_NAME', 'SolaSkinner Hosting');
}
// Default for local/dev; production profiles force false after host profile resolves.
/** true = block search engines until public launch (meta robots, robots.txt, X-Robots-Tag). */
if (!defined('HS_PRELAUNCH')) {
    define('HS_PRELAUNCH', false);
}
define('HS_PUBLIC_HTML', 'public_html');
define('HS_DATA_DIR', __DIR__ . '/data');

/** SSH defaults (override in config.local.php on production) */
if (!defined('HS_SSH_HOST')) {
    define('HS_SSH_HOST', 'localhost');
}
if (!defined('HS_SSH_PORT')) {
    define('HS_SSH_PORT', 22);
}
if (!defined('HS_SSH_USER')) {
    define('HS_SSH_USER', '');
}
if (!defined('HS_SERVER_IP')) {
    define('HS_SERVER_IP', '127.0.0.1');
}
if (!defined('HS_FTP_USER_PREFIX')) {
    define('HS_FTP_USER_PREFIX', '');
}

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

function hs_ssh_display_host(): string
{
    if (hs_host_profile_flag('white_label')) {
        return hs_server_ip();
    }
    return HS_SSH_HOST;
}

function hs_ssh_command(): string
{
    return 'ssh -p ' . HS_SSH_PORT . ' ' . HS_SSH_USER . '@' . hs_ssh_display_host();
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
            'canonical_url' => 'https://bilohash.com/hosting',
            'server_ip' => '45.84.204.61',
            'vps_ip' => '187.127.91.82',
            'server_hostname' => 'bilohash.com',
            'platform_free_zone' => 'site.bilohash.com',
            'host_platform' => true,
            'host_platform_url' => 'https://host.bilohash.com',
            'manual_domain_dns' => true,
            'white_label' => true,
            'ftp_username_mode' => 'hostinger',
            'nameservers' => 'ns1.dns-parking.com,ns2.dns-parking.com',
            'display_nameservers' => 'ns1.dns-parking.com,ns2.dns-parking.com',
            'email_mx' => 'mx1.hostinger.com, mx2.hostinger.com',
            'webmail_host' => 'webmail.bilohash.com',
            'panel_url' => 'https://bilohash.com/hosting/panel/',
            'support_inbox_email' => 'support@bilohash.com',
        ],
        'eko-host.com' => [
            'production' => true,
            'base_path' => '',
            'primary_domain' => 'eko-host.com',
            'brand_domain' => 'eko-host.com',
            'canonical_url' => 'https://eko-host.com',
            'server_ip' => '45.84.204.61',
            'nameservers' => 'ns1.dns-parking.com,ns2.dns-parking.com',
            'email_mx' => 'mx1.hostinger.com, mx2.hostinger.com',
            'webmail_host' => 'webmail.eko-host.com',
            'panel_url' => 'https://eko-host.com/panel/',
        ],
        'solaskinner.com' => [
            'production' => true,
            'base_path' => '',
            'php_versions' => '8.4,8.3,8.2',
            'php_recommended' => '8.3',
            'site_name' => 'Solaskinner',
            'primary_domain' => 'solaskinner.com',
            'brand_domain' => 'solaskinner.com',
            'canonical_url' => 'https://solaskinner.com',
            'server_ip' => '67.223.118.123',
            'server_hostname' => 'cloud-eu.solaskinner.com',
            // Geographic label for plan → technical tab
            'server_location' => 'Norway and Europe',
            // Registry / Namecheap hosting NS — real hosts that resolve (required).
            // Do NOT use fictional ns1/ns2.solaskinner.com until those hostnames exist as private NS.
            'nameservers' => 'dns1.namecheaphosting.com,dns2.namecheaphosting.com',
            'display_nameservers' => 'dns1.namecheaphosting.com,dns2.namecheaphosting.com',
            'email_mx' => 'mx1.solaskinner.com, mx2.solaskinner.com',
            // Client mail UI lives in /panel/webmail.php — not cPanel :2096 (server login only).
            'webmail_panel_only' => true,
            // Shared Roundcube for all client domains (login: info@clientdomain.com + mailbox password).
            // Roundcube lives on cPanel :2096; branded webmail.solaskinner.com needs its own SAN cert.
            'webmail_roundcube_host' => 'server326.web-hosting.com',
            'webmail_roundcube_port' => 2096,
            'webmail_roundcube_brand_host' => 'webmail.solaskinner.com',
            'webmail_legacy_host' => 'webmail.solaskinner.com',
            'mail_incoming_host' => 'mail.solaskinner.com',
            'mail_outgoing_host' => 'mail.solaskinner.com',
            'panel_url' => 'https://solaskinner.com/panel/',
            'support_inbox_email' => 'support@solaskinner.com',
            'white_label' => true,
            'ftp_username_mode' => 'cpanel',
        ],
    ];
}

function hs_resolve_host_profile(): array
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    $profiles = hs_host_profiles();
    if (PHP_SAPI === 'cli' && ($host === '' || $host === 'localhost')) {
        $sshUser = defined('HS_SSH_USER') ? (string) HS_SSH_USER : '';
        if ($sshUser === 'u762384583' && isset($profiles['bilohash.com'])) {
            return $profiles['bilohash.com'] + ['host' => 'bilohash.com'];
        }
        foreach ($profiles as $key => $profile) {
            if (!empty($profile['production']) && count(array_filter($profiles, static fn(array $p): bool => !empty($p['production']))) === 1) {
                return $profile + ['host' => (string) $key];
            }
        }
    }
    if (isset($profiles[$host])) {
        return $profiles[$host] + ['host' => $host];
    }
    foreach ($profiles as $key => $profile) {
        if (str_ends_with($host, '.' . $key)) {
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
// Live production hosting is never a public “demo site”.
if (!defined('HS_DEMO_MODE')) {
    define('HS_DEMO_MODE', empty($hs_profile['production']));
}

function hs_request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    if (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] !== 'off') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_HTTPS'] ?? '')) === '1'
        || strtolower((string) ($_SERVER['HTTP_X_HTTPS'] ?? '')) === 'on') {
        return true;
    }
    if (str_contains((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''), '"scheme":"https"')) {
        return true;
    }
    return false;
}

$protocol = hs_request_is_https() ? 'https' : 'http';
if (!empty($hs_profile['production'])) {
    $protocol = 'https';
}

$host = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? HS_PRIMARY_DOMAIN))) ?? HS_PRIMARY_DOMAIN;
$site_url = rtrim($protocol . '://' . $host . ($base_path !== '' ? $base_path : ''), '/');
$assets_url = ($base_path !== '' ? rtrim($base_path, '/') : '') . '/assets';
$public_html_root = __DIR__ . '/' . HS_PUBLIC_HTML;

if (!defined('HS_CANONICAL_URL')) {
    $canonical = (string) ($hs_profile['canonical_url'] ?? $site_url);
    define('HS_CANONICAL_URL', rtrim($canonical !== '' ? $canonical : $site_url, '/'));
}

function hs_default_primary_domain(): string
{
    return HS_PRIMARY_DOMAIN;
}

function hs_prelaunch_mode(): bool
{
    static $loaded = false;
    if (!$loaded) {
        $platformSettings = __DIR__ . '/includes/platform-settings.php';
        if (is_file($platformSettings)) {
            require_once $platformSettings;
        }
        $loaded = true;
    }
    if (function_exists('hs_platform_prelaunch_enabled')) {
        return hs_platform_prelaunch_enabled();
    }

    return defined('HS_PRELAUNCH') && HS_PRELAUNCH;
}

function hs_sync_public_robots_txt(): void
{
    $path = __DIR__ . '/robots.txt';
    @file_put_contents($path, hs_robots_txt_body(), LOCK_EX);
}

function hs_render_prelaunch_banner(array $t): string
{
    if (!hs_prelaunch_mode()) {
        return '';
    }
    $text = trim((string) ($t['prelaunch_banner'] ?? ''));
    if ($text === '') {
        $text = 'Site under development.';
    }

    return '<div class="hs-dev-banner" role="status" aria-live="polite">'
        . '<i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>'
        . '<span>' . hs_h($text) . '</span></div>';
}

function hs_robots_meta(): string
{
    return hs_prelaunch_mode() ? 'noindex,nofollow,noarchive' : 'index,follow';
}

function hs_robots_txt_body(): string
{
    if (hs_prelaunch_mode()) {
        return "User-agent: *\nDisallow: /\n";
    }

    $base = rtrim(HS_CANONICAL_URL, '/');

    return "User-agent: *\nAllow: /\nDisallow: /panel/\nDisallow: /admin/\nDisallow: /data/\nDisallow: /public_html/\nDisallow: /try-builder.php\n"
        . "\n# AI / answer engines — public marketing pages allowed\n"
        . "User-agent: GPTBot\nAllow: /\nDisallow: /panel/\nDisallow: /admin/\nDisallow: /data/\n"
        . "User-agent: ChatGPT-User\nAllow: /\n"
        . "User-agent: Google-Extended\nAllow: /\n"
        . "User-agent: anthropic-ai\nAllow: /\n"
        . "User-agent: ClaudeBot\nAllow: /\n"
        . "User-agent: PerplexityBot\nAllow: /\n"
        . "User-agent: Bytespider\nAllow: /\n"
        . "\nSitemap: {$base}/sitemap.php\n";
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

function hs_host_profile_flag(string $key): bool
{
    static $profile = null;
    if ($profile === null) {
        $profile = hs_resolve_host_profile();
    }
    return !empty($profile[$key]);
}

/** Public server IP for DNS A-records (host profile overrides default 127.0.0.1 on production). */
function hs_server_ip(): string
{
    if (defined('HS_SERVER_IP') && HS_SERVER_IP !== '' && HS_SERVER_IP !== '127.0.0.1') {
        return HS_SERVER_IP;
    }
    $profileIp = hs_host_profile_value('server_ip');
    if ($profileIp !== null && $profileIp !== '') {
        return $profileIp;
    }
    return defined('HS_SERVER_IP') ? HS_SERVER_IP : '127.0.0.1';
}

/** VPS IP for client sites on Host Platform (separate from shared hosting IP). */
function hs_vps_server_ip(): string
{
    $vps = hs_host_profile_value('vps_ip');
    if ($vps !== null && $vps !== '') {
        return $vps;
    }
    return hs_server_ip();
}

/** Registry NS used for domain API (Namecheap etc.) — never shown in client panel when white-label is on. */
function hs_registry_nameservers(): array
{
    if (defined('NC_NAMESERVERS') && NC_NAMESERVERS !== '') {
        return array_values(array_filter(array_map('trim', explode(',', NC_NAMESERVERS))));
    }
    $raw = hs_host_profile_value('nameservers');
    if ($raw !== null && $raw !== '') {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
    return ['ns1.example.com', 'ns2.example.com'];
}

/** Branded NS shown in client panel (white-label). Falls back to registry NS. */
function hs_display_nameservers(): array
{
    $raw = hs_host_profile_value('display_nameservers');
    if ($raw !== null && $raw !== '') {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
    return hs_registry_nameservers();
}

/** @return list<string> */
function hs_server_nameservers(): array
{
    return hs_display_nameservers();
}

function hs_server_hostname(): string
{
    $host = hs_host_profile_value('server_hostname');
    if ($host !== null && $host !== '') {
        return $host;
    }
    return 'server1';
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

function hs_webmail_panel_url(): string
{
    return hs_absolute_url(hs_panel_path('webmail.php'));
}

/** Hosting support inbox — where panel “write to support” messages are delivered. */
function hs_host_support_inbox_email(?array $t = null): string
{
    $custom = hs_host_profile_value('support_inbox_email');
    if (is_string($custom) && trim($custom) !== '' && filter_var(trim($custom), FILTER_VALIDATE_EMAIL)) {
        return strtolower(trim($custom));
    }
    if ($t !== null) {
        $fromLang = trim((string) ($t['footer_email'] ?? ''));
        if ($fromLang !== '' && filter_var($fromLang, FILTER_VALIDATE_EMAIL)) {
            return strtolower($fromLang);
        }
    }

    return 'support@' . hs_default_primary_domain();
}

/** Shared Roundcube host for client mailboxes on this server (e.g. webmail.solaskinner.com). */
function hs_webmail_roundcube_shared_host(): ?string
{
    $host = hs_host_profile_value('webmail_roundcube_host');
    if (is_string($host) && trim($host) !== '') {
        return strtolower(trim($host));
    }
    if (hs_host_profile_flag('webmail_panel_only')) {
        $legacy = hs_host_profile_value('webmail_legacy_host');
        if (is_string($legacy) && trim($legacy) !== '') {
            return strtolower(trim($legacy));
        }
    }

    return null;
}

/** Roundcube/IMAP webmail for a client-owned domain (not platform domain). */
function hs_webmail_roundcube_url(?string $clientDomain): ?string
{
    $clientDomain = strtolower(trim((string) $clientDomain));
    $platform = strtolower(hs_default_primary_domain());
    if ($clientDomain === '' || $clientDomain === $platform) {
        return null;
    }

    $shared = hs_webmail_roundcube_shared_host();
    if ($shared !== null) {
        $port = (int) (hs_host_profile_value('webmail_roundcube_port') ?? 0);
        $suffix = $port > 0 && $port !== 443 ? ':' . $port : '';

        return 'https://' . $shared . $suffix;
    }

    $prefix = (string) (hs_host_profile_value('webmail_subdomain_prefix') ?? 'webmail');
    if ($prefix !== '') {
        return 'https://' . $prefix . '.' . $clientDomain;
    }

    return 'https://' . $clientDomain . ':2096';
}

function hs_webmail_url(?string $domain = null): string
{
    if (hs_host_profile_flag('webmail_panel_only') || hs_host_profile_flag('white_label')) {
        return hs_webmail_panel_url();
    }
    $host = hs_host_profile_value('webmail_host');
    if ($host === null || $host === '') {
        $domain = $domain ?? hs_default_primary_domain();
        $host = 'webmail.' . $domain;
    }

    return 'https://' . $host;
}

function hs_webmail_legacy_url(): ?string
{
    $host = hs_host_profile_value('webmail_legacy_host');
    if (!is_string($host) || trim($host) === '') {
        return null;
    }
    return 'https://' . trim($host);
}

function hs_panel_path(string $script = ''): string
{
    $script = ltrim($script, '/');
    if ($script !== '' && str_starts_with($script, HS_PANEL_DIR . '/')) {
        return $script;
    }

    return HS_PANEL_DIR . ($script !== '' ? '/' . $script : '/');
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
    if (!is_string($public_html_root) || $public_html_root === '') {
        $public_html_root = __DIR__ . '/' . HS_PUBLIC_HTML;
    }
    return rtrim($public_html_root, '/\\') . ($relative !== '' ? '/' . ltrim(str_replace(['..', '\\'], ['', '/'], $relative), '/') : '');
}

/** Whether public site URLs include the public_html/ segment (nested CMS installs). */
function hs_public_url_uses_nested_folder(): bool
{
    global $base_path;

    return rtrim((string) $base_path, '/') !== '';
}

/** URL path prefix before account folder (empty on cPanel, public_html/ on nested hosting). */
function hs_public_url_segment_prefix(): string
{
    return hs_public_url_uses_nested_folder() ? HS_PUBLIC_HTML . '/' : '';
}

/** Absolute URL to an account folder or file under the panel host. */
function hs_account_public_url(string $username, string $file = ''): string
{
    global $site_url;
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';
    $url = rtrim((string) $site_url, '/') . '/' . hs_public_url_segment_prefix() . $safe;
    if ($file !== '') {
        $url .= '/' . ltrim(str_replace(['..', '\\'], ['', '/'], $file), '/');
    }

    return $url;
}

function hs_cms_root_htaccess(): string
{
    return __DIR__ . '/.htaccess';
}

/** Map a panel-hosted site URL to a path relative to the on-disk public_html/ tree. */
function hs_public_url_to_rel(string $url): ?string
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    if ($path === '') {
        return null;
    }
    global $site_url;
    $panelPath = rtrim((string) parse_url((string) $site_url, PHP_URL_PATH), '/');
    if ($panelPath !== '' && str_starts_with($path, $panelPath . '/')) {
        $path = substr($path, strlen($panelPath));
    }
    $path = ltrim($path, '/');
    if ($path === '' || str_contains($path, '..')) {
        return null;
    }
    if (hs_public_url_uses_nested_folder()) {
        if (!str_starts_with($path, HS_PUBLIC_HTML . '/')) {
            return null;
        }

        return substr($path, strlen(HS_PUBLIC_HTML) + 1);
    }
    $skip = '(admin|panel|pma|assets|scripts|login|register|checkout|logout|install\.php|migrate-to-mysql\.php|config\.php|data|lang|includes)';
    if (preg_match('/^' . $skip . '(\/|$)/', $path) === 1) {
        return null;
    }

    return $path;
}

function hs_public_url(string $username, string $slug, ?string $installBase = null): string
{
    global $site_url;
    $s = preg_replace('/[^a-z0-9_-]/i', '', $slug) ?: 'www';
    $base = trim(str_replace('\\', '/', (string) ($installBase ?? preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'site')), '/');
    if ($base === '' || str_contains($base, '..')) {
        $base = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'site';
    }
    $prefix = hs_public_url_segment_prefix();

    return rtrim($site_url, '/') . '/' . ($prefix !== '' ? $prefix : '') . $base . '/' . $s . '/';
}