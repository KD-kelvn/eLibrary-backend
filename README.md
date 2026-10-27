# eLibraryV2 — Backend API

Laravel 12 REST API for eLibraryV2. Librarians catalogue physical and digital books and run circulation, Members (role code `student`) borrow physical books and read digital books online, and Admins manage users, roles, menus and settings. The React SPA lives in the separate `frontend` repo; product and design docs live in the separate `design` repo.

## Stack

- PHP 8.4 (composer requires `^8.2`; CI uses 8.4), Laravel 12, `internachi/modular` domain modules
- Laravel Sanctum token auth, OTP via `spatie/laravel-one-time-passwords`
- PostgreSQL with one schema per domain (`auth`, `book_catalog`, `book_borrowing`, `settings`)
- Spatie: activity log, health, schedule monitor, media library, pdf-to-text, simple-excel
- Database queue and cache by default

## Prerequisites

- PHP 8.4 with `pdo_pgsql`, `mbstring`, `zip`, `gd`
- Composer 2
- PostgreSQL 15+ (the migrations use `CREATE SCHEMA IF NOT EXISTS`, so they need PostgreSQL; SQLite is only used by the current example tests)
- Node 22 (only for Laravel's own asset scripts; the SPA is in the `frontend` repo)
- A mail catcher for local OTP/email (e.g. Mailpit on `127.0.0.1:1025`)

## Setup

```bash
git clone <backend-remote> backend && cd backend
git checkout develop
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`. `.env.example` defaults to SQLite; switch to PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=elibrary
DB_USERNAME=elibrary
DB_PASSWORD=secret
```

Other keys in `.env.example` that matter:

| Key | Purpose |
| :-- | :-- |
| `APP_URL` | API base URL (default `http://localhost:8000`) |
| `FRONTEND_URL` | SPA URL used in links/emails (default `http://localhost:5173`) |
| `CORS_ALLOWED_ORIGINS` | Comma-separated origins allowed to call the API (FR 2.4) |
| `QUEUE_CONNECTION` / `CACHE_STORE` / `SESSION_DRIVER` | `database` by default |
| `MAIL_*`, `MAIL_FROM_*`, `MAIL_MARKDOWN_THEME=elibrary` | Outgoing email (OTP, password reset) |
| `AUTH_OTP_EXPIRY_MINUTES` | OTP lifetime (default 10) |
| `AUTH_OTP_EXPOSE_IN_RESPONSE` | `true` returns the OTP in the API response for local testing. **Must be `false` in production.** |
| `FILESYSTEM_DISK` | Default disk. Ebooks always use the private `ebooks` disk (`storage/app/private`) |
| `AWS_*` | Only if you move storage to S3 |

SMS uses `SmsProvider` (`log` | `beem` | `institutional`). Default is `log`. Configure via `SMS_DRIVER` / `BEEM_*` env or `PUT /api/settings/providers` (secrets write-only). Beem request shape matches family-hub.

Then create the database and run:

```bash
createdb elibrary                      # or via psql/pgAdmin
php artisan migrate                    # creates the PG schemas and tables
php artisan db:seed                    # roles (admin, librarian, student), permissions/menus, settings, borrowing processes, penalty policy
php artisan storage:link               # public disk for branding/login-slide images
php artisan book-catalogue:secure-digital-storage   # one-off: move legacy ebooks to the private disk
```

No users are seeded. Register through `POST /api/auth/register` (or the SPA), then grant roles with `POST /api/auth/role-assignments` as an admin. To bootstrap the first admin, assign the `admin` role in the database or with `php artisan tinker`.

## Running

```bash
composer run dev     # php artisan serve + queue:listen + pail logs + vite, via concurrently
```

or separately:

```bash
php artisan serve              # http://localhost:8000
php artisan queue:work         # notifications/mail are queued
php artisan schedule:work      # local scheduler (production: cron `* * * * * php artisan schedule:run`)
```

Scheduled jobs (`routes/console.php`): health checks and heartbeats every minute, `activitylog:clean` and schedule-monitor prune daily, `borrowing:create-penalty-batches` at 00:00 and `borrowing:generate-daily-penalties` at 00:10 (output in `storage/logs/scheduler-YYYY-MM-DD.log`).

## Scripts

| Command | What it does |
| :-- | :-- |
| `composer run setup` | install, copy `.env`, key, migrate, `npm install && npm run build` |
| `composer run dev` | dev server + queue + logs + vite |
| `composer test` | clear config cache, `php artisan test` |
| `vendor/bin/pint` / `vendor/bin/pint --test` | format / check style (CI runs `--test`) |
| `php artisan route:list --except-vendor` | list API routes |

## Testing

```bash
php artisan test
```

CI (`.github/workflows/ci.yml`) runs Pint and `php artisan test` on PHP 8.4 with SQLite in memory, on pushes and PRs to `main`, `develop` and legacy `dev`. Today only Laravel's example tests exist; domain feature tests are backlog T-07. Because the migrations need PostgreSQL schemas, new feature tests should run against PostgreSQL (CI will need a Postgres service).

## Project structure

```
app/                     cross-cutting glue only (Support\SchemaTable, Support\ApiErrorCode, ResponseTrait, AdminActivity)
config/                  framework + package config (cors.php, filesystems.php ebooks disk)
domains/
  authentication/        register, login, OTP, password reset, sessions, users, profiles      -> /api/auth/*
  authorization/         roles, assignments, revocations, modules/pages/actions, menus          -> /api/auth/*
  book-catalogue/        books, categories, tags, shelves, copies, digital files, favourites   -> /api/book-catalogue/*
  book-borrowing/        requests, approve/reject/return, processes, penalties, policies       -> /api/book-borrowing/*
  book-reading/          reading histories, member dashboard                                    -> /api/book-reading/*
  settings/              branding, login slides                                                 -> /api/settings/*
  <domain>/api-routes, database/{migrations,seeders}, src/{Http,Services,Repositories,Policies,Models,Enums,...}
routes/console.php       scheduler
tests/                   PHPUnit
```

Each layer has one job: Route → Controller → FormRequest → Service → Repository → Resource. See `.cursor/rules/backend-architecture.mdc` and `design/ENGINEERING-STANDARDS.md`.

## Versioning

- SemVer in root `VERSION`; exposed as `config('app.version')` (override with `APP_VERSION`).
- Git tags on `main`: `vX.Y.Z` (see `design/BRANCHING.md` and `design/VERSIONING.md`).
- Current baseline: see root `VERSION`.

## Security

Do not commit `APP_KEY` or other secrets. See [`SECURITY.md`](./SECURITY.md).

## Design docs

In the `design` repo (sibling folder `../design/`):

- [README](../design/README.md): doc map and reading order
- [SRS](../design/SRS.md), [LIBRARY-POLICY](../design/LIBRARY-POLICY.md), [GLOSSARY](../design/GLOSSARY.md)
- [SDD](../design/SDD.md): architecture, ERD, state machines
- [TRACEABILITY](../design/TRACEABILITY.md), [IMPLEMENTATION-BACKLOG](../design/IMPLEMENTATION-BACKLOG.md)
- [CONTRIBUTING](../design/CONTRIBUTING.md), [BRANCHING](../design/BRANCHING.md), [ENGINEERING-STANDARDS](../design/ENGINEERING-STANDARDS.md)
