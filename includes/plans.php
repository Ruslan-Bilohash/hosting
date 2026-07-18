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
    return hs_plan_catalog_plan($id);
}

function hs_plan_id_valid(string $id): bool
{
    $catalog = hs_plan_catalog_load();
    $plans = $catalog['plans'];
    if (isset($plans[$id])) {
        return true;
    }

    return $id === 'pro' && isset($plans['business']);
}

function hs_plan_normalize_id(string $id): string
{
    return $id === 'pro' ? 'business' : $id;
}

/** Shared/VPS hosting (not domain-only). */
function hs_plan_is_hosting(string $planId): bool
{
    $id = hs_plan_normalize_id($planId);
    if ($id === 'domain') {
        return false;
    }
    $plan = hs_plan($id);

    $type = (string) ($plan['type'] ?? '');

    return $type !== 'domain_only' && $type !== 'managed_service';
}

function hs_plan_panel_label(string $planId, array $t): string
{
    $id = hs_plan_normalize_id($planId);

    return (string) ($t['plan_hosting_' . $id] ?? $t['plan_' . $id] ?? $id);
}

/**
 * Effective hosting limits from selected plan + active add-ons (extra sites / storage).
 *
 * @return array{
 *   plan_id:string,disk_gb:int,storage_mb:int,sites:int,sites_unlimited:bool,
 *   sites_display:string,inodes:int,databases:int,ram_mb:int,cpu_cores:int,
 *   traffic:string,addon_sites:int,addon_storage_gb:int
 * }
 */
function hs_user_effective_plan_limits(array $user): array
{
    $planId = hs_plan_normalize_id((string) ($user['plan'] ?? 'starter'));
    if ($planId === '' || $planId === 'domain') {
        $planId = 'starter';
    }
    $plan = hs_plan($planId);
    $addonSites = 0;
    $addonStorageGb = 0;
    $svcIds = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];
    foreach ($svcIds as $sid) {
        $id = strtolower((string) $sid);
        if ($id === 'extra_sites_5' || preg_match('/extra_sites_(\d+)/', $id, $m)) {
            $addonSites += isset($m[1]) ? (int) $m[1] : 5;
        }
        if ($id === 'extra_storage_10gb' || preg_match('/extra_storage_(\d+)/', $id, $m2)) {
            $addonStorageGb += isset($m2[1]) ? (int) $m2[1] : 10;
        }
    }
    $sitesUnlimited = !empty($plan['sites_unlimited']) || (int) ($plan['sites'] ?? 0) >= 999;
    $baseSites = max(1, (int) ($plan['sites'] ?? 1));
    $sites = $sitesUnlimited ? 999 : ($baseSites + $addonSites);
    $diskGb = max(1, (int) ($plan['disk_gb'] ?? 5) + $addonStorageGb);
    $storageMb = max(512, (int) ($plan['storage_mb'] ?? ($diskGb * 1024)));
    if ($addonStorageGb > 0) {
        $storageMb = max($storageMb, $diskGb * 1024);
    }
    $sitesDisplay = $sitesUnlimited ? '∞' : (string) $sites;

    return [
        'plan_id' => $planId,
        'disk_gb' => $diskGb,
        'storage_mb' => $storageMb,
        'sites' => $sites,
        'sites_unlimited' => $sitesUnlimited,
        'sites_display' => $sitesDisplay,
        'inodes' => max(1000, (int) ($plan['inodes'] ?? 50000)),
        'databases' => max(1, (int) ($plan['databases'] ?? 2)),
        'ram_mb' => max(256, (int) ($plan['ram_mb'] ?? 1024)),
        'cpu_cores' => max(1, (int) ($plan['cpu_cores'] ?? 1)),
        'traffic' => trim((string) ($plan['traffic'] ?? 'unlimited')) !== ''
            ? trim((string) ($plan['traffic'] ?? 'unlimited'))
            : 'unlimited',
        'addon_sites' => $addonSites,
        'addon_storage_gb' => $addonStorageGb,
        'auto_backup' => !empty($plan['auto_backup']),
        'backup_freq' => (string) ($plan['backup_freq'] ?? ''),
        'imunify360' => !empty($plan['imunify360']),
        'webapp_details' => array_key_exists('webapp_details', $plan)
            ? !empty($plan['webapp_details'])
            : true,
    ];
}

