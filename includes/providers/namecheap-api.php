<?php
declare(strict_types=1);

function hs_namecheap_configured(): bool
{
    return defined('NC_API_USER') && NC_API_USER !== ''
        && defined('NC_API_KEY') && NC_API_KEY !== ''
        && defined('NC_CLIENT_IP') && NC_CLIENT_IP !== '';
}

/** Skip live Namecheap HTTP during public page loads; admin/cron sets HS_ALLOW_NAMECHEAP_LIVE_API. */
function hs_namecheap_live_api_allowed(): bool
{
    return defined('HS_ALLOW_NAMECHEAP_LIVE_API') && HS_ALLOW_NAMECHEAP_LIVE_API;
}

function hs_namecheap_circuit_file(): string
{
    return HS_DATA_DIR . '/namecheap-circuit.json';
}

/** @return array{open_until:int,failures:int,last_error:string} */
function hs_namecheap_circuit_read(): array
{
    if (isset($GLOBALS['hs_namecheap_circuit_memo']) && is_array($GLOBALS['hs_namecheap_circuit_memo'])) {
        return $GLOBALS['hs_namecheap_circuit_memo'];
    }
    $memo = ['open_until' => 0, 'failures' => 0, 'last_error' => ''];
    $file = hs_namecheap_circuit_file();
    if (is_readable($file)) {
        $raw = json_decode((string) file_get_contents($file), true);
        if (is_array($raw)) {
            $memo['open_until'] = (int) ($raw['open_until'] ?? 0);
            $memo['failures'] = (int) ($raw['failures'] ?? 0);
            $memo['last_error'] = (string) ($raw['last_error'] ?? '');
        }
    }
    $GLOBALS['hs_namecheap_circuit_memo'] = $memo;
    return $memo;
}

function hs_namecheap_circuit_reset(): void
{
    $file = hs_namecheap_circuit_file();
    if (is_file($file)) {
        @unlink($file);
    }
    $GLOBALS['hs_namecheap_circuit_memo'] = ['open_until' => 0, 'failures' => 0, 'last_error' => ''];
}

/** @return array{open:bool,open_until:int,seconds_left:int,failures:int,last_error:string} */
function hs_namecheap_circuit_info(): array
{
    $state = hs_namecheap_circuit_read();
    $openUntil = (int) ($state['open_until'] ?? 0);
    $left = max(0, $openUntil - time());

    return [
        'open' => $left > 0,
        'open_until' => $openUntil,
        'seconds_left' => $left,
        'failures' => (int) ($state['failures'] ?? 0),
        'last_error' => (string) ($state['last_error'] ?? ''),
    ];
}

function hs_namecheap_circuit_is_open(): bool
{
    return hs_namecheap_circuit_info()['open'];
}

