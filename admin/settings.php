<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/platform-settings.php';

$admin_active = 'settings';
hs_admin_require();

$success = '';
$error = '';
$prelaunch = hs_platform_prelaunch_enabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'Invalid request.';
    } elseif (isset($_POST['save_prelaunch'])) {
        $enabled = ($_POST['prelaunch'] ?? '') === '1';
        if (hs_platform_set_prelaunch($enabled)) {
            $prelaunch = $enabled;
            $success = $enabled
                ? ($t['admin_prelaunch_saved_on'] ?? 'Pre-launch mode enabled.')
                : ($t['admin_prelaunch_saved_off'] ?? 'Site is live — search engines can index.');
        } else {
            $error = $t['admin_prelaunch_save_fail'] ?? 'Could not save settings.';
        }
    }
}

$page_title = $t['admin_settings_title'] ?? 'Site settings';
ob_start();
?>
<div class="hs-admin-page">
  <h1 style="margin:0 0 1rem"><?= hs_h($page_title) ?></h1>

  <?php if ($success !== ''): ?>
    <div class="hs-alert hs-alert-success" style="margin-bottom:1rem"><?= hs_h($success) ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="hs-alert hs-alert-error" style="margin-bottom:1rem"><?= hs_h($error) ?></div>
  <?php endif; ?>

  <section class="hp-card" style="max-width:42rem">
    <h2 class="hp-card-title"><i class="fa-solid fa-screwdriver-wrench"></i> <?= hs_h($t['admin_prelaunch_title'] ?? 'Pre-launch mode') ?></h2>
    <div class="hp-card-body">
      <p class="hp-muted" style="margin-top:0"><?= hs_h($t['admin_prelaunch_desc'] ?? '') ?></p>
      <p style="margin:1rem 0">
        <strong><?= hs_h($t['admin_prelaunch_status_label'] ?? 'Status') ?>:</strong>
        <?php if ($prelaunch): ?>
          <span class="hs-dom-status hs-dom-status-pending_registration"><?= hs_h($t['admin_prelaunch_status_on'] ?? 'On — banner visible, noindex') ?></span>
        <?php else: ?>
          <span class="hs-dom-status hs-dom-status-active"><?= hs_h($t['admin_prelaunch_status_off'] ?? 'Off — site indexed') ?></span>
        <?php endif; ?>
      </p>
      <form method="post" action="">
        <?= hs_csrf_field() ?>
        <input type="hidden" name="save_prelaunch" value="1">
        <input type="hidden" name="prelaunch" value="<?= $prelaunch ? '0' : '1' ?>">
        <button type="submit" class="hs-btn <?= $prelaunch ? 'hs-btn-primary' : 'hs-btn-ghost' ?>">
          <i class="fa-solid <?= $prelaunch ? 'fa-rocket' : 'fa-eye-slash' ?>"></i>
          <?= hs_h($prelaunch
              ? ($t['admin_prelaunch_btn_off'] ?? 'Launch site (allow indexing)')
              : ($t['admin_prelaunch_btn_on'] ?? 'Enable pre-launch banner')) ?>
        </button>
      </form>
      <ul class="hp-muted" style="margin:1.25rem 0 0;padding-left:1.1rem;font-size:.85rem;line-height:1.55">
        <li><?= hs_h($t['admin_prelaunch_effect_banner'] ?? 'Shows “Site under development” banner on all pages.') ?></li>
        <li><?= hs_h($t['admin_prelaunch_effect_robots'] ?? 'Sets robots meta, X-Robots-Tag and robots.txt.') ?></li>
        <li><?= hs_h($t['admin_prelaunch_effect_sitemap'] ?? 'Sitemap is served only when pre-launch is off.') ?></li>
      </ul>
    </div>
  </section>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';