$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
$h = '/home/u762384583/domains/bilohash.com/public_html/hosting'
try {
    Set-SCPItem @p -Path (Join-Path $PSScriptRoot 'test-support-web.php') -Destination "$h/scripts/" -NewName 'test-support-web.php'
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command "cd '$h' && php scripts/test-support-web.php 2>&1" -TimeOut 60
    if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}