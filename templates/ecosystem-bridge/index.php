<?php
declare(strict_types=1);
/**
 * BILOHASH Hosting — ecosystem app bridge for {{APP_SLUG}}
 */
$metaFile = __DIR__ . '/.bilohash-hosting.json';
$meta = is_file($metaFile) ? json_decode((string) file_get_contents($metaFile), true) : [];
$ecosystem = (string) ($meta['ecosystem_path'] ?? '{{ECOSYSTEM_PATH}}');
$index = rtrim($ecosystem, '/\\') . '/index.php';
if (is_file($index)) {
    chdir(dirname($index));
    require $index;
    return;
}
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>{{SITE_NAME}}</title></head>
<body style="font-family:system-ui,sans-serif;padding:2rem">
<h1>{{SITE_NAME}}</h1>
<p>BILOHASH {{APP_SLUG}} — bridge active. Configure ecosystem path in hosting panel.</p>
</body></html>