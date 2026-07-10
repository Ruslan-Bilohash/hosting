# BILOHASH Hosting CMS

**PHP hostingkontrollpanel på hPanel-nivå** — selg planer, domener, fakturaer, e-post og installer hele **BILOHASH CMS-økosystemet** (15+ apper) fra ett panel.

| | |
|---|---|
| **Live demo** | https://bilohash.com/hosting/ |
| **Versjon** | **v2.7.0** |
| **Lisens** | **30 dagers GRATIS demo** — deretter [info@bilohash.com](mailto:info@bilohash.com) ([LICENSE.md](LICENSE.md)) |
| **Stack** | PHP 8.2+, MySQL 2.0, JSON fallback, Hostinger-deploy |
| **Språk** | Ukrainsk, engelsk, norsk |
| **Webmail** | https://webmail.bilohash.com/ |
| **Nyhet** | https://bilohash.com/news/hosting.html |

![Dashboard](docs/screenshots/dashboard.webp)

---

## 30 dagers gratis demo-lisens

Ved installasjon (`/install.php`) registreres **30 kalenderdager** evaluering. I demo-perioden kan du bruke alle funksjoner på egen server.

Etter 30 dager kreves kommersiell lisens fra BILOHASH: **info@bilohash.com**

---

## Hurtigstart

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
php -S localhost:8080
```

### Demo-pålogging

| Rolle | Bruker | Passord | URL |
|-------|--------|---------|-----|
| Klient | `demo` | `demo` | `/panel/` |
| Plattformadmin | `admin` | `admin` | `/login.php` |
| Super-admin | `admin` | `admin` | `/admin/login.php` |

---

## Skjermbildegalleri (WebP)

Alle bilder er **WebP uten kvalitetstap** (`screenshot/*.jpg` → `docs/screenshots/*.webp`, kvalitet 92).  
Produksjon: `https://bilohash.com/hosting/docs/screenshots/<navn>.webp`

### Dashboard og konto

![dashboard](docs/screenshots/dashboard.webp) · ![dashboard2](docs/screenshots/dashboard2.webp) · ![account](docs/screenshots/account.webp) · ![analytics](docs/screenshots/analytics.webp) · ![activity_history](docs/screenshots/activity_history.webp)

### Domener og DNS

![domains](docs/screenshots/domains.webp) · ![subdomains](docs/screenshots/subdomains.webp) · ![dns-zone](docs/screenshots/dns-zone.webp) · ![register_domains](docs/screenshots/register_domains.webp)

### Nettsteder og installer

![website](docs/screenshots/website.webp) · ![builder_website](docs/screenshots/builder_website.webp) · ![auto_installer](docs/screenshots/auto_installer.webp) · ![migrate_website](docs/screenshots/migrate_website.webp)

### WordPress og databaser

![install_wordpress](docs/screenshots/install_wordpress.webp) · ![databases_management](docs/screenshots/databases_management.webp) · ![phpmyadmin](docs/screenshots/phpmyadmin.webp)

### E-post, filer, sikkerhet

![manager_email](docs/screenshots/manager_email.webp) · ![file_manager](docs/screenshots/file_manager.webp) · ![security](docs/screenshots/security.webp) · ![ssl](docs/screenshots/ssl.webp)

**Malware-skanner** — 18 mønstre med **kritisk / høy / middels** alvorlighetsgrad.

### Ytelse, backup, support

![performance](docs/screenshots/performance.webp) · ![backups](docs/screenshots/backups.webp) · ![support](docs/screenshots/support.webp) · ![invoices](docs/screenshots/invoices.webp) · ![clients](docs/screenshots/clients.webp)

### AI API

![grok_api](docs/screenshots/grok_api.webp) · ![chat_gpt_api](docs/screenshots/chat_gpt_api.webp)

---

## Funksjoner

- **40+ verktøy** i klientpanel (domener, DNS, filer, MySQL, SSL, backup, fakturaer, analyse)
- **15 økosystem-planeter** — Shop, Booking, Auction, Faktura… ett klikk
- **MySQL 2.0** — full JSON-migrering via `/migrate-to-mysql.php`
- **BILOHASH Webmail** — IMAP/SMTP med fleksible Hostinger-innstillinger

---

## Deploy

```powershell
powershell -NoProfile -File scripts/deploy-to-hostinger.ps1
```

---

## Lenker

- Demo: https://bilohash.com/hosting/  
- Lisens: info@bilohash.com  
- GitHub: https://github.com/Ruslan-Bilohash/hosting  
- Changelog: [CHANGELOG.md](CHANGELOG.md)