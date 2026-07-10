# BILOHASH Hosting CMS

**Панель хостингу рівня hPanel** — продаж тарифів, доменів, інвойсів і повної **екосистеми BILOHASH CMS** (15+ додатків) з однієї PHP-панелі.

- **Демо:** https://bilohash.com/hosting/
- **Версія:** `includes/version.php` (зараз v2.5.0)
- **Стек:** PHP 8.2+, JSON зараз → **повний перехід на MySQL у планах**

## Швидкий старт

```bash
git clone https://github.com/Ruslan-Bilohash/hosting.git
cd hosting
php -S localhost:8080
```

Відкрийте http://localhost:8080/ — демо-користувачі створюються автоматично.

### Демо-доступ

| Роль | Логін | Пароль | URL |
|------|-------|--------|-----|
| Клієнт | `demo` | `demo` | `/login.php` → `/panel/` |
| Адмін платформи | `admin` | `admin` | `/login.php` (Клієнти, імперсонація) |
| Супер-адмін | `admin` | `admin` | `/admin/login.php` |

## Можливості

- **Панель клієнта** — дашборд, домени, DNS, файли, MySQL, бекапи, SSL, продуктивність, підтримка, інвойси, лендінг, WordPress, Git
- **15 «планет» екосистеми** — Shop, Booking, Auction, Faktura, News, AI… встановлення в один клік
- **Бізнес** — автоінвойси, номери клієнтів `BH-CL-#####`, особисті пошти підтримки, журнал дій (20 на сторінку)
- **Мови** — українська, англійська, норвезька
- **Деплой** — `scripts/deploy-to-hostinger.ps1`

## Структура

```
hosting/
├── index.php              # Продаюча головна + пошук домену
├── panel/                 # Панель клієнта
├── admin/                 # Адмін-зона оператора
├── includes/              # Ядро (функції hs_*)
├── templates/             # Шаблони інсталятора CMS
├── data/                  # JSON (не комітити прод-дані)
└── prompt/                # Документація для AI-агентів
```

## Дорожня карта

- [x] UI як hPanel, інсталятор екосистеми, інвойси
- [x] Журнал активності в `data/logs/{userId}.json`
- [x] `php.ini` на акаунт
- [ ] **Повний перехід на MySQL** — users, sites, settings, logs, orders
- [ ] Платіжний шлюз (Stripe/Vipps)
- [ ] API реєстратора доменів

## Посилання

- [CHANGELOG.md](CHANGELOG.md)
- [README.md](README.md) · [README-no.md](README-no.md)
- [GitHub](https://github.com/Ruslan-Bilohash/hosting)