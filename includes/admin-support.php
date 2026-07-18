<?php
declare(strict_types=1);

require_once __DIR__ . '/support.php';

function hs_admin_support_panel_host(): string
{
    return ecosystem_message_shop_host(hs_support_panel_url());
}

/** @param array<string,mixed> $row */
function hs_admin_support_is_hosting_thread(array $row): bool
{
    $role = strtolower((string) ($row['from_role'] ?? ''));
    if (str_contains($role, 'hosting')) {
        return true;
    }
    $subject = (string) ($row['subject'] ?? '');
    if (str_starts_with($subject, '[Hosting/')) {
        return true;
    }
    $shopUrl = (string) ($row['shop_url'] ?? '');
    if ($shopUrl === '') {
        return false;
    }
    if (str_contains($shopUrl, '/panel/support.php') || str_contains($shopUrl, '/panel/')) {
        $panelHost = hs_admin_support_panel_host();
        $rowHost = ecosystem_message_shop_host($shopUrl);
        if ($panelHost === '' || $rowHost === '' || $rowHost === $panelHost) {
            return true;
        }
    }

    return false;
}

function hs_admin_support_unread_count(): int
{
    if (!hs_ecosystem_messages_ready()) {
        return 0;
    }
    $n = 0;
    foreach (ecosystem_owner_messages_load() as $row) {
        if (!hs_admin_support_is_hosting_thread($row)) {
            continue;
        }
        if ((string) ($row['status'] ?? 'new') === 'new') {
            $n++;
        }
    }

    return $n;
}

/** @return list<array<string,mixed>> */
function hs_admin_support_threads(string $lang): array
{
    $out = [];
    foreach (ecosystem_owner_messages_load() as $row) {
        if (!hs_admin_support_is_hosting_thread($row)) {
            continue;
        }
        $ui = ecosystem_message_row_for_ui($row, $lang);
        $ui['admin_unread'] = ((string) ($row['status'] ?? 'new')) === 'new';
        $out[] = $ui;
    }
    usort($out, static fn(array $a, array $b): int => strcmp(
        (string) ($b['last_activity'] ?? $b['ts'] ?? ''),
        (string) ($a['last_activity'] ?? $a['ts'] ?? '')
    ));

    return $out;
}

function hs_admin_support_can_access(string $messageId): bool
{
    $row = ecosystem_owner_messages_by_id($messageId);
    if ($row === null) {
        return false;
    }

    return hs_admin_support_is_hosting_thread($row);
}

/**
 * Clients for the admin “write to client” picker.
 *
 * @return list<array{id:string,username:string,email:string,name:string,plan:string,status:string,label:string,search:string}>
 */
function hs_admin_support_clients_for_picker(array $t): array
{
    require_once __DIR__ . '/storage.php';
    require_once __DIR__ . '/plans.php';
    $out = [];
    foreach (hs_users() as $u) {
        if (!is_array($u)) {
            continue;
        }
        $id = (string) ($u['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $username = (string) ($u['username'] ?? '');
        $email = (string) ($u['email'] ?? '');
        $name = hs_support_client_display_name($u);
        $plan = (string) ($u['plan'] ?? 'starter');
        $status = (string) ($u['subscription_status'] ?? '');
        $planLabel = hs_plan_panel_label($plan, $t);
        $labelParts = array_filter([$name, $username !== '' ? '@' . $username : '', $email, $planLabel, $status]);
        $label = implode(' · ', $labelParts);
        $out[] = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'name' => $name,
            'plan' => $plan,
            'plan_label' => $planLabel,
            'status' => $status,
            'label' => $label,
            'search' => strtolower($name . ' ' . $username . ' ' . $email . ' ' . $plan . ' ' . $planLabel . ' ' . $status . ' ' . $id),
        ];
    }
    usort($out, static function (array $a, array $b): int {
        $sa = (string) ($a['status'] ?? '');
        $sb = (string) ($b['status'] ?? '');
        if ($sa === 'active' && $sb !== 'active') {
            return -1;
        }
        if ($sb === 'active' && $sa !== 'active') {
            return 1;
        }

        return strcasecmp((string) ($a['username'] ?? ''), (string) ($b['username'] ?? ''));
    });

    return $out;
}

/**
 * Send support email from support@… to any address and build HTML body.
 *
 * @param array<string,string> $t
 * @return array{ok:bool,error?:string}
 */
function hs_admin_support_send_email(string $toEmail, string $subject, string $bodyHtml, array $t = []): array
{
    require_once __DIR__ . '/mail-templates.php';
    $toEmail = strtolower(trim($toEmail));
    $subject = trim($subject);
    $bodyHtml = hs_support_sanitize_html($bodyHtml);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email'];
    }
    if ($subject === '' || !hs_support_body_has_content($bodyHtml)) {
        return ['ok' => false, 'error' => 'required'];
    }
    $html = hs_mail_template_support_message($subject, $bodyHtml, $t);
    $ok = hs_mail_send_support($toEmail, $subject, $html);
    if (!$ok) {
        return ['ok' => false, 'error' => 'mail_failed'];
    }

    return ['ok' => true];
}

