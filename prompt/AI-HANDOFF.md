# AI Handoff — Hosting CMS

**Read first:** [`HOSTING-CMS-MASTER.md`](HOSTING-CMS-MASTER.md)  
**Version:** `includes/version.php` (v2.5.0)  
**GitHub:** https://github.com/Ruslan-Bilohash/hosting  
**Production:** https://bilohash.com/hosting/

## Quick architecture

```
hosting/
├── index.php                 # Sales landing, planets ecosystem, GitHub CTA
├── register.php, checkout.php, domain-check.php
├── panel/                    # Client hPanel
├── admin/                    # Operator UI
├── includes/
│   ├── activity-log.php      # Per-user logs → data/logs/{userId}.json
│   ├── client-identity.php   # BH-CL-#####, support mailboxes
│   ├── invoices.php          # Auto invoices + PDF
│   ├── installer.php         # CMS deploy
│   ├── panel-section-*.php   # Tab UI + router
│   ├── panel-features.php    # POST handlers
│   └── ecosystem-catalog.php # 15 apps + planet blurbs
├── data/                     # JSON (migrate to MySQL — planned)
├── lang/                     # en, uk, no + panel-*
├── prompt/                   # This file + MASTER + VERSION
└── scripts/
    ├── deploy-to-hostinger.ps1
    └── verify-*.ps1
```

## Demo credentials

| Role | Login | Password |
|------|-------|----------|
| Client panel | `demo` | `demo` |
| Platform admin | `admin` | `admin` |

## Rules

- Prefix `hs_` / `HS_`, CSRF on POST
- Never commit production `data/*.json` or secrets
- Deploy after production fixes: `scripts/deploy-to-hostinger.ps1`
- i18n: update `lang/en.php` + `uk.php` + `no.php` (+ `panel-*`)

## What's done (2026-07)

- Invoicing, client ID, support email per account
- Analytics activity log (login, logout duration, visits, changes)
- DNS tab, dashboard site details, php.ini
- GitHub repo with README + business/planets homepage

## What's planned

- **Full MySQL migration** (replace JSON for users, sites, settings, logs, orders)
- Payment gateway, domain registrar API, outbound email

## Selling flow

1. Landing domain search → `register.php?domain=` → `checkout.php`
2. Panel: installer, domains, support, invoices
3. Admin: `admin/admin` → Clients → impersonate