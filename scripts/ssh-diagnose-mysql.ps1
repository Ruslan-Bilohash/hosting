$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
$root = '/home/u762384583/domains/bilohash.com/public_html/hosting'
try {
    $cmds = @(
        "ls -la $root/data/",
        "test -f $root/data/mysql-provision.config.php && echo HAS_PROVISION || echo NO_PROVISION",
        "test -f $root/data/ssh.config.local.php && echo HAS_SSH || echo NO_SSH",
        'php -r ''$r="/home/u762384583/domains/bilohash.com/public_html/hosting/data"; if (is_readable($r."/db.config.php")) { $c=require $r."/db.config.php"; echo "DB_HOST=".($c["host"]??"")."\n"; echo "DB_NAME=".($c["database"]??"")."\n"; echo "DB_USER=".($c["user"]??"")."\n"; }''',
        'php -r ''$r="/home/u762384583/domains/bilohash.com/public_html/hosting/data"; if (is_readable($r."/mysql-provision.config.php")) { $c=require $r."/mysql-provision.config.php"; echo "PROV_USER=".($c["user"]??"")."\n"; echo "PROV_PREFIX=".($c["name_prefix"]??"")."\n"; } else { echo "NO_PROV_FILE\n"; }''',
        'php -r ''require "/home/u762384583/domains/bilohash.com/public_html/hosting/config.php"; require "/home/u762384583/domains/bilohash.com/public_html/hosting/includes/database.php"; require "/home/u762384583/domains/bilohash.com/public_html/hosting/includes/mysql-provision.php"; echo "ENABLED=".(hs_mysql_provision_enabled()?"yes":"no")."\n"; $pdo=hs_mysql_provision_admin_pdo(); echo "PDO=".($pdo?"ok":"fail")."\n"; if ($pdo) { try { $pdo->exec("CREATE DATABASE IF NOT EXISTS u762384583_hs_provision_test CHARACTER SET utf8mb4"); $pdo->exec("DROP DATABASE IF NOT EXISTS u762384583_hs_provision_test"); echo "CREATE=ok\n"; } catch (Throwable $e) { echo "CREATE=".$e->getMessage()."\n"; } }'''
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