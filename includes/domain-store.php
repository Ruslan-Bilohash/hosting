<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/user-settings.php';
require_once __DIR__ . '/domain-lookup.php';
require_once __DIR__ . '/providers/namecheap-api.php';

/** Featured TLDs — Namecheap wholesale USD + flat +30% retail (no promo tiers). */
function hs_domain_featured_tld_catalog(): array
{
    return [
        'com' => [
            'tagline' => 'The King of domains',
            'wholesale_usd' => 11.28,
            'renew_usd' => 18.48,
            'promo_pct' => 0,
        ],
        'net' => [
            'tagline' => 'A true Internet original',
            'wholesale_usd' => 12.48,
            'renew_usd' => 18.58,
            'promo_pct' => 0,
        ],
        'org' => [
            'tagline' => 'The domain you can trust',
            'wholesale_usd' => 8.48,
            'renew_usd' => 18.98,
            'promo_pct' => 0,
        ],
    ];
}

/** @return array<string, float> TLD => retail EUR/year */
function hs_domain_tld_prices_fallback(): array
{
    require_once __DIR__ . '/providers/namecheap-api.php';
    $markup = 1 + (hs_namecheap_markup_pct() / 100);
    $out = [];
    foreach (hs_namecheap_manual_wholesale_usd() as $tld => $usd) {
        if ($usd <= 0) {
            continue;
        }
        $out[$tld] = round(hs_namecheap_usd_to_eur($usd) * $markup, 2);
    }

    return $out;
}

/** Retail EUR/year for a TLD from wholesale USD base. */
function hs_domain_retail_eur_from_usd(float $wholesaleUsd): float
{
    require_once __DIR__ . '/providers/namecheap-api.php';
    $markup = 1 + (hs_namecheap_markup_pct() / 100);

    return round(hs_namecheap_usd_to_eur($wholesaleUsd) * $markup, 2);
}

/** @param array<string, float> $prices */
function hs_domain_filter_registrable_prices(array $prices): array
{
    require_once __DIR__ . '/providers/namecheap-api.php';
    $out = [];
    foreach ($prices as $tld => $price) {
        if (hs_domain_tld_registrable((string) $tld)) {
            $out[$tld] = $price;
        }
    }

    return $out;
}

/** @return array<string, float> TLD => retail EUR/year (registrable zones only) */
function hs_domain_tld_prices(): array
{
    require_once __DIR__ . '/providers/hostinger-domains.php';
    if (hs_domain_registration_provider() === 'hostinger') {
        $hi = hs_hostinger_tld_prices_eur(false);
        if ($hi !== []) {
            require_once __DIR__ . '/providers/namecheap-api.php';
            $markup = 1 + (hs_namecheap_markup_pct() / 100);
            $out = [];
            foreach ($hi as $tld => $eur) {
                if ($eur > 0) {
                    $out[$tld] = round($eur * $markup, 2);
                }
            }

            return hs_domain_filter_registrable_prices($out);
        }
    }

    require_once __DIR__ . '/providers/namecheap-api.php';
    $nc = hs_namecheap_tld_prices_eur();
    if ($nc !== []) {
        return hs_domain_filter_registrable_prices($nc);
    }

    return hs_domain_filter_registrable_prices(hs_domain_tld_prices_fallback());
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
    // Prefer live premium retail from last Namecheap check (already +markup)
    $cached = hs_domain_check_cache_get($p['full']);
    if (is_array($cached) && !empty($cached['ok'])) {
        if (!empty($cached['premium']) && isset($cached['price']) && (float) $cached['price'] > 0) {
            return round((float) $cached['price'], 2);
        }
        if (isset($cached['price_eur']) && (float) $cached['price_eur'] > 0 && !empty($cached['premium'])) {
            return round((float) $cached['price_eur'], 2);
        }
    }
    $prices = hs_domain_tld_prices();

    return $prices[$p['tld']] ?? 15.99;
}

/** True when last check marked domain as premium aftermarket name. */
function hs_domain_is_premium(string $domain): bool
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return false;
    }
    $cached = hs_domain_check_cache_get($p['full']);

    return is_array($cached) && !empty($cached['premium']);
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

function hs_domain_check_cache_dir(): string
{
    return HS_DATA_DIR . '/cache/domain-check';
}

