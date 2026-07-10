<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
$site = '{{SITE_NAME}}';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($site, ENT_QUOTES, 'UTF-8') ?></title>
<style>
body{font-family:system-ui,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;background:#f8fafc;color:#0f172a}
main{text-align:center;padding:2rem}code{background:#e2e8f0;padding:.2rem .5rem;border-radius:.35rem}
</style>
</head>
<body>
<main>
<h1><?= htmlspecialchars($site, ENT_QUOTES, 'UTF-8') ?></h1>
<p>PHP <?= PHP_VERSION ?> is ready. Replace this file or install a BILOHASH CMS.</p>
</main>
</body>
</html>