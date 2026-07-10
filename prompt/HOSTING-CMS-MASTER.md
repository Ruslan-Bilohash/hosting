# HOSTING CMS — Master prompt (AI agent)

**Product:** sell **hosting plans** + **domains**. Client gets hPanel-style UI. Every sidebar item and every tab must return HTTP 200 and have at least one working action (form POST that persists).

**Live:** https://bilohash.com/hosting/  
**GitHub:** https://github.com/Ruslan-Bilohash/hosting  
**Local:** `C:\bilohash\hosting\`  
**Deploy:** `powershell -NoProfile -File scripts\deploy-to-hostinger.ps1`  
**Verify:** `powershell -NoProfile -File scripts\verify-panel-urls.ps1`

## Business flow

1. Landing (`index.php`) — domain search, business section, **15 planets** ecosystem, plans, GitHub CTA  
2. `register.php` — account + plan + optional domain → `checkout.php`  
3. `checkout.php` — demo payment activates `subscription_status=active`, binds domain  
4. `panel/` — full hPanel clone (accordion nav, domain picker, impersonation for admin)

## Demo credentials

| Role | Login | Password |
|------|-------|----------|
| Client | `demo` | `demo` |
| Platform admin | `admin` | `admin` |

Super-admin: `/admin/login.php` — `admin` / `admin` (or `data/admin.config.php`).

## Storage (current → planned)

| Today (JSON) | Planned (MySQL) |
|--------------|-----------------|
| `data/users.json` | `hs_users` table |
| `data/sites.json` | `hs_sites` table |
| `data/user-settings.json` | `hs_user_settings` |
| `data/logs/{userId}.json` | `hs_activity_log` |
| orders, invoices JSON | dedicated tables |

**Roadmap priority:** full MySQL migration — see `includes/database.php`, `install/schema.sql`.

## Code rules

- Prefix `hs_` / `HS_`  
- CSRF on every POST  
- `hs_public_path()`, `hs_safe_path()` for files  
- Lang: `lang/en.php` → `uk.php`, `no.php`; panel strings in `lang/panel-*.php`  
- Never commit production `data/*.json` or `data/*.config.php`

## Menu matrix (MUST all work)

### Standalone pages (`panel/*.php`)

| Key | File | Notes |
|-----|------|-------|
| dashboard | `index.php` | stats, site details, cache clear |
| plan | `plan.php` | specs, FTP, NS |
| analytics | `analytics.php` | activity log 20/page |
| php | `php.php` | `php.ini` save |
| account | `account.php` | client ID, support mailbox |
| clients | `clients.php` | admin impersonate |

### Section routers

Sections: `performance`, `security`, `domains`, `websites`, `files`, `databases`, `advanced`, `wordpress`, `api`  
Tabs in `includes/panel-tabs.php` → content `panel-section-content.php`, POST `panel-section-router.php` + `panel-features.php`.

### Ecosystem (15 planets)

`includes/ecosystem-catalog.php` — Shop, Booking, Auction, Freelance, Pizza, Today, GameHub, Tavle, Faktura, Lending, Hosting, News, WordPress, 3D, AI.  
Install via `includes/installer.php`.

## When adding a feature

1. Setting default in `includes/user-settings.php`  
2. POST in `includes/panel-features.php` or section router  
3. UI in `includes/panel-section-content.php`  
4. i18n en + uk (+ no)  
5. `hs_panel_log()` for activity log  
6. Verify script + deploy

## Production server

- SSH: `ssh -p 65002 u762384583@45.84.204.61`  
- Remote: `/home/u762384583/domains/bilohash.com/public_html/hosting/`  
- Ecosystem siblings: `/public_html/shop/`, `/booking/`, etc.

## Definition of done

- [ ] `verify-panel-urls.ps1` → 0 failures  
- [ ] Register + checkout → panel opens  
- [ ] Install Shop → public URL 200  
- [ ] `demo`/`demo` and `admin`/`admin` work after fresh seed  
- [ ] CHANGELOG + prompt/VERSION updated on release