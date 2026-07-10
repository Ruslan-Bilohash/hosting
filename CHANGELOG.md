# Changelog

All notable changes to BILOHASH Hosting CMS.

## [2.5.0] — 2026-07-10

### Added
- GitHub repository with README (en / uk / no) and open-source landing block
- Homepage **business value** section for agencies & SMB
- **BILOHASH Universe** — 15 ecosystem “planets” marketing grid
- Demo credentials standardized: `demo`/`demo`, `admin`/`admin`
- `data/admin.config.example.php`

### Changed
- Platform admin login default: `admin` / `admin` (was `administrator` / `bilohost2026`)
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