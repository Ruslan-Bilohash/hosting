<?php
declare(strict_types=1);

/**
 * WHM API bridge — deploy ONLY on Nebula (solaops public_html).
 * Stellar cannot open :2087; this calls WHM on 127.0.0.1:2087.
 *
 * SECURITY:
 *  - POST only
 *  - Shared secret (header X-Sola-Bridge-Secret)
 *  - IP allowlist (Stellar outbound)
 *  - WHM function allowlist (no open proxy)
 *  - Simple rate limit
 *  - Do not put this file on Stellar with a live secret
 */

// === CONFIG (edit on Nebula only; must match pool Bridge secret) ===
const BRIDGE_SECRET = 'CHANGE_ME_TO_LONG_RANDOM_SECRET';
/** Stellar outbound only — do not trust X-Forwarded-For for auth */
const BRIDGE_ALLOW_IPS = [
    '67.223.118.121',
    '67.223.118.123',
];
const WHM_HOST = '127.0.0.1';
const WHM_PORT = 2087;
const WHM_SSL = true;
/** Max POSTs per IP per window */
const BRIDGE_RATE_MAX = 60;
const BRIDGE_RATE_WINDOW = 60;

/** Only these WHM json-api functions may be proxied */
const BRIDGE_ALLOWED_FUNCTIONS = [
    'version',
    'listaccts',
    'listpkgs',
    'addpkg',
    'editpkg',
    'createacct',
    'create_user_session',
    'passwd',
    'suspendacct',
    'unsuspendacct',
    'removeacct',
    'modifyacct',
    'editquota',
    'limitbw',
    'accountsummary',
];

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
// Prefer REMOTE_ADDR only (XFF is spoofable)
if (BRIDGE_ALLOW_IPS !== [] && !in_array($remote, BRIDGE_ALLOW_IPS, true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'ip_not_allowed']);
    exit;
}

$secret = (string) ($_SERVER['HTTP_X_SOLA_BRIDGE_SECRET'] ?? '');
// Do not accept secret from POST body (logs / referrer risk)
if (
    BRIDGE_SECRET === ''
    || BRIDGE_SECRET === 'CHANGE_ME_TO_LONG_RANDOM_SECRET'
    || !hash_equals(BRIDGE_SECRET, $secret)
) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'bad_secret']);
    exit;
}

// Rate limit (tmp file under system temp — works without write to public_html)
$rateKey = 'sola_bridge_' . preg_replace('/[^a-f0-9.:]/i', '', $remote);
$rateFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . $rateKey . '.rl';
$now = time();
$bucket = ['t' => $now, 'n' => 0];
if (is_file($rateFile)) {
    $rawRl = (string) @file_get_contents($rateFile);
    $decoded = $rawRl !== '' ? json_decode($rawRl, true) : null;
    if (is_array($decoded) && isset($decoded['t'], $decoded['n'])) {
        $bucket = ['t' => (int) $decoded['t'], 'n' => (int) $decoded['n']];
    }
}
if ($now - $bucket['t'] > BRIDGE_RATE_WINDOW) {
    $bucket = ['t' => $now, 'n' => 0];
}
$bucket['n']++;
@file_put_contents($rateFile, json_encode($bucket), LOCK_EX);
if ($bucket['n'] > BRIDGE_RATE_MAX) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'rate_limited']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'json_body_required']);
    exit;
}

$function = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($payload['function'] ?? '')) ?? '');
$apiUser = trim((string) ($payload['api_user'] ?? ''));
$apiToken = trim((string) ($payload['api_token'] ?? ''));
$params = $payload['params'] ?? [];
if (!is_array($params)) {
    $params = [];
}

if ($function === '' || $apiUser === '' || $apiToken === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'function_user_token_required']);
    exit;
}

if (!in_array($function, BRIDGE_ALLOWED_FUNCTIONS, true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'function_not_allowed']);
    exit;
}

// Strip nested api.* keys that could confuse WHM
unset($params['api.version'], $params['api_user'], $params['api_token']);

$qs = http_build_query(array_merge(['api.version' => 1], $params));
$scheme = WHM_SSL ? 'https' : 'http';
$url = $scheme . '://' . WHM_HOST . ':' . WHM_PORT . '/json-api/' . rawurlencode($function) . '?' . $qs;

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'curl_missing_on_bridge']);
    exit;
}

$ch = curl_init($url);
if ($ch === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'curl_init']);
    exit;
}
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 55,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_HTTPHEADER => [
        'Authorization: whm ' . $apiUser . ':' . $apiToken,
    ],
]);
$body = curl_exec($ch);
$errno = curl_errno($ch);
$err = curl_error($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0 || !is_string($body)) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => $err !== '' ? $err : 'curl_' . $errno,
        'bridge' => true,
    ]);
    exit;
}

http_response_code($code > 0 ? $code : 200);
echo $body;
