<?php
declare(strict_types=1);

require_once __DIR__ . '/hostinger-api.php';
$hsDomainHostingerPanel = __DIR__ . '/../domain-hostinger-panel.php';
if (is_file($hsDomainHostingerPanel)) {
    require_once $hsDomainHostingerPanel;
}

/** Active domain registrar for this host profile: hostinger | namecheap | local */
function hs_domain_registration_provider(): string
{
    if (hs_hostinger_configured() && (hs_manual_domain_hostinger() || hs_host_profile_flag('host_platform'))) {
        return 'hostinger';
    }
    require_once __DIR__ . '/namecheap-api.php';
    if (hs_namecheap_configured()) {
        return 'namecheap';
    }

    return 'local';
}

function hs_hostinger_domain_catalog_file(): string
{
    return HS_DATA_DIR . '/hostinger-domain-catalog.json';
}

/** @return array<string, array{item_id:string,price_eur:float,name:string}> */
function hs_hostinger_domain_catalog_load(): array
{
    $raw = hs_read_json(hs_hostinger_domain_catalog_file());
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $tld => $row) {
        if (!is_array($row)) {
            continue;
        }
        $out[(string) $tld] = $row;
    }

    return $out;
}

/** @param array<string, array{item_id:string,price_eur:float,name:string}> $catalog */
function hs_hostinger_domain_catalog_save(array $catalog): bool
{
    return hs_write_json(hs_hostinger_domain_catalog_file(), $catalog);
}

/** @return array{ok:bool,catalog?:array<string,array{item_id:string,price_eur:float,name:string}>,count?:int,error?:string,detail?:string} */
function hs_hostinger_refresh_domain_catalog(bool $force = false): array
{
    if (!hs_hostinger_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    if (!$force) {
        $cached = hs_hostinger_domain_catalog_load();
        if ($cached !== []) {
            return ['ok' => true, 'catalog' => $cached, 'count' => count($cached)];
        }
    }

    $res = hs_hostinger_api('GET', '/api/billing/v1/catalog?category=DOMAIN');
    if (!$res['ok']) {
        return [
            'ok' => false,
            'error' => (string) ($res['error'] ?? 'catalog_failed'),
            'detail' => is_array($res['data'] ?? null)
                ? (string) (($res['data']['message'] ?? $res['data']['error'] ?? '') ?: $res['error'] ?? '')
                : (string) ($res['error'] ?? ''),
        ];
    }

    $items = $res['data'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }
    if (isset($items['data']) && is_array($items['data'])) {
        $items = $items['data'];
    }

    $catalog = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = strtoupper(trim((string) ($item['name'] ?? '')));
        $itemId = trim((string) ($item['id'] ?? ''));
        if ($itemId === '' || $name === '') {
            continue;
        }
        $tld = ltrim($name, '.');
        if (!preg_match('/^[A-Z0-9.-]+$/', $tld)) {
            continue;
        }
        $tld = strtolower($tld);
        $priceCents = 0;
        $prices = $item['prices'] ?? [];
        if (is_array($prices)) {
            foreach ($prices as $priceRow) {
                if (!is_array($priceRow)) {
                    continue;
                }
                $period = (int) ($priceRow['period'] ?? $priceRow['periodMonths'] ?? 12);
                if ($period === 12 || $period === 1) {
                    $priceCents = (int) ($priceRow['price'] ?? $priceRow['amount'] ?? 0);
                    if ($period === 1) {
                        $priceCents *= 12;
                    }
                    break;
                }
            }
            if ($priceCents === 0 && isset($prices[0]) && is_array($prices[0])) {
                $priceCents = (int) ($prices[0]['price'] ?? $prices[0]['amount'] ?? 0);
            }
        }
        $catalog[$tld] = [
            'item_id' => $itemId,
            'price_eur' => round($priceCents / 100, 2),
            'name' => $name,
        ];
    }

    if ($catalog !== []) {
        hs_hostinger_domain_catalog_save($catalog);
    }

    return ['ok' => true, 'catalog' => $catalog, 'count' => count($catalog)];
}

