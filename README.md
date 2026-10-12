<div align="center">
  <img src="public/logo.png" alt="GTrack logo" width="180">

  <h1>GTrack</h1>

  <h3>GCash Transaction and Store Management System</h3>

A web app for a sari-sari store and GCash agent in the Philippines. Track cash-in and cash-out transactions, daily balances, GCash-paid store purchases, and puhunan/utang in one place.

  <br>

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-UI-06B6D4?logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-Interactions-8BC0D0?logo=alpinedotjs&logoColor=black)
![Vite](https://img.shields.io/badge/Vite-Build-646CFF?logo=vite&logoColor=white)

</div>

---

## Features

- Record cash-in and cash-out transactions with server-calculated service charges.
- Track cash on hand and GCash balance, with opening and closing balances for each daily session.
- Record store items paid with GCash and move the matching cash from the GCash drawer to the tindahan.
- Record puhunan (capital added), utang (money borrowed), and repayments, with optional cash/GCash balance movements.
- Browse transaction history and daily balances, and export filtered transaction or session reports as landscape A4 PDFs.
- Use quick transaction amounts and suggestions from previously entered customer mobile numbers.
- Automatically close forgotten daily sessions when Laravel's scheduler is configured.
- Prevent duplicate GCash references and reject balance movements that would take cash or GCash below zero.

## Requirements

- PHP 8.2 or later and Composer
- Node.js and npm
- MySQL (or another Laravel-supported database configured in `.env`)

On Windows, XAMPP is one way to install PHP and MySQL. Make sure PHP's `zip` extension is enabled if Composer reports it cannot extract packages.

## Setup

```bash
git clone https://github.com/trstnrbrd/G-Track.git gtrack
cd gtrack
composer install
npm install
```

Create the local environment file and application key:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

On macOS/Linux, use `cp .env.example .env` instead. Configure the database settings in `.env`. For the default XAMPP configuration, start MySQL and create a database named `gtrack`, then run:

```bash
php artisan migrate --seed
```

The seeder creates the owner login (`admin@gtrack.com`) and the balance row. The password is whatever you set as `GTRACK_ADMIN_PASSWORD` in `.env`; if that is empty, a random one is generated and printed once in the terminal. Change it in Settings after your first login. Re-running the seeder never changes an existing account's password.

Start the Laravel and Vite development servers in separate terminals:

```bash
php artisan serve
npm run dev
```

Open <http://127.0.0.1:8000/login> and sign in. For production or a machine without the Vite server, build the frontend assets with `npm run build`.

## Daily development

Start MySQL, then run `php artisan serve` and `npm run dev`. To build assets for a deployment, run `npm run build`. To run the test suite (which uses in-memory SQLite and does not require MySQL):

```bash
php artisan test
```

## Core money rules

### Cash-in and cash-out

The service charge is configured in one place, `config/gtrack.php`. The server calculates it from the submitted amount; browser values are not trusted. The current brackets are:

| Transaction amount | Service charge |
| ------------------ | -------------: |
| ₱1–₱500            |            ₱10 |
| ₱501–₱1,000        |            ₱15 |
| ₱1,001–₱2,500      |            ₱20 |
| ₱2,501–₱5,000      |            ₱25 |
| ₱5,001–₱10,000     |            ₱30 |
| ₱10,001–₱20,000    |            ₱50 |
| Above ₱20,000      |           ₱100 |

The customer may pay the charge in cash or have it deducted from the GCash amount. `charge_paid_in` records which balance receives the charge. Cash-in requires a customer mobile number; cash-out requires a reference number. A reference can only be recorded once.

All balance changes are performed transactionally with database row locks. A transaction is rejected if either balance would become negative. Preserve these rules when changing `app/Models/Transaction.php` or the transaction controllers.

### Daily sessions

Start Day captures the opening balances. Transactions and store sales require an open session. End Day captures closing balances; by default, cash is reset to zero and the GCash float carries over. These settings are in `config/gtrack.php` under `end_day_resets`.

Only one session can be open at a time. The database enforces this with the unique `daily_sessions.active_guard` index. Close sessions through `DailySession::close()` so the guard is released correctly.

If enabled, `gtrack:close-stale-sessions` closes sessions open past `auto_close_after_hours` (currently 16 hours). Auto-closed sessions are marked as estimates, not verified drawer counts. Preview eligible sessions with:

```bash
php artisan gtrack:close-stale-sessions --dry-run
```

The Laravel scheduler must be invoked for automatic closing to run. On Windows, create a Task Scheduler task that runs `php artisan schedule:run` from the project directory every minute (or at a suitable regular interval).

### Store sales paid with GCash

The Store page records an item purchased with GCash for the tindahan. The amount increases the recorded GCash balance and decreases cash on hand by the same amount, representing cash moved from the GCash drawer to the store till. An open daily session and enough cash on hand are required. The optional reference is protected against duplicates.

### Puhunan and utang

The Puhunan page records three entry types: borrow (kumuha), repay (nagbayad), and capital added (dagdag puhunan). The ledger shows total capital and outstanding borrowed amount. Choose cash or GCash when the entry physically moves that balance; choose no balance movement when it is bookkeeping only. Physical movements require an open session and cannot exceed the available balance. Repayments cannot exceed current utang.

### Exports and counter shortcuts

History exports follow the page's date, type, and search filters. Balance exports summarize sessions for the selected range. PDF reports are landscape A4 and capped at `export_max_rows` (currently 500) to limit memory use; report totals still cover all matching records. Quick amount buttons and the customer suggestion limit are configured in `config/gtrack.php` and the dashboard controller.

## Troubleshooting

| Problem                            | What to check                                                                          |
| ---------------------------------- | -------------------------------------------------------------------------------------- |
| MySQL connection refused           | Start MySQL and verify `DB_HOST`, `DB_PORT`, and credentials in `.env`.                |
| Database does not exist            | Create the configured database, then run `php artisan migrate --seed`.                 |
| Missing application encryption key | Run `php artisan key:generate`.                                                        |
| Page has no styling or scripts     | Run `npm run dev`, or `npm run build` for built assets; then hard-refresh the browser. |
| Composer cannot extract packages   | Enable PHP's `zip` extension or install an unzip utility.                              |
| PHP version error                  | Confirm the CLI PHP version is 8.2 or later (`php --version`).                         |

## Project map

- `app/Models/` — transaction, balance, session, store-sale, and capital-entry rules
- `app/Http/Controllers/` — dashboard, recording, history, balance, and export flows
- `app/Console/Commands/` — stale-session closing command
- `config/gtrack.php` — service-charge brackets, quick amounts, day closing, auto-close, and PDF row limit
- `database/migrations/` — database schema
- `database/seeders/DatabaseSeeder.php` — initial admin account and balance row
- `resources/views/` — Blade pages and PDF templates
- `tests/` — unit and feature coverage

## Tech stack

- Backend: Laravel 12, PHP 8.2+
- UI: Blade, Tailwind CSS, Alpine.js
- Assets: Vite
- Database: MySQL for the default local setup; in-memory SQLite in tests
- PDF reports: `barryvdh/laravel-dompdf`
