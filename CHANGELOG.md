# Changelog

All notable changes to BILOHASH Hosting CMS.

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