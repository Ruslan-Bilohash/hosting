$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$lp = Invoke-WebRequest -Uri "$base/login.php?lang=uk" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$acc = Invoke-WebRequest -Uri "$base/panel/account.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ("account: HTTP {0}" -f $acc.StatusCode)

$markers = @(
    'hs-account-page',
    'hs-account-master',
    'hs-service-chips',
    'account_master_pass_title',
    'change_master_pass',
    'account-pass-value',
    'hs-account-hero'
)
foreach ($m in $markers) {
    $ok = $acc.Content -match [regex]::Escape($m) -or ($m -notmatch 'account_' -and $acc.Content -match $m)
    if ($m -eq 'account_master_pass_title') {
        $ok = $acc.Content -match 'Головний пароль'
    }
    Write-Host ("  {0}: {1}" -f $m, $(if ($ok) { 'OK' } else { 'MISSING' }))
}

# Change password test (demo -> demo, should still work)
$body = @{ change_master_pass = '1'; current_pass = 'demo'; new_pass = 'demo'; confirm_pass = 'demo'; csrf = $csrf }
$ch = Invoke-WebRequest -Uri "$base/panel/account.php?lang=uk" -Method POST -Body $body -WebSession $sess -UseBasicParsing
Write-Host ("  password-change-post: HTTP {0}" -f $ch.StatusCode)
if ($ch.Content -match 'hs-alert-success|оновлено') { Write-Host '  password-change: OK' }

# FTP tab should show same password link
$files = Invoke-WebRequest -Uri "$base/panel/files.php?tab=ftppass&lang=uk" -WebSession $sess -UseBasicParsing
if ($files.Content -match 'account\.php') { Write-Host '  ftp-link-account: OK' } else { Write-Host '  ftp-link-account: MISSING' }