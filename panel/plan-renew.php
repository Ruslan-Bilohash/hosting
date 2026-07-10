<?php
declare(strict_types=1);

$panel_active = 'plan-renew';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/plan-renew-ui.php';

$page_title = $t['btn_renew'] ?? 'Renew';
$panel_tip_key = 'plan';

$planId = (string) ($user['plan'] ?? 'starter');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_plan'])) {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? '';
    } else {
        $months = max(1, min(24, (int) ($_POST['renew_months'] ?? 1)));
        $users = hs_users();
        $userId = (string) $user['id'];
        foreach ($users as &$u) {
            if (($u['id'] ?? '') === $userId) {
                $base = $u['paid_until'] ?? gmdate('c');
                $baseTs = strtotime((string) $base);
                if ($baseTs < time()) {
                    $baseTs = time();
                }
                $u['paid_until'] = gmdate('c', strtotime('+' . $months . ' month', $baseTs));
                $u['subscription_status'] = 'active';
                $u['active'] = true;
                $user = $u;
                break;
            }
        }
        unset($u);
        hs_save_users($users);
        $success = str_replace('{n}', (string) $months, $t['plan_renewed_months'] ?? $t['plan_renewed'] ?? 'Renewed');
        if (function_exists('hs_panel_log')) {
            require_once dirname(__DIR__) . '/includes/panel-features.php';
            hs_panel_log($userId, 'plan_renew', (string) $months . 'm');
        }
        require_once dirname(__DIR__) . '/includes/order-notifications.php';
        $planPrice = (float) (hs_plan($planId)['price_nok'] ?? 0) * $months;
        hs_notify_order_event('plan_renew', $user, ['lang' => $lang, 'price_nok' => $planPrice, 'months' => $months]);
    }
}

ob_start();
echo hs_plan_renew_page($user, $t, $lang, $error, $success);
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';