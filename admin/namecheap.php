<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/providers/hostinger-domains.php';
require_once dirname(__DIR__) . '/includes/domain-orders.php';

hs_admin_require();

$provider = hs_domain_registration_provider();
$admin_active = $provider === 'hostinger' ? 'namecheap' : 'namecheap';

$error = '';
$success = '';
$testResult = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif ($provider === 'hostinger' && isset($_POST['test_hostinger'])) {
        $res = hs_hostinger_test_connection(!empty($_POST['refresh_catalog']));
        if ($res['ok']) {
            $success = ($t['admin_hostinger_test_ok'] ?? 'Hostinger API OK')
                . ' — ' . ($res['detail'] ?? '');
        } else {
            $error = ($t['admin_hostinger_test_fail'] ?? 'Hostinger API test failed')
                . ': ' . ($res['detail'] ?? $res['error'] ?? '');
        }
    } elseif ($provider === 'hostinger' && isset($_POST['sync_domains'])) {
        $sync = hs_hostinger_sync_domain_registries();
        if ($sync['ok']) {
            $success = ($t['admin_hostinger_sync_ok'] ?? 'Domain sync complete')
                . ' — ' . (int) ($sync['hi_domains'] ?? 0)
                . ', ' . ($t['admin_hostinger_sync_checked'] ?? 'checked') . ': ' . (int) ($sync['checked'] ?? 0)
                . ', ' . ($t['admin_hostinger_sync_updated'] ?? 'updated') . ': ' . (int) ($sync['updated'] ?? 0);
        } else {
            $error = ($t['admin_hostinger_sync_fail'] ?? 'Domain sync failed')
                . ': ' . ($sync['detail'] ?? $sync['error'] ?? '');
        }
    } elseif ($provider === 'hostinger' && isset($_POST['check_domain'])) {
        $dom = strtolower(trim((string) ($_POST['domain'] ?? '')));
        if ($dom === '') {
            $error = $t['admin_hostinger_domain_empty'] ?? 'Enter a domain';
        } else {
            $check = hs_hostinger_check_domains([$dom]);
            if ($check['ok'] && !empty($check['results'][0])) {
                $row = $check['results'][0];
                $avail = !empty($row['available']);
                $success = $dom . ': ' . ($avail
                    ? ($t['admin_hostinger_available'] ?? 'Available')
                    : ($t['admin_hostinger_taken'] ?? 'Taken'))
                    . ' (Hostinger API)';
                if (($row['restriction'] ?? '') !== '') {
                    $success .= ' — ' . $row['restriction'];
                }
            } else {
                $error = ($t['admin_hostinger_check_fail'] ?? 'Check failed')
                    . ': ' . ($check['detail'] ?? $check['error'] ?? '');
            }
        }
    } elseif ($provider !== 'hostinger') {
        require_once dirname(__DIR__) . '/includes/providers/namecheap-api.php';
        if (isset($_POST['test_namecheap'])) {
            $res = hs_namecheap_test_connection(!empty($_POST['refresh_prices']), true);
            if ($res['ok']) {
                $success = ($t['admin_namecheap_test_ok'] ?? 'Namecheap API OK') . ' — ' . ($res['detail'] ?? '');
            } else {
                $error = ($t['admin_namecheap_test_fail'] ?? 'Namecheap test failed') . ': ' . ($res['detail'] ?? $res['error'] ?? '');
            }
        }
    }
}

$pendingOrders = hs_domain_orders_pending();
$syncCronCmd = hs_domain_sync_cron_command();

if ($provider === 'hostinger') {
    $status = hs_hostinger_domain_status();
    $portfolio = hs_hostinger_domains_list_all();
    $status['portfolio_count'] = count($portfolio);
    $catalog = hs_hostinger_domain_catalog_load();
    if ($catalog === [] && $status['configured']) {
        $ref = hs_hostinger_refresh_domain_catalog(false);
        $catalog = is_array($ref['catalog'] ?? null) ? $ref['catalog'] : [];
    }
    $status['catalog_count'] = count($catalog);
    require_once dirname(__DIR__) . '/includes/providers/namecheap-api.php';
    $markupPct = hs_namecheap_markup_pct();
    $page_title = $t['admin_hostinger_title'] ?? 'Hostinger Domains';
} else {
    require_once dirname(__DIR__) . '/includes/providers/namecheap-api.php';
    $status = hs_namecheap_status();
    $catalog = [];
    $portfolio = [];
    $markupPct = (float) ($status['markup_pct'] ?? 0);
    $page_title = $t['admin_namecheap_title'] ?? 'Namecheap API';
}

