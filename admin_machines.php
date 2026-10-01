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

// Update machine status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $machine_id = $_POST["machine_id"] ?? "";
    $new_status = $_POST["status"] ?? "";

    // Only allow valid statuses
    if (
        is_numeric($machine_id) &&
        ($new_status === "active" || $new_status === "inactive")
    ) {

        $sql = "UPDATE machines
                SET status = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $machine_id);
        $stmt->execute();
    }

    header("Location: admin_machines.php");
    exit();
}

// Get all machines
$sql = "SELECT id, machine_name, status
        FROM machines
        ORDER BY id ASC";

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

    <title>Machines - Admin</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">

    <h1>Laundry Booking System</h1>

    <h2>Machine Management</h2>

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
                    <th>ID</th>
                    <th>Machine</th>
                    <th>Status</th>
                    <th>Change Status</th>
                </tr>

            </thead>

            <tbody>

                <?php while ($machine = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($machine["id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($machine["machine_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($machine["status"]); ?>
                        </td>

                        <td>

                            <form method="POST" action="">

                                <input
                                    type="hidden"
                                    name="machine_id"
                                    value="<?php echo htmlspecialchars($machine["id"]); ?>"
                                >

                                <select name="status">

                                    <option
                                        value="active"
                                        <?php
                                        if ($machine["status"] === "active") {
                                            echo "selected";
                                        }
                                        ?>
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="inactive"
                                        <?php
                                        if ($machine["status"] === "inactive") {
                                            echo "selected";
                                        }
                                        ?>
                                    >
                                        Inactive
                                    </option>

                                </select>

                                <button type="submit">
                                    Update
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty-message">
            No machines found.
        </div>

    <?php endif; ?>

</div>

</body>

</html>