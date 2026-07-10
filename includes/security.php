<?php
declare(strict_types=1);

function hs_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function hs_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => hs_cookie_path(),
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
    $base = realpath($base);
    if ($base === false) {
        return null;
    }
    $target = $base . '/' . ltrim(str_replace(['\\', '..'], ['/', ''], $relative), '/');
    $real = realpath($target);
    if ($real === false && !is_dir(dirname($target))) {
        return null;
    }
    if ($real !== false && strpos($real, $base) !== 0) {
        return null;
    }
    return $target;
}