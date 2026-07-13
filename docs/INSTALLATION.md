# LeadCRM — Installation Guide

## Requirements

- PHP 8.3+
- Composer 2.x
- MySQL 8
- Node.js 18+ & npm (for frontend assets)
- Apache/Nginx (WAMP/XAMPP supported)

## Step 1 — Clone & Install Dependencies

```bash
cd D:\wamp64\www\NetSture_Client\indiamart_lead
composer install
npm install
```

## Step 2 — Environment Configuration

Copy `.env.example` to `.env` if not already present:

```bash
copy .env.example .env
php artisan key:generate
```

Key settings in `.env`:

```env
APP_NAME=LeadCRM
APP_URL=http://localhost/indiamart_lead/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=leadcrm
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

INDIAMART_API_KEY=
INDIAMART_GLUSR_ID=
INDIAMART_ACCESS_TOKEN=
INDIAMART_AUTO_SYNC=true
INDIAMART_SYNC_INTERVAL=30
```

## Step 3 — Create Database

Create the MySQL database:

```sql
CREATE DATABASE leadcrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Step 4 — Run Migrations & Seeders

```bash
php artisan migrate
php artisan db:seed
```

## Step 5 — Storage & Permissions

```bash
php artisan storage:link
```

On Linux/macOS, ensure `storage/` and `bootstrap/cache/` are writable.

## Step 6 — Build Frontend Assets

```bash
npm run build
```

For development:

```bash
npm run dev
```

## Step 7 — WAMP Virtual Host (Optional)

Point document root to `public/`:

```
DocumentRoot "D:/wamp64/www/NetSture_Client/indiamart_lead/public"
```

Or access via: `http://localhost/indiamart_lead/public`

## Step 8 — Queue Worker (Required for IndiaMART Sync)

```bash
php artisan queue:work --tries=3
```

## Step 9 — Scheduler (Required for Auto Sync)

Add to cron (Linux) or Task Scheduler (Windows):

```bash
* * * * * cd /path/to/indiamart_lead && php artisan schedule:run >> /dev/null 2>&1
```

## Verify Installation

```bash
php artisan about
```

Expected: Laravel 12.x, PHP 8.3, environment `local`.

## Default Login (after seeders in Step 4)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@gmail.com | password |
| Admin | admin@gmail.com | password |
| Sales Manager | manager@gmail.com | password |
| Sales Executive | executive@gmail.com | password |
| Tele Caller | telecaller@gmail.com | password |
| Marketing | marketing@gmail.com | password |
| Viewer | viewer@gmail.com | password |

Run seeders:

```bash
php artisan db:seed
```
