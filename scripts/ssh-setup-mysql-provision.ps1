$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
try {
    $root = '/home/u762384583/domains/bilohash.com/public_html/hosting/data'
    $cmd = @"
if [ -f '$root/db.config.php' ] && [ ! -f '$root/mysql-provision.config.php' ]; then
  php -r '
    `$db = require \"$root/db.config.php\";
    `$out = \"<?php\\nreturn [\\n\"
      . \"    host => \" . var_export(`$db[\"host\"] ?? \"localhost\", true) . \",\\n\"
      . \"    user => \" . var_export(`$db[\"user\"] ?? \"\", true) . \",\\n\"
      . \"    pass => \" . var_export(`$db[\"pass\"] ?? \"\", true) . \",\\n\"
      . \"    db_prefix => hs_,\\n\"
      . \"];\\n\";
    file_put_contents(\"$root/mysql-provision.config.php\", `$out);
    chmod(\"$root/mysql-provision.config.php\", 0640);
    echo \"PROVISION_CONFIG_WRITTEN\";
  '
else
  echo 'SKIP_OR_EXISTS'
fi
php -r '
  require \"/home/u762384583/domains/bilohash.com/public_html/hosting/config.php\";
  require \"/home/u762384583/domains/bilohash.com/public_html/hosting/includes/database.php\";
  require \"/home/u762384583/domains/bilohash.com/public_html/hosting/includes/mysql-provision.php\";
  echo hs_mysql_provision_enabled() ? \"ENABLED\" : \"DISABLED\";
  `$pdo = hs_mysql_provision_admin_pdo();
  if (!`$pdo) { echo \" NO_PDO\"; exit; }
  try {
    `$pdo->exec(\"CREATE DATABASE IF NOT EXISTS hs_provision_test CHARACTER SET utf8mb4\");
    `$pdo->exec(\"DROP DATABASE IF NOT EXISTS hs_provision_test\");
    echo \" CREATE_OK\";
  } catch (Throwable `$e) {
    echo \" CREATE_FAIL:\" . `$e->getMessage();
  }
'
"@
    Write-Host $cmd
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $cmd -TimeOut 60
    ($r.Output + $r.Error) | ForEach-Object { Write-Host $_ }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}