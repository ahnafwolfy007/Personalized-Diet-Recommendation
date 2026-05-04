# Personalized Diet Recommendation (DietSync)

Student prototype for a **personalized diet recommendation** web app: users log food, see reports and diet plans, while dietitians and admins have their own dashboards.

## What lives on `main`

This branch is **documentation only** — there is no application code here.

## Where the code is

All **static HTML/CSS** frontend pages and `style.css` are on the branch:

**`ahnaf/frontend`**

Clone and open that branch to work on the UI, for example:

```bash
git clone https://github.com/ahnafwolfy007/Personalized-Diet-Recommendation.git
cd Personalized-Diet-Recommendation
git checkout ahnaf/frontend
```

Then open `index.html` in a browser (or use a simple local server if you prefer).

## Pages (on `ahnaf/frontend`)

| Page | Role / purpose |
|------|----------------|
| `index.html` | Landing |
| `login.html`, `register.html` | Auth (prototype links) |
| `user-dashboard.html`, `log-food.html`, `daily-report.html`, `diet-plan.html` | End user |
| `dietitian-dashboard.html`, `create-meal-plan.html` | Dietitian |
| `admin-dashboard.html`, `users-management.html` | Admin |

## Tech

- HTML + CSS only (no build step)
- Single stylesheet: `style.css`

## License / course use

Use and adapt as needed for your course or portfolio; adjust this section if you add a formal license.
