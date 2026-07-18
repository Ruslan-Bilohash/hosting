<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem-bridge.php';
require_once __DIR__ . '/support-ai.php';
require_once __DIR__ . '/client-identity.php';

function hs_support_panel_url(): string
{
    return hs_absolute_url(hs_panel_path('support.php'));
}

function hs_support_ecosystem_admin_url(string $lang): string
{
    $ecoLang = $lang === 'uk' ? 'ua' : $lang;
    $url = 'https://bilohash.com/ecosystem/admin-messages.php';
    if ($ecoLang !== 'en') {
        $url .= '?lang=' . rawurlencode($ecoLang);
    }
    return $url;
}

/** @return list<array{id:string,label:string,category:string,subject:string,body:string}> */
function hs_support_message_templates(array $t): array
{
    return [
        [
            'id' => 'site_down',
            'label' => $t['support_tpl_site_down'] ?? 'Site not loading',
            'category' => 'support',
            'subject' => $t['support_tpl_site_down_subj'] ?? 'Site is not accessible',
            'body' => $t['support_tpl_site_down_body'] ?? "My website does not open in the browser.\n\nURL:\nTime noticed:\nError message (if any):",
        ],
        [
            'id' => 'ssl_dns',
            'label' => $t['support_tpl_ssl'] ?? 'SSL / DNS issue',
            'category' => 'support',
            'subject' => $t['support_tpl_ssl_subj'] ?? 'SSL or DNS configuration',
            'body' => $t['support_tpl_ssl_body'] ?? "I have a problem with SSL certificate or DNS records.\n\nDomain:\nWhat I tried:",
        ],
        [
            'id' => 'billing',
            'label' => $t['support_tpl_billing'] ?? 'Billing / plan',
            'category' => 'billing',
            'subject' => $t['support_tpl_billing_subj'] ?? 'Question about hosting plan',
            'body' => $t['support_tpl_billing_body'] ?? "I have a question about my hosting plan or invoice.\n\nDetails:",
        ],
        [
            'id' => 'migration',
            'label' => $t['support_tpl_migration'] ?? 'Site migration',
            'category' => 'feature',
            'subject' => $t['support_tpl_migration_subj'] ?? 'Help with site migration',
            'body' => $t['support_tpl_migration_body'] ?? "I need help migrating a site to BILOHASH hosting.\n\nCurrent URL:\nCMS (WordPress/other):\nSize estimate:",
        ],
        [
            'id' => 'wordpress',
            'label' => $t['support_tpl_wp'] ?? 'WordPress issue',
            'category' => 'bug',
            'subject' => $t['support_tpl_wp_subj'] ?? 'WordPress installation or plugin issue',
            'body' => $t['support_tpl_wp_body'] ?? "WordPress issue on my hosting account.\n\nSite folder:\nProblem:\nSteps to reproduce:",
        ],
        [
            'id' => 'feature',
            'label' => $t['support_tpl_feature'] ?? 'Feature request',
            'category' => 'feature',
            'subject' => $t['support_tpl_feature_subj'] ?? 'Feature suggestion',
            'body' => $t['support_tpl_feature_body'] ?? "I would like to suggest a feature for the hosting panel.\n\nIdea:\nWhy it helps:",
        ],
    ];
}

