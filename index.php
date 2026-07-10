<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';
require_once __DIR__ . '/includes/domain-store.php';
require_once __DIR__ . '/includes/plans.php';
require_once __DIR__ . '/includes/ecosystem-catalog.php';

hs_seed_demo_data();

if (hs_client_id() !== null) {
    hs_redirect('panel/');
}

$domainResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_domain'])) {
    $domainResult = hs_domain_check_availability((string) ($_POST['domain'] ?? ''));
}
$domainCheckUrl = hs_url('domain-check.php');
$ecoCatalog = bh_ecosystem_catalog();

$panelFeats = [
    ['icon' => 'fa-wand-magic-sparkles', 'title' => 'panel_feat_landing', 'text' => 'panel_feat_landing_desc'],
    ['icon' => 'fa-box-open', 'title' => 'panel_feat_installer', 'text' => 'panel_feat_installer_desc'],
    ['icon' => 'fa-folder-open', 'title' => 'panel_feat_files', 'text' => 'panel_feat_files_desc'],
    ['icon' => 'fa-globe', 'title' => 'panel_feat_domains', 'text' => 'panel_feat_domains_desc'],
    ['icon' => 'fa-shield-halved', 'title' => 'panel_feat_security', 'text' => 'panel_feat_security_desc'],
    ['icon' => 'fa-cloud-arrow-up', 'title' => 'panel_feat_backup', 'text' => 'panel_feat_backup_desc'],
    ['icon' => 'fa-database', 'title' => 'panel_feat_db', 'text' => 'panel_feat_db_desc'],
    ['icon' => 'fa-terminal', 'title' => 'panel_feat_ssh', 'text' => 'panel_feat_ssh_desc'],
    ['icon' => 'fa-gauge-high', 'title' => 'panel_feat_perf', 'text' => 'panel_feat_perf_desc'],
    ['icon' => 'fa-envelope', 'title' => 'panel_feat_email', 'text' => 'panel_feat_email_desc'],
    ['icon' => 'fa-headset', 'title' => 'panel_feat_support', 'text' => 'panel_feat_support_desc'],
];

$steps = [
    ['num' => '1', 'title' => 'step_1_title', 'text' => 'step_1_desc', 'icon' => 'fa-layer-group'],
    ['num' => '2', 'title' => 'step_2_title', 'text' => 'step_2_desc', 'icon' => 'fa-rocket'],
    ['num' => '3', 'title' => 'step_3_title', 'text' => 'step_3_desc', 'icon' => 'fa-sliders'],
];

$stats = [
    ['value' => 'stats_cms', 'label' => 'stats_cms_label'],
    ['value' => 'stats_panel', 'label' => 'stats_panel_label'],
    ['value' => 'stats_langs', 'label' => 'stats_langs_label'],
    ['value' => 'stats_setup', 'label' => 'stats_setup_label'],
];

ob_start();
?>
<section class="hs-hero">
  <div>
    <p class="hs-hero-kicker"><i class="fa-solid fa-bolt"></i> <?= hs_h($t['hero_kicker'] ?? '') ?></p>
    <h1><span class="hs-gradient-text"><?= hs_h($t['hero_title'] ?? '') ?></span></h1>
    <p><?= hs_h($t['hero_sub'] ?? '') ?></p>
    <div class="hs-hero-actions">
      <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['cta_register'] ?? '') ?></a>
      <a href="<?= hs_h(hs_url('#panel')) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['nav_panel'] ?? 'Panel') ?></a>
      <a href="<?= hs_h(hs_url('login.php')) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['cta_demo'] ?? '') ?></a>
    </div>
    <p class="hs-hero-demo-note"><?= hs_h($t['demo_creds'] ?? '') ?></p>
  </div>
  <div class="hs-hero-card">
    <div class="hs-hero-card-label"><?= hs_h($t['domain_search_title'] ?? 'Find your domain') ?></div>
    <form method="post" class="hp-stack" data-hs-domain-search data-check-url="<?= hs_h($domainCheckUrl) ?>"
      data-msg-available="<?= hs_h($t['domain_available'] ?? 'Available') ?>"
      data-msg-taken="<?= hs_h($t['domain_taken'] ?? 'Taken') ?>"
      data-msg-invalid="<?= hs_h($t['domain_invalid'] ?? 'Enter a valid domain (e.g. mysite.lt)') ?>"
      data-msg-error="<?= hs_h($t['domain_lookup_error'] ?? 'Could not check domain. Try again.') ?>"
      data-msg-checking="<?= hs_h($t['domain_checking'] ?? 'Checking registry…') ?>"
      data-msg-cta="<?= hs_h($t['domain_register_cta'] ?? 'Register with this domain') ?>"
      data-register-base="<?= hs_h(hs_url('register.php')) ?>">
      <div class="hs-field" style="margin:0">
        <input type="text" name="domain" placeholder="mysite.lt" required autocomplete="off"
          value="<?= hs_h($_POST['domain'] ?? '') ?>" data-hs-domain-input>
      </div>
      <button type="submit" name="search_domain" value="1" class="hs-btn hs-btn-primary" style="width:100%" data-hs-domain-btn><?= hs_h($t['domain_search_btn'] ?? 'Search') ?></button>
    </form>
    <div data-hs-domain-result class="hs-domain-result">
    <?php if (is_array($domainResult)): ?>
      <?php if (!empty($domainResult['ok'])): ?>
        <div><strong><?= hs_h((string) ($domainResult['domain'] ?? '')) ?></strong></div>
        <?php if (!empty($domainResult['available'])): ?>
          <span class="hs-domain-ok"><?= hs_h($t['domain_available'] ?? 'Available') ?></span>
          — <?= hs_h(hs_domain_format_price((float) ($domainResult['price'] ?? 0), $lang)) ?>
          <div style="margin-top:.5rem">
            <a href="<?= hs_h(hs_url('register.php', ['domain' => (string) ($domainResult['domain'] ?? '')])) ?>" class="hs-btn hs-btn-ghost" style="width:100%"><?= hs_h($t['domain_register_cta'] ?? 'Register with this domain') ?></a>
          </div>
        <?php else: ?>
          <span class="hs-domain-taken"><?= hs_h($t['domain_taken'] ?? 'Taken') ?></span>
        <?php endif; ?>
      <?php else: ?>
        <span class="hs-domain-taken"><?= hs_h(match ($domainResult['error'] ?? '') {
            'invalid' => $t['domain_invalid'] ?? 'Invalid domain',
            default => $t['domain_lookup_error'] ?? 'Lookup failed',
        }) ?></span>
      <?php endif; ?>
    <?php endif; ?>
    </div>
  </div>
