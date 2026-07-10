# BILOHASH Hosting CMS

**hPanel-klasse hostingkontrollpanel** — selg planer, domener, fakturaer og hele **BILOHASH CMS-økosystemet** (15+ apper) fra ett PHP-panel.

- **Demo:** https://bilohash.com/hosting/
- **Versjon:** `includes/version.php` (nå v2.5.0)
- **Stack:** PHP 8.2+, JSON i dag → **full MySQL-migrering planlagt**

## Rask start

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
php -S localhost:8080
```

Åpne http://localhost:8080/ — demobrukere opprettes automatisk.

### Demo-pålogging

| Rolle | Bruker | Passord | URL |
|-------|--------|---------|-----|
| Kunde | `demo` | `demo` | `/login.php` → `/panel/` |
| Plattformadmin | `admin` | `admin` | `/login.php` |
| Super-admin | `admin` | `admin` | `/admin/login.php` |

## Funksjoner

- **Kundepanel** — dashboard, domener, DNS, filer, MySQL, backup, SSL, ytelse, support, fakturaer, landing, WordPress, Git
- **15 «planeter»** — Shop, Booking, Auction, Faktura, News, AI… installasjon med ett klikk
- **Forretning** — auto-fakturaer, kunde-ID `BH-CL-#####`, support-postkasser, aktivitetslogg (20 per side)
- **Språk** — ukrainsk, engelsk, norsk
- **Deploy** — `scripts/deploy-to-hostinger.ps1`

## Veikart

- [x] hPanel-UI, økosystem-installer, fakturaer
- [x] Aktivitetslogg i `data/logs/{userId}.json`
- [x] `php.ini` per konto
- [ ] **Full MySQL-migrering**
- [ ] Betalingsgateway
- [ ] Domeneregistrar-API

## Lenker

- [CHANGELOG.md](CHANGELOG.md)
- [README.md](README.md) · [README-ua.md](README-ua.md)
- [GitHub](https://github.com/Ruslan-Bilohash/hosting)