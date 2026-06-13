<?php
// backend/helpers.php
// Shared nutrition calculations used by several endpoints. Centralizing this
// math keeps the dashboard, report, and dietitian views consistent.

if (!function_exists('activity_factor')) {
    /** Map a stored activity-level label to its TDEE multiplier. */
    function activity_factor(?string $activity): float
    {
        $activity = $activity ?? '';
        if (strpos($activity, 'Lightly') !== false)    return 1.375;
        if (strpos($activity, 'Moderately') !== false) return 1.55;
        if (strpos($activity, 'Very') !== false)       return 1.725;
        if (strpos($activity, 'Extra') !== false)      return 1.9;
        return 1.2; // Sedentary / default
    }
}

if (!function_exists('calculate_bmi')) {
    /**
     * Return [bmi (float|null), label (string)] for the given metrics.
     * Returns [null, 'N/A'] when height/weight are not usable.
     */
    function calculate_bmi($height_cm, $weight_kg): array
    {
        $height_cm = (float) $height_cm;
        $weight_kg = (float) $weight_kg;
        if ($height_cm <= 0 || $weight_kg <= 0) {
            return [null, 'N/A'];
        }
        $h = $height_cm / 100;
        $bmi = round($weight_kg / ($h * $h), 1);
        if ($bmi < 18.5)   $label = 'Underweight';
        elseif ($bmi < 25) $label = 'Normal weight';
        elseif ($bmi < 30) $label = 'Overweight';
        else               $label = 'Obese';
        return [$bmi, $label];
    }
}

if (!function_exists('daily_calorie_need')) {
    /**
     * Daily calorie need via Mifflin-St Jeor BMR * activity factor.
     * Returns 0 when the profile lacks the data needed to compute it.
     */
    function daily_calorie_need($height_cm, $weight_kg, $age, ?string $gender, ?string $activity): int
    {
        $height_cm = (float) $height_cm;
        $weight_kg = (float) $weight_kg;
        $age = (int) $age;
        if ($height_cm <= 0 || $weight_kg <= 0 || $age <= 0) {
            return 0;
        }
        $bmr = ($gender === 'Male')
            ? 10 * $weight_kg + 6.25 * $height_cm - 5 * $age + 5
            : 10 * $weight_kg + 6.25 * $height_cm - 5 * $age - 161;
        return (int) round($bmr * activity_factor($activity));
    }
}

if (!function_exists('serving_units')) {
    /**
     * Standard household-measurement units mapped to their gram value.
     * Used to convert a user-friendly amount into grams server-side so calorie
     * math is never trusted to the client. Values are sensible standards.
     */
    function serving_units(): array
    {
        return [
            'g'       => 1,    // grams (pass-through)
            'portion' => 150,  // a standard portion/serving
            'glass'   => 250,  // a glass (≈250 ml)
            'tbsp'    => 15,   // table-spoon
            'tsp'     => 5,    // tea-spoon
        ];
    }
}

if (!function_exists('food_categories')) {
    /** The fixed set of food categories offered in the add/edit-food dropdowns. */
    function food_categories(): array
    {
        return ['Grain', 'Protein', 'Dairy', 'Fruit', 'Vegetable', 'Legume', 'Nut',
                'Oil', 'Beverage', 'Sweet', 'Prepared', 'Condiment', 'General'];
    }
}

if (!function_exists('serving_to_grams')) {
    /**
     * Convert an amount in the given unit to grams using serving_units().
     * Unknown units fall back to grams. Returns a float >= 0.
     */
    function serving_to_grams(string $unit, float $amount): float
    {
        $units = serving_units();
        $factor = $units[$unit] ?? 1;
        return max(0.0, $amount * $factor);
    }
}

if (!function_exists('log_activity')) {
    /**
     * Record a meaningful action in activity_log for the admin Activity Monitor.
     * Deliberately NOT used for individual food/water log entries (too noisy).
     * Failures are swallowed (logged) so they never break the primary action.
     */
    function log_activity(mysqli $conn, ?int $userId, string $role, string $action, ?string $detail = null): void
    {
        try {
            $stmt = $conn->prepare("INSERT INTO activity_log (user_id, actor_role, action, detail) VALUES (?, ?, ?, ?)");
            if (!$stmt) { return; }
            $stmt->bind_param('isss', $userId, $role, $action, $detail);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            error_log('log_activity failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('day_bounds')) {
    /**
     * Return [start, endExclusive] datetime strings for a Y-m-d date, for use in
     * sargable range queries: logged_at >= start AND logged_at < endExclusive.
     */
    function day_bounds(string $date): array
    {
        $start = $date . ' 00:00:00';
        $end = date('Y-m-d 00:00:00', strtotime($date . ' +1 day'));
        return [$start, $end];
    }
}
