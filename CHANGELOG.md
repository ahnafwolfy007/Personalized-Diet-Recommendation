# Changelog

All notable changes to DietSync are documented here.

## [Feature upgrade] – 2026-06-14

A large feature pass spanning authentication, profiles, meal logging, plans,
analytics and admin tooling — still vanilla PHP/JS/CSS, no dependencies. New
tables are additive; existing databases upgrade via `database/migration_v2.sql`.

### Authentication & profiles

- **Role removed from login.** Login now takes email + password only; the role is
  derived from the account, never chosen by the client. `backend/login.php` was
  rewritten on the shared `auth.php` helpers and looks the user up by email alone
  (still a single generic "Invalid email or password" message).
  *Files: `frontend/login.php`, `backend/login.php`.*
- **Role-based registration.** Registration starts with a two-button role choice
  (Patient = green, Dietitian = indigo, with hover/focus states), then reveals a
  role-specific form — health metrics for patients, professional details
  (`works_at`, experience, specialization, bio) for dietitians. Both roles save to
  the same `users` table; role-irrelevant columns are stored `NULL`.
  *Files: `frontend/register.php`, `backend/register.php`.*
- **Public profiles + "View Profile".** Every patient and dietitian has a public,
  role-aware profile (`frontend/profile-view.php`, `backend/get_public_profile.php`).
  Patients can view a dietitian before requesting, dietitians can view a patient
  before accepting, and admins can view anyone from User Management. Email is only
  exposed to the admin or the user themselves. Dietitians gained an editable
  **Profile** page (`frontend/dietitian-profile.php`).

### Meal logging

- **Household measurement units.** Log Food accepts Grams, Portion (150 g),
  Glass (250 g), Table-spoon (15 g) and Tea-spoon (5 g). The unit→grams conversion
  and the calorie math are done server-side (`serving_to_grams()` in
  `helpers.php`); the client value is never trusted. `food_logs` gained
  `serving_unit` / `serving_amount`.
- **Smart food sorting.** For patients, `get_foods.php` returns the food list with
  their most recently/frequently logged foods first (a single LEFT-JOIN over a
  per-user usage summary — no N+1), reducing repetitive searching.
- **User-contributed foods + admin verification.** Users can add a missing food
  (name, category, calories/100g) via `backend/add_food.php`; it is stored
  `is_verified = 0`, attributed to the author, and usable immediately but visibly
  badged **Unverified**. A new admin **Foods** page
  (`frontend/admin-foods.php` + `admin_get_foods/update_food/delete_food.php`)
  lists every food (pending first) to verify, edit, or remove.

### Diet plans

- **Database-driven meal plans.** Dietitians now build plans by selecting foods and
  amounts from the database instead of free text (`diet_plan_items` table). The
  total calories for the three meals must be **≤ the patient's required calories**,
  re-validated server-side; all writes are transactional. Legacy free-text plans
  still render as a fallback.
  *Files: `frontend/dietitian-create-plan.php`, `backend/dietitian_create_plan.php`,
  `backend/dietitian_get_plan.php`, `backend/get_diet_plan.php`.*
- **Interactive "Taken" checkmarks.** Patients tick a plan item as taken; this
  auto-logs it to their meal log (and un-ticking removes it), idempotent per item
  per day via `meal_completions` and a transaction.
  *Files: `frontend/user-diet-plan.php`, `backend/log_plan_item.php`.*

### Relationships, analytics & monitoring

- **Unassignment with rationale.** A patient or dietitian can end an assignment but
  must give a reason (`backend/unassign.php`, `assignment_removals` table). The
  removed counterpart sees the reason as a banner
  (`backend/get_removal_notice.php`), and it is recorded for the admin.
- **Patient Analytics (dietitian).** New sidebar page showing each patient's
  today vs need, 7-day average intake, plan adherence, today's water and last
  activity — all from aggregated queries (no N+1).
  *Files: `frontend/dietitian-analytics.php`, `backend/dietitian_get_analytics.php`.*
- **Water intake (patient).** A dedicated water tracker (quick-add + custom,
  daily goal, 7-day chart) whose totals surface in Patient Analytics.
  *Files: `frontend/user-water.php`, `backend/log_water.php` / `get_water.php` /
  `delete_water.php`.*
- **Admin Analytics & Activity Monitor.** `frontend/admin-analytics.php` shows
  app-wide stats and a 7-day signup chart; `frontend/admin-activity.php` shows a
  paginated feed of meaningful actions (login, registration, requests, plan
  create/update, feedback, food add/verify, unassignment). Individual food and
  water log entries are deliberately **not** recorded, keeping the feed signal-rich.

### Security / reliability

