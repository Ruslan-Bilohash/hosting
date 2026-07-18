<?php
declare(strict_types=1);

// hs_resource_usage() lives in user-settings.php and needs plan helpers.
if (!function_exists('hs_resource_usage') && is_file(__DIR__ . '/user-settings.php')) {
    require_once __DIR__ . '/user-settings.php';
}
if (!function_exists('hs_plan') && is_file(__DIR__ . '/plans.php')) {
    require_once __DIR__ . '/plans.php';
}

/** @return array{disk_mb:float,inodes:int,cpu:float,memory_mb:int,bandwidth_gb:float,sites:int} */
function hs_usage_snapshot(array $user, array $sites): array
{
    $r = hs_resource_usage($user, $sites);
    return [
        'disk_mb' => (float) ($r['storage_used_mb'] ?? 0),
        'inodes' => (int) ($r['inodes_used'] ?? 0),
        'cpu' => (float) ($r['cpu_percent'] ?? 0),
        'memory_mb' => (int) ($r['memory_mb'] ?? 0),
        'bandwidth_gb' => (float) ($r['bandwidth_gb'] ?? 0),
        'sites' => (int) ($r['sites_used'] ?? 0),
    ];
}

/** @param list<array<string,mixed>> $history */
function hs_usage_trim_history(array $history): array
{
    if ($history === []) {
        return [];
    }
    usort($history, static fn ($a, $b) => strcmp((string) ($a['ts'] ?? ''), (string) ($b['ts'] ?? '')));
    $cutoff = strtotime('-90 days');
    $weekCutoff = strtotime('-7 days');
    $byDay = [];
    $recent = [];
    foreach ($history as $row) {
        $ts = strtotime((string) ($row['ts'] ?? ''));
        if ($ts === false || $ts < $cutoff) {
            continue;
        }
        if ($ts >= $weekCutoff) {
            $recent[] = $row;
            continue;
        }
        $day = gmdate('Y-m-d', $ts);
        $byDay[$day] = $row;
    }
    return array_values(array_merge(array_values($byDay), $recent));
}

function hs_usage_should_record(?array $last, array $snap): bool
{
    if ($last === null) {
        return true;
    }
    $lastTs = strtotime((string) ($last['ts'] ?? ''));
    if ($lastTs === false) {
        return true;
    }
    if (time() - $lastTs < 3600) {
        return false;
    }
    return true;
}

/** Record a real usage snapshot (throttled to once per hour). */
function hs_usage_track(string $userId, array $user, array $sites): void
{
    $settings = hs_user_settings_get($userId);
    $history = is_array($settings['usage_history'] ?? null) ? $settings['usage_history'] : [];
    $last = $history !== [] ? $history[array_key_last($history)] : null;
    $snap = hs_usage_snapshot($user, $sites);
    if (!hs_usage_should_record(is_array($last) ? $last : null, $snap)) {
        return;
    }
    $snap['ts'] = gmdate('c');
    $history[] = $snap;
    hs_user_settings_save($userId, ['usage_history' => hs_usage_trim_history($history)]);
}

/** @return list<array<string,mixed>> */
function hs_usage_history_hours(string $userId, int $hours = 24): array
{
    $settings = hs_user_settings_get($userId);
    $history = is_array($settings['usage_history'] ?? null) ? $settings['usage_history'] : [];
    if ($history === []) {
        return [];
    }
    $cutoff = time() - max(1, $hours) * 3600;
    $out = [];
    foreach ($history as $row) {
        $ts = strtotime((string) ($row['ts'] ?? ''));
        if ($ts !== false && $ts >= $cutoff) {
            $out[] = $row;
        }
    }
    usort($out, static fn ($a, $b) => strcmp((string) ($a['ts'] ?? ''), (string) ($b['ts'] ?? '')));
    return $out;
}

/** @param list<array<string,mixed>> $history */
function hs_usage_chart_series_hours(array $history, int $hours = 24): array
{
    if ($history === []) {
        return ['labels' => [], 'disk' => [], 'memory' => [], 'cpu' => []];
    }
    if (count($history) < 2) {
        $history[] = array_merge($history[0], ['ts' => gmdate('c')]);
    }
    $labels = [];
    $disk = [];
    $memory = [];
    $cpu = [];
    foreach ($history as $row) {
        $ts = strtotime((string) ($row['ts'] ?? ''));
        $labels[] = $ts !== false ? gmdate('H:i', $ts) : '';
        $disk[] = round((float) ($row['disk_mb'] ?? 0), 1);
        $memory[] = (int) ($row['memory_mb'] ?? 0);
        $cpu[] = round((float) ($row['cpu'] ?? 0), 1);
    }
    return compact('labels', 'disk', 'memory', 'cpu');
}

/** @return list<array<string,mixed>> */
function hs_usage_history(string $userId, int $days = 30): array
{
    $settings = hs_user_settings_get($userId);
    $history = is_array($settings['usage_history'] ?? null) ? $settings['usage_history'] : [];
    if ($history === []) {
        return [];
    }
    $cutoff = strtotime('-' . max(1, $days) . ' days');
    $out = [];
    foreach ($history as $row) {
        $ts = strtotime((string) ($row['ts'] ?? ''));
        if ($ts !== false && $ts >= $cutoff) {
            $out[] = $row;
        }
    }
    usort($out, static fn ($a, $b) => strcmp((string) ($a['ts'] ?? ''), (string) ($b['ts'] ?? '')));
    return $out;
}

