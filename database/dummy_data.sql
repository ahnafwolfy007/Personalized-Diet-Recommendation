-- ============================================================
-- DietSync — Demo data (optional)
-- ============================================================
-- Load AFTER schema.sql:   mysql -u root dietsync < database/dummy_data.sql
--
-- Adds four patients (assigned to the two seeded dietitians, Sarah=2 / Michael=3)
-- with full plans, plan items, food + water logs and answered feedback. Every
-- column is populated — no NULLs or empty values, and every id is present.
--
-- Password for all demo patients: password123
-- (hash below)
-- ============================================================

USE dietsync;

-- The built-in food that water entries reference (created by schema.sql).
SET @water_id := (SELECT food_id FROM foods WHERE name = 'Drinking Water');

-- ─────────────────────────────────────────────────────────────
-- PATIENTS (user_id 5–8). Health + "about" fields all filled.
--   Alice & Bob → Dr. Sarah Johnson (2);  Carol & David → Dr. Michael Chen (3)
-- ─────────────────────────────────────────────────────────────
INSERT INTO users
  (user_id, name, email, password, role, status, age, gender, height_cm, weight_kg, activity_level, assigned_dietitian_id, works_at, experience_years, specialization, bio, created_at)
VALUES
(5, 'Alice Thompson', 'alice@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient', 'active',
  27, 'Female', 163, 58, 'Moderately Active (3-5 days/week)', 2, 'Northwind Design', 4, 'Graphic design', 'Aiming to eat more balanced meals and lose a little weight.', '2026-05-02 10:00:00'),
(6, 'Bob Martinez',   'bob@dietsync.com',   '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient', 'active',
  34, 'Male', 182, 90, 'Very Active (6-7 days/week)', 2, 'Summit Logistics', 9, 'Operations management', 'Training hard and trying to fuel for muscle gain.', '2026-05-02 10:10:00'),
(7, 'Carol Lee',      'carol@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient', 'active',
  22, 'Female', 158, 52, 'Sedentary (little or no exercise)', 3, 'Maple Leaf Cafe', 2, 'Hospitality', 'Working on portion control and snacking less in the evenings.', '2026-05-03 11:00:00'),
(8, 'David Nguyen',   'david@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient', 'active',
  41, 'Male', 175, 78, 'Lightly Active (1-3 days/week)', 3, 'Harbour Engineering', 15, 'Civil engineering', 'Managing cholesterol with a heart-healthy diet.', '2026-05-03 11:15:00');

-- ─────────────────────────────────────────────────────────────
-- ASSIGNMENT REQUESTS (all accepted)
-- ─────────────────────────────────────────────────────────────
INSERT INTO dietitian_requests (patient_id, dietitian_id, status, created_at) VALUES
(5, 2, 'accepted', '2026-05-02 10:30:00'),
(6, 2, 'accepted', '2026-05-02 10:45:00'),
(7, 3, 'accepted', '2026-05-03 11:20:00'),
(8, 3, 'accepted', '2026-05-03 11:35:00');

-- ─────────────────────────────────────────────────────────────
-- DIET PLANS (plan_id 1–4). Notes + water goal filled.
-- ─────────────────────────────────────────────────────────────
INSERT INTO diet_plans (plan_id, patient_id, dietitian_id, notes, water_goal_ml, created_at, updated_at) VALUES
(1, 5, 2, 'Target around 1800 kcal/day. Eat every 3-4 hours and avoid sugary drinks.', 2500, '2026-05-03 08:00:00', '2026-05-03 08:00:00'),
(2, 6, 2, 'High-protein plan for muscle gain. Protein within 30 minutes after training.', 3000, '2026-05-03 09:00:00', '2026-05-03 09:00:00'),
(3, 7, 3, 'Weight-management plan. No snacking after 8pm; include a 20-minute daily walk.', 2000, '2026-05-04 10:00:00', '2026-05-04 10:00:00'),
(4, 8, 3, 'Heart-healthy plan. Low sodium and oily fish twice a week.', 2200, '2026-05-04 11:00:00', '2026-05-04 11:00:00');

