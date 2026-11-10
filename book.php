<?php

/* LaundryWise file overview: Handles the resident booking request: validates the machine/date/time, checks overlapping confirmed bookings, and creates or replaces a booking only when the requested slot passes validation. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();
date_default_timezone_set("Europe/Copenhagen");

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
    header("Location: login.php");
    exit();
}

$machine_id = $_GET["machine_id"] ?? '';
$date = $_GET["date"] ?? '';
$start_time = $_GET["start_time"] ?? '';
$end_time = $_GET["end_time"] ?? '';
$replace_booking_id = isset($_GET["replace_booking_id"])
    ? intval($_GET["replace_booking_id"])
    : 0;

$message = "";
$error = "";

$machine = null;
$new_booking_id = 0;
$email_notifications_enabled = 0;

$user_id = $_SESSION["user_id"];

$sql = "SELECT email_notifications_enabled
        FROM users
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user_settings = $result->fetch_assoc();

if ($user_settings) {
    $email_notifications_enabled =
        (int) $user_settings["email_notifications_enabled"];
}

if (!$machine_id || !$date || !$start_time || !$end_time) {

    $error = "Invalid booking information.";

} else {

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

    if (!$error) {

        $date_check = DateTime::createFromFormat("Y-m-d", $date);
        $start_check = DateTime::createFromFormat("H:i", $start_time);
        $end_check = DateTime::createFromFormat("H:i", $end_time);

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

    /* Explanation: Uses Denmark’s local timezone so booking date/time validation follows the residence’s local clock. */
    $timezone = new DateTimeZone("Europe/Copenhagen");
    $now = new DateTime("now", $timezone);

    if (!$error && $date < $now->format("Y-m-d")) {
        $error = "You cannot book a past date.";
    }

    if (!$error) {
        $start_minutes = ((int)$start_check->format("H")) * 60 + (int)$start_check->format("i");
        $end_minutes = ((int)$end_check->format("H")) * 60 + (int)$end_check->format("i");

        if ($end_minutes - $start_minutes !== 60) {
            $error = "Laundry bookings must last exactly one hour.";
        } elseif ($start_minutes < 480 || $end_minutes > 1320) {
            $error = "Bookings must start at or after 08:00 and finish by 22:00.";
        } elseif ($date === $now->format("Y-m-d") && $start_check <= DateTime::createFromFormat("H:i", $now->format("H:i"), $timezone)) {
            $error = "That time has already started or passed. Please choose a future time.";
        }
    }

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
        $existing_booking = $result->fetch_assoc();

        if ($existing_booking) {

            if ($replace_booking_id > 0) {

                try {

                    /* Explanation: Starts a database transaction so related booking updates either succeed together or can be rolled back together. */
                    $conn->begin_transaction();

                    $sql = "SELECT id
                            FROM bookings
                            WHERE id = ?
                            AND user_id = ?
                            AND status = 'confirmed'
                            /* Explanation: Locks the selected booking row during the transaction to reduce the risk of concurrent changes. */
                            FOR UPDATE";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "ii",
                        $replace_booking_id,
                        $user_id
                    );
                    $stmt->execute();

                    $original_result = $stmt->get_result();
                    $original_booking = $original_result->fetch_assoc();

                    if (!$original_booking) {
                        throw new Exception(
                            "The original booking could not be found."
                        );
                    }

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

                    $alternative_result = $stmt->get_result();
                    $alternative_booking = $alternative_result->fetch_assoc();

                    if ($alternative_booking) {
                        throw new Exception(
                            "This alternative time slot is no longer available."
                        );
                    }

                    $sql = "UPDATE bookings
                            SET status = 'cancelled',
                                reminder_enabled = 0
                            WHERE id = ?
                            AND user_id = ?
                            AND status = 'confirmed'";

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param(
                        "ii",
                        $replace_booking_id,
                        $user_id
                    );

                    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                        throw new Exception(
                            "The original booking could not be cancelled."
                        );
                    }

                    $sql = "INSERT INTO bookings
                            (
                                user_id,
                                machine_id,
                                booking_date,
                                start_time,
                                end_time,
                                status,
                                reminder_enabled
                            )
                            VALUES (?, ?, ?, ?, ?, 'confirmed', ?)";

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param(
                        "iisssi",
                        $user_id,
                        $machine_id,
                        $date,
                        $start_time,
                        $end_time,
                        $email_notifications_enabled
                    );

                    if (!$stmt->execute()) {
                        throw new Exception(
                            "The alternative booking could not be created."
                        );
                    }

                    $new_booking_id = $conn->insert_id;

                    $conn->commit();

                    $message = "Booking changed successfully.";

                } catch (Throwable $e) {

                    $conn->rollback();

                    $error = $e->getMessage();
                }

            } else {

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
    }

    if (!$error && !$message) {

        $user_id = $_SESSION["user_id"];

        $sql = "INSERT INTO bookings
                (
                    user_id,
                    machine_id,
                    booking_date,
                    start_time,
                    end_time,
                    status,
                    reminder_enabled
                )
                VALUES (?, ?, ?, ?, ?, 'confirmed', ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iisssi",
            $user_id,
            $machine_id,
            $date,
            $start_time,
            $end_time,
            $email_notifications_enabled
        );

        if ($stmt->execute()) {
            $new_booking_id = $conn->insert_id;
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

    <h1>
        <?php
        echo $replace_booking_id > 0
            ? "Booking Changed"
            : "Booking Confirmed";
        ?>
    </h1>

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

    <?php if ($new_booking_id > 0 && $email_notifications_enabled): ?>

        <div style="margin-top:25px;padding:16px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff;">
            🔔 Email notifications are enabled in your Settings.
        </div>

    <?php elseif ($new_booking_id > 0): ?>

        <div style="margin-top:25px;padding:16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
            🔕 Email notifications are currently disabled. You can change this in Settings.
        </div>

    <?php endif; ?>

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
