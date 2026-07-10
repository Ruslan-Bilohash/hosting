<?php
declare(strict_types=1);

$panel_active = 'php';
$panel_php_mode = true;
$panel_hide_tip = true;
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/php-config.php';

$page_title = $t['php_title'] ?? 'PHP Configuration';
$panel_tip_key = 'php';

$error = '';
$success = '';
$userId = (string) $user['id'];
$username = (string) ($user['username'] ?? 'user');
$s = $hs_user_settings;
$tab = hs_php_tab_id($_GET['tab'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } elseif (isset($_POST['php_sync'])) {
        hs_php_sync_from_server($userId, $username);
        $s = hs_user_settings_get($userId);
        $hs_user_settings = $s;
        $success = $t['php_synced'] ?? 'Synced from server';
        if (function_exists('hs_panel_log')) {
            require_once dirname(__DIR__) . '/includes/panel-features.php';
            hs_panel_log($userId, 'php_settings', 'sync');
        }
    } elseif (isset($_POST['php_preset'])) {
        $preset = (string) ($_POST['php_preset'] ?? 'default');
        if (!isset(hs_php_presets()[$preset])) {
            $preset = 'default';
        }
        $patch = hs_php_preset_patch($preset);
        if (hs_user_settings_save($userId, $patch)) {
            $s = hs_user_settings_get($userId);
            hs_apply_php_ini($username, $s);
            hs_php_write_probe($username, $userId);
            $hs_user_settings = $s;
            $success = $t['php_preset_applied'] ?? ($t['php_options_saved'] ?? 'Saved');
            if (function_exists('hs_panel_log')) {
                require_once dirname(__DIR__) . '/includes/panel-features.php';
                hs_panel_log($userId, 'php_settings', 'preset:' . $preset);
            }
        }
    } elseif (isset($_POST['php_ext_preset'])) {
        $preset = (string) ($_POST['php_ext_preset'] ?? '');
        if (isset(hs_php_extension_presets()[$preset])) {
            $patch = hs_php_extension_preset_patch($preset);
            if (hs_user_settings_save($userId, $patch)) {
                $s = hs_user_settings_get($userId);
                hs_apply_php_ini($username, $s);
                hs_php_write_probe($username, $userId);
                $hs_user_settings = $s;
                $success = $t['php_ext_preset_applied'] ?? ($t['php_extensions_saved'] ?? 'Saved');
            }
        }
    } elseif (isset($_POST['save_php_version'])) {
        $patch = hs_php_patch_from_post($_POST, 'version');
        if (hs_user_settings_save($userId, $patch)) {
            $s = hs_user_settings_get($userId);
            hs_apply_php_ini($username, $s);
            hs_php_write_probe($username, $userId);
            $hs_user_settings = $s;
            $success = $t['php_version_saved'] ?? ($t['php_saved'] ?? 'Saved');
        }
    } elseif (isset($_POST['save_php_extensions'])) {
        $patch = hs_php_patch_from_post($_POST, 'extensions');
        if (hs_user_settings_save($userId, $patch)) {
            $s = hs_user_settings_get($userId);
            hs_apply_php_ini($username, $s);
            hs_php_write_probe($username, $userId);
            $hs_user_settings = $s;
            $success = $t['php_extensions_saved'] ?? ($t['php_saved'] ?? 'Saved');
        }
    } elseif (isset($_POST['save_php_options'])) {
        $patch = hs_php_patch_from_post($_POST, 'options');
        if (hs_user_settings_save($userId, $patch)) {
            $s = hs_user_settings_get($userId);
            hs_apply_php_ini($username, $s);
            hs_php_write_probe($username, $userId);
            $hs_user_settings = $s;
            $success = $t['php_options_saved'] ?? ($t['php_saved'] ?? 'Saved');
        }
    }
}

$panelLive = hs_php_live_directives();
$siteLive = hs_php_fetch_site_live($username, $userId);
$userIniPath = hs_php_user_ini_path($username);
$userIniExists = is_file($userIniPath);
$userIniPreview = $userIniExists ? (file_get_contents($userIniPath) ?: '') : hs_php_build_user_ini($s);
$phpPending = hs_php_has_pending_ini($s, is_array($siteLive) ? $siteLive : null, $userIniPath);

$mainForm = match ($tab) {
    'extensions' => hs_php_render_extensions_form($s, is_array($siteLive) ? $siteLive : null, $t),
    'options' => hs_php_render_options_form($s, $t),
    default => hs_php_render_version_form($s, $t),
};

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<div class="hs-php-page">
  <?= hs_php_render_hero($s, $user, is_array($siteLive) ? $siteLive : null, $t, $phpPending) ?>
  <?= hs_php_render_quick_actions($t) ?>
  <?= hs_php_render_tabs($tab, $t) ?>

  <div class="hs-php-layout">
    <div class="hs-php-main">
      <?= hs_php_render_guide($tab, $t) ?>
      <?= $mainForm ?>
      <?= hs_php_render_live_collapsible($panelLive, is_array($siteLive) ? $siteLive : null, $s, $userIniPath, $t) ?>
    </div>
    <aside class="hs-php-aside">
      <?= hs_php_render_paths_card($username, $userIniPreview, $userIniExists, $t) ?>
      <section class="hp-card hs-php-help-card">
        <h2 class="hp-card-title"><i class="fa-solid fa-lightbulb"></i> <?= hs_h($t['php_help_title'] ?? 'Tips') ?></h2>
        <div class="hp-card-body">
          <ul class="hs-php-tips">
            <?php for ($i = 1; $i <= 3; $i++):
                $tipKey = 'php_tip_' . $i;
                if (empty($t[$tipKey])) {
                    continue;
                }
            ?>
              <li><?= hs_h($t[$tipKey]) ?></li>
            <?php endfor; ?>
          </ul>
          <p class="hp-muted" style="margin:.75rem 0 0;font-size:.85rem">
            <a href="https://www.php.net/manual/en/configuration.file.php" target="_blank" rel="noopener"><?= hs_h($t['php_doc_link'] ?? 'PHP php.ini docs') ?> <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
          </p>
        </div>
      </section>
      <?= hs_php_render_faq($t) ?>
    </aside>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';