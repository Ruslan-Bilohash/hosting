<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/support.php';

$user = hs_client_require();

if (!hs_ecosystem_messages_ready()) {
    http_response_code(500);
    exit('Messaging module missing');
}

$panelUrl = hs_support_panel_url();
$username = (string) ($user['username'] ?? '');
$email = strtolower(trim((string) ($user['email'] ?? '')));

$messageId = trim((string) ($_GET['message_id'] ?? ''));
$postId = trim((string) ($_GET['post_id'] ?? ''));
$attId = trim((string) ($_GET['att_id'] ?? ''));

if ($messageId === '' || $postId === '' || $attId === '') {
    http_response_code(400);
    exit('Bad request');
}
if (!ecosystem_message_client_can_access($messageId, $username, $email, $panelUrl)) {
    http_response_code(403);
    exit('Forbidden');
}

$res = ecosystem_message_attachment_resolve($messageId, $postId, $attId);
if (!$res['ok'] || empty($res['path'])) {
    http_response_code(404);
    exit('Not found');
}

$mime = (string) ($res['mime'] ?? 'application/octet-stream');
$name = (string) ($res['name'] ?? 'file');
$path = (string) $res['path'];

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;