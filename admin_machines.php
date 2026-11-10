
<?php

/* LaundryWise file overview: Admin-only page for viewing machines and changing their active/inactive status. Inactive machines should not be offered for new resident bookings. */


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
        /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Machine Management - LaundryWise Admin</title>

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
            width: min(1180px, 94%);
            margin: 35px auto;
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
            font-size: 25px;
            color: var(--text);
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

        .machine-count {
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
            padding: 16px 20px;
            color: var(--deep-purple);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        td {
            padding: 16px 20px;
            border-top: 1px solid #F0EDF7;
            color: var(--text);
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #FAF9FF;
        }

        .machine-id {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            padding: 6px 9px;
            border-radius: 7px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 12px;
            font-weight: 700;
        }

        .machine-name {
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-active {
            background: #EAF7EF;
            color: #267447;
        }

        .status-active::before {
            background: #35A568;
        }

        .status-inactive {
            background: #F3F0F6;
            color: #746B81;
        }

        .status-inactive::before {
            background: #A49AAF;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 240px;
        }

        .status-form select {
            min-width: 125px;
            padding: 10px 12px;
            border: 1px solid #DCD5ED;
            border-radius: 9px;
            outline: none;
            background: #FFFFFF;
            color: var(--text);
            font-size: 13px;
            cursor: pointer;
        }

        .status-form select:focus {
            border-color: #8A78C7;
            box-shadow: 0 0 0 3px rgba(138, 120, 199, 0.13);
        }

        .update-button {
            padding: 10px 15px;
            border: none;
            border-radius: 9px;
            background: linear-gradient(135deg, #51418B, #7561B5);
            color: white;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .update-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(81, 65, 139, 0.2);
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

            th {
                font-size: 11px;
            }

            td {
                font-size: 13px;
            }

            .status-form {
                align-items: stretch;
                flex-direction: column;
                min-width: 130px;
            }

            .status-form select,
            .update-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <header class="dashboard-header">
        <div>
            <h1 class="brand">LaundryWise</h1>
            <h2 class="page-title">Machine Management</h2>

            <p class="welcome-text">
                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"]); ?>.
                Manage machine status across your laundry system.
            </p>
        </div>

        <div class="admin-label">Administrator</div>
    </header>

    <!-- Admin navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">

        <a href="admin_dashboard.php">Dashboard</a>

        <a href="admin_residents.php">Residents</a>

        <a href="admin_machines.php" class="active">Machines</a>

        <a href="admin_bookings.php">Bookings</a>

        <a href="logout.php">Logout</a>

    </nav>

    <!-- Machine list -->
    <section>

        <div class="section-header">
            <div>
                <h2 class="section-heading">All Machines</h2>

                <p class="section-description">
                    View each machine and update whether it is active or inactive.
                </p>
            </div>

            <div class="machine-count"
                 title="Number of machines"
                 aria-label="Machine count">
                <?php echo (int)$result->num_rows; ?>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Machine</th>
                            <th>Current Status</th>
                            <th>Change Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($machine = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <span class="machine-id">
                                        <?php echo (int)$machine["id"]; ?>
                                    </span>
                                </td>

                                <td class="machine-name">
                                    <?php echo htmlspecialchars($machine["machine_name"]); ?>
                                </td>

                                <td>
                                    <?php if ($machine["status"] === "active"): ?>

                                        <span class="status-badge status-active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>

                                    <form method="POST"
                                          action="admin_machines.php"
                                          class="status-form">

                                        <input
                                            type="hidden"
                                            name="machine_id"
                                            value="<?php echo (int)$machine["id"]; ?>"
                                        >

                                        <select name="status" aria-label="New status for <?php echo htmlspecialchars($machine["machine_name"], ENT_QUOTES, 'UTF-8'); ?>">

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

                                        <button type="submit" class="update-button">
                                            Update
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-message">
                <div class="empty-icon" aria-hidden="true">▣</div>

                <h3>No machines found</h3>

                <p>
                    Machines will appear here when they are available in the system.
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