/**
 * Start a new hosting support thread from admin → client or free email.
 * Always tries to send a real email from support@domain.
 *
 * @param array<string,string> $t
 * @return array{ok:bool,id?:string,error?:string,mail_sent?:bool}
 */
function hs_admin_support_compose_to_client(
    string $userId,
    string $subject,
    string $body,
    string $adminUser,
    string $authorName,
    string $lang,
    string $toEmail = '',
    array $t = []
): array {
    require_once __DIR__ . '/storage.php';
    require_once __DIR__ . '/mail-templates.php';

    $subject = trim($subject);
    $body = hs_support_sanitize_html($body);
    $toEmail = strtolower(trim($toEmail));
    $userId = trim($userId);

    if ($subject === '' || !hs_support_body_has_content($body)) {
        return ['ok' => false, 'error' => 'required'];
    }
    if (!hs_ecosystem_messages_ready()) {
        return ['ok' => false, 'error' => 'module_missing'];
    }

    $user = null;
    $username = '';
    $displayName = '';
    $hostingUserId = '';

    if ($userId !== '') {
        $user = hs_user_by_id($userId);
        if ($user === null) {
            return ['ok' => false, 'error' => 'client_not_found'];
        }
        $username = (string) ($user['username'] ?? 'user');
        $displayName = hs_support_client_display_name($user);
        $hostingUserId = $userId;
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $toEmail = function_exists('hs_client_support_email')
                ? hs_client_support_email($user)
                : (string) ($user['email'] ?? '');
            if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $toEmail = (string) ($user['email'] ?? '');
            }
        }
    }

    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email'];
    }

    if ($username === '') {
        $local = strstr($toEmail, '@', true);
        $username = is_string($local) && $local !== '' ? preg_replace('/[^a-z0-9._-]/i', '', $local) : 'external';
        if ($username === '') {
            $username = 'external';
        }
        $displayName = $displayName !== '' ? $displayName : $toEmail;
    }
    if ($displayName === '') {
        $displayName = $toEmail;
    }

    $panelUrl = hs_support_panel_url();
    $fullSubject = $user !== null
        ? ('[Hosting/' . $username . '] ' . $subject)
        : ('[Hosting/mail] ' . $subject);
    $id = ecosystem_owner_messages_new_id();
    $postId = function_exists('ecosystem_message_post_new_id')
        ? ecosystem_message_post_new_id()
        : ('post_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(3)));

    $row = [
        'id' => $id,
        'ts' => gmdate('c'),
        'last_activity' => gmdate('c'),
        'from_user' => $username,
        'from_name' => $displayName,
        'from_role' => $user !== null ? 'Hosting client' : 'External email',
        'from_email' => $toEmail,
        'shop_url' => $panelUrl,
        'category' => 'support',
        'subject' => $fullSubject,
        'body' => $body,
        'status' => 'read',
        'lang' => $lang === 'uk' ? 'ua' : $lang,
        'ip' => '',
        'client_unread' => $user !== null,
        'hosting_user_id' => $hostingUserId,
        'outbound_from' => hs_mail_support_from_email(),
        'thread' => [[
            'id' => $postId,
            'ts' => gmdate('c'),
            'author' => 'owner',
            'author_name' => $authorName,
            'author_user' => $adminUser,
            'body' => $body,
            'attachments' => [],
        ]],
    ];

    $rows = ecosystem_owner_messages_load();
    $rows[] = $row;
    if (!ecosystem_message_rows_save($rows) && !ecosystem_owner_messages_save_all($rows)) {
        return ['ok' => false, 'error' => 'save_failed'];
    }

    $mailRes = hs_admin_support_send_email($toEmail, $subject, $body, $t);
    $mailSent = !empty($mailRes['ok']);

    return ['ok' => true, 'id' => $id, 'mail_sent' => $mailSent, 'to' => $toEmail];
}

