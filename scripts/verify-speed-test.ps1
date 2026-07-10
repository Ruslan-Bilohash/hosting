$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$page = Invoke-WebRequest -Uri "$base/panel/performance.php?tab=speed&lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ("speed-page: HTTP {0}" -f $page.StatusCode)
if ($page.Content -match 'id="hs-speed-url"[^>]*value="([^"]*)"') {
    Write-Host ("default-url-input: {0}" -f $Matches[1])
}
if ($page.Content -match '"default_url":"([^"]*)"') {
    Write-Host ("default-url-json: {0}" -f $Matches[1])
}
@('hs-speed-lab', 'hs-speed-hero', 'speed-test.js', 'HS_SPEED_LAB', 'data-hs-speed-run') | ForEach-Object {
    $ok = $page.Content -match [regex]::Escape($_)
    Write-Host ("  {0}: {1}" -f $_, $(if ($ok) { 'OK' } else { 'MISSING' }))
}

$body = (@{ csrf = $csrf; url = '' } | ConvertTo-Json -Compress)
try {
    $api = Invoke-WebRequest -Uri "$base/panel/performance-speed-api.php" -Method POST -Body $body -ContentType 'application/json' -WebSession $sess -UseBasicParsing
    $json = $api.Content.TrimStart([char]0xFEFF, '?') | ConvertFrom-Json
    Write-Host ("api: ok={0} desktop={1} mobile={2}" -f $json.ok, $json.desktop, $json.mobile)
    if ($json.report.metrics) {
        Write-Host ("  ttfb={0}ms load={1}ms" -f $json.report.metrics.ttfb_ms, $json.report.metrics.load_ms)
    }
} catch {
    $resp = $_.Exception.Response
    if ($resp) {
        $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
        Write-Host $reader.ReadToEnd()
    } else {
        Write-Host $_.Exception.Message
    }
}