/** Public/cron respect the breaker; admin + domain-check may still call Namecheap. */
function hs_namecheap_circuit_blocks_request(): bool
{
    if (!hs_namecheap_circuit_is_open()) {
        return false;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_NAME'] ?? ''));
    // Keep domain search working even if the circuit is open (RDAP/WHOIS is too slow / 504).
    if (str_contains($script, '/admin/') || str_contains($script, 'domain-check.php')) {
        return false;
    }

    return true;
}

function hs_namecheap_circuit_record(bool $ok, string $error = ''): void
{
    $state = hs_namecheap_circuit_read();
    if ($ok) {
        $state = ['open_until' => 0, 'failures' => 0, 'last_error' => ''];
    } else {
        $failures = (int) ($state['failures'] ?? 0) + 1;
        $openFor = $failures >= 2 ? 900 : 300;
        $state = [
            'open_until' => time() + $openFor,
            'failures' => $failures,
            'last_error' => $error !== '' ? $error : (string) ($state['last_error'] ?? 'api_error'),
        ];
    }
    if (is_dir(HS_DATA_DIR)) {
        @file_put_contents(hs_namecheap_circuit_file(), json_encode($state, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}

/** True when Namecheap HTTP should be skipped (circuit open or public page). */
function hs_namecheap_skip_live_api(): bool
{
    return !hs_namecheap_configured()
        || hs_namecheap_circuit_blocks_request()
        || !hs_namecheap_live_api_allowed();
}

/**
 * TLDs we must not sell/register via Namecheap (Norid .no, etc.).
 * Availability lookup may still work; shop UI and orders exclude these zones.
 */
function hs_namecheap_registry_only_tlds(): array
{
    return ['no'];
}

function hs_namecheap_tld_api_supported(string $tld): bool
{
    $tld = strtolower(trim($tld));
    return $tld !== '' && !in_array($tld, hs_namecheap_registry_only_tlds(), true);
}

function hs_namecheap_is_registry_only_tld(string $tld): bool
{
    return !hs_namecheap_tld_api_supported($tld);
}

function hs_namecheap_sellable_tlds_cache_file(): string
{
    return HS_DATA_DIR . '/namecheap-sellable-tlds.json';
}

/**
 * TLDs we actively sell — curated manual list intersected with Namecheap API register pricing.
 * Prevents charging for zones we cannot register via API.
 *
 * @return list<string>
 */
function hs_namecheap_sellable_tlds(bool $forceRefresh = false): array
{
    static $memo = null;
    static $memoAt = 0;
    $ttl = 43200;

    if (!$forceRefresh && is_array($memo) && $memoAt > time() - $ttl) {
        return $memo;
    }

    $cacheFile = hs_namecheap_sellable_tlds_cache_file();
    if (!$forceRefresh && is_readable($cacheFile)) {
        $raw = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($raw) && ($raw['fetched_at'] ?? 0) > time() - $ttl && is_array($raw['tlds'] ?? null)) {
            $memo = array_values(array_filter($raw['tlds'], 'is_string'));
            $memoAt = (int) $raw['fetched_at'];
            return $memo;
        }
    }

    $manual = array_keys(hs_namecheap_manual_wholesale_usd());
    $manual = array_values(array_filter($manual, static fn(string $t): bool => hs_namecheap_tld_api_supported($t)));

    if (!hs_namecheap_configured() || hs_namecheap_skip_live_api()) {
        $memo = $manual;
        $memoAt = time();
        return $memo;
    }

    $apiRegister = hs_namecheap_fetch_register_prices_usd();
    if ($apiRegister === []) {
        $memo = $manual;
        $memoAt = time();
        return $memo;
    }

    $sellable = [];
    foreach ($manual as $tld) {
        if (isset($apiRegister[$tld]) && (float) $apiRegister[$tld] > 0) {
            $sellable[] = $tld;
        }
    }

    $sellable = $sellable !== [] ? $sellable : $manual;
    $fetchedAt = time();
    if (is_dir(HS_DATA_DIR)) {
        @file_put_contents($cacheFile, json_encode([
            'fetched_at' => $fetchedAt,
            'tlds' => $sellable,
            'manual_count' => count($manual),
            'api_register_count' => count($apiRegister),
        ], JSON_UNESCAPED_UNICODE));
    }

    $memo = $sellable;
    $memoAt = $fetchedAt;
    return $sellable;
}

function hs_domain_tld_registrable(string $tld): bool
{
    $tld = strtolower(trim($tld));
    if ($tld === '' || hs_namecheap_is_registry_only_tld($tld)) {
        return false;
    }

    return in_array($tld, hs_namecheap_sellable_tlds(), true);
}

/** @param array{open?:bool,seconds_left?:int,last_error?:string} $circuit */
function hs_namecheap_circuit_detail_message(array $circuit): string
{
    $last = trim((string) ($circuit['last_error'] ?? ''));
    $left = max(0, (int) ($circuit['seconds_left'] ?? 0));
    $mins = (int) ceil($left / 60);
    $msg = 'API temporarily paused after repeated errors';
    if ($last !== '') {
        $msg .= ' (last: ' . $last . ')';
    }
    if ($left > 0) {
        $msg .= '. Retry in ~' . $mins . ' min or reset the circuit in Admin → Namecheap.';
    }

    return $msg;
}

function hs_namecheap_api_base(): string
{
    $sandbox = defined('NC_SANDBOX') && NC_SANDBOX;
    return $sandbox
        ? 'https://api.sandbox.namecheap.com/xml.response'
        : 'https://api.namecheap.com/xml.response';
}

/** @return array{ok:bool,xml?:SimpleXMLElement,error?:string,errors?:list<string>} */
function hs_namecheap_call(string $command, array $params = []): array
{
    if (!hs_namecheap_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }
    if (hs_namecheap_circuit_blocks_request()) {
        $circuit = hs_namecheap_circuit_info();
        return [
            'ok' => false,
            'error' => 'circuit_open',
            'circuit' => $circuit,
            'detail' => hs_namecheap_circuit_detail_message($circuit),
        ];
    }

    $base = [
        'ApiUser' => NC_API_USER,
        'ApiKey' => NC_API_KEY,
        'UserName' => defined('NC_USERNAME') ? NC_USERNAME : NC_API_USER,
        'ClientIp' => NC_CLIENT_IP,
        'Command' => $command,
    ];
    $query = http_build_query($base + $params);
    $url = hs_namecheap_api_base() . '?' . $query;
    $debug = defined('NC_DEBUG') && NC_DEBUG;

    $body = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT => 'Solaskinner-Hosting-CMS/2.7 (+https://solaskinner.com)',
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            curl_close($ch);
            hs_namecheap_circuit_record(false, 'http_failed');
            return ['ok' => false, 'error' => 'http_failed'];
        }
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'BILOHASH-Hosting-CMS/2.0']]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            hs_namecheap_circuit_record(false, 'http_failed');
            return ['ok' => false, 'error' => 'http_failed'];
        }
        $body = (string) $raw;
    }

    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if (!$xml instanceof SimpleXMLElement) {
        return ['ok' => false, 'error' => 'invalid_xml'];
    }

    $status = strtoupper((string) ($xml['Status'] ?? ''));
    if ($status !== 'OK') {
        $errors = [];
        if (isset($xml->Errors->Error)) {
            foreach ($xml->Errors->Error as $err) {
                $errors[] = trim((string) $err);
            }
        }
        $out = [
            'ok' => false,
            'error' => $errors[0] ?? 'api_error',
            'errors' => $errors,
            'xml' => $xml,
        ];
        hs_namecheap_circuit_record(false, (string) ($errors[0] ?? 'api_error'));
        if ($debug) {
            hs_namecheap_debug_log($command, $params, $out);
        }
        return $out;
    }

    hs_namecheap_circuit_record(true);
    $out = ['ok' => true, 'xml' => $xml];
    if ($debug) {
        hs_namecheap_debug_log($command, $params, $out);
    }
    return $out;
}

