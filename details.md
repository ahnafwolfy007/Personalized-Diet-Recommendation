# DietSync — The Project Bible 📖

> **Who is this for?** You. The teammate who is brand new to web development and
> feels a little lost staring at a folder full of `.php`, `.js`, and `.sql` files.
> By the end of this document you will understand *exactly* how DietSync works,
> from the moment someone clicks a button to the instant a row lands in the
> database — and you'll feel confident changing it.
>
> We explain every piece of jargon the first time it appears. Take your time.
> Nothing here assumes you already "get it."

---

## Table of contents

1. [Project Overview & Architecture Rationale](#1-project-overview--architecture-rationale)
2. [Directory & File Map](#2-directory--file-map)
3. [The Database Layer (MySQL)](#3-the-database-layer-mysql)
4. [The Frontend–Backend Connection (How they talk)](#4-the-frontendbackend-connection-how-they-talk)
5. [Step-by-Step Feature Workflows](#5-step-by-step-feature-workflows)
6. [Security & State Management](#6-security--state-management)
7. [A Beginner's Guide to Modifying the Project](#7-a-beginners-guide-to-modifying-the-project)
8. [Glossary (quick jargon lookup)](#8-glossary-quick-jargon-lookup)

---

## 1. Project Overview & Architecture Rationale

### What is DietSync?

**DietSync is a web app that helps people eat better.** A user logs the food they
eat, the app adds up the calories, compares them against what that person *should*
eat, and shows progress. A qualified professional can attach to a patient and
write them a meal plan. An administrator keeps the whole system tidy.

There are **three kinds of users** (we call these **roles**):

| Role          | Who they are                          | What they mainly do                                                                 |
|---------------|---------------------------------------|-------------------------------------------------------------------------------------|
| **Patient**   | A regular person tracking their diet  | Log food & water, follow a diet plan, see their calorie/BMI progress, message a dietitian |
| **Dietitian** | A nutrition professional              | Accept patients, build meal plans, watch each patient's intake, answer questions    |
| **Admin**     | The platform operator                 | Manage users, verify community-submitted foods, watch site-wide stats and activity  |

> **What is a "role"?** It's just a label stored on each account that decides what
> that person is allowed to see and do. The whole security model revolves around
> this one word — keep it in mind.

### The Architecture (the big picture)

A web app is really **two programs talking to each other**:

```
   YOUR BROWSER                          THE SERVER (your PC running PHP)
 ┌──────────────┐    1. asks for page   ┌────────────────────────────────┐
 │              │ ───────────────────►  │  frontend/*.php  (the "pages")  │
 │  HTML + CSS  │ ◄───────────────────  │                                 │
 │  + JavaScript│    2. gets HTML back  │            ▼ talks to ▼         │
 │              │                       │  backend/*.php   (the "logic")  │
 │              │ ───3. asks for data─► │            ▼ talks to ▼         │
 │              │ ◄───4. gets JSON────  │  MySQL database  (the "memory") │
 └──────────────┘                       └────────────────────────────────┘
```

- The **frontend** is what the user sees and clicks — the pages, the styling, the
  little bits of interactivity.
- The **backend** is the brain — it checks who you are, does the math, and reads
  from / writes to the database.
- The **database** is the long-term memory — it remembers users, foods, and logs
  even after everyone closes their browser.

In DietSync we keep these in clearly separated folders:

```
frontend/   ← the pages the user looks at        (HTML, CSS, JavaScript, a little PHP)
backend/    ← the logic & data access            (pure PHP)
database/   ← the blueprint for the "memory"     (SQL)
```

### Why vanilla PHP / JS / CSS, with *no frameworks*?

> **What is a "framework"?** A large pre-written toolkit (like React, Laravel, or
> Vue) that gives you lots of features for free — but you have to learn *its* rules,
> install *its* tools, and run a "build step" before your code can run.

DietSync deliberately uses **none** of those. We wrote everything by hand with the
plain, built-in languages. Here is *why* — and why it's great for beginners:

- **Easier learning curve.** What you see is what runs. There is no hidden magic
  translating your code into something else. A `.php` file *is* the program.
- **No build step.** You don't have to run `npm install` or compile anything. Save
  the file, refresh the browser, done.
- **Lightweight & direct control.** No giant dependency folder, no version
  upgrades breaking things. The entire app is a handful of small files you can
  read top to bottom in an afternoon.
- **Transferable knowledge.** Once you understand *raw* PHP/JS, every framework
  later makes more sense, because you'll know what it's doing for you under the hood.

The trade-off: we write a bit more code by hand (e.g., our own pagination and
security helpers). That's a deliberate choice — clarity over convenience.

---

## 2. Directory & File Map

Here is every meaningful file, with a one or two sentence "job description."

### Top level

| File / Folder    | What it's for |
|------------------|---------------|
| `README.md`      | The quick intro + feature list + setup summary. |
| `SETUP.md`       | Detailed, step-by-step instructions to run the project locally. |
| `CHANGELOG.md`   | A dated history of what changed in the project and why. |
| `details.md`     | **This file** — the deep explanation you're reading now. |
| `database/`      | The SQL files that build and seed the database. |
| `backend/`       | All the server-side PHP logic and API endpoints. |
| `frontend/`      | All the user-facing pages, styling, and JavaScript. |
| `images/`        | Static images used by the app. |

### `database/`

| File              | What it's for |
|-------------------|---------------|
| `schema.sql`      | **The single source of truth for the database.** Running it DROPs (deletes) any old `dietsync` database and rebuilds every table from scratch, then seeds the default accounts and ~240 foods. |
| `dummy_data.sql`  | *Optional* demo data — four sample patients with plans, food/water logs, and feedback — so you have something to look at. |

### `backend/` — shared infrastructure (the files every endpoint leans on)

| File           | What it's for |
|----------------|---------------|
| `session.php`  | Starts the user's **session** (the server's memory of "who is logged in") and creates the **CSRF token** (a security code — explained in §6). No database needed. |
| `config.php`   | Holds the database connection details and **opens the connection** (`$conn`). Every endpoint that touches data uses this. |
| `auth.php`     | The security gatekeeper helpers: `require_login()`, `require_role()`, `require_post()`, `require_csrf()`, and `json_response()`. Endpoints include this to enforce "who's allowed." |
| `helpers.php`  | Shared math & utilities: BMI, daily-calorie need, unit→grams conversion, pagination math, and the activity logger. |

### `backend/` — the endpoints (each one answers a specific request)

> **What is an "endpoint"?** A single PHP file the frontend can call to *do one
> thing* — log in, save a food, fetch a list. Think of each as a tiny door with a
> specific purpose. The full concept is in §4.

These are grouped by who uses them. Names are intentionally descriptive
(`verb_noun.php`):

**Authentication & profile**
| File | Job |
|------|-----|
| `login.php` | Check email + password, start a session, return where to go next. |
| `register.php` | Create a new patient or dietitian account, then auto-log them in. |
| `logout.php` | End the session. |
| `get_profile.php` / `update_profile.php` | Read / edit *your own* profile. |
| `get_public_profile.php` | Read someone *else's* public profile (role-aware, privacy-safe). |

**Patient — food, water, plans, reports**
| File | Job |
|------|-----|
| `get_foods.php` | Return the food catalogue, smart-sorted so your most-used foods come first. |
| `add_food.php` | Let any user submit a new food (starts unverified, pending admin review). |
| `log_food.php` | Save a food entry (converts the chosen unit to grams, computes calories). |
| `delete_food_log.php` | Remove one of your food-log entries. |
| `log_water.php` / `get_water.php` / `delete_water.php` | Add / read / remove water intake (stored in the same `food_logs` table). |
| `get_diet_plan.php` | Fetch the patient's current plan, including which items are "Taken" today. |
| `log_plan_item.php` | Tick (or un-tick) a plan meal as "Taken," auto-logging it to the food log. |
| `get_dashboard.php` | The numbers for the patient home screen (calories today, BMI, etc.). |
| `get_report.php` | Aggregated history for the reports page. |

**Patient ↔ Dietitian relationship**
| File | Job |
|------|-----|
| `get_dietitians.php` | List dietitians a patient can request. |
| `patient_send_request.php` / `patient_check_request.php` | Send / check an assignment request. |
| `dietitian_get_requests.php` / `dietitian_respond_request.php` | Dietitian sees and accepts/rejects requests. |
| `unassign.php` | Either side ends the assignment **with a required reason**. |
| `get_removal_notice.php` | Show the removed person a banner explaining why, and let them dismiss it. |
| `*_feedback.php` (several) | The patient↔dietitian message thread. |

**Dietitian tools**
| File | Job |
|------|-----|
| `dietitian_get_patients.php` / `dietitian_get_all_patients.php` | List the dietitian's patients. |
| `dietitian_get_plan.php` / `dietitian_create_plan.php` | Read / save a patient's meal plan (validated against their calorie need). |
| `dietitian_get_analytics.php` | Per-patient intake, adherence, water, last-active. |

**Admin tools**
| File | Job |
|------|-----|
| `admin_get_users.php` / `admin_delete_user.php` | Manage user accounts. |
| `admin_get_foods.php` / `admin_update_food.php` / `admin_delete_food.php` | Verify / edit / remove community foods. |
| `admin_get_stats.php` / `admin_get_analytics.php` | Site-wide numbers. |
| `admin_get_activity.php` | The Activity Monitor feed (deliberately **excludes** routine food/water logging). |

### `frontend/`

| File / Folder | What it's for |
|---------------|---------------|
| `index.php` | The public landing page. |
| `login.php` / `register.php` | The login and (two-step, role-based) registration pages. |
| `guard.php` | **The page bouncer.** Included at the top of every protected page; calls `guard(<role>)` to kick out anyone not allowed *before* any HTML is sent. |
| `user-*.php` | The patient pages (dashboard, log-food, diet-plan, water, meal-log, report, profile, feedback). |
| `dietitian-*.php` | The dietitian pages (dashboard, create-plan, analytics, feedback, profile). |
| `admin-*.php` | The admin pages (dashboard, users, foods, analytics, activity). |
| `profile-view.php` | The read-only public profile viewer. |
| `partials/head.php` | Shared `<head>` markup (title, the CSRF `<meta>` tag, CSS, `app.js`). |
| `partials/sidebar.php` | The shared navigation menu, which shows different links per role. |
| `partials/category_options.php` | Emits the food-category `<option>` list for dropdowns. |
| `assets/app.js` | **Shared JavaScript** on every page: auto-attaches the CSRF token to requests, `escapeHtml()`, toast popups, the pagination control, and modals. |
| `styles/base.css` | The single hand-written stylesheet (the whole design system). |
| `styles/user-profile.css` | A small extra stylesheet for the profile pages. |

---

## 3. The Database Layer (MySQL)

> **What is a database?** A program (MySQL, in our case) whose only job is to store
> data reliably and let you ask questions about it very fast. Data lives in
> **tables** — think spreadsheets, where each **row** is one record and each
> **column** is one field.

The complete blueprint is in [`database/schema.sql`](database/schema.sql). Running
that one file builds every table.

### The core tables

#### `users` — everybody (patients, dietitians, admins)
One row per account. A single `role` column decides what kind of user it is.

- **Shared columns:** `user_id` (the unique number for this person), `name`,
  `email` (must be unique), `password` (stored *hashed* — never plain text), `role`,
  `status`.
- **Patient-only columns** (NULL for other roles): `age`, `gender`, `height_cm`,
  `weight_kg`, `activity_level` — the inputs to the calorie/BMI math.
- **Dietitian-only columns** (NULL for other roles): `works_at`,
  `experience_years`, `specialization`, `bio` — the professional details.
- **`assigned_dietitian_id`** — for a patient, the `user_id` of *their* dietitian
  (or NULL if they have none yet).

#### `foods` — the food catalogue
One row per food, with `calories_per_100g` (the nutritional fact everything is
computed from). Community contributions carry `created_by` (who submitted it) and
`is_verified` (0 until an admin approves it). There's also a special built-in
`Drinking Water` food (0 calories) that water entries point at — more on that below.

#### `food_logs` — *the* activity table (this is the clever bit)
Instead of three separate tables, **every patient entry lives here**:
- a normal food entry (`entry_type = 'food'`),
- a glass of water (`entry_type = 'water'`, pointing at the "Drinking Water" food),
- a "Taken" tick from a diet plan (`plan_item_id` > 0).

Each row stores `quantity_g` (the resolved grams), `calories_consumed`, the chosen
`serving_unit`/`serving_amount`, and `logged_at` (the timestamp). Keeping it all in
one place means "today's intake" is a single, simple query.

#### `diet_plans` + `diet_plan_items` — meal plans
- `diet_plans`: one plan per (patient, dietitian) pair, plus `notes` and a
  `water_goal_ml`.
- `diet_plan_items`: the individual meals — each row is *(plan, meal, food,
  amount, calories)*. A plan "has many" items.

#### `feedbacks` — the message thread
Each row is a patient `message` and the dietitian's optional `response`, with a
`status` of `pending` or `responded`.

#### `dietitian_requests` — assignment requests
Each row is "patient X would like dietitian Y," with `status` of `pending`,
`accepted`, or `rejected`.

> Two more supporting tables: **`assignment_removals`** (the recorded reason when
> an assignment is ended) and **`activity_log`** (the admin Activity Monitor feed).

### How the tables relate: Foreign Keys, explained simply

A **foreign key** is a column in one table that holds the `id` of a row in another
table — that's how rows get *linked*. Read these as plain English:

```
food_logs.user_id   ──► users.user_id      "this log belongs to THIS user"
food_logs.food_id   ──► foods.food_id       "this log is about THIS food"
diet_plan_items.plan_id ──► diet_plans.plan_id   "this meal is part of THIS plan"
diet_plans.patient_id   ──► users.user_id   "this plan is FOR this patient"
users.assigned_dietitian_id ──► users.user_id    "this patient's dietitian"
```

Foreign keys also keep data honest. For example, `food_logs.user_id` is defined
with `ON DELETE CASCADE` — meaning if a user is deleted, *their* logs are
automatically deleted too, so you never have orphaned records pointing at a person
who no longer exists.

### How PHP talks to the database

#### `config.php` — opening the connection

```php
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
```

This one line creates `$conn`, a live connection to MySQL, using the credentials
defined just above it. Every endpoint pulls in `config.php` (usually via `auth.php`)
and then uses `$conn` to run queries. If the connection fails, `config.php`
immediately returns a safe generic error and stops — it never leaks the real reason
to the browser.

#### Prepared statements — and why we *always* use them

> **What is a "query"?** A command, written in SQL, that asks the database to do
> something: *"find the user whose email is X,"* *"insert this new food."*

The naïve (and **dangerous**) way to build a query is to glue user input straight
into the text:

```php
// ❌ NEVER do this
$sql = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";
```

If a malicious user types `' OR '1'='1` as their email, the query's meaning
changes and they could read every user's data. This attack is called **SQL
injection**, and it's one of the most common ways apps get hacked.

The safe way — used **everywhere** in DietSync — is a **prepared statement**. You
write the query with `?` placeholders, then hand the values over *separately*. The
database treats them strictly as data, never as commands:

```php
// ✅ The DietSync way (from login.php)
$stmt = $conn->prepare("SELECT user_id, name, password, role FROM users WHERE email = ?");
$stmt->bind_param('s', $email);   // 's' = this value is a string
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
```

`bind_param('s', $email)` says "fill the first `?` with `$email`, and it's a
**s**tring." The letters mean: `s` = string, `i` = integer, `d` = decimal/float.
Because the value can never break out of its slot, injection is impossible.

**Rule for you: if a value comes from the user, it goes through a `?` placeholder.
No exceptions.**

---

## 4. The Frontend–Backend Connection (How they talk)

### What is an API / endpoint?

Imagine a restaurant. You (the **frontend**) don't walk into the kitchen — you give
your order to a waiter at a specific window. Each window does one job. In web terms:

- An **endpoint** is one of those windows — a single backend URL you can call to do
  one thing (e.g. `backend/log_food.php` = "save a food").
- An **API** (Application Programming Interface) is the whole set of windows plus
  the rules for ordering: *what* to send and *what* you'll get back.

In DietSync, every file in `backend/` is an endpoint. The "rules" are simple:
**send a form (or query string), get back JSON.**

> **What is JSON?** A plain-text way to write data that both JavaScript and PHP
> understand. It looks like this:
> ```json
> { "success": true, "calories": 248.5, "quantity_g": 250 }
> ```
> It's just labels and values. The backend produces it; the frontend reads it.

### What is `fetch`?

`fetch()` is a built-in JavaScript function that lets a page **talk to the server
without reloading.** The page asks an endpoint for something in the background, gets
JSON back, and updates just the part of the screen that changed. (This style is
often called **AJAX**.)

### The round trip, step by step

Here's the universal pattern every interactive feature follows. Read it once and
you'll recognise it everywhere in the codebase.

**① Frontend sends the request (JavaScript):**
```js
var formData = new FormData(theForm);          // gather the inputs
fetch('../backend/log_food.php', {             // pick the endpoint
    method: 'POST',                            // POST = "I want to change something"
    body: formData
})
.then(function (res) { return res.json(); })   // ② parse the JSON reply
.then(function (data) {                         // ③ react to it
    if (data.success) {
        showToast('Saved!', 'success');         // update the screen
    } else {
        showToast(data.message, 'error');       // show the problem
    }
});
```

**② Backend receives it, does the work, replies (PHP):**
```php
$user_id = require_login();    // who is this? (reads the session)
require_post();                // must be a POST
require_csrf();                // must carry a valid security token

// ... validate input, run prepared-statement queries ...

json_response(['success' => true, 'calories' => $calories]);  // reply as JSON
```

**③ Frontend reads the JSON and updates the page** — shows a toast, refreshes a
list, ticks a checkbox. **No full page reload happens.**

### Two important details that make this seamless

- **The CSRF token rides along automatically.** Notice the frontend code above
  never mentions a security token — yet every "POST" is protected. That's because
  [`assets/app.js`](frontend/assets/app.js) *wraps* the browser's `fetch` and
  quietly attaches the token (from a `<meta>` tag) to every changing request. You
  get the protection for free. (Full story in §6.)
- **Output is always escaped.** When JavaScript puts server data onto the page, it
  runs it through `escapeHtml()` first, so a user who names a food
  `<script>steal()</script>` can't run code on someone else's screen. This blocks
  an attack called **XSS (cross-site scripting)**.

---

## 5. Step-by-Step Feature Workflows

Let's trace three real features from "click" to "saved," naming the exact files.

### Workflow 1 — User Login 🔑

**Goal:** A returning user types their email + password and lands on the right
dashboard.

```
[ login.php page ]
   │  user types email + password, clicks "Login"
   ▼
[ inline JavaScript ]  (bottom of frontend/login.php)
   │  e.preventDefault()  ← stop the normal form submit/reload
   │  fetch('../backend/login.php', { method:'POST', body: formData })
   ▼
[ backend/login.php ]
   │  1. require_post()                      → must be a POST
   │  2. read email + password from $_POST
   │  3. prepared SELECT … WHERE email = ?   → look the user up by EMAIL ONLY
   │  4. password_verify(typed, stored hash) → is the password correct?
   │  5. if bad → return { success:false, message:"Invalid email or password." }
   │  6. session_regenerate_id(true)         → fresh session id (anti-fixation)
   │  7. $_SESSION['user_id'/'user_role'…]   → REMEMBER who they are
   │  8. return { success:true, redirect:"…dashboard.php" }
   ▼
[ back in the browser ]
   │  data.success === true
   ▼
   window.location.href = data.redirect      → go to the role's dashboard
```

**Key teaching points:**
- The role is **never** sent by the browser — it's read from the stored account.
  A user cannot choose to "log in as admin."
- A **single generic error** ("Invalid email or password") is returned whether the
  email or the password was wrong, so an attacker can't discover which emails exist.
- Step 7 is the entire point of logging in: the server writes facts into the
  **session** so the *next* request knows who you are (see §6).

### Workflow 2 — Patient Logs Food 🍎

**Goal:** A patient searches for "Banana," enters "1 glass," and sees it appear in
today's log instantly.

```
[ user-log-food.php ]
   │  patient searches → picks "Banana", enters amount "1", unit "glass"
   │  clicks "Add to Log"
   ▼
[ inline JavaScript ]
   │  fetch('../backend/log_food.php', { method:'POST', body: formData })
   │     (CSRF token auto-attached by app.js)
   ▼
[ backend/log_food.php ]
   │  1. require_login(); require_post(); require_csrf()
   │  2. read food_id, serving_unit ('glass'), serving_amount (1)
   │  3. validate the unit against serving_units()  (allow-list)
   │  4. quantity_g = serving_to_grams('glass', 1)  → 250 g   ← done in PHP!
   │  5. SELECT calories_per_100g … WHERE food_id = ?   (prepared)
   │  6. calories = calories_per_100g / 100 * 250      ← computed in PHP!
   │  7. logged_at = date('Y-m-d H:i:s')               ← PHP clock, not MySQL
   │  8. INSERT INTO food_logs (…) VALUES (…)           (prepared)
   │  9. return { success:true, calories:…, quantity_g:250 }
   ▼
[ back in the browser ]
   │  showToast("Logged!", "success")
   └─ re-fetch today's log list and re-render it → entry appears immediately
```

**Key teaching points:**
- **The browser never sends the calories or grams.** It sends *"glass"* and *"1."*
  The server does the unit→grams conversion (`serving_to_grams`) and the calorie
  math itself. This means a tampered-with browser cannot lie about calories — a
  core security principle: **never trust the client; recompute on the server.**
- `logged_at` is written from PHP's clock (not MySQL's `CURRENT_TIMESTAMP`) so the
  timestamp always matches the "today" filter the dashboard uses. (This fixed a
  real bug where a just-logged entry didn't show up under "today.")

### Workflow 3 — Dietitian Creates a Diet Plan 📋

**Goal:** A dietitian assembles meals for a patient, and the app refuses to save a
plan that exceeds the patient's calorie budget.

```
[ dietitian-create-plan.php ]
   │  dietitian picks a patient, adds meal rows (food + amount + unit),
   │  sets a water goal, clicks "Save Plan"
   │  JS bundles all the rows into a JSON string
   ▼
[ fetch → backend/dietitian_create_plan.php ]   (POST + CSRF)
   ▼
[ backend/dietitian_create_plan.php ]
   │  1. require_role('dietitian'); require_post(); require_csrf()
   │  2. SELECT … WHERE user_id = ? AND assigned_dietitian_id = ?  ← OWNERSHIP CHECK
   │       (the patient MUST belong to this dietitian, or it's rejected 403)
   │  3. daily_need = daily_calorie_need(patient's height/weight/age/…)
   │  4. for each item: convert unit→grams, re-SELECT the food's calories,
   │       recompute that item's calories, add to a running total
   │  5. if total > daily_need → reject: "exceeds the patient's requirement"
   │  6. begin_transaction()                         ← all-or-nothing
   │       upsert the diet_plans row (+ water goal)
   │       delete old diet_plan_items, insert the new ones
   │     commit()   (rollback() if anything fails)
   │  7. log_activity(… 'plan_created'/'plan_updated' …)
   │  8. return { success:true, message:"Diet plan saved! Total: … kcal." }
   ▼
[ browser ] → showToast(success); the patient will now see it on their Diet Plan page
```

**Key teaching points:**
- **The calorie cap is enforced on the server (step 5).** The page also shows a
  live total to *help* the dietitian, but even if someone bypassed the page, the
  backend recomputes every item and refuses an over-budget plan.
- **Ownership is checked in the SQL itself (step 2):** `AND assigned_dietitian_id
  = ?`. A dietitian literally cannot save a plan for a patient who isn't theirs,
  because the lookup returns no row.
- **A transaction (step 6)** groups several writes so they either *all* succeed or
  *all* roll back — you can never end up with a half-saved plan.

---

## 6. Security & State Management

This section answers two questions: *"How does the app remember who I am?"* and
*"How does it stop people doing things they shouldn't?"*

### How we "remember" who is logged in: PHP Sessions

The web is **stateless** — each request to the server is a blank slate; the server
doesn't naturally remember the previous one. So how does the app know you're still
logged in on page 5? **Sessions.**

Here's the mechanism, plainly:

1. When you log in, the server creates a **session** — a small bucket of data kept
   *on the server* — and writes facts into it:
   ```php
   $_SESSION['user_id']   = 4;
   $_SESSION['user_role'] = 'patient';
   ```
2. The server hands your browser a single, random **session id** as a cookie.
3. On every later request, your browser automatically sends that cookie back. The
   server uses the id to find *your* bucket and instantly knows "this is user 4,
   a patient."

`backend/session.php` sets this up **once**, securely, for the whole app:
- `httponly` — JavaScript can't read the session cookie (limits theft via XSS).
- `secure` — over HTTPS the cookie is only sent encrypted.
- `samesite: Lax` — the cookie isn't sent on sketchy cross-site requests.

And at login we call `session_regenerate_id(true)` to issue a brand-new id at the
moment of login — closing a hole called **session fixation**.

### What `auth.php` does: protecting the endpoints

[`backend/auth.php`](backend/auth.php) is the **gatekeeper toolkit**. Every backend
endpoint includes it and starts with a few guard calls. Each guard either lets the
request continue *or* immediately replies with an error and stops.

| Guard | Plain-English meaning | If it fails |
|-------|-----------------------|-------------|
| `require_login()` | "You must be logged in." Returns your `user_id`. | `401 Not logged in` |
| `require_role('dietitian')` | "You must be logged in **and** be a dietitian." | `403 Access denied` |
| `require_post()` | "This action must use POST (it changes data)." | `405 Invalid method` |
| `require_csrf()` | "This request must carry a valid security token." | `419 Token invalid` |

A typical write-endpoint therefore opens like a checklist:

```php
$dietitian_id = require_role('dietitian');  // logged in AND a dietitian?
require_post();                              // a POST?
require_csrf();                              // valid token?
// ...only now do we touch the database.
```

### What CSRF is, and how we stop it

> **CSRF (Cross-Site Request Forgery)** is an attack where a *different, evil*
> website secretly makes your logged-in browser fire a request at DietSync — like
> "delete my account" — riding on your existing session.

The defense is a **CSRF token**: a secret random string the server generates per
session (`session.php`) and embeds in each page as a `<meta name="csrf-token">` tag
(`partials/head.php`). Every changing request must echo that token back. An evil
site can't read it (it's on a different origin), so its forged request is rejected
with `419`.

The beautiful part: you rarely think about it, because `app.js` **auto-attaches the
token** to every POST `fetch`, and `require_csrf()` checks it on the server. Protection
by default.

### Two layers of access control: page guard *and* endpoint guard

Security is enforced in **two** places, on purpose:

1. **`frontend/guard.php`** runs at the very top of every protected *page*. It
   redirects you away *before any HTML is produced* if you're not logged in or hold
   the wrong role. (A dietitian who types the admin URL gets bounced to their own
   dashboard.)
2. **`backend/auth.php`** guards every *endpoint* the same way.

Why both? Because the page guard only protects *viewing pages*, while the endpoint
guards protect the *data itself*. Even if someone skipped the pages and called an
endpoint directly, `auth.php` stops them. **Never rely on the UI hiding a button as
your security — the real check is always on the server.**

### The recurring security recipe

Every state-changing endpoint in DietSync follows the same five-ingredient recipe.
When you write a new one, copy it:

1. **POST + CSRF** — `require_post()` then `require_csrf()`.
2. **Authentication & role** — `require_login()` / `require_role(...)`.
3. **Ownership in the `WHERE` clause** — e.g. `AND user_id = ?` so users can only
   touch *their own* rows.
4. **Recompute on the server** — never trust calories, totals, or grams from the
   browser; calculate them in PHP.
5. **Prepared statements + escaped output** — `?` placeholders going in,
   `escapeHtml()` coming out.

---

## 7. A Beginner's Guide to Modifying the Project

You will eventually want to add something new. Here's a safe, repeatable order to
do it — illustrated with a worked example.

### The golden rule of ordering

> **Build from the database outward: Data → Backend → Frontend.**
> You can't save data you have no table for, and you can't display data your
> backend doesn't return yet. So always go in that direction.

### Worked example: add a "Steps Tracker" (let patients log daily steps)

**Step 1 — The database (the foundation).**
Add a table to [`database/schema.sql`](database/schema.sql). Follow the style of the
existing tables exactly (InnoDB, utf8mb4, a foreign key back to `users`):

```sql
CREATE TABLE step_logs (
    step_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    steps      INT NOT NULL,
    logged_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_step_logs_user_logged (user_id, logged_at),
    CONSTRAINT fk_step_logs_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Then re-run `schema.sql` so your local database has the new table.
*(Reminder: re-running it wipes the database — that's expected for a fresh build.)*

**Step 2 — The backend "save" endpoint.**
Create `backend/log_steps.php`. **Copy an existing simple endpoint like
`log_water.php` as your template** so you inherit the security recipe:

```php
<?php
require_once __DIR__ . '/auth.php';

$user_id = require_login();   // 1. who are you?
require_post();               // 2. must be POST
require_csrf();               // 3. valid token

$steps = intval($_POST['steps'] ?? 0);          // 4. read + validate input
if ($steps <= 0 || $steps > 100000) {
    json_response(['success' => false, 'message' => 'Enter a valid step count.']);
}

$now  = date('Y-m-d H:i:s');
$stmt = $conn->prepare(
    "INSERT INTO step_logs (user_id, steps, logged_at) VALUES (?, ?, ?)"  // 5. prepared
);
$stmt->bind_param('iis', $user_id, $steps, $now);
$ok = $stmt->execute();
$stmt->close();

json_response($ok
    ? ['success' => true]
    : ['success' => false, 'message' => 'Could not save steps.']);
```

**Step 3 — The backend "read" endpoint.**
Create `backend/get_steps.php` (model it on `get_water.php`) to return today's
total and recent history as JSON. Use `require_login()`, a prepared `SELECT … WHERE
user_id = ?`, and `day_bounds()` from `helpers.php` for "today."

**Step 4 — The frontend page.**
Create `frontend/user-steps.php`. The fastest path is to **copy
`frontend/user-water.php`** and adapt it, because it already has the correct
skeleton:

```php
<?php require_once __DIR__ . '/guard.php'; guard('patient');   // page bouncer
$pageTitle = 'Steps'; require __DIR__ . '/partials/head.php'; ?>
<body>
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main-content">
     <!-- your form + list go here -->
  </main>
  <script>
    // fetch('../backend/get_steps.php')  → render today's steps
    // form submit → fetch('../backend/log_steps.php', {method:'POST', body: …})
    //   then re-render. (CSRF token is auto-attached by app.js.)
  </script>
</body>
```

**Step 5 — Wire it into navigation.**
Add a link to the patient section of
[`frontend/partials/sidebar.php`](frontend/partials/sidebar.php) so users can reach
the new page.

**Step 6 — Test, then document.**
Click through it as a patient. Then add a line to
[`CHANGELOG.md`](CHANGELOG.md) describing what you added.

### Your pre-flight checklist for *any* new feature

- [ ] **Data:** new/changed table added to `schema.sql`, with a foreign key & index.
- [ ] **Backend (save):** `require_login`/`require_role` + `require_post` +
      `require_csrf`, input validated, ownership in the `WHERE`, **prepared
      statements**, any math recomputed server-side.
- [ ] **Backend (read):** authenticated, prepared, returns clean JSON.
- [ ] **Frontend:** page starts with `guard(<role>)`, includes `head.php` +
      `sidebar.php`, uses `fetch`, renders dynamic values through `escapeHtml`.
- [ ] **Navigation:** linked from `sidebar.php` for the right role(s).
- [ ] **Quality:** run `php -l yourfile.php` to catch syntax errors; click-test it.
- [ ] **Docs:** update `CHANGELOG.md` (and `README.md`/`SETUP.md` if user-facing).

> **The fastest way to be correct is to imitate.** Find the existing file closest
> to what you want, copy it, and change it. Every file in this project already
> follows the security and style rules — by copying, you inherit them for free.

---

## 8. Glossary (quick jargon lookup)

| Term | Plain meaning |
|------|---------------|
| **Frontend** | The part the user sees and clicks (HTML/CSS/JS pages). |
| **Backend** | The server-side logic that checks permissions, does math, and reads/writes data (PHP). |
| **Endpoint** | One backend file that does one job when called (e.g. `log_food.php`). |
| **API** | The whole collection of endpoints + the rules for using them. |
| **Query** | A command written in SQL asking the database to do something. |
| **JSON** | A simple text format of labels and values used to pass data between JS and PHP. |
| **`fetch`** | A JavaScript function that talks to an endpoint in the background (no page reload). |
| **AJAX** | The general name for "update the page by fetching data without reloading." |
| **Role** | The label (`patient`/`dietitian`/`admin`) deciding what a user may do. |
| **Session** | The server's per-user memory of "who is logged in." |
| **Cookie** | A small token the browser stores and resends; here it carries the session id. |
| **Foreign key** | A column holding another table's id, linking the two rows together. |
| **Prepared statement** | A query with `?` placeholders that keeps user input as *data*, blocking SQL injection. |
| **SQL injection** | An attack that smuggles commands in through unescaped user input (we prevent it). |
| **CSRF** | An attack where another site forges a request from your logged-in browser (blocked by the token). |
| **XSS** | An attack that injects malicious HTML/JS into a page (blocked by `escapeHtml`). |
| **Hash** | A one-way scramble of a password; we store the hash, never the real password. |
| **Transaction** | A group of database writes that all succeed or all roll back together. |
| **Migration** | A change to the database structure. *DietSync uses none* — `schema.sql` is the single rebuildable source. |

---

> **Final word.** DietSync is intentionally small and consistent. Once you've read
> one endpoint and one page, you've effectively read them all — they follow the same
> patterns on purpose. When in doubt: **find the closest existing file, copy it,
> and follow the five-ingredient security recipe.** You've got this. 💪

*See also: [README.md](README.md) for the feature overview, [SETUP.md](SETUP.md)
to run it locally, and [CHANGELOG.md](CHANGELOG.md) for the change history.*
