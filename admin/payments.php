<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/init.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/payments.php';

hs_admin_require();
$admin_active = 'payments';

$settings = hs_payment_settings_load();
$error = '';
$success = '';
$testResult = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif (isset($_POST['test_stripe'])) {
        $res = hs_payment_test_stripe();
        $testResult = $res['ok']
            ? ($t['admin_payments_test_ok'] ?? 'Stripe connection OK')
            : (($t['admin_payments_test_fail'] ?? 'Test failed') . ': ' . ($res['error'] ?? '') . ' ' . ($res['detail'] ?? ''));
    } elseif (isset($_POST['test_paypal'])) {
        $res = hs_payment_test_paypal();
        $testResult = $res['ok']
            ? ($t['admin_payments_test_ok'] ?? 'PayPal connection OK')
            : (($t['admin_payments_test_fail'] ?? 'Test failed') . ': ' . ($res['error'] ?? ''));
    } elseif (isset($_POST['save_payments'])) {
        $next = hs_payment_settings_defaults();
        $next['mode'] = (string) ($_POST['mode'] ?? 'test');
        $next['charge_currency'] = strtoupper((string) ($_POST['charge_currency'] ?? 'EUR'));
        $next['simulated_enabled'] = !empty($_POST['simulated_enabled']);
        $next['stripe_enabled'] = !empty($_POST['stripe_enabled']);
        $next['paypal_enabled'] = !empty($_POST['paypal_enabled']);

        $next['stripe_test_pk'] = hs_payment_merge_secret((string) ($_POST['stripe_test_pk'] ?? ''), (string) ($settings['stripe_test_pk'] ?? ''));
        $next['stripe_test_sk'] = hs_payment_merge_secret((string) ($_POST['stripe_test_sk'] ?? ''), (string) ($settings['stripe_test_sk'] ?? ''));
        $next['stripe_live_pk'] = hs_payment_merge_secret((string) ($_POST['stripe_live_pk'] ?? ''), (string) ($settings['stripe_live_pk'] ?? ''));
        $next['stripe_live_sk'] = hs_payment_merge_secret((string) ($_POST['stripe_live_sk'] ?? ''), (string) ($settings['stripe_live_sk'] ?? ''));
        $next['stripe_webhook_test_secret'] = hs_payment_merge_secret((string) ($_POST['stripe_webhook_test_secret'] ?? ''), (string) ($settings['stripe_webhook_test_secret'] ?? ''));
        $next['stripe_webhook_live_secret'] = hs_payment_merge_secret((string) ($_POST['stripe_webhook_live_secret'] ?? ''), (string) ($settings['stripe_webhook_live_secret'] ?? ''));

        $next['paypal_test_client_id'] = hs_payment_merge_secret((string) ($_POST['paypal_test_client_id'] ?? ''), (string) ($settings['paypal_test_client_id'] ?? ''));
        $next['paypal_test_secret'] = hs_payment_merge_secret((string) ($_POST['paypal_test_secret'] ?? ''), (string) ($settings['paypal_test_secret'] ?? ''));
        $next['paypal_live_client_id'] = hs_payment_merge_secret((string) ($_POST['paypal_live_client_id'] ?? ''), (string) ($settings['paypal_live_client_id'] ?? ''));
        $next['paypal_live_secret'] = hs_payment_merge_secret((string) ($_POST['paypal_live_secret'] ?? ''), (string) ($settings['paypal_live_secret'] ?? ''));

        if (hs_payment_settings_save($next)) {
            $success = $t['admin_payments_saved'] ?? 'Payment settings saved';
            $settings = hs_payment_settings_load();
        } else {
            $error = $t['admin_plans_save_fail'] ?? 'Save failed';
        }
    }
}

$webhookUrl = hs_absolute_url('payment-webhook.php');
$returnUrl = hs_absolute_url('payment-return.php');
$mode = hs_payment_mode();

