$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
$fp = Invoke-WebRequest -Uri "$base/panel/files.php" -WebSession $sess -UseBasicParsing
Write-Host 'Status:' $fp.StatusCode
$idx = $fp.Content.IndexOf('data-fm-modal')
if ($idx -ge 0) {
    Write-Host 'Modal HTML:' $fp.Content.Substring($idx, [Math]::Min(350, $fp.Content.Length - $idx))
}
$idx2 = $fp.Content.IndexOf('window.HS_FM=')
if ($idx2 -ge 0) {
    $chunk = $fp.Content.Substring($idx2, [Math]::Min(500, $fp.Content.Length - $idx2))
    Write-Host 'HS_FM start:' $chunk
}