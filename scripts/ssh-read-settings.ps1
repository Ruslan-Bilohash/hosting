$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
try {
    $root = '/home/u762384583/domains/ilove.lt/public_html/data'
    foreach ($c in @("cat $root/user-settings.json", "dig +short MX ilove.lt", "curl -sI https://webmail.ilove.lt | head -5")) {
        Write-Host "=== $c ==="
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $c -TimeOut 20
        if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
    }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}