<?php
declare(strict_types=1);

require_once __DIR__ . '/currency.php';

function hs_plan_catalog_file(): string
{
    return hs_data_file('plans-catalog');
}

/**
 * Hosting wholesale USD/mo — Namecheap **Stellar** shared list (no promo renew tricks).
 * Maps: starter → Stellar, plus → Stellar Plus, business → Stellar Business.
 * Solaskinner retail = wholesale × (1 + NC_HOSTING_MARKUP_PCT), default +70%.
 *
 * Namecheap Stellar public (USD/mo): $5.88 / $7.88 / $11.88
 * Override via NC_HOSTING_WHOLESALE_USD in config.local.
 *
 * @return array<string, float>
 */
function hs_nc_hosting_wholesale_usd(): array
{
    if (defined('NC_HOSTING_WHOLESALE_USD') && is_array(NC_HOSTING_WHOLESALE_USD)) {
        $out = [];
        foreach (NC_HOSTING_WHOLESALE_USD as $k => $v) {
            $out[(string) $k] = (float) $v;
        }
        if ($out !== []) {
            return $out;
        }
    }

    return [
        'starter' => 5.88,  // Namecheap Stellar
        'plus' => 7.88,     // Stellar Plus
        'business' => 11.88, // Stellar Business
    ];
}

/**
 * @deprecated No promo discounts — always 0.
 * @return array<string, int>
 */
function hs_nc_hosting_promo_discount_pct(): array
{
    return [
        'starter' => 0,
        'plus' => 0,
        'business' => 0,
    ];
}

/** Hosting markup % (default 70). */
function hs_nc_hosting_markup_pct(): float
{
    if (function_exists('hs_namecheap_hosting_markup_pct')) {
        return hs_namecheap_hosting_markup_pct();
    }
    if (defined('NC_HOSTING_MARKUP_PCT')) {
        return max(0.0, min(500.0, (float) NC_HOSTING_MARKUP_PCT));
    }

    return 70.0;
}

/**
 * Retail USD/mo = Namecheap wholesale × (1 + hosting markup).
 * No sale/strikethrough pricing.
 */
function hs_nc_hosting_retail_usd(string $planId): float
{
    $wholesale = (float) (hs_nc_hosting_wholesale_usd()[$planId] ?? 0.0);
    if ($wholesale <= 0) {
        return 0.0;
    }
    $markup = hs_nc_hosting_markup_pct();

    return round($wholesale * (1 + $markup / 100), 2);
}

/** Retail NOK/mo from live FX rates. */
function hs_nc_hosting_retail_nok(string $planId): int
{
    $usd = hs_nc_hosting_retail_usd($planId);
    if ($usd <= 0) {
        return 0;
    }
    require_once __DIR__ . '/currency.php';
    $usdRate = max(0.001, (float) (hs_exchange_rates()['USD'] ?? 0.095));

    return (int) max(1, round($usd / $usdRate));
}

/** Retail EUR/mo from live FX rates. */
function hs_nc_hosting_retail_eur(string $planId): float
{
    $usd = hs_nc_hosting_retail_usd($planId);
    if ($usd <= 0) {
        return 0.0;
    }
    if (function_exists('hs_namecheap_usd_to_eur')) {
        return hs_namecheap_usd_to_eur($usd);
    }
    require_once __DIR__ . '/currency.php';
    $usdRate = max(0.001, (float) (hs_exchange_rates()['USD'] ?? 0.095));
    $eurRate = max(0.001, (float) (hs_exchange_rates()['EUR'] ?? 0.088));

    return round(($usd / $usdRate) * $eurRate, 2);
}

/** @deprecated No crossed-out “was” price — always 0. */
function hs_nc_hosting_list_nok(string $planId): int
{
    unset($planId);

    return 0;
}

/**
 * Recompute starter/plus/business prices from Namecheap wholesale + hosting markup.
 * Clears promo fields. Safe to call from admin “Sync prices”.
 *
 * @return array{ok:bool,plans:array<string,array<string,mixed>>,markup_pct:float}
 */
function hs_plan_catalog_sync_namecheap_prices(): array
{
    $catalog = hs_plan_catalog_load();
    $markup = hs_nc_hosting_markup_pct();
    $synced = [];
    foreach (['starter', 'plus', 'business'] as $id) {
        if (!isset($catalog['plans'][$id]) && !isset(hs_plan_catalog_defaults()['plans'][$id])) {
            continue;
        }
        $base = $catalog['plans'][$id] ?? hs_plan_catalog_defaults()['plans'][$id];
        $wholesale = (float) (hs_nc_hosting_wholesale_usd()[$id] ?? 0);
        // Always re-apply retail = wholesale × (1 + markup); unlock stale admin locks
        $base['pricing_locked'] = false;
        $base['price_nok'] = hs_nc_hosting_retail_nok($id);
        $base['price_eur'] = hs_nc_hosting_retail_eur($id);
        $base['price_was_nok'] = 0;
        $base['discount_pct'] = 0;
        $base['wholesale_usd'] = $wholesale;
        $base['markup_pct'] = $markup;
        $base['pricing_source'] = 'namecheap_hosting';
        $catalog['plans'][$id] = $base;
        $synced[$id] = [
            'wholesale_usd' => $wholesale,
            'retail_usd' => hs_nc_hosting_retail_usd($id),
            'price_nok' => $base['price_nok'],
            'price_eur' => $base['price_eur'],
        ];
    }
    $ok = hs_plan_catalog_save($catalog);

    return ['ok' => $ok, 'plans' => $synced, 'markup_pct' => $markup];
}

/**
 * Realistic stacks for Solaskinner on Namecheap shared (cPanel):
 * static SPA builds in public_html, PHP apps, optional Node via cPanel “Setup Node.js App”.
 *
 * @return array{frontend_list:string,backend_list:string,node_list:string,pkg_list:string}
 */
