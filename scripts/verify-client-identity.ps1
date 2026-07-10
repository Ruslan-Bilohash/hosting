$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php?lang=uk" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$acc = Invoke-WebRequest -Uri "$base/panel/account.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host '=== account.php ==='
Write-Host ("HTTP {0}" -f $acc.StatusCode)
@(
    @{ Name = 'client_number BH-CL'; Ok = ($acc.Content -match 'BH-CL-\d{5}') }
    @{ Name = 'support_email demo@clients'; Ok = ($acc.Content -match 'demo@clients\.bilohash\.com') }
    @{ Name = 'account_client_id label'; Ok = ($acc.Content -match 'account_client_id|fa-hashtag') }
    @{ Name = 'hs-account-badge-id'; Ok = ($acc.Content -match 'hs-account-badge-id') }
    @{ Name = 'NOT shared demo@bilohash'; Ok = ($acc.Content -notmatch 'demo@bilohash\.com') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }

$sup = Invoke-WebRequest -Uri "$base/panel/support.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host '=== support.php ==='
Write-Host ("HTTP {0}" -f $sup.StatusCode)
@(
    @{ Name = 'support_email demo@clients'; Ok = ($sup.Content -match 'demo@clients\.bilohash\.com') }
    @{ Name = 'client_id BH-CL'; Ok = ($sup.Content -match 'BH-CL-\d{5}') }
    @{ Name = 'readonly email field'; Ok = ($sup.Content -match 'hs-support-email-locked|readonly') }
    @{ Name = 'NOT shared demo@bilohash'; Ok = ($sup.Content -notmatch 'demo@bilohash\.com') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }