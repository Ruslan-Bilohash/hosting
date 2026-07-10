$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
$root = '/home/u762384583/domains/bilohash.com/public_html/hosting'
$data = "$root/data"
$tmpDir = Join-Path $env:TEMP ('hs-ssh-setup-' + [guid]::NewGuid().ToString('n'))
New-Item -ItemType Directory -Path $tmpDir | Out-Null
$sshLocal = Join-Path $tmpDir 'ssh.config.local.php'
@(
    '<?php',
    'define(''HS_SSH_PASSWORD_SET'', true);',
    ('define(''HS_SSH_PASSWORD'', ' + (ConvertTo-Json $Password) + ');')
) | Set-Content -Path $sshLocal -Encoding UTF8
try {
    Set-SCPItem @p -Path (Join-Path $PSScriptRoot 'setup-shared-mysql.php') -Destination "$root/scripts/" -NewName 'setup-shared-mysql.php'
    Set-SCPItem @p -Path $sshLocal -Destination $data -NewName 'ssh.config.local.php'
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command "chmod 640 '$data/ssh.config.local.php' && cd '$root' && php scripts/setup-shared-mysql.php" -TimeOut 120
    if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
    if ($r.Error) { $r.Error | ForEach-Object { Write-Host "ERR: $_" } }
} finally {
    Remove-Item -Recurse -Force $tmpDir -ErrorAction SilentlyContinue
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}