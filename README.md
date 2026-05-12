# VeA_EconModels

Web application for screening US-listed equities and managing virtual portfolios. Built as a bachelor's thesis project at Ventspils University of Applied Sciences.

## What's inside

- **Instrument screening** — filter ~5,000 US tickers by sector, industry, price, volume, fundamental ranges (revenue, net income, total assets, equity, operating cash flow), and technical performance (1m / 3m / 6m / 1y returns). Per-instrument detail pages show price history charts and merged fundamental data from EDGAR (2009–2017) and SimFin (2018–2023).
- **Custom indexes** — define a basket of instruments by either filter rules or manual selection, view weighted time-series (market-cap, equal-weight, or price-weighted).
- **Virtual portfolios** — create multiple portfolios per user, buy/sell with fractional shares (3-decimal precision matching Fidelity/Schwab Slices), see day/week/total returns per holding, full transaction ledger, and value-over-time chart.
- **User authentication** — email/password registration, login rate-limiting, password reset flow.

All UI text is in Latvian.

## Stack

- **PHP 8.3-FPM** (Laravel 11)
- **PostgreSQL 16 + TimescaleDB 2.25** (hypertable on the daily-prices table)
- **Redis 7** (cache, sessions, queues)
- **Nginx** (reverse proxy)
- **Tailwind CSS** via CDN (no Vite build step)
- **Docker Compose** for the whole stack

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose plugin on Linux)
- [Git](https://git-scm.com/)

You don't need PHP, Composer, or PostgreSQL installed locally — everything runs in containers.

## Development setup

### 1. Clone

```bash
git clone https://github.com/TomassMil/VeA_EconModels.git
cd VeA_EconModels
```

### 2. Configure environment

```bash
cp src/.env.example src/.env
```

Then generate an application key (after the containers are up — see step 4). For now you can leave `APP_KEY=` empty.

### 3. Build and start the containers

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

This builds the PHP image (PHP 8.3 + extensions: pdo_pgsql, intl, bcmath, redis), starts PostgreSQL with TimescaleDB, Redis, and Nginx. The PHP container's entrypoint waits for the database to be ready and runs `php artisan migrate --force` automatically.

### 4. Generate the app key

```bash
docker compose exec app php artisan key:generate
```

### 5. Install Composer dependencies (first run only)

```bash
docker compose exec app composer install
```

### 6. Open in browser

[http://localhost:8080](http://localhost:8080)

Register a user, log in, and navigate to **Instrumenti**, **Indeksi**, or **Portfelis**.

### Note on data

A fresh dev install starts with an empty database — only Laravel's framework tables (`users`, `sessions`, `cache`, `migrations`, etc.) and the empty domain tables. To populate `instruments`, `prices_daily`, `simfin_*`, and `financial_data`, you need the import scripts in `~/data/VeA_data/`, `~/data/VeA_Simfin/`, and `~/data/VeA_fundamental_data/` (not in this repo). These scripts read CSV / XBRL exports and bulk-load them into PostgreSQL.

## Useful commands

### Container management

```bash
# Start (after first build)
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Stop
docker compose -f docker-compose.yml -f docker-compose.dev.yml down

# Rebuild after Dockerfile / code changes
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build

# View logs
docker logs vea_econmodels_app -f
docker logs vea_econmodels_nginx -f
docker logs vea_econmodels_db -f
```

### Laravel commands

```bash
docker compose exec app php artisan migrate:status
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan tinker
docker compose exec app php artisan test
```

### Database access

```bash
# Inside the container
docker compose exec db psql -U laravel -d econmodels

# From host (port 5433 is mapped externally in dev)
psql -h localhost -p 5433 -U laravel -d econmodels
```

## Port mappings (dev)

| Service | Internal | Host |
|---|---|---|
| Nginx (HTTP) | 80 | 8080 |
| PostgreSQL | 5432 | 5433 |
| Redis | 6379 | 6380 |

## Project layout

```
VeA_EconModels/
├── src/                            # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/       # InstrumentController, PortfolioController, IndexController, ProfileController, Auth/*
│   │   ├── Models/                 # Instrument, Portfolio, PortfolioTransaction, Index, User
│   │   ├── Policies/               # PortfolioPolicy, IndexPolicy (authorization)
│   │   ├── Services/               # ChartService (portfolio + index time series)
│   │   └── Support/                # Money helper (BCMath wrappers)
│   ├── database/migrations/        # Schema migrations
│   ├── resources/views/            # Blade templates
│   ├── routes/                     # web.php, auth.php
│   └── tests/                      # Feature/InstrumentPagesTest.php
├── docker/
│   ├── php/Dockerfile              # PHP 8.3-FPM image build
│   ├── php/entrypoint.sh           # Container startup (perms, migrate, cache)
│   ├── php/conf.d/                 # opcache-dev.ini, opcache-prod.ini
│   ├── nginx/default.conf          # Production Nginx (HTTPS, security headers)
│   └── nginx/default.dev.conf      # Development Nginx (HTTP, port 8080)
├── docker-compose.yml              # Base services (app, nginx, db, redis)
├── docker-compose.dev.yml          # Dev overrides (port mappings, volumes)
├── docker-compose.prod.yml         # Production overrides (HTTPS, env-driven secrets)
└── README.md
```

## Production deployment (overview)

Production runs at [https://econmodels.venta.lv](https://econmodels.venta.lv) on a server with Let's Encrypt certificates already configured. The full deployment procedure is documented separately. The high-level steps:

1. Set up a strong `DB_PASSWORD` and `REDIS_PASSWORD` in the production `.env` (use `openssl rand -base64 32` and store in a password manager).
2. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://econmodels.venta.lv` in the production `.env`.
3. `chmod 600 src/.env` so only the container user can read it.
4. Start with the prod overlay: `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build`.
5. Migrations run automatically via the entrypoint script.
6. Bulk-load fundamental data and prices using `rsync` to copy the import scripts and CSV exports from a development machine, then run them inside the database container.

## Testing

```bash
docker compose exec app php artisan test
```

Tests use SQLite via the `RefreshDatabase` trait and do not touch the development PostgreSQL database.

## License & author

Developed for academic purposes as a bachelor's thesis at Ventspils University of Applied Sciences.

- **Author:** Toms Millers
- **Institution:** Ventspils Augstskola
- **Year:** 2025–2026

This project is free for personal and non-commercial use. Commercial use requires permission. Read LICENSE for more.
