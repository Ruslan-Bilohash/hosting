<?php
declare(strict_types=1);

$panel_active = 'clients';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin-clients.php';
require_once dirname(__DIR__) . '/includes/plan-catalog.php';
require_once dirname(__DIR__) . '/includes/invoice-ui.php';

if (!$hs_is_platform_admin || hs_impersonation_active()) {
    hs_redirect('panel/');
}

$page_title = $t['nav_clients'] ?? 'Clients';
$panel_tip_key = 'clients';
$error = '';
$success = '';
$editId = trim((string) ($_GET['edit'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } else {
        $res = hs_admin_client_update(trim((string) ($_POST['user_id'] ?? '')), [
            'plan' => $_POST['plan'] ?? '',
            'subscription_status' => $_POST['subscription_status'] ?? '',
            'paid_until' => $_POST['paid_until'] ?? '',
            'admin_notes' => $_POST['admin_notes'] ?? '',
            'plan_services' => $_POST['plan_services'] ?? [],
            'lang' => $lang,
        ]);
        if (!empty($res['ok'])) {
            $success = $t['admin_client_saved'] ?? 'Updated';
            $editId = trim((string) ($_POST['user_id'] ?? ''));
        } else {
            $error = $t['admin_plans_save_fail'] ?? 'Save failed';
        }
    }
}

$rows = [];
foreach (hs_users() as $u) {
    if (($u['id'] ?? '') === ($user['id'] ?? '')) {
        continue;
    }
    $hay = strtolower((string) ($u['username'] ?? '') . ' ' . (string) ($u['email'] ?? ''));
    if ($q !== '' && !str_contains($hay, strtolower($q))) {
        continue;
    }
    $rows[] = hs_admin_client_row_data($u);
}

$editUser = $editId !== '' ? hs_user_by_id($editId) : null;
$catalog = hs_plan_catalog_load();
$userServiceIds = is_array($editUser['plan_services'] ?? null) ? $editUser['plan_services'] : [];

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<p class="hp-muted" style="margin-bottom:1rem"><?= hs_h($t['clients_admin_hint'] ?? '') ?></p>

<div class="hs-admin-split hs-clients-panel">
  <section class="hp-card">
    <h2 class="hp-card-title"><?= hs_h($t['nav_clients'] ?? '') ?> (<?= count($rows) ?>)</h2>
    <div class="hp-card-body">
      <form method="get" class="hs-admin-search" style="margin-bottom:1rem">
        <input type="search" name="q" value="<?= hs_h($q) ?>" placeholder="<?= hs_h($t['admin_clients_search'] ?? 'Search…') ?>">
        <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-search"></i></button>
      </form>
      <div class="hs-table-wrap">
        <table class="hs-table">
          <thead><tr>
            <th><?= hs_h($t['account_username'] ?? '') ?></th>
            <th><?= hs_h($t['account_email'] ?? '') ?></th>
            <th><?= hs_h($t['account_plan'] ?? '') ?></th>
            <th><?= hs_h($t['admin_client_status'] ?? '') ?></th>
            <th><?= hs_h($t['panel_websites'] ?? '') ?></th>
            <th></th>
          </tr></thead>
          <tbody>
          <?php foreach ($rows as $row):
            $u = $row['user'];
            $uid = (string) ($u['id'] ?? '');
            $name = hs_client_display_name($u);
            $status = (string) ($u['subscription_status'] ?? 'active');
          ?>
            <tr<?= $uid === $editId ? ' class="is-selected"' : '' ?>>
              <td><strong><?= hs_h($name) ?></strong><br><code><?= hs_h($u['username'] ?? '') ?></code></td>
              <td><?= hs_h($u['email'] ?? '') ?></td>
              <td><?= hs_h($t['plan_' . ($u['plan'] ?? 'starter')] ?? $u['plan'] ?? '') ?></td>
              <td><span class="hs-plan-status hs-plan-status-<?= hs_h($status) ?>"><?= hs_h($status) ?></span></td>
              <td><?= (int) $row['site_count'] ?></td>
              <td class="hs-admin-row-actions">
                <a href="<?= hs_h(hs_url(hs_panel_path('clients.php'), ['edit' => $uid, 'q' => $q])) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><?= hs_h($t['admin_edit'] ?? 'Edit') ?></a>
                <form method="post" action="<?= hs_h(hs_url(hs_panel_path('impersonate.php'))) ?>" style="display:inline;margin:0"><?= hs_csrf_field() ?><input type="hidden" name="user_id" value="<?= hs_h($uid) ?>"><button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><?= hs_h(str_replace('{name}', $name, $t['clients_manage_as'] ?? '')) ?></button></form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="hp-card hs-admin-form-card">
    <h2 class="hp-card-title"><?= hs_h($editUser ? ($t['admin_edit_client'] ?? 'Edit') : ($t['admin_select_client'] ?? 'Select client')) ?></h2>
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
          <div class="hs-field">
            <label><?= hs_h($t['account_plan'] ?? '') ?></label>
            <select name="plan">
              <?php foreach ($catalog['plans'] as $pid => $plan): ?>
                <option value="<?= hs_h($pid) ?>"<?= ($editUser['plan'] ?? '') === $pid ? ' selected' : '' ?>><?= hs_h($t['plan_' . $pid] ?? $pid) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="hs-field">
            <label><?= hs_h($t['admin_client_status'] ?? '') ?></label>
            <select name="subscription_status">
              <?php foreach (['active', 'pending', 'suspended'] as $st): ?>
                <option value="<?= hs_h($st) ?>"<?= ($editUser['subscription_status'] ?? 'active') === $st ? ' selected' : '' ?>><?= hs_h($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="hs-field">
            <label><?= hs_h($t['plan_renews'] ?? '') ?></label>
            <input type="date" name="paid_until" value="<?= hs_h($paidVal) ?>">
          </div>
          <div class="hs-field">
            <label><?= hs_h($t['admin_client_notes'] ?? '') ?></label>
            <textarea name="admin_notes" rows="3"><?= hs_h((string) ($editUser['admin_notes'] ?? '')) ?></textarea>
          </div>
          <fieldset class="hs-field hs-admin-services-fieldset">
            <legend><?= hs_h($t['plan_services_title'] ?? '') ?></legend>
            <?php foreach (hs_plan_catalog_services(false) as $svc):
              $sid = (string) ($svc['id'] ?? '');
            ?>
              <label class="hs-check"><input type="checkbox" name="plan_services[]" value="<?= hs_h($sid) ?>"<?= in_array($sid, $userServiceIds, true) ? ' checked' : '' ?>> <?= hs_h(hs_plan_catalog_service_label($svc, $lang)) ?></label>
            <?php endforeach; ?>
          </fieldset>
          <button type="submit" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_save'] ?? 'Save') ?></button>
        </form>
        <form method="post" action="<?= hs_h(hs_url(hs_panel_path('impersonate.php'))) ?>" style="margin-top:.75rem"><?= hs_csrf_field() ?><input type="hidden" name="user_id" value="<?= hs_h($editId) ?>"><button type="submit" class="hs-btn hs-btn-ghost"><?= hs_h(str_replace('{name}', hs_client_display_name($editUser), $t['clients_manage_as'] ?? '')) ?></button></form>
        <div class="hs-client-invoices" style="margin-top:1.25rem">
          <h3 style="margin:0 0 .75rem;font-size:1rem"><?= hs_h($t['nav_invoices'] ?? 'Invoices') ?></h3>
          <?= hs_invoice_render_table(hs_invoices_for_user($editId), $t, $lang, false) ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';