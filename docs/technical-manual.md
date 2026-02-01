# Tele-Fleet Technical Manual

## Document Control
- Document: Tele-Fleet Technical Manual
- Audience: Technical and operations teams
- Version: v1.0.0
- Prepared for: Tele-Fleet project
- Prepared by: Engineering

## Table of Contents
1. Overview
2. System Architecture
3. Environment Requirements
4. Installation and Setup
5. Configuration
6. Database and Migrations
7. Background Services
8. Storage and File Handling
9. Notifications and Mail
10. Real-Time Features
11. Deployment Options
12. Security and Access Control
13. Maintenance Module
14. Reporting Module
15. Backup and System Logs
16. Troubleshooting
17. Change Management

## 1. Overview
Tele-Fleet is a single-company fleet management system supporting multiple branches. It provides trip requests, assignments, maintenance tracking, incidents, reporting, and real-time collaboration. This manual covers deployment, configuration, and operational practices for v1.0.0.

## 2. System Architecture
- Backend: Laravel 12 (PHP 8.2)
- Frontend: Blade templates with Bootstrap 5 and DataTables
- Real-time: Laravel Reverb + Pusher protocol + Laravel Echo
- Database: MySQL
- Queue: Database queue
- Scheduler: Laravel scheduler for reminders/maintenance checks
- File storage: Local disk (storage/app)

Key components:
- Trip workflow (request, approval, assignment, completion)
- Incident reporting with attachments
- Maintenance scheduling + mileage monitoring
- Reporting (My Reports, Branch Report, Fleet Reports, Custom Reports)
- Chat (support and direct, realtime)
- Notifications (in-app + email)

## 3. Environment Requirements
- PHP 8.2+
- Composer 2+
- MySQL 8+
- Node.js 18+
- npm
- Laravel-compatible web server (Apache/Nginx) or PHP built-in server

## 4. Installation and Setup
1. Clone the repository
2. Install dependencies
   - `composer install`
   - `npm install`
3. Configure `.env` (see section 5)
4. Generate application key
   - `php artisan key:generate`
5. Run migrations
   - `php artisan migrate`
6. Build frontend assets
   - `npm run build` (or `npm run dev` for local)
7. Start the app
   - `php artisan serve`

## 5. Configuration
Key `.env` settings:
- `APP_URL` (base URL)
- `DB_*` (database credentials)
- `MAIL_*` (SMTP)
- `BROADCAST_DRIVER=reverb`
- `REVERB_*` (Reverb app + host/port)
- `QUEUE_CONNECTION=database`

Recommended in production:
- `APP_DEBUG=false`
- Use HTTPS and set `REVERB_SCHEME=https`
- Secure database credentials

## 6. Database and Migrations
- Run `php artisan migrate` after every deployment.
- If schema changes are added, follow standard Laravel migration practices.
- Do not edit migrations once deployed to production.

## 7. Background Services
Tele-Fleet relies on background services for reminders and checks.

1) Queue worker
- `php artisan queue:work --tries=3`
- Use Supervisor/Systemd in production.

2) Scheduler
- `php artisan schedule:work` (local)
- `* * * * * php /path/to/artisan schedule:run` (production cron)
- Scheduled backups: `telefleet:backup-database`
  - Runs daily at `BACKUP_SCHEDULE_TIME` (default `02:00`)
  - Retention: `BACKUP_KEEP_COUNT` (default 7)

## 8. Storage and File Handling
- Incident attachments are stored on local disk by default.
- Ensure `storage/` is writable by the web server.
- If using S3, configure `FILESYSTEM_DISK=s3`.

## 9. Notifications and Mail
- Notifications are stored in the database and delivered via email.
- Mail failures should not block app flow.
- Configure SMTP in `.env` and verify sender domain.

## 10. Real-Time Features
- Reverb server handles websockets for chat and live updates.
- Start Reverb:
  - `php artisan reverb:start --debug --port=8081`
- Client uses Laravel Echo + Pusher protocol.
- If Reverb is unavailable, the UI falls back to silent polling.

## 11. Deployment Options

### 11.1 Shared Hosting (lowest cost)
Recommended for MVP/testing. Websockets are typically unavailable.
- Set `REALTIME_ENABLED=false`
- Chat is hidden and Help Desk is enabled
- Dashboard uses polling for charts, calendar, and metrics
- If cron is supported, run:
  - `* * * * * /usr/bin/php /path/to/artisan schedule:run >> /dev/null 2>&1`

### 11.2 VPS (full control)
Recommended for production scale and realtime.
- Run Reverb + queue worker + scheduler
- Full SMTP and backups supported
- Use Supervisor/Systemd to keep workers alive

### 11.3 PaaS (Railway/Render/Heroku-style)
Fast to deploy but platform limits may apply.
- Use managed database add-ons
- Verify websocket support and storage limits

## 12. Security and Access Control
- Role-based access enforced via middleware.
- Roles:
  - Super Admin
  - Fleet Manager
  - Branch Head
  - Branch Admin
- Sensitive operations (delete/restore) restricted to Super Admin.

## 13. Maintenance Module
- Maintenance schedule is tracked in `vehicle_maintenances`.
- Vehicle maintenance status is derived from schedule and mileage checks.
- Due/Overdue thresholds are based on mileage target (default 5000 km).
- When maintenance is in progress, vehicle status is forced to `maintenance`.

## 14. Reporting Module
Available reports:
- My Requests (per user)
- Branch Report (Branch Head)
- Fleet Reports (Super Admin, Fleet Manager)
- Custom Reports (Super Admin, Fleet Manager)

Exports:
- CSV and PDF supported.

## 15. Backup and System Logs
### Database Backups
- Stored under `storage/app/backups/db/`
- Created via the Database Backups page or `telefleet:backup-database`
- Retention is controlled by `BACKUP_KEEP_COUNT` (default 7)
- If `mysqldump` is unavailable, a SQL fallback is generated directly via the DB connection

### System Logs
- Log files stored in `storage/logs/`
- Default app log: `laravel.log`
- Tele-Fleet audit log: `telefleet-YYYY-MM-DD.log` (daily)
- Logs are read-only in the UI and can be downloaded

## 16. Troubleshooting
Common issues:
- Reverb connection errors: confirm `REVERB_*` values and port firewall
- Mail errors: verify sender domain, SPF/DKIM, and SMTP credentials
- 419 Page Expired: ensure session driver + APP_URL are consistent
- DataTables warnings: ensure column counts match

## 17. Change Management
- Use feature branches and pull requests.
- Keep migrations backward compatible.
- Update manuals with every release.
