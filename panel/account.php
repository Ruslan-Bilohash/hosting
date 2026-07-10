<?php
declare(strict_types=1);

$panel_active = 'account';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/master-password.php';
require_once dirname(__DIR__) . '/includes/client-identity.php';

$page_title = $t['account_title'] ?? 'Account';
$panel_tip_key = 'account';

$error = '';
$success = '';
$generatedPass = '';
$userId = (string) ($user['id'] ?? '');
$masterPass = hs_master_password_plain($userId);
$services = hs_master_password_services($t);
$displayName = hs_client_display_name($user);
$initial = mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8');
$planId = (string) ($user['plan'] ?? 'starter');
$planLabel = $t['plan_' . $planId] ?? $planId;
$folderRel = 'public_html/' . preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) . '/';
$clientNumber = hs_client_number($user);
$supportEmail = hs_client_support_email($user);

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
            $masterPass = (string) ($_POST['new_pass'] ?? '');
            $user = hs_client_user() ?? $user;
        } else {
            $error = hs_master_password_error_message((string) ($res['error'] ?? ''), $t);
        }
    } elseif (isset($_POST['generate_master_pass'])) {
        $res = hs_master_password_generate($user, (string) ($_POST['current_pass_gen'] ?? ''));
        if ($res['ok']) {
            $generatedPass = (string) ($res['password'] ?? '');
            $masterPass = $generatedPass;
            $success = $t['account_master_pass_generated'] ?? 'New password generated for all services.';
            $user = hs_client_user() ?? $user;
        } else {
            $error = hs_master_password_error_message((string) ($res['error'] ?? ''), $t);
        }
    }
}

$passVisible = $generatedPass !== '' ? $generatedPass : $masterPass;
$hasPass = $passVisible !== '';

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<div class="hs-account-page" data-hs-account>
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
          <div class="hs-account-field-value"><i class="fa-solid fa-user"></i> <?= hs_h($user['username'] ?? '') ?></div>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_plan'] ?? 'Plan') ?></span>
          <div class="hs-account-field-value">
            <strong><?= hs_h($planLabel) ?></strong>
            <span class="hp-muted">— <?= hs_h(hs_format_plan_price($planId, $lang)) ?><?= hs_h($t['per_month'] ?? '') ?></span>
          </div>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['account_folder'] ?? 'Site folder') ?></span>
          <div class="hs-account-field-value hs-account-mono"><i class="fa-solid fa-folder-open"></i> <?= hs_h($folderRel) ?></div>
        </div>
        <div class="hs-account-field">
          <span class="hs-account-field-label"><?= hs_h($t['domains_primary'] ?? 'Domain') ?></span>
          <div class="hs-account-field-value"><?= hs_h((string) ($hs_user_settings['primary_domain'] ?? '—')) ?></div>
        </div>
      </div>
    </section>

    <section class="hs-account-card hs-account-master">
      <div class="hs-account-master-head">
        <div>
          <h2 class="hs-account-card-title"><i class="fa-solid fa-shield-halved"></i> <?= hs_h($t['account_master_pass_title'] ?? 'Main password') ?></h2>
          <p class="hs-account-master-desc"><?= hs_h($t['account_master_pass_desc'] ?? 'One password for panel login, FTP and SSH.') ?></p>
        </div>
        <?php if ($hasPass): ?>
        <div class="hs-account-pass-pill" id="account-pass-pill">
          <code id="account-pass-value"><?= hs_h($passVisible) ?></code>
          <button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-account-pass-toggle
            data-secret="<?= hs_h($passVisible) ?>"
            data-label-show="<?= hs_h($t['account_master_pass_show'] ?? 'Show') ?>"
            data-label-hide="<?= hs_h($t['account_master_pass_hide'] ?? 'Hide') ?>">
            <?= hs_h($t['account_master_pass_hide'] ?? 'Hide') ?>
          </button>
          <button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-secret="account-pass-value"
            data-secret="<?= hs_h($passVisible) ?>"
            data-copied-label="<?= hs_h($t['account_master_pass_copied'] ?? 'Copied') ?>">
            <i class="fa-solid fa-copy"></i>
          </button>
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

  <section class="hs-account-limits">
    <h2 class="hs-account-card-title"><i class="fa-solid fa-gauge-high"></i> <?= hs_h($t['nav_plan_details'] ?? 'Plan limits') ?></h2>
    <div class="hs-account-limits-grid">
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_websites_limit'] ?? 'Sites') ?></span>
        <strong><?= hs_h((string) ($hs_plan['sites'] ?? 1)) ?></strong>
      </div>
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_storage_limit'] ?? 'Storage') ?></span>
        <strong><?= hs_h(hs_plan_storage_label($hs_plan, $t)) ?></strong>
      </div>
      <div class="hs-account-limit">
        <span><?= hs_h($t['plan_ram'] ?? 'RAM') ?></span>
        <strong><?= hs_h((string) ($hs_plan['ram_mb'] ?? '')) ?> <?= hs_h($t['plan_unit_mb'] ?? 'MB') ?></strong>
      </div>
      <div class="hs-account-limit">
        <span><?= hs_h($t['domains_primary'] ?? 'Domain') ?></span>
        <strong><?= hs_h((string) ($hs_user_settings['primary_domain'] ?? '—')) ?></strong>
      </div>
    </div>
  </section>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';