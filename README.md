# GTrack

A web-based GCash transaction management system for a sari-sari store that also operates as a GCash agent. It replaces the manual paper logbook — tracking cash-in / cash-out transactions, service charges, GCash and on-hand cash balances, and end-of-day reconciliation.

Built with **Laravel 12**, **Blade**, **Tailwind CSS**, **Alpine.js**, and **MySQL**.

---

## Setup guide (fresh machine)

Follow these steps to run GTrack on a new laptop.

### 1. Install the prerequisites (Windows, via the console)

These commands use **winget**, the package manager built into Windows 10/11. Open **PowerShell** and run them.

**First, check what you already have** (if a version prints, it's installed — skip that one):

```powershell
git --version
node --version
npm --version
php --version
composer --version
```

**Install whatever is missing:**

```powershell
# Git
winget install Git.Git

# Node.js + npm (LTS)
winget install OpenJS.NodeJS.LTS

# PHP + MySQL + phpMyAdmin (XAMPP, PHP 8.2+)
winget install ApacheFriends.Xampp.8.2
#   If that ID isn't found, run:  winget search xampp
#   ...or download from https://www.apachefriends.org (choose PHP 8.2+)

# Composer (PHP package manager — install AFTER PHP/XAMPP)
winget install Composer.Composer
#   If it errors, download https://getcomposer.org/Composer-Setup.exe
```

**Add PHP to your PATH** so the `php` command works in any terminal:

```powershell
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\xampp\php", "User")
```
*(Change `C:\xampp\php` if XAMPP is installed elsewhere.)*

**Close and reopen PowerShell / VS Code** (so the new PATH loads), then verify everything:

```powershell
git --version
node --version
php --version
composer --version
```

> Also install the **Laravel Blade** extension in VS Code for syntax highlighting.

### 2. Clone the project

```bash
git clone https://github.com/trstnrbrd/G-Track.git gtrack
cd gtrack
```

### 3. Install dependencies

```bash
composer install      # PHP packages (creates vendor/)
npm install           # JS packages (creates node_modules/)
```

### 4. Create the environment file

```bash
copy .env.example .env      # Windows
# cp .env.example .env       # macOS / Linux

php artisan key:generate
```

The `.env` is already set up for MySQL with database name `gtrack`, user `root`, no password (the XAMPP default). If your MySQL uses a different user/password, edit `DB_USERNAME` / `DB_PASSWORD` in `.env`.

### 5. Set up the database

1. Open the **XAMPP Control Panel** and **Start** MySQL (and Apache if you want phpMyAdmin).
2. In **phpMyAdmin** (http://localhost/phpmyadmin), create a new database named **`gtrack`**.
3. Run the migrations and seeder:

```bash
php artisan migrate --seed
```

This creates all the tables and seeds the login account + starting balance row.

### 6. Run the app

Open **two terminals** in the project folder:

```bash
# Terminal 1 — Laravel server
php artisan serve

# Terminal 2 — Vite (compiles CSS/JS, with hot reload)
npm run dev
```

Then open: **http://127.0.0.1:8000/login**

### 7. Log in

| Field | Value |
|-------|-------|
| Email | `admin@gtrack.com` |
| Password | `gtrack2026` |

> The login account and balance row come from the database seeder (`database/seeders/DatabaseSeeder.php`).

---

## Daily run (after the first setup)

You don't repeat the install every time — just:

1. Start **MySQL** in XAMPP.
2. `php artisan serve` (terminal 1)
3. `npm run dev` (terminal 2)
4. Open http://127.0.0.1:8000

---

## Viewing on your phone (same Wi-Fi)

```bash
npm run build                                  # compile assets once
php artisan serve --host=0.0.0.0 --port=8000   # expose to the network
```

Find your PC's local IP (`ipconfig` on Windows → IPv4 Address), then on your phone open `http://<PC-IP>:8000`. Make sure both devices are on the same Wi-Fi and allow the connection through Windows Firewall.

---

## Working together with Git

So changes don't collide:

```bash
git pull                       # always pull the latest before you start
# ... make your changes ...
git add -A
git commit -m "describe what you changed"
git push
```

Tip: work on separate branches (e.g. `frontend` and `backend`) and merge via Pull Requests to avoid conflicts.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `SQLSTATE[HY000] [2002]... refused` | MySQL isn't running — start it in XAMPP. |
| `Database 'gtrack' doesn't exist` | Create the `gtrack` database in phpMyAdmin. |
| Page has no styling | `npm run dev` isn't running, or run `npm run build`. |
| `No application encryption key` | Run `php artisan key:generate`. |
| Composer/PHP version error | Make sure PHP is 8.2+ (check XAMPP's PHP version). |
| `composer install` fails: *zip extension and unzip/7z commands are both missing* | Enable the `zip` extension: open `C:\xampp\php\php.ini`, change `;extension=zip` to `extension=zip`, save, then re-run `composer install`. Verify with `php -m \| findstr zip`. |
| Styles/JS changes not showing | Hard refresh (Ctrl + Shift + R); if on a build, run `npm run build`. |

---

## How the money works

This is the part you cannot infer from the code alone, so read it before
changing anything in `app/Models/Transaction.php`.

**Cash In** — the customer loads money into *their* GCash wallet. The shop sends
GCash and receives physical cash. **Cash Out** — the customer withdraws. The shop
receives GCash and hands over physical cash.

The service charge can be settled two ways, and that is the only thing that
varies. `charge_paid_in` records which:

| Scenario | `type` | `charge_paid_in` | Cash | GCash |
|---|---|---|---|---|
| "Pa cash in 500" — customer hands over 515 | `cash_in` | `cash` | **+515** | **−500** |
| "Ibawas na lang sa cash in" — hands 500, receives 485 | `cash_in` | `gcash` | **+500** | **−485** |
| "Pa cash out 500" — sends 500, pays the fee in cash | `cash_out` | `cash` | **−485** | **+500** |
| Customer sends 515 in GCash, takes 500 cash | `cash_out` | `gcash` | **−500** | **+515** |

The rule underneath all four: **the amount leaves one balance and enters the
other, then the fee is added to whichever balance it was paid in.** The shop
nets exactly the service charge every time. That is `Transaction::deltasFor()`,
and `tests/Unit/TransactionRulesTest.php` locks all four cases down.

### Service charge rates

Defined **once**, in `config/gtrack.php`. The server computes every charge from
that table and ignores whatever the browser submits. The modal's rate guide and
live preview render from the same table — never hardcode a rate anywhere else.

| Amount | Fee |
|---|---|
| ₱1 – ₱500 | ₱10 |
| ₱501 – ₱1,000 | ₱15 |
| ₱1,001 – ₱2,500 | ₱20 |
| ₱2,501 – ₱5,000 | ₱25 |
| ₱5,001 – ₱10,000 | ₱30 |
| ₱10,001 – ₱20,000 | ₱50 |
| Above ₱20,000 | ₱100 |

### The daily session

Transactions can only be recorded while a session is open. **Start Day** records
the opening balances; **End Day** records the closing balances, then zeroes the
cash (it leaves the drawer overnight) while the GCash float carries over to the
next day. Configured in `config/gtrack.php` under `end_day_resets`.

Only one session can be open at a time, enforced by a unique index on
`daily_sessions.active_guard` — not just an application check, so a double-click
or two staff starting at once cannot split a day across two sessions. Always
close a session through `DailySession::close()`, which releases that guard.

### Rules that must not be relaxed

- The service charge is **always** recomputed server-side.
- A transaction that would push either balance below zero is refused.
- Balance changes happen inside `DB::transaction()` with the row locked.
- Cash out requires a reference number (the customer's e-receipt is the only
  proof the money arrived); cash in requires a mobile number (that is where the
  money is sent).

---

## Automation

### Auto-closing a forgotten day

If nobody presses End Day, the session stays open overnight and the next day's
transactions attach to yesterday — silently blending two days in every report.
`gtrack:close-stale-sessions` closes any session open longer than
`auto_close_after_hours` in `config/gtrack.php` (default 16h).

Auto-closed sessions are flagged with `auto_closed` and shown as **Auto-closed**
(amber) on the Balance page and in the PDF export. That distinction matters: an
auto-closed day's closing balances are what the *app believed*, not what anyone
counted in the drawer. Never read them as a verified count.

Check what it would do without changing anything:

```bash
php artisan gtrack:close-stale-sessions --dry-run
```

**This only runs if something invokes Laravel's scheduler every minute.** On
Windows, create a Task Scheduler entry:

1. Open **Task Scheduler** → **Create Task** (not "Basic Task")
2. **General:** name it `GTrack Scheduler`; tick *Run whether user is logged on or not*
3. **Triggers** → New → *Daily*, repeat every **5 minutes** for a duration of *Indefinitely*
4. **Actions** → New → *Start a program*:
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: the project folder (where `artisan` lives)
5. **Conditions:** untick *Start the task only if the computer is on AC power*

Without this step nothing is scheduled — the command exists but never fires.

### PDF exports

**Export PDF** on History and Balance downloads a landscape A4 report of exactly
what the current filters show, not the whole table — with totals, a repeating
table header, and "Page X of Y" on every page. Built with `barryvdh/laravel-dompdf`
from the Blade views in `resources/views/exports/`.

dompdf is memory-hungry, so a report lists at most `export_max_rows` (500, in
`config/gtrack.php`, where the measurements behind that number are recorded).
Past the cap the PDF says so, and its totals still cover every row. For a longer
period, narrow the date range.

### Counter shortcuts

- **Quick amounts** — one-tap buttons above the Amount field, configured in
  `config/gtrack.php` under `quick_amounts`
- **Customer autocomplete** — the mobile field suggests the last 50 numbers
  already served, so suki customers don't get retyped

### Duplicate protection

A GCash reference identifies exactly one real transfer, so `reference_number`
carries a unique index. Recording the same reference twice is refused by the form
*and* by the database — the index is what wins a race between two simultaneous
submits. Cash-ins without a reference are unaffected, since both MySQL and SQLite
allow unlimited NULLs in a unique index.

---

## Running the tests

```bash
php artisan test
```

Tests run against in-memory SQLite, so **MySQL does not need to be running**.
Anything touching money needs a test — see `tests/Unit/TransactionRulesTest.php`
and `tests/Feature/RecordTransactionTest.php`.

---

## Tech stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade, Tailwind CSS, Alpine.js, SweetAlert2
- **Build:** Vite
- **Database:** MySQL
- **Auth:** Laravel Breeze (single super_admin; no public registration)