/** @param array<string, string> $t */
function hs_render_admin_support_panel(array $t, string $lang): string
{
    $brand = (string) ($t['brand'] ?? 'Solaskinner');
    $clients = hs_admin_support_clients_for_picker($t);
    require_once __DIR__ . '/mail-templates.php';
    $supportFrom = function_exists('hs_mail_support_from_email')
        ? hs_mail_support_from_email()
        : hs_host_support_inbox_email($t);
    $cfg = [
        'lang' => $lang,
        'csrf' => hs_csrf_token(),
        'apiMessages' => hs_admin_url('support-messages.php'),
        'apiMessageFile' => hs_admin_url('support-message-file.php'),
        'clients' => $clients,
        'supportFrom' => $supportFrom,
        'i18n' => [
            'inbox_sent_ok' => $t['admin_support_reply_ok'] ?? 'Reply sent.',
            'inbox_sent_ok_mail' => $t['admin_support_reply_ok_mail'] ?? 'Reply sent and emailed from {from}.',
            'inbox_sent_ok_nomail' => $t['admin_support_reply_ok_nomail'] ?? 'Reply saved in panel (email could not be sent).',
            'inbox_sent_error' => $t['admin_support_reply_fail'] ?? 'Reply failed.',
            'inbox_author_owner' => $t['admin_support_author_team'] ?? ($brand . ' support'),
            'inbox_author_client' => $t['admin_support_author_client'] ?? 'Client',
            'inbox_empty' => $t['admin_support_inbox_empty'] ?? 'No conversations yet.',
            'inbox_select' => $t['admin_support_inbox_select'] ?? 'Select a conversation.',
            'inbox_unread' => $t['admin_support_inbox_unread'] ?? 'New',
            'inbox_error' => $t['admin_support_inbox_error'] ?? 'Could not load conversations.',
            'back_list' => $t['admin_support_back_list'] ?? 'Conversations',
            'archive' => $t['admin_support_archive'] ?? 'Archive',
            'mark_read' => $t['admin_support_mark_read'] ?? 'Mark read',
            'archive_ok' => $t['admin_support_archive_ok'] ?? 'Conversation archived.',
            'mark_read_ok' => $t['admin_support_mark_read_ok'] ?? 'Marked as read.',
            'loading' => $t['support_loading'] ?? 'Loading…',
            'fill_required' => $t['support_fill_required'] ?? 'Message is required.',
            'editor_reply_placeholder' => $t['support_editor_reply_placeholder'] ?? 'Your reply…',
            'link_prompt' => $t['support_editor_link_prompt'] ?? 'Enter link URL:',
            'module_missing' => $t['support_module_missing'] ?? 'Messaging module unavailable',
            'compose_title' => $t['admin_support_compose_title'] ?? 'Write to client',
            'compose_open' => $t['admin_support_compose_open'] ?? 'New message',
            'compose_client' => $t['admin_support_compose_client'] ?? 'Client',
            'compose_client_optional' => $t['admin_support_compose_client_optional'] ?? 'Client (optional)',
            'compose_email' => $t['admin_support_compose_email'] ?? 'To email',
            'compose_email_ph' => $t['admin_support_compose_email_ph'] ?? 'any@email.com',
            'compose_email_hint' => str_replace(
                '{from}',
                $supportFrom,
                $t['admin_support_compose_email_hint'] ?? 'Sends a real email from {from}. Client picker is optional.'
            ),
            'compose_search' => $t['admin_support_compose_search'] ?? 'Search by name, @user, email or plan…',
            'compose_subject' => $t['admin_support_compose_subject'] ?? 'Subject',
            'compose_subject_ph' => $t['admin_support_compose_subject_ph'] ?? 'How can we help?',
            'compose_send' => $t['admin_support_compose_send'] ?? 'Send email',
            'compose_cancel' => $t['admin_support_compose_cancel'] ?? 'Cancel',
            'compose_pick' => $t['admin_support_compose_pick'] ?? 'Choose a client or type any email below',
            'compose_none' => $t['admin_support_compose_none'] ?? 'No clients match your search.',
            'compose_need_client' => $t['admin_support_compose_need_email'] ?? 'Enter a recipient email.',
            'compose_need_email' => $t['admin_support_compose_need_email'] ?? 'Enter a valid recipient email.',
            'compose_need_subject' => $t['admin_support_compose_need_subject'] ?? 'Enter a subject.',
            'compose_ok' => $t['admin_support_compose_ok'] ?? 'Message sent.',
            'compose_ok_mail' => $t['admin_support_compose_ok_mail'] ?? 'Sent from {from} to {to}.',
            'compose_ok_nomail' => $t['admin_support_compose_ok_nomail'] ?? 'Saved in support, but email failed to send.',
            'compose_error' => $t['admin_support_compose_error'] ?? 'Could not send message.',
            'filter_ph' => $t['admin_support_filter_ph'] ?? 'Filter conversations…',
            'status_active' => $t['admin_client_status_active'] ?? 'active',
        ],
    ];

    $demoBanner = '';
    $demoText = trim((string) ($t['support_demo_banner'] ?? ''));
    if ($demoText !== '' && function_exists('ecosystem_support_hosting_demo_banner')) {
        $demoBanner = ecosystem_support_hosting_demo_banner($demoText);
    }

    $lead = (string) ($t['admin_support_lead'] ?? '');
    $leadMail = str_replace(
        '{from}',
        $supportFrom,
        (string) ($t['admin_support_lead_mail'] ?? 'Outgoing mail is sent from {from} to any address.')
    );

    return '<div id="hs-admin-support" class="hs-support hs-admin-support">'
        . $demoBanner
        . '<div class="hs-admin-support-toast hidden" data-support-toast role="status" aria-live="polite"></div>'
        . '<div class="hs-admin-support-toolbar">'
        . '<div class="hs-support-desc-wrap">'
        . '<p class="hp-muted hs-support-desc" style="margin:0">' . hs_h($lead) . '</p>'
        . '<p class="hp-muted hs-support-desc-mail" style="margin:.35rem 0 0"><i class="fa-solid fa-envelope"></i> '
        . hs_h($leadMail) . '</p></div>'
        . '<button type="button" class="hs-btn hs-btn-primary hp-dash-btn-sm" data-support-compose-open>'
        . '<i class="fa-solid fa-pen-to-square"></i> ' . hs_h($t['admin_support_compose_open'] ?? 'New message')
        . '</button></div>'
        . '<div class="hs-support-panel" data-support-panel="inbox">'
        . '<div class="hs-support-inbox"><aside class="hs-support-list-wrap" data-support-list-wrap>'
        . '<div class="hs-admin-support-list-tools">'
        . '<label class="hs-admin-support-filter-label"><span class="hs-visually-hidden">' . hs_h($t['admin_support_filter_ph'] ?? 'Filter') . '</span>'
        . '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>'
        . '<input type="search" class="hs-admin-support-filter" data-support-list-filter '
        . 'placeholder="' . hs_h($t['admin_support_filter_ph'] ?? 'Filter conversations…') . '" autocomplete="off"></label>'
        . '</div>'
        . '<p class="hs-support-loading hp-muted hidden" data-support-list-loading><i class="fa-solid fa-spinner fa-spin"></i> '
        . hs_h($t['support_loading'] ?? 'Loading…') . '</p>'
        . '<div class="hs-support-list" data-support-list></div>'
        . '<div class="hs-support-empty hidden" data-support-list-empty>'
        . '<p class="hp-muted">' . hs_h($t['admin_support_inbox_empty'] ?? '') . '</p></div></aside>'
        . '<div class="hs-support-detail-wrap" data-support-detail-wrap>'
        . '<div class="hs-support-detail-empty" data-support-detail-empty><p class="hp-muted">'
        . hs_h($t['admin_support_inbox_select'] ?? '') . '</p>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-support-compose-open-empty>'
        . '<i class="fa-solid fa-pen-to-square"></i> ' . hs_h($t['admin_support_compose_open'] ?? 'New message')
        . '</button></div>'
        // Compose panel
        . '<div class="hs-support-compose hidden" data-support-compose>'
        . '<div class="hs-support-compose-head">'
        . '<h3><i class="fa-solid fa-envelope"></i> ' . hs_h($t['admin_support_compose_title'] ?? 'Write to client') . '</h3>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-support-compose-cancel>'
        . hs_h($t['admin_support_compose_cancel'] ?? 'Cancel') . '</button></div>'
        . '<form class="hs-support-compose-form" data-support-compose-form autocomplete="off">'
        . '<div class="hs-field hs-admin-client-picker" data-client-picker>'
        . '<label for="hs-admin-support-client-search">' . hs_h($t['admin_support_compose_client_optional'] ?? ($t['admin_support_compose_client'] ?? 'Client (optional)')) . '</label>'
        . '<div class="hs-admin-client-picker-box">'
        . '<i class="fa-solid fa-user-group" aria-hidden="true"></i>'
        . '<input type="search" id="hs-admin-support-client-search" class="hs-admin-client-picker-input" '
        . 'data-client-picker-input placeholder="' . hs_h($t['admin_support_compose_search'] ?? 'Search…') . '" '
        . 'autocomplete="off" role="combobox" aria-expanded="false" aria-controls="hs-admin-support-client-list" aria-autocomplete="list">'
        . '<input type="hidden" name="user_id" data-client-picker-value value="">'
        . '</div>'
        . '<div class="hs-admin-client-picker-selected hidden" data-client-picker-selected></div>'
        . '<ul class="hs-admin-client-picker-list hidden" id="hs-admin-support-client-list" data-client-picker-list role="listbox"></ul>'
        . '<p class="hp-muted hs-admin-client-picker-hint" data-client-picker-hint>'
        . hs_h($t['admin_support_compose_pick'] ?? 'Choose a client or type any email below') . '</p>'
        . '</div>'
        . '<label class="hs-field"><span>' . hs_h($t['admin_support_compose_email'] ?? 'To email') . '</span>'
        . '<input type="email" name="to_email" data-support-compose-email maxlength="200" required '
        . 'placeholder="' . hs_h($t['admin_support_compose_email_ph'] ?? 'any@email.com') . '" autocomplete="email">'
        . '<span class="hp-muted hs-admin-support-email-hint">' . hs_h(str_replace(
            '{from}',
            $supportFrom,
            $t['admin_support_compose_email_hint'] ?? 'Sends a real email from {from}.'
        )) . '</span></label>'
        . '<label class="hs-field"><span>' . hs_h($t['admin_support_compose_subject'] ?? 'Subject') . '</span>'
        . '<input type="text" name="subject" data-support-compose-subject maxlength="200" '
        . 'placeholder="' . hs_h($t['admin_support_compose_subject_ph'] ?? '') . '"></label>'
        . hs_support_render_editor('compose', $t['admin_support_compose_body'] ?? ($t['admin_support_reply_label'] ?? 'Message'), $t['support_editor_reply_placeholder'] ?? '', 'compact')
        . '<button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-paper-plane"></i> '
        . hs_h($t['admin_support_compose_send'] ?? 'Send email') . '</button>'
        . '</form></div>'
        // Thread detail
        . '<div class="hs-support-detail hidden" data-support-detail>'
        . '<button type="button" class="hs-support-back" data-support-back><i class="fa-solid fa-arrow-left"></i> '
        . hs_h($t['admin_support_back_list'] ?? 'Conversations') . '</button>'
        . '<div class="hs-admin-support-meta hidden" data-support-client-meta></div>'
        . '<h3 class="hs-support-detail-subject" data-support-detail-subject></h3>'
        . '<div class="hs-admin-support-actions" data-support-thread-actions></div>'
        . '<div class="hs-support-thread" data-support-thread></div>'
        . '<form class="hs-support-reply" data-support-reply-form>'
        . hs_support_render_editor('reply', $t['admin_support_reply_label'] ?? 'Reply to client', $t['support_editor_reply_placeholder'] ?? '', 'compact')
        . '<label class="hs-field"><span>' . hs_h($t['support_inbox_attach'] ?? 'Screenshots') . '</span>'
        . '<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple data-support-reply-files></label>'
        . '<button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-paper-plane"></i> '
        . hs_h($t['admin_support_reply_btn'] ?? 'Send reply') . '</button></form></div>'
        . '<p class="hs-support-status hidden" data-support-inbox-status></p>'
        . '</div></div></div>'
        . '<script>window.HS_ADMIN_SUPPORT=' . json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script></div>';
}