<?php
// frontend/partials/category_options.php
// Emits <option> tags for the food-category dropdowns (add/edit food), kept in
// sync with backend/helpers.php food_categories(). Include inside a <select>.
require_once __DIR__ . '/../../backend/helpers.php';
foreach (food_categories() as $cat) {
    echo '<option value="' . htmlspecialchars($cat, ENT_QUOTES) . '">' . htmlspecialchars($cat) . '</option>';
}
