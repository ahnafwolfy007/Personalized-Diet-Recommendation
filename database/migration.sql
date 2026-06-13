-- ============================================================
-- DietSync — Migration for EXISTING databases
-- Adds the performance indexes and the diet-plan uniqueness
-- constraint that the original dump did not include.
-- Safe to run on a database created from the pre-consolidation dump.
-- (A fresh install via schema.sql already includes all of these.)
--
-- Uses MariaDB "IF NOT EXISTS" index syntax. On MySQL 8, remove the
-- "IF NOT EXISTS" clauses (and skip any index that already exists).
-- ============================================================

USE dietsync;

-- Filter users by role (get_dietitians, admin stats/users).
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);

-- Daily food-log range lookups: WHERE user_id = ? AND logged_at >= ? AND logged_at < ?
CREATE INDEX IF NOT EXISTS idx_food_logs_user_logged ON food_logs (user_id, logged_at);

-- Food list ordering by category, name.
CREATE INDEX IF NOT EXISTS idx_foods_category_name ON foods (category, name);

-- Dietitian feedback inbox filters by dietitian + status.
CREATE INDEX IF NOT EXISTS idx_feedbacks_dietitian_status ON feedbacks (dietitian_id, status);

-- Pending-request lookups on both sides.
CREATE INDEX IF NOT EXISTS idx_requests_patient_status   ON dietitian_requests (patient_id, status);
CREATE INDEX IF NOT EXISTS idx_requests_dietitian_status ON dietitian_requests (dietitian_id, status);

-- Enforce one plan per (patient, dietitian) pair.
-- NOTE: this fails if duplicate pairs already exist; de-duplicate first if so.
ALTER TABLE diet_plans
  ADD UNIQUE INDEX IF NOT EXISTS uniq_plan_patient_dietitian (patient_id, dietitian_id);
