$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$jar = Join-Path $env:TEMP 'hs-fm-cookies.txt'
if (Test-Path $jar) { Remove-Item $jar -Force }

# Login
$loginPage = Invoke-WebRequest -Uri "$base/login.php" -SessionVariable sess -UseBasicParsing
$csrf = if ($loginPage.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
$body = @{ login = 'demo'; password = 'demo'; csrf = $csrf }
$r = Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body $body -WebSession $sess -UseBasicParsing -MaximumRedirection 5
Write-Host "Login status:" $r.StatusCode

$api = "$base/panel/files-api.php?action=list"
$apiR = Invoke-WebRequest -Uri $api -WebSession $sess -UseBasicParsing
Write-Host "API status:" $apiR.StatusCode
Write-Host "API content-type:" $apiR.Headers['Content-Type']
Write-Host "API body (first 500):" $apiR.Content.Substring(0, [Math]::Min(500, $apiR.Content.Length))

$files = Invoke-WebRequest -Uri "$base/panel/files.php" -WebSession $sess -UseBasicParsing
Write-Host "Files page status:" $files.StatusCode
if ($files.Content -match 'window\.HS_FM=({[^<]+})') { Write-Host "HS_FM found" } else { Write-Host "HS_FM MISSING" }
if ($files.Content -match 'monaco-editor') { Write-Host "Monaco CDN present" } else { Write-Host "Monaco CDN MISSING" }
if ($files.Content -match 'file-manager\.js') { Write-Host "file-manager.js present" } else { Write-Host "file-manager.js MISSING" }