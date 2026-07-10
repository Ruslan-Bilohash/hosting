<?php
declare(strict_types=1);

const HS_BACKUP_MAX_BYTES = 524288000; // 500 MB per archive
const HS_BACKUP_CRON_JOB_ID = 'hs_backup_auto';

/** @return array<string, array{cron:string,retention:int,label_key:string}> */
function hs_backup_frequencies(): array
{
    return [
        'day' => ['cron' => '0 3 * * *', 'retention' => 7, 'label_key' => 'backup_freq_day'],
        'week' => ['cron' => '0 3 * * 0', 'retention' => 4, 'label_key' => 'backup_freq_week'],
        'month' => ['cron' => '0 3 1 * *', 'retention' => 12, 'label_key' => 'backup_freq_month'],
        'year' => ['cron' => '0 3 1 1 *', 'retention' => 5, 'label_key' => 'backup_freq_year'],
        'off' => ['cron' => '', 'retention' => 0, 'label_key' => 'backup_freq_off'],
    ];
}

function hs_backup_schedule_label(string $freq, array $t): string
{
    $key = hs_backup_frequencies()[$freq]['label_key'] ?? 'backup_freq_day';
    return (string) ($t[$key] ?? $freq);
}

function hs_backup_user_dir(array $user): string
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    return hs_public_path($username);
}

function hs_backup_storage_dir(array $user): string
{
    return rtrim(hs_backup_user_dir($user), '/\\') . '/backups';
}

function hs_backup_ensure_token(string $userId): string
{
    $settings = hs_user_settings_get($userId);
    $token = trim((string) ($settings['backup_cron_token'] ?? ''));
    if ($token !== '') {
        return $token;
    }
    $token = bin2hex(random_bytes(16));
    hs_user_settings_save($userId, ['backup_cron_token' => $token]);
    return $token;
}

function hs_backup_cron_url(array $user, string $token): string
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    return hs_canonical_url(hs_panel_path('backup-cron.php'))
        . '?user=' . rawurlencode($username)
        . '&token=' . rawurlencode($token);
}

function hs_backup_cron_command(array $user, string $token): string
{
    $url = hs_backup_cron_url($user, $token);
    return 'curl -fsS ' . escapeshellarg($url) . ' >/dev/null 2>&1';
}

function hs_backup_sync_cron(string $userId, array $user): void
{
    $settings = hs_user_settings_get($userId);
    $freq = (string) ($settings['backup_schedule'] ?? 'day');
    $auto = !empty($settings['backup_auto']);
    $jobs = is_array($settings['cron_jobs'] ?? null) ? $settings['cron_jobs'] : [];
    $jobs = array_values(array_filter($jobs, static fn($j) => is_array($j) && ($j['id'] ?? '') !== HS_BACKUP_CRON_JOB_ID));

    if ($auto && $freq !== 'off') {
        $meta = hs_backup_frequencies()[$freq] ?? hs_backup_frequencies()['day'];
        $token = hs_backup_ensure_token($userId);
        $jobs[] = [
            'id' => HS_BACKUP_CRON_JOB_ID,
            'type' => 'backup',
            'schedule' => $meta['cron'],
            'command' => hs_backup_cron_command($user, $token),
            'created_at' => gmdate('c'),
            'label' => 'Hosting CMS auto backup',
        ];
    }

    hs_user_settings_save($userId, ['cron_jobs' => $jobs]);
}

function hs_backup_should_skip_path(string $relPath): bool
{
    $relPath = str_replace('\\', '/', trim($relPath, '/'));
    if ($relPath === 'backups' || str_starts_with($relPath, 'backups/')) {
        return true;
    }
    return false;
}

function hs_backup_zip_add_tree(ZipArchive $zip, string $dir, string $zipPrefix, int &$totalBytes): bool
{
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $full = $dir . '/' . $name;
        $rel = ($zipPrefix !== '' ? $zipPrefix . '/' : '') . $name;
        if (hs_backup_should_skip_path($rel)) {
            continue;
        }
        if (is_link($full)) {
            continue;
        }
        if (is_dir($full)) {
            $zip->addEmptyDir($rel . '/');
            if (!hs_backup_zip_add_tree($zip, $full, $rel, $totalBytes)) {
                return false;
            }
        } else {
            $sz = (int) filesize($full);
            $totalBytes += $sz;
            if ($totalBytes > HS_BACKUP_MAX_BYTES) {
                return false;
            }
            if (!$zip->addFile($full, $rel)) {
                return false;
            }
        }
    }
    return true;
}

