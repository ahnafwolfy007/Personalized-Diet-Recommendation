# DietSync

A personalized diet-recommendation system. Patients log food and track calories,
dietitians review assigned patients and write meal plans, and an admin manages users.
Built with vanilla PHP (mysqli), plain JavaScript, and a small hand-written CSS system —
no frameworks, no build step.

## Tech stack

- **Backend:** PHP 8 + MySQL/MariaDB (`mysqli`, prepared statements)
- **Frontend:** server-rendered PHP pages that load data via `fetch` from JSON endpoints
- **Styling:** a single utility stylesheet (`frontend/styles/base.css`)

## Project layout

```
backend/    JSON endpoints + shared infrastructure
  session.php   session bootstrap + CSRF token
  config.php    DB connection
  auth.php      JSON responses, auth/role/CSRF guards (used by endpoints)
  helpers.php   BMR / BMI / calorie-need calculations
frontend/   user-facing pages
  guard.php           server-side auth guard (included by every protected page)
  assets/app.js       CSRF auto-attach + escapeHtml + mobile nav
  partials/           shared head + sidebar
  styles/             base.css (+ user-profile.css)
database/
  schema.sql      authoritative schema for a FRESH install (run this)
  migration.sql   index additions for an EXISTING (pre-consolidation) database
  dummy_data.sql  optional demo data
```

## Setup

1. Start MySQL/MariaDB (e.g. via XAMPP).
2. Create the database and tables:
   ```
   mysql -u root < database/schema.sql
   ```
   (Optional demo data: `mysql -u root dietsync < database/dummy_data.sql`)
3. Adjust credentials in `backend/config.php` if your MySQL user/password differ.
4. Serve the project root with PHP and open `frontend/index.php`.

### Default accounts

All seeded accounts use the password **`password`**.

| Role      | Email                 |
|-----------|-----------------------|
| Admin     | admin@dietsync.com    |
| Dietitian | sarah@dietsync.com    |
| Patient   | john@dietsync.com     |

## Security model

- Every protected page calls `guard(<role>)` server-side and redirects unauthenticated
  or wrong-role visitors before any HTML is rendered.
- All endpoints authorize via `require_login()` / `require_role()`; state-changing
  endpoints additionally require a POST and a valid CSRF token.
- The CSRF token is emitted as a `<meta name="csrf-token">` tag and attached to every
  mutating `fetch` automatically by `assets/app.js`.
- All dynamic values rendered into the DOM are escaped with `escapeHtml()`.
- Passwords are hashed with bcrypt; the session id is regenerated on login.
