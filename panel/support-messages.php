<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/client-auth.php';
require_once dirname(__DIR__) . '/includes/support.php';

require_once dirname(__DIR__) . '/includes/i18n.php';
$user = hs_client_require();
global $lang;

if (!hs_ecosystem_messages_ready()) {
    hs_support_json_response(['ok' => false, 'error' => 'module_missing'], 500);
}

$panelUrl = hs_support_panel_url();
$username = (string) ($user['username'] ?? '');
$email = strtolower(trim((string) ($user['email'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = trim((string) ($_GET['id'] ?? ''));
    if ($id !== '') {
        if (!ecosystem_message_client_can_access($id, $username, $email, $panelUrl)) {
            hs_support_json_response(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $row = ecosystem_owner_messages_by_id($id);
        if ($row === null) {
            hs_support_json_response(['ok' => false, 'error' => 'not_found'], 404);
        }
        ecosystem_message_client_mark_read($id, $username, $email, ecosystem_message_shop_host($panelUrl));
        hs_support_json_response(['ok' => true, 'thread' => ecosystem_message_row_for_ui($row, $lang)]);
    }

    $threads = ecosystem_message_threads_for_client($username, $email, $panelUrl);
    $unread = 0;
    foreach ($threads as $th) {
        if (!empty($th['client_unread'])) {
            $unread++;
        }
    }
    hs_support_json_response(['ok' => true, 'threads' => $threads, 'unread' => $unread]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_support_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$messageId = trim((string) ($_POST['message_id'] ?? ''));
$body = hs_support_sanitize_html((string) ($_POST['body'] ?? ''));

if ($messageId === '') {
    hs_support_json_response(['ok' => false, 'error' => 'message_id_required'], 400);
}
if (!ecosystem_message_client_can_access($messageId, $username, $email, $panelUrl)) {
    hs_support_json_response(['ok' => false, 'error' => 'forbidden'], 403);
}

$uploads = [];
if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'] ?? null)) {
    foreach ($_FILES['attachments']['name'] as $i => $name) {
        if (($_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $uploads[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($_FILES['attachments']['tmp_name'][$i] ?? ''),
            'error' => (int) ($_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($_FILES['attachments']['size'][$i] ?? 0),
        ];
    }
}
if (!hs_support_body_has_content($body) && $uploads === []) {
    hs_support_json_response(['ok' => false, 'error' => 'body_required'], 400);
}

$ok = ecosystem_message_add_post($messageId, 'client', $body, [
    'author_name' => hs_support_client_display_name($user),
    'author_user' => $username,
], $uploads !== [] ? $uploads : null);

if (!$ok) {
    hs_support_json_response(['ok' => false, 'error' => 'save_failed'], 500);
}

$row = ecosystem_owner_messages_by_id($messageId);
hs_support_json_response([
    'ok' => true,
    'thread' => $row !== null ? ecosystem_message_row_for_ui($row, $lang) : null,
]);