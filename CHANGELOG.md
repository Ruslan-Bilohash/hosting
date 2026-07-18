# Changelog

All notable changes to BILOHASH Hosting CMS / SolaSkinner Hosting panel.

## [2.9.55] — 2026-07-18 — Public test release

### Added
- **WHM / Nebula cPanel pool** — auto-provision client cPanels after hosting payment (`admin/cpanel-pool.php`, `includes/whm-api.php`, `includes/cpanel-provision.php`)
- **HTTPS bridge** for Namecheap Stellar→Nebula when outbound `:2087` is blocked (`tools/sola-whm-bridge.php`)
- Plan packages: `sola_mini`, `sola_starter`, `sola_plus`, `sola_business`, `sola_vps` with disk quotas
- Per-client FTP jail onboarding, SFTP/secure access panel page
- Admin debugger health report; pool limits (25 accounts / 30 GB Nebula)
- Live production: **https://solaskinner.com/** (SolaSkinner brand)

### Security
- WHM bridge: function allowlist, IP allowlist, rate limit, secret header only
- `.htaccess` denies `data/`, `includes/`, `scripts/`, `tools/`
- Secrets (`data/*.json`, API tokens, `config.local.php`) never committed
- Client packages: shell disabled by default (`hasshell: false`)

### Install / demo
- `/install.php` — 30-day FREE demo license + MySQL 2.0
- Demo users `demo` / `demo`, admin `admin` / `admin`
- Full frontend + panel CSS/JS as shipped on solaskinner.com
- 60 WebP screenshots in `docs/screenshots/`

### Changed
- Version **2.9.55** (`includes/version.php`)
- README / LICENSE updated for public test release

## [2.7.2] — 2026-07-10

### Added
- **GitHub Packages** — Docker image `ghcr.io/ruslan-bilohash/hosting` (PHP 8.2 + Apache)
- **Release demo ZIP** — `hosting-cms-demo-30d-{version}.zip` (30-day MySQL demo, no secrets)
- `composer.json` — package `bilohash/hosting-cms`
- `.github/workflows/publish-package.yml` — auto-publish on GitHub Release

### Install package
```bash
docker pull ghcr.io/ruslan-bilohash/hosting:v2.7.2
docker run -p 8080:80 ghcr.io/ruslan-bilohash/hosting:v2.7.2
# Open http://localhost:8080/install.php
```

## [2.7.1] — 2026-07-10

### Added
- **MySQL 2.0 — 30 days FREE for testing** — full MySQL schema (users, sites, invoices, orders, logs) usable at no cost during the demo period; documented in `LICENSE.md`, install wizard (uk/en/no) and GitHub Release notes
- `docs/GITHUB-RELEASE-v2.7.1.md` — multilingual release description with screenshot gallery links

### Changed
- Install wizard license block clarifies: MySQL install + JSON migration included in the 30-day evaluation
- README (en/ua/no) — MySQL 2.0 demo testing highlighted at the top

### License
- **30-day FREE demo** for self-hosted evaluation and MySQL testing
- After 30 days: [info@bilohash.com](mailto:info@bilohash.com)

## [2.7.0] — 2026-07-10

### Added
- **30-day FREE demo license** — documented in `LICENSE.md`; install wizard records `license_started_at` + `license_demo_days=30`
- **WebP screenshot gallery** — 60 panel screenshots in `docs/screenshots/` (lossless-quality WebP from `screenshot/`)
- **BILOHASH Webmail** — IMAP/SMTP client at `webmail.bilohash.com` with flexible Hostinger SMTP/IMAP overrides via `config.local.php`
- Rewritten **README.md**, **README-ua.md**, **README-no.md** with full feature docs and screenshot galleries
- News article: https://bilohash.com/news/hosting.html

### Improved
- **Malware scanner** (`panel/security.php?tab=malware`) — severity badges (critical/high/medium), expanded patterns, findings summary, scan scope legend
- Portfolio homepage & news hub — Hosting CMS in ecosystem planets (Nucleus), live projects and featured cards