function hs_plan_catalog_webapp_defaults(): array
{
    return [
        // Built static output → public_html (no long-running Node required)
        'frontend_list' => 'HTML/CSS/JS, React (SPA), Vue.js, Vite, Angular (static), Svelte, Astro (static), Gatsby',
        // Primary stack is PHP; simple Node apps via cPanel when enabled
        'backend_list' => 'PHP 8.2–8.4, WordPress, Laravel, custom PHP, Express (Node.js App)',
        'node_list' => '18.x, 20.x (cPanel Node.js App)',
        'pkg_list' => 'Composer (PHP), npm (Node apps)',
    ];
}

/** @return array{frontend_list:string,backend_list:string,node_list:string,pkg_list:string} */
function hs_plan_catalog_webapp_config(): array
{
    $catalog = hs_plan_catalog_load();
    $stored = is_array($catalog['webapp'] ?? null) ? $catalog['webapp'] : [];

    return array_merge(hs_plan_catalog_webapp_defaults(), array_intersect_key($stored, hs_plan_catalog_webapp_defaults()));
}

function hs_plan_webapp_details_enabled(string $planId): bool
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower($planId)) ?? '';
    if ($id === '' || $id === 'domain') {
        return false;
    }
    $catalog = hs_plan_catalog_load();
    $plan = $catalog['plans'][$id] ?? [];
    if (($plan['type'] ?? '') === 'domain_only') {
        return false;
    }
    if (array_key_exists('webapp_details', $plan)) {
        return !empty($plan['webapp_details']);
    }

    return true;
}

