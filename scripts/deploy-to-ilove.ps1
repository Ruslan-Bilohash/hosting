# Deploy Hosting CMS to ilove.lt (document root)
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')

$LocalRoot = 'C:\bilohash\hosting'
$RemoteRoot = '/home/u762384583/domains/ilove.lt/public_html'
$zipPath = Join-Path $env:TEMP ('ilove-hosting-' + [guid]::NewGuid().ToString('n') + '.zip')

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Push-Location $LocalRoot
try {
    & tar -a -c -f $zipPath --exclude='data/users.json' --exclude='data/sites.json' --exclude='data/user-settings.json' *
} finally {
    Pop-Location
}
Write-Host "Packed $((Get-Item $zipPath).Length) bytes"

Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true; ConnectionTimeout = 30 }
$s = New-SSHSession @p
try {
    Set-SCPItem @p -Path $zipPath -Destination $RemoteRoot -NewName '_ilove-deploy.zip'
    $cmd = "mkdir -p '$RemoteRoot/data' '$RemoteRoot/public_html' && cd '$RemoteRoot' && unzip -o _ilove-deploy.zip && rm -f _ilove-deploy.zip && chmod 750 data 2>/dev/null && chmod -R 755 public_html 2>/dev/null; echo DEPLOY_OK"
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $cmd -TimeOut 180
    $out = ($r.Output | Out-String) + ($r.Error | Out-String)
    Write-Host $out.Trim()
    if ($out -notmatch 'DEPLOY_OK') { throw $out.Trim() }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
    Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
}
Write-Host 'Deploy OK: https://ilove.lt/'