function hs_hostinger_catalog_item_id_for_tld(string $tld): ?string
{
    $tld = ltrim(strtolower(trim($tld)), '.');
    $catalog = hs_hostinger_domain_catalog_load();
    if ($catalog === []) {
        $ref = hs_hostinger_refresh_domain_catalog(true);
        $catalog = is_array($ref['catalog'] ?? null) ? $ref['catalog'] : [];
    }

    return isset($catalog[$tld]['item_id']) ? (string) $catalog[$tld]['item_id'] : null;
}

/** @return array<string, float> TLD => EUR/year (wholesale from Hostinger catalog) */
function hs_hostinger_tld_prices_eur(bool $refresh = false): array
{
    $ref = hs_hostinger_refresh_domain_catalog($refresh);
    if (!$ref['ok']) {
        return [];
    }
    $out = [];
    foreach ($ref['catalog'] ?? [] as $tld => $row) {
        if (!is_array($row)) {
            continue;
        }
        $out[$tld] = (float) ($row['price_eur'] ?? 0);
    }

    return $out;
}

/** @return array{configured:bool,account:string,api_base:string,portfolio_count:int,catalog_count:int,nameservers:list<string>,display_nameservers:list<string>,server_ip:string,vps_id:int} */
function hs_hostinger_domain_status(): array
{
    $cfg = hs_hostinger_config();

    return [
        'configured' => hs_hostinger_configured(),
        'account' => trim((string) ($cfg['hosting_account'] ?? 'u762384583')),
        'api_base' => hs_hostinger_api_base(),
        'portfolio_count' => 0,
        'catalog_count' => count(hs_hostinger_domain_catalog_load()),
        'nameservers' => hs_registry_nameservers(),
        'display_nameservers' => hs_display_nameservers(),
        'server_ip' => hs_server_ip(),
        'vps_id' => (int) ($cfg['vps_id'] ?? 0),
    ];
}

/** @return array{ok:bool,detail?:string,portfolio_count?:int,error?:string} */
function hs_hostinger_test_connection(bool $refreshCatalog = false): array
{
    if (!hs_hostinger_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    $list = hs_hostinger_domains_list_all();
    if ($list === [] && hs_hostinger_api('GET', '/api/domains/v1/portfolio')['ok'] === false) {
        $probe = hs_hostinger_api('GET', '/api/domains/v1/portfolio');
        return [
            'ok' => false,
            'error' => (string) ($probe['error'] ?? 'api_error'),
            'detail' => is_array($probe['data'] ?? null)
                ? (string) (($probe['data']['message'] ?? '') ?: $probe['error'] ?? '')
                : (string) ($probe['error'] ?? ''),
        ];
    }
    if ($refreshCatalog) {
        hs_hostinger_refresh_domain_catalog(true);
    }
    $detail = count($list) . ' ' . (count($list) === 1 ? 'domain' : 'domains') . ' in portfolio';
    $cat = count(hs_hostinger_domain_catalog_load());
    if ($cat > 0) {
        $detail .= ', ' . $cat . ' TLD prices cached';
    }

    return ['ok' => true, 'detail' => $detail, 'portfolio_count' => count($list)];
}

/** @return list<array{domain:string,status:string,expires:string,id:int}> */
function hs_hostinger_domains_list_all(): array
{
    if (!hs_hostinger_configured()) {
        return [];
    }
    $res = hs_hostinger_api('GET', '/api/domains/v1/portfolio');
    if (!$res['ok']) {
        return [];
    }
    $rows = $res['data'] ?? [];
    if (!is_array($rows)) {
        return [];
    }
    if (isset($rows['data']) && is_array($rows['data'])) {
        $rows = $rows['data'];
    }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $domain = strtolower(trim((string) ($row['domain'] ?? '')));
        if ($domain === '') {
            continue;
        }
        $expires = '';
        if (!empty($row['expiresAt'])) {
            $expires = gmdate('Y-m-d', strtotime((string) $row['expiresAt']));
        } elseif (!empty($row['expires_at'])) {
            $expires = gmdate('Y-m-d', strtotime((string) $row['expires_at']));
        }
        $out[] = [
            'domain' => $domain,
            'status' => (string) ($row['status'] ?? ''),
            'expires' => $expires,
            'id' => (int) ($row['id'] ?? 0),
        ];
    }

    return $out;
}