/** @return array{plans:array<string,array<string,mixed>>,services:list<array<string,mixed>>,webapp:array<string,string>} */
function hs_plan_catalog_defaults(): array
{
    return [
        'webapp' => hs_plan_catalog_webapp_defaults(),
        'plans' => [
            'free' => [
                'id' => 'free',
                'active' => false,
                'sort' => 2,
                'type' => 'shared',
                'sites' => 1,
                'storage_mb' => 1024,
                'databases' => 1,
                'ecosystem_apps' => true,
                'webapp_details' => false,
                'price_nok' => 0,
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => 'free',
                'platform_subdomain' => true,
                'disk_gb' => 1,
                'ram_mb' => 256,
                'cpu_cores' => 1,
                'inodes' => 50000,
                'max_processes' => 15,
                'php_workers' => 5,
                'traffic' => 'fair_use',
            ],
            'domain' => [
                'id' => 'domain',
                'active' => true,
                'sort' => 5,
                'type' => 'domain_only',
                'sites' => 0,
                'storage_mb' => 0,
                'databases' => 0,
                'ecosystem_apps' => false,
                'webapp_details' => false,
                'price_nok' => 0,
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => '',
                'disk_gb' => 0,
                'ram_mb' => 0,
                'cpu_cores' => 0,
                'inodes' => 0,
                'max_processes' => 0,
                'php_workers' => 0,
                'traffic' => '—',
            ],
            /** Retired test plan — kept inactive so old users still resolve labels. */
            'test5' => [
                'id' => 'test5',
                'active' => false,
                'sort' => 1,
                'type' => 'shared',
                'pricing_locked' => true,
                'test_plan' => true,
                'sites' => 1,
                'storage_mb' => 1024,
                'databases' => 1,
                'ecosystem_apps' => true,
                'price_nok' => 5,
                'price_eur' => 0,
                'badge' => 'test',
                'disk_gb' => 1,
                'cpanel_package' => 'sola_starter',
            ],
            /**
             * Simple low-cost plan for real card/PayPal checkout smoke tests (client path).
             * Fixed 10 NOK/mo — not Namecheap-synced.
             */
            'mini' => [
                'id' => 'mini',
                'active' => true,
                'sort' => 6,
                'type' => 'shared',
                'pricing_locked' => true,
                'test_plan' => true,
                'sites' => 1,
                'sites_unlimited' => false,
                'storage_mb' => 1024,
                'storage_unlimited' => false,
                'databases' => 1,
                'ecosystem_apps' => true,
                'webapp_details' => false,
                'auto_backup' => false,
                'subdomains' => 5,
                'ftp_users' => 5,
                'price_nok' => 10,
                'price_eur' => 0.90,
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => 'test',
                'disk_gb' => 1,
                'cpanel_package' => 'sola_starter',
                'ram_mb' => 512,
                'cpu_cores' => 1,
                'inodes' => 100000,
                'max_processes' => 20,
                'php_workers' => 10,
                'traffic' => 'fair_use',
            ],
            'starter' => [
                'id' => 'starter',
                'active' => true,
                'sort' => 10,
                // Namecheap Stellar wholesale $5.88 → retail +70% · 1 site
                'sites' => 1,
                'sites_unlimited' => false,
                'storage_mb' => 20480,
                'storage_unlimited' => false,
                'databases' => 5,
                'ecosystem_apps' => true,
                'webapp_details' => true,
                'auto_backup' => true,
                'backup_freq' => 'twice_week',
                'subdomains' => 30,
                'ftp_users' => 50,
                'price_nok' => hs_nc_hosting_retail_nok('starter'),
                'price_eur' => hs_nc_hosting_retail_eur('starter'),
                'wholesale_usd' => 5.88,
                'markup_pct' => hs_nc_hosting_markup_pct(),
                'pricing_source' => 'namecheap_hosting',
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => '',
                'disk_gb' => 20,
                'cpanel_package' => 'sola_starter',
                'ram_mb' => 1024,
                'cpu_cores' => 1,
                'inodes' => 300000,
                'max_processes' => 40,
                'php_workers' => 20,
                'traffic' => 'unlimited',
            ],
            'client_hosting_50' => [
                'id' => 'client_hosting_50',
                'active' => false,
                'sort' => 8,
                'type' => 'hosting',
                'billing_period' => 'year',
                'price_eur' => 120.0,
                'price_nok' => hs_eur_to_nok(120.0),
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => '',
                'sites' => 3,
                'sites_unlimited' => false,
                'storage_mb' => 20480,
                'storage_unlimited' => false,
                'databases' => 5,
                'ecosystem_apps' => true,
                'webapp_details' => false,
                'disk_gb' => 20,
                'ram_mb' => 1024,
                'cpu_cores' => 1,
                'inodes' => 250000,
                'max_processes' => 40,
                'php_workers' => 20,
                'traffic' => 'unlimited',
            ],
            'plus' => [
                'id' => 'plus',
                'active' => true,
                'sort' => 15,
                // Mid tier: more disk & sites than Start, below Business
                'sites' => 100,
                'sites_unlimited' => true,
                'storage_mb' => 51200,
                'storage_unlimited' => false,
                'databases' => 25,
                'ecosystem_apps' => true,
                'webapp_details' => true,
                'auto_backup' => true,
                'backup_freq' => 'twice_week_auto',
                'subdomains' => 0,
                'ftp_users' => 0,
                'price_nok' => hs_nc_hosting_retail_nok('plus'),
                'price_eur' => hs_nc_hosting_retail_eur('plus'),
                'wholesale_usd' => 7.88,
                'markup_pct' => hs_nc_hosting_markup_pct(),
                'pricing_source' => 'namecheap_hosting',
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => 'popular',
                'disk_gb' => 50,
                'cpanel_package' => 'sola_plus',
                'ram_mb' => 2048,
                'cpu_cores' => 2,
                'inodes' => 300000,
                'max_processes' => 60,
                'php_workers' => 30,
                'traffic' => 'unlimited',
            ],
            'business' => [
                'id' => 'business',
                'active' => true,
                'sort' => 20,
                // Top tier: most disk, inodes, Imunify360 — always better than Plus
                'sites' => 100,
                'sites_unlimited' => true,
                'storage_mb' => 102400,
                'storage_unlimited' => false,
                'databases' => 50,
                'ecosystem_apps' => true,
                'webapp_details' => true,
                'auto_backup' => true,
                'backup_freq' => 'twice_week_auto',
                'imunify360' => true,
                'subdomains' => 0,
                'ftp_users' => 0,
                'price_nok' => hs_nc_hosting_retail_nok('business'),
                'price_eur' => hs_nc_hosting_retail_eur('business'),
                'wholesale_usd' => 11.88,
                'markup_pct' => hs_nc_hosting_markup_pct(),
                'pricing_source' => 'namecheap_hosting',
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => '',
                'disk_gb' => 100,
                'cpanel_package' => 'sola_business',
                'ram_mb' => 3072,
                'cpu_cores' => 2,
                'inodes' => 600000,
                'max_processes' => 80,
                'php_workers' => 40,
                'traffic' => 'unlimited',
            ],
            'maintenance' => [
                'id' => 'maintenance',
                'active' => true,
                'sort' => 40,
                'type' => 'managed_service',
                'billing_period' => 'year',
                'price_eur' => 120.0,
                'price_nok' => hs_eur_to_nok(120.0),
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => 'service',
                'labels' => [
                    'uk' => 'Обслуговування сайту',
                    'en' => 'Site maintenance',
                    'no' => 'Nettstedvedlikehold',
                ],
                'desc' => [
                    'uk' => 'Річний догляд сайту Solaskinner: оновлення CMS/PHP, моніторинг, пріоритетна підтримка.',
                    'en' => 'Yearly Solaskinner site care: CMS/PHP updates, monitoring and priority support.',
                    'no' => 'Årlig Solaskinner-vedlikehold: CMS/PHP-oppdateringer, overvåking og prioritert support.',
                ],
                'sites' => 0,
                'storage_mb' => 0,
                'databases' => 0,
                'ecosystem_apps' => false,
                'webapp_details' => false,
                'disk_gb' => 0,
                'ram_mb' => 0,
                'cpu_cores' => 0,
                'inodes' => 0,
                'max_processes' => 0,
                'php_workers' => 0,
                'traffic' => '—',
            ],
            'seo_specialist' => [
                'id' => 'seo_specialist',
                'active' => true,
                'sort' => 45,
                'type' => 'managed_service',
                'billing_period' => 'month',
                'price_eur' => 80.0,
                'price_nok' => hs_eur_to_nok(80.0),
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => 'service',
                'labels' => [
                    'uk' => 'SEO-спеціаліст',
                    'en' => 'SEO specialist',
                    'no' => 'SEO-spesialist',
                ],
                'desc' => [
                    'uk' => 'Щомісячна SEO-оптимізація сайту Solaskinner, технічний аудит і звіти по позиціях.',
                    'en' => 'Monthly SEO for Solaskinner sites: technical audit and ranking reports.',
                    'no' => 'Månedlig SEO for Solaskinner: teknisk revisjon og rangeringsrapporter.',
                ],
                'sites' => 0,
                'storage_mb' => 0,
                'databases' => 0,
                'ecosystem_apps' => false,
                'webapp_details' => false,
                'disk_gb' => 0,
                'ram_mb' => 0,
                'cpu_cores' => 0,
                'inodes' => 0,
                'max_processes' => 0,
                'php_workers' => 0,
                'traffic' => '—',
            ],
            'vps' => [
                'id' => 'vps',
                'active' => true,
                'sort' => 30,
                'type' => 'vps',
                'sites' => 10,
                'storage_mb' => 20480,
                'databases' => 20,
                'ram_gb' => 2,
                'cpu_cores' => 2,
                'bandwidth_tb' => 3,
                'ecosystem_apps' => true,
                'webapp_details' => true,
                'price_nok' => 223,
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => 'vps',
                'disk_gb' => 20,
                'ram_mb' => 2048,
                'inodes' => 200000,
                'max_processes' => 100,
                'php_workers' => 50,
                'traffic' => '3 TB',
            ],
        ],
        'services' => [
            [
                'id' => 'extra_sites_5',
                'active' => true,
                'sort' => 10,
                'icon' => 'fa-globe',
                'price_nok' => 65,
                'labels' => [
                    'uk' => '+5 додаткових сайтів',
                    'en' => '+5 extra websites',
                    'no' => '+5 ekstra nettsteder',
                ],
                'desc' => [
                    'uk' => 'Розширте ліміт сайтів на 5',
                    'en' => 'Extend your website limit by 5',
                    'no' => 'Utvid nettstedsgrensen med 5',
                ],
            ],
            [
                'id' => 'extra_storage_10gb',
                'active' => true,
                'sort' => 20,
                'icon' => 'fa-hard-drive',
                'price_nok' => 38,
                'labels' => [
                    'uk' => '+10 ГБ сховища',
                    'en' => '+10 GB storage',
                    'no' => '+10 GB lagring',
                ],
                'desc' => [
                    'uk' => 'Додатковий диск для файлів і баз',
                    'en' => 'Extra disk space for files and databases',
                    'no' => 'Ekstra diskplass for filer og databaser',
                ],
            ],
            [
                'id' => 'ssl_wildcard',
                'active' => true,
                'sort' => 30,
                'icon' => 'fa-lock',
                'price_nok' => 131,
                'labels' => [
                    'uk' => 'Wildcard SSL',
                    'en' => 'Wildcard SSL',
                    'no' => 'Wildcard SSL',
                ],
                'desc' => [
                    'uk' => 'SSL для всіх піддоменів',
                    'en' => 'SSL certificate for all subdomains',
                    'no' => 'SSL-sertifikat for alle underdomener',
                ],
            ],
            [
                'id' => 'priority_support',
                'active' => true,
                'sort' => 40,
                'icon' => 'fa-headset',
                'price_nok' => 104,
                'labels' => [
                    'uk' => 'Пріоритетна підтримка',
                    'en' => 'Priority support',
                    'no' => 'Prioritert support',
                ],
                'desc' => [
                    'uk' => 'Відповідь протягом 2 годин',
                    'en' => 'Response within 2 hours',
                    'no' => 'Svar innen 2 timer',
                ],
            ],
            // Domains are sold on /domains and /domain.php — never as plan add-on checkboxes.
            [
                'id' => 'api_ai',
                'active' => true,
                'sort' => 50,
                'icon' => 'fa-wand-magic-sparkles',
                'billing_period' => 'year',
                'price_eur' => 20.0,
                'price_nok' => hs_eur_to_nok(20.0),
                'labels' => [
                    'uk' => 'API + AI',
                    'en' => 'API + AI',
                    'no' => 'API + AI',
                ],
                'desc' => [
                    'uk' => 'Доступ до API та AI-помічника в панелі — €20/рік',
                    'en' => 'API and AI assistant access in the panel — €20/yr',
                    'no' => 'API og AI-assistent i panelet — €20/år',
                ],
            ],
        ],
    ];
}

