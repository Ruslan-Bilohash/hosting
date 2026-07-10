$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
$root = '/home/u762384583/domains/bilohash.com/public_html'
$hosting = "$root/hosting"
try {
    Set-SCPItem @p -Path (Join-Path $PSScriptRoot 'test-support-cli.php') -Destination "$hosting/scripts/" -NewName 'test-support-cli.php'
    $cmds = @(
        "ls -la $root/includes/ecosystem-owner-messages.php 2>&1 || echo MISSING_ECO",
        "cd $hosting && php scripts/test-support-cli.php 2>&1"
    )
    foreach ($c in $cmds) {
        Write-Host "=== $c ==="
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $c -TimeOut 60
        if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
    }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}