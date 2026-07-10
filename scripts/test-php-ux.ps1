$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
$php = Invoke-WebRequest -Uri "$base/panel/php.php" -WebSession $sess -UseBasicParsing
Write-Host 'php.php:' $php.StatusCode
$checks = @('hs-php-quick-actions', 'hs-php-faq', 'hs-php-guide', 'hs-php-hero', 'php-live-compare', 'php-panel.js')
foreach ($c in $checks) {
    $ok = $php.Content -like "*$c*"
    Write-Host "$c : $(if ($ok) { 'OK' } else { 'MISS' })"
}