/** @return array{plans:array<string,array<string,mixed>>,services:list<array<string,mixed>>,updated_at?:string} */
function hs_plan_catalog_load(): array
{
    $defaults = hs_plan_catalog_defaults();
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        $stored = hs_db_meta_get_array(HS_DB_META_PLANS_CATALOG, []);
    } else {
        $stored = hs_read_json(hs_plan_catalog_file());
    }
    if ($stored === []) {
        return $defaults;
    }
    $plans = $defaults['plans'];
    foreach ($stored['plans'] ?? [] as $id => $row) {
        if (!is_array($row)) {
            continue;
        }
        $sid = (string) ($row['id'] ?? $id);
        if ($sid === '') {
            continue;
        }
        $plans[$sid] = array_merge($plans[$sid] ?? ['id' => $sid], $row, ['id' => $sid]);
    }
    // Soft-deleted plans (incl. defaults) stay gone until re-created.
    $removedPlans = [];
    foreach (is_array($stored['removed_plans'] ?? null) ? $stored['removed_plans'] : [] as $rid) {
        $rid = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $rid)) ?? '';
        if ($rid === '') {
            continue;
        }
        $removedPlans[] = $rid;
        unset($plans[$rid]);
    }
    $services = [];
    $seen = [];
    foreach ($stored['services'] ?? [] as $svc) {
        if (!is_array($svc) || ($svc['id'] ?? '') === '') {
            continue;
        }
        $sid = (string) $svc['id'];
        // Domains are sold on dedicated pages — never plan add-on checkboxes.
        if (hs_plan_catalog_service_is_domain($sid, $svc)) {
            continue;
        }
        $seen[$sid] = true;
        $services[] = $svc;
    }
    foreach ($defaults['services'] as $svc) {
        $sid = (string) ($svc['id'] ?? '');
        if ($sid === '' || hs_plan_catalog_service_is_domain($sid, $svc)) {
            continue;
        }
        if (empty($seen[$sid])) {
            $services[] = $svc;
        }
    }
    // Fill missing billing_period / price_eur on legacy stored rows (keep admin prices)
    $defById = [];
    foreach ($defaults['services'] as $defSvc) {
        if (is_array($defSvc) && ($defSvc['id'] ?? '') !== '') {
            $defById[(string) $defSvc['id']] = $defSvc;
        }
    }
    foreach ($services as $i => $svc) {
        if (!is_array($svc)) {
            continue;
        }
        $sid = (string) ($svc['id'] ?? '');
        $def = $defById[$sid] ?? [];
        if (empty($svc['billing_period'])) {
            $services[$i]['billing_period'] = (string) ($def['billing_period'] ?? 'month');
        }
        if (!isset($svc['price_eur']) && isset($def['price_eur'])) {
            $services[$i]['price_eur'] = (float) $def['price_eur'];
        }
        if (!isset($svc['sort']) && isset($def['sort'])) {
            $services[$i]['sort'] = (int) $def['sort'];
        }
        if (empty($svc['icon']) && !empty($def['icon'])) {
            $services[$i]['icon'] = (string) $def['icon'];
        }
    }
    usort($services, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));
    uasort($plans, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));

    // No promo UI. starter/plus/business: auto retail = Namecheap wholesale × hosting markup (unless pricing_locked).
    foreach ($plans as $pid => $plan) {
        if (!is_array($plan)) {
            continue;
        }
        // Never advertise unlimited SSD — Nebula packages have fixed GB.
        $plans[$pid]['storage_unlimited'] = false;
        // Keep starter/plus/business Stellar quotas locked (ignore stale admin JSON).
        if (isset($defaults['plans'][$pid]['disk_gb']) && in_array($pid, ['starter', 'plus', 'business'], true)) {
            $def = $defaults['plans'][$pid];
            $plans[$pid]['disk_gb'] = (int) $def['disk_gb'];
            $plans[$pid]['storage_mb'] = (int) ($def['storage_mb'] ?? max(512, $plans[$pid]['disk_gb'] * 1024));
            $plans[$pid]['storage_unlimited'] = false;
            if (isset($def['sites'])) {
                $plans[$pid]['sites'] = (int) $def['sites'];
            }
            if (array_key_exists('sites_unlimited', $def)) {
                $plans[$pid]['sites_unlimited'] = !empty($def['sites_unlimited']);
            }
            if (isset($def['inodes'])) {
                $plans[$pid]['inodes'] = (int) $def['inodes'];
            }
            if (isset($def['databases'])) {
                $plans[$pid]['databases'] = (int) $def['databases'];
            }
            if (isset($def['backup_freq'])) {
                $plans[$pid]['backup_freq'] = (string) $def['backup_freq'];
            }
            if (array_key_exists('imunify360', $def)) {
                $plans[$pid]['imunify360'] = !empty($def['imunify360']);
            }
            if (array_key_exists('auto_backup', $def)) {
                $plans[$pid]['auto_backup'] = !empty($def['auto_backup']);
            }
        } elseif (($plan['type'] ?? '') !== 'domain_only' && $pid !== 'domain') {
            $diskGb = (int) ($plans[$pid]['disk_gb'] ?? 0);
            $storageMb = (int) ($plans[$pid]['storage_mb'] ?? 0);
            if ($diskGb <= 0 && $storageMb > 0) {
                $diskGb = max(1, (int) round($storageMb / 1024));
                $plans[$pid]['disk_gb'] = $diskGb;
            }
            if ($diskGb > 0) {
                $plans[$pid]['storage_mb'] = max(512, $diskGb * 1024);
            }
        }
        $plans[$pid]['price_was_nok'] = 0;
        $plans[$pid]['discount_pct'] = 0;
        // Never sell the temporary Stripe test plan publicly
        if ($pid === 'test5') {
            $plans[$pid]['active'] = false;
        }
        // Domain-only is an order type, not a free hosting tariff card.
        if ($pid === 'domain' || ($plans[$pid]['type'] ?? '') === 'domain_only') {
            $plans[$pid]['type'] = 'domain_only';
            $plans[$pid]['price_nok'] = 0;
            $plans[$pid]['price_eur'] = 0;
            $plans[$pid]['badge'] = '';
            // Keep in catalog for checkout order_type=domain, but never as a public hosting plan.
            $plans[$pid]['sites'] = 0;
            $plans[$pid]['disk_gb'] = 0;
            $plans[$pid]['storage_mb'] = 0;
            $plans[$pid]['ecosystem_apps'] = false;
        }
        // Retired free hosting tier — must stay off public registration / pricing.
        if ($pid === 'free') {
            $plans[$pid]['active'] = false;
            $plans[$pid]['badge'] = 'free';
        }
        if (!in_array($pid, ['starter', 'plus', 'business'], true)) {
            continue;
        }
        if (!empty($plan['pricing_locked'])) {
            continue;
        }
        if (!isset(hs_nc_hosting_wholesale_usd()[$pid])) {
            continue;
        }
        $plans[$pid]['price_nok'] = hs_nc_hosting_retail_nok($pid);
        $plans[$pid]['price_eur'] = hs_nc_hosting_retail_eur($pid);
        $plans[$pid]['wholesale_usd'] = (float) (hs_nc_hosting_wholesale_usd()[$pid] ?? 0);
        $plans[$pid]['markup_pct'] = hs_nc_hosting_markup_pct();
        $plans[$pid]['pricing_source'] = 'namecheap_hosting';
    }

    return [
        'plans' => $plans,
        'services' => $services,
        'removed_plans' => array_values(array_unique($removedPlans)),
        'webapp' => array_merge(
            hs_plan_catalog_webapp_defaults(),
            is_array($stored['webapp'] ?? null) ? array_intersect_key($stored['webapp'], hs_plan_catalog_webapp_defaults()) : []
        ),
        'updated_at' => (string) ($stored['updated_at'] ?? ''),
    ];
}

