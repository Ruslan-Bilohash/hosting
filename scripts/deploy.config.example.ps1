# Copy to deploy.config.local.ps1 — never commit the real file.
$DeployHost = 'your-server.example.com'
$Port       = 22
$User       = 'ssh_user'
$Password   = ''   # leave empty when using SSH keys
$RemoteRoot = '/home/USER/domains/example.com/public_html/hosting'
$LocalRoot  = 'C:\path\to\hosting'