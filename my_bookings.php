<?php

session_start();

require_once "config/database.php";

// Make sure the resident is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get the logged-in resident's bookings
$sql = "SELECT
            bookings.id,
            bookings.booking_date,
            bookings.start_time,
            bookings.end_time,
            bookings.status,
            machines.machine_name
        FROM bookings
        INNER JOIN machines
            ON bookings.machine_id = machines.id
        WHERE bookings.user_id = ?
        ORDER BY bookings.booking_date DESC,
                 bookings.start_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Bookings</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h1>My Bookings</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["name"]); ?>
    </p>

    <hr>

    <!-- Resident navigation -->
    <p>
        <a href="dashboard.php">Dashboard</a>
        |
        <a href="my_bookings.php">My Bookings</a>
        |
        <a href="logout.php">Logout</a>
    </p>

    <hr>

    <?php if ($result->num_rows > 0): ?>

        <table>

            <thead>

                <tr>
                    <th>Machine</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <tr>

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
                            <?php echo htmlspecialchars(
                                ucfirst($booking["status"])
                            ); ?>
                        </td>

                        <td>

                            <?php if ($booking["status"] === "confirmed"): ?>

                                <a
                                    class="cancel-link"
                                    href="cancel_booking.php?id=<?php echo $booking["id"]; ?>"
                                >
                                    Cancel
                                </a>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty-message">
            You do not have any bookings yet.
        </div>

    <?php endif; ?>

</div>

</body>

</html>