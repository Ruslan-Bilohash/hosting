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