function hs_namecheap_debug_log_file(): string
{
    return HS_DATA_DIR . '/namecheap-api-debug.log';
}

/** @param array<string, mixed> $params @param array<string, mixed> $result */
function hs_namecheap_debug_log(string $command, array $params, array $result): void
{
    if (!is_dir(HS_DATA_DIR)) {
        return;
    }
    $file = hs_namecheap_debug_log_file();
    if (is_file($file) && filesize($file) > 5_000_000) {
        @rename($file, $file . '.' . date('Ymd-His') . '.bak');
    }
    $safeParams = $params;
    foreach (['ApiKey', 'Password'] as $key) {
        if (isset($safeParams[$key])) {
            $safeParams[$key] = '***';
        }
    }
    $line = json_encode([
        'at' => gmdate('c'),
        'command' => $command,
        'params' => $safeParams,
        'ok' => $result['ok'] ?? false,
        'error' => $result['error'] ?? null,
        'errors' => $result['errors'] ?? null,
    ], JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function hs_namecheap_parse_nc_date(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '';
    }
    return gmdate('c', $ts);
}

/** @return list<array{domain:string,expires:string,status:string,auto_renew:bool,is_expired:bool}> */
function hs_namecheap_domains_list_all(): array
{
    $all = [];
    $page = 1;
    $pageSize = 100;
    while ($page <= 50) {
        $res = hs_namecheap_call('namecheap.domains.getList', [
            'Page' => (string) $page,
            'PageSize' => (string) $pageSize,
            'SortBy' => 'NAME',
        ]);
        if (!$res['ok'] || !isset($res['xml'])) {
            break;
        }
        $root = $res['xml']->CommandResponse->DomainGetListResult ?? null;
        if ($root === null) {
            break;
        }
        $nodes = $root->Domain ?? [];
        if ($nodes === null || count($nodes) === 0) {
            break;
        }
        foreach ($nodes as $node) {
            $domain = strtolower(trim((string) ($node['Name'] ?? '')));
            if ($domain === '') {
                continue;
            }
            $all[] = [
                'domain' => $domain,
                'expires' => trim((string) ($node['Expires'] ?? '')),
                'status' => trim((string) ($node['Status'] ?? '')),
                'auto_renew' => strtolower((string) ($node['AutoRenew'] ?? 'false')) === 'true',
                'is_expired' => strtolower((string) ($node['IsExpired'] ?? 'false')) === 'true',
            ];
        }
        if (count($nodes) < $pageSize) {
            break;
        }
        $page++;
    }
    return $all;
}

/** @return array{ok:bool,nc_domains?:int,checked?:int,updated?:int,error?:string} */
function hs_namecheap_sync_domain_registries(): array
{
    if (!hs_namecheap_configured()) {
        return ['ok' => false, 'error' => 'not_configured'];
    }

    require_once dirname(__DIR__) . '/panel-domains.php';
    require_once dirname(__DIR__) . '/storage.php';
    require_once dirname(__DIR__) . '/user-settings.php';

    $ncList = hs_namecheap_domains_list_all();
    if ($ncList === []) {
        $probe = hs_namecheap_call('namecheap.users.getBalances');
        if (!$probe['ok']) {
            return [
                'ok' => false,
                'error' => (string) ($probe['error'] ?? 'getList_failed'),
                'detail' => (string) ($probe['detail'] ?? $probe['error'] ?? 'getList_failed'),
            ];
        }
    }

    $ncByDomain = [];
    foreach ($ncList as $row) {
        $ncByDomain[$row['domain']] = $row;
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
            if ($dom === '' || !isset($ncByDomain[$dom])) {
                continue;
            }
            $checked++;
            $nc = $ncByDomain[$dom];
            $newExpires = hs_namecheap_parse_nc_date($nc['expires']);
            $patch = [];
            if ($newExpires !== '' && (string) ($entry['expires_at'] ?? '') !== $newExpires) {
                $patch['expires_at'] = $newExpires;
            }
            if (($nc['status'] ?? '') !== '' && (string) ($entry['nc_status'] ?? '') !== $nc['status']) {
                $patch['nc_status'] = $nc['status'];
            }
            if (($entry['nc_auto_renew'] ?? null) !== $nc['auto_renew']) {
                $patch['nc_auto_renew'] = $nc['auto_renew'];
            }
            if ($patch !== []) {
                foreach ($patch as $k => $v) {
                    $entry[$k] = $v;
                }
                $entry['nc_synced_at'] = gmdate('c');
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
        'nc_domains' => count($ncList),
        'checked' => $checked,
        'updated' => $updated,
    ];
}

function hs_namecheap_domain_sync_cron_url(): string
{
    if (!defined('HS_DOMAIN_SYNC_TOKEN') || HS_DOMAIN_SYNC_TOKEN === '') {
        return '';
    }
    return hs_canonical_url('admin/domain-sync-cron.php') . '?token=' . rawurlencode((string) HS_DOMAIN_SYNC_TOKEN);
}

function hs_namecheap_domain_sync_cron_command(): string
{
    $url = hs_namecheap_domain_sync_cron_url();
    if ($url === '') {
        return '';
    }
    return '/usr/bin/curl -fsS ' . escapeshellarg($url);
}

/**
 * Live Namecheap domains.check — includes premium detection + wholesale USD fees.
 *
 * @return array{
 *   ok:bool,
 *   results?:list<array{
 *     domain:string,available:bool,premium:bool,
 *     wholesale_usd:?float,eap_usd:float,renew_usd:?float,
 *     price_usd:?float,price_eur:?float
 *   }>,
 *   error?:string
 * }
 */
function hs_namecheap_check_domains(array $domains): array
{
    $domains = array_values(array_unique(array_filter(array_map(
        static fn($d) => strtolower(trim((string) $d)),
        $domains
    ))));
    if ($domains === []) {
        return ['ok' => false, 'error' => 'empty'];
    }
    if (count($domains) > 50) {
        $domains = array_slice($domains, 0, 50);
    }

    $res = hs_namecheap_call('namecheap.domains.check', [
        'DomainList' => implode(',', $domains),
    ]);
    if (!$res['ok'] || !isset($res['xml'])) {
        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'check_failed')];
    }

    require_once dirname(__DIR__) . '/domain-store.php';
    $results = [];
    $nodes = $res['xml']->CommandResponse->DomainCheckResult ?? null;
    if ($nodes === null) {
        return ['ok' => false, 'error' => 'no_results'];
    }
    foreach ($nodes as $node) {
        $domain = strtolower((string) ($node['Domain'] ?? ''));
        if ($domain === '') {
            continue;
        }
        $available = strtolower((string) ($node['Available'] ?? 'false')) === 'true';
        $premium = strtolower((string) ($node['IsPremiumName'] ?? 'false')) === 'true';
        $regUsd = null;
        $eapUsd = 0.0;
        $renewUsd = null;
        if (isset($node['PremiumRegistrationPrice']) && (string) $node['PremiumRegistrationPrice'] !== '') {
            $regUsd = (float) $node['PremiumRegistrationPrice'];
        }
        if (isset($node['EapFee']) && (string) $node['EapFee'] !== '') {
            $eapUsd = max(0.0, (float) $node['EapFee']);
        }
        // Some responses use PremiumAssistiveYearlyFee / IcannFee
        if ($eapUsd <= 0 && isset($node['PremiumAssistiveYearlyFee'])) {
            $eapUsd = max(0.0, (float) $node['PremiumAssistiveYearlyFee']);
        }
        if (isset($node['PremiumRenewalPrice']) && (string) $node['PremiumRenewalPrice'] !== '') {
            $renewUsd = (float) $node['PremiumRenewalPrice'];
        }
        // Non-premium: leave wholesale null (use TLD catalog). Premium: reg + EAP.
        $wholesaleUsd = null;
        $priceEur = null;
        if ($available && $premium && $regUsd !== null && $regUsd > 0) {
            $wholesaleUsd = round($regUsd + $eapUsd, 4);
            $priceEur = hs_domain_retail_eur_from_usd($wholesaleUsd);
        } elseif ($available && !$premium) {
            // Standard price from TLD list (already includes markup)
            $priceEur = hs_domain_price($domain);
        }
        $results[] = [
            'domain' => $domain,
            'available' => $available,
            'premium' => $premium,
            'wholesale_usd' => $wholesaleUsd,
            'eap_usd' => $eapUsd,
            'renew_usd' => $renewUsd,
            // Back-compat: price = wholesale USD for premium (old callers)
            'price' => $wholesaleUsd,
            'price_usd' => $wholesaleUsd,
            'price_eur' => $priceEur,
            'premium_registration_usd' => $regUsd,
        ];
    }

    return ['ok' => true, 'results' => $results];
}