/**
 * Persist catalog. Pass removed_plans to keep soft-deleted default tariffs from reappearing.
 *
 * @param array{plans?:array,services?:list,removed_plans?:list,webapp?:array} $data
 */
function hs_plan_catalog_save(array $data): bool
{
    $removed = [];
    foreach (is_array($data['removed_plans'] ?? null) ? $data['removed_plans'] : [] as $rid) {
        $rid = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $rid)) ?? '';
        if ($rid !== '') {
            $removed[$rid] = true;
        }
    }
    $payload = [
        'plans' => is_array($data['plans'] ?? null) ? $data['plans'] : [],
        'services' => is_array($data['services'] ?? null) ? array_values($data['services']) : [],
        'removed_plans' => array_keys($removed),
        'updated_at' => gmdate('c'),
    ];
    if (is_array($data['webapp'] ?? null)) {
        $payload['webapp'] = array_merge(
            hs_plan_catalog_webapp_defaults(),
            array_intersect_key($data['webapp'], hs_plan_catalog_webapp_defaults())
        );
    }
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        return hs_db_meta_set_array(HS_DB_META_PLANS_CATALOG, $payload);
    }
    return hs_write_json(hs_plan_catalog_file(), $payload);
}

/**
 * Soft-delete a plan from the live catalog (works for built-in defaults too).
 *
 * @param array<string, mixed> $catalog from hs_plan_catalog_load()
 * @return array{ok:bool,error?:string,catalog?:array<string,mixed>}
 */
