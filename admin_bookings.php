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

// Get all bookings with resident and machine information
$sql = "SELECT
            bookings.id,
            users.resident_id,
            users.name,
            machines.machine_name,
            bookings.booking_date,
            bookings.start_time,
            bookings.end_time,
            bookings.status,
            bookings.created_at
        FROM bookings
        INNER JOIN users
            ON bookings.user_id = users.id
        INNER JOIN machines
            ON bookings.machine_id = machines.id
        ORDER BY bookings.booking_date DESC,
                 bookings.start_time DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Bookings - Admin</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h1>Laundry Booking System</h1>

    <h2>Booking Management</h2>

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

    <?php if ($result->num_rows > 0): ?>

        <table>

            <thead>

                <tr>
                    <th>Booking ID</th>
                    <th>Resident ID</th>
                    <th>Resident Name</th>
                    <th>Machine</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>

            </thead>

            <tbody>

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($booking["id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking["resident_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking["name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking["machine_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking["booking_date"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                substr($booking["start_time"], 0, 5)
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                substr($booking["end_time"], 0, 5)
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking["status"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking["created_at"]); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty-message">
            No bookings found.
        </div>

    <?php endif; ?>

</div>

</body>

</html>