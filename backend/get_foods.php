<?php
// backend/get_foods.php
// Returns all foods from the database for the food log dropdown

require_once __DIR__ . '/auth.php';

require_login();

$result = $conn->query("SELECT food_id, name, calories_per_100g, category FROM foods ORDER BY category, name");

$foods = [];
while ($row = $result->fetch_assoc()) {
    $foods[] = $row;
}

json_response(['success' => true, 'foods' => $foods]);
