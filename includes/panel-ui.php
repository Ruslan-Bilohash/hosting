<?php
declare(strict_types=1);

function hs_panel_tip(string $key, array $t): string
{
    return (string) ($t['tip_' . $key] ?? $t['tip_default'] ?? '');
}

function hs_render_tip(string $key, array $t): string
{
    $text = hs_panel_tip($key, $t);
    if ($text === '') {
        return '';
    }
    $title = hs_h($t['tip_title'] ?? 'Tip');
    return '<aside class="hp-tip"><div class="hp-tip-icon"><i class="fa-solid fa-lightbulb"></i></div><div><strong>' . $title . '</strong><p>' . hs_h($text) . '</p></div></aside>';
}

function hs_render_progress(string $label, float $used, float $max, string $unit = ''): string
{
    $pct = $max > 0 ? min(100, round(($used / $max) * 100, 1)) : 0;
    $warn = $pct >= 85 ? ' hp-bar-warn' : '';
    $u = $unit !== '' ? ' ' . hs_h($unit) : '';
    return '<div class="hp-bar-wrap"><div class="hp-bar-head"><span>' . hs_h($label) . '</span><span>' . hs_h((string) $used) . $u . ' / ' . hs_h((string) $max) . $u . ' (' . hs_h((string) $pct) . '%)</span></div><div class="hp-bar"><div class="hp-bar-fill' . $warn . '" style="width:' . (float) $pct . '%"></div></div></div>';
}

function hs_render_card(string $title, string $body, ?string $footer = null): string
{
    $html = '<section class="hp-card"><h2 class="hp-card-title">' . hs_h($title) . '</h2><div class="hp-card-body">' . $body . '</div>';
    if ($footer !== null) {
        $html .= '<div class="hp-card-foot">' . $footer . '</div>';
    }
    return $html . '</section>';
}

function hs_render_kv_table(array $rows): string
{
    $html = '<table class="hs-table"><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr><th style="width:40%">' . hs_h($row[0]) . '</th><td>' . $row[1] . '</td></tr>';
    }
    return $html . '</tbody></table>';
}

/** @return list<array{0:string,1:string}> */
function hs_panel_site_details_rows(string $domain, ?array $user, array $t): array
{
    $srv = hs_server_constants($user);
    return [
        [$t['plan_site_url'] ?? '', '<a href="https://' . hs_h($domain) . '" target="_blank" rel="noopener">https://' . hs_h($domain) . '</a>'],
        [$t['plan_site_www'] ?? '', '<a href="https://www.' . hs_h($domain) . '" target="_blank" rel="noopener">https://www.' . hs_h($domain) . '</a>'],
        [$t['plan_site_ip'] ?? '', '<code>' . hs_h($srv['ip']) . '</code>'],
    ];
}

function hs_panel_site_details_card(string $domain, ?array $user, array $t, string $extraClass = ''): string
{
    $cls = $extraClass !== '' ? ' ' . trim($extraClass) : '';
    return '<section class="hp-card hp-dash-site-details' . $cls . '">'
        . '<div class="hp-card-title">' . hs_h($t['plan_site_details'] ?? 'Site details') . '</div>'
        . '<div class="hp-card-body">' . hs_render_kv_table(hs_panel_site_details_rows($domain, $user, $t)) . '</div>'
        . '</section>';
}

function hs_render_guide(array $t): string
{
    $items = [];
    for ($i = 1; $i <= 5; $i++) {
        $key = 'guide_' . $i;
        if (!empty($t[$key])) {
            $items[] = '<li>' . hs_h($t[$key]) . '</li>';
        }
    }
    if ($items === []) {
        return '';
    }
    $title = hs_h($t['guide_title'] ?? 'Quick start');
    return hs_render_card($title, '<ol class="hp-guide">' . implode('', $items) . '</ol>');
}

function hs_render_essential_tile(string $icon, string $title, string $desc, string $actionsHtml): string
{
    return '<article class="hp-dash-tile">'
        . '<div class="hp-dash-tile-head">'
        . '<div class="hp-dash-tile-icon"><i class="fa-solid ' . hs_h($icon) . '"></i></div>'
        . '<div class="hp-dash-tile-body"><h3>' . hs_h($title) . '</h3><p>' . hs_h($desc) . '</p></div>'
        . '</div>'
        . '<div class="hp-dash-tile-actions">' . $actionsHtml . '</div>'
        . '</article>';
}

function hs_render_perf_score(string $label, int $score, string $scanLabel, string $scanDate): string
{
    $color = $score >= 90 ? '#059669' : ($score >= 70 ? '#d97706' : '#dc2626');
    return '<div class="hp-dash-perf-item">'
        . '<div class="hp-dash-perf-ring" style="--hp-score:' . (int) $score . ';--hp-score-color:' . $color . '"><span>' . (int) $score . '</span></div>'
        . '<div><strong>' . hs_h($label) . '</strong>'
        . '<span class="hp-dash-perf-meta">' . hs_h($scanLabel) . '<br>' . hs_h($scanDate) . '</span></div>'
        . '</div>';
}

function hs_render_resource_stat(string $label, string $value, ?string $sub = null): string
{
    $subHtml = $sub !== null ? '<span class="hp-dash-res-sub">' . hs_h($sub) . '</span>' : '';
    return '<div class="hp-dash-res-item"><span class="hp-dash-res-label">' . hs_h($label) . '</span>'
        . '<span class="hp-dash-res-value">' . $value . '</span>' . $subHtml . '</div>';
}