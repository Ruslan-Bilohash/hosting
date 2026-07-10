<?php
declare(strict_types=1);

function hs_plan_catalog_file(): string
{
    return hs_data_file('plans-catalog');
}

/** @return array{plans:array<string,array<string,mixed>>,services:list<array<string,mixed>>} */
function hs_plan_catalog_defaults(): array
{
    return [
        'plans' => [
            'starter' => [
                'id' => 'starter',
                'active' => true,
                'sort' => 10,
                'sites' => 1,
                'storage_mb' => 5120,
                'databases' => 2,
                'ecosystem_apps' => true,
                'price_nok' => 25,
                'price_was_nok' => 99,
                'discount_pct' => 75,
                'badge' => 'popular',
                'disk_gb' => 5,
                'ram_mb' => 1024,
                'cpu_cores' => 1,
                'inodes' => 150000,
                'max_processes' => 40,
                'php_workers' => 20,
                'traffic' => 'unlimited',
            ],
            'business' => [
                'id' => 'business',
                'active' => true,
                'sort' => 20,
                'sites' => 5,
                'storage_mb' => 30720,
                'databases' => 10,
                'ecosystem_apps' => true,
                'price_nok' => 99,
                'price_was_nok' => 0,
                'discount_pct' => 0,
                'badge' => '',
                'disk_gb' => 30,
                'ram_mb' => 3072,
                'cpu_cores' => 2,
                'inodes' => 400000,
                'max_processes' => 80,
                'php_workers' => 40,
                'traffic' => 'unlimited',
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
                'price_nok' => 169,
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
                'price_nok' => 49,
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
                'price_nok' => 29,
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
                'price_nok' => 99,
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
                'price_nok' => 79,
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
        ],
    ];
}

/** @return array{plans:array<string,array<string,mixed>>,services:list<array<string,mixed>>,updated_at?:string} */
function hs_plan_catalog_load(): array
{
    $defaults = hs_plan_catalog_defaults();
    $stored = hs_read_json(hs_plan_catalog_file());
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
    $services = [];
    $seen = [];
    foreach ($stored['services'] ?? [] as $svc) {
        if (!is_array($svc) || ($svc['id'] ?? '') === '') {
            continue;
        }
        $sid = (string) $svc['id'];
        $seen[$sid] = true;
        $services[] = $svc;
    }
    foreach ($defaults['services'] as $svc) {
        $sid = (string) ($svc['id'] ?? '');
        if ($sid !== '' && empty($seen[$sid])) {
            $services[] = $svc;
        }
    }
    usort($services, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));
    uasort($plans, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));
    return [
        'plans' => $plans,
        'services' => $services,
        'updated_at' => (string) ($stored['updated_at'] ?? ''),
    ];
}

function hs_plan_catalog_save(array $data): bool
{
    $payload = [
        'plans' => is_array($data['plans'] ?? null) ? $data['plans'] : [],
        'services' => is_array($data['services'] ?? null) ? array_values($data['services']) : [],
        'updated_at' => gmdate('c'),
    ];
    return hs_write_json(hs_plan_catalog_file(), $payload);
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

/** @return list<array<string, mixed>> */
function hs_plan_catalog_services(bool $activeOnly = true): array
{
    $catalog = hs_plan_catalog_load();
    $out = [];
    foreach ($catalog['services'] as $svc) {
        if (!is_array($svc)) {
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
    $labels = is_array($svc['labels'] ?? null) ? $svc['labels'] : [];
    return (string) ($labels[$lang] ?? $labels['en'] ?? $labels['uk'] ?? ($svc['id'] ?? ''));
}

function hs_plan_catalog_service_desc(array $svc, string $lang): string
{
    $desc = is_array($svc['desc'] ?? null) ? $svc['desc'] : [];
    return (string) ($desc[$lang] ?? $desc['en'] ?? $desc['uk'] ?? '');
}

/** @return list<array<string, mixed>> */
function hs_user_plan_services(array $user, string $lang): array
{
    $ids = is_array($user['plan_services'] ?? null) ? $user['plan_services'] : [];
    if ($ids === []) {
        return [];
    }
    $map = [];
    foreach (hs_plan_catalog_services() as $svc) {
        $map[(string) ($svc['id'] ?? '')] = $svc;
    }
    $out = [];
    foreach ($ids as $id) {
        $sid = (string) $id;
        if ($sid !== '' && isset($map[$sid])) {
            $out[] = $map[$sid];
        }
    }
    return $out;
}

function hs_plan_catalog_normalize_plan_row(array $row): array
{
    $id = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($row['id'] ?? ''))) ?? '';
    if ($id === '') {
        return [];
    }
    return [
        'id' => $id,
        'active' => !empty($row['active']),
        'sort' => max(0, (int) ($row['sort'] ?? 0)),
        'sites' => max(1, (int) ($row['sites'] ?? 1)),
        'storage_mb' => max(512, (int) ($row['storage_mb'] ?? 5120)),
        'databases' => max(1, (int) ($row['databases'] ?? 2)),
        'ecosystem_apps' => !empty($row['ecosystem_apps']),
        'price_nok' => max(0, (float) ($row['price_nok'] ?? 0)),
        'price_was_nok' => max(0, (float) ($row['price_was_nok'] ?? 0)),
        'discount_pct' => max(0, min(99, (int) ($row['discount_pct'] ?? 0))),
        'badge' => preg_replace('/[^a-z]/', '', (string) ($row['badge'] ?? '')) ?? '',
        'type' => (string) ($row['type'] ?? ''),
        'ram_gb' => max(0, (int) ($row['ram_gb'] ?? 0)),
        'cpu_cores' => max(1, (int) ($row['cpu_cores'] ?? 1)),
        'bandwidth_tb' => max(0, (float) ($row['bandwidth_tb'] ?? 0)),
        'disk_gb' => max(1, (int) ($row['disk_gb'] ?? 5)),
        'ram_mb' => max(256, (int) ($row['ram_mb'] ?? 1024)),
        'inodes' => max(1000, (int) ($row['inodes'] ?? 50000)),
        'max_processes' => max(10, (int) ($row['max_processes'] ?? 40)),
        'php_workers' => max(5, (int) ($row['php_workers'] ?? 20)),
        'traffic' => trim((string) ($row['traffic'] ?? 'unlimited')) !== '' ? trim((string) $row['traffic']) : 'unlimited',
    ];
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
    return [
        'id' => $id,
        'active' => !empty($row['active']),
        'sort' => max(0, (int) ($row['sort'] ?? 0)),
        'icon' => trim((string) ($row['icon'] ?? 'fa-puzzle-piece')) !== '' ? trim((string) $row['icon']) : 'fa-puzzle-piece',
        'price_nok' => max(0, (float) ($row['price_nok'] ?? 0)),
        'labels' => $labels,
        'desc' => $desc,
    ];
}