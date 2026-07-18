<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/invoices.php';
require_once dirname(__DIR__) . '/includes/invoice-ui.php';
require_once dirname(__DIR__) . '/includes/storage.php';
require_once dirname(__DIR__) . '/includes/plans.php';

hs_admin_require();
$admin_active = 'invoices';

$error = '';
$success = '';
$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$viewId = trim((string) ($_GET['view'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif (isset($_POST['set_status'])) {
        $res = hs_invoice_set_status(
            trim((string) ($_POST['invoice_id'] ?? '')),
            (string) ($_POST['status'] ?? '')
        );
        if (!empty($res['ok'])) {
            $success = $t['admin_invoice_status_saved'] ?? 'Invoice status updated';
            $viewId = (string) ($_POST['invoice_id'] ?? $viewId);
        } else {
            $error = $t['admin_invoice_status_fail'] ?? 'Could not update invoice';
        }
    } elseif (isset($_POST['delete_invoice'])) {
        $id = trim((string) ($_POST['invoice_id'] ?? ''));
        if (hs_invoice_delete($id)) {
            $success = $t['admin_invoice_deleted'] ?? 'Invoice deleted';
            $viewId = '';
        } else {
            $error = $t['admin_invoice_delete_fail'] ?? 'Delete failed';
        }
    } elseif (isset($_POST['create_invoice'])) {
        $uid = trim((string) ($_POST['user_id'] ?? ''));
        $user = $uid !== '' ? hs_user_by_id($uid) : null;
        $amount = (float) ($_POST['amount_nok'] ?? 0);
        $desc = trim((string) ($_POST['description'] ?? ''));
        $type = preg_replace('/[^a-z_]/', '', strtolower((string) ($_POST['type'] ?? 'plan'))) ?: 'plan';
        $st = (string) ($_POST['status'] ?? 'pending');
        if ($user === null) {
            $error = $t['admin_client_not_found'] ?? 'Client not found';
        } elseif ($amount <= 0 || $desc === '') {
            $error = $t['admin_invoice_invalid'] ?? 'Enter description and amount';
        } else {
            $inv = hs_invoice_create(
                $user,
                $type,
                [['desc' => $desc, 'qty' => 1, 'unit_nok' => $amount]],
                $lang,
                in_array($st, ['pending', 'paid'], true) ? $st : 'pending'
            );
            if ($inv !== null) {
                $success = ($t['admin_invoice_created'] ?? 'Invoice created') . ': ' . ($inv['number'] ?? '');
                $viewId = (string) ($inv['id'] ?? '');
            } else {
                $error = $t['admin_invoice_create_fail'] ?? 'Could not create invoice';
            }
        }
    }
}

$all = hs_invoices_all();
usort($all, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

$rows = [];
foreach ($all as $inv) {
    $hay = strtolower(
        (string) ($inv['number'] ?? '') . ' '
        . (string) ($inv['username'] ?? '') . ' '
        . (string) ($inv['email'] ?? '') . ' '
        . (string) ($inv['id'] ?? '')
    );
    if ($q !== '' && !str_contains($hay, strtolower($q))) {
        continue;
    }
    if ($statusFilter !== '' && (string) ($inv['status'] ?? '') !== $statusFilter) {
        continue;
    }
    $rows[] = $inv;
}

$viewInv = $viewId !== '' ? hs_invoice_by_id($viewId) : null;
$users = hs_users();
$pendingCnt = count(array_filter($all, static fn(array $i): bool => ($i['status'] ?? '') === 'pending'));
$paidSum = 0.0;
foreach ($all as $i) {
    if (($i['status'] ?? '') === 'paid') {
        $paidSum += (float) ($i['total_nok'] ?? 0);
    }
}

$page_title = $t['admin_invoices_title'] ?? 'Invoices';
ob_start();
?>
<div class="hs-admin-page">
  <h1 style="margin:0 0 1rem"><i class="fa-solid fa-file-invoice-dollar"></i> <?= hs_h($page_title) ?></h1>

  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

  <div class="hs-grid hs-grid-4" style="margin-bottom:1.25rem">
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_invoices_total'] ?? 'Total') ?></div><div class="value"><?= count($all) ?></div></div>
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_invoices_pending'] ?? 'Pending') ?></div><div class="value"><?= $pendingCnt ?></div></div>
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_invoices_filtered'] ?? 'Filtered') ?></div><div class="value"><?= count($rows) ?></div></div>
    <div class="hs-stat"><div class="label"><?= hs_h($t['admin_invoices_paid_sum'] ?? 'Paid (NOK)') ?></div><div class="value" style="font-size:1.1rem"><?= hs_h(number_format($paidSum, 0, '.', ' ')) ?></div></div>
  </div>

  <div class="hs-admin-split">
    <section class="hp-card">
      <h2 class="hp-card-title"><?= hs_h($t['admin_invoices_list'] ?? 'All invoices') ?></h2>
      <div class="hp-card-body">
        <form method="get" class="hs-admin-search" style="margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.5rem">
          <input type="search" name="q" value="<?= hs_h($q) ?>" placeholder="<?= hs_h($t['admin_clients_search'] ?? 'Search…') ?>" style="flex:1;min-width:10rem">
          <select name="status">
            <option value=""><?= hs_h($t['admin_invoices_all_status'] ?? 'All statuses') ?></option>
            <?php foreach (['pending', 'paid', 'overdue', 'cancelled', 'refunded'] as $st): ?>
              <option value="<?= hs_h($st) ?>"<?= $statusFilter === $st ? ' selected' : '' ?>><?= hs_h($st) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-search"></i></button>
        </form>
        <div class="hs-table-wrap">
          <table class="hs-table">
            <thead>
              <tr>
                <th><?= hs_h($t['admin_invoice_number'] ?? 'Number') ?></th>
                <th><?= hs_h($t['admin_col_user'] ?? 'Client') ?></th>
                <th><?= hs_h($t['admin_col_plan'] ?? 'Type') ?></th>
                <th><?= hs_h($t['plan_price'] ?? 'Amount') ?></th>
                <th><?= hs_h($t['admin_client_status'] ?? 'Status') ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
              <tr><td colspan="6" class="hp-muted"><?= hs_h($t['admin_invoices_empty'] ?? 'No invoices found.') ?></td></tr>
            <?php else: ?>
              <?php foreach ($rows as $inv):
                $iid = (string) ($inv['id'] ?? '');
              ?>
                <tr<?= $iid === $viewId ? ' class="is-selected"' : '' ?>>
                  <td><code><?= hs_h((string) ($inv['number'] ?? $iid)) ?></code></td>
                  <td><?= hs_h((string) ($inv['username'] ?? '')) ?><br><span class="hp-muted" style="font-size:.8rem"><?= hs_h((string) ($inv['email'] ?? '')) ?></span></td>
                  <td><?= hs_h(hs_invoice_type_label($inv, $t)) ?></td>
                  <td><?= hs_h(hs_invoice_format_total($inv, $lang)) ?></td>
                  <td><span class="hs-plan-status hs-plan-status-<?= hs_h((string) ($inv['status'] ?? 'pending')) ?>"><?= hs_h((string) ($inv['status'] ?? '')) ?></span></td>
                  <td><a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_admin_url('invoices.php', ['view' => $iid, 'q' => $q, 'status' => $statusFilter])) ?>"><?= hs_h($t['admin_edit'] ?? 'Open') ?></a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <div>
      <section class="hp-card hs-admin-form-card" style="margin-bottom:1rem">
        <h2 class="hp-card-title"><?= hs_h($t['admin_invoice_create'] ?? 'Create invoice') ?></h2>
        <div class="hp-card-body">
          <form method="post" class="hs-admin-form">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="create_invoice" value="1">
            <div class="hs-field">
              <label><?= hs_h($t['admin_clients'] ?? 'Client') ?></label>
              <select name="user_id" required>
                <option value="">—</option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= hs_h((string) ($u['id'] ?? '')) ?>"><?= hs_h((string) ($u['username'] ?? '')) ?> · <?= hs_h((string) ($u['email'] ?? '')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_invoice_desc'] ?? 'Description') ?></label>
              <input type="text" name="description" required placeholder="Hosting plan · Starter">
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_invoice_amount'] ?? 'Amount (NOK)') ?></label>
              <input type="number" name="amount_nok" min="1" step="1" required>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_invoice_type'] ?? 'Type') ?></label>
              <select name="type">
                <option value="plan">plan</option>
                <option value="domain">domain</option>
                <option value="plan_domain">plan_domain</option>
                <option value="plan_change">plan_change</option>
                <option value="other">other</option>
              </select>
            </div>
            <div class="hs-field">
              <label><?= hs_h($t['admin_client_status'] ?? 'Status') ?></label>
              <select name="status">
                <option value="pending">pending</option>
                <option value="paid">paid</option>
              </select>
            </div>
            <button type="submit" class="hs-btn hs-btn-primary"><i class="fa-solid fa-plus"></i> <?= hs_h($t['admin_invoice_create'] ?? 'Create') ?></button>
          </form>
        </div>
      </section>

      <?php if ($viewInv !== null): ?>
      <section class="hp-card hs-admin-form-card">
        <h2 class="hp-card-title"><?= hs_h((string) ($viewInv['number'] ?? $viewInv['id'] ?? '')) ?></h2>
        <div class="hp-card-body">
          <p>
            <strong><?= hs_h((string) ($viewInv['username'] ?? '')) ?></strong><br>
            <span class="hp-muted"><?= hs_h((string) ($viewInv['email'] ?? '')) ?></span><br>
            <?= hs_h(hs_invoice_type_label($viewInv, $t)) ?> ·
            <strong><?= hs_h(hs_invoice_format_total($viewInv, $lang)) ?></strong><br>
            <span class="hp-muted"><?= hs_h(hs_format_date((string) ($viewInv['created_at'] ?? ''))) ?></span>
          </p>
          <?php if (!empty($viewInv['lines']) && is_array($viewInv['lines'])): ?>
            <ul class="hp-muted" style="padding-left:1.1rem;font-size:.9rem">
              <?php foreach ($viewInv['lines'] as $line): ?>
                <li><?= hs_h((string) ($line['desc'] ?? '')) ?> — <?= hs_h(hs_format_nok_price((float) ($line['total_nok'] ?? 0), $lang)) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <form method="post" style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:end">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="set_status" value="1">
            <input type="hidden" name="invoice_id" value="<?= hs_h((string) ($viewInv['id'] ?? '')) ?>">
            <div class="hs-field" style="margin:0">
              <label><?= hs_h($t['admin_client_status'] ?? 'Status') ?></label>
              <select name="status">
                <?php foreach (['pending', 'paid', 'overdue', 'cancelled', 'refunded'] as $st): ?>
                  <option value="<?= hs_h($st) ?>"<?= ($viewInv['status'] ?? '') === $st ? ' selected' : '' ?>><?= hs_h($st) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_save'] ?? 'Save') ?></button>
          </form>
          <form method="post" style="margin-top:.75rem" onsubmit="return confirm(<?= json_encode($t['admin_confirm_delete'] ?? 'Delete?', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="delete_invoice" value="1">
            <input type="hidden" name="invoice_id" value="<?= hs_h((string) ($viewInv['id'] ?? '')) ?>">
            <button type="submit" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-trash"></i> <?= hs_h($t['admin_invoice_delete'] ?? 'Delete') ?></button>
          </form>
          <?php if (function_exists('hs_url')): ?>
            <p style="margin-top:1rem">
              <a class="hs-btn hs-btn-ghost hp-dash-btn-sm" href="<?= hs_h(hs_url(hs_panel_path('invoice-view.php'), ['id' => (string) ($viewInv['id'] ?? '')])) ?>" target="_blank" rel="noopener">
                <i class="fa-solid fa-eye"></i> <?= hs_h($t['admin_invoice_view_client'] ?? 'Client view') ?>
              </a>
            </p>
          <?php endif; ?>
        </div>
      </section>
      <?php else: ?>
      <section class="hp-card">
        <div class="hp-card-body">
          <p class="hp-muted"><?= hs_h($t['admin_invoice_select_hint'] ?? 'Select an invoice from the list to change status or delete.') ?></p>
        </div>
      </section>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';
