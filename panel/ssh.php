<?php
declare(strict_types=1);

$panel_active = 'adv-ssh';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/plan-specs.php';

$page_title = $t['tab_adv_ssh'] ?? 'SSH access';
$panel_tip_key = 'advanced';

$error = '';
$success = '';
$userId = (string) $user['id'];
$sshOn = !empty($hs_user_settings['ssh_enabled']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } elseif (isset($_POST['toggle_ssh'])) {
        $new = !$sshOn;
        if (hs_user_settings_save($userId, ['ssh_enabled' => $new])) {
            $hs_user_settings = hs_user_settings_get($userId);
            $sshOn = $new;
            $success = $new ? ($t['ssh_enabled_msg'] ?? '') : ($t['ssh_disabled_msg'] ?? '');
            if (function_exists('hs_panel_log')) {
                require_once dirname(__DIR__) . '/includes/panel-features.php';
                hs_panel_log($userId, 'ssh_toggle', $new ? 'on' : 'off');
            }
        }
    }
}

require_once dirname(__DIR__) . '/includes/master-password.php';
$passValue = hs_master_password_plain($userId);
$hasPass = $passValue !== '';
$cmd = hs_ssh_command();

$passBlock = $hasPass
    ? '<div class="hs-ssh-pass-row">'
        . '<code id="ssh-pass-value" class="hs-ssh-pass-visible">' . hs_h($passValue) . '</code>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-ssh-pass-toggle data-target="ssh-pass-value"'
        . ' data-secret="' . hs_h($passValue) . '" data-label-show="' . hs_h($t['ssh_pass_show'] ?? 'Show') . '"'
        . ' data-label-hide="' . hs_h($t['ssh_pass_hide'] ?? 'Hide') . '">'
        . hs_h($t['ssh_pass_hide'] ?? 'Hide') . '</button>'
        . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-secret="ssh-pass-value" data-secret="' . hs_h($passValue) . '"'
        . ' data-copied-label="' . hs_h($t['ssh_pass_copied'] ?? 'Copied') . '"><i class="fa-solid fa-copy"></i> '
        . hs_h($t['ssh_pass_copy'] ?? 'Copy') . '</button></div>'
    : '<p class="hp-muted hs-ssh-pass-empty">' . hs_h($t['ssh_pass_empty'] ?? '') . '</p>';

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
<div class="hs-ssh-page">
  <aside class="hs-ssh-guide">
    <h3 class="hs-ssh-guide-title"><i class="fa-solid fa-book-open"></i> <?= hs_h($t['ssh_guide_title'] ?? 'How to connect') ?></h3>
    <ol class="hs-ssh-guide-list">
      <?php for ($i = 1; $i <= 5; $i++):
          $key = 'ssh_guide_' . $i;
          if (empty($t[$key])) {
              continue;
          }
      ?>
        <li><span class="hs-ssh-guide-num"><?= $i ?></span><?= hs_h($t[$key]) ?></li>
      <?php endfor; ?>
    </ol>
  </aside>

  <?= hs_render_card(
      $t['ssh_data_title'] ?? 'SSH data',
      '<p class="hp-muted">' . hs_h($t['ssh_pass_hint'] ?? '') . '</p>'
      . hs_render_kv_table([
          ['IP / Host', '<code id="ssh-host">' . hs_h(HS_SSH_HOST) . '</code> <button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="ssh-host" data-copied-label="' . hs_h($t['ssh_pass_copied'] ?? 'Copied') . '"><i class="fa-solid fa-copy"></i></button>'],
          [$t['ssh_port'] ?? 'Port', '<code id="ssh-port">' . hs_h((string) HS_SSH_PORT) . '</code> <button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="ssh-port" data-copied-label="' . hs_h($t['ssh_pass_copied'] ?? 'Copied') . '"><i class="fa-solid fa-copy"></i></button>'],
          [$t['ssh_username'] ?? 'Username', '<code id="ssh-user">' . hs_h(HS_SSH_USER) . '</code> <button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="ssh-user" data-copied-label="' . hs_h($t['ssh_pass_copied'] ?? 'Copied') . '"><i class="fa-solid fa-copy"></i></button>'],
          [$t['ssh_status'] ?? 'SSH status', '<span class="hp-status-' . ($sshOn ? 'ok' : 'off') . '">' . ($sshOn ? ($t['ssh_status_active'] ?? 'ACTIVE') : ($t['ssh_status_off'] ?? 'OFF')) . '</span>'],
      ])
      . '<div class="hs-field" style="margin-top:1rem"><label>' . hs_h($t['ssh_password'] ?? 'Password') . '</label>' . $passBlock . '</div>'
      . '<div class="hs-ssh-copy-all"><button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" id="ssh-copy-all">'
      . '<i class="fa-solid fa-clipboard-list"></i> ' . hs_h($t['ssh_copy_all'] ?? 'Copy all credentials') . '</button></div>',
      '<div class="hs-ssh-actions">'
      . '<form method="post" style="display:inline">' . hs_csrf_field()
      . '<button type="submit" name="toggle_ssh" value="1" class="hs-btn ' . ($sshOn ? 'hs-btn-ghost' : 'hs-btn-primary') . '">'
      . hs_h($sshOn ? ($t['ssh_disable'] ?? '') : ($t['ssh_enable'] ?? '')) . '</button></form>'
      . '<a href="' . hs_h(hs_url(hs_panel_path('account.php'))) . '" class="hs-btn hs-btn-primary">'
      . '<i class="fa-solid fa-key"></i> ' . hs_h($t['account_manage_pass'] ?? 'Change main password') . '</a>'
      . '</div>'
  ) ?>

  <?= hs_render_card(
      $t['ssh_login_title'] ?? 'Log in via SSH',
      '<p class="hp-muted">' . hs_h($t['ssh_login_hint'] ?? '') . '</p>'
      . '<div class="hp-ssh-cmd"><code id="ssh-cmd">' . hs_h($cmd) . '</code>'
      . '<button type="button" class="hs-btn hs-btn-ghost hp-dash-btn-sm" data-copy-target="ssh-cmd" data-copied-label="' . hs_h($t['ssh_pass_copied'] ?? 'Copied') . '">'
      . hs_h($t['ssh_copy'] ?? 'Copy') . '</button></div>'
      . ($hasPass
          ? '<p class="hp-muted" style="margin-top:.75rem"><i class="fa-solid fa-key"></i> ' . hs_h($t['ssh_pass_ready'] ?? '') . '</p>'
          : '<p class="hp-muted" style="margin-top:.75rem"><i class="fa-solid fa-circle-info"></i> ' . hs_h($t['ssh_pass_hpanel'] ?? '') . '</p>')
      . '<p class="hp-muted" style="margin-top:1rem">' . hs_h($t['ssh_desc'] ?? '') . '</p>'
      . '<p class="hp-muted" style="margin-top:.5rem;font-size:.85rem"><a href="https://hpanel.hostinger.com/" target="_blank" rel="noopener">Hostinger hPanel <i class="fa-solid fa-arrow-up-right-from-square"></i></a> · '
      . hs_h($t['ssh_fingerprint_hint'] ?? '') . '</p>'
  ) ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';