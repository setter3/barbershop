# ARSHAM BARBERSHOP

Persian RTL multi-barber booking application built with Laravel 13, Blade, Alpine.js and Vite.

## Current MVP

- Public four-step booking flow without customer accounts
- Dynamic availability from weekly schedules, time off and active reservations
- Transactional reservation creation with unique slot claims to prevent double booking
- Manual-confirmation mode for launch before a payment gateway is connected
- Separate reservation and payment states
- Authenticated admin control room for reservations, barbers, weekly schedules, time off, services and business settings
- MySQL/MariaDB production target with no vendor-specific hosting dependency

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Set `ADMIN_SEED_NAME`, `ADMIN_SEED_EMAIL`, and `ADMIN_SEED_PASSWORD` before the first `php artisan db:seed` to create the initial administrator. Demo barbers and services are intentionally separate and can be loaded with `php artisan db:seed --class=DemoBookingSeeder`.

## cPanel-compatible deployment

Requirements: PHP 8.3+, MySQL 8+/MariaDB 10.6+, Composer 2, and PHP extensions commonly required by Laravel (`ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`).

1. Point the domain document root to this project's `public` directory. Do not expose the project root.
2. Create a MySQL database and production `.env` with `APP_ENV=production`, `APP_DEBUG=false`, the final `APP_URL`, database credentials, and a strong generated `APP_KEY`.
3. Run `composer install --no-dev --optimize-autoloader` and build assets before upload or run `npm ci && npm run build` on the server.
4. Run `php artisan migrate --force`, `php artisan db:seed --class=SettingSeeder --force`, `php artisan storage:link`, and `php artisan optimize`.
5. Make `storage` and `bootstrap/cache` writable by the hosting account.
6. Add one cron entry that runs `php /absolute/project/path/artisan schedule:run` every minute. This is required when online-deposit holds are enabled.

Keep `BARBERSHOP_BOOKING_MODE=manual_confirmation` for the initial no-gateway release. Change it to `online_deposit` only after a payment provider has been implemented and verified.

## Verification

```bash
php artisan test
php vendor/bin/pint --test
npm run build
composer audit
npm audit
```
