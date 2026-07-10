$ErrorActionPreference = 'Continue'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null
try {
    $p = Invoke-WebRequest -Uri "$base/panel/support.php" -WebSession $sess -UseBasicParsing
    Write-Host 'status:' $p.StatusCode
} catch {
    $resp = $_.Exception.Response
    if ($resp) {
        $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
        $body = $reader.ReadToEnd()
        Write-Host 'status:' ([int]$resp.StatusCode)
        Write-Host $body.Substring(0, [Math]::Min(1500, $body.Length))
    } else {
        Write-Host $_.Exception.Message
    }
}