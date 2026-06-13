-- ============================================================
-- DietSync — Migration v2 (feature upgrade)
-- Additive schema changes for the feature upgrade. Safe to run on a database
-- created from the original schema.sql. A fresh install via the updated
-- schema.sql already includes all of these.
--
-- Design note: food_logs is the single table for EVERY patient entry — normal
-- food, water intake (entry_type='water'), and "Taken" diet-plan items
-- (plan_item_id set). The separate water_logs / meal_completions tables are
-- intentionally NOT used and are dropped at the end if a previous run created them.
--
-- Uses MariaDB "IF NOT EXISTS" syntax for columns/indexes. On MySQL 8, remove
-- the "IF NOT EXISTS" clauses and skip anything that already exists.
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

ALTER TABLE foods
  ADD CONSTRAINT fk_foods_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- food_logs : universal entry table (food / water / taken plan item)
--   * serving_unit / serving_amount : the unit + amount the user picked
--     (quantity_g stays the resolved grams calories derive from; for water it
--      holds the millilitres and serving_unit = 'ml')
--   * entry_type : 'food' (default) or 'water'
--   * plan_item_id : set when the row came from ticking a diet-plan item "Taken"
--   * food_id is now NULLable so water rows (no food) fit the same table
-- ------------------------------------------------------------
ALTER TABLE food_logs
  ADD COLUMN IF NOT EXISTS serving_unit   VARCHAR(20) NOT NULL DEFAULT 'g',
  ADD COLUMN IF NOT EXISTS serving_amount FLOAT       NULL,
  ADD COLUMN IF NOT EXISTS entry_type     ENUM('food','water') NOT NULL DEFAULT 'food',
  ADD COLUMN IF NOT EXISTS plan_item_id   INT         NULL;

ALTER TABLE food_logs MODIFY food_id INT NULL;

CREATE INDEX IF NOT EXISTS idx_food_logs_type      ON food_logs (user_id, entry_type, logged_at);
CREATE INDEX IF NOT EXISTS idx_food_logs_plan_item ON food_logs (plan_item_id);

-- ------------------------------------------------------------
-- diet_plans : a dietitian-set daily water goal for the patient
-- ------------------------------------------------------------
ALTER TABLE diet_plans
  ADD COLUMN IF NOT EXISTS water_goal_ml INT NULL;

-- ------------------------------------------------------------
-- diet_plan_items : database-driven meal plans (food + amount).
--   serving_unit / serving_amount mirror the patient's measurement options so a
--   dietitian can plan "1 glass" etc; quantity_g is the resolved grams.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS diet_plan_items (
    item_id        INT AUTO_INCREMENT PRIMARY KEY,
    plan_id        INT   NOT NULL,
    meal           ENUM('breakfast','lunch','dinner') NOT NULL,
    food_id        INT   NOT NULL,
    quantity_g     FLOAT NOT NULL,
    serving_unit   VARCHAR(20) NOT NULL DEFAULT 'g',
    serving_amount FLOAT NULL,
    calories       FLOAT NOT NULL,
    KEY idx_plan_items_plan_meal (plan_id, meal),
    KEY idx_plan_items_food (food_id),
    CONSTRAINT fk_plan_items_plan FOREIGN KEY (plan_id) REFERENCES diet_plans(plan_id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_items_food FOREIGN KEY (food_id) REFERENCES foods(food_id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If diet_plan_items already existed from an earlier run, add the unit columns.
ALTER TABLE diet_plan_items
  ADD COLUMN IF NOT EXISTS serving_unit   VARCHAR(20) NOT NULL DEFAULT 'g',
  ADD COLUMN IF NOT EXISTS serving_amount FLOAT NULL;

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

-- ------------------------------------------------------------
-- Consolidation cleanup: water + completions now live in food_logs.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS meal_completions;
DROP TABLE IF EXISTS water_logs;
