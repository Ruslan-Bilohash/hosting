<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

hs_admin_require();
$admin_active = 'tools';

$health = null;
if (is_file(dirname(__DIR__) . '/includes/admin-server-health.php')) {
    require_once dirname(__DIR__) . '/includes/admin-server-health.php';
    if (function_exists('hs_admin_server_health_snapshot')) {
        try {
            $health = hs_admin_server_health_snapshot();
        } catch (Throwable $e) {
            $health = ['error' => $e->getMessage()];
        }
    }
}

$page_title = $t['admin_tools_title'] ?? 'API · Debug · Testers';
ob_start();

$cards = [
    [
        'href' => hs_admin_url('payments.php'),
        'icon' => 'fa-credit-card',
        'title' => $t['admin_payments_title'] ?? 'Payments',
        'desc' => $t['admin_tools_payments_desc'] ?? 'Stripe / PayPal keys, test connection, webhook URL, demo checkout.',
    ],
    [
        'href' => hs_admin_url('namecheap.php'),
        'icon' => 'fa-globe',
        'title' => $t['admin_namecheap_title'] ?? 'Domains API',
        'desc' => $t['admin_tools_domains_desc'] ?? 'Namecheap / Hostinger API test, domain check, portfolio sync.',
    ],
    [
        'href' => hs_admin_url('mysql.php'),
        'icon' => 'fa-database',
        'title' => $t['admin_mysql_title'] ?? 'MySQL',
        'desc' => $t['admin_tools_mysql_desc'] ?? 'Provision config, CREATE DATABASE test, provision all clients.',
    ],
    [
        'href' => hs_admin_url('pma-tool.php'),
        'icon' => 'fa-table',
        'title' => $t['admin_pma_title'] ?? 'phpMyAdmin',
        'desc' => $t['admin_tools_pma_desc'] ?? 'Open phpMyAdmin, deploy package to /pma if missing, sign-on with provisioned credentials.',
    ],
    [
        'href' => hs_admin_url('cpanel-pool.php'),
        'icon' => 'fa-server',
        'title' => $t['admin_cpanel_pool_title'] ?? 'cPanel / WHM',
        'desc' => $t['admin_tools_cpanel_desc'] ?? 'WHM API, packages map, account pool limits.',
    ],
    [
        'href' => hs_admin_url('invoices.php'),
        'icon' => 'fa-file-invoice-dollar',
        'title' => $t['admin_invoices_title'] ?? 'Invoices',
        'desc' => $t['admin_tools_invoices_desc'] ?? 'Create, mark paid, cancel, refund invoices for any client.',
    ],
    [
        'href' => hs_admin_url('files.php'),
        'icon' => 'fa-folder-open',
        'title' => $t['admin_files_title'] ?? 'Server files',
        'desc' => $t['admin_tools_files_desc'] ?? 'Browse server / public_html files as operator.',
    ],
    [
        'href' => hs_admin_url('debugger.php'),
        'icon' => 'fa-bug',
        'title' => $t['admin_debugger_title'] ?? 'Site debugger',
        'desc' => $t['admin_tools_debugger_desc'] ?? 'Full site scan: structure, PHP files, public URLs, APIs, error store, save/download reports.',
    ],
    [
        'href' => hs_admin_url('server-health-api.php'),
        'icon' => 'fa-heart-pulse',
        'title' => $t['admin_health_title'] ?? 'Server health API',
        'desc' => $t['admin_tools_health_desc'] ?? 'JSON health endpoint for monitoring (auth required).',
    ],
    [
        'href' => hs_admin_url('namecheap-test-once.php'),
        'icon' => 'fa-vial',
        'title' => $t['admin_nc_test_title'] ?? 'Namecheap deep test',
        'desc' => $t['admin_tools_nc_test_desc'] ?? 'One-shot Namecheap API diagnostics.',
    ],
    [
        'href' => hs_admin_url('stripe-domain-test-once.php'),
        'icon' => 'fa-vial-circle-check',
        'title' => $t['admin_stripe_domain_test'] ?? 'Stripe + domain test',
        'desc' => $t['admin_tools_stripe_test_desc'] ?? 'Checkout / domain payment path tester.',
    ],
    [
        'href' => hs_admin_url('pricing-audit-once.php'),
        'icon' => 'fa-magnifying-glass-chart',
        'title' => $t['admin_pricing_audit'] ?? 'Pricing audit',
        'desc' => $t['admin_tools_pricing_desc'] ?? 'Audit plan/domain markup and catalog prices.',
    ],
    [
        'href' => hs_admin_url('refresh-domain-prices.php'),
        'icon' => 'fa-arrows-rotate',
        'title' => $t['admin_refresh_prices'] ?? 'Refresh domain prices',
        'desc' => $t['admin_tools_refresh_prices_desc'] ?? 'Pull fresh TLD prices from registrar API.',
    ],
    [
        'href' => hs_admin_url('ops-guide.php'),
        'icon' => 'fa-book',
        'title' => $t['admin_ops_guide_title'] ?? 'Ops guide',
        'desc' => $t['admin_tools_ops_desc'] ?? 'Operator runbook: domains, payments, clients, support.',
    ],
    [
        'href' => hs_admin_url('settings.php'),
        'icon' => 'fa-gear',
        'title' => $t['admin_settings_title'] ?? 'Site settings',
        'desc' => $t['admin_tools_settings_desc'] ?? 'Pre-launch banner, indexing / robots mode.',
    ],
    [
        'href' => hs_admin_url('domain-sync-cron.php'),
        'icon' => 'fa-clock',
        'title' => $t['admin_domain_sync_cron'] ?? 'Domain sync cron',
        'desc' => $t['admin_tools_domain_cron_desc'] ?? 'Manual trigger for domain registry sync job.',
    ],
    [
        'href' => hs_admin_url('coupons.php'),
        'icon' => 'fa-ticket',
        'title' => $t['admin_coupons_title'] ?? 'Coupons',
        'desc' => $t['admin_tools_coupons_desc'] ?? 'Discount codes for plans and domains.',
    ],
];

