<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';
require_once __DIR__ . '/includes/domain-store.php';
require_once __DIR__ . '/includes/domain-search-history.php';
if (is_file(__DIR__ . '/includes/brand-mark.php')) {
    require_once __DIR__ . '/includes/brand-mark.php';
}

$recentSearches = hs_domain_search_history_list();

if (hs_client_user() !== null) {
    hs_redirect('panel/');
}

$domainCheckUrl = hs_url('domain-check.php');
$prefillSld = trim((string) ($_GET['sld'] ?? $_GET['q'] ?? ''));
$autoSearch = $prefillSld !== '' ? '1' : '0';

ob_start();
$body_class = 'hs-page-domain-search';
$page_theme_color = '#059669';
$page_extra_css = ['css/domain-page-mobile.css'];
$page_seo = [
    'type' => 'domain',
    'path' => 'domain',
    'title' => (string) ($t['domain_page_meta_title'] ?? ''),
    'description' => (string) ($t['domain_page_meta_description'] ?? ''),
    'keywords' => (string) ($t['domain_page_keywords'] ?? ''),
];
?>
<section class="hs-domain-search-page">
  <div class="hs-container hs-domain-search-page-inner">
    <header class="hs-domain-search-head">
      <p class="hs-domain-search-kicker"><i class="fa-solid fa-globe" aria-hidden="true"></i> <?= hs_h($t['domain_page_kicker'] ?? '') ?></p>
      <h1><?= hs_h($t['domain_page_title'] ?? 'Find and register your domain') ?></h1>
      <p class="hs-domain-search-lead"><?= hs_h($t['domain_page_lead'] ?? '') ?></p>
      <?php if (!empty($t['domain_page_markets'])): ?>
      <p class="hs-domain-search-markets"><i class="fa-solid fa-earth-europe" aria-hidden="true"></i> <?= hs_h($t['domain_page_markets']) ?></p>
      <?php endif; ?>
    </header>

    <div class="hs-domain-search-panel-wrap">
      <?= hs_brand_domain_search_sun() ?>
      <div class="hs-domain-search-panel">
        <?= hs_render_domain_search_form($t, $lang, [
            'check_url' => $domainCheckUrl,
            'prefill' => $prefillSld,
            'autosearch' => $autoSearch,
            'variant' => 'page',
        ]) ?>
        <div data-hs-domain-recent-slot><?= hs_render_domain_search_recent($t, $recentSearches) ?></div>
      </div>
    </div>

    <div class="hs-domain-search-results-wrap" data-hs-domain-results-wrap hidden>
      <div data-hs-domain-result class="hs-domain-search-results" aria-live="polite"></div>
    </div>

    <div class="hs-domain-search-foot">
      <ul class="hs-domain-search-perks">
        <?php foreach (['domain_page_perk_1', 'domain_page_perk_2', 'domain_page_perk_3'] as $perkKey): ?>
          <?php if (!empty($t[$perkKey])): ?>
          <li><i class="fa-solid fa-check" aria-hidden="true"></i> <?= hs_h($t[$perkKey]) ?></li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
      <a href="<?= hs_h(hs_url('domains')) ?>" class="hs-btn hs-btn-ghost hs-btn-sm"><i class="fa-solid fa-table-list" aria-hidden="true"></i> <?= hs_h($t['domain_page_catalog_cta'] ?? 'All TLD prices') ?></a>
    </div>
  </div>
</section>

<section class="hs-domain-page-zones" aria-label="<?= hs_h($t['domain_page_zones_title'] ?? 'Popular zones') ?>">
  <div class="hs-container">
    <h2><?= hs_h($t['domain_page_zones_title'] ?? 'Popular domain zones in Europe') ?></h2>
    <p class="hs-domain-page-zones-lead"><?= hs_h($t['domain_page_zones_lead'] ?? '') ?></p>
    <div class="hs-domain-page-zone-grid">
      <?php
      $zoneCopy = [
          'eu' => $t['domain_page_zone_eu'] ?? '.eu — European Union',
          'lt' => $t['domain_page_zone_lt'] ?? '.lt — Lithuania',
          'pl' => $t['domain_page_zone_pl'] ?? '.pl — Poland',
          'se' => $t['domain_page_zone_se'] ?? '.se — Sweden',
          'no' => $t['domain_page_zone_no'] ?? '.no — Norway',
          'com' => $t['domain_page_zone_com'] ?? '.com — worldwide',
          'be' => $t['domain_page_zone_be'] ?? '.be — Belgium',
      ];
      $prices = hs_domain_tld_prices();
      foreach (['eu', 'lt', 'pl', 'se', 'com', 'be'] as $zoneTld):
          if (!isset($prices[$zoneTld])) {
              continue;
          }
      ?>
      <article class="hs-domain-page-zone-card">
        <strong>.<?= hs_h($zoneTld) ?></strong>
        <span><?= hs_h($zoneCopy[$zoneTld] ?? '') ?></span>
        <em><?= hs_h(hs_domain_format_price((float) $prices[$zoneTld], $lang)) ?></em>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="hs-domain-page-bottom-ctas">
      <a href="<?= hs_h(hs_url('register.php', ['order' => 'domain'])) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['domain_only_cta'] ?? 'Domain only') ?></a>
      <a href="<?= hs_h(hs_url('register.php', ['order' => 'bundle'])) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['domain_register_cta'] ?? 'Hosting + domain') ?></a>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$page_title = (string) ($t['domain_page_meta_title'] ?? $t['domain_search_title'] ?? 'Domain search');
require __DIR__ . '/includes/layout-public.php';