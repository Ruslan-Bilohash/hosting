<?php
declare(strict_types=1);

/**
 * SolaSkinner homepage — clean conversion layout (Linear/Vercel-style principles):
 * one hero, one product story, one features grid, one CMS list, pricing, FAQ, CTA.
 * SEO in head + visible FAQ. Fully responsive: mobile / tablet / desktop.
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';
require_once __DIR__ . '/includes/plans.php';
require_once __DIR__ . '/includes/ecosystem-catalog.php';
require_once __DIR__ . '/includes/hero-login-card.php';
require_once __DIR__ . '/includes/home-panel-block.php';

hs_seed_demo_data();

if (hs_client_id() !== null) {
    hs_redirect('panel/');
}

$seoHomeTitle = trim((string) ($t['meta_title'] ?? ''));
$seoHomeDesc = trim((string) ($t['meta_description'] ?? ''));
$seoHomeKw = trim((string) ($t['meta_keywords'] ?? ''));
if ($seoHomeTitle === '' || str_contains($seoHomeTitle, 'BILOHASH Hosting')) {
    $seoHomeTitle = match ($lang) {
        'no' => 'SolaSkinner Hosting — webhotell Europa | Norge, Tyskland, Litauen',
        'uk' => 'SolaSkinner Hosting — веб-хостинг Європа | Норвегія, Німеччина, Литва',
        default => 'SolaSkinner Hosting — web hosting Europe | Norway, Germany, Lithuania',
    };
}
if ($seoHomeDesc === '' || str_contains($seoHomeDesc, 'BILOHASH Hosting —')) {
    $seoHomeDesc = (string) ($t['tagline'] ?? 'European web hosting with free SSL, SSD and one-click CMS.');
}
$seo = [
    'type' => 'home',
    'path' => '',
    'title' => $seoHomeTitle,
    'description' => $seoHomeDesc,
    'keywords' => $seoHomeKw,
    'og_type' => 'website',
];
$GLOBALS['hs_hide_demo_banner'] = true;

// Domain lookup runs on /domain only (keeps homepage light).
$ecoCatalog = bh_ecosystem_catalog();

// One features list only (no second copy later)
$feats = [
    ['icon' => 'fa-puzzle-piece', 'title' => $t['feat_ecosystem'] ?? '15+ CMS free', 'text' => $t['feat_ecosystem_desc'] ?? ''],
    ['icon' => 'fa-paintbrush', 'title' => $t['feat_page_builder'] ?? 'Free page builder', 'text' => $t['feat_page_builder_desc'] ?? ''],
    ['icon' => 'fa-globe', 'title' => $t['feat_domains'] ?? 'Domains & DNS', 'text' => $t['feat_domains_desc'] ?? ''],
    ['icon' => 'fa-server', 'title' => $t['feat_hosting'] ?? 'SSD hosting EU', 'text' => $t['feat_hosting_desc'] ?? ''],
    ['icon' => 'fa-gauge-high', 'title' => $t['feat_speed'] ?? 'Server performance', 'text' => $t['feat_speed_desc'] ?? ''],
    ['icon' => 'fa-box-open', 'title' => $t['feat_install'] ?? '1-click install', 'text' => $t['feat_install_desc'] ?? ''],
    ['icon' => 'fa-shield-halved', 'title' => $t['feat_secure'] ?? 'SSL & security', 'text' => $t['feat_secure_desc'] ?? ''],
    ['icon' => 'fa-language', 'title' => $t['feat_i18n'] ?? 'Multilingual panel', 'text' => $t['feat_i18n_desc'] ?? ''],
];

$steps = [
    ['num' => '1', 'title' => 'step_1_title', 'text' => 'step_1_desc', 'icon' => 'fa-layer-group'],
    ['num' => '2', 'title' => 'step_2_title', 'text' => 'step_2_desc', 'icon' => 'fa-rocket'],
    ['num' => '3', 'title' => 'step_3_title', 'text' => 'step_3_desc', 'icon' => 'fa-sliders'],
];

$body_class = 'hs-home-clean hs-public-body';
$page_theme_color = '#059669';
// Do not preload speedtest.webp — it is not the LCP (hero is text + login form).
// Preloading a non-LCP image hurts mobile LCP/bandwidth (PageSpeed).
$page_preload_lcp = null;
$page_extra_css = ['css/home-speed.css', 'css/shot-lightbox.css'];

ob_start();
?>
<section class="hs-home-hero" id="top">
  <div class="hs-home-hero-inner">
    <div class="hs-home-hero-copy">
      <p class="hs-home-kicker"><?= hs_h($t['hero_kicker'] ?? 'European web hosting') ?></p>
      <h1><?= hs_h($t['hero_title'] ?? 'Hosting for sites that rank') ?></h1>
      <p class="hs-home-lead"><?= hs_h($t['hero_sub'] ?? '') ?></p>
      <div class="hs-home-cta">
        <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['cta_register'] ?? 'Get started') ?></a>
        <a href="#pricing" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_pricing'] ?? 'View plans') ?></a>
      </div>
    </div>
    <div class="hs-home-hero-card" id="login">
      <h2 class="hs-home-card-title"><?= hs_h($t['nav_login'] ?? 'Client login') ?></h2>
      <?= hs_render_hero_login_card($t) ?>
    </div>
  </div>
</section>

<section class="hs-home-domain" id="domain" aria-label="<?= hs_h($t['domain_search_title'] ?? 'Domain search') ?>">
  <div class="hs-home-domain-inner">
    <div>
      <h2><?= hs_h($t['domain_search_title'] ?? 'Find a domain') ?></h2>
      <p><?= hs_h($t['hero_sub'] ?? '') ?></p>
    </div>
    <div class="hs-home-domain-actions">
      <a href="<?= hs_h(hs_url('domain')) ?>" class="hs-btn hs-btn-primary hs-home-domain-cta">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <?= hs_h($t['domain_home_cta'] ?? $t['domain_search_btn'] ?? 'Search domain') ?>
      </a>
    </div>
  </div>
</section>

<section class="hs-home-section" id="features">
  <div class="hs-home-wrap">
    <header class="hs-home-section-head">
      <h2><?= hs_h($t['features_title'] ?? $t['panel_title'] ?? 'Everything in one panel') ?></h2>
      <p><?= hs_h($t['panel_lead'] ?? $t['panel_desc'] ?? '') ?></p>
    </header>
    <div class="hs-home-feature-grid">
      <?php foreach ($feats as $f):
          if (trim((string) ($f['title'] ?? '')) === '') {
              continue;
          }
      ?>
      <article class="hs-home-feature">
        <i class="fa-solid <?= hs_h($f['icon']) ?>" aria-hidden="true"></i>
        <h3><?= hs_h((string) $f['title']) ?></h3>
        <p><?= hs_h((string) ($f['text'] ?? '')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= hs_render_home_panel_block($t, $lang) ?>

<section class="hs-home-section hs-home-section--soft" id="how">
  <div class="hs-home-wrap">
    <header class="hs-home-section-head">
      <h2><?= hs_h($t['steps_title'] ?? 'How it works') ?></h2>
      <p><?= hs_h($t['steps_sub'] ?? '') ?></p>
    </header>
    <div class="hs-home-steps">
      <?php foreach ($steps as $step): ?>
      <article class="hs-home-step">
        <span class="hs-home-step-num"><?= hs_h($step['num']) ?></span>
        <h3><?= hs_h($t[$step['title']] ?? '') ?></h3>
        <p><?= hs_h($t[$step['text']] ?? '') ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="hs-home-section hs-home-builder" id="page-builder" aria-labelledby="page-builder-title">
  <div class="hs-home-wrap">
    <div class="hs-home-builder-grid">
      <div class="hs-home-builder-copy">
        <p class="hs-home-kicker hs-home-builder-badge"><i class="fa-solid fa-gift" aria-hidden="true"></i> <?= hs_h($t['page_builder_badge'] ?? 'Free out of the box') ?></p>
        <h2 id="page-builder-title"><?= hs_h($t['page_builder_title'] ?? 'Free visual page builder included') ?></h2>
        <p class="hs-home-builder-lead"><?= hs_h($t['page_builder_lead'] ?? '') ?></p>
        <p><?= hs_h($t['page_builder_desc'] ?? $t['landing_desc'] ?? '') ?></p>
        <ul class="hs-home-builder-perks">
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t['page_builder_perk_1'] ?? '') ?></li>
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t['page_builder_perk_2'] ?? '') ?></li>
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t['page_builder_perk_3'] ?? '') ?></li>
        </ul>
        <div class="hs-home-cta">
          <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['page_builder_cta'] ?? $t['cta_register'] ?? 'Get started') ?></a>
          <a href="#pricing" class="hs-btn hs-btn-ghost"><?= hs_h($t['page_builder_cta_secondary'] ?? $t['nav_pricing'] ?? 'Plans') ?></a>
        </div>
      </div>
      <div class="hs-home-builder-visual" aria-hidden="true">
        <div class="hs-home-builder-mock">
          <div class="hs-home-builder-mock-bar"><span></span><span></span><span></span></div>
          <div class="hs-home-builder-mock-body">
            <div class="hs-home-builder-mock-side"></div>
            <div class="hs-home-builder-mock-canvas">
              <div class="hs-home-builder-mock-block is-hero"></div>
              <div class="hs-home-builder-mock-block"></div>
              <div class="hs-home-builder-mock-block is-wide"></div>
            </div>
          </div>
        </div>
        <p class="hs-home-builder-visual-caption"><?= hs_h($t['panel_feat_landing'] ?? 'Landing page builder') ?></p>
      </div>
    </div>
  </div>
</section>

<section class="hs-home-section hs-home-speed" id="performance" aria-labelledby="speed-title">
  <div class="hs-home-wrap">
    <header class="hs-home-section-head">
      <p class="hs-home-kicker"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> <?= hs_h($t['speed_badge'] ?? 'Performance first') ?></p>
      <h2 id="speed-title"><?= hs_h($t['speed_title'] ?? 'Built for PageSpeed — and your visitors') ?></h2>
      <p><?= hs_h($t['speed_lead'] ?? '') ?></p>
    </header>
    <div class="hs-home-speed-grid">
      <div class="hs-home-speed-visual">
        <?php
        $speedWebp = is_file(__DIR__ . '/assets/speedtest.webp') ? hs_asset('speedtest.webp') : '';
        $speedJpg = is_file(__DIR__ . '/assets/speedtest.jpg') ? hs_asset('speedtest.jpg') : hs_asset('speedtest.jpg');
        $speedAlt = (string) ($t['feat_speed'] ?? $t['speed_title'] ?? 'PageSpeed performance');
        $speedZoom = $speedAlt . ' — ' . (string) ($t['shot_zoom_action'] ?? 'enlarge');
        ?>
        <button type="button" class="hs-home-speed-shot hs-shot-zoom-trigger" data-hs-shot-zoom aria-label="<?= hs_h($speedZoom) ?>">
          <picture>
            <?php if ($speedWebp !== ''): ?>
            <source srcset="<?= hs_h($speedWebp) ?>" type="image/webp">
            <?php endif; ?>
            <img src="<?= hs_h($speedJpg) ?>" alt="<?= hs_h($speedAlt) ?>" width="1200" height="675" loading="lazy" decoding="async" class="hs-home-speed-img">
          </picture>
          <span class="hs-shot-zoom-hint" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
        </button>
        <div class="hs-home-speed-scores" role="list" aria-label="<?= hs_h($t['speed_scores_label'] ?? 'Lab scores') ?>">
          <?php
          $speedScores = [
              ['n' => '96', 'label' => $t['speed_score_perf'] ?? 'Performance'],
              ['n' => '95', 'label' => $t['speed_score_a11y'] ?? 'Accessibility'],
              ['n' => '96', 'label' => $t['speed_score_bp'] ?? 'Best practices'],
              ['n' => '100', 'label' => $t['speed_score_seo'] ?? 'SEO'],
          ];
          foreach ($speedScores as $sc):
          ?>
          <div class="hs-home-speed-score" role="listitem">
            <span class="hs-home-speed-ring" style="--score:<?= hs_h($sc['n']) ?>"><strong><?= hs_h($sc['n']) ?></strong></span>
            <span class="hs-home-speed-score-label"><?= hs_h((string) $sc['label']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hs-home-speed-copy">
        <h3><?= hs_h($t['speed_servers_title'] ?? 'We pick servers for this level of speed') ?></h3>
        <p><?= hs_h($t['speed_servers_body'] ?? $t['feat_speed_desc'] ?? '') ?></p>
        <ul class="hs-home-speed-points">
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t['speed_point_1'] ?? '') ?></li>
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t['speed_point_2'] ?? '') ?></li>
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t['speed_point_3'] ?? '') ?></li>
        </ul>
        <p class="hs-home-speed-note"><?= hs_h($t['speed_note'] ?? '') ?></p>
        <div class="hs-home-cta">
          <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['speed_cta'] ?? $t['cta_register'] ?? 'Get started') ?></a>
          <a href="#pricing" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_pricing'] ?? 'Plans') ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="hs-home-section" id="ecosystem">
  <div class="hs-home-wrap">
    <header class="hs-home-section-head">
      <h2><?= hs_h($t['ecosystem_title'] ?? 'CMS included in every plan') ?></h2>
      <p><?= hs_h($t['ecosystem_lead'] ?? $t['ecosystem_desc'] ?? '') ?></p>
    </header>
    <div class="hs-home-cms-grid">
      <?php
      // Map every homepage CMS tile to its SEO landing (seo/hosting-for-*.php).
      if (is_file(__DIR__ . '/includes/seo-apps-catalog.php')) {
          require_once __DIR__ . '/includes/seo-apps-catalog.php';
      }
      $langQs = ($lang !== '' && $lang !== 'en') ? ['lang' => $lang] : [];
      // Prefer SEO catalog order (user-facing names); fall back to ecosystem catalog.
      $cmsTiles = [];
      if (function_exists('hs_seo_apps_order') && function_exists('hs_seo_app')) {
          foreach (hs_seo_apps_order() as $slug) {
              if ($slug === 'hosting') {
                  continue; // SEO-only product, not in public ecosystem grid
              }
              $seoApp = hs_seo_app((string) $slug);
              if (!is_array($seoApp)) {
                  continue;
              }
              $eco = is_array($ecoCatalog[$slug] ?? null) ? $ecoCatalog[$slug] : [];
              $cmsTiles[] = [
                  'slug' => (string) $slug,
                  'short' => (string) ($seoApp['name'] ?? $eco['short'] ?? $slug),
                  'icon' => (string) ($eco['icon'] ?? preg_replace('/^fa-/', '', (string) ($seoApp['icon'] ?? 'cube'))),
                  'icon_brand' => !empty($seoApp['icon_brand']) || !empty($eco['icon_brand']),
                  'color' => (string) ($eco['color'] ?? '#059669'),
                  'file' => (string) ($seoApp['file'] ?? ('hosting-for-' . $slug . '.php')),
              ];
          }
      }
      if ($cmsTiles === []) {
          foreach ($ecoCatalog as $slug => $app) {
              $cmsTiles[] = [
                  'slug' => (string) $slug,
                  'short' => (string) ($app['short'] ?? $slug),
                  'icon' => (string) ($app['icon'] ?? 'cube'),
                  'icon_brand' => !empty($app['icon_brand']),
                  'color' => (string) ($app['color'] ?? '#0f172a'),
                  'file' => 'hosting-for-' . preg_replace('/[^a-z0-9-]/', '', strtolower((string) $slug)) . '.php',
              ];
          }
      }
      foreach ($cmsTiles as $tile):
          $seoHref = hs_url('seo/' . $tile['file'], $langQs);
      ?>
      <a class="hs-home-cms" href="<?= hs_h($seoHref) ?>" style="--c:<?= hs_h($tile['color']) ?>">
        <i class="<?= !empty($tile['icon_brand']) ? 'fa-brands' : 'fa-solid' ?> fa-<?= hs_h($tile['icon']) ?>" aria-hidden="true"></i>
        <span><?= hs_h($tile['short']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <p class="hs-home-cms-note">
      <a href="<?= hs_h(hs_url('seo/', $langQs)) ?>"><?= hs_h($t['seo_internal_all'] ?? 'All CMS hosting guides →') ?></a>
    </p>
  </div>
</section>

<section class="hs-home-section hs-home-section--soft" id="pricing">
  <div class="hs-home-wrap">
    <header class="hs-home-section-head">
      <h2><?= hs_h($t['plans_title'] ?? 'Hosting plans') ?></h2>
      <p><?= hs_h($t['plans_sub'] ?? '') ?></p>
    </header>
    <?= hs_render_public_plan_cards($t, $lang) ?>
  </div>
</section>

<section class="hs-home-section" id="faq">
  <div class="hs-home-wrap hs-home-wrap--narrow">
    <header class="hs-home-section-head">
      <h2><?= hs_h($t['seo_faq_title'] ?? 'FAQ') ?></h2>
      <p><?= hs_h($t['seo_faq_lead'] ?? '') ?></p>
    </header>
    <div class="hs-home-faq">
      <?php for ($i = 1; $i <= 8; $i++):
          $q = trim((string) ($t['seo_faq_q' . $i] ?? ''));
          $a = trim((string) ($t['seo_faq_a' . $i] ?? ''));
          if ($q === '' || $a === '') {
              continue;
          }
      ?>
      <details class="hs-home-faq-item"<?= $i === 1 ? ' open' : '' ?>>
        <summary><?= hs_h($q) ?></summary>
        <p><?= hs_h($a) ?></p>
      </details>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section class="hs-home-final" id="start">
  <div class="hs-home-wrap hs-home-final-inner">
    <h2><?= hs_h($t['cta_final_title'] ?? 'Ready to launch?') ?></h2>
    <p><?= hs_h($t['cta_final_sub'] ?? '') ?></p>
    <div class="hs-home-cta">
      <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['cta_register'] ?? 'Get started') ?></a>
      <a href="<?= hs_h(hs_url('login.php')) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_login'] ?? 'Log in') ?></a>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/includes/layout-public.php';
