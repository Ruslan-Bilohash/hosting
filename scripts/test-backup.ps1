$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$loginPage = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($loginPage.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$backupsPage = Invoke-WebRequest -Uri "$base/panel/backups.php" -WebSession $sess -UseBasicParsing
$fmCsrf = if ($backupsPage.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Write-Host "Backups page:" $backupsPage.StatusCode
Write-Host "Has schedule:" ($backupsPage.Content -match 'backup_schedule')
Write-Host "Has ZIP hint:" ($backupsPage.Content -match 'ZIP')

$r = Invoke-WebRequest -Uri "$base/panel/backups.php" -Method POST -Body @{ csrf = $fmCsrf; create_backup = '1' } -WebSession $sess -UseBasicParsing
Write-Host "Create backup:" $r.StatusCode ($r.Content -match 'backup_created|file-zipper|alert-success')