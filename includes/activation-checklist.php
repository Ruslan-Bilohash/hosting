<?php
declare(strict_types=1);

require_once __DIR__ . '/panel-ui.php';

/**
 * Resolve i18n string; never show raw key names in the UI.
 */
function hs_activation_t(array $t, string $key, string $fallback): string
{
    $val = $t[$key] ?? null;
    if (!is_string($val)) {
        return $fallback;
    }
    $val = trim($val);
    if ($val === '' || $val === $key) {
        return $fallback;
    }

    return $val;
}

/**
 * @return list<array{id:string,icon:string,title_key:string,tip_key:string,title:string,tip:string,url:string,done:bool,current:bool}>
 */
function hs_activation_checklist_steps(array $user, array $t): array
{
    $pending = hs_user_subscription_pending($user);
    return [
        [
            'id' => 'account',
            'icon' => 'fa-user-check',
            'title_key' => 'activate_step_account',
            'tip_key' => 'activate_tip_account',
            'title' => hs_activation_t($t, 'activate_step_account', 'Account created'),
            'tip' => hs_activation_t($t, 'activate_tip_account', 'Registration complete — payment is the next step.'),
            'url' => hs_panel_path(''),
            'done' => true,
            'current' => false,
        ],
        [
            'id' => 'pay',
            'icon' => 'fa-credit-card',
            'title_key' => 'activate_step_pay',
            'tip_key' => 'activate_tip_pay',
            'title' => hs_activation_t($t, 'activate_step_pay', 'Pay for hosting'),
            'tip' => hs_activation_t($t, 'activate_tip_pay', 'Pay your plan — we register the domain and activate hosting.'),
            'url' => hs_panel_path('activate.php'),
            'done' => !$pending,
            'current' => $pending,
        ],
        [
            'id' => 'live',
            'icon' => 'fa-rocket',
            'title_key' => 'activate_step_live',
            'tip_key' => 'activate_tip_live',
            'title' => hs_activation_t($t, 'activate_step_live', 'Site online'),
            'tip' => hs_activation_t($t, 'activate_tip_live', 'After payment your site opens at the address below.'),
            'url' => hs_panel_path(''),
            'done' => !$pending,
            'current' => false,
        ],
    ];
}

function hs_render_activation_checklist(array $user, array $t): string
{
    if (!function_exists('hs_user_subscription_pending')) {
        require_once __DIR__ . '/panel-ui.php';
    }
    if (!hs_user_subscription_pending($user)) {
        return '';
    }

    $steps = hs_activation_checklist_steps($user, $t);
    $done = 0;
    foreach ($steps as $step) {
        if (!empty($step['done'])) {
            $done++;
        }
    }
    $pct = (int) round(($done / max(1, count($steps))) * 100);

    $items = '';
    foreach ($steps as $i => $step) {
        $isDone = !empty($step['done']);
        $isCurrent = !empty($step['current']);
        $title = (string) ($step['title'] ?? hs_activation_t($t, (string) ($step['title_key'] ?? ''), (string) ($step['title_key'] ?? '')));
        $tip = (string) ($step['tip'] ?? hs_activation_t($t, (string) ($step['tip_key'] ?? ''), ''));
        $url = hs_url((string) ($step['url'] ?? hs_panel_path('')));
        $cls = 'hp-activate-step';
        if ($isDone) {
            $cls .= ' is-done';
        }
        if ($isCurrent) {
            $cls .= ' is-current';
        }

        $items .= '<li class="' . $cls . '">'
            . '<div class="hp-activate-step-icon" aria-hidden="true"><i class="fa-solid ' . ($isDone ? 'fa-circle-check' : ($isCurrent ? 'fa-circle-dot' : 'fa-circle')) . '"></i></div>'
            . '<div class="hp-activate-step-body">'
            . '<div class="hp-activate-step-head"><span class="hp-activate-step-num">' . ($i + 1) . '</span>'
            . '<i class="fa-solid ' . hs_h($step['icon']) . '"></i> <strong>' . hs_h($title) . '</strong></div>';
        if ($tip !== '') {
            $items .= '<p class="hp-activate-step-tip">' . hs_h($tip) . '</p>';
        }
        if ($isCurrent) {
            $items .= '<a href="' . hs_h($url) . '" class="hs-btn hs-btn-primary hp-dash-btn-sm"><i class="fa-solid fa-credit-card"></i> '
                . hs_h(hs_activation_t($t, 'panel_activate_pay_btn', 'Pay now')) . '</a>';
        }
        $items .= '</div></li>';
    }

    return '<section class="hp-activate-card" style="--hp-activate-pct:' . $pct . '">'
        . '<div class="hp-activate-head">'
        . '<h2 class="hp-activate-title"><i class="fa-solid fa-list-check"></i> '
        . hs_h(hs_activation_t($t, 'activate_checklist_title', 'What to do next')) . '</h2>'
        . '<p class="hp-activate-sub">'
        . hs_h(hs_activation_t($t, 'activate_checklist_sub', 'Three simple steps to launch your site.')) . '</p>'
        . '</div>'
        . '<ol class="hp-activate-steps">' . $items . '</ol>'
        . '</section>';
}