function hs_plan_catalog_delete_plan(array $catalog, string $planId): array
{
    $planId = preg_replace('/[^a-z0-9_-]/', '', strtolower($planId)) ?? '';
    if ($planId === '' || $planId === 'starter') {
        // Keep a minimal default hosting plan so registration never empties.
        if ($planId === 'starter') {
            return ['ok' => false, 'error' => 'protected'];
        }

        return ['ok' => false, 'error' => 'invalid'];
    }
    if (!isset($catalog['plans'][$planId])) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    unset($catalog['plans'][$planId]);
    $removed = is_array($catalog['removed_plans'] ?? null) ? $catalog['removed_plans'] : [];
    $removed[] = $planId;
    $catalog['removed_plans'] = array_values(array_unique(array_filter(array_map(
        static fn($id): string => preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $id)) ?? '',
        $removed
    ))));
    if (!hs_plan_catalog_save($catalog)) {
        return ['ok' => false, 'error' => 'save'];
    }

    return ['ok' => true, 'catalog' => hs_plan_catalog_load()];
}

/** @return array<string, array<string, mixed>> */
function hs_plan_catalog_plans(bool $activeOnly = true): array
{
    $catalog = hs_plan_catalog_load();
    $out = [];
    foreach ($catalog['plans'] as $id => $plan) {
        if ($activeOnly && empty($plan['active'])) {
            continue;
        }
        $out[$id] = $plan;
    }
    return $out;
}

function hs_plan_catalog_is_public(string $id, array $plan): bool
{
    if ($id === 'free' || $id === 'domain' || ($plan['type'] ?? '') === 'domain_only') {
        return false;
    }
    return !empty($plan['active']);
}

/** Active tariffs shown on landing, registration and checkout (sorted). */
/** @return array<string, array<string, mixed>> */
function hs_plan_catalog_public_plans(?string $category = null): array
{
    $out = [];
    foreach (hs_plan_catalog_plans(true) as $id => $plan) {
        if (!hs_plan_catalog_is_public($id, $plan)) {
            continue;
        }
        $type = (string) ($plan['type'] ?? '');
        if ($category === 'hosting' && $type === 'managed_service') {
            continue;
        }
        if ($category === 'managed_service' && $type !== 'managed_service') {
            continue;
        }
        $out[$id] = $plan;
    }
    uasort($out, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));
    return $out;
}

/** @return list<array{level:string,message:string,detail?:string}> */
function hs_plan_catalog_audit(): array
{
    $issues = [];
    $catalog = hs_plan_catalog_load();
    $defaults = hs_plan_catalog_defaults();
    $publicIds = array_keys(hs_plan_catalog_public_plans());

    foreach ($catalog['plans'] as $id => $plan) {
        if ($id === 'domain' || ($plan['type'] ?? '') === 'domain_only') {
            continue;
        }
        if (!empty($plan['active']) && !in_array($id, $publicIds, true)) {
            $issues[] = ['level' => 'warn', 'message' => 'plan_hidden', 'detail' => $id];
        }
        if (empty($plan['active'])) {
            continue;
        }
        if (!empty($plan['storage_unlimited'])) {
            continue;
        }
        $diskGb = (int) ($plan['disk_gb'] ?? 0);
        $storageMb = (int) ($plan['storage_mb'] ?? 0);
        $expectedMb = $diskGb > 0 ? $diskGb * 1024 : 0;
        if ($expectedMb > 0 && abs($storageMb - $expectedMb) > 64) {
            $issues[] = [
                'level' => 'warn',
                'message' => 'plan_disk_mismatch',
                'detail' => $id . ': disk_gb=' . $diskGb . ' vs storage_mb=' . $storageMb,
            ];
        }
        if (isset($defaults['plans'][$id]) && in_array($id, ['starter', 'plus', 'business'], true)) {
            $defNok = (int) ($defaults['plans'][$id]['price_nok'] ?? 0);
            $curNok = (int) round((float) ($plan['price_nok'] ?? 0));
            if ($defNok > 0 && abs($curNok - $defNok) > 2) {
                $issues[] = [
                    'level' => 'info',
                    'message' => 'plan_price_drift',
                    'detail' => $id . ': ' . $curNok . ' NOK (calc ' . $defNok . ')',
                ];
            }
        }
    }

    $landingMissing = [];
    foreach ($publicIds as $pid) {
        $found = false;
        foreach ($catalog['plans'] as $id => $plan) {
            if ($id === $pid && !empty($plan['active'])) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $landingMissing[] = $pid;
        }
    }
    if ($landingMissing !== []) {
        $issues[] = ['level' => 'error', 'message' => 'landing_plan_missing', 'detail' => implode(', ', $landingMissing)];
    }

    return $issues;
}

/** @return array<string, mixed> */
function hs_plan_catalog_plan(string $id): array
{
    $catalog = hs_plan_catalog_load();
    $plans = $catalog['plans'];
    if (isset($plans[$id])) {
        return $plans[$id];
    }
    if ($id === 'pro' && isset($plans['business'])) {
        return $plans['business'];
    }
    return $plans['starter'] ?? hs_plan_catalog_defaults()['plans']['starter'];
}

