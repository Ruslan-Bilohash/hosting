# Deploy Hosting CMS — configure paths in scripts/deploy.config.local.ps1 (see .example).
$ErrorActionPreference = 'Stop'

$cfgLocal = Join-Path $PSScriptRoot 'deploy.config.local.ps1'
$cfgShop = Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1'
if (Test-Path $cfgLocal) {
    . $cfgLocal
} elseif (Test-Path $cfgShop) {
    . $cfgShop
} else {
    throw 'Missing deploy.config.local.ps1 — copy scripts/deploy.config.example.ps1'
}

if (-not $LocalRoot) { $LocalRoot = 'C:\bilohash\hosting' }
if (-not $RemoteRoot) { throw 'Set $RemoteRoot in deploy.config.local.ps1' }

$zipPath = Join-Path $env:TEMP ('hosting-deploy-' + [guid]::NewGuid().ToString('n') + '.zip')

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Push-Location $LocalRoot
try {
    & tar -a -c -f $zipPath `
        --exclude='.git' `
        --exclude='screenshot' `
        --exclude='scripts/deploy.config.local.ps1' `
        --exclude='scripts/ssh-*' `
        --exclude='scripts/deploy-to-ilove.ps1' `
        --exclude='scripts/setup-shared-mysql.php' `
        --exclude='scripts/write-provision-from-dbconfig.php' `
        --exclude='scripts/test-provision.php' `
        --exclude='data/logs' `
        --exclude='data/users.json' --exclude='data/sites.json' --exclude='data/user-settings.json' `
        --exclude='data/domain-orders.json' --exclude='data/plans-catalog.json' --exclude='data/hosting-orders.json' `
        --exclude='data/invoices.json' --exclude='data/invoice-counter.json' --exclude='data/client-counter.json' `
        --exclude='data/db.config.php' --exclude='data/admin.config.php' --exclude='data/mysql-provision.config.php' `
        --exclude='data/ssh.config.local.php' --exclude='data/pma.config.php' --exclude='data/installed.lock' `
        --exclude='public_html/demo' --exclude='public_html/admin' `
        --exclude='pma/vendor' --exclude='pma/js' --exclude='pma/templates' *
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
Write-Host "Deploy OK: $RemoteRoot"
& (Join-Path $PSScriptRoot 'install-phpmyadmin.ps1')