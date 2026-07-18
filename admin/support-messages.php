<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-support.php';
require_once dirname(__DIR__) . '/includes/mail-templates.php';

hs_admin_require();

if (!hs_ecosystem_messages_ready()) {
    hs_support_json_response(['ok' => false, 'error' => 'module_missing'], 500);
}

$adminUser = (string) ($_SESSION[HS_ADMIN_USER_KEY] ?? 'admin');
$brand = (string) ($t['brand'] ?? 'Solaskinner');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = trim((string) ($_GET['id'] ?? ''));
    if ($id !== '') {
        if (!hs_admin_support_can_access($id)) {
            hs_support_json_response(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $row = ecosystem_owner_messages_by_id($id);
        if ($row === null) {
            hs_support_json_response(['ok' => false, 'error' => 'not_found'], 404);
        }
        ecosystem_owner_messages_mark_read($id);
        hs_support_json_response(['ok' => true, 'thread' => ecosystem_message_row_for_ui($row, $lang)]);
    }

    if (isset($_GET['clients'])) {
        hs_support_json_response([
            'ok' => true,
            'clients' => hs_admin_support_clients_for_picker($t),
        ]);
    }

    $threads = hs_admin_support_threads($lang);
    $unread = hs_admin_support_unread_count();
    hs_support_json_response(['ok' => true, 'threads' => $threads, 'unread' => $unread]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hs_support_json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
    hs_support_json_response(['ok' => false, 'error' => 'csrf'], 403);
}

if (isset($_POST['compose_new'])) {
    $userId = trim((string) ($_POST['user_id'] ?? ''));
    $toEmail = trim((string) ($_POST['to_email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $body = hs_support_sanitize_html((string) ($_POST['body'] ?? ''));
    $authorName = (string) ($t['admin_support_author_team'] ?? ($brand . ' support'));
    $res = hs_admin_support_compose_to_client($userId, $subject, $body, $adminUser, $authorName, $lang, $toEmail, $t);
    if (empty($res['ok'])) {
        hs_support_json_response(['ok' => false, 'error' => (string) ($res['error'] ?? 'fail')], 400);
    }
    $row = ecosystem_owner_messages_by_id((string) ($res['id'] ?? ''));
    hs_support_json_response([
        'ok' => true,
        'id' => (string) ($res['id'] ?? ''),
        'mail_sent' => !empty($res['mail_sent']),
        'to' => (string) ($res['to'] ?? $toEmail),
        'from' => function_exists('hs_mail_support_from_email') ? hs_mail_support_from_email() : '',
        'thread' => $row !== null ? ecosystem_message_row_for_ui($row, $lang) : null,
    ]);
}

if (isset($_POST['mark_read'])) {
    $messageId = trim((string) ($_POST['message_id'] ?? ''));
    if ($messageId === '' || !hs_admin_support_can_access($messageId)) {
        hs_support_json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
    ecosystem_owner_messages_mark_read($messageId);
    hs_support_json_response(['ok' => true]);
}

if (isset($_POST['archive'])) {
    $messageId = trim((string) ($_POST['message_id'] ?? ''));
    if ($messageId === '' || !hs_admin_support_can_access($messageId)) {
        hs_support_json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }
    ecosystem_owner_messages_archive($messageId);
    hs_support_json_response(['ok' => true]);
}

$messageId = trim((string) ($_POST['message_id'] ?? ''));
$body = hs_support_sanitize_html((string) ($_POST['body'] ?? ''));

if ($messageId === '' || !hs_admin_support_can_access($messageId)) {
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

$authorName = (string) ($t['admin_support_author_team'] ?? ($brand . ' support'));
$ok = ecosystem_message_add_post($messageId, 'owner', $body, [
    'author_name' => $authorName,
    'author_user' => $adminUser,
], $uploads !== [] ? $uploads : null);

if (!$ok) {
    hs_support_json_response(['ok' => false, 'error' => 'save_failed'], 500);
}

ecosystem_owner_messages_mark_read($messageId);
$row = ecosystem_owner_messages_by_id($messageId);
$toEmail = is_array($row) ? strtolower(trim((string) ($row['from_email'] ?? ''))) : '';
// Email is sent inside ecosystem_message_add_post → ecosystem_message_notify_client
// (uses hs_mail_send_support from support@ when available).
$mailSent = $toEmail !== '' && filter_var($toEmail, FILTER_VALIDATE_EMAIL);

hs_support_json_response([
    'ok' => true,
    'mail_sent' => $mailSent,
    'to' => $toEmail,
    'from' => function_exists('hs_mail_support_from_email') ? hs_mail_support_from_email() : '',
    'thread' => $row !== null ? ecosystem_message_row_for_ui($row, $lang) : null,
]);