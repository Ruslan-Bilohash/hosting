$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$dash = Invoke-WebRequest -Uri "$base/panel/?lang=uk" -WebSession $sess -UseBasicParsing
$dom = Invoke-WebRequest -Uri "$base/panel/domains.php?tab=overview&lang=uk" -WebSession $sess -UseBasicParsing

Write-Host '=== dashboard ==='
@(
    @{ Name = 'hp-dash-site-details'; Ok = ($dash.Content -match 'hp-dash-site-details') }
    @{ Name = 'site IP'; Ok = ($dash.Content -match '45\.84\.204\.61') }
    @{ Name = 'https links'; Ok = ($dash.Content -match 'https://') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }

Write-Host '=== domains overview ==='
@(
    @{ Name = 'no hp-dash-site-details'; Ok = ($dom.Content -notmatch 'hp-dash-site-details') }
    @{ Name = 'no site IP table'; Ok = ($dom.Content -notmatch 'plan_site_ip|45\.84\.204\.61') }
    @{ Name = 'has primary domain form'; Ok = ($dom.Content -match 'primary_domain') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }