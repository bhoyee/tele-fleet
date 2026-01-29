# Tele-Fleet

Tele-Fleet is a single-company fleet management system for Nigeria-based operations with multiple branches. It delivers a working demo with role-based workflows, professional UI, and real-time features (notifications + chat).

Documentation:
- Technical Manual: docs/technical-manual.md
- User Manual: docs/user-manual.md

## Highlights
- Role-based dashboards and access control (Super Admin, Fleet Manager, Branch Head, Branch Admin).
- Trip workflow: request -> approval -> assignment -> logbook.
- Branch, vehicle, driver, trip, and incident management.
- In-app + email notifications, plus SMS hooks.
- Realtime chat support via Laravel Reverb.
- Reports with PDF/Excel exports.

## Roles
- **Super Admin**: full access, user management, approvals, assignments, reports, and admin controls.
- **Fleet Manager**: trip approvals, assignments, logbooks, incidents, and reports.
- **Branch Head**: branch visibility, trip requests, and approvals within branch.
- **Branch Admin**: creates trip requests, views own request history, files incidents.

## Tech Stack
- Laravel 12.x, PHP 8.2+ (8.3 recommended)
- MySQL 8.0+
- Blade + Bootstrap 5.3
- Laravel Reverb for realtime chat
- DomPDF + CSV exports

## Quick Start

### 1) Install dependencies
```bash
composer install
```

### 2) Copy .env and set values
```bash
copy .env.example .env
php artisan key:generate
```

### 3) Database setup (XAMPP/MySQL)
Create a database called `tele_fleet`, then update `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tele_fleet
DB_USERNAME=root
DB_PASSWORD=
```

### 4) Run migrations + seeders
```bash
php artisan migrate
php artisan db:seed
```

### 5) Start the app
```bash
php artisan serve
```

Visit: `http://127.0.0.1:8000`

## Realtime Chat (Reverb)

### Reverb env variables
```
BROADCAST_DRIVER=reverb
REVERB_APP_ID=1
REVERB_APP_KEY=telefleet
REVERB_APP_SECRET=telefleet-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8081
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8081
REVERB_SERVER_PATH=
```

### Start Reverb (keep running)
```bash
php artisan reverb:start --debug
```

If realtime chat does not connect, ensure:
- Port 8081 is listening.
- `php.exe` is allowed in Windows Firewall (Private).
- Only one Reverb process is running.

## Notifications
- In-app notifications show in the top bar.
- Email notifications use SMTP settings in `.env`.
- SMS hooks are prepared for Termii/SendChamp.

## Reports
- My Requests report supports date filters.
- Export to PDF and Excel (CSV).

## Tests
```bash
./vendor/bin/pest
```

## Troubleshooting

### Reverb connection state
Open browser console:
```
window.ChatEcho?.connector?.pusher?.connection?.state
```
Expected: `connected`.

### Clear config cache
```bash
php artisan config:clear
```

### Common issues
- **No app key**: run `php artisan key:generate`
- **Mail errors**: confirm sender email is valid for SMTP host
- **Realtime not updating**: check Reverb process + firewall

## License
Proprietary - internal demo build.
