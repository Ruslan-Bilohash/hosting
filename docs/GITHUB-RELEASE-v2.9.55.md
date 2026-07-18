# Hosting CMS v2.9.55 — Public test release

**Tag:** `v2.9.55`  
**Date:** 2026-07-18  
**Live:** https://solaskinner.com/  
**Repo:** https://github.com/Ruslan-Bilohash/hosting  

## 30-day FREE demo

Install → `/install.php` → evaluation license **30 days** (MySQL 2.0 included).  
License file: [LICENSE.md](../LICENSE.md)

| Role | Login | Password |
|------|-------|----------|
| Client | `demo` | `demo` |
| Admin | `admin` | `admin` |
| Super-admin | `admin` | `admin` → `/admin/login.php` |

## Full functionality

### Public site
- Marketing homepage (speed hero, plans, ecosystem)
- Domain search / cart / checkout
- Multi-language (uk, en, no, …)
- SEO app landings (`/seo/hosting-for-*.php`)
- Legal: terms, privacy, cookies
- Install wizard (`install.php`)

### Client panel (`/panel/`)
- Dashboard, websites, file manager, FTP/SFTP
- Domains, DNS, SSL, email, databases, phpMyAdmin
- WordPress, app installer (15+ ecosystem apps)
- Backups, Git deploy, cron, PHP config
- Performance, security, malware scan
- Invoices (PDF), plan renew/change, support tickets
- SSH / secure access page, account & master password

### Platform admin (`/admin/`)
- Clients, impersonation, plans, coupons, payments
- Namecheap domains API, MySQL tools
- **cPanel / WHM Nebula pool** (auto-provision after pay)
- File manager, support, debugger, server health
- Ops guide

### Hosting automation (new in 2.9.x)
- WHM API createacct / packages / SSO
- Bridge for shared hosts that block outbound :2087
- Pool quotas: accounts + disk GB

## Screenshots

All files in [`docs/screenshots/`](screenshots/) (WebP).

### Dashboard & account
| | |
|--|--|
| ![dashboard](screenshots/dashboard.webp) | ![account](screenshots/account.webp) |
| ![dashboard_client](screenshots/dashboard_client.webp) | ![resourse_usage](screenshots/resourse_usage.webp) |

### Domains & files
| | |
|--|--|
| ![domains](screenshots/domains.webp) | ![file_manager](screenshots/file_manager.webp) |
| ![dns-zone](screenshots/dns-zone.webp) | ![ssl](screenshots/ssl.webp) |

### Apps & security
| | |
|--|--|
| ![auto_installer](screenshots/auto_installer.webp) | ![security](screenshots/security.webp) |
| ![install_wordpress](screenshots/install_wordpress.webp) | ![databases_management](screenshots/databases_management.webp) |

### Admin & support
| | |
|--|--|
| ![clients](screenshots/clients.webp) | ![support](screenshots/support.webp) |
| ![invoices](screenshots/invoices.webp) | ![performance](screenshots/performance.webp) |

## Quick start

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
git checkout v2.9.55   # or latest main
php -S localhost:8080
# open http://localhost:8080/install.php
```

Or download the Release ZIP from GitHub Releases.

## Requirements

- PHP 8.2+ (curl, json, mbstring, openssl)
- Optional MySQL 8 / MariaDB for production schema
- Writable `data/` directory

## Security notes for testers

- Change `admin` / `demo` passwords immediately on public hosts
- Never commit `data/*.json`, WHM tokens, or `config.local.php`
- WHM bridge template secret must be set to a long random value

## Changelog

See [CHANGELOG.md](../CHANGELOG.md) section **2.9.55**.

## Contact

- Product: https://solaskinner.com/  
- License / commercial: info@bilohash.com  
