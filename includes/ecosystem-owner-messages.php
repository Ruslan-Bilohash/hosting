<?php
declare(strict_types=1);

require_once __DIR__ . '/portfolio-database.php';
require_once __DIR__ . '/bh-mail.php';

const ECOSYSTEM_MESSAGE_CATEGORIES = ['support', 'bug', 'billing', 'feature', 'other'];
const ECOSYSTEM_MESSAGE_STATUSES = ['new', 'read', 'archived'];

function ecosystem_owner_messages_path(): string
{
    return dirname(__DIR__) . '/data/owner-messages.json';
}

/** @return list<array<string,mixed>> */
function ecosystem_owner_messages_load(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $rows = [];
    if (pf_mysql_installed()) {
        pf_db_ensure_table('ecosystem_owner_messages');
        $rows = pf_db_load_rows('ecosystem_owner_messages');
    } else {
        $path = ecosystem_owner_messages_path();
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $rows = $decoded;
            }
        }
    }

    usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['ts'] ?? ''), (string) ($a['ts'] ?? '')));
    $cache = $rows;

    return $rows;
}

/** @param list<array<string,mixed>> $rows */
function ecosystem_owner_messages_save_all(array $rows): bool
{
    $dir = dirname(ecosystem_owner_messages_path());
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $json = json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    $ok = file_put_contents(ecosystem_owner_messages_path(), $json . "\n", LOCK_EX) !== false;

    if (pf_mysql_installed()) {
        pf_db_ensure_table('ecosystem_owner_messages');
        pf_db_replace_rows('ecosystem_owner_messages', array_values($rows), 'id');
    }

    return $ok;
}

function ecosystem_owner_messages_new_id(): string
{
    return 'msg_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(3));
}

function ecosystem_owner_messages_count_unread(): int
{
    $n = 0;
    foreach (ecosystem_owner_messages_load() as $row) {
        if ((string) ($row['status'] ?? 'new') === 'new') {
            $n++;
        }
    }
    return $n;
}

function ecosystem_owner_messages_by_id(string $id): ?array
{
    $id = trim($id);
    foreach (ecosystem_owner_messages_load() as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return $row;
        }
    }
    return null;
}

/** @param array<string,mixed> $fields */
function ecosystem_owner_messages_add(array $fields): ?string
{
    $subject = trim((string) ($fields['subject'] ?? ''));
    $body = trim((string) ($fields['body'] ?? ''));
    if ($subject === '' || $body === '') {
        return null;
    }

    $category = (string) ($fields['category'] ?? 'support');
    if (!in_array($category, ECOSYSTEM_MESSAGE_CATEGORIES, true)) {
        $category = 'support';
    }

    $postId = 'post_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(3));
    $row = [
        'id'            => ecosystem_owner_messages_new_id(),
        'ts'            => gmdate('c'),
        'last_activity' => gmdate('c'),
        'from_user'     => trim((string) ($fields['from_user'] ?? '')),
        'from_name'     => trim((string) ($fields['from_name'] ?? '')),
        'from_role'     => trim((string) ($fields['from_role'] ?? 'admin')),
        'from_email'    => strtolower(trim((string) ($fields['from_email'] ?? ''))),
        'shop_url'      => trim((string) ($fields['shop_url'] ?? '')),
        'category'      => $category,
        'subject'       => $subject,
        'body'          => $body,
        'status'        => 'new',
        'lang'          => trim((string) ($fields['lang'] ?? 'en')),
        'ip'            => trim((string) ($fields['ip'] ?? '')),
        'client_unread' => false,
        'thread'        => [[
            'id'          => $postId,
            'ts'          => gmdate('c'),
            'author'      => 'client',
            'author_name' => trim((string) ($fields['from_name'] ?? '')),
            'author_user' => trim((string) ($fields['from_user'] ?? '')),
            'body'        => $body,
            'attachments' => [],
        ]],
    ];

    $rows = ecosystem_owner_messages_load();
    $rows[] = $row;
    if (!ecosystem_owner_messages_save_all($rows)) {
        return null;
    }

    ecosystem_owner_messages_notify_owner($row);

    return (string) $row['id'];
}

