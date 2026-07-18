<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem-owner-messages.php';

const ECOSYSTEM_MSG_ATTACH_MAX_BYTES = 5_242_880; // 5 MB
const ECOSYSTEM_MSG_ATTACH_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

function ecosystem_message_attachments_root(): string
{
    $dir = dirname(__DIR__) . '/data/message-attachments';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function ecosystem_message_post_new_id(): string
{
    return 'post_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(3));
}

function ecosystem_message_file_new_id(): string
{
    return 'att_' . bin2hex(random_bytes(6));
}

/** @param array<string,mixed> $row */
function ecosystem_message_thread_normalize(array &$row): void
{
    if (!isset($row['thread']) || !is_array($row['thread']) || $row['thread'] === []) {
        $body = trim((string) ($row['body'] ?? ''));
        if ($body !== '') {
            $row['thread'] = [[
                'id'          => ecosystem_message_post_new_id(),
                'ts'          => (string) ($row['ts'] ?? gmdate('c')),
                'author'      => 'client',
                'author_name' => (string) ($row['from_name'] ?? ''),
                'author_user' => (string) ($row['from_user'] ?? ''),
                'body'        => $body,
                'attachments' => [],
            ]];
        } else {
            $row['thread'] = [];
        }
    }
    if (!isset($row['client_unread'])) {
        $row['client_unread'] = false;
    }
    if (!isset($row['last_activity']) || (string) $row['last_activity'] === '') {
        $row['last_activity'] = (string) ($row['ts'] ?? gmdate('c'));
    }
}

/** @return list<array<string,mixed>> */
function ecosystem_message_thread_posts(array $row): array
{
    ecosystem_message_thread_normalize($row);
    $posts = is_array($row['thread'] ?? null) ? $row['thread'] : [];
    usort($posts, static fn(array $a, array $b): int => strcmp((string) ($a['ts'] ?? ''), (string) ($b['ts'] ?? '')));
    return $posts;
}

/** @return array<string,mixed>|null */
function ecosystem_message_thread_last_post(array $row): ?array
{
    $posts = ecosystem_message_thread_posts($row);
    if ($posts === []) {
        return null;
    }
    return $posts[count($posts) - 1];
}

function ecosystem_message_thread_preview(array $row, int $max = 110): string
{
    $last = ecosystem_message_thread_last_post($row);
    $text = $last !== null ? (string) ($last['body'] ?? '') : (string) ($row['body'] ?? '');
    if ($text === '' && $last !== null && !empty($last['attachments'])) {
        $text = '📎 ' . (string) ($last['attachments'][0]['name'] ?? 'attachment');
    }
    // System tickets and rich replies may store HTML — preview must be plain text.
    $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], ' ', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    $text = trim($text);
    if (mb_strlen($text) > $max) {
        return mb_substr($text, 0, $max) . '…';
    }
    return $text;
}

/** @param array<string,mixed> $row */
function ecosystem_message_row_for_ui(array $row, string $lang = 'en'): array
{
    ecosystem_message_thread_normalize($row);
    $posts = ecosystem_message_thread_posts($row);
    $uiPosts = [];
    foreach ($posts as $post) {
        $atts = [];
        foreach ((array) ($post['attachments'] ?? []) as $att) {
            if (!is_array($att)) {
                continue;
            }
            $atts[] = [
                'id'   => (string) ($att['id'] ?? ''),
                'name' => (string) ($att['name'] ?? ''),
                'mime' => (string) ($att['mime'] ?? ''),
                'size' => (int) ($att['size'] ?? 0),
            ];
        }
        $uiPosts[] = [
            'id'          => (string) ($post['id'] ?? ''),
            'ts'          => (string) ($post['ts'] ?? ''),
            'ts_label'    => license_cabinet_format_ts((string) ($post['ts'] ?? '')),
            'author'      => (string) ($post['author'] ?? 'client'),
            'author_name' => (string) ($post['author_name'] ?? ''),
            'author_user' => (string) ($post['author_user'] ?? ''),
            'body'        => (string) ($post['body'] ?? ''),
            'attachments' => $atts,
        ];
    }

    return [
        'id'            => (string) ($row['id'] ?? ''),
        'ts'            => (string) ($row['ts'] ?? ''),
        'ts_label'      => license_cabinet_format_ts((string) ($row['ts'] ?? '')),
        'last_activity' => (string) ($row['last_activity'] ?? ''),
        'status'        => (string) ($row['status'] ?? 'new'),
        'category'      => (string) ($row['category'] ?? 'support'),
        'subject'       => (string) ($row['subject'] ?? ''),
        'body'          => (string) ($row['body'] ?? ''),
        'preview'       => ecosystem_message_thread_preview($row),
        'from_name'     => (string) ($row['from_name'] ?? ''),
        'from_user'     => (string) ($row['from_user'] ?? ''),
        'from_role'     => (string) ($row['from_role'] ?? ''),
        'from_email'    => (string) ($row['from_email'] ?? ''),
        'shop_url'      => (string) ($row['shop_url'] ?? ''),
        'lang'          => (string) ($row['lang'] ?? 'en'),
        'client_unread' => !empty($row['client_unread']),
        'thread'        => $uiPosts,
    ];
}

