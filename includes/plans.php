<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem-pricing-local.php';
require_once __DIR__ . '/plan-catalog.php';

function hs_plans(): array
{
    return hs_plan_catalog_plans();
}

function hs_plan(string $id): array
{
    $plans = hs_plans();
    if (isset($plans[$id])) {
        return $plans[$id];
    }
    if ($id === 'pro') {
        return $plans['business'];
    }
    return $plans['starter'];
}

function hs_plan_id_valid(string $id): bool
{
    return isset(hs_plans()[$id]);
}

function hs_user_site_limit(array $user): int
{
    return (int) (hs_plan((string) ($user['plan'] ?? 'starter'))['sites'] ?? 1);
}

function hs_user_can_add_site(array $user): bool
{
    return count(hs_sites_for_user((string) $user['id'])) < hs_user_site_limit($user);
}

function hs_user_database_limit(array $user): int
{
    $plan = hs_plan((string) ($user['plan'] ?? 'starter'));
    return max(1, (int) ($plan['databases'] ?? 2));
}

function hs_format_plan_price(string $planId, string $lang): string
{
    $plan = hs_plan($planId);
    $nok = (float) ($plan['price_nok'] ?? 49);
    return hs_format_nok_price($nok, $lang);
}

function hs_format_plan_was_price(string $planId, string $lang): string
{
    $plan = hs_plan($planId);
    $nok = (float) ($plan['price_was_nok'] ?? 0);
    if ($nok <= 0) {
        return '';
    }
    return hs_format_nok_price($nok, $lang);
}

function hs_plan_storage_label(array $plan, array $t): string
{
    $mb = (int) ($plan['storage_mb'] ?? 0);
    if ($mb >= 1024 && $mb % 1024 === 0) {
        return str_replace('{n}', (string) (int) ($mb / 1024), $t['plan_storage_gb'] ?? '{n} GB');
    }
    return str_replace('{n}', (string) $mb, $t['plan_storage'] ?? '{n} MB');
}

function hs_plan_sites_label(array $plan, array $t): string
{
    $n = (int) ($plan['sites'] ?? 1);
    if ($n === 1) {
        return (string) ($t['plan_one_site'] ?? '1 site');
    }
    return str_replace('{n}', (string) $n, $t['plan_sites'] ?? '{n} sites');
}

function hs_plan_desc_key(string $planId): string
{
    return 'plan_' . $planId . '_desc';
}

/** @return list<string> */
function hs_plan_feature_lines(array $plan, array $t): array
{
    if (($plan['type'] ?? '') === 'vps') {
        $lines = [];
        if (!empty($plan['ecosystem_apps'])) {
            $lines[] = (string) ($t['plan_ecosystem_free'] ?? $t['plan_ecosystem'] ?? '');
        }
        $lines[] = str_replace('{n}', (string) (int) ($plan['ram_gb'] ?? 2), $t['plan_ram_gb'] ?? '{n} GB RAM');
        $lines[] = str_replace('{n}', (string) (int) ($plan['cpu_cores'] ?? 2), $t['plan_vps_cpu_cores'] ?? '{n} CPU cores');
        $lines[] = str_replace('{n}', (string) (int) (($plan['storage_mb'] ?? 0) / 1024), $t['plan_storage_gb'] ?? '{n} GB SSD');
        $lines[] = str_replace('{n}', (string) ($plan['bandwidth_tb'] ?? 3), $t['plan_bandwidth_tb'] ?? '{n} TB bandwidth');
        $lines[] = (string) ($t['plan_vps_root'] ?? 'Root access · dedicated resources');
        $lines[] = (string) ($t['plan_ssl_free'] ?? 'Free SSL');
        return array_values(array_filter($lines, static fn(string $x): bool => $x !== ''));
    }
    $lines = [];
    if (!empty($plan['ecosystem_apps'])) {
        $lines[] = (string) ($t['plan_ecosystem_free'] ?? $t['plan_ecosystem'] ?? '');
    }
    $lines[] = hs_plan_sites_label($plan, $t);
    $lines[] = hs_plan_storage_label($plan, $t);
    $lines[] = (string) ($t['plan_ssl_free'] ?? 'Free SSL');
    $lines[] = (string) ($t['plan_panel_full'] ?? 'Full BILOHASH panel');
    return array_values(array_filter($lines, static fn(string $x): bool => $x !== ''));
}

function hs_render_plan_price_block(array $plan, array $t, string $lang): string
{
    $was = hs_format_plan_was_price((string) ($plan['id'] ?? ''), $lang);
    $now = hs_format_plan_price((string) ($plan['id'] ?? ''), $lang);
    $per = hs_h($t['per_month'] ?? '/mo');
    $html = '<div class="hs-plan-prices">';
    if ($was !== '') {
        $html .= '<span class="hs-plan-price-was">' . hs_h($was) . $per . '</span>';
    }
    $html .= '<span class="hs-plan-price-now">' . hs_h($now) . '</span><span class="hs-plan-price-per">' . $per . '</span></div>';
    return $html;
}

function hs_render_public_plan_cards(array $t, string $lang): string
{
    $html = '<div class="hs-plans hs-plans-public">';
    foreach (hs_plans() as $pid => $plan) {
        $descKey = hs_plan_desc_key($pid);
        $desc = (string) ($t[$descKey] ?? '');
        $badges = '';
        if (($plan['badge'] ?? '') === 'popular') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-popular">' . hs_h($t['plan_popular'] ?? 'Most popular') . '</span>';
        }
        if (($plan['badge'] ?? '') === 'vps') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-vps">' . hs_h($t['plan_vps_badge'] ?? 'VPS') . '</span>';
        }
        if ((int) ($plan['discount_pct'] ?? 0) > 0) {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-sale">' . hs_h($t['plan_discount_now'] ?? 'Sale!') . '</span>';
        }
        $features = '';
        foreach (hs_plan_feature_lines($plan, $t) as $line) {
            $features .= '<li><i class="fa-solid fa-check"></i> ' . hs_h($line) . '</li>';
        }
        $html .= '<article class="hs-plan-card' . (($plan['badge'] ?? '') === 'popular' ? ' is-popular' : '') . '">'
            . ($badges !== '' ? '<div class="hs-plan-badges">' . $badges . '</div>' : '')
            . '<h3>' . hs_h($t['plan_' . $pid] ?? $pid) . '</h3>'
            . ($desc !== '' ? '<p class="hs-plan-desc">' . hs_h($desc) . '</p>' : '')
            . hs_render_plan_price_block($plan, $t, $lang)
            . '<ul class="hs-plan-features">' . $features . '</ul>'
            . '<a href="' . hs_h(hs_url('register.php', ['plan' => $pid])) . '" class="hs-btn hs-btn-primary hs-plan-cta">'
            . hs_h($t['plan_cta'] ?? 'Get started') . '</a>'
            . '</article>';
    }
    return $html . '</div>';
}