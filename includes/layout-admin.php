<?php
declare(strict_types=1);
/** @var array $t */
/** @var string $lang */
/** @var array $lang_meta */
/** @var string|null $admin_nav_active */

require_once __DIR__ . '/admin-nav.php';

$page_title = $page_title ?? ($t['admin_title'] ?? HS_SITE_NAME);
// Support both $admin_nav_active and legacy $admin_active from older pages
$admin_nav_active = $admin_nav_active ?? ($admin_active ?? 'dashboard');
$robots = $robots ?? 'noindex,nofollow';
$lang_meta = $lang_meta ?? (hs_langs()[$lang ?? 'en'] ?? ['html' => 'en']);
$brandName = (string) ($t['brand'] ?? 'SolaSkinner');
?>
<!DOCTYPE html>
<html lang="<?= hs_h($lang_meta['html'] ?? 'en') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?= hs_h($robots) ?>">
<meta name="theme-color" content="#059669">
<title><?= hs_h($page_title) ?> · Admin</title>
<?php
if (is_file(__DIR__ . '/brand-mark.php')) {
    require_once __DIR__ . '/brand-mark.php';
}
if (function_exists('hs_render_favicon_links')) {
    echo hs_render_favicon_links();
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="<?= hs_h(hs_asset('css/app.css')) ?>">
<?php if (!empty($admin_support_mode)): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" crossorigin="anonymous">
<?php endif; ?>
</head>
<body class="hs-panel hp-panel hs-admin-shell<?= !empty($admin_support_mode) ? ' hs-support-page' : '' ?><?= !empty($admin_fm_mode) ? ' hs-fm-page' : '' ?>">
<div class="hs-overlay" data-hs-overlay></div>
<?= hs_admin_render_sidebar($t, (string) $admin_nav_active) ?>

<div class="hs-main hp-main hs-admin-main-wrap">
  <header class="hp-topbar hs-admin-topbar">
    <button type="button" class="hs-burger" data-hs-burger aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <div class="hp-topbar-title">
      <strong><?= hs_h($page_title) ?></strong>
      <span class="hp-muted"><?= hs_h($brandName) ?> · solaskinner.com</span>
    </div>
    <div class="hp-topbar-actions hs-admin-topbar-actions">
      <?= hs_render_lang_dropdown($lang ?? 'en') ?>
      <a href="<?= hs_h(hs_url(hs_panel_path(''))) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm">
        <i class="fa-solid fa-table-cells-large"></i> <?= hs_h($t['nav_panel'] ?? 'Panel') ?>
      </a>
      <a href="<?= hs_h(hs_admin_url('logout.php')) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm">
        <i class="fa-solid fa-right-from-bracket"></i> <?= hs_h($t['admin_logout'] ?? 'Logout') ?>
      </a>
    </div>
  </header>

  <div class="hs-admin-main">
    <?= $content ?? '' ?>
  </div>

  <footer class="hs-admin-footer">
    <a href="<?= hs_h(hs_admin_url('logout.php')) ?>"><?= hs_h($t['admin_logout'] ?? 'Logout') ?></a>
    ·
    <a href="<?= hs_h(hs_url()) ?>"><?= hs_h($t['breadcrumb_home'] ?? 'Home') ?></a>
    ·
    <span class="hp-muted"><?= hs_h(function_exists('hs_version_label') ? hs_version_label() : '') ?></span>
  </footer>
</div>

<script src="<?= hs_h(hs_asset('js/app.js')) ?>" defer></script>
<?php if (!empty($admin_support_mode)): ?>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js" crossorigin="anonymous" defer></script>
<script src="<?= hs_h(hs_asset('js/support.js')) ?>" defer></script>
<?php endif; ?>
<?php if (!empty($admin_fm_mode)): ?>
<script src="<?= hs_h(hs_asset('js/file-manager.js')) ?>" defer></script>
<?php endif; ?>
<?php if (!empty($extra_footer_scripts) && is_array($extra_footer_scripts)): ?>
<?php foreach ($extra_footer_scripts as $scr): ?>
<script src="<?= hs_h(hs_asset((string) $scr)) ?>" defer></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
