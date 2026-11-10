
<?php

/* LaundryWise file overview: Admin workflow for reviewing pending registration requests and approving or rejecting access according to the application’s stored request status. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Admin authentication
if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$error = "";

// Generate unique Resident ID
function generateResidentId($conn)
{
    do {
        $characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

        $part1 = "";
        $part2 = "";

        for ($i = 0; $i < 4; $i++) {
            $part1 .= $characters[
                random_int(0, strlen($characters) - 1)
            ];
        }

        for ($i = 0; $i < 4; $i++) {
            $part2 .= $characters[
                random_int(0, strlen($characters) - 1)
            ];
        }

        $resident_id = "LW-" . $part1 . "-" . $part2;

        $stmt = $conn->prepare(
            "SELECT id
             FROM resident_access
             WHERE resident_id = ?
             LIMIT 1"
        );

        /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
        $stmt->bind_param("s", $resident_id);
        $stmt->execute();

        $result = $stmt->get_result();

    } while ($result->num_rows > 0);

    return $resident_id;
}

// Send Resident ID email
function sendResidentIdEmail(
    $full_name,
    $email,
    $resident_id
) {
    $mailConfig = require __DIR__ . "/config/mail.php";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

        $mail->Host = $mailConfig["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig["username"];
        $mail->Password = $mailConfig["password"];
        $mail->SMTPSecure = $mailConfig["encryption"];
        $mail->Port = $mailConfig["port"];

        $mail->setFrom(
            $mailConfig["from_email"],
            $mailConfig["from_name"]
        );

        $mail->addAddress($email, $full_name);

        $mail->isHTML(true);

        $mail->Subject = "Your LaundryWise Resident ID";

        $safe_name = htmlspecialchars(
            $full_name,
            ENT_QUOTES,
            "UTF-8"
        );

        $safe_resident_id = htmlspecialchars(
            $resident_id,
            ENT_QUOTES,
            "UTF-8"
        );

        $mail->Body = "
            <div style=\"
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: auto;
                padding: 30px;
                color: #302A46;
                border: 1px solid #E8E3F4;
                border-radius: 16px;
            \">

                <h1 style=\"
                    color: #35256F;
                    margin-bottom: 25px;
                \">
                    LaundryWise
                </h1>

                <p>Hello {$safe_name},</p>

                <p>
                    Your registration request has been approved.
                </p>

                <p>
                    Your unique LaundryWise Resident ID is:
                </p>

                <div style=\"
                    background: #F0EDFF;
                    padding: 22px;
                    text-align: center;
                    border-radius: 12px;
                    margin: 22px 0;
                    color: #35256F;
                \">

                    <strong style=\"
                        font-size: 24px;
                        letter-spacing: 2px;
                    \">
                        {$safe_resident_id}
                    </strong>

                </div>

                <p>
                    You can now log in to LaundryWise using this
                    Resident ID and the password you created
                    during registration.
                </p>

                <p>Please keep your Resident ID safe.</p>

                <p>
                    Regards,<br>
                    <strong>LaundryWise</strong>
                </p>

            </div>
        ";

        $mail->AltBody =
            "Hello {$full_name},\n\n" .
            "Your registration request has been approved.\n\n" .
            "Your LaundryWise Resident ID is: {$resident_id}\n\n" .
            "You can now log in using your Resident ID and " .
            "the password you created during registration.\n\n" .
            "Regards,\nLaundryWise";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}

// Approve registration
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "approve"
) {
    $request_id = (int)($_POST["request_id"] ?? 0);

    if ($request_id <= 0) {
        $error = "Invalid registration request.";

    } else {
        // Get pending request
        $stmt = $conn->prepare(
            "SELECT
                id,
                full_name,
                email,
                username,
                password_hash,
                email_notifications_enabled,
                status
             FROM resident_requests
             WHERE id = ?
             AND status = 'pending'
             LIMIT 1"
        );

        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        if (!$request) {
            $error =
                "This registration request no longer exists " .
                "or has already been processed.";

        } else {
            // Check email is not already registered
            $stmt = $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->bind_param("s", $request["email"]);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error =
                    "This email address is already associated " .
                    "with a user account.";

            } else {
                // Check username is not already registered
                $stmt = $conn->prepare(
                    "SELECT id
                     FROM users
                     WHERE username = ?
                     LIMIT 1"
                );

                $stmt->bind_param("s", $request["username"]);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = "This username is already in use.";

                } else {
                    // Generate Resident ID
                    $resident_id = generateResidentId($conn);

                    // Start database transaction
                    /* Explanation: Starts a database transaction so related booking updates either succeed together or can be rolled back together. */
                    $conn->begin_transaction();

                    try {
                        // Create user account
                        $role = "resident";

                        $stmt = $conn->prepare(
                            "INSERT INTO users
                            (
                                resident_id,
                                name,
                                username,
                                email,
                                password,
                                role,
                                email_notifications_enabled
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->bind_param(
                            "ssssssi",
                            $resident_id,
                            $request["full_name"],
                            $request["username"],
                            $request["email"],
                            $request["password_hash"],
                            $role,
                            $request["email_notifications_enabled"]
                        );

                        if (!$stmt->execute()) {
                            throw new Exception(
                                "Unable to create user account."
                            );
                        }

                        // Create resident access record
                        $is_used = 1;

                        $stmt = $conn->prepare(
                            "INSERT INTO resident_access
                            (
                                resident_id,
                                full_name,
                                email,
                                is_used
                            )
                            VALUES (?, ?, ?, ?)"
                        );

                        $stmt->bind_param(
                            "sssi",
                            $resident_id,
                            $request["full_name"],
                            $request["email"],
                            $is_used
                        );

                        if (!$stmt->execute()) {
                            throw new Exception(
                                "Unable to create Resident ID record."
                            );
                        }

                        // Mark request as approved
                        $approved_status = "approved";

                        $stmt = $conn->prepare(
                            "UPDATE resident_requests
                             SET status = ?
                             WHERE id = ?
                             AND status = 'pending'"
                        );

                        $stmt->bind_param(
                            "si",
                            $approved_status,
                            $request_id
                        );

                        if (!$stmt->execute()) {
                            throw new Exception(
                                "Unable to update registration request."
                            );
                        }

                        if ($stmt->affected_rows !== 1) {
                            throw new Exception(
                                "The registration request has already been processed."
                            );
                        }

                        // Commit database changes
                        $conn->commit();

                        // Send Resident ID email
                        $email_sent = sendResidentIdEmail(
                            $request["full_name"],
                            $request["email"],
                            $resident_id
                        );

                        if ($email_sent) {
                            $message =
                                "Registration approved successfully. " .
                                "Resident ID " .
                                $resident_id .
                                " has been sent to " .
                                $request["email"] . ".";

                        } else {
                            $error =
                                "Registration was approved and the " .
                                "Resident ID was created, but the email " .
                                "could not be sent. Please check the " .
                                "email configuration.";
                        }

                    } catch (Exception $e) {
                        $conn->rollback();

                        $error =
                            "Approval failed. No account was created. " .
                            "Please try again.";
                    }
                }
            }
        }
    }
}

