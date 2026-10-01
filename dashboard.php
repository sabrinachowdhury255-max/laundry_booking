<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}


/* Get active machines */

$sql = "SELECT id, machine_name, machine_type, status
        FROM machines
        WHERE status = 'active'
        ORDER BY machine_type, id";

$result = $conn->query($sql);

$washing_machines = [];
$dryers = [];

while ($machine = $result->fetch_assoc()) {

    if ($machine["machine_type"] === "Dryer") {
        $dryers[] = $machine;
    } else {
        $washing_machines[] = $machine;
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

    <title>Laundry Booking System</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container">


    <!-- Navigation -->

    <nav class="dashboard-nav">

        <div class="nav-title">
            Laundry Booking System
        </div>

        <div class="nav-links">

            <a href="my_bookings.php">
                My Bookings
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </nav>


    <!-- Welcome section -->

    <div class="dashboard-header">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($_SESSION["name"]); ?>
        </h1>

        <p>
            Resident ID:
            <strong>
                <?php echo htmlspecialchars($_SESSION["resident_id"]); ?>
            </strong>
        </p>

    </div>


    <!-- Washing Machines -->

    <section class="machine-section">

        <h2>Washing Machines</h2>

        <?php if (count($washing_machines) > 0): ?>

            <div class="machine-grid">

                <?php foreach ($washing_machines as $machine): ?>

                    <div class="machine-card">

                        <h3>
                            <?php echo htmlspecialchars($machine["machine_name"]); ?>
                        </h3>

                        <p>
                            <strong>Type:</strong>
                            <?php echo htmlspecialchars($machine["machine_type"]); ?>
                        </p>

                        <p>
                            <strong>Status:</strong>

                            <span class="status-active">
                                <?php echo htmlspecialchars($machine["status"]); ?>
                            </span>
                        </p>

                        <a
                            href="availability.php?machine_id=<?php echo $machine["id"]; ?>"
                        >
                            View Availability
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-message">
                No active washing machines available.
            </div>

        <?php endif; ?>

    </section>


    <!-- Dryers -->

    <section class="machine-section">

        <h2>Dryers</h2>

        <?php if (count($dryers) > 0): ?>

            <div class="machine-grid">

                <?php foreach ($dryers as $machine): ?>

                    <div class="machine-card">

                        <h3>
                            <?php echo htmlspecialchars($machine["machine_name"]); ?>
                        </h3>

                        <p>
                            <strong>Type:</strong>
                            <?php echo htmlspecialchars($machine["machine_type"]); ?>
                        </p>

                        <p>
                            <strong>Status:</strong>

                            <span class="status-active">
                                <?php echo htmlspecialchars($machine["status"]); ?>
                            </span>
                        </p>

                        <a
                            href="availability.php?machine_id=<?php echo $machine["id"]; ?>"
                        >
                            View Availability
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-message">
                No active dryers available.
            </div>

        <?php endif; ?>

    </section>


</div>

</body>

</html>