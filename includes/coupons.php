<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

const HS_DB_META_COUPONS = 'coupons_catalog';

function hs_coupons_file(): string
{
    return hs_data_file('coupons');
}

/** Public promo codes disabled — checkout uses catalog prices only. */
function hs_coupons_enabled(): bool
{
    if (defined('HS_COUPONS_ENABLED')) {
        return (bool) HS_COUPONS_ENABLED;
    }

    return false;
}

/** @return list<array<string, mixed>> */
function hs_coupons_defaults(): array
{
    // No seeded promo codes (WELCOME20 / EKOHOST / DOMAIN5 removed).
    return [];
}

/** @return list<array<string, mixed>> */
function hs_coupons_load(): array
{
    if (!hs_coupons_enabled()) {
        return [];
    }
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        $stored = hs_db_meta_get_array(HS_DB_META_COUPONS, []);
    } else {
        $stored = hs_read_json(hs_coupons_file());
    }
    if (!is_array($stored) || $stored === []) {
        return hs_coupons_defaults();
    }
    return array_values(array_filter($stored, 'is_array'));
}

/** @param list<array<string, mixed>> $coupons */
function hs_coupons_save(array $coupons): bool
{
    $payload = array_values($coupons);
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        return hs_db_meta_set_array(HS_DB_META_COUPONS, $payload);
    }
    return hs_write_json(hs_coupons_file(), $payload);
}

function hs_coupon_normalize_code(string $code): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $code) ?? '');
}

function hs_coupon_by_code(string $code): ?array
{
    $code = hs_coupon_normalize_code($code);
    if ($code === '') {
        return null;
    }
    foreach (hs_coupons_load() as $coupon) {
        if (hs_coupon_normalize_code((string) ($coupon['code'] ?? '')) === $code) {
            return $coupon;
        }
    }
    return null;
}

function hs_coupon_label(array $coupon, string $lang): string
{
    $key = 'label_' . $lang;
    return (string) ($coupon[$key] ?? $coupon['label_en'] ?? $coupon['label_uk'] ?? (string) ($coupon['code'] ?? ''));
}

