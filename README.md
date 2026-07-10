# BILOHASH Hosting CMS

**hPanel-grade PHP hosting control panel** — sell hosting plans, manage domains, invoices, email and install the full **BILOHASH CMS ecosystem** (15+ apps) from one panel.

| | |
|---|---|
| **Live demo** | https://bilohash.com/hosting/ |
| **Version** | **v2.7.0** (see `includes/version.php`) |
| **License** | **30-day FREE demo** — then [info@bilohash.com](mailto:info@bilohash.com) ([LICENSE.md](LICENSE.md)) |
| **Stack** | PHP 8.2+, MySQL 2.0 schema, JSON fallback, Hostinger-ready deploy |
| **Languages** | Ukrainian, English, Norwegian (panel + public site) |
| **Webmail** | https://webmail.bilohash.com/ (IMAP/SMTP, Hostinger defaults) |
| **News** | https://bilohash.com/news/hosting.html |

![Dashboard](docs/screenshots/dashboard.webp)

---

## 30-day free demo license

On first install (`/install.php`) the platform records a **30-calendar-day evaluation license**. During demo you can use every feature on your own server.

After 30 days, commercial hosting resale, white-label branding and production support require a license from BILOHASH:

- **Email:** info@bilohash.com  
- **Details:** [LICENSE.md](LICENSE.md)

---

## Quick start (local)

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
php -S localhost:8080
```

Open http://localhost:8080/ — demo users are seeded on first visit.

### Production install

1. Upload files to your web root (or `/hosting/` subfolder).
2. Run **https://your-domain/install.php** — accept the 30-day demo terms.
3. Optional: configure MySQL in `data/db.config.php` and migrate JSON via `/migrate-to-mysql.php`.
4. Copy `config.local.php` from `config.local.example.php` for SSH, server IP and webmail host.

### Demo credentials

| Role | Login | Password | URL |
|------|-------|----------|-----|
| Client panel | `demo` | `demo` | `/login.php` → `/panel/` |
| Platform admin | `admin` | `admin` | `/login.php` (Clients, impersonation) |
| Super-admin | `admin` | `admin` | `/admin/login.php` |

---

## Screenshots gallery

All images are **lossless-quality WebP** converted from `screenshot/` → `docs/screenshots/` (quality 92, no visual loss).  
Live URLs: `https://bilohash.com/hosting/docs/screenshots/<name>.webp`

### Dashboard & account

| Screen | Preview |
|--------|---------|
| Main dashboard | ![dashboard](docs/screenshots/dashboard.webp) |
| Dashboard (alt) | ![dashboard2](docs/screenshots/dashboard2.webp) |
| Client dashboard | ![dashboard_client](docs/screenshots/dashboard_client.webp) |
| Account settings | ![account](docs/screenshots/account.webp) |
| Plan details | ![plan_detalis](docs/screenshots/plan_detalis.webp) |
| Plan details (2) | ![plan_detalis2](docs/screenshots/plan_detalis2.webp) |
| Hosting plan renew | ![hostingplan_renew](docs/screenshots/hostingplan_renew.webp) |
| Resource usage | ![resourse_usage](docs/screenshots/resourse_usage.webp) |
| Activity history | ![activity_history](docs/screenshots/activity_history.webp) |
| Analytics | ![analytics](docs/screenshots/analytics.webp) |

### Domains & DNS

| Screen | Preview |
|--------|---------|
| Domains overview | ![domains](docs/screenshots/domains.webp) |
| Domains (2) | ![domains2](docs/screenshots/domains2.webp) |
| Register domains | ![register_domains](docs/screenshots/register_domains.webp) |
| Subdomains | ![subdomains](docs/screenshots/subdomains.webp) |
| Parked domains | ![parked_domains](docs/screenshots/parked_domains.webp) |
| Redirects | ![redirect](docs/screenshots/redirect.webp) |
| DNS zone | ![dns-zone](docs/screenshots/dns-zone.webp) |
| DNS zone editor | ![dns_zone_editer](docs/screenshots/dns_zone_editer.webp) |

### Websites, builder & migration