</section>

<section class="hs-stats" aria-label="Stats">
  <div class="hs-stats-inner">
    <?php foreach ($stats as $s): ?>
      <div class="hs-stat">
        <strong><?= hs_h($t[$s['value']] ?? '') ?></strong>
        <span><?= hs_h($t[$s['label']] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="hs-business" id="business">
  <div class="hs-business-inner">
    <span class="hs-business-badge"><i class="fa-solid fa-briefcase"></i> <?= hs_h($t['business_badge'] ?? 'For agencies & SMB') ?></span>
    <h2><?= hs_h($t['business_title'] ?? '') ?></h2>
    <p class="hs-business-lead"><?= hs_h($t['business_lead'] ?? '') ?></p>
    <div class="hs-business-grid">
      <?php foreach (['business_p1', 'business_p2', 'business_p3', 'business_p4'] as $pillar): ?>
      <article class="hs-business-card">
        <i class="fa-solid <?= hs_h(match ($pillar) {
            'business_p1' => 'fa-coins',
            'business_p2' => 'fa-users-gear',
            'business_p3' => 'fa-rocket',
            default => 'fa-shield-halved',
        }) ?>"></i>
        <h3><?= hs_h($t[$pillar . '_title'] ?? '') ?></h3>
        <p><?= hs_h($t[$pillar . '_desc'] ?? '') ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="hs-panel-showcase" id="panel">
  <div class="hs-panel-showcase-head">
    <span class="hs-panel-badge"><i class="fa-solid fa-gauge-high"></i> <?= hs_h($t['panel_badge'] ?? '') ?></span>
    <h2><?= hs_h($t['panel_title'] ?? '') ?></h2>
    <p class="hs-panel-lead"><?= hs_h($t['panel_lead'] ?? '') ?></p>
    <p class="hs-panel-desc"><?= hs_h($t['panel_desc'] ?? '') ?></p>
  </div>
  <div class="hs-panel-mock" aria-hidden="true">
    <div class="hs-panel-mock-bar">
      <span></span><span></span><span></span>
      <em>BILOHASH Panel</em>
    </div>
    <div class="hs-panel-mock-body">
      <aside class="hs-panel-mock-nav">
        <span class="is-active"><i class="fa-solid fa-gauge"></i></span>
        <span><i class="fa-solid fa-globe"></i></span>
        <span><i class="fa-solid fa-folder"></i></span>
        <span><i class="fa-solid fa-database"></i></span>
        <span><i class="fa-solid fa-shield-halved"></i></span>
        <span><i class="fa-solid fa-wand-magic-sparkles"></i></span>
      </aside>
      <div class="hs-panel-mock-main">
        <div class="hs-panel-mock-cards">
          <div class="hs-panel-mock-card"></div>
          <div class="hs-panel-mock-card"></div>
          <div class="hs-panel-mock-card"></div>
        </div>
        <div class="hs-panel-mock-chart"></div>
      </div>
    </div>
  </div>
  <div class="hs-panel-feats">
    <?php foreach ($panelFeats as $pf): ?>
      <article class="hs-panel-feat">
        <i class="fa-solid <?= hs_h($pf['icon']) ?>"></i>
        <h3><?= hs_h($t[$pf['title']] ?? '') ?></h3>
        <p><?= hs_h($t[$pf['text']] ?? '') ?></p>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="hs-panel-cta-wrap">
    <a href="<?= hs_h(hs_url('login.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['panel_cta'] ?? '') ?></a>
  </div>
