# Deploy Hosting CMS — production: https://bilohash.com/hosting/
# Same URL/path when you move to VPS (point DNS + upload this tree to /hosting/).
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')

$LocalRoot = 'C:\bilohash\hosting'
$RemoteRoot = '/home/u762384583/domains/bilohash.com/public_html/hosting'
$zipPath = Join-Path $env:TEMP ('hosting-deploy-' + [guid]::NewGuid().ToString('n') + '.zip')

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Push-Location $LocalRoot
try {
    & tar -a -c -f $zipPath --exclude='.git' --exclude='data/logs' --exclude='data/users.json' --exclude='data/sites.json' --exclude='data/user-settings.json' --exclude='data/domain-orders.json' --exclude='data/plans-catalog.json' --exclude='data/hosting-orders.json' --exclude='data/invoices.json' --exclude='data/invoice-counter.json' --exclude='data/client-counter.json' --exclude='data/db.config.php' --exclude='data/admin.config.php' --exclude='data/mysql-provision.config.php' --exclude='data/ssh.config.local.php' --exclude='data/pma.config.php' --exclude='data/installed.lock' --exclude='pma/vendor' --exclude='pma/js' --exclude='pma/templates' *
} finally {
    Pop-Location
}
Write-Host "Packed $((Get-Item $zipPath).Length) bytes"

Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true; ConnectionTimeout = 30 }
$s = New-SSHSession @p
try {
    Set-SCPItem @p -Path $zipPath -Destination $RemoteRoot -NewName '_hosting-deploy.zip'
    $cmd = "mkdir -p '$RemoteRoot/data' '$RemoteRoot/public_html' && cd '$RemoteRoot' && unzip -o _hosting-deploy.zip && rm -f _hosting-deploy.zip && chmod 750 data 2>/dev/null && chmod -R 755 public_html 2>/dev/null; echo DEPLOY_OK"
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $cmd -TimeOut 120
    $out = ($r.Output | Out-String) + ($r.Error | Out-String)
    Write-Host $out.Trim()
    if ($out -notmatch 'DEPLOY_OK') { throw $out.Trim() }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
    Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
}
Write-Host 'Deploy OK: https://bilohash.com/hosting/'
& (Join-Path $PSScriptRoot 'install-phpmyadmin.ps1')