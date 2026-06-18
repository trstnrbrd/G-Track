# GTrack

A web-based GCash transaction management system for a sari-sari store that also operates as a GCash agent. It replaces the manual paper logbook — tracking cash-in / cash-out transactions, service charges, GCash and on-hand cash balances, and end-of-day reconciliation.

Built with **Laravel 12**, **Blade**, **Tailwind CSS**, **Alpine.js**, and **MySQL**.

---

## Setup guide (fresh machine)

Follow these steps to run GTrack on a new laptop.

### 1. Install the prerequisites

Install these first (one-time):

| Tool | Notes |
|------|-------|
| **XAMPP** | Gives you MySQL + phpMyAdmin + PHP. Make sure PHP is **8.2 or higher**. |
| **Composer** | PHP package manager — https://getcomposer.org |
| **Node.js + npm** | LTS version — https://nodejs.org |
| **Git** | https://git-scm.com |
| **VS Code** | Recommended editor. Also install the *Laravel Blade* extension. |

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
| Styles/JS changes not showing | Hard refresh (Ctrl + Shift + R); if on a build, run `npm run build`. |

---

## Tech stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade, Tailwind CSS, Alpine.js, SweetAlert2
- **Build:** Vite
- **Database:** MySQL
- **Auth:** Laravel Breeze (single super_admin; no public registration)