| Screen | Preview |
|--------|---------|
| Websites list | ![website](docs/screenshots/website.webp) |
| Website builder | ![builder_website](docs/screenshots/builder_website.webp) |
| Builder main | ![builder_website_main](docs/screenshots/builder_website_main.webp) |
| Builder HTML | ![builder_website_html](docs/screenshots/builder_website_html.webp) |
| Copy website | ![copy_website](docs/screenshots/copy_website.webp) |
| Migrate website | ![migrate_website](docs/screenshots/migrate_website.webp) |
| Auto installer (ecosystem) | ![auto_installer](docs/screenshots/auto_installer.webp) |

### WordPress

| Screen | Preview |
|--------|---------|
| Install WordPress | ![install_wordpress](docs/screenshots/install_wordpress.webp) |
| WP security | ![install_wordpress_security](docs/screenshots/install_wordpress_security.webp) |
| WP auto-updater | ![wordpress_autoupdater](docs/screenshots/wordpress_autoupdater.webp) |

### Databases & phpMyAdmin

| Screen | Preview |
|--------|---------|
| Database management | ![databases_management](docs/screenshots/databases_management.webp) |
| phpMyAdmin | ![phpmyadmin](docs/screenshots/phpmyadmin.webp) |
| Remote MySQL | ![remote_mysql](docs/screenshots/remote_mysql.webp) |

### Email & webmail

| Screen | Preview |
|--------|---------|
| Email accounts | ![manager_email](docs/screenshots/manager_email.webp) |

**BILOHASH Webmail** (`webmail.bilohash.com`) — lightweight IMAP inbox + SMTP compose. Defaults: `imap.hostinger.com:993`, `smtp.hostinger.com:465` SSL. Override in `webmail/config.local.php`.

### Files, FTP & Git

| Screen | Preview |
|--------|---------|
| File manager | ![file_manager](docs/screenshots/file_manager.webp) |
| Change permissions | ![change_permissions](docs/screenshots/change_permissions.webp) |
| Folder indexing | ![folder_indexing](docs/screenshots/folder_indexing.webp) |
| FTP accounts | ![ftp_accounts](docs/screenshots/ftp_accounts.webp) |
| Git deploy | ![git_deploy](docs/screenshots/git_deploy.webp) |
| SSH access | ![ssh_access](docs/screenshots/ssh_access.webp) |

### Security

| Screen | Preview |
|--------|---------|
| Security overview | ![security](docs/screenshots/security.webp) |

**Malware scanner** (`/panel/security.php?tab=malware`) — 18 detection patterns with **critical / high / medium** severity badges, findings table, scan scope legend. Scans PHP, JS, HTML and `.htaccess` in `public_html` (up to 8 000 files).

### Performance & cache

| Screen | Preview |
|--------|---------|
| Performance | ![performance](docs/screenshots/performance.webp) |
| Website speed | ![website_speed](docs/screenshots/website_speed.webp) |
| Object cache | ![object_cache](docs/screenshots/object_cache.webp) |
| LiteSpeed cache | ![lite_speed_cache](docs/screenshots/lite_speed_cache.webp) |
| CDN | ![cdn](docs/screenshots/cdn.webp) |

### Backups, cron, PHP

| Screen | Preview |
|--------|---------|
| Backups | ![backups](docs/screenshots/backups.webp) |
| Backups (2) | ![backups2](docs/screenshots/backups2.webp) |
| Cron jobs | ![cron_jobs](docs/screenshots/cron_jobs.webp) |
| PHP configuration | ![php_configuration](docs/screenshots/php_configuration.webp) |
| Password protect dirs | ![password_protect_directories](docs/screenshots/password_protect_directories.webp) |
| IP manager | ![ip_manager](docs/screenshots/ip_manager.webp) |
| SSL | ![ssl](docs/screenshots/ssl.webp) |
| Change password | ![charge_password](docs/screenshots/charge_password.webp) |

### Support, invoices & admin

| Screen | Preview |
|--------|---------|
| Support tickets | ![support](docs/screenshots/support.webp) |
| New message | ![support_new_message](docs/screenshots/support_new_message.webp) |
| New message (2) | ![support_new_message2](docs/screenshots/support_new_message2.webp) |
| Invoices | ![invoices](docs/screenshots/invoices.webp) |
| Invoices (2) | ![invoices2](docs/screenshots/invoices2.webp) |
| Platform clients | ![clients](docs/screenshots/clients.webp) |