function hs_namecheap_format_phone(string $phone, string $country): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    $country = strtoupper($country);
    $ccMap = [
        'NO' => '47', 'UA' => '380', 'LT' => '370', 'SE' => '46', 'GB' => '44', 'UK' => '44',
        'US' => '1', 'DE' => '49', 'PL' => '48', 'LV' => '371', 'EE' => '372',
    ];
    $cc = $ccMap[$country] ?? '1';
    if (str_starts_with($digits, $cc)) {
        $local = substr($digits, strlen($cc));
    } else {
        $local = ltrim($digits, '0');
    }
    if ($local === '') {
        $local = '0000000';
    }
    return '+' . $cc . '.' . $local;
}

/** @param array<string, string> $contact */
function hs_namecheap_contact_params(array $contact, string $prefix): array
{
    $map = [
        'FirstName' => 'first_name',
        'LastName' => 'last_name',
        'Address1' => 'address',
        'City' => 'city',
        'StateProvince' => 'state',
        'PostalCode' => 'postal',
        'Country' => 'country',
        'Phone' => 'phone',
        'EmailAddress' => 'email',
    ];
    $out = [];
    foreach ($map as $suffix => $key) {
        $val = trim((string) ($contact[$key] ?? ''));
        if ($suffix === 'StateProvince' && $val === '') {
            $val = trim((string) ($contact['city'] ?? ''));
            if ($val === '') {
                $val = 'NA';
            }
        }
        if ($suffix === 'Phone') {
            $val = hs_namecheap_format_phone($val, (string) ($contact['country'] ?? 'US'));
        }
        if ($suffix === 'Country') {
            $val = strtoupper($val === 'UK' ? 'GB' : $val);
        }
        $out[$prefix . $suffix] = $val;
    }
    return $out;
}

/** @return list<string> */
function hs_namecheap_nameservers(): array
{
    return hs_registry_nameservers();
}

