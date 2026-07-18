<?php
declare(strict_types=1);

$panel_active = 'account';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/master-password.php';
require_once dirname(__DIR__) . '/includes/client-identity.php';
require_once dirname(__DIR__) . '/includes/mail-settings.php';
require_once dirname(__DIR__) . '/includes/plan-specs.php';
require_once dirname(__DIR__) . '/includes/client-ftp-onboard.php';

$page_title = $t['account_title'] ?? 'Account';
$panel_tip_key = 'account';

$error = '';
$success = '';
$userId = (string) ($user['id'] ?? '');
$displayName = hs_client_display_name($user);
$initial = mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8');
$planId = hs_plan_normalize_id((string) ($user['plan'] ?? 'starter'));
$plan = hs_plan($planId);
$planLabel = hs_plan_panel_label($planId, $t);
$accountUser = (string) ($user['username'] ?? 'user');
$domain = (string) ($hs_active_domain ?? hs_plan_display_domain($user, $hs_user_settings));
// Real per-client FTP (cPanel jail) + display folder
$ftpCred = hs_client_ftp_credentials($user);
$folderPath = rtrim(hs_ftp_account_path($accountUser, $user), '/') . '/';
$ftpHomePath = (string) ($ftpCred['homedir'] ?? '');
$ftpUser = (string) ($ftpCred['login'] ?? hs_client_ftp_login($user));
$ftpHostName = (string) ($ftpCred['host'] ?? ('ftp.' . hs_default_primary_domain()));
$ftpHost = (string) ($ftpCred['host_ip'] ?? (function_exists('hs_server_ip') ? hs_server_ip() : ''));
$ftpPort = (int) ($ftpCred['port'] ?? 21);
$sftpPort = (int) ($ftpCred['sftp_port'] ?? (defined('HS_SSH_PORT') ? HS_SSH_PORT : 22));
$ftpOk = !empty($ftpCred['ok']);
$folderTilde = $ftpHomePath !== '' ? ('~/' . ltrim($ftpHomePath, '/')) : ('~/' . trim($folderPath, '/'));
$ssh = hs_ssh_client_context($user, $hs_user_settings);
// SFTP uses same login/password as FTP jail (no shell on shared; secure file transfer)
$sharedHosting = empty($ftpOk) || !empty($ssh['shared']);
$clientNumber = hs_client_number($user);
$supportEmail = hs_client_support_email($user);
$mailSettings = hs_mail_service_settings($domain !== '' ? $domain : null, $hs_user_settings);
$filesUrl = hs_url(hs_panel_path('files.php'));
$services = hs_master_password_services($t);
foreach ($services as &$svc) {
    if (($svc['id'] ?? '') === 'ftp') {
        $svc['desc'] = str_replace('{path}', $folderPath, (string) ($t['account_service_ftp_desc'] ?? $svc['desc']));
    } elseif (($svc['id'] ?? '') === 'ssh') {
        $svc['desc'] = str_replace('{folder}', $folderTilde, (string) ($t['account_service_ssh_desc'] ?? $svc['desc']));
    }
}
unset($svc);
$mailboxes = is_array($hs_user_settings['mailboxes'] ?? null) ? $hs_user_settings['mailboxes'] : [];
$mailboxCount = count($mailboxes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['account_master_pass_error_csrf'] ?? ($t['register_error_csrf'] ?? '');
    } elseif (isset($_POST['change_master_pass'])) {
        $res = hs_master_password_change(
            $user,
            (string) ($_POST['current_pass'] ?? ''),
            (string) ($_POST['new_pass'] ?? ''),
            (string) ($_POST['confirm_pass'] ?? '')
        );
        if ($res['ok']) {
            $success = $t['account_master_pass_changed'] ?? 'Password updated for panel, FTP and SSH.';
            $user = hs_client_user() ?? $user;
        } else {
            $error = hs_master_password_error_message((string) ($res['error'] ?? ''), $t);
        }
    } elseif (isset($_POST['generate_master_pass'])) {
        $res = hs_master_password_generate($user, (string) ($_POST['current_pass_gen'] ?? ''));
        if ($res['ok']) {
            $success = $t['account_master_pass_generated'] ?? 'New password generated for all services.';
            $user = hs_client_user() ?? $user;
        } else {
            $error = hs_master_password_error_message((string) ($res['error'] ?? ''), $t);
        }
    }
}

