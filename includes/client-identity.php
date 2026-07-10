<?php
declare(strict_types=1);

function hs_client_counter_file(): string
{
    return hs_data_file('client-counter');
}

function hs_client_number_next(): string
{
    if (hs_is_mysql_installed()) {
        require_once __DIR__ . '/db-migrate.php';
        $counter = hs_db_meta_get_array(HS_DB_META_CLIENT_COUNTER, ['seq' => 0]);
        $seq = (int) ($counter['seq'] ?? 0) + 1;
        hs_db_meta_set_array(HS_DB_META_CLIENT_COUNTER, ['seq' => $seq]);
    } else {
        $counter = hs_read_json(hs_client_counter_file());
        $seq = (int) ($counter['seq'] ?? 0) + 1;
        hs_write_json(hs_client_counter_file(), ['seq' => $seq]);
    }
    return sprintf('BH-CL-%05d', $seq);
}

function hs_client_support_email_for_username(string $username): string
{
    $local = strtolower(preg_replace('/[^a-z0-9_-]/', '', $username) ?: 'user');
    return $local . '@clients.' . hs_default_primary_domain();
}

/** @param array<string,mixed> $user */
function hs_client_support_email(array $user): string
{
    $stored = strtolower(trim((string) ($user['support_email'] ?? '')));
    if ($stored !== '' && filter_var($stored, FILTER_VALIDATE_EMAIL)) {
        return $stored;
    }
    return hs_client_support_email_for_username((string) ($user['username'] ?? 'user'));
}

/** @param array<string,mixed> $user */
function hs_client_number(array $user): string
{
    return trim((string) ($user['client_number'] ?? ''));
}

/** @param array<string,mixed> $user */
function hs_client_assign_identity_fields(array $user): array
{
    if (hs_client_number($user) === '') {
        $user['client_number'] = hs_client_number_next();
    }
    if (trim((string) ($user['support_email'] ?? '')) === '') {
        $user['support_email'] = hs_client_support_email_for_username((string) ($user['username'] ?? 'user'));
    }
    return $user;
}

/** Ensure client_number + support_email exist; persists when missing. */
function hs_client_ensure_identity(array $user): array
{
    $userId = (string) ($user['id'] ?? '');
    if ($userId === '') {
        return $user;
    }
    $needs = hs_client_number($user) === '' || trim((string) ($user['support_email'] ?? '')) === '';
    if (!$needs) {
        return $user;
    }
    $updated = hs_client_assign_identity_fields($user);
    $ok = hs_user_update($userId, static function (array &$u) use ($updated): void {
        if (hs_client_number($u) === '') {
            $u['client_number'] = (string) ($updated['client_number'] ?? '');
        }
        if (trim((string) ($u['support_email'] ?? '')) === '') {
            $u['support_email'] = (string) ($updated['support_email'] ?? '');
        }
    });
    if ($ok) {
        return hs_user_by_id($userId) ?? $updated;
    }
    return $updated;
}

/** Assign numbers to all users missing client_number (e.g. after upgrade). */
function hs_client_identity_migrate_all(): void
{
    foreach (hs_users() as $user) {
        if (!is_array($user)) {
            continue;
        }
        if (hs_client_number($user) === '' || trim((string) ($user['support_email'] ?? '')) === '') {
            hs_client_ensure_identity($user);
        }
    }
}