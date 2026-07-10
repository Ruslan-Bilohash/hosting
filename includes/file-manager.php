<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/installer.php';
require_once __DIR__ . '/user-settings.php';

const HS_FM_MAX_READ = 2097152; // 2 MB
const HS_FM_MAX_SEARCH = 80;
const HS_FM_MAX_ARCHIVE_BYTES = 104857600; // 100 MB

function hs_fm_user_root(array $user): string
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', (string) ($user['username'] ?? 'user')) ?: 'user';
    $root = hs_public_path($username);
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }
    return realpath($root) ?: $root;
}

function hs_fm_norm_rel(string $rel): string
{
    $rel = str_replace('\\', '/', trim($rel));
    $rel = trim($rel, '/');
    if (str_contains($rel, '..')) {
        return '';
    }
    return $rel;
}

/** @return list<string> */
function hs_fm_sensitive_basenames(): array
{
    return [
        'config.php',
        'wp-config.php',
        'credentials.php',
        'secrets.php',
        '.env',
        '.env.local',
        '.env.production',
    ];
}

function hs_fm_is_sensitive_name(string $name): bool
{
    $lower = strtolower(basename($name));
    foreach (hs_fm_sensitive_basenames() as $blocked) {
        if ($lower === strtolower($blocked)) {
            return true;
        }
    }
    if (preg_match('/^\.?env(\.|$)/', $lower) === 1) {
        return true;
    }
    if (preg_match('/\.(pem|key|p12|pfx)$/', $lower) === 1) {
        return true;
    }

    return false;
}

function hs_fm_is_sensitive_rel(string $rel): bool
{
    $rel = hs_fm_norm_rel($rel);
    if ($rel === '') {
        return false;
    }
    foreach (explode('/', $rel) as $part) {
        if ($part !== '' && hs_fm_is_sensitive_name($part)) {
            return true;
        }
    }

    return false;
}

function hs_fm_resolve(array $user, string $rel): ?string
{
    $root = hs_fm_user_root($user);
    $rel = hs_fm_norm_rel($rel);
    return hs_safe_path($root, $rel) ?? ($rel === '' ? $root : null);
}

function hs_fm_rel_from_abs(string $root, string $abs): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $abs = str_replace('\\', '/', $abs);
    if (!str_starts_with($abs, $root)) {
        return '';
    }
    return ltrim(substr($abs, strlen($root)), '/');
}

function hs_fm_is_editable(string $name): bool
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    static $exts = [
        'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'txt', 'md', 'sql', 'htaccess',
        'env', 'ini', 'yml', 'yaml', 'svg', 'log', 'sh', 'bash', 'twig', 'vue', 'ts', 'jsx', 'tsx',
    ];
    if ($name === '.htaccess' || $name === 'wp-config.php') {
        return true;
    }
    return $ext === '' || in_array($ext, $exts, true);
}

function hs_fm_icon(string $name, bool $dir): string
{
    if ($dir) {
        return 'folder';
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match ($ext) {
        'php' => 'php',
        'js', 'jsx', 'ts', 'tsx' => 'js',
        'css', 'scss', 'less' => 'css',
        'html', 'htm' => 'html',
        'json' => 'json',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico' => 'image',
        'zip', 'gz', 'tar', 'rar', '7z' => 'archive',
        'sql' => 'database',
        'md', 'txt', 'log' => 'text',
        default => 'file',
    };
}

function hs_fm_language(string $name): string
{
    if ($name === '.htaccess' || $name === 'wp-config.php') {
        return 'php';
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match ($ext) {
        'php' => 'php',
        'js', 'mjs', 'cjs', 'jsx' => 'javascript',
        'ts', 'tsx' => 'typescript',
        'css', 'scss', 'less' => 'css',
        'html', 'htm', 'vue', 'twig' => 'html',
        'json' => 'json',
        'xml', 'svg' => 'xml',
        'sql' => 'sql',
        'md' => 'markdown',
        'yml', 'yaml' => 'yaml',
        'sh', 'bash' => 'shell',
        'env', 'ini' => 'ini',
        default => 'plaintext',
    };
}

function hs_fm_mime(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match ($ext) {
        'php' => 'text/x-php',
        'js' => 'text/javascript',
        'css' => 'text/css',
        'html', 'htm' => 'text/html',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'svg' => 'image/svg+xml',
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'zip' => 'application/zip',
        'pdf' => 'application/pdf',
        default => 'application/octet-stream',
    };
}

function hs_fm_is_image(string $name): bool
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'], true);
}

function hs_fm_is_zip_archive(string $name): bool
{
    return strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'zip';
}

function hs_fm_perms(string $path): string
{
    $perms = @fileperms($path);
    if ($perms === false) {
        return '—';
    }
    return substr(sprintf('%o', $perms), -4);
}

function hs_fm_format_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    if ($bytes < 1073741824) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    return round($bytes / 1073741824, 2) . ' GB';
}

