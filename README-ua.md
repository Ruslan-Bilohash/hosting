# BILOHASH Hosting CMS

**PHP-панель керування хостингом рівня hPanel** — продаж тарифів, домени, рахунки, пошта та встановлення **екосистеми BILOHASH CMS** (15+ застосунків) з однієї панелі.

| | |
|---|---|
| **Live demo** | https://bilohash.com/hosting/ |
| **Версія** | **v2.7.1** |
| **Ліцензія** | **30 днів безкоштовно** — **MySQL 2.0 для тесту** — далі [info@bilohash.com](mailto:info@bilohash.com) ([LICENSE.md](LICENSE.md)) |
| **Стек** | PHP 8.2+, **MySQL 2.0** (30 днів demo), JSON fallback, деплой на Hostinger |
| **Мови** | Українська, англійська, норвезька |
| **Веб-пошта** | https://webmail.bilohash.com/ |
| **Новина** | https://bilohash.com/news/hosting.html |

![Панель](docs/screenshots/dashboard.webp)

---

## 30-денна безкоштовна demo-ліцензія (MySQL 2.0)

Під час установки (`/install.php`) фіксується **30 календарних днів** оцінки. У demo **безкоштовно** доступні всі функції, включно з **повною схемою MySQL 2.0** та міграцією JSON → MySQL.

Після 30 днів комерційний resale, white-label і підтримка — за ліцензією BILOHASH: **info@bilohash.com**

---

## Швидкий старт

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
php -S localhost:8080
```

### Демо-доступ

| Роль | Логін | Пароль | URL |
|------|-------|--------|-----|
| Клієнт | `demo` | `demo` | `/panel/` |
| Адмін платформи | `admin` | `admin` | `/login.php` |
| Супер-адмін | `admin` | `admin` | `/admin/login.php` |

---

## Галерея скриншотів (WebP)

Усі зображення — **WebP без втрати якості** (`screenshot/*.jpg` → `docs/screenshots/*.webp`, quality 92).  
На продакшені: `https://bilohash.com/hosting/docs/screenshots/<імʼя>.webp`

### Дашборд і акаунт

![dashboard](docs/screenshots/dashboard.webp) · ![dashboard2](docs/screenshots/dashboard2.webp) · ![dashboard_client](docs/screenshots/dashboard_client.webp) · ![account](docs/screenshots/account.webp) · ![plan_detalis](docs/screenshots/plan_detalis.webp) · ![hostingplan_renew](docs/screenshots/hostingplan_renew.webp) · ![resourse_usage](docs/screenshots/resourse_usage.webp) · ![activity_history](docs/screenshots/activity_history.webp) · ![analytics](docs/screenshots/analytics.webp)

### Домени та DNS

![domains](docs/screenshots/domains.webp) · ![domains2](docs/screenshots/domains2.webp) · ![register_domains](docs/screenshots/register_domains.webp) · ![subdomains](docs/screenshots/subdomains.webp) · ![parked_domains](docs/screenshots/parked_domains.webp) · ![redirect](docs/screenshots/redirect.webp) · ![dns-zone](docs/screenshots/dns-zone.webp) · ![dns_zone_editer](docs/screenshots/dns_zone_editer.webp)

### Сайти, конструктор, міграція

![website](docs/screenshots/website.webp) · ![builder_website](docs/screenshots/builder_website.webp) · ![builder_website_main](docs/screenshots/builder_website_main.webp) · ![copy_website](docs/screenshots/copy_website.webp) · ![migrate_website](docs/screenshots/migrate_website.webp) · ![auto_installer](docs/screenshots/auto_installer.webp)

### WordPress

![install_wordpress](docs/screenshots/install_wordpress.webp) · ![install_wordpress_security](docs/screenshots/install_wordpress_security.webp) · ![wordpress_autoupdater](docs/screenshots/wordpress_autoupdater.webp)

### Бази даних

![databases_management](docs/screenshots/databases_management.webp) · ![phpmyadmin](docs/screenshots/phpmyadmin.webp) · ![remote_mysql](docs/screenshots/remote_mysql.webp)

### Пошта

![manager_email](docs/screenshots/manager_email.webp)

**BILOHASH Webmail** — IMAP/SMTP клієнт. За замовчуванням Hostinger: `imap.hostinger.com:993`, `smtp.hostinger.com:465`. Гнучкі налаштування в `webmail/config.local.php`.

### Файли, FTP, Git

![file_manager](docs/screenshots/file_manager.webp) · ![ftp_accounts](docs/screenshots/ftp_accounts.webp) · ![git_deploy](docs/screenshots/git_deploy.webp) · ![ssh_access](docs/screenshots/ssh_access.webp) · ![change_permissions](docs/screenshots/change_permissions.webp)

### Безпека

![security](docs/screenshots/security.webp)

**Сканер malware** — 18 патернів, рівні **критичний / високий / середній**, таблиця знахідок, легенда. Сканує PHP/JS/HTML/.htaccess у `public_html` (до 8 000 файлів).

### Продуктивність і кеш

![performance](docs/screenshots/performance.webp) · ![website_speed](docs/screenshots/website_speed.webp) · ![object_cache](docs/screenshots/object_cache.webp) · ![lite_speed_cache](docs/screenshots/lite_speed_cache.webp) · ![cdn](docs/screenshots/cdn.webp)

### Бекапи, cron, PHP

![backups](docs/screenshots/backups.webp) · ![cron_jobs](docs/screenshots/cron_jobs.webp) · ![php_configuration](docs/screenshots/php_configuration.webp) · ![ssl](docs/screenshots/ssl.webp) · ![ip_manager](docs/screenshots/ip_manager.webp)

### Підтримка, рахунки, клієнти

![support](docs/screenshots/support.webp) · ![invoices](docs/screenshots/invoices.webp) · ![clients](docs/screenshots/clients.webp)

### AI API

![grok_api](docs/screenshots/grok_api.webp) · ![chat_gpt_api](docs/screenshots/chat_gpt_api.webp)

---

## Можливості

- **40+ інструментів** у клієнтській панелі (домени, DNS, файли, MySQL, SSL, бекапи, інвойси, аналітика)
- **15 планет екосистеми** — Shop, Booking, Auction, Faktura, Landing… одним кліком
- **MySQL 2.0** — повна міграція з JSON через `/migrate-to-mysql.php`
- **Платформений адмін** — клієнти, фільтри, імперсонація, графіки диска
- **i18n** — uk / en / no

---

## Конфігурація

| Файл | Призначення |
|------|-------------|
| `config.local.php` | SSH, IP сервера, webmail (не комітити) |
| `data/db.config.php` | MySQL |
| `webmail/config.local.php` | IMAP/SMTP Hostinger або власний MX |

---

## Деплой

```powershell
powershell -NoProfile -File scripts/deploy-to-hostinger.ps1
```

---

## Посилання

- Demo: https://bilohash.com/hosting/  
- Ліцензія: info@bilohash.com  
- GitHub: https://github.com/Ruslan-Bilohash/hosting  
- Changelog: [CHANGELOG.md](CHANGELOG.md)