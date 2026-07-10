# Install phpMyAdmin 5.2.2 into hosting/pma/ on production (preserves config.inc.php from deploy).
$ErrorActionPreference = 'Stop'

$cfgLocal = Join-Path $PSScriptRoot 'deploy.config.local.ps1'
$cfgShop = Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1'
if (Test-Path $cfgLocal) {
    . $cfgLocal
} elseif (Test-Path $cfgShop) {
    . $cfgShop
} else {
    throw 'Missing deploy.config.local.ps1'
}
if (-not $RemoteRoot) { throw 'Set $RemoteRoot in deploy.config.local.ps1' }
$PmaUrl = 'https://files.phpmyadmin.net/phpMyAdmin/5.2.2/phpMyAdmin-5.2.2-all-languages.tar.gz'

Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true; ConnectionTimeout = 60 }
$s = New-SSHSession @p
try {
    $cmd = @"
set -e
cd '$RemoteRoot'
mkdir -p data/pma-tmp
chmod 750 data/pma-tmp 2>/dev/null || true
if [ ! -f pma/index.php ]; then
  echo 'Downloading phpMyAdmin...'
  curl -fsSL -o /tmp/hosting-pma.tar.gz '$PmaUrl'
  rm -rf /tmp/pma-staging
  mkdir -p /tmp/pma-staging
  tar -xzf /tmp/hosting-pma.tar.gz -C /tmp/pma-staging
  rm -rf pma/vendor 2>/dev/null || true
  cp -a /tmp/pma-staging/phpMyAdmin-5.2.2-all-languages/. pma/
  rm -rf /tmp/pma-staging /tmp/hosting-pma.tar.gz
  echo 'phpMyAdmin files installed'
else
  echo 'phpMyAdmin already present'
fi
if [ ! -f data/pma.config.php ]; then
  SEC=`$(php -r 'echo bin2hex(random_bytes(24));')
  echo "<?php" > data/pma.config.php
  echo "return ['blowfish_secret' => '`${SEC}'];" >> data/pma.config.php
  chmod 640 data/pma.config.php
  echo 'Created data/pma.config.php'
fi
test -f pma/index.php && test -f pma/config.inc.php && echo PMA_INSTALL_OK
"@
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command $cmd -TimeOut 300
    $out = ($r.Output | Out-String) + ($r.Error | Out-String)
    Write-Host $out.Trim()
    if ($out -notmatch 'PMA_INSTALL_OK') { throw $out.Trim() }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}
Write-Host "phpMyAdmin OK: https://bilohash.com/hosting/pma/ (signon via panel)"