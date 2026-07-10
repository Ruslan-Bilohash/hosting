$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
$p = Invoke-WebRequest -Uri "$base/panel/php.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ('has php.ini path: {0}' -f ($p.Content -match '/php\.ini'))
Write-Host ('no .user.ini path: {0}' -f ($p.Content -notmatch '/\.user\.ini'))