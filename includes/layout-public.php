<?php
declare(strict_types=1);
/** @var array $t */
/** @var string $lang */
/** @var array $lang_meta */

require_once __DIR__ . '/public-seo.php';
if (is_file(__DIR__ . '/brand-mark.php')) {
    require_once __DIR__ . '/brand-mark.php';
}
require_once __DIR__ . '/public-header.php';

$page_title = $page_title ?? ($t['meta_title'] ?? HS_SITE_NAME);
$body_class = trim((string) ($body_class ?? 'hs-public-body'));
if ($body_class === '') {
    $body_class = 'hs-public-body';
} elseif (!str_contains($body_class, 'hs-public-body')) {
    $body_class .= ' hs-public-body';
}
$page_theme_color = (string) ($page_theme_color ?? '#047857');

$seo = is_array($seo ?? null) ? $seo : (is_array($page_seo ?? null) ? $page_seo : []);
if (!isset($seo['type'])) {
    $seo['type'] = 'page';
}
if (!isset($seo['path'])) {
    $seo['path'] = '';
}
if (!isset($seo['title']) || $seo['title'] === '') {
    $seo['title'] = $page_title;
}
$seoResolved = hs_seo_resolve($seo, $t, $lang);
$page_title = (string) $seoResolved['title'];
$robots = !empty($seoResolved['noindex'])
    ? 'noindex,nofollow'
    : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
?>
<!DOCTYPE html>
<html lang="<?= hs_h($lang_meta['html'] ?? 'en') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="<?= hs_h($robots) ?>">
<meta name="theme-color" content="<?= hs_h($page_theme_color) ?>">
<title><?= hs_h($page_title) ?></title>
<?php if (function_exists('hs_render_favicon_links')): ?>
<?= hs_render_favicon_links() ?>
<?php endif; ?>
<?= hs_render_public_seo_head($seoResolved) ?>
<?php if (!empty($page_preload_lcp)): ?>
<link rel="preload" as="image" href="<?= hs_h((string) $page_preload_lcp) ?>" fetchpriority="high">
<?php endif; ?>
<?php
// Public pages: prefer slim public.css (panel CSS stays on app.css for panel/admin).
$hs_public_css = dirname(__DIR__) . '/assets/css/public.css';
$hs_css = is_file($hs_public_css) ? hs_asset('css/public.css') : hs_asset('css/app.css');
// Font Awesome: core + solid + brands (smaller than all.min.css). Async load.
$hs_fa_base = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css';
$hs_fa_files = [
    $hs_fa_base . '/fontawesome.min.css',
    $hs_fa_base . '/solid.min.css',
    $hs_fa_base . '/brands.min.css',
];
// Two weights only (mobile): 400 + 700.
$hs_font = 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="stylesheet" href="<?= hs_h($hs_css) ?>">
<link rel="stylesheet" href="<?= hs_h(hs_asset('css/header-public.css')) ?>">
<link rel="stylesheet" href="<?= hs_h(hs_asset('css/lang-float.css')) ?>">
<?php
// Optional page CSS (e.g. homepage speed block) — small files only.
if (!empty($page_extra_css) && is_array($page_extra_css)) {
    foreach ($page_extra_css as $extraCss) {
        $extraCss = ltrim((string) $extraCss, '/');
        if ($extraCss === '' || str_contains($extraCss, '..')) {
            continue;
        }
        echo '<link rel="stylesheet" href="' . hs_h(hs_asset($extraCss)) . '">' . "\n";
    }
}
?>
<link rel="preload" as="style" href="<?= hs_h($hs_font) ?>" onload="this.onload=null;this.rel='stylesheet'">
<?php foreach ($hs_fa_files as $hs_fa_href): ?>
<link rel="preload" as="style" href="<?= hs_h($hs_fa_href) ?>" crossorigin="anonymous" onload="this.onload=null;this.rel='stylesheet'">
<?php endforeach; ?>
<noscript>
<link rel="stylesheet" href="<?= hs_h($hs_font) ?>">
<?php foreach ($hs_fa_files as $hs_fa_href): ?>
<link rel="stylesheet" href="<?= hs_h($hs_fa_href) ?>" crossorigin="anonymous">
<?php endforeach; ?>
</noscript>
</head>
<body class="<?= hs_h($body_class) ?>">
<a class="hs-skip-link" href="#main-content"><?= hs_h($t['skip_to_content'] ?? 'Skip to content') ?></a>
<?php if (function_exists('hs_render_prelaunch_banner')): ?>
<?= hs_render_prelaunch_banner($t) ?>
<?php endif; ?>
<?= hs_render_public_header($t, $lang) ?>
<main class="hs-public-main" id="main-content">
<?= $content ?? '' ?>
</main>
<?php
require_once __DIR__ . '/public-footer.php';
if (is_file(__DIR__ . '/legal.php')) {
    require_once __DIR__ . '/legal.php';
}
echo hs_render_public_footer($t, $lang);
if (empty($extra_footer_scripts) || !is_array($extra_footer_scripts)) {
    $extra_footer_scripts = [];
}
if (function_exists('hs_has_local_legal') && hs_has_local_legal() && !in_array('js/cookie-consent.js', $extra_footer_scripts, true)) {
    $extra_footer_scripts[] = 'js/cookie-consent.js';
}
?>
<script src="<?= hs_h(hs_asset('js/app.js')) ?>" defer></script>
<?php if (!empty($extra_footer_scripts) && is_array($extra_footer_scripts)): ?>
<?php foreach ($extra_footer_scripts as $scr): ?>
<script src="<?= hs_h(hs_asset((string) $scr)) ?>" defer></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
