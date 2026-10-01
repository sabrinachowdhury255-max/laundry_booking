<?php

class GradientBoostingPredictor
{
    private array $model;

    private array $featureNames = [
        "categorical__resource_id_DR1",
        "categorical__resource_id_DR2",
        "categorical__resource_id_WM1",
        "categorical__resource_id_WM2",
        "categorical__resource_id_WM3",
        "categorical__resource_id_WM4",

        "categorical__resource_type_Dryer",
        "categorical__resource_type_Washing Machine",

        "categorical__day_of_week_Friday",
        "categorical__day_of_week_Monday",
        "categorical__day_of_week_Saturday",
        "categorical__day_of_week_Sunday",
        "categorical__day_of_week_Thursday",
        "categorical__day_of_week_Tuesday",
        "categorical__day_of_week_Wednesday",

        "numerical__hour",
        "numerical__is_weekend",
        "numerical__previous_week_booking",
        "numerical__previous_2_weeks_booking",
        "numerical__previous_3_weeks_booking",
        "numerical__previous_3_weeks_average"
    ];

    public function __construct(string $modelPath)
    {
        if (!file_exists($modelPath)) {
            throw new Exception(
                "ML model file not found: " . $modelPath
            );
        }

        $json = file_get_contents($modelPath);

        if ($json === false) {
            throw new Exception(
                "Could not read the ML model file."
            );
        }

        $model = json_decode($json, true);

        if (!is_array($model)) {
            throw new Exception(
                "Invalid ML model JSON."
            );
        }

        if (
            !isset($model["trees"]) ||
            !isset($model["learning_rate"]) ||
            !isset($model["init_prediction"])
        ) {
            throw new Exception(
                "The ML model JSON is missing required information."
            );
        }

        $this->model = $model;
    }

    /**
     * Create the same 21-feature vector
     * used by the trained Kaggle model.
     */
    private function createFeatureVector(
        string $resourceId,
        string $resourceType,
        string $dayOfWeek,
        int $hour,
        int $isWeekend,
        float $previousWeekBooking,
        float $previous2WeeksBooking,
        float $previous3WeeksBooking,
        float $previous3WeekAverage
    ): array {

        $features = [];

        /*
         * resource_id
         */

        $resourceIds = [
            "DR1",
            "DR2",
            "WM1",
            "WM2",
            "WM3",
            "WM4"
        ];

        foreach ($resourceIds as $id) {

            $features[] = (
                $resourceId === $id
            ) ? 1.0 : 0.0;
        }


        /*
         * resource_type
         */

        $resourceTypes = [
            "Dryer",
            "Washing Machine"
        ];

        foreach ($resourceTypes as $type) {

            $features[] = (
                $resourceType === $type
            ) ? 1.0 : 0.0;
        }


        /*
         * day_of_week
         */

        $days = [
            "Friday",
            "Monday",
            "Saturday",
            "Sunday",
            "Thursday",
            "Tuesday",
            "Wednesday"
        ];

        foreach ($days as $day) {

            $features[] = (
                $dayOfWeek === $day
            ) ? 1.0 : 0.0;
        }


        /*
         * Numerical features
         */

        $features[] = (float) $hour;

        $features[] = (float) $isWeekend;

        $features[] = $previousWeekBooking;

        $features[] = $previous2WeeksBooking;

        $features[] = $previous3WeeksBooking;

        $features[] = $previous3WeekAverage;


        if (count($features) !== 21) {

            throw new Exception(
                "Feature vector must contain exactly 21 features."
            );
        }

        return $features;
    }

    /**
     * Traverse one exported decision tree.
     */
    private function predictTree(
        array $tree,
        array $features
    ): float {

        $nodes = $tree["nodes"];

        $nodeId = 0;

        while (true) {

            if (!isset($nodes[$nodeId])) {

                throw new Exception(
                    "Invalid tree node: " . $nodeId
                );
            }

            $node = $nodes[$nodeId];

            $featureIndex = (int) $node["feature"];

            /*
             * feature = -2 means this is a leaf.
             */
            if ($featureIndex < 0) {

                return (float) $node["value"];
            }

            $threshold = (float) $node["threshold"];

            $featureValue = $features[$featureIndex];

            if ($featureValue <= $threshold) {

                $nodeId = (int) $node["left"];

            } else {

                $nodeId = (int) $node["right"];
            }
        }
    }

    /**
     * Predict booking demand using the exported
     * Gradient Boosting model.
     */
    public function predict(
        string $resourceId,
        string $resourceType,
        string $dayOfWeek,
        int $hour,
        int $isWeekend,
        float $previousWeekBooking,
        float $previous2WeeksBooking,
        float $previous3WeeksBooking,
        float $previous3WeekAverage
    ): float {

        $features = $this->createFeatureVector(
            $resourceId,
            $resourceType,
            $dayOfWeek,
            $hour,
            $isWeekend,
            $previousWeekBooking,
            $previous2WeeksBooking,
            $previous3WeeksBooking,
            $previous3WeekAverage
        );

        /*
         * Gradient Boosting prediction starts
         * with the initial prediction.
         */
        $prediction = (float) $this->model["init_prediction"];

        /*
         * Add the contribution from each tree.
         */
        foreach ($this->model["trees"] as $tree) {

            $treePrediction = $this->predictTree(
                $tree,
                $features
            );

            $prediction +=
                (float) $this->model["learning_rate"]
                * $treePrediction;
        }

        return $prediction;
    }
}