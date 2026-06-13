# DietSync — Setup Guide

Step-by-step instructions to run DietSync locally. It is a plain PHP + MySQL app
with **no build step, package manager, or framework** — you only need PHP and a
MySQL/MariaDB server.

---

## 1. Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP         | 8.0+    | with the `mysqli` extension (bundled with XAMPP/most distros) |
| MySQL or MariaDB | 5.7+ / 10.4+ | MariaDB is what XAMPP ships |
| A web server | any | Apache (XAMPP), or PHP's built-in server |

The easiest all-in-one option on Windows/macOS is **XAMPP**
(https://www.apachefriends.org/), which bundles Apache, PHP and MariaDB.

Check PHP is available:

```bash
php -v
```

---

## 2. Get the code

Clone or copy the project so the folder layout looks like this:

```
DietSync/
  backend/      JSON endpoints + shared infrastructure
  frontend/     user-facing PHP pages, assets, styles
  database/     schema.sql, migration*.sql, dummy_data.sql
  images/
  README.md  CHANGELOG.md  SETUP.md
```

---

## 3. Create the database

Start MySQL/MariaDB first (in XAMPP: open the Control Panel and **Start** Apache
and MySQL).

There are **no migrations** — `schema.sql` is the single, complete database file.
It **drops any existing `dietsync` database and recreates it from scratch**, then
seeds the default accounts and ~240 foods:

```bash
mysql -u root < database/schema.sql
```

Optional demo data (four patients with plans, food/water logs, answered feedback,
and a couple of foods pending review):

```bash
mysql -u root dietsync < database/dummy_data.sql
```

> ⚠️ Re-running `schema.sql` **wipes** the `dietsync` database (that's how you get
> a clean slate with no leftover tables). Don't run it against a database whose
> data you want to keep.

> No MySQL on your PATH? Use **phpMyAdmin** (bundled with XAMPP at
> `http://localhost/phpmyadmin`) and use the **Import** tab to run `schema.sql`
> (then `dummy_data.sql`). Because `schema.sql` creates the database itself, you
> do not need to create `dietsync` first.

---

## 4. Configure the database connection

Edit [backend/config.php](backend/config.php) if your MySQL credentials differ
from the defaults:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL user
define('DB_PASS', '');       // your MySQL password
define('DB_NAME', 'dietsync');
```

The default XAMPP MySQL user is `root` with an empty password, which matches the
shipped values — usually no change is needed.

---

## 5. Serve the project

### Option A — PHP built-in server (quickest)

From the project root:

```bash
php -S localhost:8000
```

Then open: **http://localhost:8000/frontend/index.php**

### Option B — XAMPP / Apache

Copy (or symlink) the project folder into XAMPP's web root:

- Windows: `C:\xampp\htdocs\DietSync`
- macOS: `/Applications/XAMPP/htdocs/DietSync`

Then open: **http://localhost/DietSync/frontend/index.php**

---

## 6. Log in

The app opens on the landing page; use **Login** or **Register**.

### Default accounts (seeded by `schema.sql`) — password: `password`

| Role      | Email                |
|-----------|----------------------|
| Admin     | admin@dietsync.com   |
| Dietitian | sarah@dietsync.com   |
| Patient   | john@dietsync.com    |

### Demo patients (only if you loaded `dummy_data.sql`) — password: `password123`

All four are assigned to the seeded dietitians (Sarah or Michael) with a plan,
food/water logs and feedback.

| Patient            | Email               | Dietitian          |
|--------------------|---------------------|--------------------|
| Alice Thompson     | alice@dietsync.com  | Dr. Sarah Johnson  |
| Bob Martinez       | bob@dietsync.com    | Dr. Sarah Johnson  |
| Carol Lee          | carol@dietsync.com  | Dr. Michael Chen   |
| David Nguyen       | david@dietsync.com  | Dr. Michael Chen   |

Login takes **email + password only** — the role comes from the account.

---

## 7. Quick smoke test

1. **Register** a new patient and a new dietitian (the role chooser is the first
   step of registration).
2. As the patient, open **Log Food**, search a food, pick an amount + unit
   (e.g. *1 glass*), and confirm it appears immediately in *Today's Food Log*.
3. As the patient, open **Water Intake** and add a glass; the total updates.
4. Patient → **Profile** → *Choose Dietitian* → send a request.
5. Dietitian → **Dashboard** → accept the request, then **Meal Plans** → build a
   plan (it must stay within the patient's calorie requirement) and set a water goal.
6. Patient → **Diet Plan** → tick a meal as *Taken*; it shows up in **Meal Log**.
7. Admin → **Foods** → verify a user-added food; **Activity Monitor** and
   **Analytics** show recent actions and stats. Long tables are paged.

---

## 8. Troubleshooting

- **"A server error occurred."** on every page → the DB connection failed. Check
  MySQL is running and the credentials in `backend/config.php`.
- **Blank page / `mysqli` errors** → ensure the PHP `mysqli` extension is enabled
  (it is by default in XAMPP). The real reason is written to PHP's error log, not
  the browser.
- **A just-logged food/water entry doesn't show under "today"** → this was a
  timezone mismatch and is fixed (entries are timestamped from PHP). If you still
  see it, make sure PHP and MySQL agree on the server time / zone.
- **Foreign-key error importing `dummy_data.sql`** → load `schema.sql` first; the
  demo data references the seeded accounts and foods.
- **Leftover `water_logs` / `meal_completions` tables from an old setup** → just
  re-run `database/schema.sql`; it drops and recreates the whole database, so the
  obsolete tables disappear. (Back up first if you need the old data.)

---

## 9. How it fits together (for developers)

- Every protected page includes `frontend/guard.php` and calls `guard(<role>)`
  before any output; it then loads data from the JSON endpoints in `backend/`.
- Shared backend infra: `session.php` (session + CSRF), `config.php` (DB),
  `auth.php` (`require_login`/`require_role`/`require_post`/`require_csrf`,
  `json_response`), `helpers.php` (nutrition math, unit conversion, pagination,
  activity logging).
- Shared frontend: `partials/head.php`, `partials/sidebar.php`,
  `assets/app.js` (CSRF auto-attach, `escapeHtml`, toasts, reusable modal +
  `renderPagination`), and the single `styles/base.css`.
- List endpoints return one page of rows plus a `pagination` block
  (`{page, per_page, total, total_pages}`) consumed by `renderPagination`.

See [README.md](README.md) for the feature overview and [CHANGELOG.md](CHANGELOG.md)
for the change history.