/**
 * Domain registration/renewal must not appear as hosting plan add-ons.
 * Domains are purchased on /domains, /domain.php, panel domains UI.
 *
 * @param array<string, mixed> $svc
 */
function hs_plan_catalog_service_is_domain(string $id, array $svc = []): bool
{
    $id = strtolower(trim($id));
    if ($id === '' || $id === 'domain') {
        return true;
    }
    if (str_starts_with($id, 'domain_') || str_starts_with($id, 'tld_') || str_starts_with($id, 'dom_')) {
        return true;
    }
    $type = strtolower(trim((string) ($svc['type'] ?? '')));
    if ($type === 'domain' || $type === 'domain_only' || $type === 'domain_registration') {
        return true;
    }
    // Heuristic: labels/desc like ".lt domain" / "Домен .lt"
    $labels = is_array($svc['labels'] ?? null) ? $svc['labels'] : [];
    $blob = strtolower(implode(' ', array_map('strval', $labels)));
    if (preg_match('/\bdomain\b|\bdomene\b|домен/u', $blob)
        && preg_match('/\.[a-z]{2,}|tld|реєстрац|registr/u', $blob)) {
        return true;
    }

    return false;
}

/** @return list<array<string, mixed>> */
function hs_plan_catalog_services(bool $activeOnly = true): array
{
    $catalog = hs_plan_catalog_load();
    $out = [];
    foreach ($catalog['services'] as $svc) {
        if (!is_array($svc)) {
            continue;
        }
        $sid = (string) ($svc['id'] ?? '');
        if ($sid === '' || hs_plan_catalog_service_is_domain($sid, $svc)) {
            continue;
        }
        if ($activeOnly && empty($svc['active'])) {
            continue;
        }
        $out[] = $svc;
    }
    return $out;
}

function hs_plan_catalog_service_label(array $svc, string $lang): string
{
    $id = (string) ($svc['id'] ?? '');
    $labels = is_array($svc['labels'] ?? null) ? $svc['labels'] : [];
    foreach ([$lang, 'en', 'uk', 'no'] as $lc) {
        $lab = trim((string) ($labels[$lc] ?? ''));
        if ($lab !== '' && $lab !== $id) {
            return $lab;
        }
    }
    // Global i18n (public + panel merged into $t)
    $t = is_array($GLOBALS['t'] ?? null) ? $GLOBALS['t'] : [];
    $fromT = trim((string) ($t['plan_' . $id] ?? $t['service_' . $id] ?? ''));
    if ($fromT !== '' && $fromT !== $id) {
        return $fromT;
    }
    if ($id !== '') {
        return ucwords(str_replace(['_', '-'], ' ', $id));
    }

    return $id;
}

function hs_plan_catalog_service_desc(array $svc, string $lang): string
{
    $id = (string) ($svc['id'] ?? '');
    $desc = is_array($svc['desc'] ?? null) ? $svc['desc'] : [];
    foreach ([$lang, 'en', 'uk', 'no'] as $lc) {
        $d = trim((string) ($desc[$lc] ?? ''));
        if ($d !== '') {
            return $d;
        }
    }
    $t = is_array($GLOBALS['t'] ?? null) ? $GLOBALS['t'] : [];

    return trim((string) ($t['plan_' . $id . '_desc'] ?? $t['service_' . $id . '_desc'] ?? ''));
}

/** @return list<array<string, mixed>> */
function hs_user_plan_services(array $user, string $lang): array
{
    $ids = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];
    if ($ids === []) {
        return [];
    }
    require_once __DIR__ . '/plan-services.php';
    $map = hs_plan_addon_catalog_map(false);
    $out = [];
    foreach ($ids as $id) {
        $sid = (string) $id;
        if ($sid !== '' && isset($map[$sid])) {
            $out[] = $map[$sid];
        }
    }
    return $out;
}

function hs_plan_billing_period(array $plan): string
{
    $period = (string) ($plan['billing_period'] ?? 'month');

    return $period === 'year' ? 'year' : 'month';
}

function hs_plan_is_managed_service(string $planId): bool
{
    $plan = hs_plan_catalog_plan($planId);

    return ($plan['type'] ?? '') === 'managed_service';
}

