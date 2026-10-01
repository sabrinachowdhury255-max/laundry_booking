<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$machine_id = $_GET["machine_id"] ?? '';
$date = $_GET["date"] ?? '';
$start_time = $_GET["start_time"] ?? '';
$end_time = $_GET["end_time"] ?? '';

$message = "";
$error = "";

$machine = null;


/*
|--------------------------------------------------------------------------
| Check required information
|--------------------------------------------------------------------------
*/

if (!$machine_id || !$date || !$start_time || !$end_time) {

    $error = "Invalid booking information.";

} else {

    /*
    |--------------------------------------------------------------------------
    | Get active machine
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT id, machine_name, machine_type
            FROM machines
            WHERE id = ?
            AND status = 'active'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $machine = $result->fetch_assoc();

    if (!$machine) {

        $error = "Machine not found.";
    }


    /*
    |--------------------------------------------------------------------------
    | Validate date and time
    |--------------------------------------------------------------------------
    */

    if (!$error) {

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

            $error = "Invalid date or time.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent past dates
    |--------------------------------------------------------------------------
    */

    if (!$error && $date < date("Y-m-d")) {

        $error = "You cannot book a past date.";
    }


    /*
    |--------------------------------------------------------------------------
    | Validate time
    |--------------------------------------------------------------------------
    */

    if (!$error && $start_time >= $end_time) {

        $error = "Invalid booking time.";
    }


    /*
    |--------------------------------------------------------------------------
    | Check whether slot is already booked
    |--------------------------------------------------------------------------
    */

    if (!$error) {

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

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Do NOT create a booking.
        | Send resident to recommendation page instead.
        |--------------------------------------------------------------------------
        */

        if ($result->num_rows > 0) {

            header(
                "Location: recommendations.php?" .
                "machine_id=" . urlencode($machine_id) .
                "&date=" . urlencode($date) .
                "&start_time=" . urlencode($start_time) .
                "&end_time=" . urlencode($end_time)
            );

            exit();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create booking
    |--------------------------------------------------------------------------
    */

    if (!$error) {

        $user_id = $_SESSION["user_id"];

        $sql = "INSERT INTO bookings
                (
                    user_id,
                    machine_id,
                    booking_date,
                    start_time,
                    end_time,
                    status
                )
                VALUES (?, ?, ?, ?, ?, 'confirmed')";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iisss",
            $user_id,
            $machine_id,
            $date,
            $start_time,
            $end_time
        );

        if ($stmt->execute()) {

            $message = "Booking confirmed successfully.";

        } else {

            $error = "Booking could not be created.";
        }
    }
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

<title>Booking Confirmation</title>

<link
    rel="stylesheet"
    href="css/style.css"
>

</head>

<body>

<div class="container">

<?php if ($message): ?>

    <h1>Booking Confirmed</h1>

    <div class="success">
        <?php echo htmlspecialchars($message); ?>
    </div>

    <div class="booking-details">

        <p>
            <strong>Machine:</strong>
            <?php echo htmlspecialchars($machine["machine_name"]); ?>
        </p>

        <p>
            <strong>Type:</strong>
            <?php echo htmlspecialchars($machine["machine_type"]); ?>
        </p>

        <p>
            <strong>Date:</strong>
            <?php echo htmlspecialchars($date); ?>
        </p>

        <p>
            <strong>Time:</strong>
            <?php echo htmlspecialchars($start_time); ?>
            -
            <?php echo htmlspecialchars($end_time); ?>
        </p>

    </div>

    <a
        class="button-link"
        href="dashboard.php"
    >
        Dashboard
    </a>

    <a
        class="button-link"
        href="my_bookings.php"
    >
        My Bookings
    </a>

    <a
        class="button-link"
        href="availability.php?machine_id=<?php echo urlencode($machine_id); ?>&date=<?php echo urlencode($date); ?>"
    >
        View Availability
    </a>

<?php else: ?>

    <h1>Booking Failed</h1>

    <div class="error">
        <?php echo htmlspecialchars($error); ?>
    </div>

    <a
        class="button-link"
        href="dashboard.php"
    >
        Back to Dashboard
    </a>

<?php endif; ?>

</div>

</body>

</html>