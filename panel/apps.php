<?php
declare(strict_types=1);

/**
 * App installer (safe URL — some hosts/WAFs block paths containing "installer").
 */
$panel_active = 'installer';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/installer.php';
require_once dirname(__DIR__) . '/includes/ecosystem-catalog.php';

$page_title = $t['installer_title'] ?? 'App installer';
$panel_tip_key = 'installer';

$apps = hs_installable_apps();
$planetBlurbs = function_exists('bh_ecosystem_planet_blurbs') ? bh_ecosystem_planet_blurbs() : [];
$paths = hs_install_ui_paths($user);
// Ensure default_subfolder key for form
if (!isset($paths['default_subfolder'])) {
    $paths['default_subfolder'] = '';
}
$error = '';
$success = '';
$setupHint = '';
$installedApp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } else {
        $app = (string) ($_POST['app'] ?? 'empty');
        $defaultMode = hs_is_ecosystem_demo_app($app) ? 'root' : 'folder';
        $mode = strtolower(trim((string) ($_POST['install_mode'] ?? $defaultMode))) === 'folder' ? 'folder' : 'root';
        $slug = (string) ($_POST['slug'] ?? '');
        if ($mode === 'root' && trim($slug) === '') {
            $slug = $app !== '' ? $app : 'site';
        }
        $cleanDemo = !isset($_POST['clean_demo']) || (string) $_POST['clean_demo'] === '1';
        $res = hs_install_site(
            $user,
            $slug,
            (string) ($_POST['title'] ?? ''),
            $app,
            (string) ($_POST['install_base'] ?? ''),
            $mode,
            ['clean_demo' => $cleanDemo]
        );
        if (!empty($res['ok'])) {
            $pathQ = !empty($res['path_label'])
                ? '&path=' . rawurlencode((string) $res['path_label'])
                : '';
            // PRG → sites list with success (no “deleted” confusion, no resubmit)
            hs_redirect(hs_panel_path('websites.php') . '?tab=overview&installed=1' . $pathQ);
        }
        $error = match ($res['error'] ?? '') {
            'limit' => $t['installer_error_limit'] ?? 'Site limit reached — delete a website first.',
            'slug_taken', 'path_exists' => $t['installer_error_slug'] ?? 'Path already in use — choose another folder or free the site root',
            'package_missing' => $t['installer_error_package'] ?? 'App package is not available on this server yet. Contact support.',
            'deploy', 'mkdir' => $t['installer_error_deploy'] ?? 'Could not copy application files.',
            default => (string) ($res['error'] ?? 'Error'),
        };
    }
}

// Mini plans: always allow opening the form (install replaces sole site silently)
$canInstall = true;
$willReplace = !hs_user_can_add_site($user);
$showAllApps = hs_user_hosting_active($user) || !empty($hs_plan['ecosystem_apps']);

$orderedApps = [];
foreach (hs_ecosystem_demo_app_slugs() as $slug) {
    if (isset($apps[$slug])) {
        $orderedApps[$slug] = $apps[$slug];
    }
}
foreach ($apps as $slug => $meta) {
    if (!isset($orderedApps[$slug])) {
        $orderedApps[$slug] = $meta;
    }
}

$formAction = hs_url(hs_panel_path('apps.php'));

ob_start();
?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<?php if ($willReplace): ?>
  <div class="hs-alert" style="margin-bottom:1rem">
    <i class="fa-solid fa-circle-info"></i>
    <?= hs_h($t['installer_will_replace_site'] ?? $t['installer_will_replace_welcome'] ?? 'Your plan allows 1 website — installing will replace the current site.') ?>
  </div>
<?php endif; ?>

