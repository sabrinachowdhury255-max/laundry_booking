```php
<?php

/* LaundryWise file overview: Updates the reminder preference for a booking after checking the request and the signed-in user’s permission. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";

// Make sure the resident is logged in.
if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$booking_id = isset($_GET["booking_id"])
    ? (int) $_GET["booking_id"]
    : 0;

$enabled = isset($_GET["enabled"])
    ? (int) $_GET["enabled"]
    : 0;

// Validate the booking ID and reminder setting.
if ($booking_id <= 0 || !in_array($enabled, [0, 1], true)) {
    header("Location: dashboard.php");
    exit();
}

// Verify that this booking belongs to the logged-in resident.
$sql = "SELECT id
        FROM bookings
        WHERE id = ?
        AND user_id = ?
        AND status = 'confirmed'
        LIMIT 1";

$stmt = $conn->prepare($sql);
/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result->fetch_assoc()) {
    header("Location: my_bookings.php");
    exit();
}

// Update the reminder preference.
$sql = "UPDATE bookings
        SET reminder_enabled = ?
        WHERE id = ?
        AND user_id = ?
        AND status = 'confirmed'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $enabled, $booking_id, $user_id);
$stmt->execute();

// Redirect based on the selected setting.
if ($enabled === 1) {
    header(
        "Location: reminder_confirmation.php?booking_id="
        . urlencode((string) $booking_id)
    );
} else {
    header("Location: my_bookings.php");
}

exit();
?>
```