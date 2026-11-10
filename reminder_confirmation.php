<?php

/* LaundryWise file overview: Displays the result/confirmation associated with a reminder action or reminder setting. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$booking_id = isset($_GET["booking_id"]) ? intval($_GET["booking_id"]) : 0;

$sql = "SELECT bookings.booking_date,
               bookings.start_time,
               bookings.end_time,
               machines.machine_name
        FROM bookings
        INNER JOIN machines
            ON bookings.machine_id = machines.id
        WHERE bookings.id = ?
        AND bookings.user_id = ?
        AND bookings.status = 'confirmed'
        AND bookings.reminder_enabled = 1
        LIMIT 1";

$stmt = $conn->prepare($sql);
/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Enabled</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">

    <h1>🔔 Reminder Enabled</h1>

    <div class="success">
        Your email reminder has been enabled.
    </div>

    <div class="booking-details">
        <p>
            <strong>Machine:</strong>
            <?php echo htmlspecialchars($booking["machine_name"]); ?>
        </p>

        <p>
            <strong>Date:</strong>
            <?php echo htmlspecialchars($booking["booking_date"]); ?>
        </p>

        <p>
            <strong>Time:</strong>
            <?php echo htmlspecialchars(substr($booking["start_time"], 0, 5)); ?>
            -
            <?php echo htmlspecialchars(substr($booking["end_time"], 0, 5)); ?>
        </p>

        <p>
            You will receive a reminder 15 minutes before this booking.
        </p>
    </div>

    <a class="button-link" href="dashboard.php">
        Dashboard
    </a>

    <a class="button-link" href="my_bookings.php">
        My Bookings
    </a>

</div>

</body>
</html>
