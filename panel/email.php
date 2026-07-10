<?php
declare(strict_types=1);

$panel_active = 'email';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';

$page_title = $t['dash_manage_email'] ?? 'Manage email';
$panel_tip_key = 'domains';

$domain = (string) ($hs_user_settings['primary_domain'] ?? hs_default_primary_domain());
$webmailUrl = hs_webmail_url($domain);
$mxLabel = hs_email_mx_label($domain);
$error = '';
$success = '';
$userId = (string) $user['id'];
$mailboxes = is_array($hs_user_settings['mailboxes'] ?? null) ? $hs_user_settings['mailboxes'] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mailbox'])) {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } else {
        $local = strtolower(trim((string) ($_POST['mailbox'] ?? '')));
        if ($local !== '' && preg_match('/^[a-z0-9._-]+$/', $local)) {
            $mailboxes[] = [
                'address' => $local . '@' . $domain,
                'created_at' => gmdate('c'),
            ];
            hs_user_settings_save($userId, ['mailboxes' => $mailboxes]);
            $hs_user_settings = hs_user_settings_get($userId);
            $mailboxes = $hs_user_settings['mailboxes'] ?? [];
            $success = $t['btn_add'] ?? 'Added';
            if (function_exists('hs_panel_log')) {
                require_once dirname(__DIR__) . '/includes/panel-features.php';
                hs_panel_log($userId, 'mailbox_add', $local);
            }
        } else {
            $error = 'Invalid mailbox';
        }
    }
}

$rows = '';
foreach ($mailboxes as $mb) {
    $rows .= '<tr><td><code>' . hs_h((string) ($mb['address'] ?? '')) . '</code></td><td>'
        . hs_h(hs_format_date((string) ($mb['created_at'] ?? ''))) . '</td></tr>';
}
if ($rows === '') {
    $rows = '<tr><td colspan="2" class="hp-muted">—</td></tr>';
}

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
<?= hs_render_card(
    $t['dash_manage_email'] ?? 'Manage email',
    '<p class="hp-muted">' . hs_h($t['email_desc'] ?? '') . '</p>'
    . '<div class="hp-actions" style="margin-bottom:1rem">'
    . '<a href="' . hs_h($webmailUrl) . '" target="_blank" rel="noopener" class="hs-btn hs-btn-primary">'
    . '<i class="fa-solid fa-envelope-open"></i> ' . hs_h($t['email_open_webmail'] ?? 'Open Webmail') . '</a></div>'
    . hs_render_kv_table([
        [$t['domains_primary'] ?? 'Domain', '<strong>' . hs_h($domain) . '</strong>'],
        ['MX', hs_h($mxLabel)],
        ['Webmail', '<a href="' . hs_h($webmailUrl) . '" target="_blank" rel="noopener">' . hs_h(parse_url($webmailUrl, PHP_URL_HOST) ?: $webmailUrl) . '</a>'],
    ])
    . '<div class="hs-table-wrap" style="margin-top:1rem"><table class="hs-table"><thead><tr><th>Mailbox</th><th>Created</th></tr></thead><tbody>'
    . $rows . '</tbody></table></div>'
    . '<form method="post" class="hp-inline-form">' . hs_csrf_field()
    . '<div class="hs-field"><label>Mailbox</label><input type="text" name="mailbox" placeholder="info" pattern="[a-z0-9._-]+" required> @' . hs_h($domain) . '</div>'
    . '<button type="submit" name="add_mailbox" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_add'] ?? 'Add') . '</button></form>',
    '<a href="' . hs_h(hs_url(hs_panel_path('domains.php'))) . '" class="hs-btn hs-btn-ghost">' . hs_h($t['dash_manage_domain'] ?? '') . '</a>'
) ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';