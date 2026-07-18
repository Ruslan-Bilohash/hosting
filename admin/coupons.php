<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/coupons.php';

hs_admin_require();
$admin_active = 'coupons';

$error = '';
$success = '';
$coupons = hs_coupons_load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif (isset($_POST['save_coupon'])) {
        $row = hs_coupon_normalize_row($_POST);
        if ($row === []) {
            $error = $t['admin_coupon_invalid'] ?? 'Invalid coupon code';
        } else {
            $found = false;
            foreach ($coupons as $i => $c) {
                if (($c['id'] ?? '') === $row['id'] || hs_coupon_normalize_code((string) ($c['code'] ?? '')) === $row['code']) {
                    $row['used_count'] = (int) ($c['used_count'] ?? 0);
                    $coupons[$i] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $coupons[] = $row;
            }
            if (hs_coupons_save($coupons)) {
                $success = $t['admin_coupon_saved'] ?? 'Coupon saved';
                $coupons = hs_coupons_load();
            } else {
                $error = $t['admin_plans_save_fail'] ?? 'Save failed';
            }
        }
    } elseif (isset($_POST['delete_coupon'])) {
        $id = trim((string) ($_POST['coupon_id'] ?? ''));
        $coupons = array_values(array_filter($coupons, static fn(array $c): bool => ($c['id'] ?? '') !== $id));
        if (hs_coupons_save($coupons)) {
            $success = $t['admin_coupon_deleted'] ?? 'Coupon removed';
            $coupons = hs_coupons_load();
        } else {
            $error = $t['admin_plans_save_fail'] ?? 'Save failed';
        }
    } elseif (isset($_POST['seed_defaults'])) {
        if (hs_coupons_save(hs_coupons_defaults())) {
            $success = $t['admin_coupon_seeded'] ?? 'Default coupons restored';
            $coupons = hs_coupons_load();
        }
    }
}

$editId = trim((string) ($_GET['edit'] ?? ''));
$edit = null;
foreach ($coupons as $c) {
    if (($c['id'] ?? '') === $editId) {
        $edit = $c;
        break;
    }
}
$new = isset($_GET['new']);
if ($new) {
    $edit = [
        'id' => '',
        'code' => '',
        'active' => true,
        'scope' => 'order',
        'type' => 'percent',
        'value' => 10,
        'tld' => '',
        'max_uses' => 100,
        'used_count' => 0,
        'expires_at' => '',
        'label_uk' => '',
        'label_en' => '',
        'label_no' => '',
    ];
}

