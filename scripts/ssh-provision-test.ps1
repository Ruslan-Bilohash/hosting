$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
try {
    $data = '/home/u762384583/domains/bilohash.com/public_html/hosting/data'
    $hosting = '/home/u762384583/domains/bilohash.com/public_html/hosting'
    $cmds = @(
        "ls -la $data",
        "cd $hosting && php scripts/write-provision-from-dbconfig.php",
        "cd $hosting && php scripts/test-provision.php"
    )
    foreach ($c in $cmds) {
        Write-Host "=== $c ==="
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $c -TimeOut 30
        if ($r.Output) { $r.Output }
        if ($r.Error) { $r.Error }
    }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}