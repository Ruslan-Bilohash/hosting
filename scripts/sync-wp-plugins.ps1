# Sync BILOHASH bundled plugins to production library (public_html/wordpress/wp-content/plugins)
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')

$LocalPlugins = 'C:\bilohash\wordpress\wp-content\plugins'
$RemotePlugins = '/home/u762384583/domains/bilohash.com/public_html/wordpress/wp-content/plugins'
$Slugs = @(
    'bilohash-ai-chat-consultant',
    'bilohash-smart-popups',
    'bilohash-booking',
    'callback-request-by-bilohash',
    'Redirect-Call-Widgets'
)

Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
try {
    foreach ($slug in $Slugs) {
        $src = Join-Path $LocalPlugins $slug
        if (-not (Test-Path $src)) {
            Write-Warning "Skip missing local: $slug"
            continue
        }
        $zipPath = Join-Path $env:TEMP ("wp-plugin-$slug.zip")
        if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
        Push-Location $src
        try { & tar -a -c -f $zipPath * } finally { Pop-Location }
        Set-SCPItem @p -Path $zipPath -Destination $RemotePlugins -NewName "_$slug.zip"
        $cmd = "cd '$RemotePlugins' && unzip -o '_$slug.zip' -d '$slug' 2>/dev/null || (mkdir -p '$slug' && unzip -o '_$slug.zip' -d '$slug') && rm -f '_$slug.zip' && echo SYNC_OK_$slug"
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $cmd -TimeOut 120
        $out = ($r.Output | Out-String).Trim()
        Write-Host $out
        Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
    }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}
Write-Host 'Plugin sync done.'