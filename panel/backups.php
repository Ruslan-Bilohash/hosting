<?php
declare(strict_types=1);

$panel_active = 'backups';
require dirname(__DIR__) . '/includes/panel-bootstrap.php';
require_once dirname(__DIR__) . '/includes/panel-features.php';
require_once dirname(__DIR__) . '/includes/backups.php';

$page_title = $t['dash_backups'] ?? 'Backups';
$panel_tip_key = 'advanced';

$userId = (string) ($user['id'] ?? '');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hs_csrf_verify($_POST['csrf'] ?? null)) {
        $error = $t['register_error_csrf'] ?? 'CSRF';
    } elseif (isset($_POST['create_backup'])) {
        $res = hs_create_user_backup($user, 'manual');
        if ($res['ok']) {
            $success = $t['backup_created'] ?? 'Backup created';
            hs_panel_log($userId, 'backup_create', (string) ($res['name'] ?? ''));
        } else {
            $error = match ($res['error'] ?? '') {
                'zip_missing' => $t['backup_zip_missing'] ?? 'ZIP not available',
                'too_large' => $t['backup_too_large'] ?? 'Too large',
                'no_files' => $t['backup_no_files'] ?? 'No files',
                default => $t['backup_failed'] ?? 'Backup failed',
            };
        }
    } elseif (isset($_POST['save_backup_settings'])) {
        $freq = (string) ($_POST['backup_schedule'] ?? 'day');
        if (!isset(hs_backup_frequencies()[$freq])) {
            $freq = 'day';
        }
        hs_user_settings_save($userId, [
            'backup_schedule' => $freq,
            'backup_auto' => !empty($_POST['backup_auto']),
        ]);
        hs_backup_sync_cron($userId, $user);
        $success = $t['backup_settings_saved'] ?? 'Settings saved';
        hs_panel_log($userId, 'backup_settings', $freq);
    }
    $hs_user_settings = hs_user_settings_get($userId);
}

$s = $hs_user_settings;
$backups = is_array($s['backups'] ?? null) ? array_reverse($s['backups']) : [];
$schedule = (string) ($s['backup_schedule'] ?? 'day');
$auto = !empty($s['backup_auto']);
$freqMeta = hs_backup_frequencies()[$schedule] ?? hs_backup_frequencies()['day'];
$cronToken = $auto ? hs_backup_ensure_token($userId) : (string) ($s['backup_cron_token'] ?? '');
$cronUrl = $auto && $cronToken !== '' ? hs_backup_cron_url($user, $cronToken) : '';
$cronCmd = $auto && $cronToken !== '' ? hs_backup_cron_command($user, $cronToken) : '';

$scheduleOptions = '';
foreach (hs_backup_frequencies() as $key => $meta) {
    if ($key === 'off') {
        continue;
    }
    $scheduleOptions .= '<option value="' . hs_h($key) . '"' . ($schedule === $key ? ' selected' : '') . '>'
        . hs_h(hs_backup_schedule_label($key, $t)) . '</option>';
}

$backupRows = '';
foreach ($backups as $b) {
    if (!is_array($b)) {
        continue;
    }
    $file = (string) ($b['file'] ?? '');
    $dl = $file !== '' && str_ends_with(strtolower($file), '.zip')
        ? '<a href="' . hs_h(hs_url(hs_panel_path('backup-download.php') . '?file=' . rawurlencode($file))) . '" class="hs-btn hs-btn-ghost hp-dash-btn-sm"><i class="fa-solid fa-download"></i> ' . hs_h($t['backup_download'] ?? 'Download') . '</a>'
        : '';
    $src = (string) ($b['source'] ?? 'manual');
    $srcLabel = $src === 'cron' ? ($t['backup_source_cron'] ?? 'auto') : ($t['backup_source_manual'] ?? 'manual');
    $backupRows .= '<tr><td><code>' . hs_h((string) ($b['name'] ?? '')) . '</code></td><td>'
        . hs_h((string) ($b['size_mb'] ?? '')) . ' MB</td><td>' . hs_h(hs_format_date((string) ($b['created_at'] ?? ''))) . '</td><td>'
        . hs_h($srcLabel) . '</td><td>' . $dl . '</td></tr>';
}

ob_start();
?>
<?php if ($success !== ''): ?><div class="hs-alert hs-alert-success"><?= hs_h($success) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="hs-alert hs-alert-error"><?= hs_h($error) ?></div><?php endif; ?>

<?= hs_render_card(
    $t['backup_schedule_title'] ?? 'Schedule',
    '<form method="post" class="hp-stack">' . hs_csrf_field()
    . '<div class="hs-field"><label>' . hs_h($t['dash_backups_schedule'] ?? 'Frequency') . '</label>'
    . '<select name="backup_schedule">' . $scheduleOptions . '</select></div>'
    . '<label class="hp-check" style="display:flex;align-items:center;gap:.5rem;margin:.75rem 0">'
    . '<input type="checkbox" name="backup_auto" value="1"' . ($auto ? ' checked' : '') . '>'
    . hs_h($t['backup_auto_enable'] ?? 'Enable automatic backups (cron)') . '</label>'
    . '<p class="hp-muted" style="font-size:.85rem">' . hs_h($t['backup_retention_hint'] ?? '') . ' '
    . hs_h((string) $freqMeta['retention']) . ' ' . hs_h($t['dash_backups_days'] ?? 'copies') . '</p>'
    . '<button type="submit" name="save_backup_settings" value="1" class="hs-btn hs-btn-primary">' . hs_h($t['btn_save'] ?? 'Save') . '</button></form>'
    . ($auto ? '<div style="margin-top:1.25rem"><p class="hp-muted" style="font-size:.85rem"><strong>' . hs_h($t['backup_cron_title'] ?? 'Cron command') . '</strong></p>'
        . '<pre style="background:#f1f5f9;padding:.75rem;border-radius:8px;font-size:.8rem;overflow:auto">' . hs_h($cronCmd) . '</pre>'
        . '<p class="hp-muted" style="font-size:.8rem">' . hs_h($t['backup_cron_hint'] ?? '') . '</p></div>' : '')
) ?>

<?= hs_render_card(
    $t['dash_backups'] ?? 'Backups',
    '<p class="hp-muted">' . hs_h($t['backup_zip_hint'] ?? 'Creates a ZIP archive of your public_html folder (excluding backups/).') . '</p>'
    . ($backupRows === '' ? '<p class="hp-muted" style="margin-top:1rem">' . hs_h($t['dash_backups_hint'] ?? '') . '</p>'
        : '<div class="hs-table-wrap" style="margin-top:1rem"><table class="hs-table"><thead><tr>'
          . '<th>' . hs_h($t['backup_col_name'] ?? 'Name') . '</th><th>' . hs_h($t['fm_size'] ?? 'Size') . '</th><th>'
          . hs_h($t['fm_modified'] ?? 'Created') . '</th><th>' . hs_h($t['backup_col_source'] ?? 'Source') . '</th><th></th></tr></thead><tbody>'
          . $backupRows . '</tbody></table></div>'),
    '<form method="post" style="display:inline">' . hs_csrf_field()
    . '<button type="submit" name="create_backup" value="1" class="hs-btn hs-btn-primary"><i class="fa-solid fa-file-zipper"></i> '
    . hs_h($t['backup_create'] ?? 'Create backup') . '</button></form>'
    . ' <a href="' . hs_h(hs_url(hs_panel_path('files.php'))) . '" class="hs-btn hs-btn-ghost">' . hs_h($t['dash_files'] ?? '') . '</a>'
) ?>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/includes/layout-panel.php';