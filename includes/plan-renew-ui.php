<?php
declare(strict_types=1);

require_once __DIR__ . '/plan-specs.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/panel-ui.php';

/** @param array<string,mixed> $user */
function hs_plan_renew_days_left(array $user): ?int
{
    $paidUntil = (string) ($user['paid_until'] ?? '');
    if ($paidUntil === '') {
        return null;
    }
    return (int) floor((strtotime($paidUntil) - time()) / 86400);
}

/** @param array<string,mixed> $user */
function hs_plan_renew_page(array $user, array $t, string $lang, string $error = '', string $success = ''): string
{
    $planId = (string) ($user['plan'] ?? 'starter');
    $paidUntil = (string) ($user['paid_until'] ?? '');
    $renewDate = $paidUntil !== '' ? date('Y-m-d', strtotime($paidUntil)) : date('Y-m-d', strtotime('+1 month'));
    $daysLeft = hs_plan_renew_days_left($user);
    $price = hs_format_plan_price($planId, $lang);
    $status = (string) ($user['subscription_status'] ?? 'active');
    $statusLabel = match ($status) {
        'pending' => $t['plan_status_pending'] ?? 'Pending',
        'suspended' => $t['plan_status_suspended'] ?? 'Suspended',
        default => $t['status_active'] ?? 'Active',
    };
    $statusClass = match ($status) {
        'pending' => 'hs-plan-status-pending',
        'suspended' => 'hs-plan-status-suspended',
        default => 'hs-plan-status-active',
    };

    $daysHtml = '';
    if ($daysLeft !== null) {
        $daysCls = $daysLeft <= 7 ? 'hs-renew-urgent' : ($daysLeft <= 14 ? 'hs-renew-soon' : '');
        $daysHtml = '<div class="hs-renew-days ' . $daysCls . '">'
            . '<i class="fa-solid fa-calendar-day"></i> '
            . hs_h(str_replace('{n}', (string) max(0, $daysLeft), $t['plan_renew_days_left'] ?? '{n} days left'))
            . '</div>';
    }

    $periods = [
        1 => ['label' => $t['plan_renew_1m'] ?? '1 month', 'mult' => 1],
        3 => ['label' => $t['plan_renew_3m'] ?? '3 months', 'mult' => 3],
        12 => ['label' => $t['plan_renew_12m'] ?? '12 months', 'mult' => 12],
    ];
    $baseNok = (float) (hs_plan($planId)['price_nok'] ?? 0);
    $periodCards = '';
    foreach ($periods as $months => $meta) {
        $totalNok = round($baseNok * $meta['mult'], 2);
        $totalLabel = hs_format_nok_price($totalNok, $lang);
        $periodCards .= '<label class="hs-renew-period">'
            . '<input type="radio" name="renew_months" value="' . (int) $months . '"' . ($months === 1 ? ' checked' : '') . '>'
            . '<span class="hs-renew-period-inner">'
            . '<strong>' . hs_h($meta['label']) . '</strong>'
            . '<span class="hs-renew-period-price">' . hs_h($totalLabel) . '</span>'
            . '<span class="hp-muted">' . hs_h($price) . hs_h($t['per_month'] ?? '') . '</span>'
            . '</span></label>';
    }

    $alerts = '';
    if ($success !== '') {
        $alerts .= '<div class="hs-alert hs-alert-success">' . hs_h($success) . '</div>';
    }
    if ($error !== '') {
        $alerts .= '<div class="hs-alert hs-alert-error">' . hs_h($error) . '</div>';
    }

    return $alerts
        . '<div class="hs-renew-layout">'
        . '<section class="hs-renew-hero">'
        . '<div class="hs-renew-hero-top">'
        . '<span class="hs-plan-status ' . $statusClass . '">' . hs_h($statusLabel) . '</span>'
        . $daysHtml
        . '</div>'
        . '<h2>' . hs_h(hs_plan_hosting_label($planId, $t)) . '</h2>'
        . '<p class="hp-muted">' . hs_h($t['plan_renew_lead'] ?? '') . '</p>'
        . '<div class="hs-renew-meta">'
        . '<div><span class="label">' . hs_h($t['plan_renews'] ?? '') . '</span><strong>' . hs_h($renewDate) . '</strong></div>'
        . '<div><span class="label">' . hs_h($t['plan_price'] ?? '') . '</span><strong>' . hs_h($price) . hs_h($t['per_month'] ?? '') . '</strong></div>'
        . '</div>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('invoices.php'))) . '" class="hs-btn hs-btn-ghost hs-renew-invoices"><i class="fa-solid fa-file-invoice-dollar"></i> '
        . hs_h($t['nav_invoices'] ?? '') . '</a>'
        . '</section>'
        . '<section class="hs-renew-checkout">'
        . '<h3>' . hs_h($t['plan_renew_checkout'] ?? 'Renew subscription') . '</h3>'
        . '<form method="post" class="hs-renew-form">'
        . hs_csrf_field()
        . '<fieldset class="hs-renew-periods"><legend>' . hs_h($t['plan_renew_period'] ?? 'Period') . '</legend>'
        . $periodCards . '</fieldset>'
        . '<div class="hs-field"><label>' . hs_h($t['checkout_card'] ?? 'Card') . '</label>'
        . '<input type="text" name="card_display" value="•••• •••• •••• 4242" readonly class="hs-renew-card-mock"></div>'
        . '<p class="hp-muted hs-renew-demo-note">' . hs_h($t['plan_renew_demo_note'] ?? '') . '</p>'
        . '<div class="hs-renew-actions">'
        . '<button type="submit" name="renew_plan" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-rotate"></i> '
        . hs_h($t['plan_renew_pay_btn'] ?? $t['btn_renew'] ?? 'Renew') . '</button>'
        . '<a href="' . hs_h(hs_url(hs_panel_path('plan.php'))) . '" class="hs-btn hs-btn-ghost">' . hs_h($t['nav_plan_details'] ?? '') . '</a>'
        . '</div></form></section></div>';
}