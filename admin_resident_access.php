
<?php

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

// Generate a unique Resident ID
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

        $check = $conn->prepare(
            "SELECT id
             FROM resident_access
             WHERE resident_id = ?
             LIMIT 1"
        );

        /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
        $check->bind_param("s", $resident_id);
        $check->execute();

        $result = $check->get_result();

    } while ($result->num_rows > 0);

    return $resident_id;
}

// Send Resident ID email
function sendResidentAccessEmail($full_name, $email, $resident_id)
{
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
        $mail->Subject = "Your LaundryWise Resident Access ID";

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
            <div style='
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: auto;
                padding: 30px;
                color: #302A46;
                border: 1px solid #E8E3F4;
                border-radius: 16px;
            '>

                <h1 style='color: #35256F;'>
                    LaundryWise
                </h1>

                <p>Hello {$safe_name},</p>

                <p>
                    Your resident access for LaundryWise has been
                    created by your accommodation administrator.
                </p>

                <p>Your unique Resident ID is:</p>

                <div style='
                    background: #F0EDFF;
                    padding: 22px;
                    text-align: center;
                    border-radius: 12px;
                    margin: 20px 0;
                    color: #35256F;
                '>

                    <strong style='
                        font-size: 24px;
                        letter-spacing: 2px;
                    '>
                        {$safe_resident_id}
                    </strong>

                </div>

                <p>
                    Use this Resident ID and the email address
                    registered with your accommodation when creating
                    your LaundryWise account.
                </p>

                <p>
                    Your Resident ID is unique and cannot be reused
                    after your account has been activated.
                </p>

                <p>
                    If you did not expect this email, please contact
                    your accommodation administrator.
                </p>

                <p>
                    Regards,<br>
                    <strong>LaundryWise</strong>
                </p>

            </div>
        ";

        $mail->AltBody =
            "Hello {$full_name},\n\n" .
            "Your LaundryWise Resident ID is: {$resident_id}\n\n" .
            "Use this Resident ID and your registered email address " .
            "to create your LaundryWise account.\n\n" .
            "Regards,\nLaundryWise";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}

// Issue Resident Access
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($full_name === "" || $email === "") {

        $error =
            "Please enter the resident's full name and email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        // Check for an existing unused access record
        $check = $conn->prepare(
            "SELECT id
             FROM resident_access
             WHERE email = ?
             AND is_used = 0
             LIMIT 1"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error =
                "An unused Resident ID has already been issued " .
                "for this email address.";

        } else {

            // Generate Resident ID
            $resident_id = generateResidentId($conn);

            // Create access record
            $stmt = $conn->prepare(
                "INSERT INTO resident_access
                (
                    resident_id,
                    full_name,
                    email,
                    is_used
                )
                VALUES (?, ?, ?, 0)"
            );

            $stmt->bind_param(
                "sss",
                $resident_id,
                $full_name,
                $email
            );

            if ($stmt->execute()) {

                // Send email
                $email_sent = sendResidentAccessEmail(
                    $full_name,
                    $email,
                    $resident_id
                );

                if ($email_sent) {

                    $message =
                        "Resident access created successfully. " .
                        "The Resident ID has been sent to " .
                        $email . ".";

                } else {

                    $error =
                        "Resident access was created, but the email " .
                        "could not be sent. Please check the email " .
                        "configuration before trying again.";
                }

            } else {

                $error =
                    "Unable to create resident access. Please try again.";
            }
        }
    }
}

// Get existing access records
$access_records = [];

$result = $conn->query(
    "SELECT
        id,
        resident_id,
        full_name,
        email,
        is_used,
        created_at
     FROM resident_access
     ORDER BY created_at DESC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $access_records[] = $row;
    }
}

// Summary counts
$total_access = count($access_records);
$activated_count = 0;
$unused_count = 0;

