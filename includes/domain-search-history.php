<?php
declare(strict_types=1);

define('HS_DOMAIN_VISITOR_COOKIE', 'hs_visitor');
define('HS_DOMAIN_SEARCH_TTL', 2592000); // 30 days
define('HS_DOMAIN_SEARCH_MAX', 15);

function hs_domain_search_history_dir(): string
{
    return HS_DATA_DIR . '/cache/domain-search-history';
}

function hs_domain_search_visitor_id(): string
{
    require_once __DIR__ . '/security.php';
    hs_session_start();

    $cookie = (string) ($_COOKIE[HS_DOMAIN_VISITOR_COOKIE] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/', $cookie)) {
        $cookie = bin2hex(random_bytes(16));
        setcookie(HS_DOMAIN_VISITOR_COOKIE, $cookie, [
            'expires' => time() + HS_DOMAIN_SEARCH_TTL,
            'path' => hs_cookie_path(),
            'secure' => hs_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[HS_DOMAIN_VISITOR_COOKIE] = $cookie;
    }

    $_SESSION['hs_domain_visitor'] = $cookie;

    return $cookie;
}

/** @return list<array{query:string,at:int,available_count?:int}> */
function hs_domain_search_history_read(string $visitorId): array
{
    $file = hs_domain_search_history_dir() . '/' . $visitorId . '.json';
    if (!is_readable($file)) {
        return [];
    }
    $raw = json_decode((string) file_get_contents($file), true);
    if (!is_array($raw) || !is_array($raw['items'] ?? null)) {
        return [];
    }
    $cutoff = time() - HS_DOMAIN_SEARCH_TTL;
    $out = [];
    foreach ($raw['items'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $query = strtolower(trim((string) ($row['query'] ?? '')));
        $at = (int) ($row['at'] ?? 0);
        if ($query === '' || $at < $cutoff) {
            continue;
        }
        $out[] = [
            'query' => $query,
            'at' => $at,
            'available_count' => (int) ($row['available_count'] ?? 0),
        ];
    }

    return array_slice($out, 0, HS_DOMAIN_SEARCH_MAX);
}

/** @return list<array{query:string,at:int,available_count?:int}> */
function hs_domain_search_history_list(): array
{
    require_once __DIR__ . '/security.php';
    hs_session_start();
    $visitor = hs_domain_search_visitor_id();
    $items = hs_domain_search_history_read($visitor);
    $_SESSION['hs_domain_search_recent'] = $items;

    return $items;
}

/** @param array{available_count?:int} $meta */
function hs_domain_search_history_add(string $query, array $meta = []): void
{
    $query = strtolower(trim($query));
    if ($query === '') {
        return;
    }
    if (!str_contains($query, '.')) {
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $query)) {
            return;
        }
    } elseif (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $query)) {
        return;
    }

    $visitor = hs_domain_search_visitor_id();
    $items = hs_domain_search_history_read($visitor);
    $items = array_values(array_filter(
        $items,
        static fn(array $row): bool => (string) ($row['query'] ?? '') !== $query
    ));
    array_unshift($items, [
        'query' => $query,
        'at' => time(),
        'available_count' => max(0, (int) ($meta['available_count'] ?? 0)),
    ]);
    $items = array_slice($items, 0, HS_DOMAIN_SEARCH_MAX);

    $dir = hs_domain_search_history_dir();
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
        return;
    }
    @file_put_contents(
        $dir . '/' . $visitor . '.json',
        json_encode([
            'visitor' => $visitor,
            'updated_at' => time(),
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    require_once __DIR__ . '/security.php';
    hs_session_start();
    $_SESSION['hs_domain_search_recent'] = $items;
}

/**
 * @param list<array{query:string,at:int,available_count?:int}> $items
 * @param array{base_url?:string,query_param?:string} $opts
 */
function hs_render_domain_search_recent(array $t, array $items, array $opts = []): string
{
    if ($items === []) {
        return '';
    }

    $baseUrl = (string) ($opts['base_url'] ?? hs_url('domain'));
    $queryParam = (string) ($opts['query_param'] ?? 'sld');
    $title = (string) ($t['domain_recent_searches'] ?? 'Recent searches');
    $hint = (string) ($t['domain_recent_searches_hint'] ?? '');
    $html = '<aside class="hs-domain-recent" data-hs-domain-recent aria-label="' . hs_h($title) . '">';
    $html .= '<div class="hs-domain-recent-head"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>';
    $html .= '<span>' . hs_h($title) . '</span></div>';
    if ($hint !== '') {
        $html .= '<p class="hs-domain-recent-hint hp-muted">' . hs_h($hint) . '</p>';
    }
    $html .= '<div class="hs-domain-recent-list" role="list">';
    foreach ($items as $row) {
        $q = (string) ($row['query'] ?? '');
        if ($q === '') {
            continue;
        }
        $url = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?')
            . rawurlencode($queryParam) . '=' . rawurlencode($q);
        $avail = (int) ($row['available_count'] ?? 0);
        $badge = $avail > 0
            ? '<span class="hs-domain-recent-badge is-ok" title="' . hs_h((string) ($t['domain_available'] ?? 'Available')) . '">' . (int) $avail . '</span>'
            : '';
        $html .= '<a href="' . hs_h($url) . '" class="hs-domain-recent-chip" role="listitem" data-hs-recent-query="' . hs_h($q) . '">'
            . '<span class="hs-domain-recent-query">' . hs_h($q) . '</span>'
            . $badge
            . '</a>';
    }
    $html .= '</div></aside>';

    return $html;
}