<form method="post" class="hp-card hs-installer-card" id="hs-installer-form" action="<?= hs_h($formAction) ?>">
  <h2 class="hp-card-title"><i class="fa-solid fa-globe" aria-hidden="true"></i> <?= hs_h($t['installer_title'] ?? 'Install application') ?></h2>
  <div class="hp-card-body">
    <?= hs_csrf_field() ?>
    <input type="hidden" name="install_site" value="1">
    <p class="hp-muted" style="margin-top:0">
      <?= hs_h($t['installer_planets_lead'] ?? 'Pick a BILOHASH ecosystem planet — we install a clean demo into your site root (or a folder).') ?>
    </p>

    <div class="hs-field">
      <label><?= hs_h($t['installer_pick'] ?? 'Choose app') ?></label>
      <div class="hs-app-grid hs-app-grid--planets">
        <?php
        $first = true;
        foreach ($orderedApps as $slug => $appMeta):
            $available = hs_app_package_available($slug);
            $locked = !$showAllApps && !in_array($slug, ['empty', 'php'], true);
            $iconClass = !empty($appMeta['icon_brand']) ? 'fa-brands' : 'fa-solid';
            $isPlanet = hs_is_ecosystem_demo_app($slug);
            $planetName = (string) ($planetBlurbs[$slug]['planet'] ?? '');
            $demoUrl = function_exists('hs_app_demo_url') ? hs_app_demo_url($slug) : '';
            $color = (string) ($appMeta['color'] ?? '#059669');
        ?>
        <label class="hs-app-tile<?= $isPlanet ? ' hs-app-tile--planet' : '' ?><?= $locked || !$available ? ' hs-app-tile-muted' : '' ?>"
          style="<?= $isPlanet ? '--hs-app-tone:' . hs_h($color) : '' ?>">
          <input type="radio" name="app" value="<?= hs_h($slug) ?>"
            <?= $first && !$locked && $available ? 'checked' : '' ?>
            <?= $locked || !$available ? 'disabled' : '' ?>>
          <i class="<?= hs_h($iconClass) ?> fa-<?= hs_h($appMeta['icon'] ?? 'cube') ?>"></i>
          <span><?= hs_h($appMeta['short'] ?? $slug) ?></span>
          <?php if ($planetName !== ''): ?>
            <em class="hs-app-planet-name"><?= hs_h($planetName) ?></em>
          <?php endif; ?>
          <?php if ($locked): ?><small class="hp-muted"><?= hs_h($t['installer_need_plan'] ?? 'Plan') ?></small><?php endif; ?>
          <?php if (!$locked && !$available): ?><small class="hp-muted"><?= hs_h($t['installer_package_soon'] ?? '…') ?></small><?php endif; ?>
          <?php if ($demoUrl !== '' && $isPlanet): ?>
            <a class="hs-app-demo-link" href="<?= hs_h($demoUrl) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation(); event.preventDefault(); window.open(this.href,'_blank');">
              <?= hs_h($t['installer_preview_demo'] ?? 'Preview') ?>
            </a>
          <?php endif; ?>
        </label>
        <?php
            if (!$locked && $available) {
                $first = false;
            }
        endforeach;
        ?>
      </div>
    </div>

    <div class="hs-field">
      <label class="hs-check" style="display:flex;align-items:flex-start;gap:.5rem;cursor:pointer">
        <input type="checkbox" name="clean_demo" value="1" checked>
        <span>
          <strong><?= hs_h($t['installer_clean_demo'] ?? 'Clean demo install') ?></strong><br>
          <span class="hp-muted" style="font-size:.85rem"><?= hs_h($t['installer_clean_demo_hint'] ?? 'Fresh package without production data.') ?></span>
        </span>
      </label>
    </div>

    <div class="hs-field">
      <label><?= hs_h($t['installer_mode_label'] ?? 'Install location') ?></label>
      <div class="hs-install-mode" style="display:flex;flex-direction:column;gap:.5rem;margin-top:.35rem">
        <label class="hs-check" style="display:flex;align-items:flex-start;gap:.5rem;cursor:pointer">
          <input type="radio" name="install_mode" value="root" checked data-hs-install-mode>
          <span>
            <strong><?= hs_h($t['installer_mode_root'] ?? 'In site root (no subfolder)') ?></strong><br>
            <span class="hp-muted" style="font-size:.85rem"><?= hs_h($t['installer_mode_root_hint'] ?? 'Best for one clean demo on your plan.') ?></span>
          </span>
        </label>
        <label class="hs-check" style="display:flex;align-items:flex-start;gap:.5rem;cursor:pointer">
          <input type="radio" name="install_mode" value="folder" data-hs-install-mode>
          <span>
            <strong><?= hs_h($t['installer_mode_folder'] ?? 'In a folder') ?></strong><br>
            <span class="hp-muted" style="font-size:.85rem"><?= hs_h($t['installer_mode_folder_hint'] ?? 'public_html/{you}/my-app/') ?></span>
          </span>
        </label>
      </div>
    </div>

    <div class="hs-field">
      <label><?= hs_h($t['installer_path'] ?? 'Install path') ?></label>
      <input type="hidden" name="install_base" value="<?= hs_h($paths['default_base']) ?>">
      <div class="hs-path-input">
        <span class="hs-path-prefix" id="hs-install-prefix"><?= hs_h($paths['prefix_label']) ?></span>
        <input type="text" id="slug" name="slug" required pattern="[a-z0-9][a-z0-9-]*" placeholder="my-shop" value="site" data-root-ph="auto">
      </div>
      <p class="hs-path-preview" id="hs-install-preview"><code><span data-path-full>public_html/<?= hs_h($paths['default_base']) ?>/</span></code></p>
    </div>
    <div class="hs-field">
      <label for="title"><?= hs_h($t['installer_title_field'] ?? 'Site title') ?></label>
      <input type="text" id="title" name="title" placeholder="<?= hs_h($t['installer_title_ph'] ?? 'My demo site') ?>">
    </div>
  </div>
  <div class="hp-card-foot">
    <button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-download"></i> <?= hs_h($t['installer_submit'] ?? 'Install clean demo') ?></button>
    <a href="<?= hs_h(hs_url(hs_panel_path('websites.php'))) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_websites'] ?? 'Websites') ?></a>
  </div>
