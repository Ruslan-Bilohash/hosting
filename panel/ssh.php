<?php
declare(strict_types=1);

/**
 * Secure remote access for the client site folder.
 * Shared multi-tenant: real per-client FTP jail (same as Account).
 * Interactive shell SSH is not available per client without dedicated WHM cPanel —
 * we expose FTPS + SFTP credentials that match the working FTP account.
 */
$panel_active = 'adv-ssh';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/plan-specs.php';
require_once dirname(__DIR__) . '/includes/master-password.php';
require_once dirname(__DIR__) . '/includes/client-ftp-onboard.php';

$page_title = $t['tab_adv_ssh'] ?? 'SFTP / secure access';
$panel_tip_key = 'advanced';

$error = '';
$success = '';
$userId = (string) ($user['id'] ?? '');

// Ensure jailed FTP account exists (same as working FTP)
$ftp = hs_client_ftp_credentials($user);
if (empty($ftp['ok']) && $hs_hosting_active) {
    $retry = hs_client_ftp_ensure($user, true);
    if (!empty($retry['ok'])) {
        $ftp = hs_client_ftp_credentials($user);
        $success = $t['ssh_ftp_provisioned'] ?? 'Your secure file account was created.';
    } else {
        $error = ($t['ssh_ftp_provision_fail'] ?? 'Could not create secure file account.')
            . (!empty($retry['error']) ? ' ' . (string) $retry['error'] : '');
    }
}

$login = (string) ($ftp['login'] ?? hs_client_ftp_login($user));
$host = (string) ($ftp['host'] ?? ('ftp.' . hs_default_primary_domain()));
$hostIp = (string) ($ftp['host_ip'] ?? (function_exists('hs_server_ip') ? hs_server_ip() : ''));
$ftpPort = (int) ($ftp['port'] ?? 21);
// Prefer standard OpenSSH SFTP port for clients; cPanel shell port is main-account only
$sftpPort = 22;
$cpanelSshPort = defined('HS_SSH_PORT') ? (int) HS_SSH_PORT : 21098;
$homedir = (string) ($ftp['homedir'] ?? '');
$hasPass = hs_master_password_has_stored($userId);

$folderDisplay = function_exists('hs_ftp_account_path')
    ? rtrim(hs_ftp_account_path((string) ($user['username'] ?? 'user'), $user), '/') . '/'
    : $homedir;

// Commands clients can copy
$sftpCmd = 'sftp -P ' . $sftpPort . ' ' . $login . '@' . $host;
$ftpUrl = 'ftp://' . $login . '@' . $host . '/';
$dedicated = function_exists('hs_whm_enabled') && hs_whm_enabled()
    && function_exists('hs_cpanel_account_for_user')
    && hs_cpanel_account_for_user($userId) !== null;

$passBlock = $hasPass
    ? hs_master_password_secret_ui('ssh-pass-value', $t, null, ['class' => 'hs-ssh-pass-row'])
    : '<p class="hp-muted hs-ssh-pass-empty">' . hs_h($t['ssh_pass_empty'] ?? 'Set a main password under Account first.') . '</p>';