$page_title = $t['admin_payments_title'] ?? 'Payments';
ob_start();
?>
<div class="hs-admin-page">
  <nav class="hs-admin-tabs" style="margin-bottom:1.25rem">
    <a href="<?= hs_h(hs_admin_url('namecheap.php')) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-globe"></i> <?= hs_h($t['admin_hostinger_title'] ?? $t['admin_namecheap_title'] ?? 'Domains') ?></a>
    <a href="<?= hs_h(hs_admin_url('payments.php')) ?>" class="hs-btn hs-btn-ghost is-active"><i class="fa-solid fa-credit-card"></i> <?= hs_h($t['admin_payments_title'] ?? 'Payments') ?></a>
    <a href="<?= hs_h(hs_admin_url('plans.php')) ?>" class="hs-btn hs-btn-ghost"><i class="fa-solid fa-layer-group"></i> <?= hs_h($t['admin_plans_title'] ?? 'Plans') ?></a>
    <a href="<?= hs_h(hs_admin_url()) ?>" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_title'] ?? 'Admin') ?></a>
  </nav>

  <?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
  <?php if ($testResult !== ''): ?><div class="hs-alert <?= str_contains($testResult, 'OK') ? 'hs-alert-success' : 'hs-alert-error' ?>"><?= hs_h($testResult) ?></div><?php endif; ?>

  <div class="hp-card" style="margin-bottom:1.25rem">
    <h2 class="hp-card-title"><?= hs_h($t['admin_payments_status'] ?? 'Status') ?></h2>
    <div class="hp-card-body">
      <ul class="hs-admin-pay-status">
        <li><strong>Stripe:</strong> <?= hs_payment_stripe_enabled() ? '✅ ' . hs_h($t['admin_payments_on'] ?? 'On') : '— ' . hs_h($t['admin_payments_off'] ?? 'Off') ?></li>
        <li><strong>PayPal:</strong> <?= hs_payment_paypal_enabled() ? '✅ ' . hs_h($t['admin_payments_on'] ?? 'On') : '— ' . hs_h($t['admin_payments_off'] ?? 'Off') ?></li>
        <li><strong><?= hs_h($t['admin_payments_demo'] ?? 'Demo checkout') ?>:</strong> <?= hs_simulated_payment_allowed() ? hs_h($t['admin_payments_on'] ?? 'On') : hs_h($t['admin_payments_off'] ?? 'Off') ?></li>
        <li><strong><?= hs_h($t['admin_payments_mode'] ?? 'Mode') ?>:</strong> <?= hs_h($mode === 'live' ? ($t['admin_payments_live'] ?? 'Live') : ($t['admin_payments_test'] ?? 'Test')) ?></li>
      </ul>
      <p class="hp-muted" style="margin-top:.75rem;font-size:.85rem">
        <?= hs_h($t['admin_payments_webhook'] ?? 'Stripe webhook URL') ?>: <code><?= hs_h($webhookUrl) ?></code><br>
        <?= hs_h($t['admin_payments_return'] ?? 'Return URL') ?>: <code><?= hs_h($returnUrl) ?>?provider=…</code>
      </p>
    </div>
  </div>

  <form method="post" class="hp-card">
    <h2 class="hp-card-title"><?= hs_h($t['admin_payments_configure'] ?? 'Configure gateways') ?></h2>
    <div class="hp-card-body hp-stack">
      <?= hs_csrf_field() ?>

      <div class="hp-grid-2">
        <div class="hs-field">
          <label><?= hs_h($t['admin_payments_mode'] ?? 'Mode') ?></label>
          <select name="mode">
            <option value="test"<?= $mode !== 'live' ? ' selected' : '' ?>><?= hs_h($t['admin_payments_test'] ?? 'Test / sandbox') ?></option>
            <option value="live"<?= $mode === 'live' ? ' selected' : '' ?>><?= hs_h($t['admin_payments_live'] ?? 'Live production') ?></option>
          </select>
        </div>
        <div class="hs-field">
          <label><?= hs_h($t['admin_payments_currency'] ?? 'Charge currency') ?></label>
          <select name="charge_currency">
            <?php foreach (['EUR', 'USD', 'NOK', 'UAH'] as $cur): ?>
            <option value="<?= hs_h($cur) ?>"<?= ($settings['charge_currency'] ?? 'EUR') === $cur ? ' selected' : '' ?>><?= hs_h($cur) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label class="hs-check"><input type="checkbox" name="simulated_enabled" value="1" <?= !empty($settings['simulated_enabled']) ? 'checked' : '' ?>> <?= hs_h($t['admin_payments_allow_demo'] ?? 'Allow demo checkout (no real payment)') ?></label>

      <h3 class="hs-reg-subtitle"><i class="fa-brands fa-stripe"></i> Stripe</h3>
      <label class="hs-check"><input type="checkbox" name="stripe_enabled" value="1" <?= !empty($settings['stripe_enabled']) ? 'checked' : '' ?>> <?= hs_h($t['admin_payments_stripe_enable'] ?? 'Enable Stripe Checkout') ?></label>
      <div class="hp-grid-2">
        <div class="hs-field"><label>Stripe test publishable key</label><input type="text" name="stripe_test_pk" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['stripe_test_pk'] ?? ''))) ?>" placeholder="pk_test_…" autocomplete="off"></div>
        <div class="hs-field"><label>Stripe test secret key</label><input type="password" name="stripe_test_sk" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['stripe_test_sk'] ?? ''))) ?>" placeholder="sk_test_…" autocomplete="new-password"></div>
        <div class="hs-field"><label>Stripe live publishable key</label><input type="text" name="stripe_live_pk" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['stripe_live_pk'] ?? ''))) ?>" placeholder="pk_live_…" autocomplete="off"></div>
        <div class="hs-field"><label>Stripe live secret key</label><input type="password" name="stripe_live_sk" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['stripe_live_sk'] ?? ''))) ?>" placeholder="sk_live_…" autocomplete="new-password"></div>
        <div class="hs-field"><label>Stripe webhook secret (test)</label><input type="password" name="stripe_webhook_test_secret" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['stripe_webhook_test_secret'] ?? ''))) ?>" placeholder="whsec_…" autocomplete="new-password"></div>
        <div class="hs-field"><label>Stripe webhook secret (live)</label><input type="password" name="stripe_webhook_live_secret" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['stripe_webhook_live_secret'] ?? ''))) ?>" placeholder="whsec_…" autocomplete="new-password"></div>
      </div>
      <button type="submit" name="test_stripe" value="1" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_payments_test_stripe'] ?? 'Test Stripe keys') ?></button>

      <h3 class="hs-reg-subtitle"><i class="fa-brands fa-paypal"></i> PayPal</h3>
      <label class="hs-check"><input type="checkbox" name="paypal_enabled" value="1" <?= !empty($settings['paypal_enabled']) ? 'checked' : '' ?>> <?= hs_h($t['admin_payments_paypal_enable'] ?? 'Enable PayPal') ?></label>
      <div class="hp-grid-2">
        <div class="hs-field"><label>PayPal sandbox client ID</label><input type="text" name="paypal_test_client_id" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['paypal_test_client_id'] ?? ''))) ?>" autocomplete="off"></div>
        <div class="hs-field"><label>PayPal sandbox secret</label><input type="password" name="paypal_test_secret" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['paypal_test_secret'] ?? ''))) ?>" autocomplete="new-password"></div>
        <div class="hs-field"><label>PayPal live client ID</label><input type="text" name="paypal_live_client_id" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['paypal_live_client_id'] ?? ''))) ?>" autocomplete="off"></div>
        <div class="hs-field"><label>PayPal live secret</label><input type="password" name="paypal_live_secret" value="<?= hs_h(hs_payment_mask_secret((string) ($settings['paypal_live_secret'] ?? ''))) ?>" autocomplete="new-password"></div>
      </div>
      <button type="submit" name="test_paypal" value="1" class="hs-btn hs-btn-ghost"><?= hs_h($t['admin_payments_test_paypal'] ?? 'Test PayPal keys') ?></button>

      <p class="hp-muted" style="font-size:.85rem"><?= hs_h($t['admin_payments_lead'] ?? 'Get test keys from Stripe Dashboard and PayPal Developer. Disable demo checkout when going live.') ?></p>

      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem">
        <button type="submit" name="save_payments" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= hs_h($t['admin_save'] ?? 'Save') ?></button>
      </div>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-admin.php';