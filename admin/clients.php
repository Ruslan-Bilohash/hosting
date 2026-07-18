<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-clients.php';
require_once dirname(__DIR__) . '/includes/plan-catalog.php';
require_once dirname(__DIR__) . '/includes/impersonation.php';
require_once dirname(__DIR__) . '/includes/invoice-ui.php';
require_once dirname(__DIR__) . '/includes/admin-nav.php';

hs_admin_require();

$error = '';
$success = '';
$editId = trim((string) ($_GET['edit'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
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
            $success = $t['admin_client_saved'] ?? 'Client updated';
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

$rows = [];
foreach (hs_users() as $u) {
    $hay = strtolower((string) ($u['username'] ?? '') . ' ' . (string) ($u['email'] ?? '') . ' ' . (string) ($u['plan'] ?? ''));
    if ($q !== '' && !str_contains($hay, strtolower($q))) {
        continue;
    }
    $rows[] = hs_admin_client_row_data($u);
}

$editUser = $editId !== '' ? hs_user_by_id($editId) : null;
$catalog = hs_plan_catalog_load();
$allServices = hs_plan_catalog_services(false);
$userServiceIds = is_array($editUser['plan_services'] ?? null) ? $editUser['plan_services'] : [];

$page_title = $t['admin_clients_manage'] ?? 'Manage clients';
$admin_nav_active = 'clients';
ob_start();
?>
<div class="hs-admin-page">
  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

  <div class="hs-admin-split">
    <section class="hp-card">
      <h2 class="hp-card-title"><?= hs_h($t['admin_clients'] ?? '') ?> (<?= count($rows) ?>)</h2>
      <div class="hp-card-body">
        <form method="get" class="hs-admin-search" style="margin-bottom:1rem">
          <input type="search" name="q" value="<?= hs_h($q) ?>" placeholder="<?= hs_h($t['admin_clients_search'] ?? 'Search…') ?>">
          <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-search"></i></button>
        </form>
        <div class="hs-table-wrap">
          <table class="hs-table hs-admin-clients-table">
            <thead><tr>
              <th><?= hs_h($t['admin_col_user'] ?? $t['account_username'] ?? 'User') ?></th>
              <th><?= hs_h($t['admin_col_email'] ?? 'Email') ?></th>
              <th><?= hs_h($t['admin_col_plan'] ?? $t['account_plan'] ?? 'Plan') ?></th>
              <th><?= hs_h($t['admin_client_status'] ?? 'Status') ?></th>
              <th><?= hs_h($t['admin_disk'] ?? 'Disk') ?></th>
              <th><?= hs_h($t['admin_col_sites'] ?? 'Sites') ?></th>
              <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $row):
              $u = $row['user'];
              $res = $row['resources'];
              $uid = (string) ($u['id'] ?? '');
              $status = (string) ($u['subscription_status'] ?? 'active');
            ?>
              <tr<?= $uid === $editId ? ' class="is-selected"' : '' ?>>
                <td><strong><?= hs_h((string) ($u['username'] ?? '')) ?></strong></td>
                <td><?= hs_h((string) ($u['email'] ?? '')) ?></td>
                <td><code><?= hs_h((string) ($u['plan'] ?? '')) ?></code></td>
                <td><span class="hs-plan-status hs-plan-status-<?= hs_h($status) ?>"><?= hs_h(hs_admin_client_status_label($t, $status)) ?></span></td>
                <td><?= hs_h(hs_format_disk_gb((float) ($res['storage_used_mb'] ?? 0))) ?> GB</td>
                <td><?= (int) $row['site_count'] ?></td>
                <td class="hs-admin-row-actions">
                  <a href="<?= hs_h(hs_admin_url('clients.php', ['edit' => $uid, 'q' => $q])) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><?= hs_h($t['admin_edit'] ?? 'Edit') ?></a>
                  <form method="post" action="<?= hs_h(hs_admin_url('impersonate.php')) ?>" style="display:inline;margin:0"><?= hs_csrf_field() ?><input type="hidden" name="user_id" value="<?= hs_h($uid) ?>"><button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-user-secret"></i></button></form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="hp-card hs-admin-form-card">
      <h2 class="hp-card-title"><?= hs_h($editUser ? ($t['admin_edit_client'] ?? 'Edit client') : ($t['admin_select_client'] ?? 'Select a client')) ?></h2>
      <div class="hp-card-body">
        <?php if ($editUser === null): ?>
          <p class="hp-muted"><?= hs_h($t['admin_select_client_hint'] ?? '') ?></p>
        <?php else:
          $paidVal = !empty($editUser['paid_until']) ? date('Y-m-d', strtotime((string) $editUser['paid_until'])) : '';
        ?>
          <form method="post" class="hs-admin-form">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="save_client" value="1">
            <input type="hidden" name="user_id" value="<?= hs_h($editId) ?>">
            <div class="hs-admin-client-summary">
              <strong><?= hs_h((string) ($editUser['username'] ?? '')) ?></strong>
              <span><?= hs_h((string) ($editUser['email'] ?? '')) ?></span>
              <span class="hp-muted"><?= hs_h($t['admin_client_created'] ?? 'Created') ?>: <?= hs_h(hs_format_date((string) ($editUser['created_at'] ?? ''))) ?></span>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['account_plan'] ?? 'Plan') ?></label>
              <select name="plan">
                <?php foreach ($catalog['plans'] as $pid => $plan): ?>
                  <option value="<?= hs_h($pid) ?>"<?= ($editUser['plan'] ?? '') === $pid ? ' selected' : '' ?>><?= hs_h($pid) ?> — <?= hs_h(hs_format_nok_price((float) ($plan['price_nok'] ?? 0), $lang)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_client_status'] ?? 'Status') ?></label>
              <select name="subscription_status">
                <?php foreach (['active', 'pending', 'suspended'] as $st): ?>
                  <option value="<?= hs_h($st) ?>"<?= ($editUser['subscription_status'] ?? 'active') === $st ? ' selected' : '' ?>><?= hs_h(hs_admin_client_status_label($t, $st)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['plan_renews'] ?? 'Paid until') ?></label>
              <input type="date" name="paid_until" value="<?= hs_h($paidVal) ?>">
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_client_notes'] ?? 'Admin notes') ?></label>
              <textarea name="admin_notes" rows="3"><?= hs_h((string) ($editUser['admin_notes'] ?? '')) ?></textarea>
            </div>
            <fieldset class="hs-field hs-admin-services-fieldset">
              <legend><?= hs_h($t['plan_services_title'] ?? 'Services') ?></legend>
              <?php foreach ($allServices as $svc):
                $sid = (string) ($svc['id'] ?? '');
              ?>
                <label class="hs-check"><input type="checkbox" name="plan_services[]" value="<?= hs_h($sid) ?>"<?= in_array($sid, $userServiceIds, true) ? ' checked' : '' ?>> <?= hs_h(hs_plan_catalog_service_label($svc, $lang)) ?> (<?= hs_h(hs_format_nok_price((float) ($svc['price_nok'] ?? 0), $lang)) ?>)</label>
              <?php endforeach; ?>
            </fieldset>
            <button type="submit" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_save'] ?? 'Save') ?></button>
          </form>
          <form method="post" action="<?= hs_h(hs_admin_url('impersonate.php')) ?>" style="margin-top:.75rem"><?= hs_csrf_field() ?><input type="hidden" name="user_id" value="<?= hs_h($editId) ?>"><button type="submit" class="hs-btn hs-btn-ghost"><?= hs_h(str_replace('{name}', hs_client_display_name($editUser), $t['clients_manage_as'] ?? '')) ?></button></form>
          <div class="hs-client-invoices" style="margin-top:1.25rem">
            <h3 style="margin:0 0 .75rem;font-size:1rem"><?= hs_h($t['nav_invoices'] ?? 'Invoices') ?></h3>
            <?= hs_invoice_render_table(hs_invoices_for_user($editId), $t, $lang, false) ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';