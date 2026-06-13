-- ============================================================
-- DietSync — Migration v2 (feature upgrade)
-- Additive schema changes for the 12-feature upgrade. Safe to run
-- on a database created from schema.sql (pre-upgrade). A fresh
-- install via the updated schema.sql already includes all of these.
--
-- Uses MariaDB "IF NOT EXISTS" syntax for columns/indexes. On MySQL 8,
-- remove the "IF NOT EXISTS" clauses and skip anything that already exists.
-- ============================================================

USE dietsync;

-- ------------------------------------------------------------
-- users : dietitian professional details + shared public bio
-- ------------------------------------------------------------
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS works_at         VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS experience_years INT          NULL,
  ADD COLUMN IF NOT EXISTS specialization   VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS bio              TEXT         NULL;

-- ------------------------------------------------------------
-- foods : user contributions + admin verification
-- ------------------------------------------------------------
ALTER TABLE foods
  ADD COLUMN IF NOT EXISTS created_by  INT          NULL,
  ADD COLUMN IF NOT EXISTS is_verified TINYINT(1)   NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_foods_verified ON foods (is_verified);

-- created_by references users; SET NULL keeps a user-added food if the author is removed.
ALTER TABLE foods
  ADD CONSTRAINT fk_foods_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- food_logs : measurement-unit metadata (calories still derived from quantity_g)
-- ------------------------------------------------------------
ALTER TABLE food_logs
  ADD COLUMN IF NOT EXISTS serving_unit   VARCHAR(20) NOT NULL DEFAULT 'g',
  ADD COLUMN IF NOT EXISTS serving_amount FLOAT       NULL;

-- ------------------------------------------------------------
-- diet_plan_items : database-driven meal plans (replaces free text)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS diet_plan_items (
    item_id    INT AUTO_INCREMENT PRIMARY KEY,
    plan_id    INT   NOT NULL,
    meal       ENUM('breakfast','lunch','dinner') NOT NULL,
    food_id    INT   NOT NULL,
    quantity_g FLOAT NOT NULL,
    calories   FLOAT NOT NULL,
    KEY idx_plan_items_plan_meal (plan_id, meal),
    KEY idx_plan_items_food (food_id),
    CONSTRAINT fk_plan_items_plan FOREIGN KEY (plan_id) REFERENCES diet_plans(plan_id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_items_food FOREIGN KEY (food_id) REFERENCES foods(food_id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- meal_completions : "Taken/Done" ticks (one per item per day)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meal_completions (
    completion_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id    INT  NOT NULL,
    item_id       INT  NOT NULL,
    completed_on  DATE NOT NULL,
    log_id        INT  NULL,
    UNIQUE KEY uniq_completion_item_day (item_id, completed_on),
    KEY idx_completions_patient_day (patient_id, completed_on),
    CONSTRAINT fk_completions_patient FOREIGN KEY (patient_id) REFERENCES users(user_id)          ON DELETE CASCADE,
    CONSTRAINT fk_completions_item    FOREIGN KEY (item_id)    REFERENCES diet_plan_items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_completions_log     FOREIGN KEY (log_id)     REFERENCES food_logs(log_id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- water_logs : daily water intake
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS water_logs (
    water_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    amount_ml INT NOT NULL,
    logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_water_user_logged (user_id, logged_at),
    CONSTRAINT fk_water_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- assignment_removals : unassignment rationale (visible to removed party + admin)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS assignment_removals (
    removal_id      INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT  NOT NULL,
    dietitian_id    INT  NOT NULL,
    removed_by      INT  NOT NULL,
    removed_by_role ENUM('patient','dietitian') NOT NULL,
    reason          TEXT NOT NULL,
    acknowledged    TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_removals_patient (patient_id),
    KEY idx_removals_dietitian (dietitian_id),
    CONSTRAINT fk_removals_patient   FOREIGN KEY (patient_id)   REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_removals_dietitian FOREIGN KEY (dietitian_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- activity_log : admin Activity Monitor feed (NO food/water entries)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NULL,
    actor_role  VARCHAR(20)  NOT NULL DEFAULT '',
    action      VARCHAR(60)  NOT NULL,
    detail      VARCHAR(255) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_activity_created (created_at),
    KEY idx_activity_user (user_id),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
