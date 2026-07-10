$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
$urls = @{
    'support' = 'panel/support.php'
    'ftp' = 'panel/files.php?tab=ftp'
    'ftppass' = 'panel/files.php?tab=ftppass'
    'git' = 'panel/advanced.php?tab=git'
}
foreach ($name in $urls.Keys) {
    $r = Invoke-WebRequest -Uri "$base/$($urls[$name])" -WebSession $sess -UseBasicParsing
    Write-Host "$name : $($r.StatusCode)"
    switch ($name) {
        'support' {
            Write-Host "  hs-support: $($r.Content -like '*hs-support*')"
            Write-Host "  support.js: $($r.Content -like '*support.js*')"
        }
        'ftppass' {
            Write-Host "  ftp-pass: $($r.Content -like '*ftp-pass-value*')"
            Write-Host "  copy-all: $($r.Content -like '*ftp-copy-all*')"
        }
        'git' {
            Write-Host "  git-form: $($r.Content -like '*hs-git-form*')"
            Write-Host "  github: $($r.Content -like '*github*')"
        }
    }
}
$api = Invoke-WebRequest -Uri "$base/panel/support-messages.php" -WebSession $sess -UseBasicParsing
Write-Host "support-api: $($api.StatusCode) $($api.Content.Substring(0, [Math]::Min(80, $api.Content.Length)))"