/** @return array{ok:bool,results?:list<array{domain:string,available:bool,restriction?:string}>,error?:string,detail?:string} */
function hs_hostinger_check_domains(array $domains): array
{
    if (!hs_hostinger_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    require_once dirname(__DIR__) . '/domain-store.php';
    $results = [];
    foreach ($domains as $domain) {
        $p = hs_domain_parse((string) $domain);
        if ($p === null) {
            return ['ok' => false, 'error' => 'invalid'];
        }
        $body = [
            'domain' => $p['sld'],
            'tlds' => [$p['tld']],
            'withAlternatives' => false,
        ];
        $res = hs_hostinger_api('POST', '/api/domains/v1/availability', $body);
        if (!$res['ok']) {
            return [
                'ok' => false,
                'error' => (string) ($res['error'] ?? 'check_failed'),
                'detail' => is_array($res['data'] ?? null) ? (string) ($res['data']['message'] ?? '') : '',
            ];
        }
        $rows = $res['data'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }
        if (isset($rows['data']) && is_array($rows['data'])) {
            $rows = $rows['data'];
        }
        $available = false;
        $restriction = '';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $full = strtolower(trim((string) ($row['domain'] ?? $p['full'])));
            if ($full === $p['full'] || $full === '') {
                $available = !empty($row['isAvailable']) || !empty($row['is_available']);
                $restriction = (string) ($row['restriction'] ?? '');
                break;
            }
        }
        if ($rows !== [] && !$available && isset($rows[0]) && is_array($rows[0])) {
            $available = !empty($rows[0]['isAvailable']) || !empty($rows[0]['is_available']);
            $restriction = (string) ($rows[0]['restriction'] ?? '');
        }
        $results[] = [
            'domain' => $p['full'],
            'available' => $available,
            'restriction' => $restriction,
        ];
    }

    return ['ok' => true, 'results' => $results];
}

/** @return array{ok:bool,available?:bool,price?:float,source?:string,error?:string} */
function hs_hostinger_check_domain(string $domain): array
{
    $check = hs_hostinger_check_domains([$domain]);
    if (!$check['ok'] || empty($check['results'][0])) {
        return ['ok' => false, 'error' => (string) ($check['error'] ?? 'check_failed')];
    }
    $row = $check['results'][0];
    require_once dirname(__DIR__) . '/domain-store.php';
    $price = hs_domain_price((string) $row['domain']);

    return [
        'ok' => true,
        'domain' => (string) $row['domain'],
        'available' => !empty($row['available']),
        'price' => $price,
        'source' => 'hostinger',
    ];
}

/** @param list<string> $nameservers */
function hs_hostinger_set_nameservers(string $domain, array $nameservers): array
{
    $domain = strtolower(trim($domain));
    $nameservers = array_values(array_filter(array_map('trim', $nameservers)));
    if ($domain === '' || count($nameservers) < 2) {
        return ['ok' => false, 'error' => 'nameservers'];
    }
    $body = [
        'ns1' => $nameservers[0],
        'ns2' => $nameservers[1],
    ];
    if (isset($nameservers[2])) {
        $body['ns3'] = $nameservers[2];
    }
    if (isset($nameservers[3])) {
        $body['ns4'] = $nameservers[3];
    }
    $res = hs_hostinger_api('PUT', '/api/domains/v1/portfolio/' . rawurlencode($domain) . '/nameservers', $body);
    if (!$res['ok']) {
        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'ns_failed')];
    }

    return ['ok' => true];
}

/** @return array{ok:bool,domain?:string,source?:string,error?:string} */
function hs_hostinger_register_domain(string $domain): array
{
    require_once dirname(__DIR__) . '/domain-store.php';
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    if (!hs_hostinger_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }

    $itemId = hs_hostinger_catalog_item_id_for_tld($p['tld']);
    if ($itemId === null || $itemId === '') {
        return ['ok' => false, 'error' => 'catalog_item_missing'];
    }

    $body = [
        'domain' => $p['full'],
        'itemId' => $itemId,
    ];
    $res = hs_hostinger_api('POST', '/api/domains/v1/portfolio', $body);
    if (!$res['ok']) {
        return [
            'ok' => false,
            'error' => (string) ($res['error'] ?? 'register_failed'),
            'detail' => is_array($res['data'] ?? null) ? (string) ($res['data']['message'] ?? '') : '',
        ];
    }

    $ns = hs_registry_nameservers();
    if (count($ns) >= 2) {
        hs_hostinger_set_nameservers($p['full'], $ns);
    }

    return ['ok' => true, 'domain' => $p['full'], 'source' => 'hostinger'];
}