-- ─────────────────────────────────────────────────────────────
-- DIET PLAN ITEMS (food + amount per meal). calories = per_100g/100 * grams.
-- ─────────────────────────────────────────────────────────────
INSERT INTO diet_plan_items (plan_id, meal, food_id, quantity_g, serving_unit, serving_amount, calories) VALUES
-- Alice (plan 1)
(1, 'breakfast', 5,   150, 'g', 150, 106.5),  -- Oatmeal
(1, 'breakfast', 77,  100, 'g', 100, 89.0),   -- Banana
(1, 'lunch',     26,  150, 'g', 150, 247.5),  -- Chicken Breast
(1, 'lunch',     2,   150, 'g', 150, 166.5),  -- Brown Rice
(1, 'dinner',    41,  120, 'g', 120, 249.6),  -- Salmon
(1, 'dinner',    111, 150, 'g', 150, 135.0),  -- Sweet Potato
-- Bob (plan 2)
(2, 'breakfast', 56,  150, 'g', 150, 232.5),  -- Boiled Egg
(2, 'breakfast', 4,   90,  'g', 90,  222.3),  -- Whole Wheat Bread
(2, 'lunch',     30,  200, 'g', 200, 542.0),  -- Beef Steak
(2, 'lunch',     9,   200, 'g', 200, 240.0),  -- Quinoa
(2, 'dinner',    27,  150, 'g', 150, 313.5),  -- Chicken Thigh
(2, 'dinner',    7,   200, 'g', 200, 262.0),  -- Pasta
-- Carol (plan 3)
(3, 'breakfast', 4,   60,  'g', 60,  148.2),  -- Whole Wheat Bread
(3, 'breakfast', 162, 20,  'g', 20,  117.6),  -- Peanut Butter
(3, 'lunch',     140, 200, 'g', 200, 232.0),  -- Lentils
(3, 'lunch',     15,  80,  'g', 80,  220.0),  -- Pita Bread
(3, 'dinner',    48,  120, 'g', 120, 154.8),  -- Tilapia
(3, 'dinner',    2,   100, 'g', 100, 111.0),  -- Brown Rice
-- David (plan 4)
(4, 'breakfast', 56,  100, 'g', 100, 155.0),  -- Boiled Egg
(4, 'breakfast', 13,  60,  'g', 60,  155.4),  -- Rye Bread
(4, 'lunch',     34,  150, 'g', 150, 202.5),  -- Turkey Breast
(4, 'lunch',     199, 200, 'g', 200, 100.0),  -- Vegetable Soup
(4, 'dinner',    44,  150, 'g', 150, 157.5),  -- Cod
(4, 'dinner',    111, 150, 'g', 150, 135.0);  -- Sweet Potato

-- ─────────────────────────────────────────────────────────────
-- FOOD LOGS (entry_type='food', plan_item_id=0 = logged manually).
-- serving_unit='g' and serving_amount = grams.
-- ─────────────────────────────────────────────────────────────
INSERT INTO food_logs (user_id, food_id, entry_type, quantity_g, calories_consumed, serving_unit, serving_amount, plan_item_id, logged_at) VALUES
-- Alice
(5, 5,   'food', 150, 106.5, 'g', 150, 0, '2026-06-13 07:30:00'),
(5, 56,  'food', 60,  93.0,  'g', 60,  0, '2026-06-13 07:35:00'),
(5, 77,  'food', 120, 106.8, 'g', 120, 0, '2026-06-13 08:00:00'),
(5, 26,  'food', 150, 247.5, 'g', 150, 0, '2026-06-13 13:00:00'),
(5, 2,   'food', 200, 222.0, 'g', 200, 0, '2026-06-13 13:10:00'),
(5, 41,  'food', 120, 249.6, 'g', 120, 0, '2026-06-13 19:30:00'),
-- Bob
(6, 56,  'food', 150, 232.5, 'g', 150, 0, '2026-06-13 07:00:00'),
(6, 4,   'food', 90,  222.3, 'g', 90,  0, '2026-06-13 07:05:00'),
(6, 30,  'food', 200, 542.0, 'g', 200, 0, '2026-06-13 13:00:00'),
(6, 9,   'food', 200, 240.0, 'g', 200, 0, '2026-06-13 13:10:00'),
(6, 27,  'food', 150, 313.5, 'g', 150, 0, '2026-06-13 19:30:00'),
-- Carol
(7, 4,   'food', 60,  148.2, 'g', 60,  0, '2026-06-13 08:00:00'),
(7, 162, 'food', 20,  117.6, 'g', 20,  0, '2026-06-13 08:05:00'),
(7, 140, 'food', 200, 232.0, 'g', 200, 0, '2026-06-13 13:00:00'),
(7, 48,  'food', 120, 154.8, 'g', 120, 0, '2026-06-13 19:30:00'),
-- David
(8, 56,  'food', 100, 155.0, 'g', 100, 0, '2026-06-13 07:30:00'),
(8, 34,  'food', 150, 202.5, 'g', 150, 0, '2026-06-13 13:00:00'),
(8, 44,  'food', 150, 157.5, 'g', 150, 0, '2026-06-13 19:30:00'),
(8, 207, 'food', 50,  83.0,  'g', 50,  0, '2026-06-13 19:40:00');