/** @return list<array{id:string,label:string,icon:string,provider:string,category:string,draft:string}> */
function hs_support_ai_agents(array $t): array
{
    return [
        [
            'id' => 'technical',
            'label' => $t['support_agent_technical'] ?? 'Technical expert',
            'icon' => 'fa-screwdriver-wrench',
            'provider' => 'grok',
            'category' => 'support',
            'draft' => $t['support_agent_technical_draft'] ?? 'Site error, slow loading or server configuration issue.',
        ],
        [
            'id' => 'billing',
            'label' => $t['support_agent_billing'] ?? 'Billing assistant',
            'icon' => 'fa-credit-card',
            'provider' => 'openai',
            'category' => 'billing',
            'draft' => $t['support_agent_billing_draft'] ?? 'Question about plan limits, renewal or invoice.',
        ],
        [
            'id' => 'migration',
            'label' => $t['support_agent_migration'] ?? 'Migration consultant',
            'icon' => 'fa-truck',
            'provider' => 'openai',
            'category' => 'feature',
            'draft' => $t['support_agent_migration_draft'] ?? 'Need to move an existing website to this hosting.',
        ],
        [
            'id' => 'wordpress',
            'label' => $t['support_agent_wp'] ?? 'WordPress specialist',
            'icon' => 'fa-wordpress',
            'provider' => 'grok',
            'category' => 'bug',
            'draft' => $t['support_agent_wp_draft'] ?? 'WordPress install, update or BILOHASH plugin issue.',
        ],
    ];
}