- New mutating endpoints all require POST + CSRF, enforce role/ownership in the
  `WHERE` clause, validate inputs against allow-lists, recompute all
  money-numbers (calories, plan totals, grams) server-side, and wrap multi-row
  writes in transactions with rollback + `error_log`.
- `get_diet_plan.php` is now scoped to the patient's current dietitian (no stale
  plan after reassignment).

### Database

- `database/migration_v2.sql` (new) adds the columns and tables above to an
  existing install. `database/schema.sql` folds the same DDL in for fresh installs,
  and `database/dummy_data.sql` seeds structured plan items, a few unverified
  foods, and water history.

## [Usability & engagement] – 2026-06-13

A follow-up pass focused on making the app friendlier, more accessible, and more
engaging — still vanilla PHP/JS/CSS, no dependencies. Every new flow was verified
against the running database (food search, add/delete logs with ownership and
CSRF checks, the dashboard target, and the incomplete-profile path).

### New features

- **Searchable food picker.** The Log Food page replaced the 239-option
  `<select>` with a type-to-search combobox (filters by name or category,
  keyboard arrow/Enter/Escape support, ARIA `combobox`/`listbox` roles). You can
  also click any row in the Food Database table to select it.
  *Files: `frontend/user-log-food.php`.*

- **Remove a logged meal.** Patients can now delete their own food log entries
  from both Log Food and Meal Log. New endpoint `backend/delete_food_log.php`
  enforces ownership (`WHERE log_id = ? AND user_id = ?`) and CSRF.
  *Verified: own delete succeeds; deleting another user's entry or re-deleting
  returns 404; a request without the CSRF token returns 419.*

- **Engaging dashboard.** Added a time-of-day greeting with the user's first
  name and the date, a calorie progress bar (turns red when over target), a
  "Log Food" quick action, "View all" / "Log your first meal" links, and a
  profile-completeness nudge that appears when age/height/weight are missing so
  the calorie target isn't a silent `0`. `get_dashboard.php` now returns a
  `profile_complete` flag.

- **Password visibility toggle** on the login and registration forms
  (accessible button with `aria-pressed`/`aria-label`).

### Experience & accessibility

- **Toast notifications.** Added a shared, non-blocking toast system
  (`showToast()` in `assets/app.js`, styles in `base.css`) with an `aria-live`
  region. Replaced blocking `alert()` calls and several ad-hoc inline message
  boxes (dietitian feedback & requests, admin user delete, patient feedback,
  food add/delete) with consistent toasts.
- Icon-only buttons (delete entry) carry descriptive `aria-label`s; the combobox
  and password toggle expose proper roles/states.

## [Production hardening] – 2026-06-13

A focused pass that closed the critical security gaps, fixed the data-access
hot paths, completed a broken feature, and modernized the UI — without
introducing any framework or build step. Every change below was verified against
a running MySQL instance (login, CSRF, role checks, the dietitian and feedback
flows, and `EXPLAIN` on the optimized queries).

### Security

- **Server-side page guards.** Added `frontend/guard.php`; every protected page
  now calls `guard(<role>)` before any output and redirects unauthenticated or
  wrong-role visitors. Previously the pages were static HTML and relied entirely
  on client-side JavaScript redirects, so the full UI shell was reachable by
  anyone and there was no real cross-role protection.
  *Verified: logged-out request to a protected page returns `302 → login.php`; a
  patient requesting the dietitian dashboard renders zero bytes.*

- **Stored XSS fixed across the app.** All values rendered into the DOM via
  `innerHTML` are now escaped with a shared `escapeHtml()` helper
  (`frontend/assets/app.js`). Affected pages: dietitian feedback (`patient_name`,
  `message`, `response`), dietitian dashboard (`patient_name`), admin users
  (`name`, `email`), profile (dietitian list), and create-plan (patient list).
  Example attack closed: a user setting their name to `<img src=x onerror=…>`
  previously executed script in another user's (including the admin's) session.

- **Robust delete action in user management.** Replaced the inline
  `onclick="deleteUser(…, '<name>')"` (which broke or injected when a name
  contained a quote or `<`) with delegated handling via `data-*` attributes.

- **CSRF protection.** Added a per-session token (`csrf_token()` in
  `backend/session.php`), emitted as a `<meta name="csrf-token">` tag and
  attached automatically to every mutating `fetch` by `app.js`. All eight
  state-changing endpoints now call `require_csrf()` (and `require_post()`).
  *Verified: a mutating POST without the token returns HTTP 419; with the token
  it succeeds.*

- **Session hardening.** Session cookie is now `HttpOnly` + `SameSite=Lax`
  (+ `Secure` over HTTPS); the session id is regenerated on login and register
  to prevent fixation; logout clears `$_SESSION`, expires the cookie, and
  destroys the session.

