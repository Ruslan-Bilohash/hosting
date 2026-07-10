<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/support.php';
require_once dirname(__DIR__) . '/includes/client-identity.php';

require_once dirname(__DIR__) . '/includes/i18n.php';
$user = hs_client_ensure_identity(hs_client_require());
global $lang;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_support_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$subject = trim((string) ($payload['subject'] ?? ''));
$body = hs_support_sanitize_html((string) ($payload['body'] ?? ''));
$category = trim((string) ($payload['category'] ?? 'support'));
$siteSlug = trim((string) ($payload['site_slug'] ?? ''));
$fromEmail = hs_client_support_email($user);

if ($subject === '' || !hs_support_body_has_content($body)) {
    hs_support_json_response(['ok' => false, 'error' => 'subject_body_required'], 400);
}

$res = hs_support_send_owner_message($user, $lang, $subject, $body, $category, $siteSlug, $fromEmail);
if (!$res['ok']) {
    hs_support_json_response(['ok' => false, 'error' => $res['error'] ?? 'save_failed'], 500);
}
hs_support_json_response(['ok' => true, 'id' => $res['id'] ?? '']);