$copyBtn = static function (string $id) use ($t): string {
    return ' <button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="' . hs_h($id) . '"'
        . ' data-copied-label="' . hs_h($t['ssh_pass_copied'] ?? 'Copied') . '"><i class="fa-solid fa-copy"></i></button>';
};

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<div class="hs-ssh-page">
  <aside class="hs-ssh-guide">
    <h3 class="hs-ssh-guide-title"><i class="fa-solid fa-book-open"></i> <?= hs_h($t['ssh_guide_title'] ?? 'How to connect') ?></h3>
    <ol class="hs-ssh-guide-list">
      <li><span class="hs-ssh-guide-num">1</span><?= hs_h($t['ssh_guide_1'] ?? 'Install FileZilla or WinSCP.') ?></li>
      <li><span class="hs-ssh-guide-num">2</span><?= hs_h($t['ssh_guide_2'] ?? 'Protocol: FTP (or FTPS). Host, user and password from this page.') ?></li>
      <li><span class="hs-ssh-guide-num">3</span><?= hs_h($t['ssh_guide_3'] ?? 'You land in your site folder only (jail) — no other clients.') ?></li>
      <li><span class="hs-ssh-guide-num">4</span><?= hs_h($t['ssh_guide_4'] ?? 'Password = main password from Account (same as panel).') ?></li>
      <li><span class="hs-ssh-guide-num">5</span><?= hs_h($t['ssh_guide_5'] ?? 'Prefer File Manager in the panel if you only need a browser.') ?></li>
    </ol>
    <p class="hp-muted" style="margin-top:1rem;font-size:.85rem">
      <?= hs_h($t['ssh_shell_note'] ?? 'Interactive shell SSH (bash) is only for dedicated cPanel accounts (WHM pool). On shared hosting use FTP/FTPS or SFTP file access below.') ?>
    </p>
  </aside>

  <?php
    $rows = [
        [
            $t['ssh_host'] ?? 'Host',
            '<code id="ssh-host">' . hs_h($host) . '</code>' . $copyBtn('ssh-host')
            . ($hostIp !== '' ? ' <span class="hp-muted">IP <code id="ssh-ip">' . hs_h($hostIp) . '</code>' . $copyBtn('ssh-ip') . '</span>' : ''),
        ],
        [
            $t['account_ftp_user'] ?? 'Username',
            '<code id="ssh-user">' . hs_h($login) . '</code>' . $copyBtn('ssh-user'),
        ],
        [
            $t['ssh_ftp_port'] ?? 'FTP port',
            '<code id="ssh-ftp-port">' . hs_h((string) $ftpPort) . '</code>' . $copyBtn('ssh-ftp-port'),
        ],
        [
            $t['ssh_sftp_port'] ?? 'SFTP port (if supported)',
            '<code id="ssh-sftp-port">' . hs_h((string) $sftpPort) . '</code>' . $copyBtn('ssh-sftp-port')
            . ' <span class="hp-muted">' . hs_h($t['ssh_sftp_port_hint'] ?? 'If SFTP fails, use FTP/FTPS on port 21 — same login.') . '</span>',
        ],
        [
            $t['account_folder'] ?? 'Site folder',
            '<code id="ssh-folder">' . hs_h($folderDisplay) . '</code>' . $copyBtn('ssh-folder'),
        ],
        [
            $t['account_ftp_path'] ?? 'Path after login',
            '<code id="ssh-jail-path">/</code> '
            . '<span class="hp-muted">' . hs_h($t['ssh_jail_hint'] ?? 'Jail root = your site. Server path:') . ' <code>' . hs_h($homedir) . '</code></span>',
        ],
        [
            $t['ssh_status'] ?? 'Status',
            !empty($ftp['ok'])
                ? '<span class="hp-status-ok">' . hs_h($t['ssh_status_active'] ?? 'FTP account ACTIVE') . '</span>'
                : '<span class="hp-status-off">' . hs_h($t['ssh_status_off'] ?? 'Not provisioned') . '</span>',
        ],
    ];

    echo hs_render_card(
        $t['ssh_data_title'] ?? 'Your secure file access',
        '<p class="hp-muted">' . hs_h($t['ssh_pass_hint'] ?? 'Same credentials as FTP on Account — jailed to your domain folder only.') . '</p>'
        . hs_render_kv_table($rows)
        . '<div class="hs-field" style="margin-top:1rem"><label>' . hs_h($t['ssh_password'] ?? 'Password (main password)') . '</label>' . $passBlock . '</div>'
        . '<div class="hs-ssh-actions" style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem">'
        . '<a href="' . hs_h(hs_url(hs_panel_path('account.php'))) . '" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-key"></i> ' . hs_h($t['account_manage_pass'] ?? 'Change main password') . '</a>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('files.php'))) . '" class="hs-btn hs-btn-ghost">'
        . '<i class="fa-solid fa-folder-open"></i> ' . hs_h($t['account_link_files'] ?? 'File Manager') . '</a>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('files.php'), ['tab' => 'ftp'])) . '" class="hs-btn hs-btn-ghost">'
        . '<i class="fa-solid fa-network-wired"></i> ' . hs_h($t['account_link_ftp'] ?? 'FTP details') . '</a>'
        . '</div>'
    );

    echo hs_render_card(
        $t['ssh_login_title'] ?? 'Connect with FileZilla / WinSCP',
        '<p class="hp-muted">' . hs_h($t['ssh_login_hint'] ?? 'Use FTP or FTPS first (proven). SFTP uses the same user if your client supports it.') . '</p>'
        . '<p class="hp-muted" style="margin:.5rem 0"><strong>FTP / FTPS</strong></p>'
        . '<div class="hp-ssh-cmd"><code id="ssh-ftp-url">' . hs_h($ftpUrl) . '</code>'
        . $copyBtn('ssh-ftp-url') . '</div>'
        . '<p class="hp-muted" style="margin:1rem 0 .5rem"><strong>SFTP</strong> (optional)</p>'
        . '<div class="hp-ssh-cmd"><code id="ssh-cmd">' . hs_h($sftpCmd) . '</code>'
        . $copyBtn('ssh-cmd') . '</div>'
        . '<p class="hp-muted" style="margin-top:1rem">' . hs_h($t['ssh_desc'] ?? '') . '</p>'
        . ($dedicated
            ? '<p class="hp-muted" style="margin-top:.75rem"><i class="fa-solid fa-circle-check"></i> '
                . hs_h($t['ssh_dedicated_note'] ?? 'Dedicated cPanel: shell SSH may also be available for your account.') . '</p>'
            : '<p class="hp-muted" style="margin-top:.75rem"><i class="fa-solid fa-circle-info"></i> '
                . hs_h($t['ssh_shared_note'] ?? 'Shared hosting: no interactive bash SSH per client. Use FTP/FTPS/SFTP into your jail or File Manager.') . '</p>')
    );
  ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';
