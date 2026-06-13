# DietSync

A personalized diet-recommendation system. Patients log food and track calories,
dietitians review assigned patients and write meal plans, and an admin manages users.
Built with vanilla PHP (mysqli), plain JavaScript, and a small hand-written CSS system —
no frameworks, no build step.

## Features

- **Patients** — dashboard with a personalized greeting, calorie progress bar and
  BMI; a type-to-search food picker that sorts your most-used foods first and lets
  you log in grams or household units (portion, glass, tea-/table-spoon); the
  ability to add a missing food; an interactive, database-driven diet plan with
  one-tap "Taken" checkmarks that auto-log the meal; a water-intake tracker; daily
  reports; a public profile; and a feedback thread with their dietitian.
- **Dietitians** — accept/reject patient requests, monitor each patient's daily
  intake, build database-driven meal plans validated against the patient's calorie
  requirement, a **Patient Analytics** view (intake, adherence, water, last
  activity), answer feedback, and maintain a professional profile.
- **Admin** — overview stats, user management with profile inspection, a **Foods**
  page to verify/edit user-contributed foods, an **Analytics** page, and an
  **Activity Monitor**.
- **Both sides** — patients and dietitians can end an assignment with a required
  reason that the other party (and the admin) can see.
- **Throughout** — server-side paginated tables, searchable inputs, non-blocking
  toast notifications, a responsive layout with a mobile drawer, password
  show/hide, and keyboard- and screen-reader-friendly controls.

> New here? See **[SETUP.md](SETUP.md)** for full, step-by-step run instructions.

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
  schema.sql      the complete database — drops & recreates everything (run this)
  dummy_data.sql  optional demo data
```

## Setup

See **[SETUP.md](SETUP.md)** for full step-by-step instructions. In short:

1. Start MySQL/MariaDB (e.g. via XAMPP).
2. Create the database — `schema.sql` is one complete file that drops and
   recreates everything (no migrations to track):
   ```
   mysql -u root < database/schema.sql
   ```
   Optional demo data (patients, plans, food/water logs, feedback):
   ```
   mysql -u root dietsync < database/dummy_data.sql
   ```
3. Adjust credentials in `backend/config.php` if your MySQL user/password differ.
4. Serve the project root with PHP and open `frontend/index.php`.

### Default accounts

Seeded by `schema.sql` — password is **`password`**:

| Role      | Email                 |
|-----------|-----------------------|
| Admin     | admin@dietsync.com    |
| Dietitian | sarah@dietsync.com    |
| Patient   | john@dietsync.com     |

Seeded by `dummy_data.sql` — four patients (password **`password123`**) assigned
to the seeded dietitians, each with a plan, food/water logs and feedback:

| Patient         | Email               | Dietitian          |
|-----------------|---------------------|--------------------|
| Alice Thompson  | alice@dietsync.com  | Dr. Sarah Johnson  |
| Bob Martinez    | bob@dietsync.com    | Dr. Sarah Johnson  |
| Carol Lee       | carol@dietsync.com  | Dr. Michael Chen   |
| David Nguyen    | david@dietsync.com  | Dr. Michael Chen   |

## Security model

- Every protected page calls `guard(<role>)` server-side and redirects unauthenticated
  or wrong-role visitors before any HTML is rendered.
- All endpoints authorize via `require_login()` / `require_role()`; state-changing
  endpoints additionally require a POST and a valid CSRF token.
- The CSRF token is emitted as a `<meta name="csrf-token">` tag and attached to every
  mutating `fetch` automatically by `assets/app.js`.
- All dynamic values rendered into the DOM are escaped with `escapeHtml()`.
- Passwords are hashed with bcrypt; the session id is regenerated on login.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed record of changes.
