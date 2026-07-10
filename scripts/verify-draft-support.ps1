$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$lp = Invoke-WebRequest -Uri "$base/login.php?lang=uk" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$checks = @(
    @{ Name = 'login'; Url = "$base/login.php?lang=uk"; Auth = $false },
    @{ Name = 'support'; Url = "$base/panel/support.php?lang=uk"; Auth = $true },
    @{ Name = 'plan'; Url = "$base/panel/plan.php?lang=uk"; Auth = $true }
)

foreach ($c in $checks) {
    $r = Invoke-WebRequest -Uri $c.Url -WebSession $sess -UseBasicParsing
    Write-Host ("[{0}] HTTP {1}" -f $c.Name, $r.StatusCode)
}

$sup = Invoke-WebRequest -Uri "$base/panel/support.php?lang=uk" -WebSession $sess -UseBasicParsing
$markers = @(
    'hs-support-editor-draft',
    'data-support-editor="draft"',
    'support.js',
    'quill',
    'support_draft',
    'editorText',
    'hs-support-editor-draft .ql-editor'
)
foreach ($m in $markers) {
    $ok = $sup.Content -match [regex]::Escape($m)
    Write-Host ("  {0}: {1}" -f $m, $(if ($ok) { 'OK' } else { 'MISSING' }))
}

$css = Invoke-WebRequest -Uri "$base/assets/css/app.css" -WebSession $sess -UseBasicParsing
if ($css.Content -match 'hs-support-editor-draft \.ql-editor \{ min-height: 14rem') {
    Write-Host '  draft-css-large: OK'
} else {
    Write-Host '  draft-css-large: MISSING'
}

$plan = Invoke-WebRequest -Uri "$base/panel/plan.php?lang=uk" -WebSession $sess -UseBasicParsing
@('bilohash.com', 'DemoFTP2026!', 'ns1', 'FTP') | ForEach-Object {
    $ok = $plan.Content -match [regex]::Escape($_)
    Write-Host ("  plan-{0}: {1}" -f $_, $(if ($ok) { 'OK' } else { 'MISSING' }))
}

# AI compose API smoke test
$aiBody = @{ agent = 'general'; draft = 'test notes'; site_slug = ''; lang = 'uk' } | ConvertTo-Json -Compress
try {
    $ai = Invoke-WebRequest -Uri "$base/panel/support-ai-compose.php" -Method POST -Body $aiBody -ContentType 'application/json' -WebSession $sess -UseBasicParsing
    $json = $ai.Content.TrimStart([char]0xFEFF, '?') | ConvertFrom-Json
    Write-Host ("  ai-compose: ok={0} demo={1}" -f $json.ok, $json.demo)
} catch {
    Write-Host "  ai-compose: ERROR $($_.Exception.Message)"
}

$sendBody = @{
    subject = 'Test support draft'
    body = '<p>Automated smoke test from verify script.</p>'
    category = 'support'
    site_slug = ''
    from_email = ''
    lang = 'uk'
} | ConvertTo-Json -Compress
try {
    $send = Invoke-WebRequest -Uri "$base/panel/support-owner-message.php" -Method POST -Body $sendBody -ContentType 'application/json' -WebSession $sess -UseBasicParsing
    $sjson = $send.Content.TrimStart([char]0xFEFF, '?') | ConvertFrom-Json
    Write-Host ("  send-message: ok={0} id={1}" -f $sjson.ok, $sjson.id)
} catch {
    Write-Host "  send-message: ERROR $($_.Exception.Message)"
}