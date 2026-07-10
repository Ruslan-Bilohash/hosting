$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$pg = Invoke-WebRequest -Uri "$base/panel/analytics.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ("HTTP {0}" -f $pg.StatusCode)
@(
    @{ Name = 'activity log table'; Ok = ($pg.Content -match 'hs-analytics-log-wrap|analytics_activity') }
    @{ Name = 'stats block'; Ok = ($pg.Content -match 'hs-analytics-stats') }
    @{ Name = 'login type badge'; Ok = ($pg.Content -match 'hs-analytics-type-login|analytics_log_type_login') }
    @{ Name = 'no fake visit counter'; Ok = ($pg.Content -notmatch 'hp-stat-lg') }
    @{ Name = 'duration column'; Ok = ($pg.Content -match 'hs-analytics-duration|analytics_col_duration') }
) | ForEach-Object { Write-Host ("  {0}: {1}" -f $_.Name, $(if ($_.Ok) { 'OK' } else { 'FAIL' })) }