$passFlash = hs_master_password_consume_flash($userId);
$hasPass = hs_master_password_has_stored($userId) || $passFlash !== null;

$incomingTable = hs_mail_render_server_table($mailSettings['incoming'], $t);
$outgoingTable = hs_mail_render_server_table($mailSettings['outgoing'], $t);
$webmailLegacy = $mailSettings['webmail_legacy_url'];
$emailManageUrl = hs_url(hs_panel_path('email.php'));

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<div class="hs-account-page" data-hs-account>
  <p class="hs-alert hs-account-scope"><i class="fa-solid fa-user-shield"></i> <?= hs_h(str_replace(
      ['{path}', '{user}'],
      [$folderPath, $accountUser],
      $t['account_scope_hint'] ?? 'Your website folder: {path}. Panel login is «{user}». Prefer File Manager for uploads.'
  )) ?></p>
  <?php if ($ftpOk): ?>
  <p class="hs-alert hs-alert-success" style="margin-top:.5rem"><i class="fa-solid fa-circle-check"></i>
    <?= hs_h($t['account_ftp_ready_note'] ?? 'Your FTP/SFTP login is ready and jailed to your site folder only.') ?>
  </p>
  <?php elseif (!empty($ftpCred['error'])): ?>
  <p class="hs-alert hs-alert-error" style="margin-top:.5rem"><i class="fa-solid fa-triangle-exclamation"></i>
    <?= hs_h($t['account_ftp_error'] ?? 'Could not create FTP account.') ?>
    <code><?= hs_h((string) $ftpCred['error']) ?></code>
  </p>
  <?php endif; ?>
  <header class="hs-account-hero">
    <div class="hs-account-hero-main">
      <div class="hs-account-avatar" aria-hidden="true"><?= hs_h($initial) ?></div>
      <div>
        <p class="hs-account-hero-kicker"><?= hs_h($t['account_subtitle'] ?? 'Your hosting identity') ?></p>
        <h1 class="hs-account-hero-title"><?= hs_h($displayName) ?></h1>
        <div class="hs-account-hero-meta">
          <span class="hs-account-badge"><i class="fa-solid fa-layer-group"></i> <?= hs_h($planLabel) ?></span>
          <span class="hs-account-badge hs-account-badge-muted"><i class="fa-solid fa-at"></i> <?= hs_h($user['username'] ?? '') ?></span>
          <?php if ($clientNumber !== ''): ?>
          <span class="hs-account-badge hs-account-badge-id"><i class="fa-solid fa-hashtag"></i> <?= hs_h($clientNumber) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <a href="<?= hs_h(hs_url(hs_panel_path('plan.php'))) ?>" class="hs-btn hs-btn-ghost hs-account-plan-link">
      <i class="fa-solid fa-arrow-up-right-from-square"></i> <?= hs_h($t['btn_change_plan'] ?? 'Change plan') ?>
    </a>
  </header>

  <div class="hs-account-grid">
    <section class="hs-account-card hs-account-profile">
      <h2 class="hs-account-card-title"><i class="fa-solid fa-id-card"></i> <?= hs_h($t['account_profile_title'] ?? 'Profile') ?></h2>
      <div class="hs-account-fields">
        <?php if ($clientNumber !== ''): ?>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_client_id'] ?? 'Client ID') ?></span>
          <div class="hs-account-field-value hs-account-mono"><i class="fa-solid fa-hashtag"></i> <strong><?= hs_h($clientNumber) ?></strong></div>
        </div>
        <?php endif; ?>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_email'] ?? 'Email') ?></span>
          <div class="hs-account-field-value"><i class="fa-solid fa-envelope"></i> <?= hs_h($user['email'] ?? '') ?></div>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_support_email'] ?? 'Support mailbox') ?></span>
          <div class="hs-account-field-value"><i class="fa-solid fa-inbox"></i> <strong><?= hs_h($supportEmail) ?></strong></div>
          <p class="hp-muted hs-account-field-hint"><?= hs_h($t['account_support_email_hint'] ?? '') ?></p>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_username'] ?? 'Username') ?></span>
          <div class="hs-account-field-value"><i class="fa-solid fa-user"></i> <?= hs_h($accountUser) ?></div>
          <p class="hp-muted hs-account-field-hint"><?= hs_h($t['account_panel_login_hint'] ?? 'Used to sign in to this control panel.') ?></p>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_plan'] ?? 'Plan') ?></span>
          <div class="hs-account-field-value">
            <strong><?= hs_h($planLabel) ?></strong>
            <span class="hp-muted">— <?= hs_h(hs_format_plan_price($planId, $lang)) ?><?= hs_h($t['per_month'] ?? '') ?></span>
          </div>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['domains_primary'] ?? 'Domain') ?></span>
          <div class="hs-account-field-value"><i class="fa-solid fa-globe"></i> <?= hs_h($domain !== '' ? $domain : '—') ?></div>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_folder'] ?? 'Site folder') ?></span>
          <div class="hs-account-field-value hs-account-mono"><i class="fa-solid fa-folder-open"></i> <code id="account-folder-path"><?= hs_h($folderPath) ?></code></div>
          <p class="hp-muted hs-account-field-hint"><?= hs_h($t['account_folder_hint'] ?? 'Your website files (also the FTP jail).') ?></p>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_ftp_host'] ?? 'FTP / SFTP host') ?></span>
          <div class="hs-account-field-value hs-account-mono">
            <code id="account-ftp-host"><?= hs_h($ftpHostName) ?></code>
            <?php if ($ftpHost !== ''): ?>
            <span class="hp-muted"> · IP <code><?= hs_h($ftpHost) ?></code></span>
            <?php endif; ?>
          </div>
          <p class="hp-muted hs-account-field-hint">
            FTP <?= hs_h((string) $ftpPort) ?>
            · SFTP <?= hs_h((string) $sftpPort) ?>
            · <?= hs_h($t['account_ftp_protocol_hint'] ?? 'Same login for FTP and SFTP (FileZilla protocol SFTP).') ?>
          </p>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_ftp_user'] ?? 'FTP / SFTP username') ?></span>
          <div class="hs-account-field-value hs-account-mono"><i class="fa-solid fa-server"></i> <code id="account-ftp-user"><?= hs_h($ftpUser) ?></code></div>
          <p class="hp-muted hs-account-field-hint"><?= hs_h($t['account_ftp_user_hint'] ?? 'Only your site folder is visible after login.') ?></p>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_ftp_path'] ?? 'Path after login') ?></span>
          <div class="hs-account-field-value hs-account-mono"><code id="account-ftp-path">/</code></div>
          <p class="hp-muted hs-account-field-hint"><?= hs_h($t['account_ftp_path_hint'] ?? 'You land in your site root (jailed). Server path:') ?>
            <code><?= hs_h($ftpHomePath !== '' ? $ftpHomePath : $folderPath) ?></code></p>
        </div>
        <div class="hs-account-access-links hp-actions">
          <a href="<?= hs_h($filesUrl) ?>" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-folder-open"></i> <?= hs_h($t['account_link_files'] ?? 'File Manager') ?></a>
          <a href="<?= hs_h(hs_url(hs_panel_path('files.php'), ['tab' => 'ftp'])) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-network-wired"></i> <?= hs_h($t['account_link_ftp'] ?? 'FTP details') ?></a>
          <a href="<?= hs_h(hs_url(hs_panel_tab_href('domains', 'overview'))) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-globe"></i> <?= hs_h($t['nav_domains'] ?? 'Domains') ?></a>
        </div>
      </div>
    </section>

    <section class="hs-account-card hs-account-master">
      <div class="hs-account-master-head">
        <div>
          <h2 class="hs-account-card-title"><i class="fa-solid fa-shield-halved"></i> <?= hs_h($t['account_master_pass_title'] ?? 'Main password') ?></h2>
          <p class="hs-account-master-desc"><?= hs_h($t['account_master_pass_desc'] ?? 'One password for panel login, FTP and SFTP (your jailed site account).') ?></p>
        </div>
        <?php if ($hasPass): ?>
        <div class="hs-account-pass-pill" id="account-pass-pill">
          <?= hs_master_password_secret_ui('account-pass-value', $t, $passFlash, ['class' => 'hs-account-pass-pill-inner']) ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="hs-service-chips">
        <?php foreach ($services as $svc): ?>
        <article class="hs-service-chip">
          <span class="hs-service-chip-icon"><i class="fa-solid <?= hs_h($svc['icon']) ?>"></i></span>
          <div>
            <strong><?= hs_h($svc['title']) ?></strong>
            <p><?= hs_h($svc['desc']) ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <form method="post" class="hs-account-pass-form" autocomplete="off">
        <?= hs_csrf_field() ?>
        <input type="hidden" name="change_master_pass" value="1">
        <div class="hs-account-form-grid">
          <div class="hs-field">
            <label for="account-current-pass"><?= hs_h($t['account_master_pass_current'] ?? 'Current password') ?></label>
            <div class="hs-account-input-wrap">
              <input type="password" id="account-current-pass" name="current_pass" required autocomplete="current-password">
              <button type="button" class="hs-account-eye" data-account-eye="account-current-pass" aria-label="Toggle"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <div class="hs-field">
            <label for="account-new-pass"><?= hs_h($t['account_master_pass_new'] ?? 'New password') ?></label>
            <div class="hs-account-input-wrap">
              <input type="password" id="account-new-pass" name="new_pass" required minlength="<?= (defined('HS_DEMO_MODE') && HS_DEMO_MODE) ? 4 : 8 ?>" autocomplete="new-password" data-account-strength>
              <button type="button" class="hs-account-eye" data-account-eye="account-new-pass" aria-label="Toggle"><i class="fa-solid fa-eye"></i></button>
            </div>
            <div class="hs-account-strength" data-account-strength-bar hidden>
              <span data-account-strength-fill></span>
            </div>
          </div>
          <div class="hs-field">
            <label for="account-confirm-pass"><?= hs_h($t['account_master_pass_confirm'] ?? 'Confirm password') ?></label>
            <div class="hs-account-input-wrap">
              <input type="password" id="account-confirm-pass" name="confirm_pass" required minlength="<?= (defined('HS_DEMO_MODE') && HS_DEMO_MODE) ? 4 : 8 ?>" autocomplete="new-password" data-account-match>
              <button type="button" class="hs-account-eye" data-account-eye="account-confirm-pass" aria-label="Toggle"><i class="fa-solid fa-eye"></i></button>
            </div>
            <p class="hs-field-hint hs-account-match-hint" data-account-match-hint
              data-ok="<?= hs_h($t['account_master_pass_match_ok'] ?? 'Passwords match') ?>"
              data-bad="<?= hs_h($t['account_master_pass_match_bad'] ?? 'Passwords do not match') ?>" hidden></p>
          </div>
        </div>
        <p class="hp-muted hs-account-pass-hint"><?= hs_h($t['account_master_pass_hint'] ?? '') ?></p>
        <div class="hs-account-form-actions">
          <button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-lock"></i> <?= hs_h($t['account_master_pass_change'] ?? 'Update password') ?></button>
        </div>
      </form>

      <details class="hs-account-generate">
        <summary><i class="fa-solid fa-wand-magic-sparkles"></i> <?= hs_h($t['account_master_pass_generate'] ?? 'Generate strong password') ?></summary>
        <form method="post" class="hs-account-generate-form">
          <?= hs_csrf_field() ?>
          <input type="hidden" name="generate_master_pass" value="1">
          <p class="hp-muted"><?= hs_h($t['account_master_pass_generate_hint'] ?? 'Enter your current password to generate a new one for all services.') ?></p>
          <div class="hs-field">
            <label for="account-current-pass-gen"><?= hs_h($t['account_master_pass_current'] ?? 'Current password') ?></label>
            <div class="hs-account-input-wrap">
              <input type="password" id="account-current-pass-gen" name="current_pass_gen" required autocomplete="current-password">
              <button type="button" class="hs-account-eye" data-account-eye="account-current-pass-gen" aria-label="Toggle"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-arrows-rotate"></i> <?= hs_h($t['account_master_pass_generate_btn'] ?? 'Generate & apply') ?></button>
        </form>
      </details>
    </section>
  </div>

  <section class="hs-account-card hs-account-mail">
    <div class="hs-account-mail-head">
      <div>
        <h2 class="hs-account-card-title"><i class="fa-solid fa-envelope"></i> <?= hs_h($t['account_mail_title'] ?? 'Mail service') ?></h2>
        <p class="hp-muted hs-account-mail-desc"><?= hs_h($t['account_mail_desc'] ?? '') ?></p>
      </div>
      <div class="hp-actions hs-account-mail-actions">
        <a href="<?= hs_h($mailSettings['webmail_url']) ?>" class="hs-btn hs-btn-primary">
          <i class="fa-solid fa-inbox"></i> <?= hs_h($t['webmail_title'] ?? 'Mail') ?>
        </a>
        <?php if (!empty($mailSettings['webmail_roundcube_url'])): ?>
        <a href="<?= hs_h($mailSettings['webmail_roundcube_url']) ?>" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost">
          <i class="fa-solid fa-envelope-open"></i> <?= hs_h($t['email_open_roundcube'] ?? 'Roundcube') ?>
        </a>
        <?php endif; ?>
        <a href="<?= hs_h($emailManageUrl) ?>" class="hs-btn hs-btn-ghost">
          <i class="fa-solid fa-inbox"></i> <?= hs_h($t['account_mail_manage'] ?? 'Manage mailboxes') ?>
        </a>
      </div>
    </div>

    <div class="hs-account-mail-grid">
      <div class="hs-account-mail-block">
        <h3 class="hs-account-mail-subtitle"><?= hs_h($t['account_mail_overview'] ?? 'Overview') ?></h3>
        <?= hs_render_kv_table([
            [$t['domains_primary'] ?? 'Domain', '<strong>' . hs_h($domain) . '</strong>'],
            ['MX', '<code>' . hs_h($mailSettings['mx']) . '</code>'],
            [$t['email_webmail_panel'] ?? 'Panel mail', '<a href="' . hs_h($mailSettings['webmail_url']) . '"><code>' . hs_h($mailSettings['webmail_url']) . '</code></a>'],
            [$t['account_mail_mailboxes'] ?? 'Mailboxes', '<strong>' . hs_h((string) $mailboxCount) . '</strong>'],
        ]) ?>
        <?php if ($webmailLegacy !== null && ($mailSettings['webmail_roundcube_url'] ?? null) !== $webmailLegacy): ?>
        <p class="hp-muted hs-account-mail-legacy">
          <i class="fa-solid fa-circle-info"></i>
          <?= hs_h(str_replace('{url}', $webmailLegacy, $t['email_webmail_legacy_note'] ?? '')) ?>
          <a href="<?= hs_h(hs_url(hs_panel_path('security.php'), ['tab' => 'ssl'])) ?>"><?= hs_h($t['email_webmail_ssl_fix'] ?? '') ?></a>
        </p>
        <?php endif; ?>
      </div>

      <div class="hs-account-mail-block">
        <h3 class="hs-account-mail-subtitle"><i class="fa-solid fa-arrow-down"></i> <?= hs_h($t['account_mail_incoming'] ?? 'Incoming mail') ?></h3>
        <?= $incomingTable ?>
      </div>

      <div class="hs-account-mail-block">
        <h3 class="hs-account-mail-subtitle"><i class="fa-solid fa-arrow-up"></i> <?= hs_h($t['account_mail_outgoing'] ?? 'Outgoing mail (SMTP)') ?></h3>
        <?= $outgoingTable ?>
        <p class="hp-muted hs-account-mail-auth"><i class="fa-solid fa-key"></i> <?= hs_h($t['account_mail_auth_hint'] ?? '') ?></p>
      </div>
    </div>
  </section>

  <section class="hs-account-limits">
    <h2 class="hs-account-card-title"><i class="fa-solid fa-gauge-high"></i> <?= hs_h($t['nav_plan_details'] ?? 'Plan limits') ?></h2>
    <div class="hs-account-limits-grid">
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_websites_limit'] ?? 'Sites') ?></span>
        <strong><?= hs_h(hs_plan_sites_label($plan, $t)) ?></strong>
      </div>
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_storage_limit'] ?? 'Storage') ?></span>
        <strong><?= hs_h(hs_plan_storage_label($plan, $t)) ?></strong>
      </div>
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_databases_limit'] ?? 'Databases') ?></span>
        <strong><?= hs_h((string) (int) ($plan['databases'] ?? hs_user_database_limit($user))) ?></strong>
      </div>
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_ram'] ?? 'RAM') ?></span>
        <strong><?= hs_h((string) (int) ($plan['ram_mb'] ?? 0)) ?> <?= hs_h($t['plan_unit_mb'] ?? 'MB') ?></strong>
      </div>
    </div>
  </section>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';