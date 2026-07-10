# Smoke-test all Hosting CMS panel URLs
$ErrorActionPreference = 'Stop'
$Base = 'https://bilohash.com/hosting'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$login = Invoke-WebRequest -Uri "$Base/login.php" -WebSession $session -UseBasicParsing
$csrf = ([regex]::Match($login.Content, 'name="csrf" value="([^"]+)"')).Groups[1].Value
Invoke-WebRequest -Uri "$Base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $session -UseBasicParsing | Out-Null

$urls = @(
  'panel/','panel/plan.php','panel/resources.php','panel/plan-renew.php','panel/installer.php',
  'panel/ssh.php','panel/php.php','panel/phpinfo.php','panel/analytics.php','panel/email.php',
  'panel/backups.php','panel/account.php','panel/performance.php','panel/security.php',
  'panel/domains.php','panel/websites.php','panel/files.php','panel/databases.php',
  'panel/advanced.php','panel/wordpress.php','panel/performance.php?tab=cache',
  'panel/domains.php?tab=parked','panel/advanced.php?tab=cron','panel/advanced.php?tab=history'
)
$fail = @()
foreach ($u in $urls) {
  try {
    $r = Invoke-WebRequest -Uri "$Base/$u" -WebSession $session -UseBasicParsing -TimeoutSec 20
    if ($r.StatusCode -ne 200) { $fail += "$u -> $($r.StatusCode)" }
  } catch {
    $fail += "$u -> ERROR"
  }
}
if ($fail.Count) {
  Write-Host "FAILED $($fail.Count)/$($urls.Count)"
  $fail | ForEach-Object { Write-Host $_ }
  exit 1
}
Write-Host "OK $($urls.Count)/$($urls.Count) panel URLs"
exit 0