/** @return array{ok:bool,name?:string,error?:string,size_mb?:float} */
function hs_create_user_backup(array $user, string $source = 'manual'): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'zip_missing'];
    }
    $userId = (string) ($user['id'] ?? '');
    $base = hs_backup_user_dir($user);
    if (!is_dir($base)) {
        return ['ok' => false, 'error' => 'no_files'];
    }

    $name = 'backup-' . gmdate('Y-m-d-His');
    $backupDir = hs_backup_storage_dir($user);
    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
        return ['ok' => false, 'error' => 'mkdir'];
    }

    $zipPath = $backupDir . '/' . $name . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['ok' => false, 'error' => 'zip_open'];
    }

    $prevLimit = (int) ini_get('max_execution_time');
    @set_time_limit(300);

    $totalBytes = 0;
    $ok = hs_backup_zip_add_tree($zip, $base, '', $totalBytes);
    $zip->close();
    @set_time_limit($prevLimit > 0 ? $prevLimit : 0);

    if (!$ok || $totalBytes > HS_BACKUP_MAX_BYTES) {
        @unlink($zipPath);
        return ['ok' => false, 'error' => $totalBytes > HS_BACKUP_MAX_BYTES ? 'too_large' : 'zip_add'];
    }

    $sizeMb = round(filesize($zipPath) / 1024 / 1024, 2);
    $settings = hs_user_settings_get($userId);
    $backups = is_array($settings['backups'] ?? null) ? $settings['backups'] : [];
    $backups[] = [
        'id' => 'bk_' . bin2hex(random_bytes(4)),
        'name' => $name,
        'file' => $name . '.zip',
        'size_mb' => $sizeMb,
        'created_at' => gmdate('c'),
        'path' => 'backups/' . $name . '.zip',
        'type' => 'zip',
        'source' => $source,
    ];

    $freq = (string) ($settings['backup_schedule'] ?? 'day');
    $retention = (int) ((hs_backup_frequencies()[$freq] ?? [])['retention'] ?? 7);
    if ($retention < 1) {
        $retention = 7;
    }
    $backups = hs_backup_prune_list($backups, $retention, $backupDir);

    hs_user_settings_save($userId, ['backups' => $backups]);

    return ['ok' => true, 'name' => $name, 'size_mb' => $sizeMb];
}

/** @param list<array<string,mixed>> $backups */
function hs_backup_prune_list(array $backups, int $retention, string $backupDir): array
{
    if (count($backups) <= $retention) {
        return $backups;
    }
    $sorted = $backups;
    usort($sorted, static fn($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    $keep = array_slice($sorted, 0, $retention);
    $drop = array_slice($sorted, $retention);
    foreach ($drop as $old) {
        $file = (string) ($old['file'] ?? '');
        if ($file !== '' && str_ends_with(strtolower($file), '.zip')) {
            $path = $backupDir . '/' . basename($file);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $json = $backupDir . '/' . preg_replace('/\.zip$/i', '', basename($file)) . '.json';
        if (is_file($json)) {
            @unlink($json);
        }
    }
    return array_values($keep);
}

function hs_backup_download_path(array $user, string $file): ?string
{
    $file = basename($file);
    if (!preg_match('/^backup-[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{6}\.zip$/', $file)) {
        return null;
    }
    $settings = hs_user_settings_get((string) ($user['id'] ?? ''));
    $backups = is_array($settings['backups'] ?? null) ? $settings['backups'] : [];
    $allowed = false;
    foreach ($backups as $b) {
        if (is_array($b) && ($b['file'] ?? '') === $file) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        return null;
    }
    $path = hs_backup_storage_dir($user) . '/' . $file;
    return is_file($path) ? $path : null;
}

/** @return array{ok:bool,error?:string} */
function hs_backup_run_cron(string $username, string $token): array
{
    $username = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $username) ?? '');
    if ($username === '' || $token === '') {
        return ['ok' => false, 'error' => 'auth'];
    }
    $user = hs_user_by_login($username);
    if ($user === null) {
        return ['ok' => false, 'error' => 'user'];
    }
    $userId = (string) ($user['id'] ?? '');
    $settings = hs_user_settings_get($userId);
    if (empty($settings['backup_auto'])) {
        return ['ok' => false, 'error' => 'disabled'];
    }
    $expected = (string) ($settings['backup_cron_token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $token)) {
        return ['ok' => false, 'error' => 'token'];
    }
    if (($user['subscription_status'] ?? '') !== 'active') {
        return ['ok' => false, 'error' => 'inactive'];
    }
    $res = hs_create_user_backup($user, 'cron');
    if (!$res['ok']) {
        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'failed')];
    }
    if (function_exists('hs_panel_log')) {
        require_once __DIR__ . '/panel-features.php';
        hs_panel_log($userId, 'backup_cron', (string) ($res['name'] ?? ''));
    }
    return ['ok' => true];
}