- **No internal details leaked.** `config.php` no longer echoes the MySQL
  `connect_error`; endpoints no longer return `$stmt->error`. Failures are
  logged server-side and surfaced to clients as a generic message.
  *Verified: an endpoint hit with the DB down returns a generic JSON error.*

- **Account-enumeration reduced.** Login now returns a single
  "Invalid email, password, or role" message instead of distinguishing a bad
  email/role from a bad password.

- **Tighter input/authorization checks.** `patient_send_request.php` now
  verifies the target is a real, active dietitian (not just any user id);
  `get_foods.php` now requires authentication.

### Database & Performance

- **One authoritative schema.** Replaced three divergent SQL files
  (`dietsync.sql`, `dietsync_v2.sql`) with `database/schema.sql` (fresh install)
  and `database/migration.sql` (index additions for an existing database).
  *Verified: `schema.sql` loads cleanly and seeds 239 foods + default accounts.*

- **Indexes added** for the real query patterns: `users(role)`,
  `food_logs(user_id, logged_at)`, `foods(category, name)`,
  `feedbacks(dietitian_id, status)`, `dietitian_requests(patient_id, status)`
  and `(dietitian_id, status)`, plus a unique `diet_plans(patient_id,
  dietitian_id)` that enforces one plan per pair.

- **Sargable date queries.** Daily-intake lookups changed from
  `DATE(logged_at) = ?` (non-indexable) to a half-open range
  `logged_at >= ? AND logged_at < ?`.
  *Verified: `EXPLAIN` now reports a `range` scan on
  `idx_food_logs_user_logged` instead of a full scan.*

- **N+1 removed.** `dietitian_get_patients.php` previously ran one extra query
  per patient to fetch today's intake; it now aggregates in a single
  `LEFT JOIN … GROUP BY`.
  *Verified: returns all assigned patients with correct `today_intake` in one
  round trip.*

- **CSS delivery.** Pages linked a one-line stylesheet that only
  `@import`-ed `base.css`, forcing a serial, render-blocking second request.
  Pages now link `base.css` directly.

### Architecture & Maintainability

- **Shared backend infrastructure:** `backend/session.php` (session + CSRF),
  `backend/auth.php` (JSON responses, `require_login`/`require_role`/
  `require_csrf`/`require_post`), and `backend/helpers.php` (BMR, BMI, daily
  calorie need, day bounds). Endpoints were converted to these helpers, removing
  copy-pasted auth checks and duplicated nutrition math.

- **Shared frontend partials:** `frontend/partials/head.php` and
  `frontend/partials/sidebar.php` replace the ~40-line sidebar and head block
  that was duplicated across 11 pages. The sidebar is role-aware and marks the
  active item.

### Feature completion

- **Patient → dietitian feedback flow.** The `feedbacks` table and the
  dietitian's read/respond endpoints existed, but patients had no way to create
  feedback (it only appeared via seed data). Added `backend/patient_send_feedback.php`,
  `backend/patient_get_feedback.php`, and the `frontend/user-feedback.php` page
  (linked in the patient sidebar).
  *Verified: a patient sends a message, sees the thread, and the dietitian
  receives it.*

### UI/UX

- **Responsive layout.** Added breakpoints so multi-column grids collapse and
  the sidebar becomes a slide-in drawer (hamburger toggle + overlay, wired in
  `app.js`) on tablet/mobile. The previous layout had no media queries and was
  unusable below ~1000px.
- **Consistent iconography.** Replaced ad-hoc emoji (👨‍⚕️, ⏳) with the same
  line-SVG set used elsewhere.
- **Polish.** `:focus-visible` outlines, disabled-button styling, and
  user-facing error copy (removed developer-facing "Is XAMPP running?" text).

### Reliability

- **Atomic request handling.** `dietitian_respond_request.php` now wraps the
  request-status update and the patient assignment in a transaction that rolls
  back on failure.
- **Input normalization.** Optional profile fields (age, gender, height, weight,
  activity) are stored as `NULL` when blank instead of `0`/empty string; date
  parameters are validated and fall back to today when malformed.

### Removed

- Legacy static HTML mockups at the repo root and their per-page stylesheets,
  superseded by the `frontend/` application.
- `database/dietsync.sql` and `database/dietsync_v2.sql` (consolidated into
  `schema.sql` + `migration.sql`).
- Orphaned stylesheets with no corresponding page
  (`dietitian-meal-logs.css`, `dietitian-users.css`) and the empty one-line
  per-page stylesheets.

### Migration notes

- **Fresh install:** run `database/schema.sql`.
- **Existing database** created from the old dump: run `database/migration.sql`
  to add the new indexes and the diet-plan uniqueness constraint. It uses
  MariaDB `IF NOT EXISTS` index syntax; on MySQL 8 remove those clauses and skip
  any index that already exists. The unique constraint requires no duplicate
  `(patient_id, dietitian_id)` plan rows.
