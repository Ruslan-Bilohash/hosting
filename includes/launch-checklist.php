<?php
declare(strict_types=1);

/** @return array<string, mixed> */
function hs_launch_checklist_defaults(): array
{
    return [
        'dismissed' => false,
        'dismissed_at' => '',
    ];
}

/** @return list<array{id:string,icon:string,title_key:string,tip_key:string,url:string,done:bool}> */
function hs_launch_checklist_steps(array $user, array $settings, array $sites, array $t): array
{
    $userId = (string) ($user['id'] ?? '');
    $landing = is_array($settings['landing_builder'] ?? null) ? $settings['landing_builder'] : [];
    $published = (string) ($landing['published_at'] ?? '') !== '';
    $domain = trim((string) ($settings['primary_domain'] ?? ''));
    $hasPass = hs_master_password_plain($userId) !== '';
    $dbs = is_array($settings['databases'] ?? null) ? $settings['databases'] : [];
    $mail = is_array($settings['mailboxes'] ?? null) ? $settings['mailboxes'] : [];

    $steps = [
        [
            'id' => 'landing',
            'icon' => 'fa-paintbrush',
            'title_key' => 'launch_step_landing',
            'tip_key' => 'launch_tip_landing',
            'url' => hs_panel_path('landing-builder.php'),
            'done' => $published,
        ],
        [
            'id' => 'domain',
            'icon' => 'fa-globe',
            'title_key' => 'launch_step_domain',
            'tip_key' => 'launch_tip_domain',
            'url' => hs_panel_tab_href('domains', 'dns'),
            'done' => $domain !== '' && $domain !== 'localhost',
        ],
        [
            'id' => 'password',
            'icon' => 'fa-key',
            'title_key' => 'launch_step_password',
            'tip_key' => 'launch_tip_password',
            'url' => hs_panel_path('account.php'),
            'done' => $hasPass,
        ],
        [
            'id' => 'ssl',
            'icon' => 'fa-lock',
            'title_key' => 'launch_step_ssl',
            'tip_key' => 'launch_tip_ssl',
            'url' => hs_panel_tab_href('security', 'ssl'),
            'done' => !empty($settings['ssl_enabled']),
        ],
        [
            'id' => 'email',
            'icon' => 'fa-envelope',
            'title_key' => 'launch_step_email',
            'tip_key' => 'launch_tip_email',
            'url' => hs_panel_path('email.php'),
            'done' => $mail !== [],
        ],
        [
            'id' => 'database',
            'icon' => 'fa-database',
            'title_key' => 'launch_step_database',
            'tip_key' => 'launch_tip_database',
            'url' => hs_panel_tab_href('databases', 'manage'),
            'done' => $dbs !== [],
            'optional' => true,
        ],
        [
            'id' => 'backup',
            'icon' => 'fa-clock-rotate-left',
            'title_key' => 'launch_step_backup',
            'tip_key' => 'launch_tip_backup',
            'url' => hs_panel_path('backups.php'),
            'done' => !empty($settings['backup_auto']),
        ],
        [
            'id' => 'live',
            'icon' => 'fa-rocket',
            'title_key' => 'launch_step_live',
            'tip_key' => 'launch_tip_live',
            'url' => $published ? (string) ($landing['published_url'] ?? hs_landing_public_url($user)) : hs_panel_path('landing-builder.php'),
            'done' => $published && count($sites) > 0,
            'external' => $published,
        ],
    ];

    return $steps;
}

function hs_launch_checklist_progress(array $steps): array
{
    $required = array_filter($steps, static fn(array $s): bool => empty($s['optional']));
    $total = count($required);
    $done = 0;
    foreach ($required as $step) {
        if (!empty($step['done'])) {
            $done++;
        }
    }
    $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;

    return ['done' => $done, 'total' => $total, 'pct' => $pct];
}

