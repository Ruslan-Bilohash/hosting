<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
$site = '{{SITE_NAME}}';
$app = '{{APP_SLUG}}';
$title = '{{APP_TITLE}}';
$color = '{{APP_COLOR}}';
$demo = '{{DEMO_URL}}';
if ($demo === '{{DEMO_URL}}' || trim((string) $demo) === '') {
    $demo = 'https://bilohash.com/';
}
$year = '{{YEAR}}';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title !== '' ? $title : $site, ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
<style>
:root{--c:<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>}
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:"DM Sans",system-ui,sans-serif;background:linear-gradient(160deg,#f8fafc,#eef2ff);color:#0f172a}
.wrap{max-width:52rem;margin:0 auto;padding:2.5rem 1.5rem}
.hero{background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;padding:2rem;box-shadow:0 16px 40px rgba(15,23,42,.06)}
.badge{display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .85rem;border-radius:999px;background:color-mix(in srgb,var(--c) 12%,#fff);color:var(--c);font-size:.8rem;font-weight:600;margin-bottom:1rem}
h1{margin:0 0 .5rem;font-size:1.75rem}
p{color:#64748b;line-height:1.6;margin:0 0 1.25rem}
.actions{display:flex;flex-wrap:wrap;gap:.75rem}
a.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.1rem;border-radius:.65rem;text-decoration:none;font-weight:600;font-size:.9rem}
a.primary{background:var(--c);color:#fff}a.ghost{background:#f1f5f9;color:#334155}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));gap:1rem;margin-top:1.5rem}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:1.25rem}
.card strong{display:block;margin-bottom:.35rem}
.card span{color:#64748b;font-size:.85rem}
footer{margin-top:2rem;text-align:center;color:#94a3b8;font-size:.8rem}
</style>
</head>
<body>
<div class="wrap">
  <section class="hero">
    <div class="badge"><i class="fa-solid fa-rocket"></i> <?= htmlspecialchars($app, ENT_QUOTES, 'UTF-8') ?></div>
    <h1><?= htmlspecialchars($title !== '' ? $title : $site, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($site, ENT_QUOTES, 'UTF-8') ?> is live on Hosting CMS. Manage files, domain and SSL from your hosting panel.</p>
    <div class="actions">
      <a class="btn primary" href="<?= htmlspecialchars($demo, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open demo</a>
      <a class="btn ghost" href="{{PANEL_URL}}" target="_blank" rel="noopener"><i class="fa-solid fa-server"></i> Hosting panel</a>
    </div>
    <div class="grid">
      <div class="card"><strong>Status</strong><span class="hp-ok" style="color:#059669">Active</span></div>
      <div class="card"><strong>PHP</strong><span><?= PHP_VERSION ?></span></div>
      <div class="card"><strong>Folder</strong><span><?= htmlspecialchars($site, ENT_QUOTES, 'UTF-8') ?></span></div>
    </div>
  </section>
  <footer>© <?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?> Hosting CMS · BILOHASH ecosystem</footer>
</div>
</body>
</html>