/** @param array<int,array<string,mixed>> $rows */
function ecosystem_message_rows_save(array $rows): bool
{
    return ecosystem_owner_messages_save_all($rows);
}

/** @param array<string,mixed> $file */
function ecosystem_message_save_upload(string $messageId, string $postId, array $file): ?array
{
    $messageId = trim($messageId);
    $postId = trim($postId);
    if ($messageId === '' || $postId === '' || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > ECOSYSTEM_MSG_ATTACH_MAX_BYTES) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) ($file['tmp_name'] ?? ''));
    if (!isset(ECOSYSTEM_MSG_ATTACH_MIMES[$mime])) {
        return null;
    }

    $orig = (string) ($file['name'] ?? 'screenshot');
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $orig) ?? 'screenshot';
    $safe = trim($safe, '.-');
    if ($safe === '') {
        $safe = 'screenshot.' . ECOSYSTEM_MSG_ATTACH_MIMES[$mime];
    }

    $attId = ecosystem_message_file_new_id();
    $dir = ecosystem_message_attachments_root() . '/' . $messageId;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return null;
    }
    $stored = $attId . '_' . $safe;
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
        return null;
    }

    return [
        'id'   => $attId,
        'name' => $orig,
        'mime' => $mime,
        'size' => $size,
        'file' => $stored,
    ];
}

/** @return array{ok:bool,path?:string,mime?:string,name?:string} */
function ecosystem_message_attachment_resolve(string $messageId, string $postId, string $attId): array
{
    $row = ecosystem_owner_messages_by_id($messageId);
    if ($row === null) {
        return ['ok' => false];
    }
    foreach (ecosystem_message_thread_posts($row) as $post) {
        if ((string) ($post['id'] ?? '') !== $postId) {
            continue;
        }
        foreach ((array) ($post['attachments'] ?? []) as $att) {
            if (!is_array($att) || (string) ($att['id'] ?? '') !== $attId) {
                continue;
            }
            $file = (string) ($att['file'] ?? '');
            if ($file === '' || str_contains($file, '..') || str_contains($file, '/')) {
                return ['ok' => false];
            }
            $path = ecosystem_message_attachments_root() . '/' . $messageId . '/' . $file;
            if (!is_file($path)) {
                return ['ok' => false];
            }
            return [
                'ok'   => true,
                'path' => $path,
                'mime' => (string) ($att['mime'] ?? 'application/octet-stream'),
                'name' => (string) ($att['name'] ?? $file),
            ];
        }
    }
    return ['ok' => false];
}

function ecosystem_message_shop_host(string $shopUrl): string
{
    $shopUrl = trim($shopUrl);
    if ($shopUrl === '') {
        return '';
    }
    $parts = parse_url($shopUrl);
    if (!is_array($parts) || empty($parts['host'])) {
        return '';
    }
    return strtolower((string) $parts['host']);
}

function ecosystem_message_row_matches_client(array $row, string $user, string $email, string $shopHost): bool
{
    $rowUser = trim((string) ($row['from_user'] ?? ''));
    $rowEmail = strtolower(trim((string) ($row['from_email'] ?? '')));
    $rowHost = ecosystem_message_shop_host((string) ($row['shop_url'] ?? ''));
    $email = strtolower(trim($email));
    $user = trim($user);
    $shopHost = strtolower(trim($shopHost));

    $userMatch = $user !== '' && $rowUser !== '' && $rowUser === $user;
    $emailMatch = $email !== '' && $rowEmail !== '' && $rowEmail === $email;
    if (!$userMatch && !$emailMatch) {
        return false;
    }

    if ($shopHost !== '' && $rowHost !== '' && $rowHost !== $shopHost) {
        return false;
    }

    return true;
}