-- ─────────────────────────────────────────────────────────────
-- WATER LOGS (entry_type='water', reference the Drinking Water food).
-- quantity_g holds the millilitres; calories are 0; serving_unit='ml'.
-- ─────────────────────────────────────────────────────────────
INSERT INTO food_logs (user_id, food_id, entry_type, quantity_g, calories_consumed, serving_unit, serving_amount, plan_item_id, logged_at) VALUES
(5, @water_id, 'water', 250, 0, 'ml', 250, 0, '2026-06-13 08:00:00'),
(5, @water_id, 'water', 500, 0, 'ml', 500, 0, '2026-06-13 12:30:00'),
(5, @water_id, 'water', 250, 0, 'ml', 250, 0, '2026-06-13 16:00:00'),
(6, @water_id, 'water', 500, 0, 'ml', 500, 0, '2026-06-13 09:00:00'),
(6, @water_id, 'water', 500, 0, 'ml', 500, 0, '2026-06-13 14:00:00'),
(7, @water_id, 'water', 250, 0, 'ml', 250, 0, '2026-06-13 10:00:00'),
(8, @water_id, 'water', 500, 0, 'ml', 500, 0, '2026-06-13 11:00:00');

-- ─────────────────────────────────────────────────────────────
-- FEEDBACK (all answered, so message and response are both filled).
-- ─────────────────────────────────────────────────────────────
INSERT INTO feedbacks (patient_id, dietitian_id, message, response, status, created_at) VALUES
(5, 2, 'Hi Dr. Sarah, I feel great but lunch is a bit much after the salad. Can I reduce the chicken?',
       'Hi Alice! Absolutely — drop the chicken to 100g and add a few chickpeas for protein. Listen to your body.',
       'responded', '2026-06-10 14:00:00'),
(7, 3, 'Hello Dr. Michael, I struggle with evening snacking. Any healthier alternatives?',
       'Hi Carol! Try 10-12 almonds or carrot sticks with hummus, and herbal tea — keep healthy snacks visible.',
       'responded', '2026-06-11 20:00:00');

-- ─────────────────────────────────────────────────────────────
-- USER-CONTRIBUTED FOODS (unverified — for the admin Foods review demo).
-- ─────────────────────────────────────────────────────────────
INSERT INTO foods (name, calories_per_100g, category, created_by, is_verified, created_at) VALUES
('Homemade Veg Curry',  95,  'Prepared', 5, 0, '2026-06-12 18:00:00'),
('Protein Energy Ball', 389, 'Sweet',    6, 0, '2026-06-12 19:00:00');

-- ─────────────────────────────────────────────────────────────
-- ACTIVITY LOG (a few recent actions for the admin Activity Monitor demo).
-- ─────────────────────────────────────────────────────────────
INSERT INTO activity_log (user_id, actor_role, action, detail, created_at) VALUES
(5, 'patient',   'register',      'Registered as patient',                 '2026-05-02 10:00:00'),
(2, 'dietitian', 'accept_request','Accepted patient #5',                   '2026-05-02 10:31:00'),
(2, 'dietitian', 'plan_created',  'Plan created for patient #5 (994 kcal)','2026-05-03 08:00:00'),
(5, 'patient',   'send_feedback', 'Sent feedback to dietitian #2',         '2026-06-10 14:00:00'),
(2, 'dietitian', 'respond_feedback','Answered feedback #1',                '2026-06-10 15:00:00'),
(6, 'patient',   'add_food',      'Added food "Protein Energy Ball" (pending verification)', '2026-06-12 19:00:00');