function hs_user_site_limit(array $user): int
{
    $limits = hs_user_effective_plan_limits($user);
    if (!empty($limits['sites_unlimited'])) {
        return 999;
    }

    return max(1, (int) ($limits['sites'] ?? 1));
}

function hs_user_can_add_site(array $user): bool
{
    $limits = hs_user_effective_plan_limits($user);
    if (!empty($limits['sites_unlimited'])) {
        return true;
    }

    return count(hs_sites_for_user((string) $user['id'])) < hs_user_site_limit($user);
}

function hs_user_database_limit(array $user): int
{
    return max(1, (int) (hs_user_effective_plan_limits($user)['databases'] ?? 2));
}

function hs_format_plan_price(string $planId, string $lang): string
{
    return hs_format_plan_price_row(hs_plan($planId), $lang);
}

function hs_format_plan_price_row(array $plan, string $lang): string
{
    $eur = (float) ($plan['price_eur'] ?? 0);
    $nok = (float) ($plan['price_nok'] ?? 49);
    if ($eur <= 0 && $nok <= 0) {
        $labels = ['uk' => 'Безкоштовно', 'no' => 'Gratis', 'en' => 'Free'];
        return $labels[$lang] ?? $labels['en'];
    }
    // Managed services (care/SEO): always show fixed EUR — not FX-converted UAH/NOK.
    if ($eur > 0 && (($plan['type'] ?? '') === 'managed_service' || !empty($plan['price_eur_fixed']))) {
        return '€' . number_format($eur, 2, '.', ' ');
    }
    if ($eur > 0) {
        return hs_format_eur_price($eur, $lang, '');
    }

    return hs_format_nok_price($nok, $lang);
}

function hs_format_plan_was_price(string $planId, string $lang): string
{
    return hs_format_plan_was_price_row(hs_plan($planId), $lang);
}

function hs_format_plan_was_price_row(array $plan, string $lang): string
{
    $nok = (float) ($plan['price_was_nok'] ?? 0);
    if ($nok <= 0) {
        return '';
    }

    return hs_format_nok_price($nok, $lang);
}

function hs_plan_period_suffix(array $plan, array $t): string
{
    if (hs_plan_billing_period($plan) === 'year') {
        return (string) ($t['per_year'] ?? '/year');
    }

    return (string) ($t['per_month'] ?? '/mo');
}

function hs_plan_storage_label(array $plan, array $t): string
{
    // Solaskinner has no unlimited SSD — always show real quota (disk_gb / storage_mb).
    $gb = (int) ($plan['disk_gb'] ?? 0);
    if ($gb <= 0) {
        $mb = (int) ($plan['storage_mb'] ?? 0);
        if ($mb >= 1024) {
            $gb = (int) max(1, round($mb / 1024));
        } elseif ($mb > 0) {
            return str_replace('{n}', (string) $mb, $t['plan_storage'] ?? '{n} MB SSD');
        } else {
            $gb = 5;
        }
    }

    return str_replace('{n}', (string) $gb, $t['plan_storage_gb'] ?? '{n} GB SSD');
}