</section>

<section class="hs-steps">
  <div class="hs-steps-head">
    <h2><?= hs_h($t['steps_title'] ?? '') ?></h2>
    <p><?= hs_h($t['steps_sub'] ?? '') ?></p>
  </div>
  <div class="hs-steps-grid">
    <?php foreach ($steps as $step): ?>
      <article class="hs-step">
        <div class="hs-step-num"><?= hs_h($step['num']) ?></div>
        <i class="fa-solid <?= hs_h($step['icon']) ?>"></i>
        <h3><?= hs_h($t[$step['title']] ?? '') ?></h3>
        <p><?= hs_h($t[$step['text']] ?? '') ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="hs-landing-highlight">
  <div class="hs-landing-highlight-inner">
    <span class="hs-landing-badge"><?= hs_h($t['landing_badge'] ?? 'New') ?></span>
    <h2><?= hs_h($t['landing_title'] ?? '') ?></h2>
    <p><?= hs_h($t['landing_desc'] ?? '') ?></p>
    <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['landing_cta'] ?? '') ?></a>
  </div>
  <div class="hs-landing-preview" aria-hidden="true">
    <div class="hs-landing-preview-bar"></div>
    <div class="hs-landing-preview-hero"></div>
    <div class="hs-landing-preview-blocks">
      <span></span><span></span><span></span>
    </div>
  </div>
</section>

<section class="hs-planets" id="planets">
  <div class="hs-planets-head">
    <span class="hs-planets-badge"><i class="fa-solid fa-planet-ringed"></i> <?= hs_h($t['planets_badge'] ?? 'BILOHASH Universe') ?></span>
    <h2><?= hs_h($t['planets_title'] ?? '') ?></h2>
    <p class="hs-planets-lead"><?= hs_h($t['planets_lead'] ?? '') ?></p>
    <p class="hs-planets-desc"><?= hs_h($t['planets_desc'] ?? '') ?></p>
  </div>
  <div class="hs-planets-orbit" aria-hidden="true"><span class="hs-planets-core"><i class="fa-solid fa-sun"></i></span></div>
  <div class="hs-planets-grid">
    <?php
    $planetBlurbs = bh_ecosystem_planet_blurbs();
    foreach ($ecoCatalog as $slug => $app):
        $meta = $planetBlurbs[$slug] ?? ['planet' => (string) ($app['short'] ?? $slug), 'tagline_key' => ''];
        $tagKey = (string) ($meta['tagline_key'] ?? '');
    ?>
    <article class="hs-planet-card" style="--planet-color:<?= hs_h((string) ($app['color'] ?? '#059669')) ?>">
      <div class="hs-planet-sphere" aria-hidden="true"></div>
      <div class="hs-planet-body">
        <span class="hs-planet-name"><?= hs_h((string) ($meta['planet'] ?? '')) ?></span>
        <h3><i class="<?= !empty($app['icon_brand']) ? 'fa-brands' : 'fa-solid' ?> fa-<?= hs_h((string) ($app['icon'] ?? 'cube')) ?>"></i> <?= hs_h((string) ($app['short'] ?? $slug)) ?></h3>
        <p><?= hs_h($tagKey !== '' ? ($t[$tagKey] ?? '') : '') ?></p>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="hs-planets-note"><i class="fa-solid fa-gift"></i> <?= hs_h($t['planets_note'] ?? '') ?></p>
</section>