/** @param list<array<string,mixed>> $history */
function hs_usage_aggregate_daily(array $history): array
{
    $buckets = [];
    foreach ($history as $row) {
        $ts = strtotime((string) ($row['ts'] ?? ''));
        if ($ts === false) {
            continue;
        }
        $day = gmdate('Y-m-d', $ts);
        $buckets[$day] = $row;
    }
    ksort($buckets);
    return array_values($buckets);
}

/** @return array{labels:list<string>,disk:list<float>,memory:list<int>,cpu:list<float>,bandwidth:list<float>,inodes:list<int>} */
function hs_usage_chart_series(array $history, int $days = 30): array
{
    $daily = hs_usage_aggregate_daily($history);
    if (count($daily) < 2 && $daily !== []) {
        $daily[] = array_merge($daily[0], ['ts' => gmdate('c')]);
    }
    $labels = [];
    $disk = [];
    $memory = [];
    $cpu = [];
    $bandwidth = [];
    $inodes = [];
    foreach ($daily as $row) {
        $ts = strtotime((string) ($row['ts'] ?? ''));
        $labels[] = $ts !== false ? gmdate('d.m', $ts) : '';
        $disk[] = round((float) ($row['disk_mb'] ?? 0), 1);
        $memory[] = (int) ($row['memory_mb'] ?? 0);
        $cpu[] = round((float) ($row['cpu'] ?? 0), 1);
        $bandwidth[] = round((float) ($row['bandwidth_gb'] ?? 0), 1);
        $inodes[] = (int) ($row['inodes'] ?? 0);
    }
    return compact('labels', 'disk', 'memory', 'cpu', 'bandwidth', 'inodes');
}

function hs_usage_format_label(string $ts): string
{
    $t = strtotime($ts);
    return $t !== false ? date('d.m.Y H:i', $t) : $ts;
}

/** @return array<string, mixed> */
function hs_usage_donut_current(array $r): array
{
    $diskPct = min(100, ($r['storage_max_mb'] ?? 1) > 0
        ? round(((float) ($r['storage_used_mb'] ?? 0) / (float) $r['storage_max_mb']) * 100, 1)
        : 0);
    return [
        'disk_used_mb' => (float) ($r['storage_used_mb'] ?? 0),
        'disk_max_mb' => (float) ($r['storage_max_mb'] ?? 1),
        'disk_pct' => $diskPct,
        'inodes_used' => (int) ($r['inodes_used'] ?? 0),
        'inodes_max' => (int) ($r['inodes_max'] ?? 1),
        'cpu' => (float) ($r['cpu_percent'] ?? 0),
        'memory_mb' => (int) ($r['memory_mb'] ?? 0),
        'bandwidth_gb' => (float) ($r['bandwidth_gb'] ?? 0),
        'sites_used' => (int) ($r['sites_used'] ?? 0),
        'sites_max' => (int) ($r['sites_max_display'] ?? $r['sites_max'] ?? 1),
    ];
}

function hs_usage_render_stat_card(string $icon, string $label, string $value, string $sub, string $accent = ''): string
{
    $cls = $accent !== '' ? ' hs-usage-stat--' . $accent : '';
    return '<article class="hs-usage-stat' . $cls . '">'
        . '<div class="hs-usage-stat-icon"><i class="fa-solid ' . hs_h($icon) . '"></i></div>'
        . '<div class="hs-usage-stat-body"><span class="hs-usage-stat-label">' . hs_h($label) . '</span>'
        . '<strong class="hs-usage-stat-value">' . $value . '</strong>'
        . '<span class="hs-usage-stat-sub">' . hs_h($sub) . '</span></div></article>';
}

function hs_usage_render_chart_box(string $id, string $title, ?string $hint = null, string $height = '280px'): string
{
    $hintHtml = $hint !== null && $hint !== '' ? '<p class="hp-muted hs-chart-hint">' . hs_h($hint) . '</p>' : '';
    return '<section class="hp-card hs-chart-card">'
        . '<h2 class="hp-card-title">' . hs_h($title) . '</h2>'
        . '<div class="hp-card-body">' . $hintHtml
        . '<div class="hs-chart-wrap" style="height:' . hs_h($height) . '"><canvas id="' . hs_h($id) . '"></canvas></div>'
        . '</div></section>';
}

/** Track all platform clients (admin dashboard). */
function hs_usage_track_all_clients(): void
{
    foreach (hs_users() as $user) {
        $uid = (string) ($user['id'] ?? '');
        if ($uid === '') {
            continue;
        }
        $sites = hs_sites_for_user($uid);
        hs_usage_track($uid, $user, $sites);
    }
}

/** @return list<array{user:array,sites:array,resources:array,history:list<array<string,mixed>>,series:array<string,mixed>}> */
function hs_usage_all_clients_data(int $days = 30): array
{
    $out = [];
    foreach (hs_users() as $user) {
        $uid = (string) ($user['id'] ?? '');
        if ($uid === '') {
            continue;
        }
        $sites = hs_sites_for_user($uid);
        $resources = hs_resource_usage($user, $sites);
        $history = hs_usage_history($uid, $days);
        $out[] = [
            'user' => $user,
            'sites' => $sites,
            'resources' => $resources,
            'history' => $history,
            'series' => hs_usage_chart_series($history, $days),
        ];
    }
    usort($out, static fn ($a, $b) => ((float) ($b['resources']['storage_used_mb'] ?? 0)) <=> ((float) ($a['resources']['storage_used_mb'] ?? 0)));
    return $out;
}