function hs_domain_check_cache_get(string $domain): ?array
{
    $domain = strtolower(trim($domain));
    if ($domain === '') {
        return null;
    }
    $file = hs_domain_check_cache_dir() . '/' . md5($domain) . '.json';
    if (!is_readable($file)) {
        return null;
    }
    $raw = json_decode((string) file_get_contents($file), true);
    if (!is_array($raw) || ($raw['domain'] ?? '') !== $domain) {
        return null;
    }
    if ((int) ($raw['cached_at'] ?? 0) < time() - 120) {
        return null;
    }
    $result = $raw['result'] ?? null;
    return is_array($result) ? $result : null;
}

/** @param array<string, mixed> $result */
function hs_domain_check_cache_set(string $domain, array $result): void
{
    $domain = strtolower(trim($domain));
    if ($domain === '' || empty($result['ok'])) {
        return;
    }
    $dir = hs_domain_check_cache_dir();
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
        return;
    }
    @file_put_contents(
        $dir . '/' . md5($domain) . '.json',
        json_encode(['domain' => $domain, 'cached_at' => time(), 'result' => $result], JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

/**
 * Availability + retail price (EUR).
 * Uses Namecheap domains.check when configured so premium names get live wholesale
 * price + our markup (default 30%).
 */
function hs_domain_check_availability(string $domain): array
{
    $p = hs_domain_parse($domain);
    if ($p === null) {
        return ['ok' => false, 'error' => 'invalid'];
    }
    if (!hs_domain_tld_registrable($p['tld'])) {
        return ['ok' => false, 'error' => 'tld_not_registrable'];
    }
    $full = $p['full'];
    $cached = hs_domain_check_cache_get($full);
    if (is_array($cached)) {
        return $cached;
    }
    $catalogPrice = (float) (hs_domain_tld_prices()[$p['tld']] ?? 15.99);

    if (hs_domain_taken_in_cms($full)) {
        $result = [
            'ok' => true,
            'domain' => $full,
            'available' => false,
            'price' => $catalogPrice,
            'premium' => false,
            'source' => 'cms',
        ];
        hs_domain_check_cache_set($full, $result);
        return $result;
    }

    // Prefer Namecheap live check (premium detection + price)
    require_once __DIR__ . '/providers/namecheap-api.php';
    if (hs_namecheap_configured() && !hs_namecheap_circuit_blocks_request()) {
        $nc = hs_namecheap_check_domains([$full]);
        if (!empty($nc['ok']) && !empty($nc['results'][0])) {
            $row = $nc['results'][0];
            $premium = !empty($row['premium']);
            $priceEur = $premium && isset($row['price_eur']) && (float) $row['price_eur'] > 0
                ? (float) $row['price_eur']
                : $catalogPrice;
            $result = [
                'ok' => true,
                'domain' => $full,
                'available' => !empty($row['available']),
                'price' => $priceEur,
                'premium' => $premium,
                'wholesale_usd' => $row['wholesale_usd'] ?? null,
                'eap_usd' => $row['eap_usd'] ?? 0,
                'premium_registration_usd' => $row['premium_registration_usd'] ?? null,
                'source' => 'namecheap',
            ];
            hs_domain_check_cache_set($full, $result);

            return $result;
        }
    }

    require_once __DIR__ . '/providers/hostinger-domains.php';
    if (hs_domain_registration_provider() === 'hostinger' && hs_hostinger_configured()) {
        $hi = hs_hostinger_check_domain($full);
        if ($hi['ok']) {
            $result = [
                'ok' => true,
                'domain' => $full,
                'available' => !empty($hi['available']),
                'price' => $catalogPrice,
                'premium' => !empty($hi['premium']),
                'source' => 'hostinger',
            ];
            hs_domain_check_cache_set($full, $result);

            return $result;
        }
    }

    $lookup = hs_domain_registry_lookup($full, $p['tld']);
    if (!$lookup['ok']) {
        return ['ok' => false, 'error' => $lookup['error'] ?? 'lookup_failed'];
    }

    $result = [
        'ok' => true,
        'domain' => $full,
        'available' => (bool) ($lookup['available'] ?? false),
        'price' => $catalogPrice,
        'premium' => false,
        'source' => (string) ($lookup['source'] ?? 'registry'),
    ];
    hs_domain_check_cache_set($full, $result);
    return $result;
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
    if (!hs_user_settings_save($userId, $patch)) {
        return false;
    }
    $user = hs_user_by_id($userId);
    if ($user !== null) {
        require_once __DIR__ . '/domain-workspace.php';
        // Always domain-named folder: public_html/{user}/{domain}/
        $folderKey = hs_domain_folder_name($p['full']);
        if ($folderKey !== '') {
            hs_domain_roots_save($userId, $p['full'], $folderKey);
        }
        hs_domain_auto_bind_site($user, $p['full'], false);
        $folder = hs_domain_docroot_path($user, $p['full']);
        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }
        hs_domain_seed_docroot_files($user, $p['full'], $folder);
    }

    return true;
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
    $items = [];
    foreach ($tlds as $tld) {
        $tld = strtolower(trim((string) $tld));
        if ($tld === '' || !isset($prices[$tld])) {
            continue;
        }
        $full = $normalized . '.' . $tld;
        $items[] = ['domain' => $full, 'tld' => $tld, 'price' => (float) $prices[$tld]];
    }
    if ($items === []) {
        return ['ok' => false, 'error' => 'no_tlds'];
    }

    $results = [];
    $needLive = [];
    foreach ($items as $item) {
        $full = (string) $item['domain'];
        $cached = hs_domain_check_cache_get($full);
        if (is_array($cached) && !empty($cached['ok'])) {
            $results[] = [
                'domain' => (string) ($cached['domain'] ?? $full),
                'tld' => (string) $item['tld'],
                'available' => (bool) ($cached['available'] ?? false),
                'price' => $cached['price'] ?? $item['price'],
                'premium' => !empty($cached['premium']),
                'source' => (string) ($cached['source'] ?? 'registry'),
            ];
            continue;
        }
        if (hs_domain_taken_in_cms($full)) {
            $row = [
                'domain' => $full,
                'tld' => (string) $item['tld'],
                'available' => false,
                'price' => $item['price'],
                'premium' => false,
                'source' => 'cms',
            ];
            hs_domain_check_cache_set($full, ['ok' => true] + $row);
            $results[] = $row;
            continue;
        }
        $needLive[] = $item;
    }

    if ($needLive !== []) {
        require_once __DIR__ . '/providers/namecheap-api.php';
        $byDomain = [];
        if (hs_namecheap_configured() && !hs_namecheap_circuit_blocks_request()) {
            $nc = hs_namecheap_check_domains(array_column($needLive, 'domain'));
            if (!empty($nc['ok']) && !empty($nc['results'])) {
                foreach ($nc['results'] as $nr) {
                    $byDomain[strtolower((string) ($nr['domain'] ?? ''))] = $nr;
                }
            }
        }

        $stillNeedRdap = [];
        foreach ($needLive as $item) {
            $full = (string) $item['domain'];
            $nr = $byDomain[$full] ?? null;
            if (is_array($nr)) {
                $premium = !empty($nr['premium']);
                $priceEur = $premium && isset($nr['price_eur']) && (float) $nr['price_eur'] > 0
                    ? (float) $nr['price_eur']
                    : (float) $item['price'];
                $row = [
                    'domain' => $full,
                    'tld' => (string) $item['tld'],
                    'available' => !empty($nr['available']),
                    'price' => $priceEur,
                    'premium' => $premium,
                    'wholesale_usd' => $nr['wholesale_usd'] ?? null,
                    'eap_usd' => $nr['eap_usd'] ?? 0,
                    'premium_registration_usd' => $nr['premium_registration_usd'] ?? null,
                    'source' => 'namecheap',
                ];
                hs_domain_check_cache_set($full, ['ok' => true] + $row);
                $results[] = $row;
            } else {
                $stillNeedRdap[] = $item;
            }
        }

        if ($stillNeedRdap !== []) {
            $lookups = hs_domain_registry_lookup_batch($stillNeedRdap);
            foreach ($stillNeedRdap as $item) {
                $full = (string) $item['domain'];
                $lookup = $lookups[$full] ?? ['ok' => false, 'error' => 'lookup_failed'];
                if (empty($lookup['ok'])) {
                    continue;
                }
                $row = [
                    'domain' => $full,
                    'tld' => (string) $item['tld'],
                    'available' => (bool) ($lookup['available'] ?? false),
                    'price' => $item['price'],
                    'premium' => false,
                    'source' => (string) ($lookup['source'] ?? 'registry'),
                ];
                hs_domain_check_cache_set($full, ['ok' => true] + $row);
                $results[] = $row;
            }
        }
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

/** Homepage hero: featured TLD chips (label may differ from registry TLD, e.g. SV → .se). */
function hs_domain_hero_featured_tlds(): array
{
    return [
        ['tld' => 'eu', 'label' => 'eu', 'default' => true],
        ['tld' => 'se', 'label' => 'sv', 'default' => true],
        ['tld' => 'pl', 'label' => 'pl', 'default' => true],
        ['tld' => 'com', 'label' => 'com', 'default' => true],
        ['tld' => 'lt', 'label' => 'lt', 'default' => true],
    ];
}

/** Panel domain search: featured TLD chips + typewriter zones. */
function hs_domain_panel_featured_tlds(): array
{
    return [
        ['tld' => 'com', 'label' => 'com', 'default' => true],
        ['tld' => 'shop', 'label' => 'shop', 'default' => true],
        ['tld' => 'eu', 'label' => 'eu', 'default' => true],
        ['tld' => 'lt', 'label' => 'lt', 'default' => true],
        ['tld' => 'pl', 'label' => 'pl', 'default' => true],
        ['tld' => 'de', 'label' => 'de', 'default' => true],
        ['tld' => 'net', 'label' => 'net', 'default' => false],
        ['tld' => 'org', 'label' => 'org', 'default' => false],
    ];
}

/** Extra TLDs on /domain — loaded on “show more”. */
function hs_domain_page_extended_tlds(): array
{
    return [
        ['tld' => 'de', 'label' => 'de', 'default' => false],
        ['tld' => 'nl', 'label' => 'nl', 'default' => false],
        ['tld' => 'uk', 'label' => 'uk', 'default' => false],
        ['tld' => 'net', 'label' => 'net', 'default' => false],
        ['tld' => 'org', 'label' => 'org', 'default' => false],
        ['tld' => 'be', 'label' => 'be', 'default' => false],
        ['tld' => 'io', 'label' => 'io', 'default' => false],
        ['tld' => 'shop', 'label' => 'shop', 'default' => false],
        ['tld' => 'co.uk', 'label' => 'co.uk', 'default' => false],
        ['tld' => 'online', 'label' => 'online', 'default' => false],
    ];
}

/**
 * @param list<array{tld:string,label?:string,default?:bool}> $items
 * @param array<string, string> $t
 */
function hs_render_domain_tld_chip_group(array $items, array $t, string $lang, string $extraClass = '', string $extraAttrs = ''): string
{
    $prices = hs_domain_tld_prices();
    $aria = (string) ($t['domain_tld_zones'] ?? 'Domain zones');
    $groupClass = trim('hs-hero-tld-chips ' . $extraClass);
    $chips = '';
    foreach ($items as $item) {
        $tld = (string) ($item['tld'] ?? '');
        if ($tld === '' || !isset($prices[$tld])) {
            continue;
        }
        $chipLabel = (string) ($item['label'] ?? $tld);
        $checked = !empty($item['default']) ? ' checked' : '';
        $price = hs_domain_format_price((float) $prices[$tld], $lang);
        $title = '.' . $chipLabel;
        if ($chipLabel !== $tld) {
            $title = '.' . $chipLabel . ' (.' . $tld . ')';
        }
        $chips .= '<label class="hs-hero-tld-chip" title="' . hs_h($title) . '">'
            . '<input type="checkbox" name="tlds[]" value="' . hs_h($tld) . '" data-hs-hero-tld' . $checked . '>'
            . '<span class="hs-hero-tld-chip-face">'
            . '<span class="hs-hero-tld-chip-name">.' . hs_h($chipLabel) . '</span>'
            . '<span class="hs-hero-tld-chip-price">' . hs_h($price) . '</span>'
            . '</span></label>';
    }
    if ($chips === '') {
        return '';
    }

    return '<div class="' . hs_h($groupClass) . '" role="group" aria-label="' . hs_h($aria) . '"'
        . ($extraAttrs !== '' ? ' ' . $extraAttrs : '')
        . '>'
        . $chips
        . '</div>';
}

/**
 * @param array<string, string> $t
 * @param array{featured_tlds?: list<array{tld:string,label?:string,default?:bool}>} $opts
 */
function hs_render_domain_page_tld_picker(array $t, string $lang, array $opts = []): string
{
    $featured = $opts['featured_tlds'] ?? hs_domain_hero_featured_tlds();
    $featuredTldKeys = [];
    foreach ($featured as $item) {
        $featuredTldKeys[(string) ($item['tld'] ?? '')] = true;
    }
    $extendedItems = [];
    foreach (hs_domain_page_extended_tlds() as $item) {
        $tld = (string) ($item['tld'] ?? '');
        if ($tld === '' || isset($featuredTldKeys[$tld])) {
            continue;
        }
        $extendedItems[] = $item;
    }

    $featuredHtml = hs_render_domain_tld_chip_group($featured, $t, $lang);
    $extendedHtml = $extendedItems !== []
        ? hs_render_domain_tld_chip_group(
            $extendedItems,
            $t,
            $lang,
            'hs-hero-tld-chips--extra is-collapsed',
            'hidden data-hs-tld-extra'
        )
        : '';

    $html = '<div class="hs-domain-tld-picker">'
        . '<p class="hs-domain-tld-picker-label">' . hs_h($t['domain_tld_zones'] ?? 'Domain zones') . '</p>'
        . $featuredHtml;
    if ($extendedHtml !== '') {
        $html .= $extendedHtml
            . '<button type="button" class="hs-domain-tld-more-btn" data-hs-tld-toggle'
            . ' aria-expanded="false"'
            . ' data-label-more="' . hs_h($t['domain_show_more_zones'] ?? 'More zones') . '"'
            . ' data-label-less="' . hs_h($t['domain_show_less_zones'] ?? 'Fewer zones') . '">'
            . '<i class="fa-solid fa-plus" aria-hidden="true"></i> '
            . hs_h($t['domain_show_more_zones'] ?? 'More zones')
            . '</button>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Live domain search form (domain page).
 *
 * @param array<string, string> $t
 * @param array{check_url?:string,prefill?:string,autosearch?:string,variant?:string,register_base?:string,domain_page_url?:string} $opts
 */
function hs_render_domain_search_form(array $t, string $lang, array $opts = []): string
{
    $checkUrl = (string) ($opts['check_url'] ?? hs_url('domain-check.php'));
    $prefill = (string) ($opts['prefill'] ?? '');
    $autosearch = (string) ($opts['autosearch'] ?? '0');
    $variant = (string) ($opts['variant'] ?? 'card');
    $isPage = $variant === 'page';
    $isPanel = $variant === 'panel';
    $isFullSearch = $isPage || $isPanel;
    if ($isPanel && !function_exists('hs_panel_tab_href')) {
        require_once __DIR__ . '/panel-tabs.php';
    }

    $extraTlds = [];
    foreach (hs_domain_page_extended_tlds() as $item) {
        $extraTlds[] = (string) ($item['tld'] ?? '');
    }
    $extraTlds = array_values(array_filter($extraTlds));

    $checkSteps = [
        $t['domain_check_step_analyze'] ?? 'Agents analyzing your query…',
        $t['domain_check_step_zones'] ?? 'Scanning selected TLD zones…',
        $t['domain_check_step_registry'] ?? ($t['domain_checking'] ?? 'Checking registry…'),
        $t['domain_check_step_whois'] ?? 'Querying WHOIS / RDAP…',
        $t['domain_check_step_prices'] ?? 'Comparing registration prices…',
    ];

    $attrs = ' data-hs-domain-search data-check-url="' . hs_h($checkUrl) . '"'
        . ' data-hs-domain-autosearch="' . hs_h($autosearch) . '"'
        . ' data-msg-available="' . hs_h($t['domain_available'] ?? 'Available') . '"'
        . ' data-msg-taken="' . hs_h($t['domain_taken'] ?? 'Taken') . '"'
        . ' data-msg-invalid="' . hs_h($t['domain_invalid'] ?? 'Enter a valid domain (e.g. mysite.lt)') . '"'
        . ' data-msg-error="' . hs_h($t['domain_lookup_error'] ?? 'Could not check domain. Try again.') . '"'
        . ' data-msg-checking="' . hs_h($t['domain_checking'] ?? 'Checking registry…') . '"'
        . ' data-check-steps="' . hs_h(json_encode(array_values($checkSteps), JSON_UNESCAPED_UNICODE)) . '"'
        . ' data-msg-cta="' . hs_h($t['domain_register_cta'] ?? 'Register with this domain') . '"'
        . ' data-msg-picked-label="' . hs_h($t['register_domain_selected'] ?? 'Your domain') . '"'
        . ' data-msg-bundle-cta="' . hs_h($t['domain_register_cta'] ?? 'Hosting + domain') . '"'
        . ' data-msg-domain-cta="' . hs_h($t['domain_only_cta'] ?? 'Domain only') . '"'
        . ' data-msg-register-this="' . hs_h($t['domain_register_this'] ?? 'Register this domain') . '"'
        . ' data-msg-register-selected="' . hs_h($t['domain_register_selected'] ?? 'Register selected') . '"'
        . ' data-msg-selected-count="' . hs_h($t['domain_register_selected_count'] ?? '{count} domains selected') . '"'
        . ' data-msg-selected-one="' . hs_h($t['domain_register_selected_one'] ?? '1 domain selected') . '"'
        . ' data-msg-cart-total="' . hs_h($t['domain_cart_total'] ?? 'Total') . '"'
        . ' data-msg-no-tlds="' . hs_h($t['domain_no_tlds'] ?? 'Select at least one zone') . '"'
        . ' data-msg-norid="' . hs_h($t['domain_tld_norid_note'] ?? '.no registers via Norid') . '"'
        . ' data-msg-show-more="' . hs_h($t['domain_show_more_results'] ?? 'Show more domains') . '"'
        . ' data-col-domain="' . hs_h($t['domain_col_name'] ?? 'Domain') . '"'
        . ' data-col-status="' . hs_h($t['domain_col_status'] ?? 'Status') . '"'
        . ' data-col-price="' . hs_h($t['domain_col_price'] ?? 'Price') . '"'
        . ' data-register-base="' . hs_h((string) ($opts['register_base'] ?? ($isPanel
            ? hs_url(hs_panel_tab_href('domains', 'register'))
            : hs_url('register.php')))) . '"';
    if ($isFullSearch && $extraTlds !== []) {
        $attrs .= ' data-extra-tlds="' . hs_h(implode(',', $extraTlds)) . '"';
    }
    if ($isPanel) {
        $attrs .= ' data-panel-mode="1"';
    }

    $formClass = match (true) {
        $isPage => 'hs-domain-search-form hs-domain-search-form--page',
        $isPanel => 'hs-domain-search-form hs-domain-search-form--panel',
        default => 'hp-stack',
    };
    $placeholder = (string) ($t[$isFullSearch ? 'domain_search_page_placeholder' : 'domain_sld_placeholder'] ?? $t['domain_sld_placeholder'] ?? '');
    if ($isPanel && $placeholder === '') {
        $placeholder = (string) ($t['dom_check_sld_placeholder'] ?? $t['dom_check_panel_placeholder'] ?? 'mybrand');
    }
    $typewriterPlaceholder = '';

    if ($isFullSearch) {
        $domainPageUrl = (string) ($opts['domain_page_url'] ?? ($isPanel
            ? hs_url(hs_panel_tab_href('domains', 'overview'))
            : hs_url('domain')));
        $typewriterBases = $isPanel
            ? 'solaskinner'
            : (string) ($t['domain_typewriter_bases'] ?? 'solaskinner,cafe,shop,nordic');
        $typewriterTlds = '';
        if ($isPanel) {
            $typewriterTlds = implode(',', array_map(
                static fn (array $item): string => (string) ($item['tld'] ?? ''),
                hs_domain_panel_featured_tlds()
            ));
        }
        $attrs .= ' data-recent-title="' . hs_h($t['domain_recent_searches'] ?? 'Recent searches') . '"'
            . ' data-recent-hint="' . hs_h($t['domain_recent_searches_hint'] ?? '') . '"'
            . ' data-recent-available="' . hs_h($t['domain_available'] ?? 'Available') . '"'
            . ' data-domain-page-url="' . hs_h($domainPageUrl) . '"'
            . ' data-typewriter-base="solaskinner"'
            . ' data-typewriter-bases="' . hs_h($typewriterBases) . '"';
        if ($typewriterTlds !== '') {
            $attrs .= ' data-typewriter-tlds="' . hs_h($typewriterTlds) . '"';
        }

        $inputPlaceholder = $prefill !== '' ? $placeholder : ($isPanel ? $placeholder : $typewriterPlaceholder);
        $panelInputWrapCls = 'hs-domain-search-input-field' . ($isPanel ? '' : ' hs-domain-typewriter-wrap')
            . ($prefill !== '' ? ' has-value' : ($isPanel ? ' is-active' : ''));
        $typewriterDemo = $isPanel ? '' : (
            '<span class="hs-domain-typewriter-demo" data-hs-domain-typewriter aria-hidden="true">'
            . '<span class="hs-domain-typewriter-text" data-hs-typewriter-text></span>'
            . '<span class="hs-domain-typewriter-cursor"></span>'
            . '</span>'
        );

        return '<form class="' . $formClass . '"' . $attrs . '>'
            . '<div class="hs-domain-search-bar">'
            . '<label class="hs-domain-search-input-wrap">'
            . '<span class="hs-domain-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>'
            . '<span class="' . $panelInputWrapCls . '">'
            . $typewriterDemo
            . '<input type="text" id="hs-domain-search-input" name="domain" class="hs-domain-search-input" placeholder="' . hs_h($inputPlaceholder) . '" required autocomplete="off"'
            . ' inputmode="url" autocapitalize="none" spellcheck="false"'
            . ' value="' . hs_h($prefill) . '" data-hs-domain-input'
            . ' aria-label="' . hs_h($t['domain_search_label'] ?? $t['domain_search_title'] ?? 'Domain name') . '">'
            . '</span>'
            . '</label>'
            . '<button type="submit" class="hs-btn hs-btn-primary hs-domain-search-submit" data-hs-domain-btn data-label="'
            . hs_h($t['domain_search_btn'] ?? 'Search') . '">'
            . '<span class="hs-domain-search-submit-text">' . hs_h($t['domain_search_btn'] ?? 'Search') . '</span>'
            . '<i class="fa-solid fa-arrow-right hs-domain-search-submit-icon" aria-hidden="true"></i>'
            . '</button>'
            . '</div>'
            . hs_render_domain_page_tld_picker($t, $lang, $isPanel
                ? ['featured_tlds' => hs_domain_panel_featured_tlds()]
                : [])
            . '</form>';
    }

    $cardTypewriterBases = (string) ($t['domain_typewriter_bases'] ?? 'solaskinner,cafe,shop,nordic');
    return '<form class="' . $formClass . '"' . $attrs
        . ' data-typewriter-base="solaskinner"'
        . ' data-typewriter-bases="' . hs_h($cardTypewriterBases) . '">'
        . hs_render_hero_domain_tld_chips($t, $lang)
        . '<div class="hs-field hs-field-domain-sld hs-domain-typewriter-wrap' . ($prefill !== '' ? ' has-value' : '') . '" style="margin:0">'
        . '<label class="hs-sr-only" for="hs-domain-card-input">' . hs_h($t['domain_search_label'] ?? $t['domain_search_title'] ?? 'Domain name') . '</label>'
        . '<div class="hs-domain-typewriter-demo" data-hs-domain-typewriter aria-hidden="true">'
        . '<span class="hs-domain-typewriter-text" data-hs-typewriter-text></span>'
        . '<span class="hs-domain-typewriter-cursor"></span>'
        . '</div>'
        . '<input type="text" id="hs-domain-card-input" name="domain" placeholder="' . hs_h($prefill !== '' ? $placeholder : $typewriterPlaceholder) . '" required autocomplete="off"'
        . ' inputmode="url" autocapitalize="none" spellcheck="false"'
        . ' value="' . hs_h($prefill) . '" data-hs-domain-input'
        . ' aria-label="' . hs_h($t['domain_search_label'] ?? $t['domain_search_title'] ?? 'Domain name') . '">'
        . '</div>'
        . '<button type="submit" class="hs-btn hs-btn-primary" style="width:100%" data-hs-domain-btn data-label="'
        . hs_h($t['domain_search_btn'] ?? 'Search') . '">' . hs_h($t['domain_search_btn'] ?? 'Search') . '</button>'
        . '</form>';
}

/**
 * Decorative domain search mockup for homepage (redirects to /domain).
 *
 * @param array<string, string> $t
 */
function hs_render_hero_domain_mockup(array $t, string $lang): string
{
    $domainPage = hs_url('domain');
    $prices = hs_domain_tld_prices();
    $chips = '<div class="hs-hero-tld-chips hs-hero-tld-chips--mock" aria-hidden="true">';
    foreach (hs_domain_hero_featured_tlds() as $item) {
        $tld = (string) ($item['tld'] ?? '');
        if ($tld === '' || !isset($prices[$tld])) {
            continue;
        }
        $chipLabel = (string) ($item['label'] ?? $tld);
        $price = hs_domain_format_price((float) $prices[$tld], $lang);
        $chips .= '<span class="hs-hero-tld-chip is-decorative">'
            . '<span class="hs-hero-tld-chip-face">'
            . '<span class="hs-hero-tld-chip-name">.' . hs_h($chipLabel) . '</span>'
            . '<span class="hs-hero-tld-chip-price">' . hs_h($price) . '</span>'
            . '</span></span>';
    }
    $chips .= '</div>';

    $typewriterBases = (string) ($t['domain_typewriter_bases'] ?? 'solaskinner,cafe,shop,nordic');
    return '<div class="hp-stack hs-domain-mockup" data-hs-domain-mockup data-domain-page="' . hs_h($domainPage) . '"'
        . ' data-typewriter-base="solaskinner"'
        . ' data-typewriter-bases="' . hs_h($typewriterBases) . '">'
        . $chips
        . '<div class="hs-field hs-field-domain-sld hs-domain-typewriter-wrap" style="margin:0">'
        . '<label class="hs-sr-only" for="hs-hero-domain-mockup-input">' . hs_h($t['domain_search_label'] ?? $t['domain_search_title'] ?? 'Domain name') . '</label>'
        . '<div class="hs-domain-typewriter-demo" data-hs-domain-typewriter aria-hidden="true">'
        . '<span class="hs-domain-typewriter-text" data-hs-typewriter-text></span>'
        . '<span class="hs-domain-typewriter-cursor"></span>'
        . '</div>'
        . '<input type="text" id="hs-hero-domain-mockup-input" placeholder="" autocomplete="off"'
        . ' inputmode="url" autocapitalize="none" spellcheck="false" data-hs-domain-mockup-input'
        . ' aria-label="' . hs_h($t['domain_search_label'] ?? $t['domain_search_title'] ?? 'Domain name') . '">'
        . '</div>'
        . '<button type="button" class="hs-btn hs-btn-primary" style="width:100%" data-hs-domain-mockup-btn>'
        . hs_h($t['domain_search_btn'] ?? 'Search') . '</button>'
        . '<p class="hs-domain-mockup-hint hp-muted">' . hs_h($t['domain_mockup_hint'] ?? '') . '</p>'
        . '</div>';
}

/** @param array<string, string> $t */
function hs_render_hero_domain_tld_chips(array $t, string $lang): string
{
    return hs_render_domain_tld_chip_group(hs_domain_hero_featured_tlds(), $t, $lang);
}

/** @param array<string, string> $t
 *  @param array{label?:string,glow?:bool,status?:string,price?:string,class?:string,changeable?:bool,change_label?:string} $opts */
function hs_render_domain_picked(string $domain, array $t, array $opts = []): string
{
    $domain = trim($domain);
    if ($domain === '') {
        return '';
    }
    $label = (string) ($opts['label'] ?? $t['register_domain_selected'] ?? 'Your domain');
    $glow = ($opts['glow'] ?? true) !== false;
    $status = (string) ($opts['status'] ?? '');
    $price = (string) ($opts['price'] ?? '');
    $classes = ['hs-domain-picked'];
    if ($glow && $status !== 'taken') {
        $classes[] = 'is-glow';
    }
    if ($status === 'taken') {
        $classes[] = 'is-taken';
    }
    $extra = trim((string) ($opts['class'] ?? ''));
    if ($extra !== '') {
        $classes[] = $extra;
    }
    $statusHtml = '';
    if ($status === 'available') {
        $statusHtml = '<span class="hs-domain-picked-status is-ok">' . hs_h($t['domain_available'] ?? 'Available') . '</span>';
    } elseif ($status === 'taken') {
        $statusHtml = '<span class="hs-domain-picked-status is-taken">' . hs_h($t['domain_taken'] ?? 'Taken') . '</span>';
    }
    $check = ($glow && $status !== 'taken')
        ? '<span class="hs-domain-picked-check" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>'
        : '';
    $changeHtml = '';
    if (!empty($opts['changeable'])) {
        $changeLabel = (string) ($opts['change_label'] ?? $t['register_domain_change'] ?? 'Change domain');
        $changeHtml = '<button type="button" class="hs-btn hs-btn-ghost hs-btn-sm hs-domain-picked-change" data-hs-domain-change>'
            . '<i class="fa-solid fa-pen" aria-hidden="true"></i> ' . hs_h($changeLabel)
            . '</button>';
    }
    return '<div class="' . hs_h(implode(' ', $classes)) . '" data-hs-domain-picked data-initial-domain="' . hs_h($domain) . '">'
        . '<span class="hs-domain-picked-icon" aria-hidden="true"><i class="fa-solid fa-globe"></i></span>'
        . '<div class="hs-domain-picked-body">'
        . '<span class="hs-domain-picked-label">' . hs_h($label) . '</span>'
        . '<strong class="hs-domain-picked-name">' . hs_h($domain) . '</strong>'
        . ($price !== '' ? '<span class="hs-domain-picked-price">' . hs_h($price) . '</span>' : '')
        . $statusHtml
        . '</div>'
        . $check
        . ($changeHtml !== '' ? '<div class="hs-domain-picked-actions">' . $changeHtml . '</div>' : '')
        . '</div>';
}