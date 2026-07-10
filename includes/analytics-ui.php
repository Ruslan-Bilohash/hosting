<?php
declare(strict_types=1);

require_once __DIR__ . '/activity-log.php';
require_once __DIR__ . '/panel-features.php';

function hs_analytics_table(array $rows, array $headers): string
{
    $html = '<table class="hs-table"><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . hs_h($h) . '</th>';
    }
    return $html . '</tr></thead><tbody>' . implode('', $rows) . '</tbody></table>';
}

function hs_analytics_pagination(int $page, int $pages, string $baseUrl, array $t): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="hs-analytics-pager" aria-label="Pagination"><div class="hs-analytics-pager-inner">';
    if ($page > 1) {
        $html .= '<a href="' . hs_h($baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . ($page - 1)) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">'
            . '<i class="fa-solid fa-chevron-left"></i> ' . hs_h($t['analytics_page_prev'] ?? 'Previous') . '</a>';
    }
    $html .= '<span class="hs-analytics-pager-info">' . hs_h(str_replace(
        ['{page}', '{pages}'],
        [(string) $page, (string) $pages],
        $t['analytics_page_of'] ?? 'Page {page} of {pages}'
    )) . '</span>';
    if ($page < $pages) {
        $html .= '<a href="' . hs_h($baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . ($page + 1)) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm">'
            . hs_h($t['analytics_page_next'] ?? 'Next') . ' <i class="fa-solid fa-chevron-right"></i></a>';
    }
    return $html . '</div></nav>';
}

/** @param list<array<string,mixed>> $users */
function hs_analytics_user_filter(array $users, string $activeUserId, string $basePath, array $t): string
{
    $html = '<form method="get" class="hs-analytics-user-filter hp-inline-form">'
        . '<label class="hs-field"><span class="hs-account-field-label">' . hs_h($t['analytics_user_filter'] ?? 'User') . '</span>'
        . '<select name="user" onchange="this.form.submit()">';
    foreach ($users as $u) {
        if (!is_array($u)) {
            continue;
        }
        $uid = (string) ($u['id'] ?? '');
        if ($uid === '') {
            continue;
        }
        $label = trim((string) ($u['username'] ?? ''));
        if ($label === '') {
            $label = $uid;
        }
        $num = trim((string) ($u['client_number'] ?? ''));
        if ($num !== '') {
            $label .= ' (' . $num . ')';
        }
        $html .= '<option value="' . hs_h($uid) . '"' . ($uid === $activeUserId ? ' selected' : '') . '>' . hs_h($label) . '</option>';
    }
    return $html . '</select></label></form>';
}

function hs_analytics_render(array $viewUser, string $viewUserId, int $page, array $t, bool $isAdminView = false, array $allUsers = []): string
{
    $stats = hs_activity_log_stats($viewUserId);
    $pager = hs_activity_log_page($viewUserId, $page);
    $baseUrl = hs_url(hs_panel_path('analytics.php'));
    if ($isAdminView && $viewUserId !== '') {
        $baseUrl = hs_url(hs_panel_path('analytics.php'), ['user' => $viewUserId]);
    }

    $summary = '<div class="hp-grid-2 hs-analytics-stats">'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['analytics_stat_logins'] ?? 'Logins') . '</div>'
        . '<div class="value">' . (int) $stats['logins'] . '</div></div>'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['analytics_stat_visits'] ?? 'Page views') . '</div>'
        . '<div class="value">' . (int) $stats['visits'] . '</div></div>'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['analytics_stat_changes'] ?? 'Changes') . '</div>'
        . '<div class="value">' . (int) $stats['changes'] . '</div></div>'
        . '<div class="hs-stat"><div class="label">' . hs_h($t['analytics_stat_session_time'] ?? 'Time in panel') . '</div>'
        . '<div class="value">' . hs_h(hs_activity_log_format_duration((int) $stats['session_time_sec'], $t)) . '</div></div>'
        . '</div>';

    $meta = '<p class="hp-muted hs-analytics-meta">';
    if ($stats['last_login'] !== '') {
        $meta .= hs_h($t['analytics_last_login'] ?? 'Last login') . ': <strong>' . hs_h(hs_format_date($stats['last_login'])) . '</strong> · ';
    }
    $meta .= hs_h(str_replace(
        ['{total}', '{file}'],
        [(string) $pager['total'], basename(hs_activity_log_user_file($viewUserId))],
        $t['analytics_log_file_hint'] ?? '{total} entries · file {file}'
    )) . '</p>';

    $filter = '';
    if ($isAdminView && $allUsers !== []) {
        $filter = '<div class="hs-analytics-filter-wrap">' . hs_analytics_user_filter($allUsers, $viewUserId, $baseUrl, $t) . '</div>';
    }

    if ($pager['entries'] === []) {
        return $filter . $summary . $meta
            . hs_render_card($t['analytics_activity_title'] ?? 'Activity log', '<p class="hp-muted">' . hs_h($t['analytics_log_empty'] ?? 'No entries yet') . '</p>');
    }

    $rows = [];
    foreach ($pager['entries'] as $e) {
        $type = (string) ($e['type'] ?? 'change');
        $action = (string) ($e['action'] ?? '');
        $label = $action !== '' ? hs_panel_log_action_label($action, $t) : hs_activity_log_type_label($type, $t);
        $duration = isset($e['duration_sec']) && (int) $e['duration_sec'] > 0
            ? hs_activity_log_format_duration((int) $e['duration_sec'], $t)
            : '—';
        $detail = (string) ($e['detail'] ?? '');
        $ip = (string) ($e['ip'] ?? '');
        if ($ip !== '' && $detail === '') {
            $detail = $ip;
        } elseif ($ip !== '') {
            $detail .= ' · ' . $ip;
        }
        $rows[] = '<tr>'
            . '<td class="hs-log-when">' . hs_h(hs_format_date((string) ($e['at'] ?? ''))) . '</td>'
            . '<td><span class="hs-analytics-type hs-analytics-type-' . hs_h($type) . '">' . hs_h(hs_activity_log_type_label($type, $t)) . '</span></td>'
            . '<td class="hs-log-action"><span class="hs-log-action-label">' . hs_h($label) . '</span>'
            . ($action !== '' && $label !== $action ? '<code class="hs-log-action-code">' . hs_h($action) . '</code>' : '')
            . '</td>'
            . '<td class="hs-log-detail">' . hs_h($detail) . '</td>'
            . '<td class="hs-analytics-duration">' . hs_h($duration) . '</td>'
            . '</tr>';
    }

    $table = '<div class="hs-activity-log-wrap hs-analytics-log-wrap">'
        . hs_analytics_table($rows, [
            $t['adv_history_col_when'] ?? 'When',
            $t['analytics_col_type'] ?? 'Type',
            $t['adv_history_col_action'] ?? 'Action',
            $t['adv_history_col_detail'] ?? 'Detail',
            $t['analytics_col_duration'] ?? 'Duration',
        ])
        . '</div>';

    $foot = hs_analytics_pagination($pager['page'], $pager['pages'], $baseUrl, $t)
        . '<p class="hp-muted hs-analytics-page-count">' . hs_h(str_replace(
            '{shown}',
            (string) count($pager['entries']),
            str_replace('{total}', (string) $pager['total'], $t['analytics_page_count'] ?? 'Showing {shown} of {total}')
        )) . '</p>';

    return $filter . $summary . $meta
        . hs_render_card($t['analytics_activity_title'] ?? 'Activity log', $table, $foot);
}