function hs_support_client_display_name(array $user): string
{
    $name = trim((string) ($user['name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return (string) ($user['username'] ?? 'user');
}

function hs_support_is_demo_client(array $user): bool
{
    return (string) ($user['username'] ?? '') === 'demo';
}

function hs_support_client_plan_label(array $user, array $t): string
{
    $planId = (string) ($user['plan'] ?? 'starter');
    if ($planId === 'pro') {
        $planId = 'business';
    }

    return (string) ($t['plan_hosting_' . $planId] ?? $t['plan_' . $planId] ?? $planId);
}

/** @return array{username:string,name:string,email:string,support_email:string,client_number:string,plan:string,plan_label:string,account_type:string,is_demo:bool,folder:string} */
function hs_support_client_context(array $user, array $t): array
{
    $username = (string) ($user['username'] ?? 'user');
    $profile = is_array($user['profile'] ?? null) ? $user['profile'] : [];

    return [
        'username' => $username,
        'name' => hs_support_client_display_name($user),
        'email' => (string) ($user['email'] ?? ''),
        'support_email' => hs_client_support_email($user),
        'client_number' => hs_client_number($user),
        'plan' => (string) ($user['plan'] ?? 'starter'),
        'plan_label' => hs_support_client_plan_label($user, $t),
        'account_type' => (string) ($profile['account_type'] ?? $user['account_type'] ?? 'personal'),
        'is_demo' => hs_support_is_demo_client($user),
        'folder' => 'public_html/' . $username . '/',
    ];
}

function hs_support_sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    $allowed = '<p><br><b><strong><i><em><u><a><ul><ol><li>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
    $clean = preg_replace('/\s(href|src)\s*=\s*"\s*javascript:[^"]*"/i', '', $clean) ?? $clean;

    return trim($clean);
}

function hs_support_body_has_content(string $body): bool
{
    $body = hs_support_sanitize_html($body);
    if ($body === '') {
        return false;
    }
    $text = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    return $text !== '';
}

function hs_support_render_editor(string $id, string $label, string $placeholder = '', string $size = 'default'): string
{
    $cls = 'hs-support-editor';
    if ($size === 'compact') {
        $cls .= ' hs-support-editor-compact';
    } elseif ($size === 'draft') {
        $cls .= ' hs-support-editor-draft';
    }

    return '<div class="hs-field hs-support-editor-field">'
        . '<span class="hs-support-editor-label">' . hs_h($label) . '</span>'
        . '<div class="' . $cls . '" data-support-editor="' . hs_h($id) . '" data-placeholder="' . hs_h($placeholder) . '">'
        . '<div class="hs-support-quill-mount" data-support-quill-body></div>'
        . '<textarea class="hs-support-fallback-ta" data-support-fallback rows="'
        . ($size === 'compact' ? '4' : ($size === 'draft' ? '3' : '8'))
        . '" placeholder="' . hs_h($placeholder) . '"></textarea>'
        . '</div></div>';
}

/** @return array<string, string> */
function hs_support_panel_strings(string $lang): array
{
    $panelLang = dirname(__DIR__) . '/lang/panel-' . $lang . '.php';
    if (!is_file($panelLang)) {
        $panelLang = dirname(__DIR__) . '/lang/panel-en.php';
    }

    return is_file($panelLang) ? (require $panelLang) : [];
}

/** @return array{ok:bool,id?:string,error?:string} */
function hs_support_send_owner_message(array $user, string $lang, string $subject, string $body, string $category, string $siteSlug, string $fromEmail): array
{
    if (!hs_ecosystem_messages_ready()) {
        return ['ok' => false, 'error' => 'module_missing'];
    }
    $username = (string) ($user['username'] ?? 'user');
    $siteSlug = preg_replace('/[^a-z0-9_-]/i', '', $siteSlug);
    $siteLabel = $siteSlug !== '' ? $username . '/' . $siteSlug : $username;
    $panelUrl = hs_support_panel_url();
    $pt = hs_support_panel_strings($lang);
    $ctx = hs_support_client_context($user, $pt);
    $clientKind = $ctx['is_demo']
        ? ($pt['support_client_demo'] ?? 'Demo client')
        : ($pt['support_client_live'] ?? 'Live client');
    $acctLabel = ($ctx['account_type'] ?? '') === 'business'
        ? ($pt['support_account_business'] ?? 'Business')
        : ($pt['support_account_personal'] ?? 'Personal');
    $body = hs_support_sanitize_html($body);
    $contactEmail = hs_client_support_email($user);
    $fromEmail = $contactEmail;
    $meta = "Hosting client: {$username} ({$clientKind})\n";
    if (($ctx['client_number'] ?? '') !== '') {
        $meta .= 'Client ID: ' . $ctx['client_number'] . "\n";
    }
    $meta .= 'Support mailbox: ' . $contactEmail . "\n";
    $meta .= 'Login email: ' . $ctx['email'] . ' · ' . $ctx['name'] . "\n";
    $meta .= 'Plan: ' . $ctx['plan_label'] . "\n";
    $meta .= 'Account: ' . $acctLabel . "\n";
    if ($siteSlug !== '') {
        $meta .= "Site: public_html/{$siteLabel}/\n";
    } else {
        $meta .= 'Site root: ' . $ctx['folder'] . "\n";
    }
    $meta .= "Panel: {$panelUrl}\n\n";
    $fullBody = $meta . $body;
    $fullSubject = '[Hosting/' . $siteLabel . '] ' . trim($subject);

    $id = ecosystem_owner_messages_add([
        'subject' => $fullSubject,
        'body' => $fullBody,
        'category' => $category,
        'from_user' => $username,
        'from_name' => hs_support_client_display_name($user),
        'from_role' => 'Hosting client',
        'from_email' => $contactEmail,
        'shop_url' => $panelUrl,
        'lang' => $lang,
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ]);
    if ($id === null) {
        return ['ok' => false, 'error' => 'save_failed'];
    }
    return ['ok' => true, 'id' => $id];
}

function hs_support_json_response(array $data, int $code = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** @param list<array<string,mixed>> $sites */
function hs_render_support_panel(array $user, array $sites, array $t, string $lang): string
{
    $GLOBALS['panel_support_mode'] = true;
    $templates = hs_support_message_templates($t);
    $agents = hs_support_ai_agents($t);
    $client = hs_support_client_context($user, $t);
    $username = $client['username'];
    $siteOptions = '';
    if ($sites === []) {
        $siteOptions = '<option value="" selected>' . hs_h($client['folder']) . '</option>';
    } else {
        foreach ($sites as $i => $si) {
            $slug = (string) ($si['slug'] ?? '');
            $path = 'public_html/' . $username . '/' . $slug . '/';
            $siteOptions .= '<option value="' . hs_h($slug) . '"' . ($i === 0 ? ' selected' : '') . '>'
                . hs_h((string) ($si['title'] ?? $slug)) . ' — ' . hs_h($path) . '</option>';
        }
    }
    $clientBadge = $client['is_demo']
        ? ($t['support_client_demo'] ?? 'Demo client')
        : ($t['support_client_live'] ?? 'Live client');
    $clientBadgeCls = $client['is_demo'] ? 'is-demo' : 'is-live';
    $acctLabel = $client['account_type'] === 'business'
        ? ($t['support_account_business'] ?? 'Business')
        : ($t['support_account_personal'] ?? 'Personal');
    $tplOptions = '';
    foreach ($templates as $tpl) {
        $tplOptions .= '<option value="' . hs_h($tpl['id']) . '">' . hs_h($tpl['label']) . '</option>';
    }
    $agentBtns = '';
    foreach ($agents as $ag) {
        $agentBtns .= '<button type="button" class="hs-support-agent" data-agent="' . hs_h($ag['id']) . '" data-category="' . hs_h($ag['category']) . '" data-draft="' . hs_h($ag['draft']) . '" data-provider="' . hs_h($ag['provider']) . '">'
            . '<i class="fa-solid ' . hs_h($ag['icon']) . '"></i> ' . hs_h($ag['label']) . '</button>';
    }
    $ecoUrl = hs_support_ecosystem_admin_url($lang);
    $userEmail = hs_client_support_email($user);
    $loginEmail = (string) ($user['email'] ?? '');
    $clientNumber = hs_client_number($user);

    $cfg = [
        'lang' => $lang,
        'apiOwner' => hs_url(hs_panel_path('support-owner-message.php')),
        'apiMessages' => hs_url(hs_panel_path('support-messages.php')),
        'apiMessageFile' => hs_url(hs_panel_path('support-message-file.php')),
        'apiAi' => hs_url(hs_panel_path('support-ai-compose.php')),
        'templates' => $templates,
        'i18n' => [
            'sent_ok' => $t['support_sent_ok'] ?? 'Message sent.',
            'sent_error' => $t['support_sent_error'] ?? 'Could not send.',
            'ai_error' => $t['support_ai_error'] ?? 'AI error.',
            'inbox_sent_ok' => $t['support_inbox_sent_ok'] ?? 'Reply sent.',
            'inbox_sent_error' => $t['support_inbox_sent_error'] ?? 'Reply failed.',
            'inbox_author_owner' => $t['support_inbox_owner'] ?? 'BILOHASH support',
            'inbox_author_you' => $t['support_inbox_you'] ?? 'You',
            'inbox_empty' => $t['support_inbox_empty'] ?? 'No conversations yet.',
            'inbox_select' => $t['support_inbox_select'] ?? 'Select a conversation.',
            'inbox_unread' => $t['support_inbox_unread'] ?? 'New reply',
            'thinking' => $t['support_ai_thinking'] ?? 'Thinking…',
            'ai_compose' => $t['support_ai_compose'] ?? 'Improve with AI',
            'module_missing' => $t['support_module_missing'] ?? 'Messaging module unavailable',
            'inbox_error' => $t['support_inbox_load_error'] ?? 'Could not load conversations',
            'back_list' => $t['support_back_list'] ?? 'Conversations',
            'new_cta' => $t['support_new_cta'] ?? 'Write first message',
            'loading' => $t['support_loading'] ?? 'Loading…',
            'fill_required' => $t['support_fill_required'] ?? 'Subject and message are required.',
            'editor_placeholder' => $t['support_editor_placeholder'] ?? 'Write your message…',
            'editor_reply_placeholder' => $t['support_editor_reply_placeholder'] ?? 'Your reply…',
            'editor_draft_placeholder' => $t['support_draft_ph'] ?? 'Draft / notes…',
            'link_prompt' => $t['support_editor_link_prompt'] ?? 'Enter link URL:',
        ],
        'client' => $client,
    ];

    return '<div id="hs-support" class="hs-support">'
        . '<div class="hs-support-hero">'
        . '<p class="hp-muted hs-support-desc">' . hs_h($t['support_desc'] ?? '') . '</p>'
        . '<a href="' . hs_h($ecoUrl) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost hs-btn-sm"><i class="fa-solid fa-inbox"></i> '
        . hs_h($t['support_eco_inbox'] ?? 'Ecosystem inbox') . '</a></div>'
        . '<div class="hs-support-tabs" role="tablist">'
        . '<button type="button" class="hs-support-tab is-active" data-support-tab="inbox"><i class="fa-solid fa-inbox"></i> '
        . hs_h($t['support_tab_inbox'] ?? 'Conversations') . '<span class="hs-support-badge hidden" data-support-unread></span></button>'
        . '<button type="button" class="hs-support-tab" data-support-tab="new"><i class="fa-solid fa-paper-plane"></i> '
        . hs_h($t['support_tab_new'] ?? 'New message') . '</button></div>'
        . '<div class="hs-support-panel" data-support-panel="inbox">'
        . '<p class="hp-muted">' . hs_h($t['support_inbox_hint'] ?? '') . '</p>'
        . '<div class="hs-support-inbox"><aside class="hs-support-list-wrap" data-support-list-wrap>'
        . '<p class="hs-support-loading hp-muted hidden" data-support-list-loading><i class="fa-solid fa-spinner fa-spin"></i> '
        . hs_h($t['support_loading'] ?? 'Loading…') . '</p>'
        . '<div class="hs-support-list" data-support-list></div>'
        . '<div class="hs-support-empty hidden" data-support-list-empty>'
        . '<p class="hp-muted">' . hs_h($t['support_inbox_empty'] ?? '') . '</p>'
        . '<button type="button" class="hs-btn hs-btn-primary hs-btn-sm" data-support-go-new><i class="fa-solid fa-paper-plane"></i> '
        . hs_h($t['support_new_cta'] ?? 'Write first message') . '</button></div></aside>'
        . '<div class="hs-support-detail-wrap" data-support-detail-wrap>'
        . '<div class="hs-support-detail-empty" data-support-detail-empty><p class="hp-muted">' . hs_h($t['support_inbox_select'] ?? '') . '</p></div>'
        . '<div class="hs-support-detail hidden" data-support-detail>'
        . '<button type="button" class="hs-support-back" data-support-back><i class="fa-solid fa-arrow-left"></i> '
        . hs_h($t['support_back_list'] ?? 'Conversations') . '</button>'
        . '<h3 class="hs-support-detail-subject" data-support-detail-subject></h3><div class="hs-support-thread" data-support-thread></div>'
        . '<form class="hs-support-reply" data-support-reply-form>'
        . hs_support_render_editor('reply', $t['support_inbox_reply'] ?? 'Reply', $t['support_editor_reply_placeholder'] ?? '', 'compact')
        . '<label class="hs-field"><span>' . hs_h($t['support_inbox_attach'] ?? 'Screenshots') . '</span>'
        . '<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple data-support-reply-files></label>'
        . '<button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-paper-plane"></i> '
        . hs_h($t['support_inbox_send'] ?? 'Send') . '</button></form>'
        . '<p class="hs-support-status hidden" data-support-inbox-status></p></div></div></div></div>'
        . '<div class="hs-support-panel hidden" data-support-panel="new">'
        . '<p class="hp-muted">' . hs_h($t['support_new_hint'] ?? '') . '</p>'
        . '<div class="hs-support-client-card">'
        . '<div class="hs-support-client-icon"><i class="fa-solid fa-user-circle"></i></div>'
        . '<div class="hs-support-client-meta">'
        . '<div class="hs-support-client-head">'
        . '<strong class="hs-support-client-login">' . hs_h($username) . '</strong>'
        . '<span class="hs-support-client-badge ' . $clientBadgeCls . '">' . hs_h($clientBadge) . '</span></div>'
        . ($clientNumber !== '' ? '<div class="hs-support-client-line"><span class="hs-support-client-id">'
        . hs_h($t['support_client_id'] ?? 'Client ID') . ': <strong>' . hs_h($clientNumber) . '</strong></span></div>' : '')
        . '<div class="hs-support-client-line">' . hs_h($client['name']) . '</div>'
        . '<div class="hs-support-client-line hs-support-client-mailbox"><i class="fa-solid fa-envelope"></i> '
        . '<strong>' . hs_h($userEmail) . '</strong></div>'
        . ($loginEmail !== '' && strtolower($loginEmail) !== strtolower($userEmail)
            ? '<div class="hs-support-client-line hp-muted">' . hs_h($t['support_login_email'] ?? 'Login email') . ': ' . hs_h($loginEmail) . '</div>'
            : '')
        . '<div class="hs-support-client-line">'
        . hs_h($t['support_client_plan'] ?? 'Plan') . ': <strong>' . hs_h($client['plan_label']) . '</strong>'
        . ' · ' . hs_h($acctLabel) . '</div>'
        . '<div class="hs-support-client-path"><i class="fa-solid fa-folder-open"></i> ' . hs_h($client['folder']) . '</div>'
        . '</div></div>'
        . '<div class="hp-grid-2"><div class="hs-field"><label>' . hs_h($t['support_site_label'] ?? 'Client site') . '</label>'
        . '<select data-support-site required>' . $siteOptions . '</select>'
        . '<span class="hs-field-hint">' . hs_h($t['support_site_hint'] ?? '') . '</span></div>'
        . '<div class="hs-field"><label>' . hs_h($t['support_category'] ?? 'Category') . '</label>'
        . '<select data-support-category><option value="support">' . hs_h($t['support_cat_support'] ?? 'Support') . '</option>'
        . '<option value="bug">' . hs_h($t['support_cat_bug'] ?? 'Bug') . '</option>'
        . '<option value="billing">' . hs_h($t['support_cat_billing'] ?? 'Billing') . '</option>'
        . '<option value="feature">' . hs_h($t['support_cat_feature'] ?? 'Feature') . '</option>'
        . '<option value="other">' . hs_h($t['support_cat_other'] ?? 'Other') . '</option></select></div></div>'
        . '<div class="hs-field"><label>' . hs_h($t['support_template'] ?? 'Template') . '</label>'
        . '<select data-support-template><option value="">' . hs_h($t['support_template_none'] ?? '— Choose template —') . '</option>' . $tplOptions . '</select></div>'
        . '<div class="hs-field"><label>' . hs_h($t['support_your_email'] ?? 'Your support mailbox') . '</label>'
        . '<input type="email" data-support-email value="' . hs_h($userEmail) . '" readonly class="hs-support-email-locked" required>'
        . '<span class="hs-field-hint">' . hs_h($t['support_email_hint'] ?? 'Personal mailbox for this account — replies go here.') . '</span></div>'
        . hs_support_render_editor('draft', $t['support_draft'] ?? 'Draft / notes', $t['support_draft_ph'] ?? '', 'draft')
        . '<div class="hs-support-agents"><span class="hs-support-agents-label">' . hs_h($t['support_agents_label'] ?? 'AI agents') . '</span>' . $agentBtns . '</div>'
        . '<div class="hs-support-ai-row"><button type="button" class="hs-btn hs-btn-primary" data-support-ai-btn><i class="fa-solid fa-wand-magic-sparkles"></i> '
        . hs_h($t['support_ai_compose'] ?? 'Improve with AI') . '</button></div>'
        . '<div class="hs-field"><label>' . hs_h($t['support_subject'] ?? 'Subject') . '</label><input type="text" data-support-subject required></div>'
        . hs_support_render_editor('body', $t['support_message'] ?? 'Message', $t['support_editor_placeholder'] ?? '')
        . '<button type="button" class="hs-btn hs-btn-primary" data-support-send><i class="fa-solid fa-paper-plane"></i> '
        . hs_h($t['support_send'] ?? 'Send to BILOHASH') . '</button>'
        . '<p class="hs-support-status hidden" data-support-new-status></p></div>'
        . '<script>window.HS_SUPPORT=' . json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script></div>';
}