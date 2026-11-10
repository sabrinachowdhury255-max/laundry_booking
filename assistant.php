<?php

/* LaundryWise file overview: Handles the LaundryWise assistant request and response logic. It is an additional interface and does not replace the core booking workflow. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

header("Content-Type: application/json");

require_once "config/database.php";
require_once "ml/GradientBoostingPredictor.php";


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "answer" => "Please log in first."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Read question
|--------------------------------------------------------------------------
*/

$question =
    trim(
        $_POST["question"] ?? ""
    );


if ($question === "") {

    echo json_encode([
        "success" => true,
        "answer" =>
            "Sure 😊 What would you like to know?"
    ]);

    exit();
}


$q =
    strtolower(
        preg_replace(
            '/\s+/',
            ' ',
            $question
        )
    );


$q =
    preg_replace(
        '/[?!.,]/',
        '',
        $q
    );


/*
|--------------------------------------------------------------------------
| Date detection
|--------------------------------------------------------------------------
*/

$isTomorrow =
    strpos($q, "tomorrow") !== false ||
    strpos($q, "next day") !== false;


/*
|--------------------------------------------------------------------------
| Helper: match phrases
|--------------------------------------------------------------------------
*/

function containsAny(
    $text,
    $phrases
) {

    foreach ($phrases as $phrase) {

        if (
            strpos(
                $text,
                $phrase
            ) !== false
        ) {

            return true;
        }
    }

    return false;
}


/*
|--------------------------------------------------------------------------
| POLICY / FAQ QUESTIONS
|--------------------------------------------------------------------------
| These answers describe only functionality that exists
| in the LaundryWise system.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Cancellation
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "what happens if i cancel",
            "what happens when i cancel",
            "if i cancel",
            "when i cancel",
            "can i cancel",
            "how do i cancel",
            "cancel my booking",
            "cancel a booking"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "You can cancel a confirmed booking from My Bookings. Once cancelled, the booking is marked as cancelled and the time can become available for another resident."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| No-show
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "if i don't show",
            "if i dont show",
            "what if i don't come",
            "what if i dont come",
            "if i do not come",
            "if i don't use",
            "if i dont use",
            "no show",
            "no-show"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "If you know you won't use your booking, it's best to cancel it so the time can become available to someone else. LaundryWise currently does not apply or track a separate no-show penalty."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Booking occupied slot
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "book an occupied",
            "book a booked",
            "book an unavailable",
            "book unavailable",
            "already booked",
            "someone already booked",
            "can i book this time"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "No. A confirmed booking prevents another resident from booking the same machine and time slot."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Automatic booking
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "automatically book",
            "auto book",
            "automatic booking",
            "will you book",
            "does it book",
            "recommendation book"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "No. I only suggest suitable alternatives. You choose the option and confirm the booking yourself."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Physical machine control
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "control the machine",
            "control a washing machine",
            "start the washing machine",
            "start machine",
            "turn on the machine",
            "stop the machine",
            "physical machine",
            "machine remotely"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "No. LaundryWise manages digital availability and bookings. It does not physically control or monitor the washing machines."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Availability guarantee
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "guarantee availability",
            "guarantee a machine",
            "guaranteed availability",
            "guaranteed machine",
            "guarantee that"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "No. LaundryWise shows digital availability and prevents conflicting bookings, but it cannot guarantee the physical machine is actually free or operating."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| How recommendation works
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "how are recommendations made",
            "how do you recommend",
            "how do you choose",
            "how is the recommendation",
            "how does recommendation",
            "how do recommendations",
            "what do you use",
            "what does the ai use"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "I first look at available machines and time slots. Then LaundryWise uses previous booking patterns to estimate relative availability and helps rank suitable options."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Why recommendation
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "why did you recommend",
            "why did you suggest",
            "why this time",
            "why this machine",
            "why was this recommended",
            "why was this suggested"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "I look for an available option and use previous booking patterns to estimate relative availability. The suggestion is intended to give you a suitable alternative rather than simply showing another available slot."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| How to book
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "how do i book",
            "how can i book",
            "make a booking",
            "make booking",
            "book a machine",
            "how to book"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "Choose a machine from the dashboard, select an available date and time slot, then confirm your booking."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Change booking
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "change my booking",
            "change a booking",
            "change booking",
            "edit my booking",
            "edit booking",
            "reschedule",
            "move my booking"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "LaundryWise lets you cancel a confirmed booking and choose another available time. If your requested alternative is unavailable, the system can also suggest other suitable options."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| My bookings
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "my bookings",
            "my reservations",
            "where are my bookings",
            "see my bookings",
            "view my bookings",
            "show my bookings"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "You can see your current and previous booking records in My Bookings."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Account security
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "password protected",
            "password secure",
            "account protected",
            "how is my account",
            "is my password",
            "security"
        ]
    )
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "LaundryWise stores passwords using secure password hashing rather than storing them as plain text."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Greetings
|--------------------------------------------------------------------------
*/

