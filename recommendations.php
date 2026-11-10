<?php

/* LaundryWise file overview: Validates the requested slot, gathers eligible machine/time alternatives, and uses the trained predictor to estimate demand and rank the alternatives. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();
date_default_timezone_set("Europe/Copenhagen");

require_once "config/database.php";
require_once "ml/GradientBoostingPredictor.php";


/*
|--------------------------------------------------------------------------
| Login protection
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
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

// All requested slots must be one hour, within opening hours, and in the future.
$requested_start_minutes = ((int)$start_check->format("H")) * 60 + (int)$start_check->format("i");
$requested_end_minutes = ((int)$end_check->format("H")) * 60 + (int)$end_check->format("i");
if ($requested_end_minutes - $requested_start_minutes !== 60) {
    die("Please choose a one-hour laundry slot.");
}
if ($requested_start_minutes < 480 || $requested_end_minutes > 1320) {
    die("Bookings must start at or after 08:00 and finish by 22:00.");
}
/* Explanation: Uses Denmark’s local timezone so booking date/time validation follows the residence’s local clock. */
$timezone = new DateTimeZone("Europe/Copenhagen");
$now = new DateTime("now", $timezone);
if ($date < $now->format("Y-m-d") || ($date === $now->format("Y-m-d") && $start_check <= DateTime::createFromFormat("H:i", $now->format("H:i"), $timezone))) {
    die("That laundry slot has already started or passed. Return to the dashboard and choose a future time.");
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

/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
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
    $sql = "SELECT id, user_id
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
| Check the requested slot, but do not block recommendations
|--------------------------------------------------------------------------
|
| The dashboard search can request recommendations even when the
| preferred slot is currently free. The requested machine/time is still
| excluded from the alternatives below, and booked alternatives are never
| returned. This does not reserve a machine or guarantee availability.
|
|--------------------------------------------------------------------------
*/

$requested_slot_is_booked = isSlotBooked(
    $conn,
    $machine_id,
    $date,
    $start_time,
    $end_time
);

/*
|--------------------------------------------------------------------------
| Find the user's own original booking
|--------------------------------------------------------------------------
*/

$original_booking_id = 0;

$user_id = $_SESSION["user_id"];

$sql = "SELECT id
        FROM bookings
        WHERE user_id = ?
        AND machine_id = ?
        AND booking_date = ?
        AND status = 'confirmed'
        AND start_time < ?
        AND end_time > ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iisss",
    $user_id,
    $machine_id,
    $date,
    $end_time,
    $start_time
);

$stmt->execute();

$result = $stmt->get_result();

$original_booking = $result->fetch_assoc();

