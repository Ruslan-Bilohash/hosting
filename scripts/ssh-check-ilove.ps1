$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true; ConnectionTimeout = 30 }
$s = New-SSHSession @p
try {
    $root = '/home/u762384583/domains/ilove.lt/public_html'
    $cmds = @(
        "php -l $root/login.php 2>&1",
        "php -l $root/includes/panel-nav.php 2>&1",
        "php -l $root/includes/panel-tabs.php 2>&1",
        "php -l $root/includes/menu-registry.php 2>&1",
        "php -d display_errors=1 $root/login.php 2>&1 | head -40",
        "tail -n 30 $root/error_log 2>/dev/null",
        "tail -n 30 /home/u762384583/domains/ilove.lt/logs/error.log 2>/dev/null"
    )
    foreach ($c in $cmds) {
        Write-Host "=== $c ==="
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $c -TimeOut 30
        if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
        if ($r.Error) { $r.Error | ForEach-Object { Write-Host $_ } }
    }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}