if (
    containsAny(
        $q,
        [
            "hello",
            "hi",
            "hey",
            "good morning",
            "good afternoon",
            "good evening"
        ]
    )
    &&
    strlen($q) < 30
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "Hi! 👋 What can I help you with today?"
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Everything below this point uses ML
|--------------------------------------------------------------------------
*/


date_default_timezone_set(
    "Europe/Copenhagen"
);


$today =
    date("Y-m-d");


$tomorrow =
    date(
        "Y-m-d",
        strtotime("+1 day")
    );


/*
|--------------------------------------------------------------------------
| Determine smart intent
|--------------------------------------------------------------------------
*/

$intent = "unknown";


if (
    containsAny(
        $q,
        [
            "best time",
            "good time",
            "good slot",
            "best slot",
            "when should i",
            "when should we",
            "when can i",
            "when can we",
            "what time should",
            "when is best",
            "when should i do laundry",
            "when should i wash",
            "when should i go"
        ]
    )
) {

    $intent =
        $isTomorrow
        ? "best_tomorrow"
        : "best_today";
}


elseif (
    containsAny(
        $q,
        [
            "quiet",
            "quietest",
            "less busy",
            "least busy",
            "high availability",
            "lowest demand",
            "not busy",
            "calm",
            "less crowded"
        ]
    )
) {

    $intent =
        $isTomorrow
        ? "low_tomorrow"
        : "low_today";
}


elseif (
    containsAny(
        $q,
        [
            "busy",
            "busiest",
            "crowded",
            "low availability",
            "lowest availability",
            "most busy",
            "when is everyone",
            "when should i avoid",
            "what time should i avoid"
        ]
    )
) {

    $intent =
        $isTomorrow
        ? "high_tomorrow"
        : "high_today";
}


elseif (
    containsAny(
        $q,
        [
            "which machine",
            "what machine",
            "machine should i",
            "machine should we",
            "best machine",
            "good machine",
            "machine to use",
            "where should i wash"
        ]
    )
) {

    $intent =
        "machine";
}


elseif (
    containsAny(
        $q,
        [
            "available",
            "availability",
            "free machine",
            "free machines",
            "can i wash",
            "can i do laundry",
            "is there a machine"
        ]
    )
) {

    $intent =
        "availability";
}


/*
|--------------------------------------------------------------------------
| Unknown
|--------------------------------------------------------------------------
*/

if (
    $intent === "unknown"
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "I can help with bookings, cancellations, available machines, quieter times, busy periods, recommendations and LaundryWise policies. 😊"
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Slot booking check
|--------------------------------------------------------------------------
*/

function isSlotBooked(
    $conn,
    $machineId,
    $date,
    $startTime,
    $endTime
) {

    $sql = "SELECT id
            FROM bookings
            WHERE machine_id = ?
            AND booking_date = ?
            AND status = 'confirmed'
            AND start_time < ?
            AND end_time > ?
            LIMIT 1";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {
        return true;
    }


    /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
    $stmt->bind_param(
        "isss",
        $machineId,
        $date,
        $endTime,
        $startTime
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $booked =
        $result->num_rows > 0;


    $stmt->close();


    return $booked;
}


/*
|--------------------------------------------------------------------------
| Historical features
|--------------------------------------------------------------------------
*/

function getHistoricalFeatures(
    $conn,
    $machineId,
    $date,
    $startTime
) {

    $dates = [

        date(
            "Y-m-d",
            strtotime(
                $date . " -7 days"
            )
        ),

        date(
            "Y-m-d",
            strtotime(
                $date . " -14 days"
            )
        ),

        date(
            "Y-m-d",
            strtotime(
                $date . " -21 days"
            )
        )
    ];


    $values = [];


    foreach ($dates as $historicalDate) {

        $sql = "SELECT COUNT(*) AS total
                FROM bookings
                WHERE machine_id = ?
                AND booking_date = ?
                AND start_time = ?
                AND status IN ('confirmed', 'completed')";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            $values[] = 0;

            continue;
        }


        $stmt->bind_param(
            "iss",
            $machineId,
            $historicalDate,
            $startTime
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $row =
            $result->fetch_assoc();


        $values[] =
            (float)(
                $row["total"] ?? 0
            );


        $stmt->close();
    }


    return [

        "previous_week_booking" =>
            $values[0],

        "previous_2_weeks_booking" =>
            $values[1],

        "previous_3_weeks_booking" =>
            $values[2],

        "previous_3_week_average" =>
            array_sum($values) / 3
    ];
}


/*
|--------------------------------------------------------------------------
| Resource mapping
|--------------------------------------------------------------------------
*/

function getModelResourceId(
    $machine
) {

    if (
        $machine["machine_type"] === "Dryer"
    ) {

        return $machine["machine_name"];
    }


    if (
        preg_match(
            '/Machine\s+(\d+)/',
            $machine["machine_name"],
            $matches
        )
    ) {

        return "WM" .
            $matches[1];
    }


    return $machine["machine_name"];
}


/*
|--------------------------------------------------------------------------
| Active machines
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id,
            machine_name,
            machine_type,
            status
        FROM machines
        WHERE status = 'active'
        ORDER BY machine_type, id";


$result =
    $conn->query($sql);


$machines = [];


while (
    $machine =
    $result->fetch_assoc()
) {

    $machines[] =
        $machine;
}


/*
|--------------------------------------------------------------------------
| Load model
|--------------------------------------------------------------------------
*/

try {

    $predictor =
        new GradientBoostingPredictor(
            __DIR__ .
            "/ml/gradient_boosting_model_php.json"
        );

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "answer" =>
            "I'm having trouble accessing the laundry predictions right now."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Target date
|--------------------------------------------------------------------------
*/

$targetDate =
    in_array(
        $intent,
        [
            "best_tomorrow",
            "low_tomorrow",
            "high_tomorrow"
        ]
    )
    ? $tomorrow
    : $today;


/*
|--------------------------------------------------------------------------
| Generate candidates
|--------------------------------------------------------------------------
*/

$candidates = [];


foreach ($machines as $machine) {

    $machineId =
        (int)$machine["id"];


    $resourceId =
        getModelResourceId(
            $machine
        );


    for (
        $hour = 8;
        $hour <= 21;
        $hour++
    ) {

        $startTime =
            sprintf(
                "%02d:00:00",
                $hour
            );


        $endTime =
            sprintf(
                "%02d:00:00",
                $hour + 1
            );


        if (
            isSlotBooked(
                $conn,
                $machineId,
                $targetDate,
                $startTime,
                $endTime
            )
        ) {

            continue;
        }


        if (
            $targetDate === $today &&
            $hour <= (int)date("H")
        ) {

            continue;
        }


        $dayOfWeek =
            date(
                "l",
                strtotime($targetDate)
            );


        $isWeekend =
            (
                $dayOfWeek === "Saturday" ||
                $dayOfWeek === "Sunday"
            )
            ? 1
            : 0;


        $history =
            getHistoricalFeatures(
                $conn,
                $machineId,
                $targetDate,
                $startTime
            );


        $predictedDemand =
            $predictor->predict(
                $resourceId,
                $machine["machine_type"],
                $dayOfWeek,
                $hour,
                $isWeekend,
                $history[
                    "previous_week_booking"
                ],
                $history[
                    "previous_2_weeks_booking"
                ],
                $history[
                    "previous_3_weeks_booking"
                ],
                $history[
                    "previous_3_week_average"
                ]
            );


        $candidates[] = [

            "machine_name" =>
                $machine["machine_name"],

            "machine_id" =>
                $machineId,

            "machine_type" =>
                $machine["machine_type"],

            "start_time" =>
                sprintf(
                    "%02d:00",
                    $hour
                ),

            "end_time" =>
                sprintf(
                    "%02d:00",
                    $hour + 1
                ),

            "hour" =>
                $hour,

            "predicted_demand" =>
                $predictedDemand
        ];
    }
}


/*
|--------------------------------------------------------------------------
| No slots
|--------------------------------------------------------------------------
*/

if (
    count($candidates) === 0
) {

    echo json_encode([
        "success" => true,
        "answer" =>
            "It looks like there aren't any suitable available slots right now."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| Highest estimated availability first
|--------------------------------------------------------------------------
*/

usort(
    $candidates,
    function ($a, $b) {

        return
            $a["predicted_demand"]
            <=>
            $b["predicted_demand"];
    }
);


/*
|--------------------------------------------------------------------------
| Best time
|--------------------------------------------------------------------------
*/

if (
    $intent === "best_today" ||
    $intent === "best_tomorrow"
) {

    $best =
        $candidates[0];


    $when =
        $intent === "best_tomorrow"
        ? "tomorrow"
        : "today";


    $answer =
        "✨ I'd suggest " .
        $best["start_time"] .
        "–" .
        $best["end_time"] .
        " on " .
        $best["machine_name"] .
        " " .
        $when .
        ". It currently looks like a quieter available option.";
}


/*
|--------------------------------------------------------------------------
| Quiet
|--------------------------------------------------------------------------
*/

elseif (
    $intent === "low_today" ||
    $intent === "low_tomorrow"
) {

    $top =
        array_slice(
            $candidates,
            0,
            3
        );


    $when =
        $intent === "low_tomorrow"
        ? "tomorrow"
        : "today";


    $parts = [];


    foreach ($top as $item) {

        $parts[] =
            $item["machine_name"] .
            " " .
            $item["start_time"] .
            "–" .
            $item["end_time"];
    }


    $answer =
        "🌿 Some quieter options " .
        $when .
        " are:\n• " .
        implode(
            "\n• ",
            $parts
        ) .
        ".";
}


/*
|--------------------------------------------------------------------------
| Busy
|--------------------------------------------------------------------------
*/

elseif (
    $intent === "high_today" ||
    $intent === "high_tomorrow"
) {

    $busy =
        $candidates;


    usort(
        $busy,
        function ($a, $b) {

            return
                $b["predicted_demand"]
                <=>
                $a["predicted_demand"];
        }
    );


    $top =
        array_slice(
            $busy,
            0,
            3
        );


    $when =
        $intent === "high_tomorrow"
        ? "tomorrow"
        : "today";


    $parts = [];


    foreach ($top as $item) {

        $parts[] =
            $item["start_time"] .
            "–" .
            $item["end_time"];
    }


    $answer =
        "🌙 It looks busier around:\n• " .
        implode(
            "\n• ",
            $parts
        ) .
        "\n" .
        $when .
        ". If you can, I'd choose another time.";
}


/*
|--------------------------------------------------------------------------
| Machine
|--------------------------------------------------------------------------
*/

elseif (
    $intent === "machine"
) {

    $best =
        $candidates[0];


    $answer =
        "🫧 I'd suggest " .
        $best["machine_name"] .
        " around " .
        $best["start_time"] .
        "–" .
        $best["end_time"] .
        ". It currently looks like a quieter available choice.";
}


/*
|--------------------------------------------------------------------------
| Availability
|--------------------------------------------------------------------------
*/

elseif (
    $intent === "availability"
) {

    $best =
        $candidates[0];


    $answer =
        "Yes — there are available slots. One quieter option is " .
        $best["machine_name"] .
        " at " .
        $best["start_time"] .
        "–" .
        $best["end_time"] .
        ".";
}


echo json_encode([

    "success" =>
        true,

    "answer" =>
        $answer

]);

?>