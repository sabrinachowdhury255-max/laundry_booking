
<?php

/* LaundryWise file overview: Admin interface for reviewing resident records and performing the resident-management actions implemented in this page. */


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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Residents - LaundryWise Admin</title>

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
            gap: 7px;
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

        .residents-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
        }

        .resident-count {
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
            padding: 17px 20px;
            border-top: 1px solid #F0EDF7;
            color: var(--text);
            font-size: 14px;
            line-height: 1.5;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #FAF9FF;
        }

        .resident-id {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 7px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 12px;
            font-weight: 700;
        }

        .resident-name {
            font-weight: 700;
        }

        .resident-email {
            color: var(--muted);
            overflow-wrap: anywhere;
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

        @media (max-width: 650px) {
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

            .residents-header {
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
        }
    </style>
</head>

<body>

<div class="container">

    <header class="dashboard-header">
        <div>
            <h1 class="brand">LaundryWise</h1>
            <h2 class="page-title">Residents</h2>

            <p class="welcome-text">
                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"]); ?>.
                Manage and review registered residents.
            </p>
        </div>

        <div class="admin-label">Administrator</div>
    </header>

    <!-- Admin navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">

        <a href="admin_dashboard.php">
            Dashboard
        </a>

        <a href="admin_residents.php" class="active">
            Residents
        </a>

        <a href="admin_machines.php">
            Machines
        </a>

        <a href="admin_bookings.php">
            Bookings
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

    <!-- Residents list -->
    <section>

        <div class="residents-header">
            <div>
                <h2 class="section-heading">Registered Residents</h2>

                <p class="section-description">
                    View resident IDs, names and email addresses.
                </p>
            </div>

            <div class="resident-count"
                 title="Number of residents"
                 aria-label="Resident count">
                <?php echo (int)$result->num_rows; ?>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Resident ID</th>
                            <th>Name</th>
                            <th>Email Address</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($resident = $result->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <span class="resident-id">
                                        <?php echo htmlspecialchars($resident["resident_id"]); ?>
                                    </span>
                                </td>

                                <td class="resident-name">
                                    <?php echo htmlspecialchars($resident["name"]); ?>
                                </td>

                                <td class="resident-email">
                                    <?php echo htmlspecialchars($resident["email"]); ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-message">
                <div class="empty-icon" aria-hidden="true">♙</div>

                <h3>No residents found</h3>

                <p>
                    Registered residents will appear here when available.
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
