<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/client-auth.php';
require_once __DIR__ . '/includes/plans.php';
require_once __DIR__ . '/includes/domain-store.php';

hs_seed_demo_data();
$user = hs_client_require();
$planId = (string) ($user['plan'] ?? 'starter');
$status = (string) ($user['subscription_status'] ?? 'pending');
$pendingDomain = (string) ($user['pending_domain'] ?? '');
$domainPrice = $pendingDomain !== '' ? (hs_domain_price($pendingDomain) ?? 0) : 0;

if ($status === 'active') {
    hs_redirect(hs_panel_path(''));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } else {
        $userId = (string) ($user['id'] ?? '');
        $activeUser = $user;
        $pd = '';
        $users = hs_users();
        foreach ($users as &$u) {
            if (($u['id'] ?? '') === $userId) {
                $u['subscription_status'] = 'active';
                $u['paid_until'] = gmdate('c', strtotime('+1 month'));
                $pd = (string) ($u['pending_domain'] ?? '');
                if ($pd !== '') {
                    hs_domain_bind_to_user($userId, $pd, true, true);
                    $u['pending_domain'] = null;
                }
                $activeUser = $u;
                break;
            }
        }
        unset($u);
        hs_save_users($users);
        require_once __DIR__ . '/includes/mysql-provision.php';
        hs_ensure_user_database($userId, (string) ($activeUser['username'] ?? 'user'), $activeUser);
        require_once __DIR__ . '/includes/order-notifications.php';
        $planPrice = (float) (hs_plan($planId)['price_nok'] ?? 0);
        $notifyPayload = ['lang' => $lang, 'price_nok' => $planPrice];
        if ($pd !== '') {
            $domPrice = (float) (hs_domain_price($pd) ?? 0);
            $notifyPayload['domain'] = $pd;
            $notifyPayload['domain_price_nok'] = $domPrice;
            $notifyPayload['price_nok'] += $domPrice;
        }
        hs_notify_order_event('plan_activated', $activeUser, $notifyPayload);
        hs_session_start();
        unset($_SESSION['hs_pending_domain']);
        hs_redirect(hs_panel_path(''));
    }
}

ob_start();
?>
<div class="hs-auth-wrap">
  <div class="hs-auth-card" style="max-width:480px">
    <h1><?= hs_h($t['checkout_title'] ?? 'Complete payment') ?></h1>
    <?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>
    <p class="hp-muted"><?= hs_h($t['checkout_plan'] ?? '') ?>: <strong><?= hs_h($t['plan_' . $planId] ?? $planId) ?></strong></p>
    <?php if ($pendingDomain !== ''): ?>
    <p class="hp-muted"><?= hs_h($t['checkout_domain'] ?? 'Domain') ?>: <strong><?= hs_h($pendingDomain) ?></strong>
      â€” <?= hs_h(hs_domain_format_price($domainPrice, $lang)) ?></p>
    <?php endif; ?>
    <p class="hp-muted" style="font-size:1.25rem;font-weight:700;color:var(--hs-accent)">
      <?= hs_h(hs_format_plan_price($planId, $lang)) ?><?= hs_h($t['per_month'] ?? '') ?>
    </p>
    <form method="post">
      <?= hs_csrf_field() ?>
      <div class="hs-field">
        <label><?= hs_h($t['checkout_card'] ?? 'Card number') ?></label>
        <input type="text" placeholder="4242 4242 4242 4242" autocomplete="off">
      </div>
      <button type="submit" class="hs-btn hs-btn-primary" style="width:100%"><?= hs_h($t['checkout_pay'] ?? 'Pay & activate') ?></button>
    </form>
    <p class="hp-muted" style="margin-top:1rem;font-size:.8rem"><?= hs_h($t['checkout_demo'] ?? '') ?></p>
  </div>
</div>
<?php
$content = ob_get_clean();
$page_title = $t['checkout_title'] ?? '';
require __DIR__ . '/includes/layout-public.php';