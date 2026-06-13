-- ============================================================
-- DietSync – Dummy Data Seed
-- Password for all NEW accounts: password123
-- Hash: $2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- 1. USERS  (new IDs: 9-14)
--    user_id 9  = Dr. Emma Wilson    (dietitian)
--    user_id 10 = Dr. James Park     (dietitian)
--    user_id 11 = Alice Thompson     (patient – assigned to Emma)
--    user_id 12 = Bob Martinez       (patient – assigned to Emma)
--    user_id 13 = Carol Lee          (patient – assigned to James)
--    user_id 14 = David Nguyen       (patient – assigned to James)
-- ─────────────────────────────────────────────────────────────
INSERT INTO `users`
  (`user_id`,`name`,`email`,`password`,`role`,`age`,`gender`,`height_cm`,`weight_kg`,`activity_level`,`status`,`created_at`,`assigned_dietitian_id`)
VALUES
  (9,  'Dr. Emma Wilson',  'emma@dietsync.com',  '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'dietitian', 38, 'Female', 165, 62,  'Moderately Active (3-5 days/week)', 'active', '2026-05-01 09:00:00', NULL),
  (10, 'Dr. James Park',   'james@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'dietitian', 45, 'Male',   178, 80,  'Lightly Active (1-3 days/week)',    'active', '2026-05-01 09:05:00', NULL),
  (11, 'Alice Thompson',   'alice@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient',   27, 'Female', 163, 58,  'Moderately Active (3-5 days/week)', 'active', '2026-05-02 10:00:00', 9),
  (12, 'Bob Martinez',     'bob@dietsync.com',   '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient',   34, 'Male',   182, 90,  'Very Active (6-7 days/week)',       'active', '2026-05-02 10:10:00', 9),
  (13, 'Carol Lee',        'carol@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient',   22, 'Female', 158, 52,  'Sedentary (little or no exercise)', 'active', '2026-05-03 11:00:00', 10),
  (14, 'David Nguyen',     'david@dietsync.com', '$2y$10$LTnNEXpr5PdVcQ.8hXPANeocD.o3H8JvQR4M8R6tEBiAHS1yQRpbW', 'patient',   41, 'Male',   175, 78,  'Lightly Active (1-3 days/week)',    'active', '2026-05-03 11:15:00', 10);

-- ─────────────────────────────────────────────────────────────
-- 2. DIETITIAN REQUESTS  (all accepted)
-- ─────────────────────────────────────────────────────────────
INSERT INTO `dietitian_requests` (`request_id`,`patient_id`,`dietitian_id`,`status`,`created_at`) VALUES
  (4,  11, 9,  'accepted', '2026-05-02 10:30:00'),
  (5,  12, 9,  'accepted', '2026-05-02 10:45:00'),
  (6,  13, 10, 'accepted', '2026-05-03 11:20:00'),
  (7,  14, 10, 'accepted', '2026-05-03 11:35:00');

-- ─────────────────────────────────────────────────────────────
-- 3. DIET PLANS
-- ─────────────────────────────────────────────────────────────
INSERT INTO `diet_plans` (`plan_id`,`patient_id`,`dietitian_id`,`breakfast_text`,`lunch_text`,`dinner_text`,`notes`,`created_at`,`updated_at`) VALUES
(2, 11, 9,
 'Oatmeal with fresh berries and a drizzle of honey\n1 boiled egg\nGreen tea (unsweetened)\n1 banana',
 'Grilled chicken breast (150g) with brown rice (1 cup)\nMixed green salad with olive oil dressing\nCucumber slices\nWater or lemon water',
 'Baked salmon (120g) with steamed broccoli and sweet potato\nSmall Greek yogurt\nChamomile tea',
 'Target: 1800 kcal/day.\nDrink at least 2L of water daily.\nAvoid sugary drinks and fried foods.\nEat every 3-4 hours to maintain metabolism.\nWeigh-in every Monday morning.',
 '2026-05-03 08:00:00', '2026-05-03 08:00:00'),

(3, 12, 9,
 'Scrambled eggs (3 large) with whole wheat toast\nAvocado (half)\nProtein shake with almond milk\n1 apple',
 'Beef steak (200g) with quinoa (1 cup) and grilled vegetables\nGreek salad\nWater with lemon',
 'Chicken thigh (150g) with pasta and tomato sauce\nSteamed green beans\nCottage cheese (100g)',
 'Target: 2800 kcal/day – high protein for muscle gain.\nConsume protein within 30 minutes post-workout.\nAvoid alcohol.\nPre-workout: banana + peanut butter.\nRest day calories: reduce to 2400 kcal.',
 '2026-05-03 09:00:00', '2026-05-03 09:00:00'),

(4, 13, 10,
 'Whole wheat toast with peanut butter\n1 banana\nSkimmed milk (200ml)\nMultivitamin supplement',
 'Lentil soup (200ml) with pita bread\nGreek salad\n1 orange\nGreen tea',
 'Grilled tilapia (120g) with brown rice (half cup)\nSteamed spinach with garlic\nYogurt (low fat)',
 'Target: 1500 kcal/day – weight management plan.\nEat slowly and mindfully.\nNo snacking after 8pm.\nInclude a 20-minute walk daily.\nAvoid processed foods and sugar.',
 '2026-05-04 10:00:00', '2026-05-04 10:00:00'),

(5, 14, 10,
 '2 boiled eggs with rye bread\nTomato and cucumber slices\nBlack coffee (no sugar)\n1 kiwi',
 'Turkey breast sandwich on whole wheat bread\nVegetable soup\n1 pear\nSparkling water',
 'Baked cod (150g) with roasted sweet potato\nMixed salad\nHummus (2 tbsp)',
 'Target: 2000 kcal/day – heart-healthy diet.\nLow sodium: avoid adding salt to meals.\nOmega-3 sources: salmon, sardines twice a week.\nLimit red meat to once per week.\nMonitor blood pressure weekly.',
 '2026-05-04 11:00:00', '2026-05-04 11:00:00');

-- ─────────────────────────────────────────────────────────────
-- 4. FEEDBACKS
-- ─────────────────────────────────────────────────────────────
INSERT INTO `feedbacks` (`feedback_id`,`patient_id`,`dietitian_id`,`message`,`response`,`status`,`created_at`) VALUES
(1, 11, 9,
 'Hi Dr. Emma, I have been following the plan for a week now. I feel great but I am struggling to eat enough at lunch. The chicken breast seems too much for me after the salad. Can I replace some of it?',
 'Hi Alice! Great to hear you are feeling good. You can absolutely reduce the chicken to 100g and add more salad or a small portion of chickpeas for protein. Listen to your body — the goal is to feel satisfied, not stuffed. Keep it up!',
 'responded', '2026-05-10 14:00:00'),

(2, 12, 9,
 'Dr. Emma, I missed my workout for 3 days due to work travel. Should I change my diet on rest days? I ended up eating the full plan and feel a bit bloated.',
 'Hi Bob! Yes, on rest days reduce your overall intake by about 300-400 kcal. Cut down on the carbs (skip the quinoa or replace with salad) and keep protein the same. Bloating usually resolves in 1-2 days. Plan ahead when you travel — carry protein bars!',
 'responded', '2026-05-12 09:30:00'),

(3, 13, 10,
 'Hello Dr. James, I find it very hard to resist snacking in the evening. I usually eat crackers or chips. What healthier alternatives can I have?',
 'Hi Carol! Evening cravings are common. Try replacing chips with: 10-12 almonds, carrot sticks with hummus, or a small Greek yogurt. Herbal tea (chamomile or peppermint) also helps reduce hunger signals at night. Keep healthy snacks visible in your fridge!',
 'responded', '2026-05-14 20:00:00'),

(4, 14, 10,
 'Dr. James, my doctor told me my cholesterol is slightly elevated. Should I avoid eggs completely? I really enjoy them for breakfast.',
 NULL,
 'pending', '2026-06-11 08:00:00'),

(5, 11, 9,
 'Hi Dr. Emma! Quick update — I lost 1.5 kg in two weeks! I am very happy. Can I reward myself with a cheat meal?',
 'Amazing progress Alice, well done! Yes, one planned cheat meal per week is perfectly fine and actually helps with diet adherence. Just make sure it stays within reason — enjoy it guilt-free, then get right back on track the next day. You are doing fantastic!',
 'responded', '2026-06-01 18:00:00');

-- ─────────────────────────────────────────────────────────────
-- 5. FOOD LOGS  (Alice=11, Bob=12, Carol=13, David=14)
--    Spread across several dates for a realistic history
-- ─────────────────────────────────────────────────────────────

-- Alice (patient 11) – food logs
INSERT INTO `food_logs` (`log_id`,`user_id`,`food_id`,`quantity_g`,`calories_consumed`,`logged_at`) VALUES
-- June 13 (today)
(1,  11, 5,  150, 106.5, '2026-06-13 07:30:00'),  -- Oatmeal
(2,  11, 56, 60,  93.0,  '2026-06-13 07:35:00'),  -- Egg boiled
(3,  11, 77, 120, 106.8, '2026-06-13 08:00:00'),  -- Banana
(4,  11, 26, 150, 247.5, '2026-06-13 13:00:00'),  -- Chicken breast
(5,  11, 2,  200, 222.0, '2026-06-13 13:10:00'),  -- Brown rice
(6,  11, 108,80,  18.4,  '2026-06-13 13:15:00'),  -- Spinach
(7,  11, 41, 120, 249.6, '2026-06-13 19:30:00'),  -- Salmon
(8,  11, 111,150, 135.0, '2026-06-13 19:35:00'),  -- Sweet potato
-- June 12
(9,  11, 5,  150, 106.5, '2026-06-12 07:30:00'),
(10, 11, 56, 60,  93.0,  '2026-06-12 07:40:00'),
(11, 11, 76, 150, 78.0,  '2026-06-12 12:00:00'),  -- Apple
(12, 11, 26, 130, 214.5, '2026-06-12 13:00:00'),
(13, 11, 9,  180, 216.0, '2026-06-12 13:10:00'),  -- Quinoa
(14, 11, 67, 150, 88.5,  '2026-06-12 20:00:00'),  -- Greek yogurt
-- June 11
(15, 11, 4,  60,  148.2, '2026-06-11 07:30:00'),  -- Whole wheat bread
(16, 11, 162,20,  117.6, '2026-06-11 07:35:00'),  -- Peanut butter
(17, 11, 26, 150, 247.5, '2026-06-11 12:30:00'),
(18, 11, 116,100, 16.0,  '2026-06-11 12:35:00'),  -- Cucumber
(19, 11, 41, 120, 249.6, '2026-06-11 19:30:00'),
(20, 11, 106,150, 51.0,  '2026-06-11 19:35:00');  -- Broccoli

-- Bob (patient 12) – food logs
INSERT INTO `food_logs` (`log_id`,`user_id`,`food_id`,`quantity_g`,`calories_consumed`,`logged_at`) VALUES
-- June 13
(21, 12, 56, 150, 232.5, '2026-06-13 07:00:00'),  -- Boiled eggs (3)
(22, 12, 4,  90,  222.3, '2026-06-13 07:05:00'),  -- Whole wheat toast
(23, 12, 97, 100, 160.0, '2026-06-13 07:10:00'),  -- Avocado
(24, 12, 177,300, 264.0, '2026-06-13 07:20:00'),  -- Protein shake
(25, 12, 30, 200, 542.0, '2026-06-13 13:00:00'),  -- Beef steak
(26, 12, 9,  200, 240.0, '2026-06-13 13:10:00'),  -- Quinoa
(27, 12, 106,100, 34.0,  '2026-06-13 13:15:00'),  -- Broccoli
(28, 12, 27, 150, 313.5, '2026-06-13 19:30:00'),  -- Chicken thigh
(29, 12, 7,  200, 262.0, '2026-06-13 19:35:00'),  -- Pasta
-- June 12
(30, 12, 57, 150, 294.0, '2026-06-12 06:45:00'),  -- Fried eggs
(31, 12, 3,  90,  238.5, '2026-06-12 06:50:00'),  -- White bread
(32, 12, 151,50,  283.5, '2026-06-12 06:55:00'),  -- Peanuts
(33, 12, 30, 220, 596.2, '2026-06-12 13:00:00'),
(34, 12, 1,  200, 260.0, '2026-06-12 13:10:00'),  -- White rice
(35, 12, 27, 200, 418.0, '2026-06-12 19:30:00'),
(36, 12, 7,  180, 235.8, '2026-06-12 19:35:00'),
-- June 11
(37, 12, 56, 150, 232.5, '2026-06-11 07:00:00'),
(38, 12, 4,  80,  197.6, '2026-06-11 07:05:00'),
(39, 12, 177,300, 264.0, '2026-06-11 07:20:00'),
(40, 12, 30, 200, 542.0, '2026-06-11 12:30:00'),
(41, 12, 9,  200, 240.0, '2026-06-11 12:40:00'),
(42, 12, 26, 150, 247.5, '2026-06-11 19:30:00'),
(43, 12, 64, 30,  120.6, '2026-06-11 19:35:00');  -- Cheddar

-- Carol (patient 13) – food logs
INSERT INTO `food_logs` (`log_id`,`user_id`,`food_id`,`quantity_g`,`calories_consumed`,`logged_at`) VALUES
-- June 13
(44, 13, 4,  60,  148.2, '2026-06-13 08:00:00'),  -- Whole wheat bread
(45, 13, 162,20,  117.6, '2026-06-13 08:05:00'),  -- Peanut butter
(46, 13, 77, 100, 89.0,  '2026-06-13 08:10:00'),  -- Banana
(47, 13, 140,200, 232.0, '2026-06-13 13:00:00'),  -- Lentils
(48, 13, 15, 80,  220.0, '2026-06-13 13:05:00'),  -- Pita bread
(49, 13, 116,100, 16.0,  '2026-06-13 13:10:00'),  -- Cucumber
(50, 13, 48, 120, 154.8, '2026-06-13 19:30:00'),  -- Tilapia
(51, 13, 2,  100, 111.0, '2026-06-13 19:35:00'),  -- Brown rice
-- June 12
(52, 13, 5,  120, 85.2,  '2026-06-12 08:00:00'),
(53, 13, 82, 100, 32.0,  '2026-06-12 08:10:00'),  -- Strawberry
(54, 13, 140,180, 208.8, '2026-06-12 13:00:00'),
(55, 13, 108,100, 23.0,  '2026-06-12 13:10:00'),  -- Spinach
(56, 13, 67, 120, 70.8,  '2026-06-12 19:30:00'),
-- June 11
(57, 13, 4,  60,  148.2, '2026-06-11 08:00:00'),
(58, 13, 61, 200, 70.0,  '2026-06-11 08:10:00'),  -- Skimmed milk
(59, 13, 141,150, 246.0, '2026-06-11 13:00:00'),  -- Chickpeas
(60, 13, 109,150, 27.0,  '2026-06-11 13:10:00'),  -- Tomato
(61, 13, 48, 120, 154.8, '2026-06-11 19:30:00'),
(62, 13, 108,100, 23.0,  '2026-06-11 19:35:00');

-- David (patient 14) – food logs
INSERT INTO `food_logs` (`log_id`,`user_id`,`food_id`,`quantity_g`,`calories_consumed`,`logged_at`) VALUES
-- June 13
(63, 14, 56, 100, 155.0, '2026-06-13 07:30:00'),  -- Boiled egg
(64, 14, 13, 60,  155.4, '2026-06-13 07:35:00'),  -- Rye bread
(65, 14, 116,100, 16.0,  '2026-06-13 07:40:00'),  -- Cucumber
(66, 14, 85, 100, 61.0,  '2026-06-13 07:45:00'),  -- Kiwi
(67, 14, 34, 150, 202.5, '2026-06-13 13:00:00'),  -- Turkey breast
(68, 14, 199,200, 100.0, '2026-06-13 13:10:00'),  -- Veg soup
(69, 14, 91, 150, 85.5,  '2026-06-13 13:15:00'),  -- Pear
(70, 14, 44, 150, 157.5, '2026-06-13 19:30:00'),  -- Cod
(71, 14, 111,150, 135.0, '2026-06-13 19:35:00'),  -- Sweet potato
(72, 14, 207,50,  83.0,  '2026-06-13 19:40:00'),  -- Hummus
-- June 12
(73, 14, 56, 100, 155.0, '2026-06-12 07:30:00'),
(74, 14, 13, 60,  155.4, '2026-06-12 07:35:00'),
(75, 14, 76, 150, 78.0,  '2026-06-12 12:30:00'),  -- Apple
(76, 14, 42, 150, 163.5, '2026-06-12 13:00:00'),  -- Tuna
(77, 14, 4,  60,  148.2, '2026-06-12 13:05:00'),
(78, 14, 108,100, 23.0,  '2026-06-12 13:10:00'),
(79, 14, 44, 150, 157.5, '2026-06-12 19:30:00'),
(80, 14, 2,  150, 166.5, '2026-06-12 19:35:00'),
-- June 11
(81, 14, 56, 100, 155.0, '2026-06-11 07:30:00'),
(82, 14, 14, 60,  173.4, '2026-06-11 07:35:00'),  -- Sourdough bread
(83, 14, 85, 100, 61.0,  '2026-06-11 07:45:00'),
(84, 14, 34, 150, 202.5, '2026-06-11 13:00:00'),
(85, 14, 199,200, 100.0, '2026-06-11 13:10:00'),
(86, 14, 44, 150, 157.5, '2026-06-11 19:30:00'),
(87, 14, 111,150, 135.0, '2026-06-11 19:35:00');

SET FOREIGN_KEY_CHECKS = 1;