/** @param list<array<string,mixed>>|null $uploads */
function ecosystem_message_add_post(string $messageId, string $author, string $body, array $meta = [], ?array $uploads = null): bool
{
    $messageId = trim($messageId);
    $body = trim($body);
    $author = $author === 'owner' ? 'owner' : 'client';
    if ($messageId === '' || ($body === '' && ($uploads === null || $uploads === []))) {
        return false;
    }

    $rows = ecosystem_owner_messages_load();
    $found = false;
    $postId = ecosystem_message_post_new_id();
    $attachments = [];

    if ($uploads !== null) {
        foreach ($uploads as $file) {
            if (!is_array($file)) {
                continue;
            }
            $saved = ecosystem_message_save_upload($messageId, $postId, $file);
            if ($saved !== null) {
                $attachments[] = $saved;
            }
        }
    }

    foreach ($rows as $i => $row) {
        if ((string) ($row['id'] ?? '') !== $messageId) {
            continue;
        }
        ecosystem_message_thread_normalize($row);
        $row['thread'][] = [
            'id'          => $postId,
            'ts'          => gmdate('c'),
            'author'      => $author,
            'author_name' => trim((string) ($meta['author_name'] ?? '')),
            'author_user' => trim((string) ($meta['author_user'] ?? '')),
            'body'        => $body,
            'attachments' => $attachments,
        ];
        $row['last_activity'] = gmdate('c');
        if ($author === 'owner') {
            $row['client_unread'] = true;
            if ((string) ($row['status'] ?? '') === 'archived') {
                $row['status'] = 'read';
            }
        } else {
            $row['status'] = 'new';
            $row['client_unread'] = false;
        }
        $rows[$i] = $row;
        $found = true;
        break;
    }

    if (!$found) {
        return false;
    }

    if (!ecosystem_message_rows_save($rows)) {
        return false;
    }

    if ($author === 'client') {
        $row = ecosystem_owner_messages_by_id($messageId);
        if ($row !== null) {
            ecosystem_owner_messages_notify_owner($row);
        }
    } else {
        ecosystem_message_notify_client($messageId, $body);
    }

    return true;
}