/** @return array{ok:bool,error?:string,coupon?:array<string,mixed>} */
function hs_coupon_validate(string $code, bool $hasHosting = true, bool $hasDomain = false, ?string $domainTld = null): array
{
    if (!hs_coupons_enabled()) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $coupon = hs_coupon_by_code($code);
    if ($coupon === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (empty($coupon['active'])) {
        return ['ok' => false, 'error' => 'inactive'];
    }
    $expires = trim((string) ($coupon['expires_at'] ?? ''));
    if ($expires !== '' && strtotime($expires) !== false && strtotime($expires) < time()) {
        return ['ok' => false, 'error' => 'expired'];
    }
    $max = (int) ($coupon['max_uses'] ?? 0);
    $used = (int) ($coupon['used_count'] ?? 0);
    if ($max > 0 && $used >= $max) {
        return ['ok' => false, 'error' => 'exhausted'];
    }

    $scope = (string) ($coupon['scope'] ?? 'order');
    if ($scope === 'hosting' && !$hasHosting) {
        return ['ok' => false, 'error' => 'scope'];
    }
    if ($scope === 'domain' && !$hasDomain) {
        return ['ok' => false, 'error' => 'scope'];
    }

    $tld = strtolower(trim((string) ($coupon['tld'] ?? '')));
    if ($tld !== '' && $hasDomain) {
        $domainTld = strtolower(trim((string) $domainTld));
        if ($domainTld !== $tld) {
            return ['ok' => false, 'error' => 'tld'];
        }
    }

    return ['ok' => true, 'coupon' => $coupon];
}

/**
 * @return array{
 *   hosting_nok:float,
 *   domain_eur:float,
 *   hosting_discount_nok:float,
 *   domain_discount_eur:float,
 *   coupon?:array<string,mixed>
 * }
 */
function hs_coupon_apply(?array $coupon, float $hostingNok, float $domainEur, ?string $domainTld = null): array
{
    $out = [
        'hosting_nok' => max(0, $hostingNok),
        'domain_eur' => max(0, $domainEur),
        'hosting_discount_nok' => 0.0,
        'domain_discount_eur' => 0.0,
        'coupon' => null,
    ];
    if ($coupon === null) {
        return $out;
    }

    $scope = (string) ($coupon['scope'] ?? 'order');
    $type = (string) ($coupon['type'] ?? 'percent');
    $value = (float) ($coupon['value'] ?? 0);
    $appliesHosting = in_array($scope, ['hosting', 'order'], true);
    $appliesDomain = in_array($scope, ['domain', 'order'], true);

    if ($appliesHosting && $type === 'percent' && $value > 0) {
        $disc = round($out['hosting_nok'] * ($value / 100), 2);
        $out['hosting_discount_nok'] = $disc;
        $out['hosting_nok'] = max(0, $out['hosting_nok'] - $disc);
    } elseif ($appliesHosting && $type === 'fixed_nok' && $value > 0) {
        $disc = min($out['hosting_nok'], $value);
        $out['hosting_discount_nok'] = $disc;
        $out['hosting_nok'] = max(0, $out['hosting_nok'] - $disc);
    }

    if ($appliesDomain && $domainEur > 0) {
        if ($type === 'fixed_eur' && $value > 0) {
            $disc = min($out['domain_eur'], $value);
            $out['domain_discount_eur'] = $disc;
            $out['domain_eur'] = max(0, $out['domain_eur'] - $disc);
        } elseif ($type === 'domain_cap_eur' && $value > 0) {
            $tldOk = ($coupon['tld'] ?? '') === '' || strtolower((string) ($coupon['tld'] ?? '')) === strtolower((string) $domainTld);
            if ($tldOk && $out['domain_eur'] > $value) {
                $out['domain_discount_eur'] = round($out['domain_eur'] - $value, 2);
                $out['domain_eur'] = $value;
            }
        } elseif ($type === 'percent' && $value > 0) {
            $disc = round($out['domain_eur'] * ($value / 100), 2);
            $out['domain_discount_eur'] = $disc;
            $out['domain_eur'] = max(0, $out['domain_eur'] - $disc);
        }
    }

    $out['coupon'] = $coupon;
    return $out;
}

function hs_coupon_session_get(): ?array
{
    hs_session_start();
    $raw = $_SESSION['hs_coupon'] ?? null;
    return is_array($raw) ? $raw : null;
}

/** @param array<string, mixed> $coupon */
function hs_coupon_session_set(array $coupon): void
{
    hs_session_start();
    $_SESSION['hs_coupon'] = [
        'id' => (string) ($coupon['id'] ?? ''),
        'code' => (string) ($coupon['code'] ?? ''),
    ];
}

function hs_coupon_session_clear(): void
{
    hs_session_start();
    unset($_SESSION['hs_coupon']);
}

function hs_coupon_redeem(string $code): bool
{
    $code = hs_coupon_normalize_code($code);
    $coupons = hs_coupons_load();
    $changed = false;
    foreach ($coupons as $i => $coupon) {
        if (hs_coupon_normalize_code((string) ($coupon['code'] ?? '')) !== $code) {
            continue;
        }
        $coupons[$i]['used_count'] = (int) ($coupon['used_count'] ?? 0) + 1;
        $changed = true;
        break;
    }
    return $changed && hs_coupons_save($coupons);
}

/** @return array<string, mixed> */
function hs_coupon_normalize_row(array $row): array
{
    $code = hs_coupon_normalize_code((string) ($row['code'] ?? ''));
    if ($code === '') {
        return [];
    }
    $id = trim((string) ($row['id'] ?? ''));
    if ($id === '') {
        $id = 'cp_' . strtolower($code);
    }
    $scope = (string) ($row['scope'] ?? 'order');
    if (!in_array($scope, ['hosting', 'domain', 'order'], true)) {
        $scope = 'order';
    }
    $type = (string) ($row['type'] ?? 'percent');
    if (!in_array($type, ['percent', 'fixed_eur', 'fixed_nok', 'domain_cap_eur'], true)) {
        $type = 'percent';
    }
    return [
        'id' => $id,
        'code' => $code,
        'active' => !empty($row['active']),
        'scope' => $scope,
        'type' => $type,
        'value' => max(0, (float) ($row['value'] ?? 0)),
        'tld' => strtolower(preg_replace('/[^a-z0-9.]/', '', (string) ($row['tld'] ?? '')) ?? ''),
        'max_uses' => max(0, (int) ($row['max_uses'] ?? 0)),
        'used_count' => max(0, (int) ($row['used_count'] ?? 0)),
        'expires_at' => trim((string) ($row['expires_at'] ?? '')),
        'label_uk' => trim((string) ($row['label_uk'] ?? '')),
        'label_en' => trim((string) ($row['label_en'] ?? '')),
        'label_no' => trim((string) ($row['label_no'] ?? '')),
    ];
}

function hs_coupon_error_message(string $error, array $t): string
{
    return match ($error) {
        'not_found' => $t['coupon_error_not_found'] ?? 'Invalid promo code',
        'inactive' => $t['coupon_error_inactive'] ?? 'This promo code is not active',
        'expired' => $t['coupon_error_expired'] ?? 'This promo code has expired',
        'exhausted' => $t['coupon_error_exhausted'] ?? 'This promo code has reached its usage limit',
        'scope' => $t['coupon_error_scope'] ?? 'This code does not apply to your order',
        'tld' => $t['coupon_error_tld'] ?? 'This code applies to a different domain extension',
        default => $t['coupon_error_invalid'] ?? 'Could not apply promo code',
    };
}