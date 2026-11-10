<?php

/* LaundryWise file overview: Scheduled reminder script that finds eligible upcoming bookings and sends reminder messages. It is intended to be run by the configured scheduler. */


date_default_timezone_set("Europe/Copenhagen");

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config/mail.php";

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("This script can only be run from the command line.");
}

$mail_config = require __DIR__ . "/config/mail.php";

/* Explanation: Uses Denmark’s local timezone so booking date/time validation follows the residence’s local clock. */
$now = new DateTime("now", new DateTimeZone("Europe/Copenhagen"));

$window_start = (clone $now)->modify("+14 minutes");
$window_end = (clone $now)->modify("+19 minutes");

$window_start_date = $window_start->format("Y-m-d");
$window_start_time = $window_start->format("H:i:s");

$window_end_date = $window_end->format("Y-m-d");
$window_end_time = $window_end->format("H:i:s");

$sql = "SELECT
            b.id,
            b.booking_date,
            b.start_time,
            b.end_time,
            m.machine_name,
            u.name,
            u.email
        FROM bookings b
        INNER JOIN users u
            ON b.user_id = u.id
        INNER JOIN machines m
            ON b.machine_id = m.id
        WHERE b.status = 'confirmed'
        AND b.reminder_enabled = 1
        AND u.email_notifications_enabled = 1
        AND (
            CONCAT(b.booking_date, ' ', b.start_time)
            BETWEEN CONCAT(?, ' ', ?)
            AND CONCAT(?, ' ', ?)
        )
        ORDER BY b.booking_date, b.start_time";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit("Database error: could not prepare reminder query.\n");
}

/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param(
    "ssss",
    $window_start_date,
    $window_start_time,
    $window_end_date,
    $window_end_time
);

if (!$stmt->execute()) {
    exit("Database error: could not execute reminder query.\n");
}

$result = $stmt->get_result();

$sent = 0;
$failed = 0;
$found = $result->num_rows;

while ($booking = $result->fetch_assoc()) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = $mail_config["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $mail_config["username"];
        $mail->Password = $mail_config["password"];
        $mail->SMTPSecure = $mail_config["encryption"];
        $mail->Port = $mail_config["port"];

        $mail->setFrom(
            $mail_config["from_email"],
            $mail_config["from_name"]
        );

        $mail->addAddress(
            $booking["email"],
            $booking["name"]
        );

        $mail->isHTML(true);

        $machine_name = htmlspecialchars(
            $booking["machine_name"],
            ENT_QUOTES,
            "UTF-8"
        );

        $resident_name = htmlspecialchars(
            $booking["name"],
            ENT_QUOTES,
            "UTF-8"
        );

        $booking_date = htmlspecialchars(
            $booking["booking_date"],
            ENT_QUOTES,
            "UTF-8"
        );

        $start_time = htmlspecialchars(
            substr($booking["start_time"], 0, 5),
            ENT_QUOTES,
            "UTF-8"
        );

        $end_time = htmlspecialchars(
            substr($booking["end_time"], 0, 5),
            ENT_QUOTES,
            "UTF-8"
        );

        $mail->Subject = "LaundryWise Booking Reminder";

        $mail->Body = "
            <div style=\"font-family:Arial,sans-serif;line-height:1.6;\">
                <h2>LaundryWise Booking Reminder</h2>

                <p>Hello {$resident_name},</p>

                <p>
                    This is a reminder that your laundry booking
                    starts in approximately <strong>15 minutes</strong>.
                </p>

                <p>
                    <strong>Machine:</strong> {$machine_name}<br>
                    <strong>Date:</strong> {$booking_date}<br>
                    <strong>Time:</strong> {$start_time} - {$end_time}
                </p>

                <p>
                    Please arrive on time for your booking.
                </p>

                <p>
                    Regards,<br>
                    <strong>LaundryWise</strong>
                </p>
            </div>
        ";

        $mail->AltBody =
            "LaundryWise Booking Reminder\n\n" .
            "Hello " . $booking["name"] . ",\n\n" .
            "Your laundry booking starts in approximately 15 minutes.\n\n" .
            "Machine: " . $booking["machine_name"] . "\n" .
            "Date: " . $booking["booking_date"] . "\n" .
            "Time: " . substr($booking["start_time"], 0, 5) .
            " - " . substr($booking["end_time"], 0, 5) . "\n\n" .
            "Please arrive on time for your booking.\n\n" .
            "LaundryWise";

        $mail->send();

        $update_sql = "UPDATE bookings
                       SET reminder_enabled = 0
                       WHERE id = ?
                       AND status = 'confirmed'";

        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt) {
            $update_stmt->bind_param("i", $booking["id"]);
            $update_stmt->execute();
        }

        $sent++;

        echo
            "Reminder sent for booking #" .
            $booking["id"] .
            " to " .
            $booking["email"] .
            ".\n";

    } catch (Exception $e) {

        $failed++;

        echo
            "Reminder failed for booking #" .
            $booking["id"] .
            ": " .
            $mail->ErrorInfo .
            "\n";
    }
}

echo "\n";
echo "Reminder check completed.\n";
echo "Bookings found: " . $found . "\n";
echo "Emails sent: " . $sent . "\n";
echo "Emails failed: " . $failed . "\n";
?>