ob_start();
?>
<div class="hs-admin-page">
  <nav class="hs-admin-tabs" style="margin-bottom:1.25rem">
    <a href="<?= hs_h(hs_admin_url('namecheap.php')) ?>" class="hs-btn hs-btn-ghost is-active"><i class="fa-solid fa-globe"></i> <?= hs_h($page_title) ?></a>
    <a href="<?= hs_h(hs_admin_url('payments.php')) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-credit-card"></i> <?= hs_h($t['admin_payments_title'] ?? 'Payments') ?></a>
    <a href="<?= hs_h(hs_admin_url('plans.php')) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-layer-group"></i> <?= hs_h($t['admin_plans_title'] ?? 'Plans') ?></a>
    <a href="<?= hs_h(hs_admin_url()) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_title'] ?? 'Admin') ?></a>
  </nav>

  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

  <?php if ($provider === 'hostinger'): ?>

  <div class="hp-card">
    <h2 class="hp-card-title"><?= hs_h($t['admin_hostinger_status'] ?? 'Hostinger API status') ?></h2>
    <ul class="hp-list" style="margin:0 1.25rem 1rem">
      <li><strong><?= hs_h($t['admin_hostinger_configured'] ?? 'API token') ?>:</strong>
        <?= $status['configured'] ? '✅' : '❌' ?>
        <?php if (!$status['configured']): ?>
          <span class="hp-muted"> — <code>data/hostinger.config.php</code></span>
        <?php endif; ?>
      </li>
      <li><strong><?= hs_h($t['admin_hostinger_account'] ?? 'Hosting account') ?>:</strong>
        <code><?= hs_h($status['account'] !== '' ? $status['account'] : '—') ?></code></li>
      <li><strong>API:</strong> <code><?= hs_h($status['api_base']) ?></code></li>
      <li><strong><?= hs_h($t['admin_hostinger_portfolio'] ?? 'Domains in portfolio') ?>:</strong> <?= (int) $status['portfolio_count'] ?></li>
      <li><strong><?= hs_h($t['admin_hostinger_catalog'] ?? 'Cached TLD catalog') ?>:</strong> <?= (int) $status['catalog_count'] ?></li>
      <li><strong><?= hs_h($t['dns_ns_registry'] ?? 'NS (registry)') ?>:</strong> <code><?= hs_h(implode(', ', $status['nameservers'])) ?></code></li>
      <li><strong><?= hs_h($t['dns_ns_panel'] ?? 'NS (panel)') ?>:</strong> <code><?= hs_h(implode(', ', $status['display_nameservers'])) ?></code></li>
      <li><strong><?= hs_h($t['admin_hostinger_server_ip'] ?? 'Server IP (A record)') ?>:</strong> <code><?= hs_h($status['server_ip']) ?></code></li>
      <li><strong><?= hs_h($t['admin_hostinger_markup'] ?? 'Retail markup') ?>:</strong> <?= hs_h((string) $markupPct) ?>%</li>
      <li><strong><?= hs_h($t['admin_domain_orders_title'] ?? 'Pending orders') ?>:</strong> <?= count($pendingOrders) ?></li>
    </ul>
    <form method="post" style="padding:0 1.25rem 1.25rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
      <?= hs_csrf_field() ?>
      <label class="hs-check"><input type="checkbox" name="refresh_catalog" value="1"> <?= hs_h($t['admin_hostinger_refresh_catalog'] ?? 'Refresh TLD catalog from Hostinger') ?></label>
      <button type="submit" name="test_hostinger" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-plug"></i> <?= hs_h($t['admin_hostinger_test_btn'] ?? 'Test API connection') ?></button>
      <button type="submit" name="sync_domains" value="1" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-rotate"></i> <?= hs_h($t['admin_hostinger_sync_btn'] ?? 'Sync domains from Hostinger') ?></button>
    </form>
  </div>

  <div class="hp-card" style="margin-top:1rem">
    <h2 class="hp-card-title"><?= hs_h($t['admin_hostinger_setup_title'] ?? 'Hostinger domain setup') ?></h2>
    <p class="hp-muted" style="margin:0 1.25rem .75rem;font-size:.88rem">
      <?= hs_h($t['admin_hostinger_setup_lead'] ?? 'BILOHASH registers client domains via Hostinger Cloud API after checkout.') ?>
      <a href="https://developers.hostinger.com" target="_blank" rel="noopener">developers.hostinger.com</a>
    </p>
    <ul class="hp-list" style="margin:0 1.25rem 1.25rem">
      <li><?= hs_h($t['admin_hostinger_setup_token'] ?? 'hPanel → Profile → API — paste token into data/hostinger.config.php') ?></li>
      <li><?= hs_h($t['admin_hostinger_setup_whois'] ?? 'hPanel → Domains → WHOIS profiles — default contacts for TLDs used at checkout') ?></li>
      <li><?= hs_h($t['admin_hostinger_setup_payment'] ?? 'Hostinger billing: default payment method is charged when API registers a domain') ?></li>
      <li><?= hs_h($t['admin_hostinger_setup_ns'] ?? 'After registration, NS from host profile are applied automatically') ?></li>
      <li><?= hs_h($t['admin_hostinger_setup_hpanel'] ?? 'Clients link domains in panel → Parked domains; document root folder is shown per domain') ?></li>
      <li><?= hs_h($t['admin_hostinger_setup_sync'] ?? 'Weekly cron syncs expiry dates from Hostinger portfolio into client panels') ?></li>
    </ul>
    <?php if ($syncCronCmd !== ''): ?>
    <div style="padding:0 1.25rem 1.25rem">
      <p class="hp-muted" style="font-size:.85rem;margin:0 0 .5rem"><?= hs_h($t['admin_hostinger_cron_hint'] ?? 'Cron (once a week):') ?></p>
      <pre style="margin:0;padding:.75rem 1rem;background:var(--hs-surface-2,#f4f4f5);border-radius:.5rem;font-size:.8rem;overflow:auto"><code><?= hs_h($syncCronCmd) ?></code></pre>
    </div>
    <?php else: ?>
    <p class="hp-muted" style="padding:0 1.25rem 1.25rem;font-size:.85rem">
      <?= hs_h($t['admin_hostinger_cron_token'] ?? 'Set HS_DOMAIN_SYNC_TOKEN in config.local.php to enable the weekly cron command.') ?>
    </p>
    <?php endif; ?>
  </div>

  <?php if ($catalog !== []): ?>
  <div class="hp-card" style="margin-top:1rem">
    <h2 class="hp-card-title"><?= hs_h($t['admin_hostinger_price_table'] ?? 'Hostinger domain catalog (wholesale EUR/year)') ?></h2>
    <div class="hs-table-wrap" style="margin:0 1.25rem 1.25rem;max-height:360px;overflow:auto">
      <table class="hs-table"><thead><tr><th>TLD</th><th><?= hs_h($t['admin_hostinger_price'] ?? 'Wholesale') ?></th><th><?= hs_h($t['admin_hostinger_retail'] ?? 'Retail') ?></th></tr></thead><tbody>
        <?php
        $rows = $catalog;
        ksort($rows);
        foreach ($rows as $tld => $row):
          $wholesale = (float) ($row['price_eur'] ?? 0);
          $retail = round($wholesale * (1 + $markupPct / 100), 2);
        ?>
        <tr><td><code>.<?= hs_h((string) $tld) ?></code></td><td>€<?= hs_h(number_format($wholesale, 2)) ?></td><td>€<?= hs_h(number_format($retail, 2)) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($portfolio !== []): ?>
  <div class="hp-card" style="margin-top:1rem">
    <h2 class="hp-card-title"><?= hs_h($t['admin_hostinger_portfolio_title'] ?? 'Hostinger domain portfolio') ?></h2>
    <div class="hs-table-wrap" style="margin:0 1.25rem 1.25rem;max-height:320px;overflow:auto">
      <table class="hs-table"><thead><tr><th><?= hs_h($t['admin_hostinger_col_domain'] ?? 'Domain') ?></th><th><?= hs_h($t['admin_hostinger_col_status'] ?? 'Status') ?></th><th><?= hs_h($t['admin_hostinger_col_expires'] ?? 'Expires') ?></th></tr></thead><tbody>
        <?php foreach ($portfolio as $row): ?>
        <tr>
          <td><code><?= hs_h((string) ($row['domain'] ?? '')) ?></code></td>
          <td><?= hs_h((string) ($row['status'] ?? '—')) ?></td>
          <td><?= hs_h((string) (($row['expires'] ?? '') !== '' ? $row['expires'] : '—')) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <?php endif; ?>

  <div class="hp-card" style="margin-top:1rem">
    <h2 class="hp-card-title"><?= hs_h($t['admin_hostinger_check_title'] ?? 'Check domain availability') ?></h2>
    <form method="post" class="hp-inline-form" style="padding:0 1.25rem 1.25rem">
      <?= hs_csrf_field() ?>
      <div class="hs-field"><label><?= hs_h($t['admin_hostinger_domain_label'] ?? 'Domain') ?></label>
        <input type="text" name="domain" placeholder="example.com" required></div>
      <button type="submit" name="check_domain" value="1" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_hostinger_check_btn'] ?? 'Check via Hostinger') ?></button>
    </form>
  </div>

  <p class="hp-muted" style="margin-top:1rem;font-size:.85rem">
    <?= hs_h($t['admin_hostinger_flow'] ?? 'Flow: client pays → domain registers at Hostinger API → NS applied → panel activates domain and shows hPanel folder.') ?>
  </p>

  <?php else: ?>
  <div class="hp-card">
    <p class="hp-muted" style="padding:1.25rem">
      <?= hs_h($t['admin_hostinger_not_active'] ?? 'This host uses Namecheap API. Configure data/hostinger.config.php and manual_domain_dns host profile for Hostinger registration.') ?>
    </p>
    <p style="padding:0 1.25rem 1.25rem">
      <a href="https://www.namecheap.com/support/knowledgebase/article/9391/27/reseller-hosting-getting-started/" target="_blank" rel="noopener" class="hs-btn hs-btn-ghost">Namecheap reseller KB</a>
    </p>
  </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';