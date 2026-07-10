$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$ov = Invoke-WebRequest -Uri "$base/panel/domains.php?tab=overview&lang=uk" -WebSession $sess -UseBasicParsing
Write-Host '=== overview ==='
@(
    @{ Name = 'no hs-dns-table'; Ok = ($ov.Content -notmatch 'hs-dns-table') }
    @{ Name = 'no dns add form'; Ok = ($ov.Content -notmatch 'hs-dns-add-form') }
    @{ Name = 'has domain registry'; Ok = ($ov.Content -match 'hs-dom-registry|domain_registry') }
    @{ Name = 'dns tab link'; Ok = ($ov.Content -match 'tab=dns') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }

$dns = Invoke-WebRequest -Uri "$base/panel/domains.php?tab=dns&lang=uk" -WebSession $sess -UseBasicParsing
Write-Host '=== dns tab ==='
@(
    @{ Name = 'hs-dns-table'; Ok = ($dns.Content -match 'hs-dns-table') }
    @{ Name = 'hs-dns-add-form'; Ok = ($dns.Content -match 'hs-dns-add-form') }
    @{ Name = 'dns tab active'; Ok = ($dns.Content -match 'hp-tab active[^>]*>[^<]*DNS|tab=dns') }
    @{ Name = 'form action dns tab'; Ok = ($dns.Content -match 'domains\.php\?tab=dns') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }