<?php
declare(strict_types=1);
/** @var array $t */
/** @var string $lang */
/** @var array $lang_meta */
$page_title = $page_title ?? ($t['meta_title'] ?? HS_SITE_NAME);
?>
<!DOCTYPE html>
<html lang="<?= hs_h($lang_meta['html'] ?? 'en') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index,follow">
<meta name="theme-color" content="#059669">
<title><?= hs_h($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="<?= hs_h(hs_asset('css/app.css')) ?>">
</head>
<body>
<div class="hs-demo-banner" role="note">
  <i class="fa-solid fa-flask"></i>
  <span><?= hs_h($t['demo_banner'] ?? 'Demo build — test version of the BILOHASH hosting panel.') ?></span>
</div>
<header class="hs-public-header">
  <a href="<?= hs_h(hs_url()) ?>" class="hs-logo">
    <span class="hs-logo-mark"><i class="fa-solid fa-cloud"></i></span>
    <?= hs_h($t['brand'] ?? '') ?>
  </a>
  <nav class="hs-nav">
    <a href="<?= hs_h(hs_url('#panel')) ?>" class="hs-nav-link"><?= hs_h($t['nav_panel'] ?? 'Panel') ?></a>
    <a href="<?= hs_h(hs_url('#ecosystem')) ?>" class="hs-nav-link"><?= hs_h($t['nav_ecosystem'] ?? 'Ecosystem') ?></a>
    <a href="<?= hs_h(hs_url('#pricing')) ?>" class="hs-nav-link"><?= hs_h($t['nav_pricing'] ?? 'Plans') ?></a>
    <?= hs_render_lang_dropdown($lang) ?>
    <a href="<?= hs_h(hs_url('login.php')) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_login'] ?? '') ?></a>
    <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['nav_register'] ?? '') ?></a>
  </nav>
</header>
<?= $content ?? '' ?>
<?php
require_once __DIR__ . '/public-footer.php';
echo hs_render_public_footer($t, $lang);
?>
<script src="<?= hs_h(hs_asset('js/app.js')) ?>" defer></script>
<?php if (!empty($extra_footer_scripts) && is_array($extra_footer_scripts)): ?>
<?php foreach ($extra_footer_scripts as $scr): ?>
<script src="<?= hs_h(hs_asset((string) $scr)) ?>" defer></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>