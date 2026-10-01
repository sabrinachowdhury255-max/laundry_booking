<?php

session_start();

require_once "config/database.php";

// Make sure the resident is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Get the booking ID
$booking_id = $_GET["id"] ?? '';

if (!$booking_id || !is_numeric($booking_id)) {
    header("Location: my_bookings.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Check that the booking belongs to the logged-in resident
$sql = "SELECT id, booking_date, start_time, end_time, status
        FROM bookings
        WHERE id = ?
        AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Only confirmed bookings can be cancelled
if ($booking["status"] !== "confirmed") {
    header("Location: my_bookings.php");
    exit();
}

// Cancel the booking
$sql = "UPDATE bookings
        SET status = 'cancelled'
        WHERE id = ?
        AND user_id = ?
        AND status = 'confirmed'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();

header("Location: my_bookings.php");
exit();

?>