/** @return array{name:string,path:string,dir:bool,size:int,size_label:string,modified:int,modified_label:string,icon:string,editable:bool,language:string,mime:string,perms:string,preview:bool} */
function hs_fm_entry(string $root, string $rel, string $name): array
{
    $full = rtrim($root, '/\\') . '/' . ($rel !== '' ? $rel . '/' : '') . $name;
    $isDir = is_dir($full);
    $size = $isDir ? 0 : (int) (@filesize($full) ?: 0);
    $mtime = (int) (@filemtime($full) ?: 0);
    $path = trim($rel . '/' . $name, '/');
    return [
        'name' => $name,
        'path' => $path,
        'dir' => $isDir,
        'size' => $size,
        'size_label' => $isDir ? '—' : hs_fm_format_size($size),
        'modified' => $mtime,
        'modified_label' => $mtime ? date('Y-m-d H:i', $mtime) : '—',
        'icon' => hs_fm_icon($name, $isDir),
        'editable' => !$isDir && hs_fm_is_editable($name),
        'language' => $isDir ? '' : hs_fm_language($name),
        'mime' => $isDir ? '' : hs_fm_mime($name),
        'perms' => hs_fm_perms($full),
        'preview' => !$isDir && hs_fm_is_image($name),
        'archive' => !$isDir && hs_fm_is_zip_archive($name),
    ];
}

/** @return array{ok:bool,path:string,entries:list<array<string,mixed>>,parent:string,error?:string} */
function hs_fm_list(array $user, string $rel): array
{
    $root = hs_fm_user_root($user);
    $dir = hs_fm_resolve($user, $rel);
    if ($dir === null || !is_dir($dir)) {
        return ['ok' => false, 'path' => '', 'entries' => [], 'parent' => '', 'error' => 'not_found'];
    }
    $rel = hs_fm_rel_from_abs($root, $dir);
    $entries = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (hs_fm_is_sensitive_name($name)) {
            continue;
        }
        $entries[] = hs_fm_entry($root, $rel, $name);
    }
    usort($entries, static function ($a, $b) {
        if ($a['dir'] !== $b['dir']) {
            return $b['dir'] <=> $a['dir'];
        }
        return strcasecmp($a['name'], $b['name']);
    });
    $parent = $rel !== '' ? dirname($rel) : '';
    if ($parent === '.') {
        $parent = '';
    }
    return ['ok' => true, 'path' => $rel, 'entries' => $entries, 'parent' => $parent];
}