/**
 * @param array<string, string> $contact
 * @param array{premium?:bool,premium_registration_usd?:float,eap_usd?:float}|null $premiumFees
 *        Pass exact fees from namecheap.domains.check for premium names.
 */
function hs_namecheap_register_domain(string $domain, array $contact, ?array $premiumFees = null): array
{
    require_once dirname(__DIR__) . '/domain-store.php';
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }

    // Live check for premium fees if not supplied
    if ($premiumFees === null && hs_namecheap_configured()) {
        $chk = hs_namecheap_check_domains([$p['full']]);
        if (!empty($chk['ok']) && !empty($chk['results'][0])) {
            $row = $chk['results'][0];
            if (!empty($row['premium'])) {
                $premiumFees = [
                    'premium' => true,
                    'premium_registration_usd' => (float) ($row['premium_registration_usd'] ?? $row['wholesale_usd'] ?? 0),
                    'eap_usd' => (float) ($row['eap_usd'] ?? 0),
                ];
            }
        }
    }

    $params = [
        'DomainName' => $p['full'],
        'Years' => '1',
        'AddFreeWhoisguard' => 'yes',
        'WGEnabled' => 'no',
    ];

    if (!empty($premiumFees['premium'])) {
        $regPrice = (float) ($premiumFees['premium_registration_usd'] ?? 0);
        $eap = (float) ($premiumFees['eap_usd'] ?? 0);
        if ($regPrice <= 0) {
            return ['ok' => false, 'error' => 'premium_price_missing'];
        }
        // Namecheap requires exact values from domains.check
        $params['IsPremiumDomain'] = 'true';
        $params['PremiumPrice'] = (string) $regPrice;
        $params['EapFee'] = (string) max(0, $eap);
    }

    foreach (['Registrant', 'Tech', 'Admin', 'AuxBilling'] as $role) {
        $params += hs_namecheap_contact_params($contact, $role);
    }

    $ns = hs_namecheap_nameservers();
    if ($ns !== []) {
        $params['Nameservers'] = implode(',', $ns);
    }

    $res = hs_namecheap_call('namecheap.domains.create', $params);
    if (!$res['ok']) {
        // Retry with alternate premium param names used by some API versions
        $err = (string) ($res['error'] ?? '');
        if (!empty($premiumFees['premium']) && (str_contains(strtolower($err), 'premium') || str_contains(strtolower($err), 'eap'))) {
            $regPrice = (float) ($premiumFees['premium_registration_usd'] ?? 0);
            $eap = (float) ($premiumFees['eap_usd'] ?? 0);
            unset($params['IsPremiumDomain'], $params['PremiumPrice'], $params['EapFee']);
            $params['PremiumRegistrationPrice'] = (string) $regPrice;
            $params['EapFee'] = (string) max(0, $eap);
            $res = hs_namecheap_call('namecheap.domains.create', $params);
        }
    }
    if (!$res['ok']) {
        return [
            'ok' => false,
            'error' => (string) ($res['error'] ?? 'register_failed'),
            'errors' => $res['errors'] ?? [],
            'premium' => !empty($premiumFees['premium']),
        ];
    }

    $registered = false;
    $charged = null;
    if (isset($res['xml']->CommandResponse->DomainCreateResult)) {
        $node = $res['xml']->CommandResponse->DomainCreateResult;
        $registered = strtolower((string) ($node['Registered'] ?? 'true')) === 'true';
        if (isset($node['ChargedAmount'])) {
            $charged = (float) $node['ChargedAmount'];
        }
    }

    if (!$registered) {
        return ['ok' => false, 'error' => 'not_registered', 'premium' => !empty($premiumFees['premium'])];
    }

    if ($ns !== [] && count($ns) >= 2) {
        hs_namecheap_set_nameservers($p['full'], $ns);
    }

    return [
        'ok' => true,
        'domain' => $p['full'],
        'charged' => $charged,
        'source' => 'namecheap',
        'premium' => !empty($premiumFees['premium']),
    ];
}

/** @param list<string> $nameservers */
function hs_namecheap_set_nameservers(string $domain, array $nameservers): array
{
    require_once dirname(__DIR__) . '/domain-store.php';
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $nameservers = array_values(array_filter(array_map('trim', $nameservers)));
    if (count($nameservers) < 2) {
        return ['ok' => false, 'error' => 'nameservers'];
    }

    return hs_namecheap_call('namecheap.domains.dns.setCustom', [
        'SLD' => $p['sld'],
        'TLD' => $p['tld'],
        'Nameservers' => implode(',', array_slice($nameservers, 0, 5)),
    ]);
}

/** @return ?array<string, string> */
function hs_namecheap_registrant_from_user(string $userId, array $user): ?array
{
    require_once dirname(__DIR__) . '/user-settings.php';
    $settings = hs_user_settings_get($userId);
    $r = is_array($settings['registrant'] ?? null) ? $settings['registrant'] : [];
    if ($r === [] && is_array($user['profile'] ?? null)) {
        $r = $user['profile'];
    }

    $first = trim((string) ($r['first_name'] ?? ''));
    $last = trim((string) ($r['last_name'] ?? ''));
    $email = strtolower(trim((string) ($user['email'] ?? '')));
    $phone = trim((string) ($r['phone'] ?? ''));
    $address = trim((string) ($r['address'] ?? ''));
    $city = trim((string) ($r['city'] ?? ''));
    $postal = trim((string) ($r['postal'] ?? ''));
    $country = strtoupper(trim((string) ($r['country'] ?? '')));

    if ($first === '' || $last === '' || $email === '' || $phone === '' || $address === '' || $city === '' || $postal === '' || $country === '') {
        return null;
    }

    return [
        'first_name' => $first,
        'last_name' => $last,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'postal' => $postal,
        'country' => $country,
        'state' => trim((string) ($r['state'] ?? '')),
    ];
}

