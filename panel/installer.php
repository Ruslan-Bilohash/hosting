<?php
declare(strict_types=1);

$panel_active = 'installer';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/installer.php';

$page_title = $t['installer_title'] ?? 'App installer';
$panel_tip_key = 'installer';

$apps = hs_installable_apps();
$paths = hs_install_ui_paths($user);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } elseif (!hs_user_can_add_site($user)) {
        $error = $t['installer_error_limit'] ?? '';
    } else {
        $res = hs_install_site(
            $user,
            (string) ($_POST['slug'] ?? ''),
            (string) ($_POST['title'] ?? ''),
            (string) ($_POST['app'] ?? 'empty'),
            (string) ($_POST['install_base'] ?? '')
        );
        if ($res['ok']) {
            $pathLabel = (string) ($res['path_label'] ?? '');
            $success = ($t['installer_success'] ?? '') . ($pathLabel !== '' ? ' (' . $pathLabel . ')' : '');
            $hs_sites = hs_sites_for_user((string) $user['id']);
        } else {
            $error = match ($res['error'] ?? '') {
                'limit' => $t['installer_error_limit'] ?? '',
                'slug_taken', 'path_exists' => $t['installer_error_slug'] ?? '',
                default => $res['error'] ?? 'Error',
            };
        }
    }
}

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<?php if (!hs_user_can_add_site($user)): ?>
  <p class="hp-muted"><?= hs_h($t['installer_error_limit'] ?? '') ?></p>
  <a href="<?= hs_h(hs_url(hs_panel_path('plan.php'))) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['btn_change_plan'] ?? '') ?></a>
<?php else: ?>
<form method="post" class="hp-card" style="max-width:640px">
  <h2 class="hp-card-title"><?= hs_h($t['installer_title'] ?? '') ?></h2>
  <div class="hp-card-body">
    <?= hs_csrf_field() ?>
    <div class="hs-field">
      <label><?= hs_h($t['installer_pick'] ?? '') ?></label>
      <div class="hs-app-grid">
        <?php $first = true; foreach ($apps as $slug => $app):
          $isStarter = empty($hs_plan['ecosystem_apps']);
          if ($isStarter && !in_array($slug, ['empty', 'php'], true)) {
              continue;
          }
        ?>
        <?php $iconClass = !empty($app['icon_brand']) ? 'fa-brands' : 'fa-solid'; ?>
        <label class="hs-app-tile">
          <input type="radio" name="app" value="<?= hs_h($slug) ?>" <?= $first ? 'checked' : '' ?>>
          <i class="<?= hs_h($iconClass) ?> fa-<?= hs_h($app['icon'] ?? 'cube') ?>"></i>
          <span><?= hs_h($app['short'] ?? $slug) ?></span>
        </label>
        <?php $first = false; endforeach; ?>
      </div>
    </div>
    <div class="hs-field">
      <label><?= hs_h($t['installer_path'] ?? 'Install path') ?></label>
      <?php if ($paths['locked']): ?>
        <input type="hidden" name="install_base" value="<?= hs_h($paths['default_base']) ?>">
        <div class="hs-path-input">
          <span class="hs-path-prefix" id="hs-install-prefix"><?= hs_h($paths['prefix_label']) ?></span>
          <input type="text" id="slug" name="slug" required pattern="[a-z0-9][a-z0-9-]*" placeholder="my-shop" aria-describedby="hs-install-preview">
        </div>
        <p class="hp-muted hs-path-hint"><?= hs_h($t['installer_demo_path'] ?? '') ?></p>
      <?php else: ?>
        <div class="hs-path-input hs-path-input-full">
          <span class="hs-path-prefix">public_html/</span>
          <input type="text" name="install_base" value="<?= hs_h($paths['default_base']) ?>" pattern="[a-z0-9][a-z0-9/_-]*" placeholder="<?= hs_h($paths['default_base']) ?>" aria-label="<?= hs_h($t['installer_base'] ?? 'Base folder') ?>">
          <span class="hs-path-sep">/</span>
          <input type="text" id="slug" name="slug" required pattern="[a-z0-9][a-z0-9-]*" placeholder="my-shop" aria-describedby="hs-install-preview">
        </div>
        <p class="hp-muted hs-path-hint"><?= hs_h($t['installer_path_hint'] ?? '') ?></p>
      <?php endif; ?>
      <p class="hs-path-preview" id="hs-install-preview"><code><span data-path-full><?= hs_h($paths['prefix_label']) ?>my-shop</span></code></p>
    </div>
    <div class="hs-field">
      <label for="title"><?= hs_h($t['installer_title_field'] ?? '') ?></label>
      <input type="text" id="title" name="title" placeholder="My Shop">
    </div>
  </div>
  <div class="hp-card-foot">
    <button type="submit" class="hs-btn hs-btn-primary"><?= hs_h($t['installer_submit'] ?? '') ?></button>
  </div>
</form>
<?php endif; ?>
<script>
(function () {
  var slug = document.getElementById('slug');
  var preview = document.querySelector('[data-path-full]');
  var base = document.querySelector('input[name="install_base"]');
  var prefix = document.getElementById('hs-install-prefix');
  var defaultBase = '<?= hs_h($paths['default_base']) ?>';
  if (!slug || !preview) return;
  function upd() {
    var s = slug.value || 'my-shop';
    var b = base ? (base.value || '').replace(/^\/+|\/+$/g, '') : defaultBase;
    if (!b) b = defaultBase;
    var label = 'public_html/' + b + '/' + s;
    preview.textContent = label;
    if (prefix) prefix.textContent = 'public_html/' + b + '/';
  }
  slug.addEventListener('input', upd);
  if (base) base.addEventListener('input', upd);
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';