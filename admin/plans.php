<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/plan-catalog.php';

hs_admin_require();

$error = '';
$success = '';
$tab = ($_GET['tab'] ?? 'plans') === 'services' ? 'services' : 'plans';
$catalog = hs_plan_catalog_load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif (isset($_POST['save_plan'])) {
        $post = $_POST;
        $post['active'] = isset($_POST['active']);
        $post['ecosystem_apps'] = isset($_POST['ecosystem_apps']);
        $row = hs_plan_catalog_normalize_plan_row($post);
        if ($row === []) {
            $error = $t['admin_plans_invalid_id'] ?? 'Invalid plan ID';
        } else {
            $catalog['plans'][$row['id']] = array_merge($catalog['plans'][$row['id']] ?? [], $row);
            if (hs_plan_catalog_save($catalog)) {
                $success = $t['admin_plans_saved'] ?? 'Plan saved';
                $catalog = hs_plan_catalog_load();
            } else {
                $error = $t['admin_plans_save_fail'] ?? 'Save failed';
            }
        }
    } elseif (isset($_POST['save_service'])) {
        $post = $_POST;
        $post['active'] = isset($_POST['active']);
        $row = hs_plan_catalog_normalize_service_row($post);
        if ($row === []) {
            $error = $t['admin_services_invalid_id'] ?? 'Invalid service ID';
        } else {
            $found = false;
            foreach ($catalog['services'] as $i => $svc) {
                if (($svc['id'] ?? '') === $row['id']) {
                    $catalog['services'][$i] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $catalog['services'][] = $row;
            }
            if (hs_plan_catalog_save($catalog)) {
                $success = $t['admin_services_saved'] ?? 'Service saved';
                $catalog = hs_plan_catalog_load();
            } else {
                $error = $t['admin_plans_save_fail'] ?? 'Save failed';
            }
        }
    } elseif (isset($_POST['delete_service'])) {
        $sid = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($_POST['service_id'] ?? ''))) ?? '';
        $catalog['services'] = array_values(array_filter(
            $catalog['services'],
            static fn(array $svc): bool => ($svc['id'] ?? '') !== $sid
        ));
        if (hs_plan_catalog_save($catalog)) {
            $success = $t['admin_services_deleted'] ?? 'Service removed';
            $catalog = hs_plan_catalog_load();
        } else {
            $error = $t['admin_plans_save_fail'] ?? 'Save failed';
        }
    }
}

$editPlan = trim((string) ($_GET['edit_plan'] ?? ''));
$editService = trim((string) ($_GET['edit_service'] ?? ''));
$newPlan = isset($_GET['new_plan']);
$newService = isset($_GET['new_service']);