function hs_render_launch_checklist(array $user, array $settings, array $sites, array $t): string
{
    require_once __DIR__ . '/master-password.php';
    require_once __DIR__ . '/landing-builder.php';

    $meta = is_array($settings['launch_checklist'] ?? null)
        ? array_merge(hs_launch_checklist_defaults(), $settings['launch_checklist'])
        : hs_launch_checklist_defaults();
    if (!empty($meta['dismissed'])) {
        return '';
    }

    $steps = hs_launch_checklist_steps($user, $settings, $sites, $t);
    $prog = hs_launch_checklist_progress($steps);
    $title = $t['launch_title'] ?? 'Launch checklist';
    $subtitle = $t['launch_subtitle'] ?? 'Complete these steps to go live with your business.';

    $items = '';
    foreach ($steps as $i => $step) {
        $done = !empty($step['done']);
        $optional = !empty($step['optional']);
        $titleText = $t[$step['title_key']] ?? $step['title_key'];
        $tipText = $t[$step['tip_key']] ?? '';
        $url = hs_url($step['url']);
        $ext = !empty($step['external']) ? ' target="_blank" rel="noopener"' : '';
        $optBadge = $optional
            ? '<span class="hp-launch-opt">' . hs_h($t['launch_optional'] ?? 'Optional') . '</span>'
            : '';

        $items .= '<li class="hp-launch-item' . ($done ? ' is-done' : '') . '" data-launch-item>'
            . '<div class="hp-launch-check" aria-hidden="true"><i class="fa-solid ' . ($done ? 'fa-circle-check' : 'fa-circle') . '"></i></div>'
            . '<div class="hp-launch-body">'
            . '<div class="hp-launch-row">'
            . '<span class="hp-launch-num">' . ($i + 1) . '</span>'
            . '<i class="fa-solid ' . hs_h($step['icon']) . ' hp-launch-icon"></i>'
            . '<strong>' . hs_h($titleText) . '</strong>' . $optBadge
            . '</div>';
        if ($tipText !== '') {
            $items .= '<p class="hp-launch-tip"><i class="fa-solid fa-lightbulb"></i> ' . hs_h($tipText) . '</p>';
        }
        $btnLabel = $done ? ($t['launch_btn_review'] ?? 'Review') : ($t['launch_btn_start'] ?? 'Start');
        $items .= '<a href="' . hs_h($url) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm hp-launch-btn"' . $ext . '>'
            . hs_h($btnLabel) . ' <i class="fa-solid fa-arrow-right"></i></a>'
            . '</div></li>';
    }

    $ringStyle = '--hp-launch-pct:' . $prog['pct'] . ';';

    return '<section class="hp-launch-card" data-hp-launch style="' . hs_h($ringStyle) . '">'
        . '<div class="hp-launch-head">'
        . '<div class="hp-launch-progress" aria-label="' . hs_h($prog['pct'] . '%') . '">'
        . '<svg viewBox="0 0 36 36" class="hp-launch-ring"><path class="hp-launch-ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>'
        . '<path class="hp-launch-ring-fill" stroke-dasharray="' . (int) $prog['pct'] . ', 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/></svg>'
        . '<span class="hp-launch-pct">' . (int) $prog['pct'] . '%</span></div>'
        . '<div><h2 class="hp-launch-title"><i class="fa-solid fa-list-check"></i> ' . hs_h($title) . '</h2>'
        . '<p class="hp-launch-sub">' . hs_h($subtitle) . '</p>'
        . '<p class="hp-launch-count">' . hs_h(str_replace(['{done}', '{total}'], [(string) $prog['done'], (string) $prog['total']], $t['launch_progress'] ?? '{done} / {total} completed')) . '</p>'
        . '</div>'
        . '<form method="post" class="hp-launch-dismiss">' . hs_csrf_field()
        . '<button type="submit" name="launch_dismiss" value="1" class="hs-btn hs-btn-ghost hp-dash-btn-sm" title="' . hs_h($t['launch_dismiss'] ?? 'Hide') . '">'
        . '<i class="fa-solid fa-xmark"></i></button></form>'
        . '</div>'
        . '<ol class="hp-launch-list">' . $items . '</ol>'
        . '<div class="hp-launch-foot">'
        . '<a href="' . hs_h(hs_url(hs_panel_path('landing-builder.php'))) . '" class="hs-btn hs-btn-primary">'
        . '<i class="fa-solid fa-paintbrush"></i> ' . hs_h($t['launch_cta_builder'] ?? 'Open landing builder') . '</a>'
        . '</div></section>';
}