function ecosystem_message_notify_client(string $messageId, string $snippet): void
{
    $row = ecosystem_owner_messages_by_id($messageId);
    if ($row === null) {
        return;
    }
    $to = (string) ($row['from_email'] ?? '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $lang = (string) ($row['lang'] ?? 'en');
    $shopUrl = (string) ($row['shop_url'] ?? 'https://bilohash.com/shop/admin/support.php');
    if (!str_contains($shopUrl, 'support.php')) {
        $shopUrl = rtrim($shopUrl, '/') . '/support.php';
    }
    if ($lang !== 'en') {
        $shopUrl .= (str_contains($shopUrl, '?') ? '&' : '?') . 'lang=' . rawurlencode($lang === 'ua' ? 'uk' : $lang);
    }
    $shopUrl .= (str_contains($shopUrl, '?') ? '&' : '?') . 'tab=inbox&id=' . rawurlencode($messageId);

    $baseSubject = (string) ($row['subject'] ?? 'Support');
    $subject = str_starts_with(strtolower($baseSubject), 're:') ? $baseSubject : ('Re: ' . $baseSubject);

    // Hosting/platform: send from support@ via PHP mail (works without bilohash SMTP).
    if (function_exists('hs_mail_send_support') && function_exists('hs_mail_template_support_message')) {
        $bodyHtml = trim($snippet);
        if ($bodyHtml === '') {
            return;
        }
        if (!preg_match('/<[a-z][\s\S]*>/i', $bodyHtml)) {
            $bodyHtml = '<p>' . nl2br(htmlspecialchars($bodyHtml, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        $bodyHtml .= '<p style="margin-top:1.25rem"><a href="' . htmlspecialchars($shopUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars('Open conversation', ENT_QUOTES, 'UTF-8') . '</a></p>';
        $html = hs_mail_template_support_message($subject, $bodyHtml);
        hs_mail_send_support($to, $subject, $html);
        return;
    }

    $html = '<p>' . nl2br(htmlspecialchars(mb_substr($snippet, 0, 500))) . '</p>'
        . '<p><a href="' . htmlspecialchars($shopUrl) . '">Open conversation in Shop admin</a></p>'
        . '<p style="color:#94a3b8;font-size:13px">— BILOHASH</p>';
    if (function_exists('bh_send_mail')) {
        bh_send_mail($to, $subject, $html, function_exists('bh_owner_email') ? bh_owner_email() : null, 'BILOHASH');
    }
}

/** @return array{ok:bool,message:string,id?:string} */
function ecosystem_message_owner_compose(string $lang, string $toEmail, string $toName, string $subject, string $body, string $category = 'support', string $shopUrl = ''): array
{
    $L = ecosystem_admin_texts($lang);
    if (!ecosystem_admin_is_logged_in()) {
        return ['ok' => false, 'message' => $L['login_error'] ?? 'Not signed in.'];
    }
    $toEmail = strtolower(trim($toEmail));
    $subject = trim($subject);
    $body = trim($body);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => $L['message_compose_invalid_email'] ?? 'Invalid client email.'];
    }
    if ($subject === '' || $body === '') {
        return ['ok' => false, 'message' => $L['message_compose_required'] ?? 'Subject and message are required.'];
    }
    if (!in_array($category, ECOSYSTEM_MESSAGE_CATEGORIES, true)) {
        $category = 'support';
    }

    $id = ecosystem_owner_messages_new_id();
    $row = [
        'id'            => $id,
        'ts'            => gmdate('c'),
        'last_activity' => gmdate('c'),
        'from_user'     => '',
        'from_name'     => trim($toName),
        'from_role'     => 'client',
        'from_email'    => $toEmail,
        'shop_url'      => trim($shopUrl),
        'category'      => $category,
        'subject'       => $subject,
        'body'          => $body,
        'status'        => 'read',
        'lang'          => $lang,
        'ip'            => '',
        'client_unread' => true,
        'thread'        => [[
            'id'          => ecosystem_message_post_new_id(),
            'ts'          => gmdate('c'),
            'author'      => 'owner',
            'author_name' => 'BILOHASH',
            'author_user' => 'owner',
            'body'        => $body,
            'attachments' => [],
        ]],
    ];

    $rows = ecosystem_owner_messages_load();
    $rows[] = $row;
    if (!ecosystem_message_rows_save($rows)) {
        return ['ok' => false, 'message' => $L['message_error'] ?? 'Could not save message.'];
    }
    ecosystem_message_notify_client($id, $body);
    return ['ok' => true, 'message' => $L['message_compose_sent'] ?? 'Message sent to client.', 'id' => $id];
}

function ecosystem_message_client_mark_read(string $messageId, string $user, string $email, string $shopHost): bool
{
    $rows = ecosystem_owner_messages_load();
    $found = false;
    foreach ($rows as $i => $row) {
        if ((string) ($row['id'] ?? '') !== $messageId) {
            continue;
        }
        if (!ecosystem_message_row_matches_client($row, $user, $email, $shopHost)) {
            return false;
        }
        if (!empty($row['client_unread'])) {
            $rows[$i]['client_unread'] = false;
            $found = true;
        }
        break;
    }
    return $found ? ecosystem_message_rows_save($rows) : true;
}

/** @return list<array<string,mixed>> */
function ecosystem_message_threads_for_client(string $user, string $email, string $shopUrl): array
{
    $host = ecosystem_message_shop_host($shopUrl);
    $out = [];
    foreach (ecosystem_owner_messages_load() as $row) {
        if (!ecosystem_message_row_matches_client($row, $user, $email, $host)) {
            continue;
        }
        $out[] = ecosystem_message_row_for_ui($row);
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string) ($b['last_activity'] ?? ''), (string) ($a['last_activity'] ?? '')));
    return $out;
}

function ecosystem_message_client_can_access(string $messageId, string $user, string $email, string $shopUrl): bool
{
    $row = ecosystem_owner_messages_by_id($messageId);
    if ($row === null) {
        return false;
    }
    return ecosystem_message_row_matches_client($row, $user, $email, ecosystem_message_shop_host($shopUrl));
}