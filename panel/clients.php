<?php
declare(strict_types=1);

$panel_active = 'clients';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin-clients.php';
require_once dirname(__DIR__) . '/includes/plan-catalog.php';
require_once dirname(__DIR__) . '/includes/invoice-ui.php';
require_once dirname(__DIR__) . '/includes/client-identity.php';

if (!$hs_is_platform_admin || hs_impersonation_active()) {
    hs_redirect('panel/');
}

$page_title = $t['nav_clients'] ?? 'Clients';
$panel_tip_key = 'clients';
$error = '';
$success = '';
$editId = trim((string) ($_GET['edit'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$filter = trim((string) ($_GET['filter'] ?? 'all'));
$sort = trim((string) ($_GET['sort'] ?? 'name'));
$allowedFilters = ['all', 'active', 'pending', 'suspended'];
$allowedSorts = ['name', 'plan', 'paid', 'disk', 'sites', 'created'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'name';
}

$listQuery = static function (array $extra = []) use ($q, $filter, $sort, $editId): array {
    $params = array_filter([
        'q' => $q,
        'filter' => $filter !== 'all' ? $filter : '',
        'sort' => $sort !== 'name' ? $sort : '',
        'edit' => $editId,
    ], static fn($v): bool => $v !== null && $v !== '');
    return array_merge($params, $extra);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } else {
        $uid = trim((string) ($_POST['user_id'] ?? ''));
        $res = hs_admin_client_update($uid, [
            'plan' => $_POST['plan'] ?? '',
            'subscription_status' => $_POST['subscription_status'] ?? '',
            'paid_until' => $_POST['paid_until'] ?? '',
            'admin_notes' => $_POST['admin_notes'] ?? '',
            'plan_services' => $_POST['plan_services'] ?? [],
            'lang' => $lang,
        ]);
        if (!empty($res['ok'])) {
            $success = $t['admin_client_saved'] ?? 'Updated';
            $editId = $uid;
        } else {
            $error = match ($res['error'] ?? '') {
                'not_found' => $t['admin_client_not_found'] ?? 'Client not found',
                'invalid_plan' => $t['admin_plans_invalid_id'] ?? 'Invalid plan',
                default => $t['admin_plans_save_fail'] ?? 'Save failed',
            };
        }
    }
}

$statusCounts = ['all' => 0, 'active' => 0, 'pending' => 0, 'suspended' => 0];
$rows = [];
foreach (hs_users() as $u) {
    if (!is_array($u)) {
        continue;
    }
    $st = (string) ($u['subscription_status'] ?? 'active');
    $statusCounts['all']++;
    if (isset($statusCounts[$st])) {
        $statusCounts[$st]++;
    }
    if (!hs_admin_client_matches_filters($u, $q, $filter)) {
        continue;
    }
    $rows[] = hs_admin_client_row_data($u);
}
$rows = hs_admin_client_sort_rows($rows, $sort);

$editUser = $editId !== '' ? hs_user_by_id($editId) : null;
$catalog = hs_plan_catalog_load();
$allServices = hs_plan_catalog_services(false);
$userServiceIds = is_array($editUser['plan_services'] ?? null) ? $editUser['plan_services'] : [];

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<div class="hs-clients-page">
  <div class="hs-clients-intro">
    <p class="hp-muted"><?= hs_h($t['clients_admin_hint'] ?? '') ?></p>
    <div class="hs-clients-stats">
      <div class="hs-stat"><div class="label"><?= hs_h($t['clients_stats_total'] ?? '') ?></div><div class="value"><?= (int) $statusCounts['all'] ?></div></div>
      <div class="hs-stat"><div class="label"><?= hs_h($t['plan_status_active'] ?? 'Active') ?></div><div class="value"><?= (int) $statusCounts['active'] ?></div></div>
      <div class="hs-stat"><div class="label"><?= hs_h($t['plan_status_pending'] ?? '') ?></div><div class="value"><?= (int) $statusCounts['pending'] ?></div></div>
      <div class="hs-stat"><div class="label"><?= hs_h($t['plan_status_suspended'] ?? '') ?></div><div class="value"><?= (int) $statusCounts['suspended'] ?></div></div>
    </div>
  </div>

  <div class="hs-admin-split hs-clients-panel">
    <section class="hp-card hs-clients-list-card">
      <h2 class="hp-card-title"><?= hs_h($t['nav_clients'] ?? '') ?> <span class="hs-clients-count">(<?= count($rows) ?>)</span></h2>
      <div class="hp-card-body">
        <form method="get" class="hs-clients-toolbar">
          <?php if ($editId !== ''): ?><input type="hidden" name="edit" value="<?= hs_h($editId) ?>"><?php endif; ?>
          <div class="hs-admin-search">
            <input type="search" name="q" value="<?= hs_h($q) ?>" placeholder="<?= hs_h($t['admin_clients_search'] ?? 'Search…') ?>" autocomplete="off">
            <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm" title="<?= hs_h($t['clients_search_btn'] ?? 'Search') ?>"><i class="fa-solid fa-search"></i></button>
            <?php if ($q !== '' || $filter !== 'all' || $sort !== 'name'): ?>
            <a href="<?= hs_h(hs_url(hs_panel_path('clients.php'), $editId !== '' ? ['edit' => $editId] : [])) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="<?= hs_h($t['clients_clear_filters'] ?? 'Clear') ?>"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
          </div>
          <div class="hs-clients-filters" role="tablist" aria-label="<?= hs_h($t['admin_client_status'] ?? 'Status') ?>">
            <?php foreach ($allowedFilters as $f):
              $active = $filter === $f;
              $params = $listQuery(['filter' => $f !== 'all' ? $f : '']);
              if ($f === 'all') {
                  unset($params['filter']);
              }
            ?>
            <a href="<?= hs_h(hs_url(hs_panel_path('clients.php'), $params)) ?>" class="hs-clients-filter<?= $active ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $active ? 'true' : 'false' ?>">
              <?= hs_h(match ($f) {
                  'active' => $t['plan_status_active'] ?? 'Active',
                  'pending' => $t['plan_status_pending'] ?? 'Pending',
                  'suspended' => $t['plan_status_suspended'] ?? 'Suspended',
                  default => $t['clients_filter_all'] ?? 'All',
              }) ?>
              <span class="hs-clients-filter-n"><?= (int) ($statusCounts[$f] ?? 0) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
          <div class="hs-clients-sort">
            <label for="hs-clients-sort"><?= hs_h($t['clients_sort_label'] ?? 'Sort') ?></label>
            <select id="hs-clients-sort" name="sort" onchange="this.form.submit()">
              <?php
              $sortOptions = [
                  'name' => $t['clients_sort_name'] ?? 'Name',
                  'plan' => $t['account_plan'] ?? 'Plan',
                  'paid' => $t['plan_renews'] ?? 'Paid until',
                  'disk' => $t['admin_disk'] ?? 'Disk',
                  'sites' => $t['panel_websites'] ?? 'Sites',
                  'created' => $t['admin_client_created'] ?? 'Created',
              ];
              foreach ($sortOptions as $sid => $slabel):
              ?>
              <option value="<?= hs_h($sid) ?>"<?= $sort === $sid ? ' selected' : '' ?>><?= hs_h($slabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <p class="hs-clients-hint hp-muted"><i class="fa-solid fa-hand-pointer"></i> <?= hs_h($t['clients_click_row'] ?? '') ?></p>

        <div class="hs-table-wrap hs-clients-table-wrap">
          <table class="hs-table hs-admin-clients-table">
            <thead><tr>
              <th><?= hs_h($t['account_username'] ?? '') ?></th>
              <th><?= hs_h($t['support_client_id'] ?? 'Client ID') ?></th>
              <th><?= hs_h($t['account_plan'] ?? '') ?></th>
              <th><?= hs_h($t['admin_client_status'] ?? '') ?></th>
              <th><?= hs_h($t['plan_renews'] ?? '') ?></th>
              <th><?= hs_h($t['admin_disk'] ?? '') ?></th>
              <th><?= hs_h($t['panel_websites'] ?? '') ?></th>
              <th><span class="sr-only"><?= hs_h($t['clients_col_actions'] ?? 'Actions') ?></span></th>
            </tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
              <tr><td colspan="8" class="hs-clients-empty"><?= hs_h($t['clients_empty'] ?? 'No clients match your search.') ?></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row):
              $u = $row['user'];
              $res = $row['resources'];
              $uid = (string) ($u['id'] ?? '');
              $name = hs_client_display_name($u);
              $status = (string) ($u['subscription_status'] ?? 'active');
              $statusLabel = hs_admin_client_status_label($t, $status);
              $planId = (string) ($u['plan'] ?? 'starter');
              $paidRaw = (string) ($u['paid_until'] ?? '');
              $paidDisplay = $paidRaw !== '' ? date('Y-m-d', strtotime($paidRaw)) : ($t['plan_no_paid_date'] ?? '—');
              $paidClass = hs_admin_client_paid_class($paidRaw !== '' ? $paidRaw : null);
              $editUrl = hs_url(hs_panel_path('clients.php'), $listQuery(['edit' => $uid]));
              $clientNum = hs_client_number($u);
            ?>
              <tr class="hs-clients-row<?= $uid === $editId ? ' is-selected' : '' ?>" data-href="<?= hs_h($editUrl) ?>" tabindex="0" role="button" aria-label="<?= hs_h(str_replace('{name}', $name, $t['clients_edit_row'] ?? 'Edit {name}')) ?>">
                <td class="hs-clients-user-cell">
                  <strong><?= hs_h($name) ?></strong>
                  <span class="hs-clients-meta"><?= hs_h($u['email'] ?? '') ?></span>
                  <code class="hs-clients-login"><?= hs_h($u['username'] ?? '') ?></code>
                </td>
                <td><?= $clientNum !== '' ? '<code>' . hs_h($clientNum) . '</code>' : '<span class="hp-muted">—</span>' ?></td>
                <td><?= hs_h($t['plan_' . $planId] ?? $planId) ?></td>
                <td><span class="hs-plan-status hs-plan-status-<?= hs_h($status) ?>"><?= hs_h($statusLabel) ?></span></td>
                <td><span class="hs-clients-paid <?= hs_h($paidClass) ?>"><?= hs_h($paidDisplay) ?></span></td>
                <td><?= hs_h(hs_format_disk_gb((float) ($res['storage_used_mb'] ?? 0))) ?> GB</td>
                <td><?= (int) $row['site_count'] ?></td>
                <td class="hs-admin-row-actions hs-clients-actions" data-stop-row="1">
                  <a href="<?= hs_h($editUrl) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="<?= hs_h($t['admin_edit'] ?? 'Edit') ?>"><i class="fa-solid fa-pen"></i><span class="hs-clients-btn-text"><?= hs_h($t['admin_edit'] ?? '') ?></span></a>
                  <form method="post" action="<?= hs_h(hs_url(hs_panel_path('impersonate.php'))) ?>">
                    <?= hs_csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= hs_h($uid) ?>">
                    <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm" title="<?= hs_h(str_replace('{name}', $name, $t['clients_manage_as'] ?? '')) ?>"><i class="fa-solid fa-user-secret"></i><span class="hs-clients-btn-text"><?= hs_h($t['clients_manage_short'] ?? 'Manage') ?></span></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="hp-card hs-admin-form-card hs-clients-edit-card" id="hs-client-edit">
      <h2 class="hp-card-title"><?= hs_h($editUser ? ($t['admin_edit_client'] ?? 'Edit') : ($t['admin_select_client'] ?? 'Select client')) ?></h2>
      <div class="hp-card-body">
        <?php if ($editUser === null): ?>
          <div class="hs-clients-placeholder">
            <i class="fa-solid fa-users" aria-hidden="true"></i>
            <p class="hp-muted"><?= hs_h($t['admin_select_client_hint'] ?? '') ?></p>
          </div>
        <?php else:
          $paidVal = !empty($editUser['paid_until']) ? date('Y-m-d', strtotime((string) $editUser['paid_until'])) : '';
          $editName = hs_client_display_name($editUser);
          $editNum = hs_client_number($editUser);
          $editSupport = hs_client_support_email($editUser);
        ?>
          <div class="hs-admin-client-summary hs-clients-summary">
            <div class="hs-clients-avatar" aria-hidden="true"><?= hs_h(mb_strtoupper(mb_substr($editName, 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
            <div>
              <strong class="hs-clients-summary-name"><?= hs_h($editName) ?></strong>
              <span><?= hs_h((string) ($editUser['email'] ?? '')) ?></span>
              <?php if ($editNum !== ''): ?><span class="hp-muted"><?= hs_h($t['support_client_id'] ?? '') ?>: <code><?= hs_h($editNum) ?></code></span><?php endif; ?>
              <span class="hp-muted"><?= hs_h($t['clients_support_email'] ?? 'Support') ?>: <?= hs_h($editSupport) ?></span>
              <span class="hp-muted"><?= hs_h($t['admin_client_created'] ?? '') ?>: <?= hs_h(hs_format_date((string) ($editUser['created_at'] ?? ''))) ?></span>
            </div>
          </div>

          <div class="hs-clients-quick-actions">
            <form method="post" action="<?= hs_h(hs_url(hs_panel_path('impersonate.php'))) ?>">
              <?= hs_csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= hs_h($editId) ?>">
              <button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-user-secret"></i> <?= hs_h(str_replace('{name}', $editName, $t['clients_manage_as'] ?? '')) ?></button>
            </form>
            <a href="<?= hs_h(hs_url(hs_panel_path('analytics.php'), ['user' => $editId])) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-chart-line"></i> <?= hs_h($t['nav_analytics'] ?? 'Analytics') ?></a>
          </div>

          <form method="post" class="hs-admin-form">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="save_client" value="1">
            <input type="hidden" name="user_id" value="<?= hs_h($editId) ?>">
            <div class="hs-field">
              <label><?= hs_h($t['account_plan'] ?? '') ?></label>
              <select name="plan">
                <?php foreach ($catalog['plans'] as $pid => $plan): ?>
                  <option value="<?= hs_h($pid) ?>"<?= ($editUser['plan'] ?? '') === $pid ? ' selected' : '' ?>><?= hs_h($t['plan_' . $pid] ?? $pid) ?> — <?= hs_h(hs_format_nok_price((float) ($plan['price_nok'] ?? 0), $lang)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="hs-grid hp-grid-2">
              <div class="hs-field">
                <label><?= hs_h($t['admin_client_status'] ?? '') ?></label>
                <select name="subscription_status">
                  <?php foreach (['active', 'pending', 'suspended'] as $st): ?>
                    <option value="<?= hs_h($st) ?>"<?= ($editUser['subscription_status'] ?? 'active') === $st ? ' selected' : '' ?>><?= hs_h(hs_admin_client_status_label($t, $st)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="hs-field">
                <label><?= hs_h($t['plan_renews'] ?? '') ?></label>
                <input type="date" name="paid_until" value="<?= hs_h($paidVal) ?>">
              </div>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_client_notes'] ?? '') ?></label>
              <textarea name="admin_notes" rows="3" placeholder="<?= hs_h($t['clients_notes_placeholder'] ?? '') ?>"><?= hs_h((string) ($editUser['admin_notes'] ?? '')) ?></textarea>
            </div>
            <fieldset class="hs-field hs-admin-services-fieldset">
              <legend><?= hs_h($t['plan_services_title'] ?? '') ?></legend>
              <?php foreach ($allServices as $svc):
                $sid = (string) ($svc['id'] ?? '');
              ?>
                <label class="hs-check"><input type="checkbox" name="plan_services[]" value="<?= hs_h($sid) ?>"<?= in_array($sid, $userServiceIds, true) ? ' checked' : '' ?>> <?= hs_h(hs_plan_catalog_service_label($svc, $lang)) ?> <span class="hs-badge-muted">(<?= hs_h(hs_format_nok_price((float) ($svc['price_nok'] ?? 0), $lang)) ?>)</span></label>
              <?php endforeach; ?>
            </fieldset>
            <button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= hs_h($t['admin_save'] ?? 'Save') ?></button>
          </form>

          <div class="hs-client-invoices">
            <h3><?= hs_h($t['nav_invoices'] ?? 'Invoices') ?></h3>
            <?= hs_invoice_render_table(hs_invoices_for_user($editId), $t, $lang, false) ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>
<script>
(function () {
  document.querySelectorAll('.hs-clients-row[data-href]').forEach(function (row) {
    row.addEventListener('click', function (e) {
      if (e.target.closest('[data-stop-row]')) return;
      window.location.href = row.getAttribute('data-href');
    });
    row.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      if (e.target.closest('[data-stop-row]')) return;
      window.location.href = row.getAttribute('data-href');
    });
  });
  var edit = document.getElementById('hs-client-edit');
  if (edit && window.location.search.indexOf('edit=') !== -1) {
    edit.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';