function hs_plan_sites_label(array $plan, array $t): string
{
    if (!empty($plan['sites_unlimited'])) {
        return (string) ($t['plan_sites_unlimited'] ?? 'Unlimited websites');
    }
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
    if (($plan['type'] ?? '') === 'managed_service') {
        $lines = [];
        $pid = (string) ($plan['id'] ?? '');
        foreach (range(1, 8) as $i) {
            $key = 'plan_' . $pid . '_feat_' . $i;
            $line = trim((string) ($t[$key] ?? ''));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
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
    if (!empty($plan['platform_subdomain'])) {
        $zone = function_exists('hs_platform_free_zone') ? hs_platform_free_zone() : 'site.bilohash.com';
        $lines[] = str_replace('{zone}', $zone, (string) ($t['plan_free_subdomain'] ?? '{zone} subdomain'));
    }
    if (!empty($plan['ecosystem_apps'])) {
        $lines[] = (string) ($t['plan_ecosystem_free'] ?? $t['plan_ecosystem'] ?? '');
    }
    $lines[] = hs_plan_sites_label($plan, $t);
    $lines[] = hs_plan_storage_label($plan, $t);
    $databases = (int) ($plan['databases'] ?? 0);
    if ($databases > 0) {
        $dbTpl = (string) ($t['plan_databases'] ?? '{n} databases');
        if (!str_contains($dbTpl, '{n}')) {
            $dbTpl = '{n} ' . trim($dbTpl);
        }
        $lines[] = str_replace('{n}', (string) $databases, $dbTpl);
    }
    $inodes = (int) ($plan['inodes'] ?? 0);
    if ($inodes > 0) {
        $inodeTpl = (string) ($t['plan_inodes'] ?? '{n} inodes');
        // Fix broken translations that omit {n} (e.g. bare "Inoder")
        if (!str_contains($inodeTpl, '{n}')) {
            $inodeTpl = '{n} ' . trim($inodeTpl);
        }
        $lines[] = str_replace(
            '{n}',
            number_format($inodes, 0, '', ' '),
            $inodeTpl
        );
    }
    $lines[] = (string) ($t['plan_bandwidth_unmetered'] ?? $t['plan_traffic_unlimited'] ?? 'Unmetered bandwidth');
    $freq = (string) ($plan['backup_freq'] ?? '');
    if ($freq === 'twice_week_auto' || (!empty($plan['auto_backup']) && $freq !== 'twice_week')) {
        $lines[] = (string) ($t['plan_backup_twice_auto'] ?? 'Backups twice a week + autobackup');
    } elseif ($freq === 'twice_week' || !empty($plan['auto_backup'])) {
        $lines[] = (string) ($t['plan_backup_twice'] ?? 'Backups twice a week');
    }
    if (!empty($plan['imunify360'])) {
        $lines[] = (string) ($t['plan_imunify360'] ?? 'Imunify360 security');
    }
    $lines[] = (string) ($t['plan_ssl_free'] ?? 'Free SSL');
    $lines[] = (string) ($t['plan_panel_full'] ?? 'Full control panel');
    $lines[] = (string) ($t['plan_apps_1click'] ?? '100+ apps with 1-click install');
    return array_values(array_filter($lines, static fn(string $x): bool => $x !== ''));
}

function hs_render_plan_price_block(array $plan, array $t, string $lang): string
{
    $was = hs_format_plan_was_price_row($plan, $lang);
    $now = hs_format_plan_price_row($plan, $lang);
    $per = hs_h(hs_plan_period_suffix($plan, $t));
    $html = '<div class="hs-plan-prices">';
    if ($was !== '') {
        $html .= '<span class="hs-plan-price-was">' . hs_h($was) . $per . '</span>';
    }
    $html .= '<span class="hs-plan-price-now">' . hs_h($now) . '</span><span class="hs-plan-price-per">' . $per . '</span></div>';
    return $html;
}

/**
 * Public plan card title: i18n key → catalog labels → humanized id.
 *
 * @param array<string, mixed> $plan
 */
function hs_plan_public_title(string $planId, array $plan, array $t, string $lang): string
{
    $fromT = trim((string) ($t['plan_' . $planId] ?? ''));
    if ($fromT !== '' && $fromT !== $planId) {
        return $fromT;
    }
    $labels = is_array($plan['labels'] ?? null) ? $plan['labels'] : [];
    foreach ([$lang, 'en', 'uk', 'no'] as $lc) {
        $lab = trim((string) ($labels[$lc] ?? ''));
        if ($lab !== '' && $lab !== $planId) {
            return $lab;
        }
    }
    if ($planId !== '') {
        return ucwords(str_replace(['_', '-'], ' ', $planId));
    }

    return $planId;
}

/**
 * Public plan card description: i18n → catalog desc.
 *
 * @param array<string, mixed> $plan
 */
function hs_plan_public_desc(string $planId, array $plan, array $t, string $lang): string
{
    $fromT = trim((string) ($t[hs_plan_desc_key($planId)] ?? ''));
    if ($fromT !== '') {
        return $fromT;
    }
    $desc = is_array($plan['desc'] ?? null) ? $plan['desc'] : [];
    foreach ([$lang, 'en', 'uk', 'no'] as $lc) {
        $d = trim((string) ($desc[$lc] ?? ''));
        if ($d !== '') {
            return $d;
        }
    }

    return '';
}

function hs_render_public_plan_cards(array $t, string $lang, ?string $category = null): string
{
    $gridClass = 'hs-plans hs-plans-public';
    if ($category === 'managed_service') {
        $gridClass .= ' hs-plans-services';
    }
    $html = '<div class="' . $gridClass . '">';
    foreach (hs_plan_catalog_public_plans($category) as $pid => $plan) {
        $title = hs_plan_public_title((string) $pid, $plan, $t, $lang);
        $desc = hs_plan_public_desc((string) $pid, $plan, $t, $lang);
        $badges = '';
        if (($plan['badge'] ?? '') === 'free') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-free">' . hs_h($t['plan_free_badge'] ?? 'Free') . '</span>';
        }
        if (($plan['badge'] ?? '') === 'popular') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-popular">' . hs_h($t['plan_popular'] ?? 'Most popular') . '</span>';
        }
        if (($plan['badge'] ?? '') === 'vps') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-vps">' . hs_h($t['plan_vps_badge'] ?? 'VPS') . '</span>';
        }
        if (($plan['badge'] ?? '') === 'service' || ($plan['type'] ?? '') === 'managed_service') {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-service">' . hs_h($t['plan_service_badge'] ?? 'Service') . '</span>';
        }
        if (($plan['badge'] ?? '') === 'test' || !empty($plan['test_plan'])) {
            $badges .= '<span class="hs-plan-badge hs-plan-badge-test">' . hs_h($t['plan_test_badge'] ?? 'Test') . '</span>';
        }
        $discountPct = (int) ($plan['discount_pct'] ?? 0);
        if ($discountPct > 0) {
            $discountLabel = str_replace('{n}', (string) $discountPct, (string) ($t['plan_discount_badge'] ?? '−{n}%'));
            $badges .= '<span class="hs-plan-badge hs-plan-badge-sale">' . hs_h($discountLabel) . '</span>';
        }
        $features = '';
        foreach (hs_plan_feature_lines($plan, $t) as $line) {
            $features .= '<li><i class="fa-solid fa-check"></i> ' . hs_h($line) . '</li>';
        }
        $html .= '<article class="hs-plan-card' . (($plan['badge'] ?? '') === 'popular' ? ' is-popular' : '') . '">'
            . ($badges !== '' ? '<div class="hs-plan-badges">' . $badges . '</div>' : '')
            . '<h3>' . hs_h($title) . '</h3>'
            . ($desc !== '' ? '<p class="hs-plan-desc">' . hs_h($desc) . '</p>' : '')
            . hs_render_plan_price_block($plan, $t, $lang)
            . '<ul class="hs-plan-features">' . $features . '</ul>'
            . '<a href="' . hs_h(hs_url('register.php', ($category === 'managed_service' || ($plan['type'] ?? '') === 'managed_service')
                ? ['addon' => $pid]
                : ['plan' => $pid])) . '" class="hs-btn hs-btn-primary hs-plan-cta">'
            . hs_h($t[(($plan['badge'] ?? '') === 'free') ? 'plan_cta_free' : 'plan_cta'] ?? 'Get started') . '</a>'
            . '</article>';
    }
    return $html . '</div>';
}