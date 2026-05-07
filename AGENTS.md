# AGENTS.md

## Cursor Cloud specific instructions

### Overview

This is a **Persian (Farsi) SaaS website builder** ("customer-site") built with **Laravel 12** + **Vite/Tailwind CSS 4**. It is a tenant website deployed for customers, with a CMS, blog, shop, page builder, and 17+ optional modules.

### System Dependencies

- **PHP 8.4** with extensions: `mbstring`, `bcmath`, `pdo_sqlite`, `pdo_mysql`, `gd`, `xml`, `curl`, `zip`
- **Composer** (PHP dependency manager)
- **MariaDB** (MySQL-compatible; used for tests and optionally for dev)
- **Node.js** + **npm** (frontend toolchain)

### Key Commands

See `composer.json` `scripts` section for canonical commands:

| Task | Command |
|------|---------|
| Full setup | `composer setup` |
| Dev server (4 concurrent processes) | `composer dev` |
| Tests | `composer test` |
| CI tests (lint + tests) | `composer test:ci` |
| PHP syntax lint | `php scripts/ci-lint.php` |
| Code style (Pint) | `./vendor/bin/pint --test` |
| Frontend build | `npm run build` |

### Dev Environment Notes

- **Database**: Dev uses MySQL (`DB_CONNECTION=mysql`). The `.env.example` defaults to SQLite, but the installer wizard requires MySQL. For dev, use MySQL with a `customer_site_dev` database.
- **Tests** require MySQL with a `customer_site_test` database. The `phpunit.xml` hardcodes `DB_CONNECTION=mysql`. MariaDB works as a drop-in replacement.
- **MariaDB root access** uses `mysql_native_password` plugin with empty password (no sudo needed): `mysql -u root`.
- **OWNER_SITE=true** in `.env` bypasses the external platform license check, so the app runs standalone without needing a saas-platform API.
- **Installer wizard**: On first run, the app redirects to `/install`. The wizard has a chicken-and-egg issue: it needs session storage (which uses the database) to validate CSRF tokens, but the database step hasn't been configured yet. Workaround: run `php artisan migrate --force` manually, create admin user via `php artisan tinker`, set settings, and create `storage/installed.lock`.
- **Blog/Shop modules**: Controlled by `MODULE_BLOG_ENABLED` and `MODULE_SHOP_ENABLED` in `.env`. Tests enable both.
- **Session/Cache/Queue**: All default to `database` driver in dev.

### Starting MariaDB

MariaDB must be started before running tests or the dev server (when using MySQL connection):

```bash
sudo mysqld_safe &
sleep 2
```

### Running Tests

```bash
# Ensure MariaDB is running, then:
cp .env.testing.example .env.testing
php artisan key:generate --env=testing
php artisan migrate --env=testing --force
composer test
```
