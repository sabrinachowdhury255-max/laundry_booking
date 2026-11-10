
<?php

/* LaundryWise file overview: Admin page for viewing and managing booking records. The database status is used to distinguish confirmed and cancelled bookings. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Management - LaundryWise Admin</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        :root {
            --lavender: #F0EDFF;
            --lavender-hover: #E2D9FF;
            --purple: #51418B;
            --deep-purple: #35256F;
            --white: #FFFFFF;
            --text: #302A46;
            --muted: #77718C;
            --border: #E8E3F4;
            --shadow: 0 8px 24px rgba(53, 37, 111, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #F8F7FC;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            width: min(1350px, 96%);
            margin: 30px auto;
            padding: 30px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 26px;
        }

        .brand {
            margin: 0 0 8px;
            color: var(--deep-purple);
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .page-title {
            margin: 0 0 9px;
            color: var(--text);
            font-size: 25px;
        }

        .welcome-text {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .admin-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 12px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .admin-label::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #7965C5;
        }

        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 12px;
            margin-bottom: 30px;
            background: #F8F6FD;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .admin-nav a {
            display: inline-flex;
            align-items: center;
            padding: 11px 14px;
            border-radius: 9px;
            color: var(--purple);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .admin-nav a:hover {
            background: var(--lavender-hover);
            color: var(--deep-purple);
        }

        .admin-nav a.active {
            background: linear-gradient(135deg, #51418B, #7561B5);
            color: var(--white);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
        }

        .section-heading {
            margin: 0 0 8px;
            color: var(--deep-purple);
            font-size: 22px;
        }

        .section-description {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .booking-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            padding: 0 12px;
            border-radius: 12px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 16px;
            font-weight: 800;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            text-align: left;
        }

        thead {
            background: #F3F0FF;
        }

        th {
            padding: 16px 15px;
            color: var(--deep-purple);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        td {
            padding: 16px 15px;
            border-top: 1px solid #F0EDF7;
            color: var(--text);
            font-size: 13px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #FAF9FF;
        }

        .booking-id,
        .resident-id {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 7px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .resident-name,
        .machine-name {
            font-weight: 700;
            white-space: nowrap;
        }

        .date-cell,
        .time-cell {
            white-space: nowrap;
        }

        .time-cell {
            color: var(--purple);
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            text-transform: capitalize;
        }

        .status-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-confirmed {
            background: #EAF7EF;
            color: #267447;
        }

        .status-confirmed::before {
            background: #35A568;
        }

        .status-pending {
            background: #FFF5DD;
            color: #8A6414;
        }

        .status-pending::before {
            background: #D8A02C;
        }

        .status-cancelled {
            background: #FCECEC;
            color: #A33C45;
        }

        .status-cancelled::before {
            background: #D65A64;
        }

        .status-other {
            background: #F0EDF7;
            color: #6F638A;
        }

        .status-other::before {
            background: #9C8DB7;
        }

        .created-cell {
            min-width: 150px;
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .empty-message {
            padding: 45px 20px;
            border: 1px dashed #D8D0EE;
            border-radius: 14px;
            background: #FCFBFF;
            text-align: center;
        }

        .empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            margin: 0 auto 15px;
            border-radius: 16px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 25px;
        }

        .empty-message h3 {
            margin: 0 0 8px;
            color: var(--deep-purple);
            font-size: 17px;
        }

        .empty-message p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .dashboard-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 700px) {
            .container {
                width: 95%;
                margin: 15px auto;
                padding: 18px;
                border-radius: 15px;
            }

            .dashboard-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .brand {
                font-size: 26px;
            }

            .page-title {
                font-size: 22px;
            }

            .admin-nav {
                gap: 5px;
                padding: 8px;
            }

            .admin-nav a {
                padding: 10px;
                font-size: 12px;
            }

            .section-header {
                align-items: flex-start;
            }

            th,
            td {
                padding: 13px 12px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <header class="dashboard-header">
        <div>
            <h1 class="brand">LaundryWise</h1>
            <h2 class="page-title">Booking Management</h2>

            <p class="welcome-text">
                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"]); ?>.
                Review resident bookings and their current status.
            </p>
        </div>

        <div class="admin-label">Administrator</div>
    </header>

    <!-- Admin navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">

        <a href="admin_dashboard.php">Dashboard</a>

        <a href="admin_residents.php">Residents</a>

        <a href="admin_machines.php">Machines</a>

        <a href="admin_bookings.php" class="active">Bookings</a>

        <a href="logout.php">Logout</a>

    </nav>

    <!-- Booking list -->
    <section>

        <div class="section-header">
            <div>
                <h2 class="section-heading">All Bookings</h2>

                <p class="section-description">
                    Review booking details, resident information and machine schedules.
                </p>
            </div>

            <div class="booking-count"
                 title="Number of bookings"
                 aria-label="Booking count">
                <?php echo (int)$result->num_rows; ?>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>

            <div class="table-wrapper">

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

                            <?php
                            $status_class = "status-other";

                            if ($booking["status"] === "confirmed") {
                                $status_class = "status-confirmed";
                            } elseif ($booking["status"] === "pending") {
                                $status_class = "status-pending";
                            } elseif (
                                $booking["status"] === "cancelled" ||
                                $booking["status"] === "canceled"
                            ) {
                                $status_class = "status-cancelled";
                            }
                            ?>

                            <tr>

                                <td>
                                    <span class="booking-id">
                                        <?php echo (int)$booking["id"]; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="resident-id">
                                        <?php echo htmlspecialchars($booking["resident_id"]); ?>
                                    </span>
                                </td>

                                <td class="resident-name">
                                    <?php echo htmlspecialchars($booking["name"]); ?>
                                </td>

                                <td class="machine-name">
                                    <?php echo htmlspecialchars($booking["machine_name"]); ?>
                                </td>

                                <td class="date-cell">
                                    <?php echo htmlspecialchars($booking["booking_date"]); ?>
                                </td>

                                <td class="time-cell">
                                    <?php echo htmlspecialchars(substr($booking["start_time"], 0, 5)); ?>
                                </td>

                                <td class="time-cell">
                                    <?php echo htmlspecialchars(substr($booking["end_time"], 0, 5)); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($booking["status"]); ?>
                                    </span>
                                </td>

                                <td class="created-cell">
                                    <?php echo htmlspecialchars($booking["created_at"]); ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-message">
                <div class="empty-icon" aria-hidden="true">▤</div>

                <h3>No bookings found</h3>

                <p>
                    Booking records will appear here when residents make bookings.
                </p>
            </div>

        <?php endif; ?>

    </section>

    <footer class="dashboard-footer">
        LaundryWise · Administration Panel
    </footer>

</div>

</body>
</html>