// Presence checks so the hub shows broken links clearly
$missingTools = [];
foreach ($cards as $card) {
    $href = (string) ($card['href'] ?? '');
    $script = basename(parse_url($href, PHP_URL_PATH) ?: '');
    if ($script !== '' && !is_file(__DIR__ . '/' . $script)) {
        $missingTools[] = $script;
    }
}
?>
<div class="hs-admin-page">
  <h1 style="margin:0 0 .35rem"><i class="fa-solid fa-screwdriver-wrench"></i> <?= hs_h($page_title) ?></h1>
  <p class="hp-muted" style="margin:0 0 1.25rem"><?= hs_h($t['admin_tools_lead'] ?? 'All Solaskinner operator tools: payment APIs, domain APIs, MySQL, invoices, health checks and one-shot testers.') ?></p>

  <?php if ($missingTools !== []): ?>
    <div class="hs-alert hs-alert-error" style="margin-bottom:1rem">
      <?= hs_h($t['admin_tools_missing'] ?? 'Missing tool files on server') ?>:
      <code><?= hs_h(implode(', ', $missingTools)) ?></code>
    </div>
  <?php endif; ?>

  <?php if (is_array($health) && empty($health['error'])): ?>
    <section class="hp-card" style="margin-bottom:1.25rem">
      <h2 class="hp-card-title"><i class="fa-solid fa-heart-pulse"></i> <?= hs_h($t['admin_health_snapshot'] ?? 'Live health snapshot') ?></h2>
      <div class="hp-card-body">
        <pre class="hs-admin-json" style="margin:0;max-height:220px;overflow:auto;font-size:.78rem;background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:10px"><?= hs_h(json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?></pre>
      </div>
    </section>
  <?php elseif (is_array($health) && !empty($health['error'])): ?>
    <div class="hs-alert hs-alert-error"><?= hs_h((string) $health['error']) ?></div>
  <?php endif; ?>

  <div class="hs-admin-tools-grid">
    <?php foreach ($cards as $card): ?>
      <a class="hs-admin-tool-card" href="<?= hs_h($card['href']) ?>">
        <div class="hs-admin-tool-icon"><i class="fa-solid <?= hs_h($card['icon']) ?>"></i></div>
        <div>
          <strong><?= hs_h((string) $card['title']) ?></strong>
          <p><?= hs_h((string) $card['desc']) ?></p>
        </div>
        <i class="fa-solid fa-chevron-right hs-admin-tool-chevron"></i>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
