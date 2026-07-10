$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\shop\scripts\deploy.config.local.ps1')
Import-Module Posh-SSH -ErrorAction Stop
$cred = New-Object PSCredential ($User, (ConvertTo-SecureString $Password -AsPlainText -Force))
$p = @{ ComputerName = $DeployHost; Port = $Port; Credential = $cred; AcceptKey = $true }
$s = New-SSHSession @p
try {
    $data = '/home/u762384583/domains/bilohash.com/public_html/hosting/data'
    $php = @"
<?php
`$f = '$data/mysql-provision.config.php';
`$cfg = require `$f;
`$cfg['port'] = (int) (`$cfg['port'] ?? 3306);
`$cfg['name_prefix'] = 'u762384583_';
`$cfg['client_host'] = (string) (`$cfg['client_host'] ?? `$cfg['host'] ?? 'localhost');
`$cfg['grant_host'] = (string) (`$cfg['grant_host'] ?? 'localhost');
`$out = "<?php\nreturn " . var_export(`$cfg, true) . ";\n";
file_put_contents(`$f, `$out);
chmod(`$f, 0640);
echo 'UPDATED';
"@
    $r = Invoke-SSHCommand -SessionId $s.SessionId -Command "php -r '$php'" -TimeOut 30
    $r.Output
    Invoke-SSHCommand -SessionId $s.SessionId -Command 'cd /home/u762384583/domains/bilohash.com/public_html/hosting && php scripts/test-provision.php' -TimeOut 30 | ForEach-Object { $_.Output }
} finally {
    Remove-SSHSession -SessionId $s.SessionId | Out-Null
}