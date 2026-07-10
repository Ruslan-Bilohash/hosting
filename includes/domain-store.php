<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/domain-lookup.php';

/** @return array<string, float> TLD => price EUR/year */
function hs_domain_tld_prices(): array
{
    return [
        'lt' => 9.99,
        'com' => 12.99,
        'net' => 11.99,
        'org' => 10.99,
        'eu' => 8.99,
        'uk' => 9.49,
        'no' => 14.99,
    ];
}

function hs_domain_normalize(string $input): ?string
{
    $d = strtolower(trim($input));
    $d = preg_replace('/^https?:\/\//', '', $d) ?? $d;
    $d = preg_replace('/\/.*$/', '', $d) ?? $d;
    if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $d)) {
        return null;
    }
    return $d;
}

function hs_domain_multi_tlds(): array
{
    return ['co.uk', 'org.uk', 'ac.uk', 'gov.uk', 'com.au', 'net.au'];
}

function hs_domain_parse(string $domain): ?array
{
    $domain = hs_domain_normalize($domain);
    if ($domain === null) {
        return null;
    }
    foreach (hs_domain_multi_tlds() as $mt) {
        $suffix = '.' . $mt;
        if (str_ends_with($domain, $suffix)) {
            $sld = substr($domain, 0, -strlen($suffix));
            if ($sld === '' || !preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $sld)) {
                return null;
            }
            return ['full' => $domain, 'sld' => $sld, 'tld' => $mt];
        }
    }
    $parts = explode('.', $domain);
    if (count($parts) < 2) {
        return null;
    }
    $tld = $parts[count($parts) - 1];
    $sld = implode('.', array_slice($parts, 0, -1));
    return ['full' => $domain, 'sld' => $sld, 'tld' => $tld];
}

function hs_domain_price(string $domain): ?float
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return null;
    }
    $prices = hs_domain_tld_prices();
    return $prices[$p['tld']] ?? 15.99;
}

function hs_domain_taken_in_cms(string $domain): bool
{
    foreach (hs_users() as $u) {
        $settings = hs_user_settings_get((string) ($u['id'] ?? ''));
        if (strtolower((string) ($settings['primary_domain'] ?? '')) === $domain) {
            return true;
        }
        foreach ($settings['extra_domains'] ?? [] as $ed) {
            if (strtolower((string) $ed) === $domain) {
                return true;
            }
        }
    }
    return false;
}

/** Real RDAP/WHOIS availability check */
function hs_domain_check_availability(string $domain): array
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    $full = $p['full'];
    $price = hs_domain_price($full);

    if (hs_domain_taken_in_cms($full)) {
        return [
            'ok' => true,
            'domain' => $full,
            'available' => false,
            'price' => $price,
            'source' => 'cms',
        ];
    }

    $lookup = hs_domain_registry_lookup($full, $p['tld']);
    if (!$lookup['ok']) {
        return ['ok' => false, 'error' => $lookup['error'] ?? 'lookup_failed'];
    }

    return [
        'ok' => true,
        'domain' => $full,
        'available' => (bool) ($lookup['available'] ?? false),
        'price' => $price,
        'source' => (string) ($lookup['source'] ?? 'registry'),
    ];
}

function hs_domain_bind_to_user(string $userId, string $domain, bool $asPrimary = true, bool $force = false): bool
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return false;
    }
    if (!$force) {
        $check = hs_domain_check_availability($p['full']);
        if (!$check['ok'] || empty($check['available'])) {
            return false;
        }
    }
    $patch = [];
    if ($asPrimary) {
        $patch['primary_domain'] = $p['full'];
    } else {
        $settings = hs_user_settings_get($userId);
        $extra = is_array($settings['extra_domains'] ?? null) ? $settings['extra_domains'] : [];
        if (!in_array($p['full'], $extra, true)) {
            $extra[] = $p['full'];
        }
        $patch['extra_domains'] = $extra;
    }
    $patch['active_domain'] = $p['full'];
    return hs_user_settings_save($userId, $patch);
}

function hs_domain_normalize_sld(string $input): ?string
{
    $s = strtolower(trim($input));
    if (str_contains($s, '.')) {
        $p = hs_domain_parse($s);
        if ($p !== null) {
            $s = $p['sld'];
        }
    }
    if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $s)) {
        return null;
    }
    return $s;
}

/** @param list<string> $tlds */
function hs_domain_check_batch(string $sld, array $tlds): array
{
    $normalized = hs_domain_normalize_sld($sld);
    if ($normalized === null) {
        return ['ok' => false, 'error' => 'invalid_sld'];
    }
    $prices = hs_domain_tld_prices();
    $results = [];
    foreach ($tlds as $tld) {
        $tld = strtolower(trim((string) $tld));
        if ($tld === '' || !isset($prices[$tld])) {
            continue;
        }
        $check = hs_domain_check_availability($normalized . '.' . $tld);
        if (!$check['ok']) {
            continue;
        }
        $results[] = [
            'domain' => (string) $check['domain'],
            'tld' => $tld,
            'available' => (bool) ($check['available'] ?? false),
            'price' => $check['price'],
            'source' => (string) ($check['source'] ?? 'registry'),
        ];
    }
    if ($results === []) {
        return ['ok' => false, 'error' => 'no_tlds'];
    }
    return ['ok' => true, 'sld' => $normalized, 'results' => $results];
}

function hs_domain_format_price(float $eur, string $lang): string
{
    require_once __DIR__ . '/currency.php';
    $suffix = match ($lang) {
        'uk' => '/рік',
        'no' => '/år',
        default => '/year',
    };
    return hs_format_eur_price($eur, $lang, $suffix);
}