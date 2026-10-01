<?php

require_once "ml/GradientBoostingPredictor.php";

try {

    $modelPath = __DIR__ . "/ml/gradient_boosting_model_php.json";

    $predictor = new GradientBoostingPredictor(
        $modelPath
    );

    /*
     * Test input
     */
    $resourceId = "WM1";
    $resourceType = "Washing Machine";
    $dayOfWeek = "Friday";

    $hour = 18;
    $isWeekend = 0;

    $previousWeekBooking = 3.0;
    $previous2WeeksBooking = 3.0;
    $previous3WeeksBooking = 3.0;
    $previous3WeeksAverage = 3.0;

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

    echo "<h1>ML Prediction Test</h1>";

    echo "<p><strong>Resource:</strong> "
        . htmlspecialchars($resourceId)
        . "</p>";

    echo "<p><strong>Type:</strong> "
        . htmlspecialchars($resourceType)
        . "</p>";

    echo "<p><strong>Day:</strong> "
        . htmlspecialchars($dayOfWeek)
        . "</p>";

    echo "<p><strong>Hour:</strong> "
        . htmlspecialchars($hour)
        . ":00</p>";

    echo "<p><strong>Predicted Demand:</strong> "
        . number_format($prediction, 6)
        . "</p>";

} catch (Throwable $e) {

    echo "<h1>ML Test Error</h1>";

    echo "<p>"
        . htmlspecialchars($e->getMessage())
        . "</p>";
}