/** @param array<string,mixed> $row */
function ecosystem_owner_messages_notify_owner(array $row): void
{
    $adminUrl = 'https://bilohash.com/ecosystem/admin-messages.php';
    $lang = (string) ($row['lang'] ?? 'en');
    if ($lang === 'ua' || $lang === 'uk') {
        $adminUrl = 'https://bilohash.com/ecosystem/admin-messages.php?lang=ua';
    } elseif ($lang === 'no') {
        $adminUrl = 'https://bilohash.com/ecosystem/admin-messages.php?lang=no';
    }
    $msgId = (string) ($row['id'] ?? '');
    if ($msgId !== '') {
        $adminUrl .= (str_contains($adminUrl, '?') ? '&' : '?') . 'id=' . rawurlencode($msgId);
    }

    $msgBody = (string) ($row['body'] ?? '');
    // Body may be HTML from rich editors — email fields are escaped as text.
    $msgBody = html_entity_decode(
        strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $msgBody)),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $msgBody = trim(preg_replace("/[ \t]+\n/", "\n", $msgBody) ?? $msgBody);

    $fields = [
        'From'     => trim((string) ($row['from_name'] ?? '')) !== ''
            ? (string) $row['from_name'] . ' (' . (string) ($row['from_user'] ?? '') . ')'
            : (string) ($row['from_user'] ?? 'Shop admin'),
        'Role'     => (string) ($row['from_role'] ?? ''),
        'Email'    => (string) ($row['from_email'] ?? ''),
        'Shop'     => (string) ($row['shop_url'] ?? ''),
        'Category' => (string) ($row['category'] ?? ''),
        'Subject'  => (string) ($row['subject'] ?? ''),
        'Message'  => $msgBody,
    ];

    $replyTo = (string) ($row['from_email'] ?? '');
    if ($replyTo === '') {
        $replyTo = null;
    }

    $subject = '[BILOHASH Admin] ' . (string) ($row['subject'] ?? 'Support message');
    try {
        if (function_exists('bh_owner_notify')) {
            bh_owner_notify($subject, 'New message from Shop CMS admin', $fields, $replyTo);
        }
    } catch (Throwable $e) {
        error_log('ecosystem_owner_messages_notify_owner: ' . $e->getMessage());
    }
}

function ecosystem_owner_messages_mark_read(string $id): bool
{
    $id = trim($id);
    if ($id === '') {
        return false;
    }
    $rows = ecosystem_owner_messages_load();
    $found = false;
    foreach ($rows as $i => $row) {
        if ((string) ($row['id'] ?? '') !== $id) {
            continue;
        }
        $rows[$i]['status'] = 'read';
        $found = true;
        break;
    }
    return $found && ecosystem_owner_messages_save_all($rows);
}

function ecosystem_owner_messages_archive(string $id): bool
{
    $id = trim($id);
    if ($id === '') {
        return false;
    }
    $rows = ecosystem_owner_messages_load();
    $found = false;
    foreach ($rows as $i => $row) {
        if ((string) ($row['id'] ?? '') !== $id) {
            continue;
        }
        $rows[$i]['status'] = 'archived';
        $found = true;
        break;
    }
    return $found && ecosystem_owner_messages_save_all($rows);
}

function ecosystem_owner_messages_mark_unread(string $id): bool
{
    $id = trim($id);
    if ($id === '') {
        return false;
    }
    $rows = ecosystem_owner_messages_load();
    $found = false;
    foreach ($rows as $i => $row) {
        if ((string) ($row['id'] ?? '') !== $id) {
            continue;
        }
        $rows[$i]['status'] = 'new';
        $found = true;
        break;
    }
    return $found && ecosystem_owner_messages_save_all($rows);
}

function ecosystem_owner_messages_restore(string $id): bool
{
    $id = trim($id);
    if ($id === '') {
        return false;
    }
    $rows = ecosystem_owner_messages_load();
    $found = false;
    foreach ($rows as $i => $row) {
        if ((string) ($row['id'] ?? '') !== $id) {
            continue;
        }
        if ((string) ($row['status'] ?? '') !== 'archived') {
            return false;
        }
        $rows[$i]['status'] = 'read';
        $found = true;
        break;
    }
    return $found && ecosystem_owner_messages_save_all($rows);
}

function ecosystem_owner_messages_mark_all_read(): int
{
    $rows = ecosystem_owner_messages_load();
    $n = 0;
    foreach ($rows as $i => $row) {
        if ((string) ($row['status'] ?? 'new') !== 'new') {
            continue;
        }
        $rows[$i]['status'] = 'read';
        $n++;
    }
    if ($n === 0) {
        return 0;
    }
    return ecosystem_owner_messages_save_all($rows) ? $n : 0;
}

/** @return array{all:int,new:int,read:int,archived:int} */
function ecosystem_owner_messages_counts(): array
{
    $counts = ['all' => 0, 'new' => 0, 'read' => 0, 'archived' => 0];
    foreach (ecosystem_owner_messages_load() as $row) {
        $counts['all']++;
        $status = (string) ($row['status'] ?? 'new');
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }
    return $counts;
}

/** @return list<array<string,mixed>> */
function ecosystem_owner_messages_for_admin_ui(): array
{
    $rows = [];
    foreach (ecosystem_owner_messages_load() as $row) {
        $rows[] = ecosystem_message_row_for_ui($row);
    }
    usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['last_activity'] ?? $b['ts'] ?? ''), (string) ($a['last_activity'] ?? $a['ts'] ?? '')));
    return $rows;
}

function ecosystem_owner_message_category_label(string $category, array $L): string
{
    $key = 'msg_cat_' . $category;
    return (string) ($L[$key] ?? ucfirst($category));
}