/** @return array{ok:bool,content?:string,path?:string,binary?:bool,size?:int,error?:string} */
function hs_fm_read(array $user, string $rel): array
{
    if (hs_fm_is_sensitive_rel($rel)) {
        return ['ok' => false, 'error' => 'forbidden'];
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !is_file($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $size = (int) filesize($path);
    if ($size > HS_FM_MAX_READ) {
        return ['ok' => false, 'error' => 'too_large', 'size' => $size];
    }
    $binary = !hs_fm_is_editable(basename($path));
    if ($binary) {
        return ['ok' => true, 'path' => $rel, 'binary' => true, 'size' => $size];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        return ['ok' => false, 'error' => 'read_failed'];
    }
    return [
        'ok' => true,
        'path' => $rel,
        'content' => $content,
        'binary' => false,
        'size' => $size,
        'language' => hs_fm_language(basename($path)),
        'perms' => hs_fm_perms($path),
    ];
}

/** @return array{ok:bool,error?:string} */
function hs_fm_write(array $user, string $rel, string $content): array
{
    if (hs_fm_is_sensitive_rel($rel)) {
        return ['ok' => false, 'error' => 'forbidden'];
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !is_file($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (!hs_fm_is_editable(basename($path))) {
        return ['ok' => false, 'error' => 'not_editable'];
    }
    if (file_put_contents($path, $content, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'write_failed'];
    }
    return ['ok' => true];
}

/** @return array{ok:bool,path?:string,error?:string} */
function hs_fm_mkdir(array $user, string $rel, string $name): array
{
    $name = trim($name);
    if ($name === '' || preg_match('/[\/\\\\]/', $name) || $name === '.' || $name === '..') {
        return ['ok' => false, 'error' => 'invalid_name'];
    }
    $parent = hs_fm_resolve($user, $rel);
    if ($parent === null || !is_dir($parent)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $target = rtrim($parent, '/\\') . '/' . $name;
    if (file_exists($target)) {
        return ['ok' => false, 'error' => 'exists'];
    }
    if (!mkdir($target, 0755)) {
        return ['ok' => false, 'error' => 'mkdir_failed'];
    }
    $root = hs_fm_user_root($user);
    return ['ok' => true, 'path' => hs_fm_rel_from_abs($root, $target)];
}

/** @return array{ok:bool,path?:string,error?:string} */
function hs_fm_create_file(array $user, string $rel, string $name): array
{
    $name = trim($name);
    if ($name === '' || preg_match('/[\/\\\\]/', $name)) {
        return ['ok' => false, 'error' => 'invalid_name'];
    }
    $parent = hs_fm_resolve($user, $rel);
    if ($parent === null || !is_dir($parent)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $target = rtrim($parent, '/\\') . '/' . $name;
    if (file_exists($target)) {
        return ['ok' => false, 'error' => 'exists'];
    }
    if (file_put_contents($target, '') === false) {
        return ['ok' => false, 'error' => 'create_failed'];
    }
    $root = hs_fm_user_root($user);
    return ['ok' => true, 'path' => hs_fm_rel_from_abs($root, $target)];
}

/** @return array{ok:bool,error?:string} */
function hs_fm_delete(array $user, string $rel): array
{
    if (hs_fm_is_sensitive_rel($rel)) {
        return ['ok' => false, 'error' => 'forbidden'];
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $root = hs_fm_user_root($user);
    if (realpath($path) === realpath($root)) {
        return ['ok' => false, 'error' => 'protected'];
    }
    if (is_dir($path)) {
        hs_recursive_remove($path);
        return ['ok' => true];
    }
    if (!unlink($path)) {
        return ['ok' => false, 'error' => 'delete_failed'];
    }
    return ['ok' => true];
}

/** @return array{ok:bool,path?:string,error?:string} */
function hs_fm_rename(array $user, string $rel, string $newName): array
{
    $newName = trim($newName);
    if ($newName === '' || preg_match('/[\/\\\\]/', $newName)) {
        return ['ok' => false, 'error' => 'invalid_name'];
    }
    if (hs_fm_is_sensitive_rel($rel) || hs_fm_is_sensitive_name($newName)) {
        return ['ok' => false, 'error' => 'forbidden'];
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !file_exists($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $root = hs_fm_user_root($user);
    if (realpath($path) === realpath($root)) {
        return ['ok' => false, 'error' => 'protected'];
    }
    $dest = dirname($path) . '/' . $newName;
    if (file_exists($dest)) {
        return ['ok' => false, 'error' => 'exists'];
    }
    if (!rename($path, $dest)) {
        return ['ok' => false, 'error' => 'rename_failed'];
    }
    return ['ok' => true, 'path' => hs_fm_rel_from_abs($root, $dest)];
}

/** @return array{ok:bool,name?:string,path?:string,error?:string} */
function hs_fm_upload(array $user, string $rel, array $file): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'no_file'];
    }
    $parent = hs_fm_resolve($user, $rel);
    if ($parent === null || !is_dir($parent)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $name = basename((string) ($file['name'] ?? 'file'));
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: 'file';
    $target = rtrim($parent, '/\\') . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'upload_failed'];
    }
    $root = hs_fm_user_root($user);
    return ['ok' => true, 'name' => $name, 'path' => hs_fm_rel_from_abs($root, $target)];
}

/** @return list<array<string,mixed>> */
function hs_fm_search(array $user, string $rel, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }
    $root = hs_fm_user_root($user);
    $base = hs_fm_resolve($user, $rel) ?? $root;
    if (!is_dir($base)) {
        return [];
    }
    $q = strtolower($query);
    $out = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $name = $item->getFilename();
        if (!str_contains(strtolower($name), $q)) {
            continue;
        }
        $relPath = hs_fm_rel_from_abs($root, $item->getPathname());
        $parentRel = dirname($relPath);
        if ($parentRel === '.') {
            $parentRel = '';
        }
        $out[] = hs_fm_entry($root, $parentRel, $name);
        if (count($out) >= HS_FM_MAX_SEARCH) {
            break;
        }
    }
    return $out;
}

/** @return list<array{path:string,name:string,children?:list<mixed>}> */
function hs_fm_tree(array $user, int $depth = 3): array
{
    $root = hs_fm_user_root($user);
    $build = static function (string $dir, string $rel, int $d) use (&$build, $root, $depth): array {
        if ($d > $depth) {
            return [];
        }
        $nodes = [];
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $dir . '/' . $name;
            if (!is_dir($full)) {
                continue;
            }
            $path = trim($rel . '/' . $name, '/');
            $nodes[] = [
                'name' => $name,
                'path' => $path,
                'children' => $build($full, $path, $d + 1),
            ];
        }
        usort($nodes, static fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $nodes;
    };
    return $build($root, '', 0);
}

function hs_fm_download_path(array $user, string $rel): ?string
{
    if (hs_fm_is_sensitive_rel($rel)) {
        return null;
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !is_file($path)) {
        return null;
    }
    return $path;
}

/** @return array{ok:bool,mime?:string,data?:string,content?:string,svg?:bool,error?:string} */
function hs_fm_preview(array $user, string $rel): array
{
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !is_file($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (!hs_fm_is_image(basename($path))) {
        return ['ok' => false, 'error' => 'not_previewable'];
    }
    $size = (int) filesize($path);
    if ($size > 5242880) {
        return ['ok' => false, 'error' => 'too_large'];
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        default => 'application/octet-stream',
    };
    if ($ext === 'svg') {
        $content = file_get_contents($path);
        if ($content === false) {
            return ['ok' => false, 'error' => 'read_failed'];
        }
        return ['ok' => true, 'mime' => $mime, 'content' => $content, 'svg' => true];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'read_failed'];
    }
    return ['ok' => true, 'mime' => $mime, 'data' => base64_encode($raw)];
}

/** chmod the account root folder (public_html/username). */
function hs_fm_chmod_user_root(array $user, string $mode): array
{
    $root = hs_fm_user_root($user);
    if (!is_dir($root)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $digits = preg_replace('/[^0-7]/', '', $mode) ?? '';
    if ($digits === '' || strlen($digits) < 3 || strlen($digits) > 4) {
        return ['ok' => false, 'error' => 'invalid_mode'];
    }
    $oct = octdec($digits);
    if ($oct < 0 || $oct > 0777) {
        return ['ok' => false, 'error' => 'invalid_mode'];
    }
    if (!@chmod($root, $oct)) {
        return ['ok' => false, 'error' => 'chmod_failed'];
    }
    return ['ok' => true, 'perms' => substr(sprintf('%o', fileperms($root)), -4)];
}

/** @return array{ok:bool,perms?:string,error?:string} */
function hs_fm_chmod(array $user, string $rel, string $mode): array
{
    $path = hs_fm_resolve($user, $rel);
    if ($path === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $root = hs_fm_user_root($user);
    if (realpath($path) === realpath($root)) {
        return ['ok' => false, 'error' => 'protected'];
    }
    $digits = preg_replace('/[^0-7]/', '', $mode) ?? '';
    if ($digits === '' || strlen($digits) > 4) {
        return ['ok' => false, 'error' => 'invalid_mode'];
    }
    $oct = octdec($digits);
    if ($oct < 0 || $oct > 0777) {
        return ['ok' => false, 'error' => 'invalid_mode'];
    }
    if (!@chmod($path, $oct)) {
        return ['ok' => false, 'error' => 'chmod_failed'];
    }
    return ['ok' => true, 'perms' => sprintf('%04o', $oct & 0777)];
}

/** @return array{ok:bool,path?:string,error?:string} */
function hs_fm_duplicate(array $user, string $rel): array
{
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !file_exists($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $root = hs_fm_user_root($user);
    if (realpath($path) === realpath($root)) {
        return ['ok' => false, 'error' => 'protected'];
    }
    $dir = dirname($path);
    $base = pathinfo($path, PATHINFO_FILENAME);
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $suffix = is_dir($path) ? '' : ($ext !== '' ? '.' . $ext : '');
    $candidate = $base . '-copy' . $suffix;
    $n = 1;
    while (file_exists($dir . '/' . $candidate)) {
        $candidate = $base . '-copy-' . $n . $suffix;
        $n++;
        if ($n > 99) {
            return ['ok' => false, 'error' => 'exists'];
        }
    }
    $dest = $dir . '/' . $candidate;
    if (is_dir($path)) {
        if (!hs_fm_copy_tree($path, $dest)) {
            return ['ok' => false, 'error' => 'duplicate_failed'];
        }
    } elseif (!copy($path, $dest)) {
        return ['ok' => false, 'error' => 'duplicate_failed'];
    }
    return ['ok' => true, 'path' => hs_fm_rel_from_abs($root, $dest)];
}

function hs_fm_copy_tree(string $src, string $dest): bool
{
    if (!mkdir($dest, 0755, true)) {
        return false;
    }
    foreach (scandir($src) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $s = $src . '/' . $name;
        $d = $dest . '/' . $name;
        if (is_dir($s)) {
            if (!hs_fm_copy_tree($s, $d)) {
                return false;
            }
        } elseif (!copy($s, $d)) {
            return false;
        }
    }
    return true;
}

/** @return array{ok:bool,path?:string,error?:string} */
function hs_fm_archive_create(array $user, string $rel, string $zipName = ''): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'zip_missing'];
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !file_exists($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    $root = hs_fm_user_root($user);
    if (realpath($path) === realpath($root)) {
        return ['ok' => false, 'error' => 'protected'];
    }
    $zipName = trim($zipName);
    if ($zipName === '') {
        $base = basename($path);
        $zipName = (is_dir($path) ? $base : $base) . '.zip';
        if (is_file($path) && hs_fm_is_zip_archive($base)) {
            $zipName = pathinfo($base, PATHINFO_FILENAME) . '-archive.zip';
        }
    }
    $zipName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $zipName) ?: 'archive.zip';
    if (!str_ends_with(strtolower($zipName), '.zip')) {
        $zipName .= '.zip';
    }
    if (str_contains($zipName, '/') || str_contains($zipName, '\\')) {
        return ['ok' => false, 'error' => 'invalid_name'];
    }
    $parent = dirname($path);
    $zipFile = $parent . '/' . $zipName;
    if (file_exists($zipFile)) {
        return ['ok' => false, 'error' => 'exists'];
    }
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['ok' => false, 'error' => 'zip_open'];
    }
    $totalBytes = 0;
    $ok = false;
    if (is_dir($path)) {
        $folder = basename($path);
        $zip->addEmptyDir($folder . '/');
        $ok = hs_fm_zip_add_tree($zip, $path, $folder, $totalBytes);
    } else {
        $totalBytes = (int) filesize($path);
        if ($totalBytes > HS_FM_MAX_ARCHIVE_BYTES) {
            $zip->close();
            @unlink($zipFile);
            return ['ok' => false, 'error' => 'too_large'];
        }
        $ok = $zip->addFile($path, basename($path));
    }
    $zip->close();
    if (!$ok || $totalBytes > HS_FM_MAX_ARCHIVE_BYTES) {
        @unlink($zipFile);
        return ['ok' => false, 'error' => $totalBytes > HS_FM_MAX_ARCHIVE_BYTES ? 'too_large' : 'zip_add'];
    }
    return ['ok' => true, 'path' => hs_fm_rel_from_abs($root, $zipFile)];
}

function hs_fm_zip_add_tree(ZipArchive $zip, string $dir, string $zipPrefix, int &$totalBytes): bool
{
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $full = $dir . '/' . $name;
        if (is_link($full)) {
            continue;
        }
        $zipPath = $zipPrefix . '/' . $name;
        if (is_dir($full)) {
            $zip->addEmptyDir($zipPath . '/');
            if (!hs_fm_zip_add_tree($zip, $full, $zipPath, $totalBytes)) {
                return false;
            }
        } else {
            $sz = (int) filesize($full);
            $totalBytes += $sz;
            if ($totalBytes > HS_FM_MAX_ARCHIVE_BYTES) {
                return false;
            }
            if (!$zip->addFile($full, $zipPath)) {
                return false;
            }
        }
    }
    return true;
}

/** @return array{ok:bool,path?:string,error?:string} */
function hs_fm_archive_extract(array $user, string $rel): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'zip_missing'];
    }
    $path = hs_fm_resolve($user, $rel);
    if ($path === null || !is_file($path)) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if (!hs_fm_is_zip_archive(basename($path))) {
        return ['ok' => false, 'error' => 'not_archive'];
    }
    $size = (int) filesize($path);
    if ($size > HS_FM_MAX_ARCHIVE_BYTES) {
        return ['ok' => false, 'error' => 'too_large'];
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return ['ok' => false, 'error' => 'zip_open'];
    }
    if (!hs_fm_zip_is_safe($zip)) {
        $zip->close();
        return ['ok' => false, 'error' => 'unsafe_zip'];
    }
    $parent = dirname($path);
    $base = pathinfo(basename($path), PATHINFO_FILENAME);
    $dest = $parent . '/' . $base;
    $n = 0;
    while (file_exists($dest)) {
        $n++;
        $dest = $parent . '/' . $base . '-extracted' . ($n > 1 ? '-' . $n : '');
        if ($n > 99) {
            $zip->close();
            return ['ok' => false, 'error' => 'exists'];
        }
    }
    if (!mkdir($dest, 0755, true)) {
        $zip->close();
        return ['ok' => false, 'error' => 'mkdir'];
    }
    if (!$zip->extractTo($dest)) {
        $zip->close();
        hs_recursive_remove($dest);
        return ['ok' => false, 'error' => 'zip_extract'];
    }
    $zip->close();
    $root = hs_fm_user_root($user);
    return ['ok' => true, 'path' => hs_fm_rel_from_abs($root, $dest)];
}

function hs_fm_zip_is_safe(ZipArchive $zip): bool
{
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false) {
            continue;
        }
        $norm = str_replace('\\', '/', $name);
        if (str_starts_with($norm, '/') || str_contains($norm, '../') || str_contains($norm, '/..')) {
            return false;
        }
    }
    return true;
}