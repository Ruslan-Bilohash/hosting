$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$s = New-SSHSession -ComputerName $DeployHost -Port $Port -Credential $cred -AcceptKey
try {
    $cmds = @(
        'ls -la /home/u762384583/domains/bilohash.com/public_html/wordpress/wp-content/plugins 2>&1 | head -15',
        'ls -la /home/u762384583/domains/bilohash.com/public_html/hosting/wordpress-plugins 2>&1 | head -5'
    )
    foreach ($c in $cmds) {
        Write-Host "=== $c ==="
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $c
        $r.Output | ForEach-Object { Write-Host $_ }
    }
} finally { Remove-SSHSession -SessionId $s.SessionId | Out-Null }