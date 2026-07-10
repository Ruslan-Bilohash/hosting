$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
try {
    $cmds = @(
        'mysql -e "SHOW DATABASES LIKE ''u762384583%'';" 2>&1 | head -30',
        'mysql -e "SELECT user, host FROM mysql.user WHERE user LIKE ''u762384583%'';" 2>&1 | head -20',
        'cat /home/u762384583/domains/bilohash.com/public_html/hosting/data/mysql-provision.config.php',
        'cat /home/u762384583/domains/bilohash.com/public_html/hosting/data/db.config.php',
        'php -r ''$j=json_decode(file_get_contents("/home/u762384583/domains/bilohash.com/public_html/hosting/data/user-settings.json"),true); foreach($j as $uid=>$s){ if(!empty($s["databases"])) { echo $uid.": ".json_encode($s["databases"])."\n"; } }'''
    )
    foreach ($c in $cmds) {
        Write-Host "=== $c ==="
        $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $c -TimeOut 60
        if ($r.Output) { $r.Output | ForEach-Object { Write-Host $_ } }
        if ($r.Error) { $r.Error | ForEach-Object { Write-Host "ERR: $_" } }
    }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}