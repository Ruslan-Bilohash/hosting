$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
$php = Invoke-WebRequest -Uri "$base/panel/php.php" -WebSession $sess -UseBasicParsing
Write-Host 'php.php:' $php.StatusCode
Write-Host 'has user.ini preview:' ($php.Content -match 'memory_limit')
Write-Host 'has live table:' ($php.Content -match 'memory_limit.*upload_max')
$probe = Invoke-WebRequest -Uri "$base/public_html/demo/hs-php-probe.php" -UseBasicParsing -ErrorAction SilentlyContinue
if ($probe) {
    Write-Host 'probe status:' $probe.StatusCode
    Write-Host 'probe body:' $probe.Content.Substring(0, [Math]::Min(200, $probe.Content.Length))
} else {
    Write-Host 'probe: need token (403 expected without token)'
}