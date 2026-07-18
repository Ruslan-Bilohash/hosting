# Hosting CMS — version & status

**Current:** v2.9.55 (2026-07-18) — public test release  
**URL:** https://solaskinner.com/  
**GitHub:** https://github.com/Ruslan-Bilohash/hosting

## Done (v2.4 → v2.5)

- Full hPanel-style client panel (40+ tools, accordion nav, tabs)
- Domain search → register → checkout → active subscription
- 15 ecosystem apps + installer (templates / bridge / starter)
- Landing builder (50+ widgets), file manager, Git deploy
- Invoicing (PDF), plan renew, plan change modal
- Per-client ID + support mailbox (`@clients.bilohash.com`)
- Activity logs per user (`data/logs/{userId}.json`), analytics 20/page
- DNS zone tab, site details on dashboard
- `php.ini` per account (not `.user.ini`)
- i18n: uk, en, no (public + panel)
- Deploy: `scripts/deploy-to-hostinger.ps1`
- **GitHub open source** — README en/ua/no, CHANGELOG, planets landing

## Demo credentials (GitHub & local)

| Role | Login | Password |
|------|-------|----------|
| Client | `demo` | `demo` |
| Platform admin | `admin` | `admin` |

Super-admin UI: `/admin/login.php` — same `admin` / `admin` unless `data/admin.config.php` overrides.

## Stack today

- PHP 8.2+
- **JSON files** in `data/` (users, sites, user-settings, orders, invoices, logs)
- Optional shared MySQL for client databases (`mysql-provision`)
- Sessions + CSRF + `password_hash()`

## Planned (priority)

1. **Full MySQL migration** — all collections out of JSON into MySQL tables (users, sites, settings, activity_log, domain_orders, hosting_orders, invoices)
2. Real payment provider (Stripe / Vipps)
3. Live domain registrar API
4. Email delivery (SMTP) for invoices & support
5. Multi-server / VPS node selector

## Plans

- **starter** — 1 site, empty/php
- **business** — 5 sites, ecosystem installer
- **pro** — 25 sites, all CMS + advanced tabs