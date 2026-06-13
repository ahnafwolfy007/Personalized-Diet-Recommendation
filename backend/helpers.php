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
