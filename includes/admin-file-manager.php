<?php
declare(strict_types=1);

require_once __DIR__ . '/file-manager.php';
require_once __DIR__ . '/storage.php';

function hs_afm_cms_root(): string
{
    $root = dirname(__DIR__);
    return realpath($root) ?: $root;
}

function hs_afm_domain_root(): string
{
    $cms = hs_afm_cms_root();
    $parent = dirname($cms);
    if (is_dir($parent) && (basename($parent) === HS_PUBLIC_HTML || is_dir($parent . '/shop') || is_dir($parent . '/hosting'))) {
        return realpath($parent) ?: $parent;
    }

    return $cms;
}

function hs_afm_home_root(): string
{
    $domain = hs_afm_domain_root();
    $path = $domain;
    for ($i = 0; $i < 5; $i++) {
        $path = dirname($path);
        if ($path === '/' || $path === '' || $path === '.') {
            break;
        }
        if (is_dir($path . '/domains') || is_dir($path . '/.ssh') || is_dir($path . '/public_html')) {
            return realpath($path) ?: $path;
        }
    }

    return hs_afm_domain_root();
}

/**
 * Broadest filesystem root readable by the PHP process (full server for admin VPS files).
 * Falls back to the account home when open_basedir / permissions block "/".
 */
function hs_afm_server_root(): string
{
    if (@is_dir('/') && @is_readable('/')) {
        $rp = @realpath('/');
        if (is_string($rp) && $rp !== '') {
            $sample = @scandir($rp);
            if (is_array($sample) && $sample !== []) {
                return $rp;
            }
        }
    }
    // Windows / restricted hosts
    if (DIRECTORY_SEPARATOR === '\\') {
        $drive = getenv('SystemDrive') ?: 'C:';
        $driveRoot = rtrim((string) $drive, '\\/') . DIRECTORY_SEPARATOR;
        if (@is_dir($driveRoot) && @is_readable($driveRoot)) {
            $rp = @realpath($driveRoot);
            if (is_string($rp) && $rp !== '') {
                return $rp;
            }
        }
    }

    return hs_afm_home_root();
}

function hs_afm_is_broad_root(string $path): bool
{
    $norm = str_replace('\\', '/', rtrim($path, '/\\'));
    if ($norm === '' || $norm === '/' || preg_match('/^[A-Za-z]:$/', $norm) === 1) {
        return true;
    }

    return false;
}

/** @return array<string, array{id:string,label:string,path:string}> */
function hs_afm_scopes(array $t = []): array
{
    $scopes = [
        'server' => [
            'id' => 'server',
            'label' => $t['admin_files_scope_server'] ?? 'Entire server (all files)',
            'path' => hs_afm_server_root(),
        ],
        'clients' => [
            'id' => 'clients',
            'label' => $t['admin_files_scope_clients'] ?? 'Client sites (public_html/)',
            'path' => hs_public_path(''),
        ],
        'cms' => [
            'id' => 'cms',
            'label' => $t['admin_files_scope_cms'] ?? 'Hosting CMS',
            'path' => hs_afm_cms_root(),
        ],
        'domain' => [
            'id' => 'domain',
            'label' => $t['admin_files_scope_domain'] ?? 'Domain web root (bilohash.com)',
            'path' => hs_afm_domain_root(),
        ],
        'home' => [
            'id' => 'home',
            'label' => $t['admin_files_scope_home'] ?? 'VPS account home',
            'path' => hs_afm_home_root(),
        ],
    ];

    foreach (hs_users() as $user) {
        if (($user['subscription_status'] ?? '') !== 'active' && ($user['subscription_status'] ?? '') !== 'pending') {
            continue;
        }
        $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? '')) ?: '';
        if ($username === '') {
            continue;
        }
        $key = 'user:' . strtolower($username);
        $scopes[$key] = [
            'id' => $key,
            'label' => str_replace('{user}', $username, $t['admin_files_scope_user'] ?? 'Client {user}'),
            'path' => hs_public_path($username),
        ];
    }

    uasort($scopes, static function (array $a, array $b): int {
        static $order = ['server' => 0, 'home' => 1, 'domain' => 2, 'cms' => 3, 'clients' => 4];
        $oa = $order[$a['id']] ?? 100;
        $ob = $order[$b['id']] ?? 100;
        if ($oa !== $ob) {
            return $oa <=> $ob;
        }

        return strcasecmp($a['label'], $b['label']);
    });

    return $scopes;
}

