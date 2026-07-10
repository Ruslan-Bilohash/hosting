# BILOHASH Hosting CMS

**hPanel-grade hosting control panel** — sell plans, domains, invoices and a full **BILOHASH CMS ecosystem** (15+ apps) from one PHP panel.

- **Live demo:** https://bilohash.com/hosting/
- **Version:** see `includes/version.php` (currently v2.5.1)
- **Stack:** PHP 8.2+, JSON storage today → **full MySQL migration planned**

## Quick start (local)

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
php -S localhost:8080
```

Open http://localhost:8080/ — on first visit demo users are seeded automatically.

### Demo credentials

| Role | Login | Password | URL |
|------|-------|----------|-----|
| Client panel | `demo` | `demo` | `/login.php` → `/panel/` |
| Platform admin | `admin` | `admin` | `/login.php` (Clients, impersonation) |
| Super-admin UI | `admin` | `admin` | `/admin/login.php` |

## What you get

- **Client panel** — dashboard, domains, DNS, files, MySQL, backups, SSL, performance, support, invoices, landing builder, WordPress, Git deploy
- **15 ecosystem “planets”** — Shop, Booking, Auction, Faktura, News, AI… install in one click
- **Business features** — auto invoices, client IDs (`BH-CL-#####`), per-account support mailboxes, activity logs (20/page)
- **i18n** — Ukrainian, English, Norwegian (panel + public site)
- **Deploy** — `scripts/deploy-to-hostinger.ps1` (Hostinger SSH)

## Project layout

```
hosting/
├── index.php              # Sales landing + domain search
├── register.php, checkout.php
├── panel/                 # Client hPanel UI
├── admin/                 # Operator back-office
├── includes/              # Core PHP (hs_* functions)
├── lang/                  # i18n
├── templates/             # Per-app install templates
├── data/                  # JSON storage (gitignored in prod)
├── prompt/                # AI agent handoff docs
└── scripts/               # Deploy & verify
```

## Configuration

1. Copy `config.local.example.php` → `config.local.php` (SSH host, server IP — **never commit**)
2. Copy `scripts/deploy.config.example.ps1` → `scripts/deploy.config.local.ps1` for production deploy
3. Copy `data/db.config.example.php` → `data/db.config.php` when using shared MySQL installer
4. Copy `data/admin.config.example.php` → `data/admin.config.php` for custom super-admin password
5. Run web installer: `/install.php` (optional MySQL schema)

### Never commit to Git

- `config.local.php`, `scripts/deploy.config.local.ps1`
- `data/*.json` (users, sites, settings, orders, invoices, logs)
- `data/db.config.php`, `data/admin.config.php`, `data/mysql-provision.config.php`, `data/ssh.config.local.php`, `data/pma.config.php`
- `public_html/*/` (client sites — created by installer at runtime)

## Roadmap

- [x] Full hPanel-style UI, ecosystem installer, invoicing v2.4
- [x] Per-user activity logs (`data/logs/{userId}.json`)
- [x] `php.ini` per account (not `.user.ini`)
- [ ] **Full MySQL migration** — users, sites, settings, logs, orders (replace JSON files)
- [ ] Real payment gateway (Stripe/Vipps)
- [ ] Live domain registrar API

## License

Proprietary BILOHASH stack — open source on GitHub for evaluation, fork and self-host. Contact [bilohash.com](https://bilohash.com) for commercial white-label.

## Links

- [CHANGELOG.md](CHANGELOG.md)
- [README-ua.md](README-ua.md) · [README-no.md](README-no.md)
- [AI handoff](prompt/AI-HANDOFF.md)