# Installation and operational setup

## Current delivery boundary

Only the foundation is implemented. Do not use this checkout to maintain business books. Follow PROGRESS.md for unmet acceptance requirements.

## Runtime

Use PHP 8.3 or 8.4 with mbstring, intl, gd, bcmath, sockets, redis, pdo_mysql, xml, curl, and zip. Composer resolves for PHP 8.3. Production uses MySQL 8 (or MariaDB 10.6+) and Redis. The inspected host has PHP 8.5.4 and lacks GD; tests run on SQLite and isolated MariaDB.

Run `composer install`, copy `.env.example` to `.env`, and run `php artisan key:generate`. Configure:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://accounting.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=accounting
DB_USERNAME=accounting
DB_PASSWORD=your-database-password
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
DRUTOGOTI_ENABLED=false
```

Run `php artisan migrate --seed --force`, the three font installation commands in README, `php artisan storage:link`, and `php artisan app:install-owner owner@example.com`. The owner password is entered interactively. Never run migrate:fresh against a live database. The seeder only installs permissions and is repeatable.

The PDF adapter uses the native engine and storage-managed Noto fonts. Remote PDF fonts/images are disabled. The health command checks that all three font files actually exist because the package installer may exit successfully after a failed download.

Run `php artisan app:doctor --production`. A successful result certifies foundation runtime checks only, not release completion. The current Phase 0 RBAC team context is `0`, reserved for platform bootstrap; company authorization comes in Phase 1.

## Local containers

A PHP 8.3 Dockerfile and Compose definition are provided. Set DB_PASSWORD and MYSQL_ROOT_PASSWORD in `.env`, then run:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Run fonts and owner installation inside the app container. The development server binds only to host loopback port 8000. The database and Redis do not publish host ports. The container setup has not been executed on this host.

## Workers and scheduling

Use one Supervisor program per queue when business workloads are implemented. Example:

```ini
[program:accounting-default]
command=/usr/bin/php /var/www/accounting/artisan queue:work redis --queue=default,reports,ai,attendance,low --sleep=3 --tries=3 --timeout=300
directory=/var/www/accounting
autostart=true
autorestart=true
user=www-data
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/log/accounting-worker.log
stopwaitsecs=360
```

```cron
* * * * * cd /var/www/accounting && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

No business scheduled jobs are registered yet. Enable profiler capture only in a controlled environment: the installed package retains raw query bindings and does not implement its advertised sampling or request-path exclusion behavior.

## Web server

Set nginx root to `/var/www/accounting/public`, route normal requests through `index.php`, and use a matching PHP-FPM pool. Once Phase 7 implements device authentication, ADMS requests require:

```nginx
location /iclock/ {
    client_max_body_size 10M;
    try_files $uri $uri/ /index.php?$query_string;
}
```

ADMS routes are currently absent by design. Adding this nginx block does not enable ingestion.

## Backups

Before any live use, establish encrypted database dumps and storage backups, keep APP_KEY separately, and test restoration into an isolated database and storage path. No backup hook or verified restore exercise is delivered yet.