function hs_afm_norm_scope(string $scope): string
{
    $scope = trim(strtolower($scope));
    if ($scope === '' || $scope === 'all' || $scope === 'root' || $scope === 'vps') {
        return 'server';
    }
    if ($scope === 'public_html') {
        return 'clients';
    }
    if (preg_match('/^user:[a-z0-9_-]+$/', $scope) === 1) {
        return $scope;
    }
    if (in_array($scope, ['server', 'clients', 'cms', 'domain', 'home'], true)) {
        return $scope;
    }

    return 'server';
}

function hs_afm_scope_root(string $scope): string
{
    $scope = hs_afm_norm_scope($scope);
    $scopes = hs_afm_scopes();
    if (!isset($scopes[$scope])) {
        return hs_afm_server_root();
    }
    $path = (string) ($scopes[$scope]['path'] ?? '');
    // Never mkdir filesystem root / broad drives
    if (!is_dir($path) && !hs_afm_is_broad_root($path) && $scope !== 'server') {
        @mkdir($path, 0755, true);
    }

    $real = @realpath($path);

    return is_string($real) && $real !== '' ? $real : $path;
}

function hs_afm_scope_label(string $scope, array $t = []): string
{
    $scope = hs_afm_norm_scope($scope);
    $scopes = hs_afm_scopes($t);

    return (string) ($scopes[$scope]['label'] ?? $scope);
}

function hs_afm_begin(string $scope): array
{
    $scope = hs_afm_norm_scope($scope);
    $root = hs_afm_scope_root($scope);
    $GLOBALS['hs_fm_override_root'] = $root;
    $GLOBALS['hs_fm_admin_mode'] = true;

    return [
        'id' => 'admin',
        'username' => 'admin',
        'subscription_status' => 'active',
    ];
}

function hs_afm_end(): void
{
    unset($GLOBALS['hs_fm_override_root'], $GLOBALS['hs_fm_admin_mode']);
}

