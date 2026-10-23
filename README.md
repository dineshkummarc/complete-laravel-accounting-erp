# Laravel Accounting

**Status: foundation and core company/module workflows are implemented. The complete accounting/business application is still in development.**

Laravel 12, Filament 3 at `/app`, an independent platform Console at `/admin`, shared session authentication and Spatie permissions, and all seven specified `imrandevbd` packages are installed with locked versions. Company onboarding, branch switching, memberships/roles, module toggles/presets, private attachments, CSV imports/exports and an audit viewer are available. Ledger and optional business-module workflows are not yet implemented.

See [installation](docs/INSTALL.md), [delivery status](docs/PROGRESS.md), [package findings](docs/PACKAGE-NOTES.md), and the preserved [requirements](docs/REQUIREMENTS.md).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan unicode-pdf:font:install --font=bengali
php artisan unicode-pdf:font:install --font=arabic
php artisan unicode-pdf:font:install --font=universal
php artisan app:install-owner owner@example.com
php artisan serve
```

The installer requests a strong password without echoing it. No default login or production sample user is seeded. Open `/app/login` to sign in, then create a company. Create staff accounts with `php artisan app:create-user email@example.com --name="Staff name"`, and assign company roles/branches through Team. `/admin`, `/drutogoti`, `/ai-hub`, and `/ai-catalog` require `platform.console`. Device ingestion and the package catalog API remain disabled until tenant-aware authorization exists.

```bash
vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=512M
composer audit --locked
php artisan app:doctor
```

The host sandbox blocks PHPStan's parallel TCP listener; `--debug` runs analysis serially there. CI uses PHP 8.3, MySQL 8, and Redis. CI execution has not been observed.