/** @return array{ok:bool,registered?:bool,skipped?:bool,error?:string,source?:string} */
function hs_namecheap_register_for_user(string $userId, string $domain, array $user): array
{
    require_once dirname(__DIR__) . '/domain-store.php';
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }

    if (hs_namecheap_is_registry_only_tld($p['tld'])) {
        return ['ok' => false, 'error' => 'tld_not_registrable'];
    }

    if (!hs_namecheap_configured()) {
        return ['ok' => true, 'skipped' => true, 'source' => 'local'];
    }

    $contact = hs_namecheap_registrant_from_user($userId, $user);
    if ($contact === null) {
        return ['ok' => false, 'error' => 'contact_incomplete'];
    }

    // Pass premium fees from check so create includes EAP/premium price
    return hs_namecheap_register_domain($domain, $contact, null);
}

/**
 * Namecheap register (1st year) USD — reseller sheet Jul 2026.
 * @see https://www.namecheap.com/domains/
 */
function hs_namecheap_manual_wholesale_usd(): array
{
    return [
        'com' => 11.28,
        'net' => 12.48,
        'org' => 8.48,
        'io' => 34.98,
        'co' => 19.98,
        'ai' => 89.98,
        'co.uk' => 6.98,
        'uk' => 6.98,
        'ca' => 11.98,
        'dev' => 10.98,
        'me' => 10.98,
        'us' => 5.98,
        'app' => 10.98,
        'in' => 9.98,
        'eu' => 6.98,
        'nl' => 7.48,
        'xyz' => 2.00,
        'info' => 3.98,
        'online' => 0.98,
        'store' => 0.98,
        'shop' => 0.98,
        'website' => 0.98,
        'space' => 0.98,
        'club' => 4.98,
        'live' => 2.98,
        'world' => 2.98,
        'life' => 2.98,
        'fun' => 3.98,
        'biz' => 8.48,
        'tech' => 9.99,
        'host' => 9.99,
        'vip' => 4.98,
        'pw' => 6.98,
        'pro' => 3.48,
        'icu' => 2.79,
        'design' => 3.98,
        'art' => 3.98,
        'best' => 2.28,
        'de' => 6.98,
        'lt' => 11.99,
        'pl' => 10.98,
        'se' => 12.98,
    ];
}

/** TLD display order in panel (after featured com/net/org). */
function hs_domain_panel_tld_order(): array
{
    return [
        'eu', 'uk', 'co.uk', 'de', 'nl', 'lt',
        'io', 'dev', 'app', 'tech', 'ai', 'co',
        'online', 'store', 'shop', 'xyz', 'me', 'us', 'ca', 'in',
        'info', 'biz', 'pro', 'club', 'live', 'space', 'website',
    ];
}

/**
 * Markup on Namecheap domain wholesale prices (register/renew).
 * Prefer NC_DOMAIN_MARKUP_PCT; fall back to NC_MARKUP_PCT (legacy).
 * Solaskinner production: 70%.
 */
function hs_namecheap_markup_pct(): float
{
    if (defined('NC_DOMAIN_MARKUP_PCT')) {
        return max(0.0, min(500.0, (float) NC_DOMAIN_MARKUP_PCT));
    }
    if (defined('NC_MARKUP_PCT')) {
        return max(0.0, min(500.0, (float) NC_MARKUP_PCT));
    }

    return 30.0;
}

/**
 * Markup on Namecheap shared hosting wholesale (Stellar-class) monthly rates.
 * Solaskinner production: 70%.
 */
function hs_namecheap_hosting_markup_pct(): float
{
    if (defined('NC_HOSTING_MARKUP_PCT')) {
        return max(0.0, min(500.0, (float) NC_HOSTING_MARKUP_PCT));
    }
    // Do not fall back to domain markup — hosting uses its own rate.
    return 70.0;
}

function hs_namecheap_usd_to_eur(float $usd): float
{
    require_once dirname(__DIR__) . '/currency.php';
    $rates = hs_exchange_rates();
    $usdRate = max(0.001, (float) ($rates['USD'] ?? 0.095));
    $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
    return round(($usd / $usdRate) * $eurRate, 2);
}

function hs_namecheap_tld_prices_cache_file(): string
{
    return HS_DATA_DIR . '/namecheap-tld-prices.json';
}

