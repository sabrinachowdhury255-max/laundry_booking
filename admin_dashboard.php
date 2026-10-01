<?php

session_start();

require_once "config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Only allow administrators
if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

// Count registered residents
$sql = "SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'resident'";

$result = $conn->query($sql);
$resident_count = $result->fetch_assoc()["total"];

// Count active machines
$sql = "SELECT COUNT(*) AS total
        FROM machines
        WHERE status = 'active'";

$result = $conn->query($sql);
$machine_count = $result->fetch_assoc()["total"];

// Count confirmed bookings
$sql = "SELECT COUNT(*) AS total
        FROM bookings
        WHERE status = 'confirmed'";

$result = $conn->query($sql);
$booking_count = $result->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h1>Laundry Booking System</h1>

    <h2>Admin Dashboard</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["name"]); ?>
    </p>

    <hr>

    <!-- Admin navigation -->
    <p>
        <a href="admin_dashboard.php">Dashboard</a>
        |
        <a href="admin_residents.php">Residents</a>
        |
        <a href="admin_machines.php">Machines</a>
        |
        <a href="admin_bookings.php">Bookings</a>
        |
        <a href="logout.php">Logout</a>
    </p>

    <hr>

    <h2>System Overview</h2>

    <div class="admin-card">

        <h3>Registered Residents</h3>

        <p class="admin-number">
            <?php echo htmlspecialchars($resident_count); ?>
        </p>

    </div>

    <div class="admin-card">

        <h3>Active Machines</h3>

        <p class="admin-number">
            <?php echo htmlspecialchars($machine_count); ?>
        </p>

    </div>

    <div class="admin-card">

        <h3>Confirmed Bookings</h3>

        <p class="admin-number">
            <?php echo htmlspecialchars($booking_count); ?>
        </p>

    </div>

</div>

</body>

</html>