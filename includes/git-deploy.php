<?php
declare(strict_types=1);

/** @return array{host:string,owner:string,repo:string}|null */
function hs_git_parse_repo(string $url): ?array
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    if (preg_match('#github\.com[:/]([^/]+)/([^/.]+)(?:\.git)?#i', $url, $m) === 1) {
        return ['host' => 'github', 'owner' => $m[1], 'repo' => $m[2]];
    }
    if (preg_match('#gitlab\.com[:/]([^/]+)/([^/.]+)(?:\.git)?#i', $url, $m) === 1) {
        return ['host' => 'gitlab', 'owner' => $m[1], 'repo' => $m[2]];
    }
    return null;
}

function hs_git_sanitize_branch(string $branch): string
{
    $branch = preg_replace('/[^a-zA-Z0-9._\\/-]/', '', trim($branch)) ?? 'main';
    return $branch !== '' ? $branch : 'main';
}

function hs_git_sanitize_subdir(string $subdir): string
{
    $subdir = trim(str_replace('\\', '/', $subdir), '/');
    if ($subdir === '' || str_contains($subdir, '..')) {
        return '';
    }
    return preg_replace('/[^a-zA-Z0-9._\\/-]/', '', $subdir) ?? '';
}

function hs_git_deploy_dir(string $username, string $subdir = ''): string
{
    $username = preg_replace('/[^a-z0-9_-]/i', '', $username) ?: 'user';
    $rel = $username . ($subdir !== '' ? '/' . $subdir : '');
    return hs_public_path($rel);
}

/** @return array{ok:bool,error?:string,output?:string,files?:int} */
function hs_git_deploy_from_github(array $parsed, string $branch, string $destDir, ?string $token = null): array
{
    $owner = $parsed['owner'];
    $repo = $parsed['repo'];
    $branch = hs_git_sanitize_branch($branch);

    if ($token !== null && $token !== '') {
        $zipUrl = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/zipball/' . rawurlencode($branch);
        $headers = "User-Agent: BILOHASH-Hosting-CMS\r\nAccept: application/vnd.github+json\r\nAuthorization: Bearer " . $token . "\r\n";
    } else {
        $zipUrl = 'https://github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/archive/refs/heads/' . rawurlencode($branch) . '.zip';
        $headers = "User-Agent: BILOHASH-Hosting-CMS\r\n";
    }

    $ctx = stream_context_create(['http' => ['timeout' => 120, 'header' => $headers]]);
    $zipBody = @file_get_contents($zipUrl, false, $ctx);
    if ($zipBody === false || $zipBody === '') {
        return ['ok' => false, 'error' => 'download_failed'];
    }

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        return ['ok' => false, 'error' => 'mkdir_failed'];
    }

    $tmpZip = $destDir . '/_git-deploy-' . bin2hex(random_bytes(4)) . '.zip';
    $tmpExtract = $destDir . '/_git-extract-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmpZip, $zipBody, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'write_zip_failed'];
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpZip) !== true) {
        @unlink($tmpZip);
        return ['ok' => false, 'error' => 'zip_open_failed'];
    }
    if (!is_dir($tmpExtract)) {
        mkdir($tmpExtract, 0755, true);
    }
    $zip->extractTo($tmpExtract);
    $zip->close();
    @unlink($tmpZip);

    $rootFolder = null;
    foreach (scandir($tmpExtract) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (is_dir($tmpExtract . '/' . $entry)) {
            $rootFolder = $tmpExtract . '/' . $entry;
            break;
        }
    }
    if ($rootFolder === null) {
        hs_git_rrmdir($tmpExtract);
        return ['ok' => false, 'error' => 'empty_archive'];
    }

    $files = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootFolder, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $rel = substr($item->getPathname(), strlen($rootFolder) + 1);
        $target = $destDir . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            $parent = dirname($target);
            if (!is_dir($parent)) {
                mkdir($parent, 0755, true);
            }
            if (copy($item->getPathname(), $target)) {
                $files++;
            }
        }
    }
    hs_git_rrmdir($tmpExtract);

    return ['ok' => true, 'output' => "Deployed {$files} files from {$owner}/{$repo}@{$branch}", 'files' => $files];
}

function hs_git_rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            hs_git_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

/** @param array<string, mixed> $settings */
function hs_git_clone_command(string $url, string $branch, string $username, string $subdir = ''): string
{
    $dest = hs_git_deploy_dir($username, $subdir);
    $branch = hs_git_sanitize_branch($branch);
    return 'git clone --branch ' . $branch . ' --single-branch ' . $url . ' ' . $dest;
}

function hs_git_pull_command(string $username, string $branch, string $subdir = ''): string
{
    return 'cd ' . hs_git_deploy_dir($username, $subdir) . ' && git pull origin ' . hs_git_sanitize_branch($branch);
}

/** @param array<string, mixed> $settings */
function hs_git_run_deploy(string $username, array $settings): array
{
    $url = trim((string) ($settings['git_url'] ?? ''));
    $parsed = hs_git_parse_repo($url);
    if ($parsed === null) {
        return ['ok' => false, 'error' => 'invalid_url'];
    }
    $branch = hs_git_sanitize_branch((string) ($settings['git_branch'] ?? 'main'));
    $subdir = hs_git_sanitize_subdir((string) ($settings['git_deploy_subdir'] ?? ''));
    $token = trim((string) ($settings['git_token'] ?? ''));
    $dest = hs_git_deploy_dir($username, $subdir);

    if (is_dir($dest . '/.git') && is_executable('/usr/bin/git')) {
        $cmd = 'cd ' . escapeshellarg($dest) . ' && git fetch origin ' . escapeshellarg($branch) . ' 2>&1 && git reset --hard FETCH_HEAD 2>&1';
        $out = [];
        exec($cmd, $out, $code);
        if ($code === 0) {
            return ['ok' => true, 'output' => implode("\n", $out), 'files' => 0];
        }
    }

    return hs_git_deploy_from_github($parsed, $branch, $dest, $token !== '' ? $token : null);
}