$page_title = $t['admin_coupons_title'] ?? 'Promo codes';
ob_start();
?>
<div class="hs-admin-page">
  <nav class="hs-admin-tabs" style="margin-bottom:1.25rem">
    <a href="<?= hs_h(hs_admin_url('coupons.php')) ?>" class="hs-btn hs-btn-ghost is-active"><i class="fa-solid fa-ticket"></i> <?= hs_h($t['admin_coupons_title'] ?? 'Promo codes') ?></a>
    <a href="<?= hs_h(hs_admin_url('plans.php')) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-layer-group"></i> <?= hs_h($t['admin_plans_title'] ?? 'Plans') ?></a>
    <a href="<?= hs_h(hs_admin_url()) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_title'] ?? 'Admin') ?></a>
  </nav>

  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

  <div class="hp-card" style="margin-bottom:1.25rem">
    <div class="hp-card-body">
      <p class="hp-muted"><?= hs_h($t['admin_coupons_lead'] ?? 'Clients enter codes at checkout. Your Namecheap wholesale cost is unchanged — discount comes from your margin.') ?></p>
      <form method="post" style="display:inline"><?= hs_csrf_field() ?><button type="submit" name="seed_defaults" value="1" class="hs-btn hs-btn-ghost" onclick="return confirm('Reset to WELCOME20, EKOHOST, DOMAIN5?')"><?= hs_h($t['admin_coupon_seed'] ?? 'Restore defaults') ?></button></form>
      <a href="<?= hs_h(hs_admin_url('coupons.php', ['new' => '1'])) ?>" class="hs-btn hs-btn-primary" style="margin-left:.5rem"><i class="fa-solid fa-plus"></i> <?= hs_h($t['admin_coupon_add'] ?? 'Add coupon') ?></a>
    </div>
  </div>

  <?php if ($edit !== null): ?>
  <div class="hp-card" style="margin-bottom:1.25rem">
    <h2 class="hp-card-title"><?= hs_h($new ? ($t['admin_coupon_add'] ?? 'Add') : ($t['admin_edit'] ?? 'Edit')) ?></h2>
    <div class="hp-card-body">
      <form method="post" class="hp-stack">
        <?= hs_csrf_field() ?>
        <input type="hidden" name="id" value="<?= hs_h((string) ($edit['id'] ?? '')) ?>">
        <div class="hp-grid-2">
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_code'] ?? 'Code') ?></label><input type="text" name="code" required value="<?= hs_h((string) ($edit['code'] ?? '')) ?>" style="text-transform:uppercase"></div>
          <div class="hs-field"><label><?= hs_h($t['admin_plans_active'] ?? 'Active') ?></label><label class="hs-check"><input type="checkbox" name="active" value="1" <?= !empty($edit['active']) ? 'checked' : '' ?>> on</label></div>
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_scope'] ?? 'Applies to') ?></label>
            <select name="scope">
              <option value="hosting"<?= ($edit['scope'] ?? '') === 'hosting' ? ' selected' : '' ?>>Hosting</option>
              <option value="domain"<?= ($edit['scope'] ?? '') === 'domain' ? ' selected' : '' ?>>Domain</option>
              <option value="order"<?= ($edit['scope'] ?? '') === 'order' ? ' selected' : '' ?>>Full order</option>
            </select>
          </div>
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_type'] ?? 'Type') ?></label>
            <select name="type">
              <option value="percent"<?= ($edit['type'] ?? '') === 'percent' ? ' selected' : '' ?>>Percent %</option>
              <option value="fixed_eur"<?= ($edit['type'] ?? '') === 'fixed_eur' ? ' selected' : '' ?>>Fixed € off domain</option>
              <option value="fixed_nok"<?= ($edit['type'] ?? '') === 'fixed_nok' ? ' selected' : '' ?>>Fixed kr off hosting</option>
              <option value="domain_cap_eur"<?= ($edit['type'] ?? '') === 'domain_cap_eur' ? ' selected' : '' ?>>Domain cap € (e.g. .com promo)</option>
            </select>
          </div>
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_value'] ?? 'Value') ?></label><input type="number" name="value" step="0.01" min="0" value="<?= hs_h((string) ($edit['value'] ?? '0')) ?>"></div>
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_tld'] ?? 'TLD only') ?></label><input type="text" name="tld" placeholder="com" value="<?= hs_h((string) ($edit['tld'] ?? '')) ?>"></div>
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_max'] ?? 'Max uses (0=∞)') ?></label><input type="number" name="max_uses" min="0" value="<?= hs_h((string) ($edit['max_uses'] ?? '0')) ?>"></div>
          <div class="hs-field"><label><?= hs_h($t['admin_coupon_expires'] ?? 'Expires (YYYY-MM-DD)') ?></label><input type="date" name="expires_at" value="<?= hs_h((string) ($edit['expires_at'] ?? '')) ?>"></div>
        </div>
        <div class="hs-field"><label>Label UK</label><input type="text" name="label_uk" value="<?= hs_h((string) ($edit['label_uk'] ?? '')) ?>"></div>
        <div class="hs-field"><label>Label EN</label><input type="text" name="label_en" value="<?= hs_h((string) ($edit['label_en'] ?? '')) ?>"></div>
        <div class="hs-field"><label>Label NO</label><input type="text" name="label_no" value="<?= hs_h((string) ($edit['label_no'] ?? '')) ?>"></div>
        <button type="submit" name="save_coupon" value="1" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_save'] ?? 'Save') ?></button>
        <a href="<?= hs_h(hs_admin_url('coupons.php')) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_cancel'] ?? 'Cancel') ?></a>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="hp-card">
    <h2 class="hp-card-title"><?= hs_h($t['admin_coupons_list'] ?? 'Active codes') ?></h2>
    <div class="hp-card-body" style="overflow-x:auto">
      <table class="hs-table">
        <thead><tr>
          <th><?= hs_h($t['admin_coupon_code'] ?? 'Code') ?></th>
          <th><?= hs_h($t['admin_coupon_scope'] ?? 'Scope') ?></th>
          <th><?= hs_h($t['admin_coupon_type'] ?? 'Type') ?></th>
          <th><?= hs_h($t['admin_coupon_value'] ?? 'Value') ?></th>
          <th><?= hs_h($t['admin_coupon_uses'] ?? 'Uses') ?></th>
          <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td><strong><?= hs_h((string) ($c['code'] ?? '')) ?></strong><?= empty($c['active']) ? ' <span class="hp-muted">(off)</span>' : '' ?></td>
            <td><?= hs_h((string) ($c['scope'] ?? '')) ?></td>
            <td><?= hs_h((string) ($c['type'] ?? '')) ?></td>
            <td><?= hs_h((string) ($c['value'] ?? '')) ?><?= ($c['tld'] ?? '') !== '' ? ' .' . hs_h((string) $c['tld']) : '' ?></td>
            <td><?= (int) ($c['used_count'] ?? 0) ?><?= (int) ($c['max_uses'] ?? 0) > 0 ? ' / ' . (int) $c['max_uses'] : '' ?></td>
            <td style="white-space:nowrap">
              <a href="<?= hs_h(hs_admin_url('coupons.php', ['edit' => (string) ($c['id'] ?? '')])) ?>" class="hs-btn hs-btn-ghost hs-btn-sm"><?= hs_h($t['admin_edit'] ?? 'Edit') ?></a>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= hs_csrf_field() ?><input type="hidden" name="coupon_id" value="<?= hs_h((string) ($c['id'] ?? '')) ?>"><button type="submit" name="delete_coupon" value="1" class="hs-btn hs-btn-ghost hs-btn-sm"><?= hs_h($t['admin_delete'] ?? 'Delete') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';