/** @return array<string, float> TLD => wholesale USD/year */
function hs_namecheap_fetch_register_prices_usd(): array
{
    if (hs_namecheap_skip_live_api()) {
        return [];
    }
    $res = hs_namecheap_call('namecheap.users.getPricing', ['ProductType' => 'DOMAIN']);
    if (!$res['ok'] || !isset($res['xml'])) {
        return [];
    }

    $out = [];
    $root = $res['xml']->CommandResponse->UserGetPricingResult ?? $res['xml']->CommandResponse->ProductType ?? null;
    if ($root === null) {
        return [];
    }

    $productTypes = $root->Name !== null ? [$root] : $root;
    foreach ($productTypes as $productType) {
        if (strtolower((string) ($productType['Name'] ?? $productType->attributes()->Name ?? '')) !== 'domains'
            && strtolower((string) ($productType['Name'] ?? '')) !== 'domain') {
            if (!isset($productType->ProductCategory)) {
                continue;
            }
        }
        foreach ($productType->ProductCategory ?? [] as $category) {
            $catName = strtolower((string) ($category['Name'] ?? ''));
            if ($catName !== '' && $catName !== 'register' && $catName !== 'registration') {
                continue;
            }
            foreach ($category->Product ?? [] as $product) {
                $tld = strtolower(ltrim((string) ($product['Name'] ?? ''), '.'));
                if ($tld === '') {
                    continue;
                }
                $best = null;
                foreach ($product->Price ?? [] as $price) {
                    $duration = (int) ($price['Duration'] ?? 1);
                    $durType = strtoupper((string) ($price['DurationType'] ?? 'YEAR'));
                    if ($duration !== 1 || $durType !== 'YEAR') {
                        continue;
                    }
                    $val = (float) ($price['Price'] ?? $price['YourPrice'] ?? 0);
                    if ($val > 0 && ($best === null || $val < $best)) {
                        $best = $val;
                    }
                }
                if ($best !== null) {
                    $out[$tld] = $best;
                }
            }
        }
    }

    return $out;
}

/** @return array<string, float> TLD => wholesale renew USD/year */
function hs_namecheap_fetch_renew_prices_usd(): array
{
    $res = hs_namecheap_call('namecheap.users.getPricing', ['ProductType' => 'DOMAIN']);
    if (!$res['ok'] || !isset($res['xml'])) {
        return [];
    }

    $out = [];
    $root = $res['xml']->CommandResponse->UserGetPricingResult ?? $res['xml']->CommandResponse->ProductType ?? null;
    if ($root === null) {
        return [];
    }

    $productTypes = $root->Name !== null ? [$root] : $root;
    foreach ($productTypes as $productType) {
        if (strtolower((string) ($productType['Name'] ?? $productType->attributes()->Name ?? '')) !== 'domains'
            && strtolower((string) ($productType['Name'] ?? '')) !== 'domain') {
            if (!isset($productType->ProductCategory)) {
                continue;
            }
        }
        foreach ($productType->ProductCategory ?? [] as $category) {
            $catName = strtolower((string) ($category['Name'] ?? ''));
            if ($catName !== '' && $catName !== 'renew' && $catName !== 'renewal') {
                continue;
            }
            foreach ($category->Product ?? [] as $product) {
                $tld = strtolower(ltrim((string) ($product['Name'] ?? ''), '.'));
                if ($tld === '') {
                    continue;
                }
                $best = null;
                foreach ($product->Price ?? [] as $price) {
                    $duration = (int) ($price['Duration'] ?? 1);
                    $durType = strtoupper((string) ($price['DurationType'] ?? 'YEAR'));
                    if ($duration !== 1 || $durType !== 'YEAR') {
                        continue;
                    }
                    $val = (float) ($price['Price'] ?? $price['YourPrice'] ?? 0);
                    if ($val > 0 && ($best === null || $val < $best)) {
                        $best = $val;
                    }
                }
                if ($best !== null) {
                    $out[$tld] = $best;
                }
            }
        }
    }

    return $out;
}

/** @return array<string, float> TLD => retail EUR/year (markup applied) */
function hs_namecheap_tld_prices_eur(bool $forceRefresh = false): array
{
    if (!hs_namecheap_configured()) {
        return [];
    }

    $cacheFile = hs_namecheap_tld_prices_cache_file();
    $ttl = 43200;
    $cached = null;
    $rawCache = [];
    if (is_readable($cacheFile)) {
        $rawCache = json_decode((string) file_get_contents($cacheFile), true);
        if (!is_array($rawCache)) {
            $rawCache = [];
        }
        if (is_array($rawCache['prices_eur'] ?? null)) {
            $cached = $rawCache['prices_eur'];
        }
    }

    $manualVersion = substr(md5(json_encode([
        hs_namecheap_manual_wholesale_usd(),
        hs_namecheap_registry_only_tlds(),
    ])), 0, 8);
    $cacheFresh = is_array($cached)
        && ($rawCache['fetched_at'] ?? 0) > time() - $ttl
        && ($rawCache['manual_version'] ?? '') === $manualVersion;

    if (!$forceRefresh && $cacheFresh) {
        $sellableFlip = array_flip(hs_namecheap_sellable_tlds());
        $filtered = [];
        foreach ($cached as $tld => $price) {
            if (isset($sellableFlip[$tld]) && (float) $price > 0) {
                $filtered[$tld] = $price;
            }
        }
        // Never fall back to unfiltered cache (would show non-registrable zones)
        return $filtered;
    }

    if (!$forceRefresh && is_array($cached) && ($rawCache['manual_version'] ?? '') === $manualVersion) {
        $sellableFlip = array_flip(hs_namecheap_sellable_tlds());
        $filtered = [];
        foreach ($cached as $tld => $price) {
            if (isset($sellableFlip[$tld]) && (float) $price > 0) {
                $filtered[$tld] = $price;
            }
        }

        return $filtered;
    }

    if (!$forceRefresh && hs_namecheap_skip_live_api()) {
        return is_array($cached) ? $cached : [];
    }

    $wholesale = hs_namecheap_manual_wholesale_usd();
    $apiWholesale = hs_namecheap_fetch_register_prices_usd();
    $sellable = hs_namecheap_sellable_tlds($forceRefresh);
    if ($sellable === []) {
        return [];
    }

    $markup = 1 + (hs_namecheap_markup_pct() / 100);
    $retail = [];
    foreach ($sellable as $tld) {
        $usd = 0.0;
        if (isset($apiWholesale[$tld]) && (float) $apiWholesale[$tld] > 0) {
            $usd = (float) $apiWholesale[$tld];
        } elseif (isset($wholesale[$tld]) && (float) $wholesale[$tld] > 0) {
            $usd = (float) $wholesale[$tld];
        }
        if ($usd <= 0) {
            continue;
        }
        $eur = hs_namecheap_usd_to_eur($usd);
        $retail[$tld] = round($eur * $markup, 2);
    }

    if ($retail !== [] && is_dir(HS_DATA_DIR)) {
        @file_put_contents($cacheFile, json_encode([
            'fetched_at' => time(),
            'manual_version' => $manualVersion,
            'markup_pct' => hs_namecheap_markup_pct(),
            'wholesale_usd' => array_intersect_key($wholesale, $retail),
            'prices_eur' => $retail,
        ], JSON_UNESCAPED_UNICODE));
    }

    return $retail;
}