### AI API keys

| Screen | Preview |
|--------|---------|
| Grok API | ![grok_api](docs/screenshots/grok_api.webp) |
| ChatGPT API | ![chat_gpt_api](docs/screenshots/chat_gpt_api.webp) |

---

## Feature overview

### Client panel (40+ tools)

- **Dashboard** — disk, plan, domains, quick actions  
- **Domains** — register, DNS zone editor, subdomains, parked domains, redirects  
- **Websites** — file manager, copy/migrate, landing builder, ecosystem auto-installer  
- **WordPress** — one-click install, security hardening, auto-updates  
- **Databases** — MySQL DBs, phpMyAdmin link, remote MySQL  
- **Email** — mailboxes, forwarders, link to BILOHASH Webmail  
- **Security** — SSL, firewall, malware scan, IP block, hotlink, indexing  
- **Performance** — cache, CDN hints, speed tools  
- **Backups** — on-demand and scheduled snapshots  
- **Support** — per-account ticket mailbox  
- **Invoices** — auto-generated hosting invoices (v2.4+)  
- **Resources** — usage charts (30-day disk/memory)  
- **Analytics** — visits and activity log with GeoIP  

### Platform admin

- Client list with filters, stats, impersonation  
- Plans catalog, orders, exchange rates  
- Activity logs (20/page), disk charts per client  
- MySQL migration wizard  

### Ecosystem “planets” (one-click install)

Install **15+ BILOHASH apps** into client `public_html`:

Shop, Booking, Auction, Freelance, Pizza, Today, GameHub, Bilen CMS, Faktura, Business Landing, News, WordPress, 3D, AI — planet **Nucleus** (Hosting) is the control center.

---

## MySQL 2.0

Schema 2.0 stores users, sites, settings, invoices, orders, logs, plans and meta in MySQL. JSON files remain as fallback until migration.

```text
/install.php          → fresh install + 30-day license meta
/migrate-to-mysql.php → one-time JSON import (platform admin)
```

Tables are auto-upgraded on boot via `hs_db_ensure_schema()`.

---

## Configuration

| File | Purpose |
|------|---------|
| `config.local.php` | SSH host, server IP, webmail host (never commit) |
| `data/db.config.php` | MySQL credentials |
| `data/admin.config.php` | Super-admin password |
| `scripts/deploy.config.local.ps1` | Hostinger deploy SSH |
| `webmail/config.local.php` | Flexible IMAP/SMTP for Hostinger or custom MX |

### Never commit

- `config.local.php`, `scripts/deploy.config.local.ps1`
- `data/*.json`, `data/db.config.php`, `data/admin.config.php`
- `data/mysql-provision.config.php`, `data/ssh.config.local.php`
- `public_html/*/` (client sites at runtime)

---

## Deploy to Hostinger

```powershell
cd C:\bilohash\hosting
powershell -NoProfile -File scripts/deploy-to-hostinger.ps1
```

Deploys code + `docs/screenshots/`; excludes raw `screenshot/` JPGs and production secrets.

---

## Project layout

```text
hosting/
├── index.php              # Sales landing + domain search
├── install.php            # Installer + 30-day license
├── migrate-to-mysql.php   # JSON → MySQL
├── register.php, checkout.php
├── panel/                 # Client hPanel UI
├── admin/                 # Operator back-office
├── includes/              # Core PHP (hs_* functions)
├── lang/                  # i18n (uk, en, no)
├── docs/screenshots/      # WebP gallery (60 screens)
├── templates/             # Per-app install templates
├── data/                  # JSON / MySQL config
└── scripts/               # Deploy & tools
```

---

## Links

- **Live demo:** https://bilohash.com/hosting/  
- **News article:** https://bilohash.com/news/hosting.html  
- **Webmail:** https://webmail.bilohash.com/  
- **GitHub:** https://github.com/Ruslan-Bilohash/hosting  
- **License:** info@bilohash.com  
- **Ecosystem:** https://bilohash.com/ecosystem/join.php  

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).