$page_title = $t['admin_plans_title'] ?? 'Plans & services';
ob_start();
?>
<div class="hs-admin-page">
  <nav class="hs-admin-tabs" style="margin-bottom:1.25rem">
    <a href="<?= hs_h(hs_admin_url('plans.php')) ?>" class="hs-btn hs-btn-ghost<?= $tab === 'plans' ? ' is-active' : '' ?>"><?= hs_h($t['admin_plans_tab'] ?? 'Tariffs') ?></a>
    <a href="<?= hs_h(hs_admin_url('plans.php', ['tab' => 'services'])) ?>" class="hs-btn hs-btn-ghost<?= $tab === 'services' ? ' is-active' : '' ?>"><?= hs_h($t['admin_services_tab'] ?? 'Services') ?></a>
    <a href="<?= hs_h(hs_admin_url('clients.php')) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-users"></i> <?= hs_h($t['admin_clients'] ?? '') ?></a>
    <a href="<?= hs_h(hs_admin_url()) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_title'] ?? '') ?></a>
  </nav>

  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

  <?php if ($tab === 'plans'): ?>
    <div class="hs-admin-split">
      <section class="hp-card">
        <h2 class="hp-card-title"><?= hs_h($t['admin_plans_list'] ?? 'Tariff catalog') ?></h2>
        <div class="hp-card-body">
          <div class="hs-table-wrap">
            <table class="hs-table">
              <thead><tr>
                <th>ID</th><th><?= hs_h($t['plan_price'] ?? 'Price') ?></th><th><?= hs_h($t['plan_websites_limit'] ?? 'Sites') ?></th><th><?= hs_h($t['plan_storage_limit'] ?? 'Storage') ?></th><th></th>
              </tr></thead>
              <tbody>
              <?php foreach ($catalog['plans'] as $pid => $plan): ?>
                <tr>
                  <td><code><?= hs_h($pid) ?></code> <?= empty($plan['active']) ? '<span class="hs-badge-muted">off</span>' : '' ?></td>
                  <td><?= hs_h(hs_format_nok_price((float) ($plan['price_nok'] ?? 0), $lang)) ?></td>
                  <td><?= (int) ($plan['sites'] ?? 0) ?></td>
                  <td><?= (int) ($plan['disk_gb'] ?? 0) ?> GB</td>
                  <td><a href="<?= hs_h(hs_admin_url('plans.php', ['edit_plan' => $pid])) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><?= hs_h($t['admin_edit'] ?? 'Edit') ?></a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p style="margin-top:1rem"><a href="<?= hs_h(hs_admin_url('plans.php', ['new_plan' => '1'])) ?>" class="hs-btn hs-btn-primary"><i class="fa-solid fa-plus"></i> <?= hs_h($t['admin_plans_add'] ?? 'Add tariff') ?></a></p>
        </div>
      </section>

      <?php if ($editPlan !== '' || $newPlan):
        $p = $newPlan ? [] : ($catalog['plans'][$editPlan] ?? []);
      ?>
      <section class="hp-card hs-admin-form-card">
        <h2 class="hp-card-title"><?= hs_h($newPlan ? ($t['admin_plans_add'] ?? 'Add tariff') : ($t['admin_edit'] ?? 'Edit') . ' ' . $editPlan) ?></h2>
        <div class="hp-card-body">
          <form method="post" class="hs-admin-form">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="save_plan" value="1">
            <div class="hp-grid-2">
              <div class="hs-field"><label>ID</label><input type="text" name="id" value="<?= hs_h((string) ($p['id'] ?? $editPlan)) ?>" <?= $newPlan ? '' : 'readonly' ?> pattern="[a-z0-9_-]+" required></div>
              <div class="hs-field"><label><?= hs_h($t['admin_plans_active'] ?? 'Active') ?></label><label class="hs-check"><input type="checkbox" name="active" value="1" <?= empty($p) || !empty($p['active']) ? 'checked' : '' ?>> <?= hs_h($t['admin_plans_active'] ?? 'Active') ?></label></div>
              <div class="hs-field"><label><?= hs_h($t['plan_price'] ?? 'Price') ?> (NOK)</label><input type="number" name="price_nok" step="1" min="0" value="<?= hs_h((string) ($p['price_nok'] ?? '0')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['admin_plans_was_price'] ?? 'Was price') ?></label><input type="number" name="price_was_nok" step="1" min="0" value="<?= hs_h((string) ($p['price_was_nok'] ?? '0')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_websites_limit'] ?? 'Sites') ?></label><input type="number" name="sites" min="1" value="<?= hs_h((string) ($p['sites'] ?? '1')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_storage_limit'] ?? 'Storage MB') ?></label><input type="number" name="storage_mb" min="512" value="<?= hs_h((string) ($p['storage_mb'] ?? '5120')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_disk'] ?? 'Disk GB') ?></label><input type="number" name="disk_gb" min="1" value="<?= hs_h((string) ($p['disk_gb'] ?? '5')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_ram'] ?? 'RAM MB') ?></label><input type="number" name="ram_mb" min="256" value="<?= hs_h((string) ($p['ram_mb'] ?? '1024')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_cpu_cores'] ?? 'CPU') ?></label><input type="number" name="cpu_cores" min="1" value="<?= hs_h((string) ($p['cpu_cores'] ?? '1')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_inodes'] ?? 'Inodes') ?></label><input type="number" name="inodes" min="1000" value="<?= hs_h((string) ($p['inodes'] ?? '50000')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['db_title'] ?? 'Databases') ?></label><input type="number" name="databases" min="1" value="<?= hs_h((string) ($p['databases'] ?? '2')) ?>"></div>
              <div class="hs-field"><label>Badge</label><input type="text" name="badge" value="<?= hs_h((string) ($p['badge'] ?? '')) ?>" placeholder="popular / vps"></div>
              <div class="hs-field"><label>Sort</label><input type="number" name="sort" value="<?= hs_h((string) ($p['sort'] ?? '0')) ?>"></div>
              <div class="hs-field"><label><?= hs_h($t['plan_traffic'] ?? 'Traffic') ?></label><input type="text" name="traffic" value="<?= hs_h((string) ($p['traffic'] ?? 'unlimited')) ?>"></div>
            </div>
            <button type="submit" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_save'] ?? 'Save') ?></button>
          </form>
        </div>
      </section>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="hs-admin-split">
      <section class="hp-card">
        <h2 class="hp-card-title"><?= hs_h($t['admin_services_list'] ?? 'Add-on services') ?></h2>
        <div class="hp-card-body">
          <div class="hs-table-wrap">
            <table class="hs-table">
              <thead><tr><th>ID</th><th><?= hs_h($t['admin_services_name'] ?? 'Name') ?></th><th><?= hs_h($t['plan_price'] ?? 'Price') ?></th><th></th></tr></thead>
              <tbody>
              <?php foreach ($catalog['services'] as $svc): ?>
                <tr>
                  <td><code><?= hs_h((string) ($svc['id'] ?? '')) ?></code></td>
                  <td><?= hs_h(hs_plan_catalog_service_label($svc, $lang)) ?></td>
                  <td><?= hs_h(hs_format_nok_price((float) ($svc['price_nok'] ?? 0), $lang)) ?></td>
                  <td>
                    <a href="<?= hs_h(hs_admin_url('plans.php', ['tab' => 'services', 'edit_service' => (string) ($svc['id'] ?? '')])) ?>" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><?= hs_h($t['admin_edit'] ?? 'Edit') ?></a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete?')"><?= hs_csrf_field() ?><input type="hidden" name="delete_service" value="1"><input type="hidden" name="service_id" value="<?= hs_h((string) ($svc['id'] ?? '')) ?>"><button type="submit" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-trash"></i></button></form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p style="margin-top:1rem"><a href="<?= hs_h(hs_admin_url('plans.php', ['tab' => 'services', 'new_service' => '1'])) ?>" class="hs-btn hs-btn-primary"><i class="fa-solid fa-plus"></i> <?= hs_h($t['admin_services_add'] ?? 'Add service') ?></a></p>
        </div>
      </section>

      <?php if ($editService !== '' || $newService):
        $s = [];
        if ($editService !== '') {
            foreach ($catalog['services'] as $svc) {
                if (($svc['id'] ?? '') === $editService) {
                    $s = $svc;
                    break;
                }
            }
        }
      ?>
      <section class="hp-card hs-admin-form-card">
        <h2 class="hp-card-title"><?= hs_h($newService ? ($t['admin_services_add'] ?? 'Add service') : ($t['admin_edit'] ?? 'Edit')) ?></h2>
        <div class="hp-card-body">
          <form method="post" class="hs-admin-form">
            <?= hs_csrf_field() ?>
            <input type="hidden" name="save_service" value="1">
            <div class="hs-field"><label>ID</label><input type="text" name="id" value="<?= hs_h((string) ($s['id'] ?? $editService)) ?>" <?= $newService ? '' : 'readonly' ?> pattern="[a-z0-9_-]+" required></div>
            <div class="hs-field"><label>Icon (FA)</label><input type="text" name="icon" value="<?= hs_h((string) ($s['icon'] ?? 'fa-puzzle-piece')) ?>"></div>
            <div class="hs-field"><label><?= hs_h($t['plan_price'] ?? 'Price') ?> (NOK)</label><input type="number" name="price_nok" step="1" min="0" value="<?= hs_h((string) ($s['price_nok'] ?? '0')) ?>"></div>
            <div class="hs-field"><label><?= hs_h($t['admin_plans_active'] ?? 'Active') ?></label><label class="hs-check"><input type="checkbox" name="active" value="1" <?= empty($s) || !empty($s['active']) ? 'checked' : '' ?>> on</label></div>
            <?php foreach (['uk', 'en', 'no'] as $lng): ?>
              <div class="hs-field"><label><?= hs_h($t['admin_services_name'] ?? 'Name') ?> (<?= hs_h($lng) ?>)</label><input type="text" name="label_<?= hs_h($lng) ?>" value="<?= hs_h((string) (($s['labels'][$lng] ?? ''))) ?>"></div>
              <div class="hs-field"><label>Desc (<?= hs_h($lng) ?>)</label><input type="text" name="desc_<?= hs_h($lng) ?>" value="<?= hs_h((string) (($s['desc'][$lng] ?? ''))) ?>"></div>
            <?php endforeach; ?>
            <button type="submit" class="hs-btn hs-btn-primary"><?= hs_h($t['admin_save'] ?? 'Save') ?></button>
          </form>
        </div>
      </section>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-public.php';