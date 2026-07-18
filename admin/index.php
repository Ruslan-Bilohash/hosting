<?php
declare(strict_types=1);

$load_charts = true;
require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/plans.php';
require_once dirname(__DIR__) . '/includes/user-settings.php';
require_once dirname(__DIR__) . '/includes/resource-usage.php';
require_once dirname(__DIR__) . '/includes/domain-store.php';
require_once dirname(__DIR__) . '/includes/domain-orders.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';
require_once dirname(__DIR__) . '/includes/admin-nav.php';

hs_seed_demo_data();
hs_admin_require();

try {
    hs_usage_track_all_clients();
} catch (Throwable $e) {
    error_log('admin/index usage track: ' . $e->getMessage());
}

$users = hs_users();
$sites = hs_sites();
$clientsData = hs_usage_all_clients_data(30);

$totalDiskMb = 0.0;
$totalSites = 0;
foreach ($clientsData as $row) {
    $totalDiskMb += (float) ($row['resources']['storage_used_mb'] ?? 0);
    $totalSites += count($row['sites']);
}

$masterLabels = [];
$masterClients = [];
$clientCards = '';
$colors = ['#059669', '#2563eb', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#4f46e5', '#ea580c'];

if ($clientsData !== []) {
    $masterLabels = $clientsData[0]['series']['labels'] ?? [];
}

foreach ($clientsData as $idx => $row) {
    $u = $row['user'];
    $res = $row['resources'];
    $series = $row['series'];
    $uid = (string) ($u['id'] ?? '');
    $uname = (string) ($u['username'] ?? '');
    $sparkId = 'admin-spark-' . preg_replace('/[^a-z0-9]/i', '', $uid);
    $diskMb = round((float) ($res['storage_used_mb'] ?? 0), 1);
    $diskGb = hs_format_disk_gb($diskMb);
    $plan = (string) ($u['plan'] ?? 'starter');
    $siteCnt = count($row['sites']);
    $color = $colors[$idx % count($colors)];

    $masterClients[] = [
        'name' => $uname,
        'disk' => $series['disk'] ?? [],
        'sparkId' => $sparkId,
        'spark' => ['labels' => $series['labels'] ?? [], 'disk' => $series['disk'] ?? []],
    ];

    $clientCards .= '<article class="hs-admin-client-card" style="--client-color:' . hs_h($color) . '">'
        . '<header class="hs-admin-client-head">'
        . '<div><strong>' . hs_h($uname) . '</strong><span class="hp-muted">' . hs_h((string) ($u['email'] ?? '')) . '</span></div>'
        . '<span class="hs-admin-plan-badge">' . hs_h($plan) . '</span>'
        . '</header>'
        . '<div class="hs-admin-client-stats">'
        . '<div><span class="label">' . hs_h($t['admin_disk'] ?? 'Disk') . '</span><strong>' . hs_h($diskGb) . ' GB</strong></div>'
        . '<div><span class="label">' . hs_h($t['admin_sites'] ?? 'Sites') . '</span><strong>' . $siteCnt . '</strong></div>'
        . '<div><span class="label">CPU</span><strong>' . hs_h((string) round((float) ($res['cpu_percent'] ?? 0))) . '%</strong></div>'
        . '</div>'
        . '<div class="hs-admin-spark-wrap"><canvas id="' . hs_h($sparkId) . '"></canvas></div>'
        . '<footer class="hs-admin-client-foot"><span class="hp-muted">' . hs_h($t['admin_chart_30d'] ?? '30 days disk (MB)') . '</span></footer>'
        . '</article>';
}

$domainOrders = hs_domain_orders_pending();
$domainOrdersRows = '';
foreach ($domainOrders as $order) {
    $price = isset($order['price']) ? hs_domain_format_price((float) $order['price'], $lang) : '—';
    $domainOrdersRows .= '<tr>'
        . '<td><code>' . hs_h((string) ($order['domain'] ?? '')) . '</code></td>'
        . '<td>' . hs_h((string) ($order['username'] ?? '')) . '</td>'
        . '<td>' . hs_h((string) ($order['email'] ?? '')) . '</td>'
        . '<td>' . hs_h($price) . '</td>'
        . '<td>' . hs_h(hs_format_date((string) ($order['ordered_at'] ?? ''))) . '</td>'
        . '<td><span class="hs-dom-status hs-dom-status-pending_registration">' . hs_h($t['admin_domain_orders_status_pending'] ?? 'Pending') . '</span></td>'
        . '</tr>';
}

$chartJson = json_encode([
    'labels' => $masterLabels,
    'clients' => $masterClients,
    'i18n' => ['disk' => $t['admin_chart_all_disk'] ?? 'Disk usage all clients (MB)'],
], JSON_UNESCAPED_UNICODE);

$page_title = $t['admin_title'] ?? '';
$admin_nav_active = 'dashboard';
ob_start();
?>
<div class="hs-admin-page">
  <div class="hs-grid hs-grid-4" style="margin-bottom:1.5rem">
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_clients'] ?? '') ?></div><div class="value"><?= count($users) ?></div></div>
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_sites'] ?? '') ?></div><div class="value"><?= $totalSites ?></div></div>
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_total_disk'] ?? 'Total disk') ?></div><div class="value" style="font-size:1.35rem"><?= hs_h(hs_format_disk_gb($totalDiskMb)) ?> GB</div></div>
    <div class="hs-stat"><div class="label">Version</div><div class="value" style="font-size:1.25rem"><?= hs_h(hs_version_label()) ?></div></div>
  </div>

  <section class="hp-card" style="margin-bottom:1.25rem">
    <h2 class="hp-card-title"><i class="fa-solid fa-bolt"></i> <?= hs_h($t['admin_quick_tools'] ?? 'Quick tools') ?></h2>
    <div class="hp-card-body" style="display:flex;flex-wrap:wrap;gap:.5rem">
      <a class="hs-btn hs-btn-primary hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('tools.php')) ?>"><i class="fa-solid fa-screwdriver-wrench"></i> <?= hs_h($t['admin_tools_title'] ?? 'API & tools') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('invoices.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> <?= hs_h($t['admin_invoices_title'] ?? 'Invoices') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('payments.php')) ?>"><i class="fa-solid fa-credit-card"></i> <?= hs_h($t['admin_payments_title'] ?? 'Payments') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('namecheap.php')) ?>"><i class="fa-solid fa-globe"></i> <?= hs_h($t['admin_namecheap_title'] ?? 'Domains API') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('clients.php')) ?>"><i class="fa-solid fa-users"></i> <?= hs_h($t['admin_clients'] ?? 'Clients') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('support.php')) ?>"><i class="fa-solid fa-headset"></i> <?= hs_h($t['admin_support_title'] ?? 'Support') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('mysql.php')) ?>"><i class="fa-solid fa-database"></i> <?= hs_h($t['admin_mysql_title'] ?? 'MySQL') ?></a>
      <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('settings.php')) ?>"><i class="fa-solid fa-gear"></i> <?= hs_h($t['admin_settings_title'] ?? 'Settings') ?></a>
    </div>
  </section>

  <section class="hp-card hs-chart-card" style="margin-bottom:1.5rem">
    <h2 class="hp-card-title"><?= hs_h($t['admin_chart_all_title'] ?? 'All clients — disk usage') ?></h2>
    <div class="hp-card-body">
      <p class="hp-muted hs-chart-hint"><?= hs_h($t['admin_chart_all_hint'] ?? '') ?></p>
      <div class="hs-chart-wrap" style="height:320px"><canvas id="admin-chart-all-disk"></canvas></div>
    </div>
  </section>

  <h2 style="margin-bottom:1rem"><?= hs_h($t['admin_clients_charts'] ?? 'Client usage charts') ?></h2>
  <?php if ($clientCards === ''): ?>
    <p class="hp-muted"><?= hs_h($t['admin_no_clients'] ?? 'No clients yet.') ?></p>
  <?php else: ?>
    <div class="hs-admin-clients-grid"><?= $clientCards ?></div>
  <?php endif; ?>

  <h2 style="margin:2rem 0 1rem"><?= hs_h($t['admin_domain_orders_title'] ?? 'Domain orders') ?></h2>
  <?php if ($domainOrdersRows === ''): ?>
    <p class="hp-muted"><?= hs_h($t['admin_domain_orders_empty'] ?? 'No pending domain orders.') ?></p>
  <?php else: ?>
    <div class="hs-table-wrap" style="margin-bottom:2rem">
      <table class="hs-table">
        <thead><tr>
          <th><?= hs_h($t['admin_domain_orders_col_domain'] ?? 'Domain') ?></th>
          <th><?= hs_h($t['admin_domain_orders_col_client'] ?? 'Client') ?></th>
          <th>Email</th>
          <th><?= hs_h($t['admin_domain_orders_col_price'] ?? 'Price') ?></th>
          <th><?= hs_h($t['admin_domain_orders_col_ordered'] ?? 'Ordered') ?></th>
          <th><?= hs_h($t['admin_domain_orders_col_status'] ?? 'Status') ?></th>
        </tr></thead>
        <tbody><?= $domainOrdersRows ?></tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2 style="margin:2rem 0 1rem"><?= hs_h($t['admin_clients'] ?? '') ?></h2>
  <p class="hp-muted" style="margin-bottom:1rem"><?= hs_h($t['admin_clients_impersonate_hint'] ?? '') ?></p>
  <div class="hs-table-wrap">
    <table class="hs-table">
      <thead><tr>
          <th><?= hs_h($t['admin_col_user'] ?? 'User') ?></th>
          <th><?= hs_h($t['admin_col_email'] ?? 'Email') ?></th>
          <th><?= hs_h($t['admin_col_plan'] ?? 'Plan') ?></th>
          <th><?= hs_h($t['admin_disk'] ?? 'Disk') ?></th>
          <th><?= hs_h($t['admin_col_sites'] ?? 'Sites') ?></th>
          <th><?= hs_h($t['admin_col_created'] ?? 'Created') ?></th>
          <th></th>
        </tr></thead>
      <tbody>
      <?php foreach ($clientsData as $row):
        $u = $row['user'];
        $res = $row['resources'];
        $cnt = count($row['sites']);
        $uid = (string) ($u['id'] ?? '');
        $uname = hs_client_display_name($u);
      ?>
        <tr>
          <td><?= hs_h($u['username'] ?? '') ?></td>
          <td><?= hs_h($u['email'] ?? '') ?></td>
          <td><?= hs_h($u['plan'] ?? '') ?></td>
          <td><?= hs_h(hs_format_disk_gb((float) ($res['storage_used_mb'] ?? 0))) ?> GB</td>
          <td><?= $cnt ?></td>
          <td><?= hs_h(hs_format_date((string) ($u['created_at'] ?? ''))) ?></td>
          <td>
            <form method="post" action="<?= hs_h(hs_admin_url('impersonate.php')) ?>" style="margin:0">
              <?= hs_csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= hs_h($uid) ?>">
              <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm">
                <?= hs_h(str_replace('{name}', $uname, $t['clients_manage_as'] ?? 'Manage as {name}')) ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>window.HS_ADMIN_USAGE_CHARTS = <?= $chartJson ?>;</script>
<script src="<?= hs_h(hs_asset('js/resource-charts.js')) ?>"></script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';