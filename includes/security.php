<?php
declare(strict_types=1);

function hs_is_sensitive_app_path(): bool
{
    $path = strtolower((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
    if ($path === '' || $path === '/') {
        return false;
    }
    $panelDir = '/' . trim(defined('HS_PANEL_DIR') ? HS_PANEL_DIR : 'panel', '/');
    if ($panelDir !== '/' && (str_starts_with($path, $panelDir . '/') || $path === $panelDir)) {
        return true;
    }
    foreach (['/admin', '/login.php', '/register.php', '/checkout.php', '/payment-return.php', '/data/', '/pma'] as $prefix) {
        if (str_starts_with($path, $prefix) || str_ends_with($path, rtrim($prefix, '/'))) {
            return true;
        }
    }

    return false;
}

/** Redirect HTTP → HTTPS on production (skip when proxy/CDN already serves HTTPS). */
function hs_enforce_https(): void
{
    if (!function_exists('hs_is_production_host') || !hs_is_production_host()) {
        return;
    }
    if (function_exists('hs_request_is_https') && hs_request_is_https()) {
        return;
    }
    // .htaccess handles HTTP→HTTPS on Hostinger; avoid duplicate redirects (ERR_TOO_MANY_REDIRECTS).
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return;
    }
    $host = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? (defined('HS_PRIMARY_DOMAIN') ? HS_PRIMARY_DOMAIN : 'localhost')));
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

/** Strict CSP for HTTP headers (not meta). Covers panel CDNs + public landing. */
function hs_security_csp_policy(): string
{
    return implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
        "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
        "img-src 'self' data: https: blob:",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        'upgrade-insecure-requests',
        // Note: do not enable require-trusted-types-for without an early default policy
        // in every layout — it breaks domain search (innerHTML) and other UI scripts.
    ]);
}

function hs_security_hsts_policy(): string
{
    return 'max-age=31536000; includeSubDomains; preload';
}

/** @return array<string, string> */
function hs_security_header_map(bool $includeHsts = true): array
{
    $map = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Resource-Policy' => 'same-origin',
        'Content-Security-Policy' => hs_security_csp_policy(),
    ];
    if ($includeHsts) {
        $map['Strict-Transport-Security'] = hs_security_hsts_policy();
    }
    return $map;
}

function hs_security_apply_headers(bool $noStore = false, bool $noIndex = false): void
{
    if (headers_sent()) {
        return;
    }
    $secure = function_exists('hs_request_is_https') && hs_request_is_https();
    if (!$secure && function_exists('hs_is_production_host') && hs_is_production_host()) {
        $secure = true;
    }
    foreach (hs_security_header_map($secure) as $name => $value) {
        header($name . ': ' . $value);
    }
    if ($noStore || hs_is_sensitive_app_path()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    if ($noIndex || hs_is_sensitive_app_path()) {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }
}

function hs_security_headers(): void
{
    hs_security_apply_headers(false, function_exists('hs_prelaunch_mode') && hs_prelaunch_mode());
}

function hs_panel_send_no_cache(): void
{
    hs_security_apply_headers(true, true);
}

/** Early Trusted Types policy — include in <head> before other scripts. */
function hs_trusted_types_bootstrap_script(): string
{
    return '<script>'
        . 'if(window.trustedTypes&&trustedTypes.createPolicy){'
        . 'trustedTypes.createPolicy("default",{createHTML:function(s){return s},'
        . 'createScriptURL:function(s){return s},createScript:function(s){return s}});}'
        . '</script>';
}

function hs_cookie_secure(): bool
{
    if (function_exists('hs_request_is_https') && hs_request_is_https()) {
        return true;
    }

    return function_exists('hs_is_production_host') && hs_is_production_host();
}

function hs_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => hs_cookie_path(),
        'secure' => hs_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (empty($_SESSION['hs_init'])) {
        session_regenerate_id(true);
        $_SESSION['hs_init'] = time();
    }
}

function hs_cookie_path(): string
{
    global $base_path;
    $p = rtrim((string) ($base_path ?? ''), '/') . '/';
    return $p !== '/' ? ($p ?: '/') : '/';
}

function hs_csrf_token(): string
{
    hs_session_start();
    if (empty($_SESSION['hs_csrf'])) {
        $_SESSION['hs_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['hs_csrf'];
}

function hs_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . hs_h(hs_csrf_token()) . '">';
}

function hs_csrf_verify(?string $token): bool
{
    hs_session_start();
    return is_string($token) && $token !== '' && !empty($_SESSION['hs_csrf'])
        && hash_equals($_SESSION['hs_csrf'], $token);
}

/** Verify CSRF from POST field, X-CSRF-Token header, or JSON payload. */
function hs_csrf_verify_request(?array $jsonPayload = null): bool
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (($token === null || $token === '') && is_array($jsonPayload)) {
        $token = $jsonPayload['csrf'] ?? null;
    }
    return hs_csrf_verify(is_string($token) ? $token : null);
}

/** @return list<string> */
function hs_upload_blocked_extensions(): array
{
    return [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',
        'cgi', 'pl', 'asp', 'aspx', 'jsp', 'htaccess', 'htpasswd', 'ini', 'user.ini',
    ];
}

function hs_upload_name_blocked(string $filename): bool
{
    $base = strtolower(basename($filename));
    if ($base === '' || $base === '.' || $base === '..') {
        return true;
    }
    if (str_starts_with($base, '.ht')) {
        return true;
    }
    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
    return $ext !== '' && in_array($ext, hs_upload_blocked_extensions(), true);
}

function hs_rate_limit(string $key, int $max = 10, int $window = 300): bool
{
    hs_session_start();
    $now = time();
    $bucket = $_SESSION['hs_rate'][$key] ?? ['t' => $now, 'n' => 0];
    if ($now - $bucket['t'] > $window) {
        $bucket = ['t' => $now, 'n' => 0];
    }
    $bucket['n']++;
    $_SESSION['hs_rate'][$key] = $bucket;
    return $bucket['n'] <= $max;
}

function hs_safe_path(string $base, string $relative): ?string
{
    $baseReal = realpath($base);
    if ($baseReal === false) {
        return null;
    }
    $relative = ltrim(str_replace(['\\', '..'], ['/', ''], $relative), '/');
    // Avoid "//foo" when base is filesystem root
    $target = $baseReal === '/' || $baseReal === '\\'
        ? '/' . $relative
        : rtrim($baseReal, '/\\') . ($relative === '' ? '' : '/' . $relative);
    if ($relative === '') {
        return $baseReal;
    }
    $real = realpath($target);
    if ($real === false && !is_dir(dirname($target))) {
        return null;
    }
    if ($real !== false) {
        $baseNorm = str_replace('\\', '/', $baseReal);
        $realNorm = str_replace('\\', '/', $real);
        if ($baseNorm === '/' || $baseNorm === '') {
            // whole filesystem jail
        } else {
            $prefix = rtrim($baseNorm, '/') . '/';
            if ($realNorm !== $baseNorm && !str_starts_with($realNorm, $prefix)) {
                return null;
            }
        }
    }
    return $target;
}

function hs_secret_token(string $constant): string
{
    return defined($constant) ? (string) constant($constant) : '';
}

/** Gate admin/scripts one-shot HTTP endpoints (token from config.local.php). */
function hs_require_secret_token(string $constant, ?string $provided = null): void
{
    $expected = hs_secret_token($constant);
    if ($expected === '') {
        http_response_code(403);
        exit('Forbidden');
    }
    $token = $provided ?? (string) ($_GET['token'] ?? $_POST['token'] ?? '');
    if ($token === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        exit('Forbidden');
    }
}