/** @return array{ok:bool,message:string} */
function ecosystem_owner_messages_handle_admin_post(string $lang): array
{
    $L = ecosystem_admin_texts($lang);

    if (!ecosystem_admin_is_logged_in()) {
        return ['ok' => false, 'message' => $L['login_error'] ?? 'Not signed in.'];
    }

    if (isset($_POST['message_mark_read'])) {
        $id = trim((string) ($_POST['message_id'] ?? ''));
        if ($id === '' || !ecosystem_owner_messages_mark_read($id)) {
            return ['ok' => false, 'message' => $L['message_error'] ?? 'Could not update message.'];
        }
        return ['ok' => true, 'message' => $L['message_marked_read'] ?? 'Marked as read.'];
    }

    if (isset($_POST['message_archive'])) {
        $id = trim((string) ($_POST['message_id'] ?? ''));
        if ($id === '' || !ecosystem_owner_messages_archive($id)) {
            return ['ok' => false, 'message' => $L['message_error'] ?? 'Could not update message.'];
        }
        return ['ok' => true, 'message' => $L['message_archived_ok'] ?? 'Message archived.'];
    }

    if (isset($_POST['message_mark_unread'])) {
        $id = trim((string) ($_POST['message_id'] ?? ''));
        if ($id === '' || !ecosystem_owner_messages_mark_unread($id)) {
            return ['ok' => false, 'message' => $L['message_error'] ?? 'Could not update message.'];
        }
        return ['ok' => true, 'message' => $L['message_marked_unread'] ?? 'Marked as unread.'];
    }

    if (isset($_POST['message_restore'])) {
        $id = trim((string) ($_POST['message_id'] ?? ''));
        if ($id === '' || !ecosystem_owner_messages_restore($id)) {
            return ['ok' => false, 'message' => $L['message_error'] ?? 'Could not update message.'];
        }
        return ['ok' => true, 'message' => $L['message_restored_ok'] ?? 'Message restored.'];
    }

    if (isset($_POST['message_mark_all_read'])) {
        $n = ecosystem_owner_messages_mark_all_read();
        if ($n === 0) {
            return ['ok' => true, 'message' => $L['message_all_read_none'] ?? 'No unread messages.'];
        }
        return ['ok' => true, 'message' => sprintf($L['message_all_read_ok'] ?? '%d marked as read.', $n)];
    }

    if (isset($_POST['message_reply'])) {
        $id = trim((string) ($_POST['message_id'] ?? ''));
        $replyBody = trim((string) ($_POST['reply_body'] ?? ''));
        $row = ecosystem_owner_messages_by_id($id);
        if ($row === null) {
            return ['ok' => false, 'message' => $L['message_reply_error'] ?? 'Reply not sent.'];
        }
        $uploads = [];
        if (isset($_FILES['reply_attachments']) && is_array($_FILES['reply_attachments']['name'] ?? null)) {
            $names = $_FILES['reply_attachments']['name'];
            $tmp = $_FILES['reply_attachments']['tmp_name'];
            $errs = $_FILES['reply_attachments']['error'];
            $sizes = $_FILES['reply_attachments']['size'];
            foreach ($names as $i => $name) {
                if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $uploads[] = [
                    'name'     => (string) $name,
                    'tmp_name' => (string) ($tmp[$i] ?? ''),
                    'error'    => (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE),
                    'size'     => (int) ($sizes[$i] ?? 0),
                ];
            }
        }
        if ($replyBody === '' && $uploads === []) {
            return ['ok' => false, 'message' => $L['message_reply_error'] ?? 'Reply not sent.'];
        }
        if (!ecosystem_message_add_post($id, 'owner', $replyBody, [
            'author_name' => 'BILOHASH',
            'author_user' => 'owner',
        ], $uploads !== [] ? $uploads : null)) {
            return ['ok' => false, 'message' => $L['message_reply_error'] ?? 'Reply not sent.'];
        }
        ecosystem_owner_messages_mark_read($id);
        return ['ok' => true, 'message' => $L['message_reply_sent'] ?? 'Reply sent.'];
    }

    if (isset($_POST['message_compose'])) {
        $toEmail = trim((string) ($_POST['compose_email'] ?? ''));
        $toName = trim((string) ($_POST['compose_name'] ?? ''));
        $subject = trim((string) ($_POST['compose_subject'] ?? ''));
        $body = trim((string) ($_POST['compose_body'] ?? ''));
        $category = trim((string) ($_POST['compose_category'] ?? 'support'));
        $shopUrl = trim((string) ($_POST['compose_shop_url'] ?? ''));
        return ecosystem_message_owner_compose($lang, $toEmail, $toName, $subject, $body, $category, $shopUrl);
    }

    return ['ok' => true, 'message' => ''];
}

require_once __DIR__ . '/ecosystem-message-thread.php';