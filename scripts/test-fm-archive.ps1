$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$loginPage = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($loginPage.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$filesPage = Invoke-WebRequest -Uri "$base/panel/files.php" -WebSession $sess -UseBasicParsing
$fmCsrf = if ($filesPage.Content -match '"csrf":"([^"]+)"') { $Matches[1] } else { '' }
Write-Host "CSRF OK:" ($fmCsrf.Length -gt 0)

$post = {
    param($action, $extra)
    $body = @{ action = $action; csrf = $fmCsrf }
    foreach ($k in $extra.Keys) { $body[$k] = $extra[$k] }
    (Invoke-WebRequest -Uri "$base/panel/files-api.php" -Method POST -Body $body -WebSession $sess -UseBasicParsing).Content
}

Write-Host "Create:" (& $post 'create' @{ path = ''; name = 'fm-test-archive.txt' })
Write-Host "Archive:" (& $post 'archive' @{ path = 'fm-test-archive.txt'; name = 'fm-test-archive.zip' })
Write-Host "Extract:" (& $post 'extract' @{ path = 'fm-test-archive.zip' })
& $post 'delete' @{ path = 'fm-test-archive.txt' } | Out-Null
& $post 'delete' @{ path = 'fm-test-archive.zip' } | Out-Null
& $post 'delete' @{ path = 'fm-test-archive' } | Out-Null
& $post 'delete' @{ path = 'fm-test-archive-extracted' } | Out-Null

$ukPage = Invoke-WebRequest -Uri "$base/panel/files.php?lang=uk" -WebSession $sess -UseBasicParsing
if ($ukPage.Content -match 'fm_archive.*ZIP') { Write-Host 'UK i18n key OK' } else { Write-Host 'UK i18n key MISSING' }