<?php
declare(strict_types=1);

$panel_active = 'activate';
$panel_tip_key = 'plan';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/panel-checkout.php';
require_once dirname(__DIR__) . '/includes/activation-checklist.php';
require_once dirname(__DIR__) . '/includes/order-types.php';
require_once dirname(__DIR__) . '/includes/plan-order-picker.php';
require_once dirname(__DIR__) . '/includes/domain-cart.php';
require_once dirname(__DIR__) . '/includes/platform-free-hosting.php';

$status = (string) ($user['subscription_status'] ?? 'active');
$domainPurchaseMode = false;

// Active clients: allow activate only for domain-only pay (Pay on Domains overview).
if ($status === 'active') {
    $domainHint = '';
    if (isset($_GET['domain']) && is_string($_GET['domain'])) {
        $domainHint = (string) $_GET['domain'];
    } elseif (isset($_POST['domain']) && is_string($_POST['domain'])) {
        $domainHint = (string) $_POST['domain'];
    }
    $wantDomainOrder = strtolower(trim((string) ($_GET['order'] ?? $_POST['order'] ?? ''))) === 'domain'
        || !empty($_POST['panel_domain_purchase'])
        || $domainHint !== '';
    if ($wantDomainOrder || hs_panel_is_domain_purchase_mode($user) || hs_user_unpaid_domains($user) !== []) {
        $prep = hs_user_prepare_panel_domain_purchase($user, $domainHint);
        $domainPurchaseMode = $prep['mode'];
        $user = $prep['user'];
    }
    if (!$domainPurchaseMode) {
        hs_redirect(hs_panel_path(''));
    }
}

// Merge domain search session cart → user pending_domains (domain-only pay needs this).
if ($status === 'pending' && function_exists('hs_user_sync_pending_domains')) {
    $user = hs_user_sync_pending_domains($user);
}

if ($status === 'pending' && hs_plan_is_free((string) ($user['plan'] ?? '')) && !hs_order_includes_domain($user)) {
    $act = hs_free_plan_activate_user($user, $lang);
    if (!empty($act['ok'])) {
        hs_redirect(hs_panel_path(''));
    }
}

$error = '';
$orderError = '';
$orderMsg = '';
$upgradeMsg = '';
$landingImportMsg = '';
$cancelled = isset($_GET['cancelled']);
$GLOBALS['panel_order_picker_mode'] = !$domainPurchaseMode;
hs_session_start();
if (!empty($_SESSION['hs_landing_guest_imported'])) {
    $landingImportMsg = (string) ($t['landing_guest_imported_account'] ?? 'Demo page saved to your account — open Landing builder after activation.');
    unset($_SESSION['hs_landing_guest_imported']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handledOrderAction = false;

    if (!$domainPurchaseMode && isset($_POST['reset_panel_order'])) {
        $handledOrderAction = true;
        if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
            $orderError = (string) ($t['register_error_csrf'] ?? 'Session expired. Try again.');
        } else {
            $orderRes = hs_panel_order_reset_cart($user, $lang);
            if ($orderRes['ok']) {
                $user = hs_client_ensure_identity((array) ($orderRes['user'] ?? $user));
                $orderMsg = (string) ($t['panel_order_reset_ok'] ?? 'Cart cleared. Choose a new plan or add domains.');
            } else {
                $errKey = (string) ($orderRes['error'] ?? 'save');
                $orderError = (string) ($t['panel_order_error_' . $errKey] ?? $t['panel_order_error'] ?? $errKey);
            }
        }
    }

    if (!$domainPurchaseMode && !$handledOrderAction && isset($_POST['remove_domain'])) {
        $handledOrderAction = true;
        if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
            $orderError = (string) ($t['register_error_csrf'] ?? 'Session expired. Try again.');
        } else {
            $orderRes = hs_panel_order_remove_domain($user, (string) ($_POST['remove_domain'] ?? ''), $lang);
            if ($orderRes['ok']) {
                $user = hs_client_ensure_identity((array) ($orderRes['user'] ?? $user));
                $orderMsg = (string) ($t['panel_order_domain_removed'] ?? 'Domain removed. Totals recalculated.');
            } else {
                $errKey = (string) ($orderRes['error'] ?? 'save');
                $orderError = (string) ($t['panel_order_error_' . $errKey] ?? $t['panel_order_error'] ?? $errKey);
            }
        }
    }

    if (!$domainPurchaseMode && !$handledOrderAction && isset($_POST['save_panel_order'])) {
        $handledOrderAction = true;
        $orderRes = hs_panel_order_save_from_post($user, $_POST, $lang);
        if ($orderRes['ok']) {
            $user = hs_client_ensure_identity((array) ($orderRes['user'] ?? $user));
            $orderMsg = (string) ($t['panel_order_saved'] ?? 'Order updated. Review the total below.');
        } else {
            $errKey = (string) ($orderRes['error'] ?? 'save');
            $orderError = (string) ($t['panel_order_error_' . $errKey] ?? $t['panel_order_error'] ?? $errKey);
        }
    }

    if (!$domainPurchaseMode && !$handledOrderAction && hs_csrf_verify($_POST['csrf'] ?? null) && isset($_POST['add_hosting_to_domain'])) {
        $handledOrderAction = true;
        $userId = (string) ($user['id'] ?? '');
        if ($userId !== '' && hs_user_order_type($user) === 'domain') {
            $ok = hs_user_update($userId, static function (array &$u): void {
                $u['order_type'] = 'bundle';
                if ((string) ($u['plan'] ?? '') === 'domain') {
                    $u['plan'] = 'starter';
                }
            });
            if ($ok) {
                $user = hs_client_ensure_identity(hs_client_require());
                require_once dirname(__DIR__) . '/includes/invoices.php';
                if (function_exists('hs_invoice_rebuild_pending_checkout')) {
                    hs_invoice_rebuild_pending_checkout($user, $lang);
                    $user = hs_user_by_id((string) ($user['id'] ?? '')) ?? $user;
                }
                $upgradeMsg = (string) ($t['panel_activate_upgrade_success'] ?? 'Hosting added to your order.');
            }
        }
    }

    if (!$handledOrderAction) {
        $res = hs_panel_checkout_handle_post($user, $t, $lang);
        $error = $res['error'];
    } else {
        $res = ['error' => '', 'redirect' => ''];
    }
    if (!empty($res['redirect'])) {
        header('Location: ' . $res['redirect'], true, 302);
        exit;
    }
}

