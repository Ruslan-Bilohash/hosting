<?php
declare(strict_types=1);

/** @return array<string, string> TLD => RDAP base URL (domain appended) */
function hs_domain_rdap_bases(): array
{
    return [
        'com' => 'https://rdap.verisign.com/com/v1/domain/',
        'net' => 'https://rdap.verisign.com/net/v1/domain/',
        'org' => 'https://rdap.publicinterestregistry.org/rdap/domain/',
        'eu' => 'https://rdap.eurid.eu/domain/',
        'no' => 'https://rdap.norid.no/domain/',
        'uk' => 'https://rdap.nominet.uk/uk/domain/',
        'co.uk' => 'https://rdap.nominet.uk/uk/domain/',
        'org.uk' => 'https://rdap.nominet.uk/uk/domain/',
    ];
}

/** @return array<string, array{server:string,available:list<string>,taken:list<string>}> */
function hs_domain_whois_config(): array
{
    return [
        'lt' => [
            'server' => 'whois.domreg.lt',
            'available' => ['status: available', 'registered: no', 'no entries found'],
            'taken' => ['status: registered', 'registered: yes'],
        ],
        'com' => [
            'server' => 'whois.verisign-grs.com',
            'available' => ['no match for'],
            'taken' => ['domain name:'],
        ],
        'net' => [
            'server' => 'whois.verisign-grs.com',
            'available' => ['no match for'],
            'taken' => ['domain name:'],
        ],
        'org' => [
            'server' => 'whois.pir.org',
            'available' => ['not found'],
            'taken' => ['domain name:'],
        ],
        'eu' => [
            'server' => 'whois.eu',
            'available' => ['status: available', 'not found'],
            'taken' => ['domain:'],
        ],
        'no' => [
            'server' => 'whois.norid.no',
            'available' => ['% no match', 'no match'],
            'taken' => ['domain name:'],
        ],
        'uk' => [
            'server' => 'whois.nic.uk',
            'available' => ['no match for'],
            'taken' => ['domain name:'],
        ],
        'co.uk' => [
            'server' => 'whois.nic.uk',
            'available' => ['no match for'],
            'taken' => ['domain name:'],
        ],
        'org.uk' => [
            'server' => 'whois.nic.uk',
            'available' => ['no match for'],
            'taken' => ['domain name:'],
        ],
    ];
}

function hs_domain_http_get(string $url, int $timeout = 15): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'BILOHASH-Hosting-CMS/1.0 (+https://bilohash.com/hosting)',
            CURLOPT_HTTPHEADER => ['Accept: application/rdap+json, application/json'],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => $body !== false ? (string) $body : ''];
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'user_agent' => 'BILOHASH-Hosting-CMS/1.0',
            'header' => "Accept: application/rdap+json\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', (string) $http_response_header[0], $m)) {
        $code = (int) $m[0];
    }
    return ['code' => $code, 'body' => $body !== false ? (string) $body : ''];
}

/** null = unsupported / inconclusive, true = available, false = taken */
function hs_domain_rdap_available(string $domain, string $tld): ?bool
{
    $bases = hs_domain_rdap_bases();
    if (!isset($bases[$tld])) {
        return null;
    }
    $url = $bases[$tld] . rawurlencode(strtolower($domain));
    $res = hs_domain_http_get($url);
    if ($res['code'] === 404) {
        return true;
    }
    if ($res['code'] === 200) {
        return false;
    }
    return null;
}

function hs_domain_socket_query(string $host, int $port, string $payload): ?string
{
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, 12);
    if ($fp === false) {
        return null;
    }
    stream_set_timeout($fp, 12);
    fwrite($fp, $payload);
    $out = '';
    while (!feof($fp)) {
        $chunk = fgets($fp, 4096);
        if ($chunk === false) {
            break;
        }
        $out .= $chunk;
        $meta = stream_get_meta_data($fp);
        if (!empty($meta['timed_out'])) {
            break;
        }
    }
    fclose($fp);
    return $out !== '' ? $out : null;
}

function hs_domain_whois_query(string $domain, string $server): ?string
{
    return hs_domain_socket_query($server, 43, $domain . "\r\n");
}

/** .lt registry DAS — das.domreg.lt:4343 */
function hs_domain_lt_das_available(string $domain): ?bool
{
    $raw = hs_domain_socket_query('das.domreg.lt', 4343, 'get 1.0 ' . strtolower($domain) . "\n");
    if ($raw === null) {
        return null;
    }
    if (preg_match('/Status:\s*(\S+)/i', $raw, $m)) {
        $status = strtolower((string) $m[1]);
        if ($status === 'available') {
            return true;
        }
        if (in_array($status, ['registered', 'blocked', 'reserved', 'quarantine', 'pendingcreate', 'pendingdelete'], true)) {
            return false;
        }
    }
    return null;
}

/** @param list<string> $needles */
function hs_domain_text_has(string $haystack, array $needles): bool
{
    $lower = strtolower($haystack);
    foreach ($needles as $n) {
        if (strpos($lower, strtolower($n)) !== false) {
            return true;
        }
    }
    return false;
}

function hs_domain_whois_available(string $domain, string $tld): ?bool
{
    $cfg = hs_domain_whois_config();
    if (!isset($cfg[$tld])) {
        return null;
    }
    $conf = $cfg[$tld];
    $raw = hs_domain_whois_query($domain, $conf['server']);
    if ($raw === null || trim($raw) === '') {
        return null;
    }
    if (hs_domain_text_has($raw, $conf['available'])) {
        return true;
    }
    if (hs_domain_text_has($raw, $conf['taken'])) {
        return false;
    }
    return null;
}

/**
 * Real registry lookup (RDAP, then WHOIS).
 *
 * @return array{ok:bool,available?:bool,source?:string,error?:string}
 */
function hs_domain_registry_lookup(string $domain, string $tld): array
{
    if ($tld === 'lt') {
        $das = hs_domain_lt_das_available($domain);
        if ($das !== null) {
            return ['ok' => true, 'available' => $das, 'source' => 'das'];
        }
    }
    $rdap = hs_domain_rdap_available($domain, $tld);
    if ($rdap !== null) {
        return ['ok' => true, 'available' => $rdap, 'source' => 'rdap'];
    }
    $whois = hs_domain_whois_available($domain, $tld);
    if ($whois !== null) {
        return ['ok' => true, 'available' => $whois, 'source' => 'whois'];
    }
    return ['ok' => false, 'error' => 'lookup_failed'];
}