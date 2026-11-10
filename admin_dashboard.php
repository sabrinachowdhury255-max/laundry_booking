
<?php

/* LaundryWise file overview: Admin overview page that gathers summary information from the database and presents the system-management navigation. */


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
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
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

// Count pending registration requests
$sql = "SELECT COUNT(*) AS total
        FROM resident_requests
        WHERE status = 'pending'";

$result = $conn->query($sql);
$pending_requests = $result->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - LaundryWise</title>

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
            padding: 0;
            background: #F8F7FC;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }

        /* Main dashboard container */
        .container {
            display: block;
            width: calc(100% - 40px);
            max-width: 1250px;
            margin: 30px auto;
            padding: 30px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .brand {
            margin: 0 0 8px;
            color: var(--deep-purple);
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .page-title {
            margin: 0 0 10px;
            color: var(--text);
            font-size: 25px;
        }

        .welcome-text {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .admin-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 15px;
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

        /* Navigation */
        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            width: 100%;
            margin: 0 0 30px;
            padding: 12px;
            background: #F8F6FD;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .admin-nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 14px;
            border-radius: 9px;
            color: var(--purple);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
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

        .pending-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 21px;
            height: 21px;
            padding: 0 6px;
            border-radius: 20px;
            background: #F2B85B;
            color: #39254D;
            font-size: 11px;
            font-weight: 800;
        }

        /* Section headings */
        .section-heading {
            margin: 0 0 8px;
            color: var(--deep-purple);
            font-size: 22px;
            line-height: 1.4;
        }

        .section-description {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Resident access section */
        .access-section {
            display: block;
            width: 100%;
            margin-bottom: 34px;
            padding: 25px;
            background: linear-gradient(135deg, #FCFBFF, #F4F0FF);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .admin-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .admin-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 45px;
            padding: 12px 17px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: linear-gradient(135deg, #51418B, #7561B5);
            color: var(--white);
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .admin-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(81, 65, 139, 0.2);
        }

        .admin-link.secondary {
            background: var(--white);
            border-color: #DCD3F4;
            color: var(--deep-purple);
        }

        .admin-link.secondary:hover {
            background: var(--lavender);
        }

        .pending-text {
            font-size: 12px;
        }

        /* System overview */
        .overview-section {
            display: block;
            width: 100%;
            min-width: 0;
        }

        .overview-heading {
            display: block;
            width: 100%;
            margin-bottom: 22px;
        }

        /*
         * Explicit grid sizing prevents the statistic cards
         * from shrinking to the width of their contents.
         */
        .admin-stats {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            align-items: stretch;
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 0;
        }

        .admin-card {
            display: block;
            position: relative;
            width: 100%;
            min-width: 0;
            max-width: none;
            min-height: 220px;
            margin: 0;
            padding: 24px;
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(53, 37, 111, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .admin-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #51418B, #B6A6EE);
        }

        .admin-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            margin: 3px 0 20px;
            border-radius: 14px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 23px;
        }

        .admin-card h3 {
            display: block;
            width: 100%;
            min-height: 42px;
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .admin-number {
            display: block;
            width: 100%;
            margin: 0;
            padding: 0;
            color: var(--deep-purple);
            font-size: 36px;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
            overflow-wrap: normal;
            word-break: normal;
        }

        /* Footer */
        .dashboard-footer {
            width: 100%;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        /* Tablet */
        @media (max-width: 950px) {
            .admin-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-card {
                min-height: 205px;
            }
        }

        /* Mobile */
        @media (max-width: 550px) {
            .container {
                width: calc(100% - 24px);
                margin: 12px auto;
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

            .access-section {
                padding: 18px;
            }

            .admin-links {
                align-items: stretch;
                flex-direction: column;
            }

            .admin-link {
                width: 100%;
            }

            .admin-stats {
                grid-template-columns: minmax(0, 1fr);
                gap: 15px;
            }

            .admin-card {
                min-height: auto;
                padding: 22px;
            }

            .admin-card h3 {
                min-height: 0;
            }

            .admin-number {
                font-size: 34px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Dashboard header -->
    <header class="dashboard-header">

        <div>
            <h1 class="brand">LaundryWise</h1>

            <h2 class="page-title">Admin Dashboard</h2>

            <p class="welcome-text">
                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"]); ?>.
                Here's your laundry system overview.
            </p>
        </div>

        <div class="admin-label">Administrator</div>

    </header>

    <!-- Admin navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">

        <a href="admin_dashboard.php" class="active">
            Dashboard
        </a>

        <a href="admin_residents.php">
            Residents
        </a>

        <a href="admin_machines.php">
            Machines
        </a>

        <a href="admin_bookings.php">
            Bookings
        </a>

        <a href="admin_registration_requests.php">
            Registration Requests

            <?php if ((int)$pending_requests > 0): ?>
                <span class="pending-badge">
                    <?php echo (int)$pending_requests; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="admin_resident_access.php">
            Resident Access
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

    <!-- Resident access management -->
    <section class="access-section">

        <h2 class="section-heading">
            Resident Access Management
        </h2>

        <p class="section-description">
            Review resident registration requests and manage access to LaundryWise.
        </p>

        <div class="admin-links">

            <a href="admin_registration_requests.php" class="admin-link">

                Registration Requests

                <?php if ((int)$pending_requests > 0): ?>
                    <span class="pending-text">
                        (<?php echo (int)$pending_requests; ?> pending)
                    </span>
                <?php endif; ?>

            </a>

            <a href="admin_resident_access.php" class="admin-link secondary">
                Resident Access Management
            </a>

        </div>

    </section>

    <!-- System overview -->
    <section class="overview-section">

        <div class="overview-heading">

            <h2 class="section-heading">
                System Overview
            </h2>

            <p class="section-description">
                A summary of residents, machine availability, bookings and registration requests.
            </p>

        </div>

        <div class="admin-stats">

            <!-- Registered residents -->
            <article class="admin-card">

                <div class="stat-icon" aria-hidden="true">
                    ♙
                </div>

                <h3>Registered Residents</h3>

                <p class="admin-number">
                    <?php echo (int)$resident_count; ?>
                </p>

            </article>

            <!-- Active machines -->
            <article class="admin-card">

                <div class="stat-icon" aria-hidden="true">
                    ▣
                </div>

                <h3>Active Machines</h3>

                <p class="admin-number">
                    <?php echo (int)$machine_count; ?>
                </p>

            </article>

            <!-- Confirmed bookings -->
            <article class="admin-card">

                <div class="stat-icon" aria-hidden="true">
                    ▤
                </div>

                <h3>Confirmed Bookings</h3>

                <p class="admin-number">
                    <?php echo (int)$booking_count; ?>
                </p>

            </article>

            <!-- Pending registration requests -->
            <article class="admin-card">

                <div class="stat-icon" aria-hidden="true">
                    ◷
                </div>

                <h3>Pending Registration Requests</h3>

                <p class="admin-number">
                    <?php echo (int)$pending_requests; ?>
                </p>

            </article>

        </div>

    </section>

    <footer class="dashboard-footer">
        LaundryWise · Administration Panel
    </footer>

</div>

</body>
</html>