$ctx = hs_panel_checkout_context($user, $t, $lang);
$domainPurchaseMode = $domainPurchaseMode || !empty($ctx['panelDomainPurchase']);
$orderState = $domainPurchaseMode
    ? ['order_type' => 'domain', 'plan' => (string) ($user['plan'] ?? ''), 'pending_domains' => hs_user_pending_domains($user)]
    : hs_panel_order_form_state(
        $user,
        $_GET,
        ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_panel_order']) && $orderError !== '') ? $_POST : null
    );
$page_title = $domainPurchaseMode
    ? (string) ($t['panel_domain_pay_title'] ?? $ctx['checkoutTitle'])
    : $ctx['checkoutTitle'];

ob_start();
?>
<div class="hp-activate-layout<?= $domainPurchaseMode ? ' hp-activate-domain-only' : '' ?>">
  <?php if (!$domainPurchaseMode): ?>
  <aside class="hp-activate-aside">
    <?= hs_render_activation_checklist($user, $t) ?>
    <?= hs_render_activate_ssl_note($t) ?>
    <?= hs_render_activate_domain_links($user, $ctx, $t) ?>
  </aside>
  <?php endif; ?>
  <div class="hp-activate-main">
  <?php if (!$domainPurchaseMode): ?>
  <div class="hp-card hs-panel-activate-order">
    <h2 class="hp-card-title"><i class="fa-solid fa-sliders"></i> <?= hs_h($t['panel_order_section'] ?? 'Configure your order') ?></h2>
    <div class="hp-card-body">
      <?php if ($orderMsg !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($orderMsg) ?></div><?php endif; ?>
      <?php if ($orderError !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($orderError) ?></div><?php endif; ?>
      <?= hs_render_panel_order_picker($user, $t, $lang, $orderState, ['context' => 'activate']) ?>
    </div>
  </div>
  <?php endif; ?>
  <div class="hp-card hs-panel-activate-pay">
    <h2 class="hp-card-title"><i class="fa-solid fa-credit-card"></i> <?= hs_h($page_title) ?></h2>
    <p class="hp-muted" style="margin:0 1.25rem 1rem;font-size:.9rem"><?= hs_h(
        $domainPurchaseMode
            ? ($t['panel_domain_pay_lead'] ?? 'Pay to register this domain. Your hosting plan stays as is.')
            : ($t['panel_activate_lead'] ?? 'Complete payment to activate your account and register the domain.')
    ) ?></p>
    <div class="hp-card-body">
      <?php if ($upgradeMsg !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($upgradeMsg) ?></div><?php endif; ?>
      <?php if ($landingImportMsg !== ''): ?><div class="hs-alert hs-alert-success"><i class="fa-solid fa-layer-group"></i> <?= hs_h($landingImportMsg) ?></div><?php endif; ?>
      <?php if ($domainPurchaseMode && $cancelled): ?>
        <div class="hs-alert"><?= hs_h($t['checkout_cancelled'] ?? 'Payment cancelled.') ?></div>
      <?php endif; ?>
      <?= hs_panel_checkout_render($user, $ctx, $t, $lang, $error, $cancelled && !$domainPurchaseMode) ?>
      <?php if ($domainPurchaseMode): ?>
        <p style="margin-top:1rem">
          <a href="<?= hs_h(hs_url(hs_panel_path('domains.php'), ['tab' => 'overview'])) ?>" class="hs-btn hs-btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> <?= hs_h($t['panel_domain_pay_back'] ?? 'Back to domains') ?>
          </a>
        </p>
      <?php endif; ?>
    </div>
  </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';
