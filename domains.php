<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/public-footer.php';

$reqUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
if (str_contains($reqUri, 'domains.php')) {
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    hs_redirect('domains' . ($qs !== '' ? '?' . $qs : ''), 301);
}

$apiUrl = hs_url('domain-pricing-api.php');
$checkUrl = hs_url('domain-check.php');
$registerUrl = hs_url('register.php');
$homeUrl = hs_url();
$searchUrl = hs_url('domain');
$page_seo = [
    'type' => 'domains_catalog',
    'path' => 'domains',
    'title' => (string) ($t['domain_catalog_page_title'] ?? ''),
    'description' => (string) ($t['domain_catalog_meta_description'] ?? $t['domain_catalog_lead'] ?? ''),
    'keywords' => (string) ($t['domain_catalog_keywords'] ?? ''),
];

ob_start();
$body_class = 'hs-theme-fire hs-page-tld-catalog';
$page_theme_color = '#ea580c';
?>
<section class="hs-tld-catalog-hero">
  <div class="hs-container">
    <p class="hs-tld-catalog-kicker"><i class="fa-solid fa-globe" aria-hidden="true"></i> <?= hs_h($t['domain_catalog_kicker'] ?? 'Solaskinner domain prices') ?></p>
    <h1 class="hs-tld-catalog-title"><?= hs_h($t['domain_catalog_title'] ?? 'Top-level domains') ?></h1>
    <p class="hs-tld-catalog-lead"><?= hs_h($t['domain_catalog_lead'] ?? 'All TLDs available via our Namecheap reseller account. Prices load live from the API.') ?></p>
    <div class="hs-tld-catalog-hero-actions">
      <a href="<?= hs_h($searchUrl) ?>" class="hs-btn hs-btn-primary"><i class="fa-solid fa-magnifying-glass"></i> <?= hs_h($t['domain_catalog_search_cta'] ?? 'Search a domain') ?></a>
      <a href="<?= hs_h(hs_url('register.php', ['order' => 'domain'])) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['domain_only_cta'] ?? 'Domain only') ?></a>
    </div>
  </div>
</section>

<section class="hs-tld-catalog-main">
  <div class="hs-container">
    <div class="hs-tld-catalog-panel" data-hs-tld-catalog
      data-api-url="<?= hs_h($apiUrl) ?>"
      data-check-url="<?= hs_h($checkUrl) ?>"
      data-register-url="<?= hs_h($registerUrl) ?>"
      data-home-url="<?= hs_h($homeUrl) ?>"
      data-search-url="<?= hs_h($searchUrl) ?>"
      data-msg-loading="<?= hs_h($t['domain_catalog_loading'] ?? 'Loading TLD prices…') ?>"
      data-msg-error="<?= hs_h($t['domain_catalog_error'] ?? 'Could not load prices. Try again.') ?>"
      data-msg-empty="<?= hs_h($t['domain_catalog_empty'] ?? 'No TLDs match your search.') ?>"
      data-msg-count="<?= hs_h($t['domain_catalog_count'] ?? '{count} zones') ?>"
      data-msg-sale="<?= hs_h($t['domain_catalog_sale'] ?? 'Sale') ?>"
      data-msg-renew="<?= hs_h($t['domain_catalog_renew'] ?? 'Renewal') ?>"
      data-msg-privacy="<?= hs_h($t['domain_catalog_privacy'] ?? 'Free domain privacy') ?>"
      data-msg-privacy-no="<?= hs_h($t['domain_catalog_privacy_no'] ?? '—') ?>"
      data-msg-manual="<?= hs_h($t['domain_catalog_manual'] ?? 'Norid registry') ?>"
      data-msg-check="<?= hs_h($t['domain_catalog_check'] ?? 'Check') ?>"
      data-msg-order="<?= hs_h($t['domain_catalog_order'] ?? 'Order') ?>"
      data-col-tld="<?= hs_h($t['domain_catalog_col_tld'] ?? 'TLD') ?>"
      data-col-privacy="<?= hs_h($t['domain_catalog_col_privacy'] ?? 'Privacy') ?>"
      data-col-register="<?= hs_h($t['domain_catalog_col_register'] ?? 'Register / year') ?>"
      data-col-renew="<?= hs_h($t['domain_catalog_col_renew'] ?? 'Renewal / year') ?>"
      data-col-action="<?= hs_h($t['domain_catalog_col_action'] ?? '') ?>">
      <div class="hs-tld-catalog-toolbar">
        <div class="hs-field hs-tld-catalog-search-field">
          <label for="hs-tld-catalog-q"><?= hs_h($t['domain_catalog_filter'] ?? 'Filter TLD') ?></label>
          <input type="search" id="hs-tld-catalog-q" class="hs-tld-catalog-search" placeholder="<?= hs_h($t['domain_catalog_filter_ph'] ?? 'e.g. com, shop, no') ?>" autocomplete="off" data-hs-tld-catalog-q>
        </div>
        <div class="hs-field">
          <label for="hs-tld-catalog-sort"><?= hs_h($t['domain_catalog_sort'] ?? 'Sort') ?></label>
          <select id="hs-tld-catalog-sort" data-hs-tld-catalog-sort>
            <option value="popular"><?= hs_h($t['domain_catalog_sort_popular'] ?? 'Popular first') ?></option>
            <option value="az"><?= hs_h($t['domain_catalog_sort_az'] ?? 'A → Z') ?></option>
            <option value="price-asc"><?= hs_h($t['domain_catalog_sort_price_asc'] ?? 'Price ↑') ?></option>
            <option value="price-desc"><?= hs_h($t['domain_catalog_sort_price_desc'] ?? 'Price ↓') ?></option>
          </select>
        </div>
        <p class="hs-tld-catalog-meta hp-muted" data-hs-tld-catalog-meta></p>
      </div>
      <div class="hs-tld-catalog-status" data-hs-tld-catalog-status role="status">
        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
        <span><?= hs_h($t['domain_catalog_loading'] ?? 'Loading…') ?></span>
      </div>
      <div class="hs-table-wrap hs-tld-catalog-table-wrap" hidden data-hs-tld-catalog-table-wrap>
        <table class="hs-table hs-tld-catalog-table">
          <thead>
            <tr>
              <th><?= hs_h($t['domain_catalog_col_tld'] ?? 'TLD') ?></th>
              <th class="hs-col-hide-sm"><?= hs_h($t['domain_catalog_col_privacy'] ?? 'Privacy') ?></th>
              <th><?= hs_h($t['domain_catalog_col_register'] ?? 'Register / year') ?></th>
              <th class="hs-col-hide-md"><?= hs_h($t['domain_catalog_col_renew'] ?? 'Renewal / year') ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody data-hs-tld-catalog-body></tbody>
        </table>
      </div>
      <p class="hp-muted hs-tld-catalog-footnote"><?= hs_h($t['domain_catalog_footnote'] ?? 'Prices shown are final for registration and renewal. * Free domain privacy on eligible gTLDs.') ?></p>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$page_title = $t['domain_catalog_page_title'] ?? ($t['domain_catalog_title'] ?? 'Domain prices');
$extra_footer_scripts = ['js/domain-pricing.js'];
require __DIR__ . '/includes/layout-public.php';