<?php

session_start();

require_once "config/database.php";
require_once "ml/GradientBoostingPredictor.php";


/*
|--------------------------------------------------------------------------
| Login protection
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Get requested booking information
|--------------------------------------------------------------------------
*/

$machine_id = isset($_GET["machine_id"])
    ? intval($_GET["machine_id"])
    : 0;

$date = $_GET["date"] ?? '';

$start_time = $_GET["start_time"] ?? '';

$end_time = $_GET["end_time"] ?? '';


/*
|--------------------------------------------------------------------------
| Basic validation
|--------------------------------------------------------------------------
*/

if (
    !$machine_id ||
    !$date ||
    !$start_time ||
    !$end_time
) {
    die("Invalid recommendation request.");
}


/*
|--------------------------------------------------------------------------
| Validate date and time
|--------------------------------------------------------------------------
*/

$date_check = DateTime::createFromFormat(
    "Y-m-d",
    $date
);

$start_check = DateTime::createFromFormat(
    "H:i",
    $start_time
);

$end_check = DateTime::createFromFormat(
    "H:i",
    $end_time
);

if (
    !$date_check ||
    $date_check->format("Y-m-d") !== $date ||
    !$start_check ||
    $start_check->format("H:i") !== $start_time ||
    !$end_check ||
    $end_check->format("H:i") !== $end_time
) {
    die("Invalid date or time.");
}


/*
|--------------------------------------------------------------------------
| Get requested machine
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id,
            machine_name,
            machine_type,
            status
        FROM machines
        WHERE id = ?
        AND status = 'active'";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $machine_id
);

$stmt->execute();

$result = $stmt->get_result();

$requested_machine = $result->fetch_assoc();

if (!$requested_machine) {
    die("Machine not found.");
}


/*
|--------------------------------------------------------------------------
| Check whether a slot is booked
|--------------------------------------------------------------------------
*/

function isSlotBooked(
    $conn,
    $machine_id,
    $date,
    $start_time,
    $end_time
) {
    $sql = "SELECT id
            FROM bookings
            WHERE machine_id = ?
            AND booking_date = ?
            AND status = 'confirmed'
            AND start_time < ?
            AND end_time > ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "isss",
        $machine_id,
        $date,
        $end_time,
        $start_time
    );

    $stmt->execute();

    $result = $stmt->get_result();

    return $result->num_rows > 0;
}


/*
|--------------------------------------------------------------------------
| Make sure the requested slot is unavailable
|--------------------------------------------------------------------------
|
| Recommendations are only needed when the requested
| machine/time slot is already unavailable.
|
|--------------------------------------------------------------------------
*/

