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

// Get all residents
$sql = "SELECT id, resident_id, name, email
        FROM users
        WHERE role = 'resident'
        ORDER BY name ASC";

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

    <title>Residents - Admin</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h1>Laundry Booking System</h1>

    <h2>Residents</h2>

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
                    <th>Resident ID</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>

            </thead>

            <tbody>

                <?php while ($resident = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($resident["resident_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($resident["name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($resident["email"]); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty-message">
            No residents found.
        </div>

    <?php endif; ?>

</div>

</body>

</html>