function hs_render_admin_files_panel(array $t, string $scope, string $rel = ''): string
{
    $scope = hs_afm_norm_scope($scope);
    $rel = hs_fm_norm_rel($rel);
    $scopeLabel = hs_afm_scope_label($scope, $t);
    $scopes = hs_afm_scopes($t);

    $scopeOptions = '';
    foreach ($scopes as $item) {
        $selected = $item['id'] === $scope ? ' selected' : '';
        $scopeOptions .= '<option value="' . hs_h($item['id']) . '"' . $selected . '>' . hs_h($item['label']) . '</option>';
    }

    $i18n = [
        'fm_name' => $t['fm_name'] ?? 'Name',
        'fm_size' => $t['fm_size'] ?? 'Size',
        'fm_modified' => $t['fm_modified'] ?? 'Modified',
        'fm_perms' => $t['fm_perms'] ?? 'Permissions',
        'fm_empty' => $t['fm_empty'] ?? 'This folder is empty',
        'fm_folder_name' => $t['fm_folder_name'] ?? 'Folder name',
        'fm_file_name' => $t['fm_file_name'] ?? 'File name',
        'fm_new_folder' => $t['fm_new_folder'] ?? 'New folder',
        'fm_new_file' => $t['fm_new_file'] ?? 'New file',
        'fm_upload' => $t['fm_upload'] ?? 'Upload',
        'fm_refresh' => $t['fm_refresh'] ?? 'Refresh',
        'fm_search' => $t['fm_search'] ?? 'Search in folder…',
        'fm_download' => $t['fm_download'] ?? 'Download',
        'fm_rename' => $t['fm_rename'] ?? 'Rename',
        'fm_delete' => $t['fm_delete'] ?? 'Delete',
        'fm_duplicate' => $t['fm_duplicate'] ?? 'Duplicate',
        'fm_chmod' => $t['fm_chmod'] ?? 'Permissions',
        'fm_save' => $t['fm_save'] ?? 'Save',
        'fm_close' => $t['fm_close'] ?? 'Close',
        'fm_close_tab' => $t['fm_close_tab'] ?? 'Close tab',
        'fm_saved' => $t['fm_saved'] ?? 'Saved',
        'fm_created' => $t['fm_created'] ?? 'Created',
        'fm_deleted' => $t['fm_deleted'] ?? 'Deleted',
        'fm_renamed' => $t['fm_renamed'] ?? 'Renamed',
        'fm_duplicated' => $t['fm_duplicated'] ?? 'Duplicated',
        'fm_uploaded' => $t['fm_uploaded'] ?? 'Upload complete',
        'fm_uploading' => $t['fm_uploading'] ?? 'Uploading…',
        'fm_error' => $t['fm_error'] ?? 'Action failed',
        'fm_binary' => $t['fm_binary'] ?? 'Binary file — download to edit',
        'fm_too_large' => $t['fm_too_large'] ?? 'File too large to edit (max 2 MB)',
        'fm_confirm_delete' => $t['fm_confirm_delete'] ?? 'Delete this item?',
        'fm_confirm_delete_title' => $t['fm_confirm_delete_title'] ?? 'Confirm delete',
        'fm_rename_title' => $t['fm_rename_title'] ?? 'Rename',
        'fm_new_folder_title' => $t['fm_new_folder_title'] ?? 'New folder',
        'fm_new_file_title' => $t['fm_new_file_title'] ?? 'New file',
        'fm_chmod_title' => $t['fm_chmod_title'] ?? 'Change permissions',
        'fm_rename_prompt' => $t['fm_rename_prompt'] ?? 'New name',
        'fm_unsaved' => $t['fm_unsaved'] ?? 'Discard unsaved changes?',
        'fm_disk' => $t['fm_disk'] ?? 'Disk used',
        'fm_drop' => $t['fm_drop'] ?? 'Drop files to upload',
        'fm_shortcuts' => $t['fm_shortcuts'] ?? 'Ctrl+S save · Ctrl+W close tab',
        'fm_cancel' => $t['fm_cancel'] ?? 'Cancel',
        'fm_ok' => $t['fm_ok'] ?? 'OK',
        'fm_create' => $t['fm_create'] ?? 'Create',
        'fm_preview' => $t['fm_preview'] ?? 'Preview',
        'fm_editor' => $t['fm_editor'] ?? 'Editor',
        'fm_no_preview' => $t['fm_no_preview'] ?? 'No preview for this file type',
        'fm_chmod_hint' => $t['fm_chmod_hint'] ?? 'e.g. 0644 for files, 0755 for folders',
        'fm_archive' => $t['fm_archive'] ?? 'Create ZIP archive',
        'fm_archive_title' => $t['fm_archive_title'] ?? 'Archive name',
        'fm_archived' => $t['fm_archived'] ?? 'Archive created',
        'fm_archive_too_large' => $t['fm_archive_too_large'] ?? 'Content too large (max 100 MB)',
        'fm_extract' => $t['fm_extract'] ?? 'Extract ZIP',
        'fm_extract_title' => $t['fm_extract_title'] ?? 'Extract archive',
        'fm_confirm_extract' => $t['fm_confirm_extract'] ?? 'Extract this ZIP archive to a new folder?',
        'fm_extracted' => $t['fm_extracted'] ?? 'Archive extracted',
        'fm_not_archive' => $t['fm_not_archive'] ?? 'Only .zip archives can be extracted',
    ];

    $vpsIp = function_exists('hs_vps_server_ip') ? hs_vps_server_ip() : '';
    $hint = $t['admin_files_hint'] ?? 'All server files visible to the PHP process (super-admin). Includes config and credentials.';
    $scopeTitle = $t['admin_files_scope_label'] ?? 'Scope';

    $toolbar = '<div class="hs-admin-files-bar">'
        . '<label class="hs-admin-files-scope"><span>' . hs_h($scopeTitle) . '</span>'
        . '<select id="hs-admin-files-scope" data-admin-files-scope>' . $scopeOptions . '</select></label>'
        . ($vpsIp !== '' ? '<span class="hp-muted"><i class="fa-solid fa-server"></i> VPS ' . hs_h($vpsIp) . '</span>' : '')
        . '</div>'
        . '<p class="hp-muted hs-admin-files-hint"><i class="fa-solid fa-shield-halved"></i> ' . hs_h($hint) . '</p>';

    $html = $toolbar . '<div id="hs-file-manager" class="hs-fm" data-fm-drop>'
        . '<div class="hs-fm-toolbar">'
        . '<div class="hs-fm-toolbar-left">'
        . '<button type="button" class="hs-fm-tool" data-fm-action="mkdir" title="' . hs_h($i18n['fm_new_folder']) . '"><i class="fa-solid fa-folder-plus"></i><span>' . hs_h($i18n['fm_new_folder']) . '</span></button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="newfile" title="' . hs_h($i18n['fm_new_file']) . '"><i class="fa-solid fa-file-circle-plus"></i><span>' . hs_h($i18n['fm_new_file']) . '</span></button>'
        . '<button type="button" class="hs-fm-tool hs-fm-tool-primary" data-fm-action="upload"><i class="fa-solid fa-cloud-arrow-up"></i><span>' . hs_h($i18n['fm_upload']) . '</span></button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="refresh" title="' . hs_h($i18n['fm_refresh']) . '"><i class="fa-solid fa-rotate"></i></button>'
        . '<input type="file" data-fm-file-input multiple hidden>'
        . '</div>'
        . '<div class="hs-fm-toolbar-right">'
        . '<input type="search" class="hs-fm-search" data-fm-search placeholder="' . hs_h($i18n['fm_search']) . '" autocomplete="off">'
        . '<button type="button" class="hs-fm-view-btn" data-fm-action="view-list" data-fm-view-list title="List"><i class="fa-solid fa-list"></i></button>'
        . '<button type="button" class="hs-fm-view-btn" data-fm-action="view-grid" data-fm-view-grid title="Grid"><i class="fa-solid fa-grip"></i></button>'
        . '<span class="hs-fm-disk" data-fm-disk></span>'
        . '</div></div>'
        . '<div class="hs-fm-upload-bar" data-fm-upload-bar hidden><div class="hs-fm-upload-fill" data-fm-upload-fill></div><span data-fm-upload-label></span></div>'
        . '<div class="hs-fm-body">'
        . '<aside class="hs-fm-sidebar" data-fm-sidebar><div class="hs-fm-sidebar-title"><i class="fa-solid fa-sitemap"></i> ' . hs_h($t['fm_tree'] ?? 'Folders') . '</div><div class="hs-fm-tree-wrap" data-fm-tree></div></aside>'
        . '<div class="hs-fm-resizer" data-fm-resizer-sidebar aria-hidden="true"></div>'
        . '<div class="hs-fm-main" data-fm-drop>'
        . '<div class="hs-fm-bc" data-fm-bc></div>'
        . '<div class="hs-fm-drop-hint"><i class="fa-solid fa-cloud-arrow-up"></i> ' . hs_h($i18n['fm_drop']) . '</div>'
        . '<div class="hs-fm-workspace" data-fm-workspace>'
        . '<div class="hs-fm-list-pane" data-fm-list-pane><div class="hs-fm-list-wrap" data-fm-list></div></div>'
        . '<div class="hs-fm-split-resizer" data-fm-resizer-split hidden aria-hidden="true"></div>'
        . '<div class="hs-fm-pane" data-fm-pane hidden>'
        . '<div class="hs-fm-tabs" data-fm-tabs></div>'
        . '<div class="hs-fm-pane-toolbar">'
        . '<span class="hs-fm-hint" data-fm-pane-hint>' . hs_h($i18n['fm_shortcuts']) . '</span>'
        . '<button type="button" class="hs-fm-tool hs-fm-tool-save" data-fm-action="save" data-fm-save disabled><i class="fa-solid fa-floppy-disk"></i> ' . hs_h($i18n['fm_save']) . '</button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="chmod" data-fm-chmod-btn hidden><i class="fa-solid fa-lock"></i> ' . hs_h($i18n['fm_chmod']) . '</button>'
        . '<button type="button" class="hs-fm-tool" data-fm-action="close-pane"><i class="fa-solid fa-xmark"></i></button>'
        . '</div>'
        . '<div class="hs-fm-editor-mount" data-fm-editor></div>'
        . '<div class="hs-fm-preview" data-fm-preview hidden></div>'
        . '</div></div></div></div>'
        . '<div class="hs-fm-ctx" data-fm-ctx hidden></div>'
        . '<div class="hs-fm-modal-backdrop" data-fm-modal-backdrop hidden>'
        . '<div class="hs-fm-modal" role="dialog" aria-modal="true"><h3 data-fm-modal-title></h3>'
        . '<input type="text" class="hs-fm-modal-input" data-fm-modal-input autocomplete="off">'
        . '<p class="hs-fm-modal-msg" data-fm-modal-msg hidden></p>'
        . '<div class="hs-fm-modal-actions">'
        . '<button type="button" class="hs-fm-tool" data-fm-modal-cancel>' . hs_h($i18n['fm_cancel']) . '</button>'
        . '<button type="button" class="hs-fm-tool hs-fm-tool-primary" data-fm-modal-ok>' . hs_h($i18n['fm_ok']) . '</button>'
        . '</div></div></div>'
        . '<div class="hs-fm-toast" data-fm-toast></div>'
        . '</div>'
        . '<script>window.HS_FM=' . json_encode([
            'api' => hs_admin_url('files-api.php'),
            'csrf' => hs_csrf_token(),
            'scope' => $scope,
            'rootLabel' => $scopeLabel,
            'startPath' => $rel,
            'i18n' => $i18n,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>'
        . '<script>(function(){var sel=document.querySelector("[data-admin-files-scope]");if(!sel)return;sel.addEventListener("change",function(){var u=new URL(window.location.href);u.searchParams.set("scope",sel.value);u.searchParams.delete("path");window.location.href=u.toString();});})();</script>';

    return $html;
}