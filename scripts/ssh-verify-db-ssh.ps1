$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
$root = '/home/u762384583/domains/bilohash.com/public_html/hosting'
try {
    Set-SCPItem @p -Path (Join-Path $PSScriptRoot 'verify-shared-mysql.php') -Destination "$root/scripts/" -NewName 'verify-shared-mysql.php'
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command "cd '$root' && php scripts/verify-shared-mysql.php" -TimeOut 60
    if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
    if ($r.Error) { $r.Error | ForEach-Object { Write-Host "ERR: $_" } }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}