if ($original_booking) {
    $original_booking_id = (int)$original_booking["id"];
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

    // Generate alternatives every 15 minutes; each alternative lasts one hour.
    for ($candidate_minutes = 480; $candidate_minutes <= 1260; $candidate_minutes += 15) {

        $candidate_hour = intdiv($candidate_minutes, 60);
        $candidate_minute = $candidate_minutes % 60;
        $candidate_start = sprintf("%02d:%02d", $candidate_hour, $candidate_minute);
        $candidate_end_minutes = $candidate_minutes + 60;
        $candidate_end = sprintf("%02d:%02d", intdiv($candidate_end_minutes, 60), $candidate_end_minutes % 60);

        if ($date === $now->format("Y-m-d") && $candidate_start <= $now->format("H:i")) {
            continue;
        }


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
                $candidate_hour,
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
            $candidate_minutes - $requested_start_minutes
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

/*
|--------------------------------------------------------------------------
| Return results to the resident dashboard when requested there
|--------------------------------------------------------------------------
|
| The dashboard will render these results from the session. Requests from
| existing pages that do not set return_to_dashboard continue to use the
| original standalone recommendations page below.
|
|--------------------------------------------------------------------------
*/

if (($_GET["return_to_dashboard"] ?? "") === "1") {
    $_SESSION["dashboard_recommendation_search"] = [
        "machine_id" => $machine_id,
        "machine_name" => $requested_machine["machine_name"],
        "machine_type" => $requested_machine["machine_type"],
        "date" => $date,
        "start_time" => $start_time,
        "end_time" => $end_time,
        "requested_slot_is_booked" => $requested_slot_is_booked,
        "original_booking_id" => $original_booking_id,
        "recommendations" => $recommendations,
        "created_at" => time()
    ];

    header("Location: dashboard.php#recommendation-results");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Recommended Alternatives | LaundryWise</title>

<link
    rel="stylesheet"
    href="css/style.css"
>

<style>
.recommendation-card {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
    padding: 18px;
    margin: 12px 0;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
}


.recommendation-visual { display:flex; align-items:center; justify-content:center; min-height:155px; margin:-18px -18px 16px; border-radius:8px 8px 0 0; background:linear-gradient(135deg,#e9ddff 0%,#dceaff 48%,#f7e7ff 100%); overflow:hidden; }
.recommendation-visual svg { width:150px; height:140px; max-width:100%; filter:drop-shadow(0 9px 9px rgba(30,50,90,.18)); }

.recommendation-info {
    flex: 1;
}

.recommendation-info h3 {
    margin: 0 0 8px;
}

.demand {
    margin-top: 8px;
    font-weight: bold;
}

.recommendation-action a {
    display: inline-block;
    padding: 10px 16px;
    background: #007bff;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    white-space: nowrap;
}

.recommendation-action a:hover {
    background: #0056b3;
}

@media (max-width: 700px) {
    .recommendation-card {
        flex-direction: column;
        align-items: stretch;
    }

    .recommendation-action a {
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }
}
</style>

</head>

<body>

<div class="container">

    <h1>Choose an Alternative</h1>

    <div class="error">
        <?php if ($requested_slot_is_booked): ?>
            The selected time is already booked.
        <?php else: ?>
            Here are alternative machines and times with lower predicted demand.
        <?php endif; ?>
        <?php if ($original_booking_id): ?>
            Choose another option below to replace your current booking.
        <?php else: ?>
            Recommendations are suggestions only and do not reserve a machine.
        <?php endif; ?>
    </div>

    <?php if (count($recommendations) > 0): ?>

        <h2>Recommended Alternatives</h2>

        <?php foreach ($recommendations as $recommendation): ?>

            <?php
            if ($recommendation["predicted_demand"] < 2) {
                $demand_level = "Low expected demand";
            } elseif ($recommendation["predicted_demand"] < 4) {
                $demand_level = "Moderate expected demand";
            } else {
                $demand_level = "High expected demand";
            }

            $booking_url =
                "book.php?" .
                "machine_id=" . urlencode($recommendation["machine_id"]) .
                "&date=" . urlencode($recommendation["date"]) .
                "&start_time=" . urlencode($recommendation["start_time"]) .
                "&end_time=" . urlencode($recommendation["end_time"]);

            if ($original_booking_id) {
                $booking_url .=
                    "&replace_booking_id=" .
                    urlencode($original_booking_id);
            }
            ?>

            <div class="recommendation-card">

                <div class="recommendation-visual" role="img" aria-label="Washing machine illustration">
                    <svg viewBox="0 0 160 145" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs><linearGradient id="standaloneWasherBody" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#cbd5e1"/></linearGradient><linearGradient id="standaloneWasherGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#64748b"/><stop offset="1" stop-color="#172033"/></linearGradient></defs>
                        <rect x="25" y="8" width="110" height="128" rx="15" fill="url(#standaloneWasherBody)" stroke="#aeb9ca" stroke-width="2"/><rect x="36" y="20" width="88" height="21" rx="5" fill="#eef2f7" stroke="#cbd5e1"/><circle cx="47" cy="30" r="4" fill="#8b5cf6"/><rect x="60" y="27" width="34" height="5" rx="2.5" fill="#cbd5e1"/><circle cx="113" cy="30" r="4" fill="#94a3b8"/><circle cx="80" cy="84" r="39" fill="#e2e8f0" stroke="#9aa8bb" stroke-width="3"/><circle cx="80" cy="84" r="31" fill="url(#standaloneWasherGlass)" stroke="#f8fafc" stroke-width="3"/><path d="M59 68 Q80 51 101 70 Q88 82 99 99 Q78 112 61 94 Q72 81 59 68Z" fill="#94a3b8" opacity=".55"/><circle cx="80" cy="84" r="7" fill="#cbd5e1" opacity=".8"/><path d="M59 60 Q75 51 91 58" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".65" fill="none"/>
                    </svg>
                </div>

                <div class="recommendation-info">

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $recommendation["machine_name"]
                        );
                        ?>
                    </h3>

                    <div>
                        <strong>Time:</strong>
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
                    </div>

                    <div class="demand">
                        <?php echo htmlspecialchars($demand_level); ?>
                    </div>

                </div>

                <div class="recommendation-action">

                    <a href="<?php echo htmlspecialchars($booking_url); ?>">
                        <?php
                        echo $original_booking_id
                            ? "Switch to this time"
                            : "Book this alternative";
                        ?>
                    </a>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="error">
            No available alternatives were found for this date.
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
