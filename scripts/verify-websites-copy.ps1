$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$page = Invoke-WebRequest -Uri "$base/panel/websites.php?tab=copy&lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ("copy-tab: HTTP {0}" -f $page.StatusCode)
@('hs-site-copy-guide', 'data-hs-site-copy', 'copy_site', 'HS_SITE_COPY_META', 'site-copy-from', 'tab=copy') | ForEach-Object {
    Write-Host ("  {0}: {1}" -f $_, $(if ($page.Content -match [regex]::Escape($_)) { 'OK' } else { 'MISSING' }))
}
$js = Invoke-WebRequest -Uri "$base/assets/js/app.js" -WebSession $sess -UseBasicParsing
Write-Host ("  js initSiteCopyTab: {0}" -f $(if ($js.Content -match 'initSiteCopyTab') { 'OK' } else { 'MISSING' }))