function hs_namecheap_outbound_ip(): string
{
    static $cached = null;
    if (is_string($cached)) {
        return $cached;
    }
    $cached = '';
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'Solaskinner-Hosting/1.0']]);
    $raw = @file_get_contents('https://api.ipify.org', false, $ctx);
    if (is_string($raw) && filter_var(trim($raw), FILTER_VALIDATE_IP)) {
        $cached = trim($raw);
    }
    return $cached;
}

/** @return array{configured:bool,api_user:string,client_ip:string,outbound_ip:string,server_ip:string,ip_match:bool,sandbox:bool,nameservers:list<string>,display_nameservers:list<string>,markup_pct:float} */
function hs_namecheap_status(): array
{
    $clientIp = defined('NC_CLIENT_IP') ? (string) NC_CLIENT_IP : '';
    $outboundIp = hs_namecheap_outbound_ip();
    $serverIp = defined('HS_SERVER_IP') ? (string) HS_SERVER_IP : '';
    return [
        'configured' => hs_namecheap_configured(),
        'api_user' => defined('NC_API_USER') ? (string) NC_API_USER : '',
        'client_ip' => $clientIp,
        'outbound_ip' => $outboundIp,
        'server_ip' => $serverIp,
        'ip_match' => $clientIp !== '' && $outboundIp !== '' && $clientIp === $outboundIp,
        'sandbox' => defined('NC_SANDBOX') && NC_SANDBOX,
        'nameservers' => hs_registry_nameservers(),
        'display_nameservers' => hs_display_nameservers(),
        'markup_pct' => hs_namecheap_markup_pct(),
        'circuit' => hs_namecheap_circuit_info(),
    ];
}

/** @return array{ok:bool,error?:string,detail?:string,balance?:float,prices?:int} */
function hs_namecheap_test_connection(bool $refreshPrices = false, bool $resetCircuit = false): array
{
    if (!hs_namecheap_configured()) {
        return ['ok' => false, 'error' => 'not_configured', 'detail' => 'Set NC_API_USER, NC_API_KEY, NC_CLIENT_IP in config.local.php'];
    }

    if ($resetCircuit) {
        hs_namecheap_circuit_reset();
    }

    $balance = null;
    $balRes = hs_namecheap_call('namecheap.users.getBalances');
    if ($balRes['ok'] && isset($balRes['xml']->CommandResponse->UserGetBalancesResult)) {
        foreach ($balRes['xml']->CommandResponse->UserGetBalancesResult->Balance ?? [] as $node) {
            if (strtoupper((string) ($node['Currency'] ?? '')) === 'USD') {
                $balance = (float) ($node['AvailableBalance'] ?? $node['Balance'] ?? 0);
                break;
            }
        }
        if ($balance === null) {
            $node = $balRes['xml']->CommandResponse->UserGetBalancesResult;
            $balance = (float) ($node['AvailableBalance'] ?? $node['Balance'] ?? 0);
        }
    }

    $check = hs_namecheap_check_domains(['solaskinner-integration-test-' . bin2hex(random_bytes(3)) . '.com']);
    if (!$check['ok']) {
        $detail = (string) ($check['detail'] ?? $check['error'] ?? 'api_error');
        if (!empty($balRes['errors'][0])) {
            $detail = (string) $balRes['errors'][0];
        } elseif (!empty($balRes['detail'])) {
            $detail = (string) $balRes['detail'];
        } elseif (!empty($balRes['error']) && ($balRes['ok'] ?? false) === false) {
            $detail = (string) $balRes['error'];
        }
        return ['ok' => false, 'error' => 'api_failed', 'detail' => $detail];
    }

    $prices = hs_namecheap_tld_prices_eur($refreshPrices);
    return [
        'ok' => true,
        'balance' => $balance,
        'prices' => count($prices),
        'detail' => 'API OK · domain check · ' . count($prices) . ' TLD prices',
    ];
}