// Reject registration
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "reject"
) {
    $request_id = (int)($_POST["request_id"] ?? 0);

    if ($request_id <= 0) {
        $error = "Invalid registration request.";

    } else {
        $stmt = $conn->prepare(
            "UPDATE resident_requests
             SET status = 'rejected'
             WHERE id = ?
             AND status = 'pending'"
        );

        $stmt->bind_param("i", $request_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Registration request rejected.";

        } else {
            $error =
                "Unable to reject this registration request.";
        }
    }
}

// Load registration requests
$requests = [];

$result = $conn->query(
    "SELECT
        id,
        full_name,
        email,
        username,
        email_notifications_enabled,
        status,
        created_at
     FROM resident_requests
     ORDER BY created_at DESC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registration Requests - LaundryWise</title>

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

        .requests-container {
            display: block;
            width: calc(100% - 40px);
            max-width: 1350px;
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
            font-size: 14px;
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
            align-items: center;
            gap: 8px;
            width: 100%;
            margin-bottom: 30px;
            padding: 12px;
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
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease;
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

        .requests-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
        }

        .request-count {
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

        .requests-card {
            display: block;
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 24px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(53, 37, 111, 0.04);
        }

        .success-message,
        .error-message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            padding: 15px 18px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .success-message {
            background: #EAF7EF;
            border: 1px solid #D0EBDD;
            color: #267447;
        }

        .error-message {
            background: #FCECEC;
            border: 1px solid #F2D0D0;
            color: #A33C45;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            background: var(--white);
            text-align: left;
        }

        thead {
            background: #F3F0FF;
        }

        th {
            padding: 15px;
            color: var(--deep-purple);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-top: 1px solid #F0EDF7;
            color: var(--text);
            font-size: 13px;
            line-height: 1.5;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #FAF9FF;
        }

        .person-name {
            font-weight: 700;
            white-space: nowrap;
        }

        .email-cell {
            overflow-wrap: anywhere;
        }

        .username-cell {
            font-weight: 600;
        }

        .notification-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 11px;
            font-weight: 700;
        }

        .notification-badge.disabled {
            background: #F3F0F6;
            color: #746B81;
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
        }

        .status-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-pending {
            background: #FFF5DD;
            color: #8A6414;
        }

        .status-pending::before {
            background: #D8A02C;
        }

        .status-approved {
            background: #EAF7EF;
            color: #267447;
        }

        .status-approved::before {
            background: #35A568;
        }

        .status-rejected {
            background: #FCECEC;
            color: #A33C45;
        }

        .status-rejected::before {
            background: #D65A64;
        }

        .submitted-cell {
            min-width: 145px;
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .action-cell {
            min-width: 180px;
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-form {
            display: inline-flex;
            margin: 0;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 9px 12px;
            border: 1px solid transparent;
            border-radius: 8px;
            color: var(--white);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .action-button:hover {
            transform: translateY(-1px);
        }

        .approve-button {
            background: linear-gradient(135deg, #51418B, #7561B5);
        }

        .approve-button:hover {
            box-shadow: 0 4px 10px rgba(81, 65, 139, 0.2);
        }

        .reject-button {
            background: var(--white);
            border-color: #E7C8CD;
            color: #A33C45;
        }

        .reject-button:hover {
            background: #FCECEC;
        }

        .processed-label {
            color: var(--muted);
            font-size: 13px;
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
            line-height: 1.6;
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
            .requests-container {
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

            .requests-summary {
                align-items: flex-start;
            }

            .requests-card {
                padding: 14px;
            }
        }
    </style>
</head>

<body>

<div class="requests-container">

    <!-- Page header -->
    <header class="dashboard-header">

        <div>
            <h1 class="brand">LaundryWise</h1>

            <h2 class="page-title">Registration Requests</h2>

            <p class="welcome-text">
                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"]); ?>.
                Review resident applications and manage registration approvals.
            </p>
        </div>

        <div class="admin-label">Administrator</div>

    </header>

    <!-- Admin navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">

        <a href="admin_dashboard.php">
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

        <a href="admin_registration_requests.php" class="active">
            Registration Requests

            <?php
            $pending_count = 0;

            foreach ($requests as $request_item) {
                if ($request_item["status"] === "pending") {
                    $pending_count++;
                }
            }
            ?>

            <?php if ($pending_count > 0): ?>
                <span class="pending-badge">
                    <?php echo (int)$pending_count; ?>
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

    <!-- Feedback messages -->
    <?php if ($message !== ""): ?>

        <div class="success-message" role="status">
            <span aria-hidden="true">✓</span>
            <div>
                <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
            </div>
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="error-message" role="alert">
            <span aria-hidden="true">!</span>
            <div>
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- Registration request list -->
    <section>

        <div class="requests-summary">

            <div>
                <h2 class="section-heading">All Registration Requests</h2>

                <p class="section-description">
                    Review applicant details, notification preferences and request status.
                </p>
            </div>

            <div class="request-count"
                 title="Total registration requests"
                 aria-label="Total registration requests">
                <?php echo count($requests); ?>
            </div>

        </div>

        <div class="requests-card">

            <?php if (count($requests) === 0): ?>

                <div class="empty-message">

                    <div class="empty-icon" aria-hidden="true">✓</div>

                    <h3>No registration requests</h3>

                    <p>
                        New resident registration requests will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-wrapper">

                    <table>

                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Notifications</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($requests as $request): ?>

                                <?php
                                $status = $request["status"];

                                if ($status === "pending") {
                                    $status_class = "status-pending";
                                } elseif ($status === "approved") {
                                    $status_class = "status-approved";
                                } else {
                                    $status_class = "status-rejected";
                                }
                                ?>

                                <tr>

                                    <td class="person-name">
                                        <?php
                                        echo htmlspecialchars(
                                            $request["full_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td class="email-cell">
                                        <?php
                                        echo htmlspecialchars(
                                            $request["email"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td class="username-cell">
                                        <?php
                                        echo htmlspecialchars(
                                            $request["username"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            (int)$request["email_notifications_enabled"] === 1
                                        ): ?>

                                            <span class="notification-badge">
                                                Enabled
                                            </span>

                                        <?php else: ?>

                                            <span class="notification-badge disabled">
                                                Disabled
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                ucfirst($status),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>
                                        </span>
                                    </td>

                                    <td class="submitted-cell">
                                        <?php
                                        echo htmlspecialchars(
                                            $request["created_at"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td class="action-cell">

                                        <?php if ($status === "pending"): ?>

                                            <div class="action-group">

                                                <form
                                                    method="POST"
                                                    action="admin_registration_requests.php"
                                                    class="action-form"
                                                    onsubmit="return confirm('Approve this registration request?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="approve"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="request_id"
                                                        value="<?php echo (int)$request["id"]; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="action-button approve-button"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>

                                                <form
                                                    method="POST"
                                                    action="admin_registration_requests.php"
                                                    class="action-form"
                                                    onsubmit="return confirm('Reject this registration request?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="reject"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="request_id"
                                                        value="<?php echo (int)$request["id"]; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="action-button reject-button"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>

                                            </div>

                                        <?php else: ?>

                                            <span class="processed-label">
                                                Processed
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <footer class="dashboard-footer">
        LaundryWise · Administration Panel
    </footer>

</div>

</body>
</html>
