
<?php

/* LaundryWise file overview: Development/testing page for checking the ML predictor and its outputs. It should not be treated as a resident-facing production page. */


require_once __DIR__ . "/ml/GradientBoostingPredictor.php";

try {

    // Load the exported Gradient Boosting model
    $modelPath = __DIR__ . "/ml/gradient_boosting_model_php.json";

    if (!file_exists($modelPath)) {
        throw new Exception("Model JSON file not found: " . $modelPath);
    }

    $predictor = new GradientBoostingPredictor($modelPath);

    // Test input: DR1, Saturday, 14:00
    $resourceId = "DR1";
    $resourceType = "Dryer";
    $dayOfWeek = "Saturday";

    $hour = 14;
    $isWeekend = 1;

    // Historical booking features
    $previousWeekBooking = 3.0;
    $previous2WeeksBooking = 3.0;
    $previous3WeeksBooking = 3.0;
    $previous3WeeksAverage = 3.0;

    // Generate prediction
    $prediction = $predictor->predict(
        $resourceId,
        $resourceType,
        $dayOfWeek,
        $hour,
        $isWeekend,
        $previousWeekBooking,
        $previous2WeeksBooking,
        $previous3WeeksBooking,
        $previous3WeeksAverage
    );

    // Display results
    echo "<h1>Gradient Boosting Prediction Test</h1>";

    echo "<h2>Test Input</h2>";

    echo "<p>Resource ID: " . htmlspecialchars($resourceId) . "</p>";
    echo "<p>Resource Type: " . htmlspecialchars($resourceType) . "</p>";
    echo "<p>Day: " . htmlspecialchars($dayOfWeek) . "</p>";
    echo "<p>Hour: " . htmlspecialchars((string)$hour) . ":00</p>";
    echo "<p>Weekend: " . $isWeekend . "</p>";

    echo "<p>Previous Week: " . $previousWeekBooking . "</p>";
    echo "<p>Previous 2 Weeks: " . $previous2WeeksBooking . "</p>";
    echo "<p>Previous 3 Weeks: " . $previous3WeeksBooking . "</p>";
    echo "<p>Previous 3-Week Average: " . $previous3WeeksAverage . "</p>";

    echo "<h2>Prediction Result</h2>";

    echo "<p><strong>Predicted Demand: "
        . number_format((float)$prediction, 6)
        . "</strong></p>";

} catch (Throwable $e) {

    echo "<h1>Prediction Test Error</h1>";

    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