if (
    !isSlotBooked(
        $conn,
        $machine_id,
        $date,
        $start_time,
        $end_time
    )
) {
    header(
        "Location: availability.php?" .
        "machine_id=" . urlencode($machine_id) .
        "&date=" . urlencode($date)
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| Get historical booking count
|--------------------------------------------------------------------------
*/

function getHistoricalBookingCount(
    $conn,
    $machine_id,
    $date,
    $start_time,
    $end_time
) {
    $sql = "SELECT COUNT(*) AS booking_count
            FROM bookings
            WHERE machine_id = ?
            AND booking_date = ?
            AND start_time = ?
            AND end_time = ?
            AND status IN ('confirmed', 'completed')";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "isss",
        $machine_id,
        $date,
        $start_time,
        $end_time
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    return (int)$row["booking_count"];
}


/*
|--------------------------------------------------------------------------
| Convert database machine name to ML resource ID
|--------------------------------------------------------------------------
|
| Database:
|
| Machine 1 -> WM1
| Machine 2 -> WM2
| Machine 3 -> WM3
| Machine 4 -> WM4
| DR1       -> DR1
| DR2       -> DR2
|
|--------------------------------------------------------------------------
*/

function getModelResourceId($machine)
{
    /*
    |--------------------------------------------------------------------------
    | Dryers already use DR1 / DR2
    |--------------------------------------------------------------------------
    */

    if ($machine["machine_type"] === "Dryer") {
        return $machine["machine_name"];
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Machine 1-4 to WM1-4
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/Machine\s+(\d+)/',
            $machine["machine_name"],
            $matches
        )
    ) {
        return "WM" . $matches[1];
    }


    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    */

    return $machine["machine_name"];
}


/*
|--------------------------------------------------------------------------
| Load Gradient Boosting model
|--------------------------------------------------------------------------
*/

$predictor = new GradientBoostingPredictor(
    __DIR__ . "/ml/gradient_boosting_model_php.json"
);


/*
|--------------------------------------------------------------------------
| Requested slot information
|--------------------------------------------------------------------------
*/

$requested_hour = (int)substr(
    $start_time,
    0,
    2
);

$date_object = new DateTime($date);

$day_of_week = $date_object->format("l");

$is_weekend =
    (
        $day_of_week === "Saturday" ||
        $day_of_week === "Sunday"
    )
    ? 1
    : 0;


/*
|--------------------------------------------------------------------------
| Get active machines of the SAME TYPE
|--------------------------------------------------------------------------
|
| If requested machine is:
|
| Washing Machine -> only Washing Machines
| Dryer           -> only Dryers
|
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id,
            machine_name,
            machine_type,
            status
        FROM machines
        WHERE status = 'active'
        AND machine_type = ?
        ORDER BY id";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "s",
    $requested_machine["machine_type"]
);

$stmt->execute();

$result = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Store recommendation candidates
|--------------------------------------------------------------------------
*/

$recommendations = [];


/*
|--------------------------------------------------------------------------
| Generate candidates
|--------------------------------------------------------------------------
*/

while ($candidate_machine = $result->fetch_assoc()) {

    for ($hour = 8; $hour < 22; $hour++) {

        $candidate_start = sprintf(
            "%02d:00",
            $hour
        );

        $candidate_end = sprintf(
            "%02d:00",
            $hour + 1
        );


        /*
        |--------------------------------------------------------------------------
        | Do not recommend the exact requested slot
        |--------------------------------------------------------------------------
        */

        if (
            (int)$candidate_machine["id"] === $machine_id &&
            $candidate_start === $start_time
        ) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Do not recommend booked slots
        |--------------------------------------------------------------------------
        */

        if (
            isSlotBooked(
                $conn,
                $candidate_machine["id"],
                $date,
                $candidate_start,
                $candidate_end
            )
        ) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Historical dates
        |--------------------------------------------------------------------------
        */

        $previous_week_date = clone $date_object;
        $previous_week_date->modify("-7 days");

        $previous_two_week_date = clone $date_object;
        $previous_two_week_date->modify("-14 days");

        $previous_three_week_date = clone $date_object;
        $previous_three_week_date->modify("-21 days");


        /*
        |--------------------------------------------------------------------------
        | Previous week
        |--------------------------------------------------------------------------
        */

        $previous_week_booking =
            getHistoricalBookingCount(
                $conn,
                $candidate_machine["id"],
                $previous_week_date->format("Y-m-d"),
                $candidate_start,
                $candidate_end
            );


        /*
        |--------------------------------------------------------------------------
        | Previous two weeks
        |--------------------------------------------------------------------------
        */

        $previous_two_weeks_booking =
            getHistoricalBookingCount(
                $conn,
                $candidate_machine["id"],
                $previous_two_week_date->format("Y-m-d"),
                $candidate_start,
                $candidate_end
            );


        /*
        |--------------------------------------------------------------------------
        | Previous three weeks
        |--------------------------------------------------------------------------
        */

        $previous_three_weeks_booking =
            getHistoricalBookingCount(
                $conn,
                $candidate_machine["id"],
                $previous_three_week_date->format("Y-m-d"),
                $candidate_start,
                $candidate_end
            );


        /*
        |--------------------------------------------------------------------------
        | Calculate three-week average
        |--------------------------------------------------------------------------
        */

        $previous_three_week_average = (
            $previous_week_booking +
            $previous_two_weeks_booking +
            $previous_three_weeks_booking
        ) / 3;


        /*
        |--------------------------------------------------------------------------
        | Convert database resource ID to ML resource ID
        |--------------------------------------------------------------------------
        */

        $model_resource_id =
            getModelResourceId(
                $candidate_machine
            );


        /*
        |--------------------------------------------------------------------------
        | Predict demand
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | GradientBoostingPredictor.php uses predict(),
        | not predictDemand().
        |
        |--------------------------------------------------------------------------
        */

        $predicted_demand =
            $predictor->predict(
                $model_resource_id,
                $candidate_machine["machine_type"],
                $day_of_week,
                $hour,
                $is_weekend,
                $previous_week_booking,
                $previous_two_weeks_booking,
                $previous_three_weeks_booking,
                $previous_three_week_average
            );


        /*
        |--------------------------------------------------------------------------
        | Calculate distance from requested time
        |--------------------------------------------------------------------------
        */

        $time_distance = abs(
            $hour - $requested_hour
        );


        /*
        |--------------------------------------------------------------------------
        | Save candidate
        |--------------------------------------------------------------------------
        */

        $recommendations[] = [

            "machine_id" =>
                $candidate_machine["id"],

            "machine_name" =>
                $candidate_machine["machine_name"],

            "machine_type" =>
                $candidate_machine["machine_type"],

            "date" =>
                $date,

            "start_time" =>
                $candidate_start,

            "end_time" =>
                $candidate_end,

            "time_distance" =>
                $time_distance,

            "predicted_demand" =>
                $predicted_demand,

            "previous_week_booking" =>
                $previous_week_booking,

            "previous_two_weeks_booking" =>
                $previous_two_weeks_booking,

            "previous_three_weeks_booking" =>
                $previous_three_weeks_booking
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Rank recommendations
|--------------------------------------------------------------------------
|
| First:
|   Closest time to requested slot
|
| Second:
|   Lower predicted demand
|
|--------------------------------------------------------------------------
*/

usort(
    $recommendations,
    function ($a, $b) {

        if (
            $a["time_distance"] ===
            $b["time_distance"]
        ) {
            return
                $a["predicted_demand"]
                <=>
                $b["predicted_demand"];
        }

        return
            $a["time_distance"]
            <=>
            $b["time_distance"];
    }
);


/*
|--------------------------------------------------------------------------
| Keep the top five recommendations
|--------------------------------------------------------------------------
*/

$recommendations = array_slice(
    $recommendations,
    0,
    5
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Recommended Alternatives</title>

<link
    rel="stylesheet"
    href="css/style.css"
>

</head>

<body>

<div class="container">

    <h1>Recommended Alternatives</h1>


    <div class="booking-details">

        <p>
            <strong>Requested Machine:</strong>

            <?php
            echo htmlspecialchars(
                $requested_machine["machine_name"]
            );
            ?>
        </p>


        <p>
            <strong>Type:</strong>

            <?php
            echo htmlspecialchars(
                $requested_machine["machine_type"]
            );
            ?>
        </p>


        <p>
            <strong>Date:</strong>

            <?php
            echo htmlspecialchars($date);
            ?>
        </p>


        <p>
            <strong>Requested Time:</strong>

            <?php
            echo htmlspecialchars($start_time);
            ?>

            -

            <?php
            echo htmlspecialchars($end_time);
            ?>
        </p>

    </div>


    <div class="error">

        This slot is already booked.

        No booking has been created for you.

    </div>


    <p>

        
        <strong>
            <?php
            echo htmlspecialchars(
                $requested_machine["machine_type"]
            );
            ?>
        </strong>
        

    </p>


    <?php if (count($recommendations) > 0): ?>

        <h2>Available Alternatives</h2>


        <?php foreach ($recommendations as $recommendation): ?>

            <div class="slot available">

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $recommendation["machine_name"]
                        );
                        ?>
                    </strong>

                    <br>


                    <?php
                    echo htmlspecialchars(
                        $recommendation["start_time"]
                    );
                    ?>

                    -

                    <?php
                    echo htmlspecialchars(
                        $recommendation["end_time"]
                    );
                    ?>


                    <br>


                    Time distance:

                    <?php
                    echo htmlspecialchars(
                        $recommendation["time_distance"]
                    );
                    ?>

                    hour(s)


                    <br>


                    Predicted demand:

                    <?php
                    echo number_format(
                        $recommendation["predicted_demand"],
                        2
                    );
                    ?>

                </div>


                <div>

                    <a
                        href="book.php?machine_id=<?php echo urlencode($recommendation["machine_id"]); ?>&date=<?php echo urlencode($recommendation["date"]); ?>&start_time=<?php echo urlencode($recommendation["start_time"]); ?>&end_time=<?php echo urlencode($recommendation["end_time"]); ?>"
                    >
                        Book this alternative
                    </a>

                </div>

            </div>

        <?php endforeach; ?>


    <?php else: ?>

        <div class="error">

            No available alternatives were found
            for this machine type and date.

        </div>

    <?php endif; ?>


    <br>


    <a
        href="availability.php?machine_id=<?php echo urlencode($machine_id); ?>&date=<?php echo urlencode($date); ?>"
        class="back"
    >
        Back to Availability
    </a>

</div>

</body>

</html>