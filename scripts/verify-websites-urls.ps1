$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$page = Invoke-WebRequest -Uri "$base/panel/websites.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ("websites: HTTP {0}" -f $page.StatusCode)

$bad = @()
[regex]::Matches($page.Content, 'hs-site-url-hint[^>]*>[\s\S]*?href="(https://[^"]+)"') | ForEach-Object {
    $url = $_.Groups[1].Value
    if ($url -match '/panel/') { $bad += "panel in url: $url" }
    elseif ($url -notmatch '/public_html/') { $bad += "no public_html: $url" }
    else { Write-Host ("  OK: {0}" -f $url) }
}
if (-not $page.Content -match 'hs-site-url-hint') {
    [regex]::Matches($page.Content, '<td><code>public_html/[^<]+</code>') | ForEach-Object { Write-Host $_.Value }
}

if ($bad.Count) {
    Write-Host 'FAIL:'
    $bad | ForEach-Object { Write-Host $_ }
    exit 1
}
Write-Host ("  edit-builder-link: {0}" -f $(if ($page.Content -match 'landing-builder\.php') { 'OK' } else { 'none (no landing sites?)' }))
Write-Host 'all site URLs look correct'