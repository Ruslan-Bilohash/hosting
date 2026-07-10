<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/plan-catalog.php';
require_once __DIR__ . '/resource-usage.php';

/** @return array{ok:bool,error?:string} */
function hs_admin_client_update(string $userId, array $fields): array
{
    $userId = trim($userId);
    if ($userId === '' || hs_user_by_id($userId) === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $planId = trim((string) ($fields['plan'] ?? ''));
    if ($planId !== '' && !isset(hs_plan_catalog_load()['plans'][$planId])) {
        return ['ok' => false, 'error' => 'invalid_plan'];
    }
    $status = trim((string) ($fields['subscription_status'] ?? ''));
    if ($status !== '' && !in_array($status, ['pending', 'active', 'suspended'], true)) {
        return ['ok' => false, 'error' => 'invalid_status'];
    }
    $serviceIds = [];
    if (isset($fields['plan_services']) && is_array($fields['plan_services'])) {
        $valid = [];
        foreach (hs_plan_catalog_services() as $svc) {
            $valid[(string) ($svc['id'] ?? '')] = true;
        }
        foreach ($fields['plan_services'] as $sid) {
            $sid = (string) $sid;
            if ($sid !== '' && !empty($valid[$sid])) {
                $serviceIds[] = $sid;
            }
        }
    }
    $paidUntil = trim((string) ($fields['paid_until'] ?? ''));
    $notes = trim((string) ($fields['admin_notes'] ?? ''));
    $oldUser = hs_user_by_id($userId);
    $oldPlan = (string) ($oldUser['plan'] ?? '');

    $ok = hs_user_update($userId, static function (array &$u) use ($planId, $status, $serviceIds, $paidUntil, $notes, $fields): void {
        if ($planId !== '') {
            $u['plan'] = $planId;
        }
        if ($status !== '') {
            $u['subscription_status'] = $status;
            $u['active'] = $status !== 'suspended';
        }
        if (isset($fields['plan_services'])) {
            $u['plan_services'] = $serviceIds;
        }
        if ($paidUntil !== '') {
            $u['paid_until'] = gmdate('c', strtotime($paidUntil));
        } elseif (array_key_exists('paid_until', $fields) && $fields['paid_until'] === '') {
            $u['paid_until'] = null;
        }
        if (array_key_exists('admin_notes', $fields)) {
            $u['admin_notes'] = $notes;
        }
    });
    if ($ok && $planId !== '' && $planId !== $oldPlan && $oldUser !== null) {
        require_once __DIR__ . '/invoices.php';
        $lang = (string) ($fields['lang'] ?? 'uk');
        hs_invoice_from_event('plan_changed', hs_user_by_id($userId) ?? $oldUser, [
            'old_plan' => $oldPlan,
            'new_plan' => $planId,
            'lang' => $lang,
        ]);
    }
    return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'save'];
}

/** @return array<string, mixed> */
function hs_admin_client_row_data(array $user): array
{
    $uid = (string) ($user['id'] ?? '');
    $sites = hs_sites_for_user($uid);
    $res = hs_resource_usage($user, $sites);
    return [
        'user' => $user,
        'sites' => $sites,
        'resources' => $res,
        'site_count' => count($sites),
    ];
}

function hs_admin_client_status_label(array $t, string $status): string
{
    return match ($status) {
        'active' => (string) ($t['plan_status_active'] ?? 'Active'),
        'pending' => (string) ($t['plan_status_pending'] ?? 'Pending'),
        'suspended' => (string) ($t['plan_status_suspended'] ?? 'Suspended'),
        default => $status,
    };
}

function hs_admin_client_paid_class(?string $paidUntil): string
{
    if ($paidUntil === null || trim($paidUntil) === '') {
        return '';
    }
    $ts = strtotime($paidUntil);
    if ($ts === false) {
        return '';
    }
    $days = (int) floor(($ts - time()) / 86400);
    if ($days < 0) {
        return 'hs-clients-paid-expired';
    }
    if ($days <= 7) {
        return 'hs-clients-paid-urgent';
    }
    if ($days <= 30) {
        return 'hs-clients-paid-soon';
    }
    return '';
}

function hs_admin_client_matches_filters(array $user, string $q, string $filter): bool
{
    $status = (string) ($user['subscription_status'] ?? 'active');
    if ($filter !== 'all' && $status !== $filter) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    $hay = strtolower(implode(' ', [
        (string) ($user['username'] ?? ''),
        (string) ($user['email'] ?? ''),
        (string) ($user['name'] ?? ''),
        (string) ($user['plan'] ?? ''),
        (string) ($user['client_number'] ?? ''),
        (string) ($user['support_email'] ?? ''),
    ]));
    return str_contains($hay, strtolower($q));
}

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array<string,mixed>>
 */
function hs_admin_client_sort_rows(array $rows, string $sort): array
{
    $sort = $sort !== '' ? $sort : 'name';
    usort($rows, static function (array $a, array $b) use ($sort): int {
        $ua = $a['user'];
        $ub = $b['user'];
        $cmp = 0;
        switch ($sort) {
            case 'plan':
                $cmp = strcmp((string) ($ua['plan'] ?? ''), (string) ($ub['plan'] ?? ''));
                break;
            case 'paid':
                $ta = !empty($ua['paid_until']) ? strtotime((string) $ua['paid_until']) : 0;
                $tb = !empty($ub['paid_until']) ? strtotime((string) $ub['paid_until']) : 0;
                $cmp = $ta <=> $tb;
                break;
            case 'disk':
                $cmp = ((float) ($a['resources']['storage_used_mb'] ?? 0)) <=> ((float) ($b['resources']['storage_used_mb'] ?? 0));
                break;
            case 'sites':
                $cmp = ((int) ($a['site_count'] ?? 0)) <=> ((int) ($b['site_count'] ?? 0));
                break;
            case 'created':
                $ta = !empty($ua['created_at']) ? strtotime((string) $ua['created_at']) : 0;
                $tb = !empty($ub['created_at']) ? strtotime((string) $ub['created_at']) : 0;
                $cmp = $ta <=> $tb;
                break;
            default:
                $cmp = strcasecmp(
                    (string) ($ua['username'] ?? ''),
                    (string) ($ub['username'] ?? '')
                );
        }
        return $cmp;
    });
    return $rows;
}