foreach ($access_records as $record) {
    if ((int)$record["is_used"] === 1) {
        $activated_count++;
    } else {
        $unused_count++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Resident Access - LaundryWise Admin</title>

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

        .access-container {
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

        /* Summary cards */
        .access-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            width: 100%;
            margin-bottom: 30px;
        }

        .stat-card {
            position: relative;
            min-width: 0;
            padding: 22px;
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(53, 37, 111, 0.04);
        }

        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #51418B, #B6A6EE);
        }

        .stat-label {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .stat-number {
            margin: 0;
            color: var(--deep-purple);
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
        }

        /* Main cards */
        .access-card {
            display: block;
            width: 100%;
            min-width: 0;
            margin-bottom: 26px;
            padding: 26px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(53, 37, 111, 0.04);
        }

        .access-card:last-child {
            margin-bottom: 0;
        }

        .card-heading {
            margin: 0 0 8px;
            color: var(--deep-purple);
            font-size: 20px;
        }

        .card-description {
            margin: 0 0 23px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Form */
        .access-form {
            width: 100%;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            min-width: 0;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: var(--text);
            font-size: 13px;
            font-weight: 700;
        }

        .form-group input {
            display: block;
            width: 100%;
            min-width: 0;
            padding: 13px 14px;
            border: 1px solid #DCD5ED;
            border-radius: 10px;
            outline: none;
            background: #FFFFFF;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input::placeholder {
            color: #A49AAF;
        }

        .form-group input:focus {
            border-color: #8A78C7;
            box-shadow: 0 0 0 3px rgba(138, 120, 199, 0.13);
        }

        .form-help {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 46px;
            padding: 13px 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #51418B, #7561B5);
            color: var(--white);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .primary-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(81, 65, 139, 0.2);
        }

        /* Feedback messages */
        .success-message,
        .error-message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 22px;
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

        /* Access records table */
        .records-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
        }

        .record-count {
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
            border-radius: 12px;
        }

        table {
            width: 100%;
            min-width: 750px;
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

        .resident-id {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 8px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .resident-name {
            font-weight: 700;
            white-space: nowrap;
        }

        .email-cell {
            overflow-wrap: anywhere;
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

        .status-used {
            background: #EAF7EF;
            color: #267447;
        }

        .status-used::before {
            background: #35A568;
        }

        .status-unused {
            background: #FFF5DD;
            color: #8A6414;
        }

        .status-unused::before {
            background: #D8A02C;
        }

        .issued-cell {
            min-width: 145px;
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        /* Empty state */
        .empty-message {
            padding: 42px 20px;
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

        @media (max-width: 850px) {
            .access-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .form-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 0;
            }
        }

        @media (max-width: 600px) {
            .access-container {
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

            .access-stats {
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 19px;
            }

            .access-card {
                padding: 18px;
            }

            .records-heading {
                align-items: flex-start;
            }

            .primary-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="access-container">

    <!-- Page header -->
    <header class="dashboard-header">

        <div>
            <h1 class="brand">LaundryWise</h1>

            <h2 class="page-title">Resident Access Management</h2>

            <p class="welcome-text">
                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"], ENT_QUOTES, "UTF-8"); ?>.
                Issue and manage authorised resident IDs.
            </p>
        </div>

        <div class="admin-label">Administrator</div>

    </header>

    <!-- Admin navigation -->
    <nav class="admin-nav" aria-label="Admin navigation">

        <a href="admin_dashboard.php">Dashboard</a>

        <a href="admin_residents.php">Residents</a>

        <a href="admin_machines.php">Machines</a>

        <a href="admin_bookings.php">Bookings</a>

        <a href="admin_registration_requests.php">
            Registration Requests
        </a>

        <a href="admin_resident_access.php" class="active">
            Resident Access
        </a>

        <a href="logout.php">Logout</a>

    </nav>

    <!-- Feedback messages -->
    <?php if ($message !== ""): ?>

        <div class="success-message" role="status">
            <span aria-hidden="true">✓</span>

            <div>
                <?php
                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </div>
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="error-message" role="alert">
            <span aria-hidden="true">!</span>

            <div>
                <?php
                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- Access statistics -->
    <section class="access-stats">

        <article class="stat-card">
            <p class="stat-label">Total Resident IDs</p>
            <p class="stat-number"><?php echo (int)$total_access; ?></p>
        </article>

        <article class="stat-card">
            <p class="stat-label">Activated IDs</p>
            <p class="stat-number"><?php echo (int)$activated_count; ?></p>
        </article>

        <article class="stat-card">
            <p class="stat-label">Not Activated</p>
            <p class="stat-number"><?php echo (int)$unused_count; ?></p>
        </article>

    </section>

    <!-- Issue Resident Access -->
    <section class="access-card">

        <h2 class="card-heading">Issue Resident Access</h2>

        <p class="card-description">
            Enter the resident's details. LaundryWise will generate
            a unique Resident ID and send it to the resident by email.
        </p>

        <form method="POST" action="" class="access-form">

            <div class="form-grid">

                <div class="form-group">

                    <label for="full_name">Resident Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="100"
                        placeholder="Enter the resident's full name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="email">Resident Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        placeholder="resident@example.com"
                        required
                    >

                    <p class="form-help">
                        The generated Resident ID will be sent to this email address.
                    </p>

                </div>

            </div>

            <button type="submit" class="primary-button">
                Issue Resident ID &amp; Send Email
            </button>

        </form>

    </section>

    <!-- Existing access records -->
    <section class="access-card">

        <div class="records-heading">

            <div>
                <h2 class="card-heading">Issued Resident Access</h2>

                <p class="card-description" style="margin-bottom: 0;">
                    View issued Resident IDs and check whether each has been activated.
                </p>
            </div>

            <div class="record-count"
                 aria-label="Total access records">
                <?php echo (int)$total_access; ?>
            </div>

        </div>

        <?php if (count($access_records) === 0): ?>

            <div class="empty-message">

                <div class="empty-icon" aria-hidden="true">♙</div>

                <h3>No Resident IDs issued yet</h3>

                <p>
                    Resident access records will appear here after you issue an ID.
                </p>

            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Resident ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Issued</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($access_records as $record): ?>

                            <tr>

                                <td>
                                    <span class="resident-id">
                                        <?php
                                        echo htmlspecialchars(
                                            $record["resident_id"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td class="resident-name">
                                    <?php
                                    echo htmlspecialchars(
                                        $record["full_name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td class="email-cell">
                                    <?php
                                    echo htmlspecialchars(
                                        $record["email"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ((int)$record["is_used"] === 1): ?>

                                        <span class="status-badge status-used">
                                            Activated
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-unused">
                                            Not Activated
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="issued-cell">
                                    <?php
                                    echo htmlspecialchars(
                                        $record["created_at"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

    <footer class="dashboard-footer">
        LaundryWise · Administration Panel
    </footer>

</div>

</body>
</html>
