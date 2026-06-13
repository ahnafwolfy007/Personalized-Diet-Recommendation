-- ============================================================
-- DietSync — Authoritative Database Schema
-- Single source of truth. Run this once on a fresh database.
-- (For an existing database, apply database/migration.sql instead.)
-- ============================================================

CREATE DATABASE IF NOT EXISTS dietsync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dietsync;

-- ------------------------------------------------------------
-- users : patients, dietitians and admins
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id               INT AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(100) NOT NULL,
    email                 VARCHAR(150) NOT NULL UNIQUE,
    password              VARCHAR(255) NOT NULL,
    role                  ENUM('patient','dietitian','admin') NOT NULL DEFAULT 'patient',
    age                   INT          DEFAULT NULL,
    gender                ENUM('Male','Female','Other') DEFAULT NULL,
    height_cm             FLOAT        DEFAULT NULL,
    weight_kg             FLOAT        DEFAULT NULL,
    activity_level        VARCHAR(60)  DEFAULT NULL,
    status                ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_dietitian_id INT          DEFAULT NULL,
    -- Dietitian-only professional details (NULL for patients/admins).
    works_at              VARCHAR(150) DEFAULT NULL,
    experience_years      INT          DEFAULT NULL,
    specialization        VARCHAR(150) DEFAULT NULL,
    bio                   TEXT         DEFAULT NULL,
    KEY idx_users_role (role),
    KEY idx_users_assigned_dietitian (assigned_dietitian_id),
    CONSTRAINT fk_users_assigned_dietitian
        FOREIGN KEY (assigned_dietitian_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- foods : reference food items with calories per 100g
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS foods (
    food_id           INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(150) NOT NULL,
    calories_per_100g FLOAT        NOT NULL,
    category          VARCHAR(50)  NOT NULL DEFAULT 'General',
    -- User contributions: created_by set when a user adds a food; is_verified=0
    -- until an admin reviews it. Seeded reference foods are verified by default.
    created_by        INT          DEFAULT NULL,
    is_verified       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_foods_category_name (category, name),
    KEY idx_foods_verified (is_verified),
    CONSTRAINT fk_foods_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- food_logs : each food a user logs
-- Composite (user_id, logged_at) index serves the daily range queries.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_logs (
    log_id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT   NOT NULL,
    food_id           INT   NOT NULL,
    quantity_g        FLOAT NOT NULL,
    calories_consumed FLOAT NOT NULL,
    -- Measurement metadata: the unit/amount the user picked (Portion, Glass, etc).
    -- quantity_g remains the resolved grams that calories are computed from.
    serving_unit      VARCHAR(20) NOT NULL DEFAULT 'g',
    serving_amount    FLOAT       DEFAULT NULL,
    logged_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_food_logs_user_logged (user_id, logged_at),
    KEY idx_food_logs_food (food_id),
    CONSTRAINT fk_food_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_food_logs_food FOREIGN KEY (food_id) REFERENCES foods(food_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- diet_plans : dietitian-authored plans (one per patient/dietitian pair)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS diet_plans (
    plan_id        INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT NOT NULL,
    dietitian_id   INT NOT NULL,
    breakfast_text TEXT,
    lunch_text     TEXT,
    dinner_text    TEXT,
    notes          TEXT,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_plan_patient_dietitian (patient_id, dietitian_id),
    KEY idx_diet_plans_dietitian (dietitian_id),
    CONSTRAINT fk_diet_plans_patient   FOREIGN KEY (patient_id)   REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_diet_plans_dietitian FOREIGN KEY (dietitian_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- feedbacks : patient messages and dietitian responses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedbacks (
    feedback_id  INT AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT NOT NULL,
    dietitian_id INT NOT NULL,
    message      TEXT NOT NULL,
    response     TEXT DEFAULT NULL,
    status       ENUM('pending','responded') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_feedbacks_dietitian_status (dietitian_id, status),
    KEY idx_feedbacks_patient (patient_id),
    CONSTRAINT fk_feedbacks_patient   FOREIGN KEY (patient_id)   REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedbacks_dietitian FOREIGN KEY (dietitian_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- dietitian_requests : patient -> dietitian assignment requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dietitian_requests (
    request_id   INT AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT NOT NULL,
    dietitian_id INT NOT NULL,
    status       ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_requests_patient_status (patient_id, status),
    KEY idx_requests_dietitian_status (dietitian_id, status),
    CONSTRAINT fk_requests_patient   FOREIGN KEY (patient_id)   REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_requests_dietitian FOREIGN KEY (dietitian_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- diet_plan_items : database-driven meal plan rows (food + amount)
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
-- meal_completions : a patient's "Taken/Done" tick for a plan item on a day
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meal_completions (
    completion_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id    INT  NOT NULL,
    item_id       INT  NOT NULL,
    completed_on  DATE NOT NULL,
    log_id        INT  NULL,
    UNIQUE KEY uniq_completion_item_day (item_id, completed_on),
    KEY idx_completions_patient_day (patient_id, completed_on),
    CONSTRAINT fk_completions_patient FOREIGN KEY (patient_id) REFERENCES users(user_id)           ON DELETE CASCADE,
    CONSTRAINT fk_completions_item    FOREIGN KEY (item_id)    REFERENCES diet_plan_items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_completions_log     FOREIGN KEY (log_id)     REFERENCES food_logs(log_id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- water_logs : daily water intake entries
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
-- assignment_removals : rationale recorded when a patient/dietitian unassigns
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
-- activity_log : admin Activity Monitor feed.
-- Records meaningful actions (login, requests, plans, feedback, food add/verify,
-- unassign). Individual food/water log entries are intentionally NOT recorded.
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

-- ============================================================
-- SEED: default accounts (all default passwords = "password")
-- ============================================================
INSERT INTO users (name, email, password, role, status, works_at, experience_years, specialization, bio) VALUES
('Admin',             'admin@dietsync.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',     'active', NULL, NULL, NULL, NULL),
('Dr. Sarah Johnson', 'sarah@dietsync.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dietitian', 'active', 'City Health Clinic', 8,  'Weight management & sports nutrition', 'Registered dietitian focused on sustainable, evidence-based eating habits.'),
('Dr. Michael Chen',  'michael@dietsync.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dietitian', 'active', 'Wellness Partners',  12, 'Clinical & diabetic nutrition',        'Clinical dietitian helping patients manage chronic conditions through diet.');

INSERT INTO users (name, email, password, role, status, age, gender, height_cm, weight_kg, activity_level) VALUES
('John Doe', 'john@dietsync.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'active', 28, 'Male', 175, 70, 'Moderately Active (3-5 days/week)');

-- ============================================================
-- SEED: foods
-- ============================================================
INSERT INTO foods (name, calories_per_100g, category) VALUES
-- GRAINS & CEREALS
('White Rice (cooked)',          130,  'Grain'),
('Brown Rice (cooked)',          111,  'Grain'),
('White Bread',                  265,  'Grain'),
('Whole Wheat Bread',            247,  'Grain'),
('Oatmeal (cooked)',             71,   'Grain'),
('Rolled Oats (dry)',            389,  'Grain'),
('Pasta (cooked)',               131,  'Grain'),
('Spaghetti (cooked)',           130,  'Grain'),
('Quinoa (cooked)',              120,  'Grain'),
('Barley (cooked)',              123,  'Grain'),
('Cornflakes',                   357,  'Grain'),
('Muesli',                       363,  'Grain'),
('Rye Bread',                    259,  'Grain'),
('Sourdough Bread',              289,  'Grain'),
('Pita Bread',                   275,  'Grain'),
('Chapati / Roti',               297,  'Grain'),
('Naan Bread',                   317,  'Grain'),
('Rice Cake',                    392,  'Grain'),
('Popcorn (plain)',              375,  'Grain'),
('Wheat Flour',                  364,  'Grain'),
('Semolina (dry)',               360,  'Grain'),
('Buckwheat (cooked)',           92,   'Grain'),
('Millet (cooked)',              119,  'Grain'),
('Couscous (cooked)',            112,  'Grain'),
('Tortilla (flour)',             312,  'Grain'),

-- PROTEINS – MEAT
('Chicken Breast (cooked)',      165,  'Protein'),
('Chicken Thigh (cooked)',       209,  'Protein'),
('Chicken Drumstick (cooked)',   172,  'Protein'),
('Ground Beef (cooked)',         254,  'Protein'),
('Beef Steak (grilled)',         271,  'Protein'),
('Pork Chop (cooked)',           231,  'Protein'),
('Lamb (cooked)',                258,  'Protein'),
('Duck (cooked)',                337,  'Protein'),
('Turkey Breast (cooked)',       135,  'Protein'),
('Beef Liver',                   175,  'Protein'),
('Sausage (pork)',               301,  'Protein'),
('Hot Dog',                      290,  'Protein'),
('Bacon (cooked)',               541,  'Protein'),
('Ham (cured)',                  145,  'Protein'),
('Salami',                       336,  'Protein'),

-- PROTEINS – SEAFOOD
('Salmon (cooked)',              208,  'Protein'),
('Tuna (canned in water)',       109,  'Protein'),
('Tuna (canned in oil)',         198,  'Protein'),
('Cod (cooked)',                 105,  'Protein'),
('Shrimp (cooked)',              99,   'Protein'),
('Crab (cooked)',                97,   'Protein'),
('Lobster (cooked)',             98,   'Protein'),
('Tilapia (cooked)',             129,  'Protein'),
('Sardine (canned)',             208,  'Protein'),
('Mackerel (cooked)',            262,  'Protein'),
('Trout (cooked)',               190,  'Protein'),
('Halibut (cooked)',             140,  'Protein'),
('Prawn (cooked)',               99,   'Protein'),
('Squid (cooked)',               175,  'Protein'),
('Mussels (cooked)',             172,  'Protein'),

-- EGGS & DAIRY
('Egg (boiled)',                 155,  'Protein'),
('Egg (fried)',                  196,  'Protein'),
('Egg White',                    52,   'Protein'),
('Egg Yolk',                     322,  'Protein'),
('Whole Milk',                   61,   'Dairy'),
('Skimmed Milk',                 35,   'Dairy'),
('Soy Milk',                     54,   'Dairy'),
('Almond Milk (unsweetened)',    17,   'Dairy'),
('Cheddar Cheese',               402,  'Dairy'),
('Mozzarella Cheese',            280,  'Dairy'),
('Cottage Cheese (low-fat)',     72,   'Dairy'),
('Greek Yogurt (plain)',         59,   'Dairy'),
('Regular Yogurt (plain)',       61,   'Dairy'),
('Butter',                       717,  'Dairy'),
('Cream Cheese',                 342,  'Dairy'),
('Parmesan Cheese',              431,  'Dairy'),
('Feta Cheese',                  264,  'Dairy'),
('Whipping Cream',               345,  'Dairy'),
('Ice Cream (vanilla)',          207,  'Dairy'),
('Sour Cream',                   198,  'Dairy'),

-- FRUITS
('Apple',                        52,   'Fruit'),
('Banana',                       89,   'Fruit'),
('Orange',                       47,   'Fruit'),
('Mango',                        60,   'Fruit'),
('Grapes',                       69,   'Fruit'),
('Watermelon',                   30,   'Fruit'),
('Strawberry',                   32,   'Fruit'),
('Blueberry',                    57,   'Fruit'),
('Raspberry',                    52,   'Fruit'),
('Kiwi',                         61,   'Fruit'),
('Pineapple',                    50,   'Fruit'),
('Papaya',                       43,   'Fruit'),
('Peach',                        39,   'Fruit'),
('Plum',                         46,   'Fruit'),
('Cherry',                       50,   'Fruit'),
('Pear',                         57,   'Fruit'),
('Lemon',                        29,   'Fruit'),
('Lime',                         30,   'Fruit'),
('Guava',                        68,   'Fruit'),
('Lychee',                       66,   'Fruit'),
('Pomegranate',                  83,   'Fruit'),
('Avocado',                      160,  'Fruit'),
('Coconut (fresh)',              354,  'Fruit'),
('Fig (fresh)',                  74,   'Fruit'),
('Date',                         277,  'Fruit'),
('Apricot',                      48,   'Fruit'),
('Melon (cantaloupe)',           34,   'Fruit'),
('Grapefruit',                   42,   'Fruit'),
('Passion Fruit',                97,   'Fruit'),
('Jackfruit',                    95,   'Fruit'),

-- VEGETABLES
('Broccoli',                     34,   'Vegetable'),
('Carrot',                       41,   'Vegetable'),
('Spinach',                      23,   'Vegetable'),
('Tomato',                       18,   'Vegetable'),
('Potato (boiled)',              87,   'Vegetable'),
('Sweet Potato (baked)',         90,   'Vegetable'),
('Onion',                        40,   'Vegetable'),
('Garlic',                       149,  'Vegetable'),
('Cabbage',                      25,   'Vegetable'),
('Lettuce',                      15,   'Vegetable'),
('Cucumber',                     16,   'Vegetable'),
('Bell Pepper (red)',            31,   'Vegetable'),
('Bell Pepper (green)',          20,   'Vegetable'),
('Cauliflower',                  25,   'Vegetable'),
('Peas (green)',                 81,   'Vegetable'),
('Corn (sweet)',                 86,   'Vegetable'),
('Mushroom (button)',            22,   'Vegetable'),
('Zucchini',                     17,   'Vegetable'),
('Eggplant',                     25,   'Vegetable'),
('Celery',                       16,   'Vegetable'),
('Asparagus',                    20,   'Vegetable'),
('Green Beans',                  31,   'Vegetable'),
('Kale',                         49,   'Vegetable'),
('Beets',                        43,   'Vegetable'),
('Radish',                       16,   'Vegetable'),
('Turnip',                       28,   'Vegetable'),
('Leek',                         61,   'Vegetable'),
('Brussels Sprouts',             43,   'Vegetable'),
('Artichoke',                    53,   'Vegetable'),
('Bok Choy',                     13,   'Vegetable'),
('Pumpkin',                      26,   'Vegetable'),
('Bitter Gourd',                 17,   'Vegetable'),
('Okra',                         33,   'Vegetable'),
('Chili Pepper (red)',           40,   'Vegetable'),

-- LEGUMES & BEANS
('Lentils (cooked)',             116,  'Legume'),
('Chickpeas (cooked)',           164,  'Legume'),
('Black Beans (cooked)',        132,  'Legume'),
('Kidney Beans (cooked)',        127,  'Legume'),
('Green Lentils (cooked)',      116,  'Legume'),
('Soybeans (cooked)',           173,  'Legume'),
('Tofu (firm)',                  144,  'Legume'),
('Edamame',                      121,  'Legume'),
('Split Peas (cooked)',          118,  'Legume'),
('Navy Beans (cooked)',         130,  'Legume'),

-- NUTS & SEEDS
('Almonds',                      579,  'Nut'),
('Peanuts',                      567,  'Nut'),
('Cashews',                      553,  'Nut'),
('Walnuts',                      654,  'Nut'),
('Pistachios',                   562,  'Nut'),
('Hazelnuts',                    628,  'Nut'),
('Macadamia Nuts',               718,  'Nut'),
('Sunflower Seeds',              584,  'Nut'),
('Pumpkin Seeds',                559,  'Nut'),
('Chia Seeds',                   486,  'Nut'),
('Flaxseeds',                    534,  'Nut'),
('Sesame Seeds',                 573,  'Nut'),
('Peanut Butter',                588,  'Nut'),
('Almond Butter',                614,  'Nut'),

-- OILS & FATS
('Olive Oil',                    884,  'Oil'),
('Coconut Oil',                  892,  'Oil'),
('Vegetable Oil',                884,  'Oil'),
('Canola Oil',                   884,  'Oil'),
('Ghee',                         876,  'Oil'),
('Margarine',                    719,  'Oil'),

-- BEVERAGES
('Orange Juice',                 45,   'Beverage'),
('Apple Juice',                  46,   'Beverage'),
('Mango Juice',                  60,   'Beverage'),
('Coconut Water',                19,   'Beverage'),
('Green Tea (unsweetened)',       1,    'Beverage'),
('Black Coffee',                  2,    'Beverage'),
('Whole Milk Latte',             67,   'Beverage'),
('Protein Shake (mixed)',        88,   'Beverage'),

-- SWEETS & SNACKS
('Dark Chocolate (70%)',         598,  'Sweet'),
('Milk Chocolate',               535,  'Sweet'),
('Honey',                        304,  'Sweet'),
('Jam (strawberry)',             278,  'Sweet'),
('Sugar (white)',                387,  'Sweet'),
('Brown Sugar',                  380,  'Sweet'),
('Biscuit (plain)',              457,  'Sweet'),
('Crackers (salted)',            421,  'Sweet'),
('Potato Chips',                 536,  'Sweet'),
('Pretzel',                      380,  'Sweet'),
('Granola Bar',                  471,  'Sweet'),
('Donut (glazed)',               452,  'Sweet'),
('Croissant',                    406,  'Sweet'),
('Muffin (blueberry)',           377,  'Sweet'),
('Pancake',                      227,  'Sweet'),
('Waffle',                       291,  'Sweet'),
('Pizza (cheese)',               266,  'Prepared'),
('French Fries',                 312,  'Prepared'),
('Burger (beef patty only)',     295,  'Prepared'),
('Hot Chocolate',                71,   'Beverage'),

-- PREPARED / MIXED DISHES
('Omelette (2 eggs)',            154,  'Prepared'),
('Vegetable Soup',               50,   'Prepared'),
('Chicken Soup',                 72,   'Prepared'),
('Dal (lentil soup)',            93,   'Prepared'),
('Biryani (chicken)',            162,  'Prepared'),
('Fried Rice',                   163,  'Prepared'),
('Vegetable Stir Fry',          80,   'Prepared'),
('Caesar Salad (no dressing)',   15,   'Prepared'),
('Greek Salad',                  96,   'Prepared'),
('Hummus',                       166,  'Prepared'),
('Guacamole',                    150,  'Prepared'),
('Salsa',                        36,   'Prepared'),
('Tzatziki',                     54,   'Prepared'),
('Sushi Rice (per 100g)',        130,  'Prepared'),
('Miso Soup',                    40,   'Prepared'),

-- FISH & SEAFOOD (additional)
('Fish Fingers (fried)',         225,  'Protein'),
('Fish Cake',                    175,  'Protein'),
('Smoked Salmon',                179,  'Protein'),
('Anchovies (canned)',           210,  'Protein'),
('Oysters (raw)',                69,   'Protein'),

-- ADDITIONAL GRAINS
('Bagel',                        245,  'Grain'),
('Crumpet',                      198,  'Grain'),
('Wafer Biscuit',                499,  'Grain'),
('Rice Noodles (cooked)',        109,  'Grain'),
('Egg Noodles (cooked)',         138,  'Grain'),
('Udon Noodles (cooked)',        124,  'Grain'),

-- MORE DAIRY
('Kefir',                        52,   'Dairy'),
('Brie Cheese',                  334,  'Dairy'),
('Gouda Cheese',                 356,  'Dairy'),
('Ricotta Cheese',               174,  'Dairy'),
('Condensed Milk',               321,  'Dairy'),
('Evaporated Milk',              135,  'Dairy'),

-- CONDIMENTS
('Ketchup',                      112,  'Condiment'),
('Mustard',                      66,   'Condiment'),
('Mayonnaise',                   680,  'Condiment'),
('Soy Sauce',                    60,   'Condiment'),
('Vinegar',                      21,   'Condiment'),
('Tabasco Sauce',                12,   'Condiment'),
('Worcestershire Sauce',        78,   'Condiment'),
('Barbecue Sauce',               172,  'Condiment'),
('Ranch Dressing',               145,  'Condiment'),
('Olive Tapenade',               168,  'Condiment');