</form>
<style>
.hs-app-grid--planets { display:grid; grid-template-columns:repeat(auto-fill,minmax(7.5rem,1fr)); gap:.65rem; }
.hs-app-tile--planet { border-color: color-mix(in srgb, var(--hs-app-tone,#059669) 45%, var(--hs-border)); position:relative; }
.hs-app-tile--planet:has(input:checked) {
  border-color: var(--hs-app-tone,#059669);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--hs-app-tone,#059669) 35%, transparent);
}
.hs-app-tile--planet > i { color: var(--hs-app-tone,#059669); }
.hs-app-planet-name { display:block; font-size:.68rem; font-style:normal; color:var(--hs-muted); font-weight:600; }
.hs-app-demo-link { display:inline-block; margin-top:.25rem; font-size:.72rem; font-weight:600; color:var(--hs-accent); }
.hs-installer-card { max-width: 920px; }
</style>
<script>
(function () {
  var slug = document.getElementById('slug');
  var preview = document.querySelector('[data-path-full]');
  var prefix = <?= json_encode('public_html/' . ($paths['default_base'] ?? 'user'), JSON_UNESCAPED_UNICODE) ?>;
  function sync() {
    if (!slug || !preview) return;
    var m = document.querySelector('input[name="install_mode"]:checked');
    var root = m && m.value === 'root';
    preview.textContent = root ? (prefix + '/') : (prefix + '/' + (slug.value || '…') + '/');
  }
  if (slug) slug.addEventListener('input', sync);
  document.querySelectorAll('[data-hs-install-mode]').forEach(function (el) { el.addEventListener('change', sync); });
  sync();
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';