function hs_plan_catalog_normalize_plan_row(array $row): array
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($row['id'] ?? ''))) ?? '';
    if ($id === '') {
        return [];
    }
    $type = trim((string) ($row['type'] ?? ''));
    $domainOnly = $type === 'domain_only' || $id === 'domain';
    $managedService = $type === 'managed_service';
    $billingPeriod = (string) ($row['billing_period'] ?? 'month');
    if (!in_array($billingPeriod, ['month', 'year'], true)) {
        $billingPeriod = 'month';
    }
    $priceEur = max(0.0, (float) ($row['price_eur'] ?? 0));

    // No unlimited SSD on Solaskinner / shared plans — always a fixed quota.
    $storageUnlimited = false;
    $diskGb = max(1, (int) ($row['disk_gb'] ?? 5));
    $storageMb = $domainOnly ? 0 : max(512, (int) ($row['storage_mb'] ?? 5120));
    if (!$domainOnly && $diskGb > 0) {
        $storageMb = max(512, $diskGb * 1024);
    }

    if ($managedService) {
        $priceNok = max(0, (float) ($row['price_nok'] ?? 0));
        if ($priceNok <= 0 && $priceEur > 0) {
            $priceNok = (float) hs_eur_to_nok($priceEur);
        }

        return [
            'id' => $id,
            'active' => !empty($row['active']),
            'sort' => max(0, (int) ($row['sort'] ?? 0)),
            'type' => 'managed_service',
            'billing_period' => $billingPeriod,
            'price_eur' => $priceEur,
            'sites' => 0,
            'sites_unlimited' => false,
            'storage_mb' => 0,
            'storage_unlimited' => false,
            'auto_backup' => false,
            'imunify360' => false,
            'databases' => 0,
            'ecosystem_apps' => false,
            'webapp_details' => false,
            'price_nok' => $priceNok,
            'price_was_nok' => max(0, (float) ($row['price_was_nok'] ?? 0)),
            'discount_pct' => max(0, min(99, (int) ($row['discount_pct'] ?? 0))),
            'badge' => preg_replace('/[^a-z_]/', '', (string) ($row['badge'] ?? 'service')) ?? 'service',
            'ram_gb' => 0,
            'cpu_cores' => 0,
            'bandwidth_tb' => 0,
            'disk_gb' => 0,
            'ram_mb' => 0,
            'inodes' => 0,
            'max_processes' => 0,
            'php_workers' => 0,
            'traffic' => '—',
        ];
    }

    $labels = [];
    $desc = [];
    foreach (['en', 'uk', 'no'] as $lng) {
        $lab = trim((string) ($row['label_' . $lng] ?? ($row['labels'][$lng] ?? '')));
        if ($lab !== '') {
            $labels[$lng] = $lab;
        }
        $d = trim((string) ($row['desc_' . $lng] ?? ($row['desc'][$lng] ?? '')));
        if ($d !== '') {
            $desc[$lng] = $d;
        }
    }
    if (is_array($row['labels'] ?? null)) {
        foreach ($row['labels'] as $lng => $lab) {
            $lab = trim((string) $lab);
            if ($lab !== '' && !isset($labels[(string) $lng])) {
                $labels[(string) $lng] = $lab;
            }
        }
    }

    $out = [
        'id' => $id,
        'active' => !empty($row['active']),
        'sort' => max(0, (int) ($row['sort'] ?? 0)),
        'type' => $type !== '' ? $type : 'shared',
        'billing_period' => $billingPeriod,
        'price_eur' => $priceEur,
        'sites' => $domainOnly ? 0 : max(1, (int) ($row['sites'] ?? 1)),
        'sites_unlimited' => !empty($row['sites_unlimited']),
        'storage_mb' => $storageMb,
        'storage_unlimited' => false,
        'auto_backup' => !empty($row['auto_backup']),
        'imunify360' => !empty($row['imunify360']),
        'databases' => $domainOnly ? 0 : max(1, (int) ($row['databases'] ?? 2)),
        'ecosystem_apps' => !empty($row['ecosystem_apps']),
        'webapp_details' => !empty($row['webapp_details']),
        'price_nok' => max(0, (float) ($row['price_nok'] ?? 0)),
        'price_was_nok' => max(0, (float) ($row['price_was_nok'] ?? 0)),
        'discount_pct' => max(0, min(99, (int) ($row['discount_pct'] ?? 0))),
        'badge' => preg_replace('/[^a-z_]/', '', (string) ($row['badge'] ?? '')) ?? '',
        'ram_gb' => max(0, (int) ($row['ram_gb'] ?? 0)),
        'cpu_cores' => max(1, (int) ($row['cpu_cores'] ?? 1)),
        'bandwidth_tb' => max(0, (float) ($row['bandwidth_tb'] ?? 0)),
        'disk_gb' => $domainOnly ? 0 : max(1, $diskGb > 0 ? $diskGb : (int) round($storageMb / 1024)),
        'ram_mb' => max(256, (int) ($row['ram_mb'] ?? 1024)),
        'inodes' => max(1000, (int) ($row['inodes'] ?? 50000)),
        'max_processes' => max(10, (int) ($row['max_processes'] ?? 40)),
        'php_workers' => max(5, (int) ($row['php_workers'] ?? 20)),
        'traffic' => trim((string) ($row['traffic'] ?? 'unlimited')) !== '' ? trim((string) $row['traffic']) : 'unlimited',
    ];
    if ($labels !== []) {
        $out['labels'] = $labels;
    }
    if ($desc !== []) {
        $out['desc'] = $desc;
    }

    return $out;
}

function hs_plan_catalog_normalize_service_row(array $row): array
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($row['id'] ?? ''))) ?? '';
    if ($id === '') {
        return [];
    }
    $labels = [];
    $desc = [];
    foreach (['uk', 'en', 'no'] as $lng) {
        $labels[$lng] = trim((string) ($row['label_' . $lng] ?? ($row['labels'][$lng] ?? '')));
        $desc[$lng] = trim((string) ($row['desc_' . $lng] ?? ($row['desc'][$lng] ?? '')));
    }
    $period = strtolower(trim((string) ($row['billing_period'] ?? 'month')));
    if ($period !== 'year') {
        $period = 'month';
    }
    $priceNok = max(0.0, (float) ($row['price_nok'] ?? 0));
    $priceEur = max(0.0, (float) ($row['price_eur'] ?? 0));
    // If only EUR set, derive NOK from live FX for checkout consistency
    if ($priceNok <= 0 && $priceEur > 0 && function_exists('hs_eur_to_nok')) {
        $priceNok = (float) hs_eur_to_nok($priceEur);
    }
    if ($priceEur <= 0 && $priceNok > 0 && function_exists('hs_exchange_rates')) {
        $rates = hs_exchange_rates();
        $eurRate = max(0.001, (float) ($rates['EUR'] ?? 0.088));
        $priceEur = round($priceNok * $eurRate, 2);
    }

    return [
        'id' => $id,
        'active' => !empty($row['active']),
        'sort' => max(0, (int) ($row['sort'] ?? 0)),
        'icon' => trim((string) ($row['icon'] ?? 'fa-puzzle-piece')) !== '' ? trim((string) $row['icon']) : 'fa-puzzle-piece',
        'billing_period' => $period,
        'price_nok' => $priceNok,
        'price_eur' => $priceEur,
        'labels' => $labels,
        'desc' => $desc,
    ];
}