### Links
- Live demo: https://bilohash.com/hosting/
- License contact: info@bilohash.com

## [2.6.4] — 2026-07-10

### Fixed
- **White page on migrate-to-mysql.php** — fatal `hs_db_load_collection_raw()` → use `hs_db_load_collection()`
- `hs_db_meta_get_scalar()` safe when meta table missing; `hs_db_ensure_schema()` creates meta table
- `config.local.php` SSH constants no longer re-define (PHP warnings before HTML)
- Migration POST shows error message instead of blank page on failure

## [2.6.3] — 2026-07-10

### Improved
- Panel **Clients** page (`panel/clients.php`): stats bar, status filters, sort, clickable rows, client ID, disk/paid-until columns, edit summary card, analytics shortcut, translated statuses

## [2.6.2] — 2026-07-10

### Fixed
- Install/migration wizard HTTP 500 — `ENT_UTF-8` typo in `htmlspecialchars()` (use `ENT_SUBSTITUTE`)
- Hardcoded English JSON-detected hint on install page — now uk/en/no

## [2.6.1] — 2026-07-10

### Added
- Full multilingual install wizard (uk/en/no): overview, 30-day demo license, security checklist, MySQL + JSON migration
- Shared `assets/css/install.css` design for install.php and migrate-to-mysql.php
- `lang/install-*.php` translation files

## [2.6.0] — 2026-07-10

### Added
- **Full MySQL migration (schema 2.0)** — invoices, domain/hosting orders, activity logs, plans catalog, counters, exchange rates, GeoIP cache
- `migrate-to-mysql.php` — one-time JSON → MySQL import with backup to `data/json-backup/` (requires platform admin credentials)
- Auto schema upgrade on boot (`hs_db_ensure_schema`)

### Security
- Platform secrets remain in PHP files only: `db.config.php`, `admin.config.php`, `mysql-provision.config.php`, `client-db/`, `ssh.config.local.php`
- Client site files stay on disk (`public_html/`)

## [2.5.6] — 2026-07-10

### Added
- Analytics: login IP and country columns in activity log; last-login summary shows IP and country (Cloudflare header + GeoIP cache)

## [2.5.5] — 2026-07-10

### Fixed
- Launch checklist “Review” link after publishing landing — no longer doubles absolute `published_url` through `hs_url()`

## [2.5.1] — 2026-07-10

### Security
- Production SSH/server settings moved to `config.local.php` (gitignored)
- Deploy paths/credentials in `scripts/deploy.config.local.ps1` (gitignored)
- Removed `public_html/demo/` and internal `ssh-*.ps1` ops scripts from repository
- Sanitized example configs and panel DNS hints (no real server IP in repo)

## [2.5.0] — 2026-07-10

### Added
- GitHub repository with README (en / uk / no) and open-source landing block
- Homepage **business value** section for agencies & SMB
- **BILOHASH Universe** — 15 ecosystem “planets” marketing grid
- Demo credentials standardized: `demo`/`demo`, `admin`/`admin`
- `data/admin.config.example.php`

### Changed
- Platform admin login default: `admin` / `admin` (legacy `administrator` migrates on seed)
- Seed migrates legacy `administrator` account to `admin`

## [2.4.9] — 2026-07-10

- PHP settings file renamed from `.user.ini` to `php.ini` per account

## [2.4.8] — 2026-07-10

- Analytics: per-user activity log files, pagination (20/page), login/session duration

## [2.4.7] — 2026-07-10

- Site details moved from Domains overview to dashboard

## [2.4.6] — 2026-07-10

- DNS zone moved to separate Domains tab

## [2.4.5] — 2026-07-10

- Per-account support mailbox and client ID (`BH-CL-#####`)

## [2.4.4] — 2026-07-10

- Domain delete rules; demo domains seed

## [2.4.3] — 2026-07-10

- Plan renew UI, plan change modal, performance diagnostics

## [2.4.2] — 2026-07-10

- Auto invoices after plan purchase/change

## Planned

- **Full MySQL migration** — replace JSON storage for users, sites, settings, logs, orders
- Payment gateway integration
- Live domain registrar API