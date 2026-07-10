$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
$ssh = Invoke-WebRequest -Uri "$base/panel/ssh.php" -WebSession $sess -UseBasicParsing
$db = Invoke-WebRequest -Uri "$base/panel/databases.php" -WebSession $sess -UseBasicParsing
Write-Host 'ssh.php:' $ssh.StatusCode
Write-Host 'ssh guide:' ($ssh.Content -like '*hs-ssh-guide*')
Write-Host 'ssh pass toggle:' ($ssh.Content -like '*ssh-pass-toggle*')
Write-Host 'databases.php:' $db.StatusCode
Write-Host 'shared db:' ($db.Content -like '*u762384583_hosting_cms*' -or $db.Content -like '*hs_demo*')
Write-Host 'db error 1044:' ($db.Content -like '*1044*')