/** @param array<string, mixed> $user */
/** @param array<string, mixed> $ctx */
function hs_render_activate_ssl_note(array $t): string
{
    return '<section class="hp-activate-side-card hp-activate-ssl">'
        . '<div class="hp-activate-ssl-badge"><i class="fa-solid fa-lock"></i> '
        . hs_h($t['panel_activate_ssl_secure'] ?? 'Secure connection') . '</div>'
        . '<h3 class="hp-activate-side-title"><i class="fa-solid fa-shield-halved"></i> '
        . hs_h($t['panel_activate_ssl_title'] ?? 'SSL & security') . '</h3>'
        . '<p class="hp-activate-side-text">' . hs_h($t['panel_activate_ssl_note'] ?? '') . '</p>'
        . '</section>';
}

/** @param array<string, mixed> $user */
/** @param array<string, mixed> $ctx */
function hs_render_activate_domain_links(array $user, array $ctx, array $t): string
{
    $orderType = (string) ($ctx['orderType'] ?? hs_user_order_type($user));
    $pendingDomain = (string) ($ctx['pendingDomain'] ?? ($user['pending_domain'] ?? ''));
    $links = '';

    if ($orderType === 'domain') {
        $links .= '<form method="post" class="hp-activate-upgrade-form">'
            . hs_csrf_field()
            . '<input type="hidden" name="add_hosting_to_domain" value="1">'
            . '<p class="hp-activate-side-text">' . hs_h($t['panel_activate_link_add_hosting_desc'] ?? '') . '</p>'
            . '<button type="submit" class="hs-btn hs-btn-primary hp-dash-btn-sm" style="width:100%">'
            . '<i class="fa-solid fa-server"></i> ' . hs_h($t['panel_activate_link_add_hosting'] ?? 'Add hosting to domain')
            . '</button></form>';
    }

    $domainsUrl = hs_url(hs_panel_path('domains.php'));
    $dnsUrl = hs_url(hs_panel_path('domains.php'), ['tab' => 'dns']);
    $links .= '<ul class="hp-activate-side-links">'
        . '<li><a href="' . hs_h($domainsUrl) . '"><i class="fa-solid fa-globe"></i> '
        . hs_h($t['panel_activate_link_domains'] ?? 'Domains & DNS') . '</a></li>';
    if ($pendingDomain !== '') {
        $links .= '<li><a href="' . hs_h($dnsUrl) . '"><i class="fa-solid fa-network-wired"></i> '
            . hs_h($t['panel_activate_link_dns'] ?? 'Connect domain to hosting') . '</a></li>';
    }
    $links .= '</ul>';

    return '<section class="hp-activate-side-card hp-activate-domain">'
        . '<h3 class="hp-activate-side-title"><i class="fa-solid fa-link"></i> '
        . hs_h($t['panel_activate_domain_title'] ?? 'Domain & hosting') . '</h3>'
        . $links
        . '</section>';
}