<section class="hs-ecosystem" id="ecosystem">
  <div class="hs-ecosystem-inner">
    <span class="hs-ecosystem-badge"><i class="fa-solid fa-gift"></i> <?= hs_h($t['ecosystem_badge'] ?? 'Included free') ?></span>
    <h2><?= hs_h($t['ecosystem_title'] ?? 'Free BILOHASH CMS ecosystem') ?></h2>
    <p class="hs-ecosystem-lead"><?= hs_h($t['ecosystem_lead'] ?? '') ?></p>
    <p class="hs-ecosystem-desc"><?= hs_h($t['ecosystem_desc'] ?? '') ?></p>
    <div class="hs-ecosystem-apps" aria-label="<?= hs_h($t['ecosystem_apps_label'] ?? 'CMS apps') ?>">
      <?php foreach ($ecoCatalog as $slug => $app): ?>
        <span class="hs-eco-chip" style="--eco-color:<?= hs_h((string) ($app['color'] ?? '#059669')) ?>">
          <i class="<?= !empty($app['icon_brand']) ? 'fa-brands' : 'fa-solid' ?> fa-<?= hs_h((string) ($app['icon'] ?? 'cube')) ?>"></i>
          <?= hs_h((string) ($app['short'] ?? $slug)) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <ul class="hs-ecosystem-perks">
      <?php foreach (['ecosystem_perk_1', 'ecosystem_perk_2', 'ecosystem_perk_3'] as $perkKey): ?>
        <?php if (!empty($t[$perkKey])): ?>
          <li><i class="fa-solid fa-check"></i> <?= hs_h($t[$perkKey]) ?></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
    <a href="<?= hs_h(hs_url('#pricing')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['ecosystem_cta'] ?? 'Choose a plan') ?></a>
  </div>
</section>

<section class="hs-features">
  <h2><?= hs_h($t['features_title'] ?? '') ?></h2>
  <?php
  $feats = [
      ['icon' => 'fa-puzzle-piece', 'title' => $t['feat_ecosystem'] ?? '', 'text' => $t['feat_ecosystem_desc'] ?? ''],
      ['icon' => 'fa-globe', 'title' => $t['feat_domains'] ?? 'Domains', 'text' => $t['feat_domains_desc'] ?? ''],
      ['icon' => 'fa-server', 'title' => $t['feat_hosting'] ?? 'Hosting', 'text' => $t['feat_hosting_desc'] ?? ''],
      ['icon' => 'fa-box-open', 'title' => $t['feat_install'] ?? '', 'text' => $t['feat_install_desc'] ?? ''],
      ['icon' => 'fa-shield-halved', 'title' => $t['feat_secure'] ?? '', 'text' => $t['feat_secure_desc'] ?? ''],
      ['icon' => 'fa-language', 'title' => $t['feat_i18n'] ?? '', 'text' => $t['feat_i18n_desc'] ?? ''],
  ];
  foreach ($feats as $f):
      if (($f['title'] ?? '') === '' && ($f['text'] ?? '') === '') {
          continue;
      }
  ?>
  <article class="hs-feat">
    <i class="fa-solid <?= hs_h($f['icon']) ?>"></i>
    <h3><?= hs_h($f['title']) ?></h3>
    <p><?= hs_h($f['text']) ?></p>
  </article>
  <?php endforeach; ?>
</section>

<section class="hs-pricing" id="pricing">
  <div class="hs-pricing-head">
    <h2><?= hs_h($t['plans_title'] ?? 'Hosting plans') ?></h2>
    <p class="hs-pricing-sub"><?= hs_h($t['plans_sub'] ?? '') ?></p>
    <p class="hs-pricing-eco"><i class="fa-solid fa-gift"></i> <?= hs_h($t['plans_ecosystem_note'] ?? '') ?></p>
  </div>
  <?= hs_render_public_plan_cards($t, $lang) ?>
</section>

<section class="hs-github-cta" id="opensource">
  <div class="hs-github-cta-inner">
    <div>
      <span class="hs-github-badge"><i class="fa-brands fa-github"></i> Open source</span>
      <h2><?= hs_h($t['github_title'] ?? '') ?></h2>
      <p><?= hs_h($t['github_desc'] ?? '') ?></p>
      <ul class="hs-github-list">
        <?php foreach (['github_point_1', 'github_point_2', 'github_point_3'] as $gp): ?>
          <?php if (!empty($t[$gp])): ?><li><i class="fa-solid fa-check"></i> <?= hs_h($t[$gp]) ?></li><?php endif; ?>
        <?php endforeach; ?>
      </ul>
      <p class="hs-github-creds"><code>demo / demo</code> · <code>admin / admin</code></p>
    </div>
    <a href="https://github.com/Ruslan-Bilohash/hosting" class="hs-btn hs-btn-primary" target="_blank" rel="noopener">
      <i class="fa-brands fa-github"></i> <?= hs_h($t['github_btn'] ?? 'View on GitHub') ?>
    </a>
  </div>
</section>

<section class="hs-cta-final">
  <div class="hs-cta-final-inner">
    <h2><?= hs_h($t['cta_final_title'] ?? '') ?></h2>
    <p><?= hs_h($t['cta_final_sub'] ?? '') ?></p>
    <div class="hs-cta-final-actions">
      <a href="<?= hs_h(hs_url('register.php')) ?>" class="hs-btn hs-btn-primary"><?= hs_h($t['cta_final_register'] ?? '') ?></a>
      <a href="<?= hs_h(hs_url('login.php')) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['cta_final_demo'] ?? '') ?></a>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/includes/layout-public.php';