<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/user-settings.php';
require_once dirname(__DIR__) . '/includes/support.php';

require_once dirname(__DIR__) . '/includes/i18n.php';
$user = hs_client_require();
global $lang;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_support_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$agent = trim((string) ($payload['agent'] ?? 'general'));
$draft = trim((string) ($payload['draft'] ?? ''));
$siteSlug = trim((string) ($payload['site_slug'] ?? ''));
$settings = hs_user_settings_get((string) ($user['id'] ?? ''));
$ai = hs_ai_normalize(is_array($settings['ai'] ?? null) ? $settings['ai'] : []);

$result = hs_support_ai_compose($ai, $agent, $draft, $lang, [
    'username' => (string) ($user['username'] ?? ''),
    'site' => $siteSlug,
    'admin_name' => hs_support_client_display_name($user),
]);

hs_support_json_response([
    'ok' => $result['ok'],
    'demo' => $result['demo'],
    'subject' => $result['subject'] ?? '',
    'body' => $result['body'] ?? '',
    'error' => $result['error'] ?? '',
], $result['ok'] ? 200 : 400);