/** @return array{ok:bool,registered?:bool,skipped?:bool,error?:string,source?:string} */
function hs_hostinger_register_for_user(string $userId, string $domain, array $user): array
{
    require_once dirname(__DIR__) . '/domain-store.php';
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    if (!hs_domain_tld_registrable($p['tld'])) {
        return ['ok' => false, 'error' => 'tld_not_registrable'];
    }
    if (!hs_hostinger_configured()) {
        return ['ok' => true, 'skipped' => true, 'source' => 'local'];
    }

    return hs_hostinger_register_domain($domain);
}

/** @return array{ok:bool,hi_domains?:int,checked?:int,updated?:int,error?:string,detail?:string} */
function hs_hostinger_sync_domain_registries(): array
{
    if (!hs_hostinger_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }

    require_once dirname(__DIR__) . '/panel-domains.php';
    require_once dirname(__DIR__) . '/storage.php';
    require_once dirname(__DIR__) . '/user-settings.php';

    $hiList = hs_hostinger_domains_list_all();
    if ($hiList === [] && !hs_hostinger_api('GET', '/api/domains/v1/portfolio')['ok']) {
        $probe = hs_hostinger_api('GET', '/api/domains/v1/portfolio');
        return [
            'ok' => false,
            'error' => (string) ($probe['error'] ?? 'portfolio_failed'),
            'detail' => is_array($probe['data'] ?? null) ? (string) ($probe['data']['message'] ?? '') : '',
        ];
    }

    $hiByDomain = [];
    foreach ($hiList as $row) {
        $hiByDomain[$row['domain']] = $row;
    }

    $checked = 0;
    $updated = 0;
    foreach (hs_users() as $user) {
        if (!is_array($user)) {
            continue;
        }
        $userId = (string) ($user['id'] ?? '');
        if ($userId === '') {
            continue;
        }
        $settings = hs_user_settings_get($userId);
        $registry = hs_user_domain_registry_ensure($userId, $settings);
        $changed = false;
        foreach ($registry as &$entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (!empty($entry['pending_registration']) || !empty($entry['pending_payment'])) {
                continue;
            }
            $dom = strtolower(trim((string) ($entry['domain'] ?? '')));
            if ($dom === '' || !isset($hiByDomain[$dom])) {
                continue;
            }
            $checked++;
            $hi = $hiByDomain[$dom];
            $newExpires = (string) ($hi['expires'] ?? '');
            $patch = [];
            if ($newExpires !== '' && (string) ($entry['expires_at'] ?? '') !== $newExpires) {
                $patch['expires_at'] = $newExpires;
            }
            if (($hi['status'] ?? '') !== '' && (string) ($entry['hi_status'] ?? '') !== $hi['status']) {
                $patch['hi_status'] = $hi['status'];
            }
            if ($patch !== []) {
                foreach ($patch as $k => $v) {
                    $entry[$k] = $v;
                }
                $entry['hi_synced_at'] = gmdate('c');
                $changed = true;
                $updated++;
            }
        }
        unset($entry);
        if ($changed) {
            hs_user_settings_save($userId, ['domain_registry' => $registry]);
        }
    }

    return [
        'ok' => true,
        'hi_domains' => count($hiList),
        'checked' => $checked,
        'updated' => $updated,
    ];
}

function hs_domain_sync_registries(): array
{
    if (hs_domain_registration_provider() === 'hostinger') {
        return hs_hostinger_sync_domain_registries();
    }
    require_once __DIR__ . '/namecheap-api.php';

    return hs_namecheap_sync_domain_registries();
}

function hs_domain_sync_cron_url(): string
{
    if (!defined('HS_DOMAIN_SYNC_TOKEN') || HS_DOMAIN_SYNC_TOKEN === '') {
        return '';
    }

    return hs_canonical_url('admin/domain-sync-cron.php') . '?token=' . rawurlencode((string) HS_DOMAIN_SYNC_TOKEN);
}

function hs_domain_sync_cron_command(): string
{
    $url = hs_domain_sync_cron_url();
    if ($url === '') {
        return '';
    }

    return '/usr/bin/curl -fsS ' . escapeshellarg($url);
}