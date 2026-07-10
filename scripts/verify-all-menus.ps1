# Verify ALL panel menu URLs (every tab)
param(
    [string]$Base = 'https://bilohash.com/hosting',
    [string]$User = 'demo',
    [string]$Pass = 'demo'
)
$ErrorActionPreference = 'Stop'
$jar = Join-Path $env:TEMP 'hs-verify-cookies.txt'
if (Test-Path $jar) { Remove-Item $jar -Force }

curl.exe -s -c $jar "$Base/login.php" -o NUL
$html = curl.exe -s -c $jar "$Base/login.php"
$csrf = ([regex]::Match($html, 'name="csrf" value="([^"]+)"')).Groups[1].Value
curl.exe -s -b $jar -c $jar -X POST "$Base/login.php" -d "login=$User&password=$Pass&csrf=$csrf" -o NUL

$urls = @(
  'panel/','panel/plan.php','panel/resources.php','panel/plan-renew.php','panel/installer.php',
  'panel/ssh.php','panel/php.php','panel/phpinfo.php','panel/analytics.php','panel/email.php',
  'panel/backups.php','panel/account.php','panel/clients.php',
  'panel/performance.php','panel/performance.php?tab=ai','panel/performance.php?tab=cache','panel/performance.php?tab=speed','panel/performance.php?tab=cdn',
  'panel/security.php','panel/security.php?tab=malware','panel/security.php?tab=wpupdate','panel/security.php?tab=ssl',
  'panel/domains.php','panel/domains.php?tab=subdomains','panel/domains.php?tab=parked','panel/domains.php?tab=redirect',
  'panel/websites.php','panel/websites.php?tab=installer','panel/websites.php?tab=migrate','panel/websites.php?tab=copy','panel/websites.php?tab=errors','panel/support.php',
  'panel/files.php','panel/files.php?tab=backups','panel/files.php?tab=ftp','panel/files.php?tab=ftppass',
  'panel/databases.php','panel/databases.php?tab=phpmyadmin','panel/databases.php?tab=remote',
  'panel/advanced.php','panel/advanced.php?tab=ssh','panel/advanced.php?tab=php','panel/advanced.php?tab=dns','panel/advanced.php?tab=cron',
  'panel/advanced.php?tab=phpinfo','panel/advanced.php?tab=cachemgr','panel/advanced.php?tab=git','panel/advanced.php?tab=htpasswd',
  'panel/advanced.php?tab=ip','panel/advanced.php?tab=hotlink','panel/advanced.php?tab=indexing','panel/advanced.php?tab=permissions','panel/advanced.php?tab=history',
  'panel/api.php','panel/api.php?tab=ai',
  'panel/wordpress.php','panel/wordpress.php?tab=security','panel/wordpress.php?tab=ai','panel/wordpress.php?tab=staging','panel/wordpress.php?tab=copy','panel/wordpress.php?tab=presets','panel/wordpress.php?tab=learn'
)

$adminOnly = @{ 'panel/clients.php' = $true }
$fail = @()
$ok = 0
foreach ($u in $urls) {
    $code = curl.exe -s -b $jar -o NUL -w "%{http_code}" "$Base/$u"
    if ($code -eq '200') { $ok++ }
    elseif ($adminOnly.ContainsKey($u) -and $code -eq '302') { $ok++ }
    else { $fail += "$u -> $code" }
}
Write-Host "OK $ok / $($urls.Count) on $Base (demo user; clients.php 302 = admin-only OK)"
if ($fail.Count) {
    $fail | ForEach-Object { Write-Host $_ }
    exit 1
}
exit 0