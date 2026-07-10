# One-off: create GitHub release from tag (uses git credential, not committed).
$ErrorActionPreference = 'Stop'
$payloadPath = Join-Path (Split-Path $PSScriptRoot -Parent) 'release-payload.json'
if (-not (Test-Path $payloadPath)) { throw "Missing $payloadPath" }

$stdin = "protocol=https`nhost=github.com`n`n"
$credOut = $stdin | git credential fill
$token = ($credOut | Select-String '^password=').Line.Substring(9)

$resp = curl.exe --max-time 60 -s -w "`nHTTP:%{http_code}" -X POST `
    'https://api.github.com/repos/Ruslan-Bilohash/hosting/releases' `
    -H "Authorization: Bearer $token" `
    -H 'Accept: application/vnd.github+json' `
    -H 'Content-Type: application/json' `
    -d "@$payloadPath"

Write-Output $resp
if ($resp -notmatch 'HTTP:201') { throw "Release API failed: $resp" }