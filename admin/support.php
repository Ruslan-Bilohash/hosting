<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-support.php';

hs_admin_require();
$admin_active = 'support';
$admin_support_mode = true;

$page_title = $t['admin_support_title'] ?? 'Support inbox';
$ready = false;
$renderError = '';
try {
    $ready = hs_ecosystem_messages_ready();
} catch (Throwable $e) {
    $renderError = $e->getMessage();
}

ob_start();
?>
<div class="hs-admin-page hs-admin-support-page">
  <?php if ($renderError !== ''): ?>
    <div class="hs-alert hs-alert-error">
      <?= hs_h($t['admin_support_boot_error'] ?? 'Support module error') ?>:
      <code><?= hs_h($renderError) ?></code>
    </div>
  <?php elseif (!$ready): ?>
    <div class="hs-alert hs-alert-error">
      <?= hs_h($t['support_module_missing'] ?? 'Messaging module is not available on this server.') ?>
      <p class="hp-muted" style="margin:.75rem 0 0;font-size:.85rem">
        <?= hs_h($t['admin_support_module_hint'] ?? 'Messaging backend is not loaded. Contact ops if this persists.') ?>
      </p>
      <p style="margin:.75rem 0 0">
        <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('tools.php')) ?>"><i class="fa-solid fa-screwdriver-wrench"></i> <?= hs_h($t['admin_tools_title'] ?? 'API & tools') ?></a>
        <a class="hs-btn hs-btn-primary hp-dash-btn-sm" href="mailto:support@solaskinner.com"><i class="fa-solid fa-envelope"></i> support@solaskinner.com</a>
      </p>
    </div>
  <?php else: ?>
    <?php
    try {
        echo hs_render_admin_support_panel($t, $lang);
    } catch (Throwable $e) {
        echo '<div class="hs-alert hs-alert-error">' . hs_h($e->getMessage()) . '</div>';
    }
    ?>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
