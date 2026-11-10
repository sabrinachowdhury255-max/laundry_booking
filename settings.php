<?php

/* LaundryWise file overview: Allows the signed-in resident to view or update account preferences handled by this page. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";

// Make sure the resident is logged in
if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";

// Get current resident information
$sql = "SELECT
            resident_id,
            name,
            username,
            email,
            email_notifications_enabled
        FROM users
        WHERE id = ?
        AND role = 'resident'";

$stmt = $conn->prepare($sql);
/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();


// Handle settings update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $notifications = isset($_POST["email_notifications_enabled"])
        ? 1
        : 0;

    if ($username === "") {

        $error = "Username is required.";

    } elseif ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        // Check whether username belongs to another resident
        $sql = "SELECT id
                FROM users
                WHERE username = ?
                AND id != ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();

        $username_result = $stmt->get_result();

        if ($username_result->num_rows > 0) {

            $error = "This username is already in use.";

        } else {

            // Check whether email belongs to another resident
            $sql = "SELECT id
                    FROM users
                    WHERE email = ?
                    AND id != ?
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();

            $email_result = $stmt->get_result();

            if ($email_result->num_rows > 0) {

                $error = "This email address is already in use.";

            } else {

                $sql = "UPDATE users
                        SET username = ?,
                            email = ?,
                            email_notifications_enabled = ?
                        WHERE id = ?
                        AND role = 'resident'";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "ssii",
                    $username,
                    $email,
                    $notifications,
                    $user_id
                );

                if ($stmt->execute()) {

                    $message = "Settings updated successfully.";

                    // Update displayed values
                    $user["username"] = $username;
                    $user["email"] = $email;
                    $user["email_notifications_enabled"] = $notifications;

                } else {

                    $error = "Unable to update your settings.";

                }
            }
        }
    }
}


// Handle password change
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["change_password"])
) {

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($current_password === "") {

        $error = "Please enter your current password.";

    } elseif ($new_password === "") {

        $error = "Please enter a new password.";

    } elseif (strlen($new_password) < 6) {

        $error = "New password must be at least 6 characters.";

    } elseif ($new_password !== $confirm_password) {

        $error = "New passwords do not match.";

    } else {

        // Get current password hash
        $sql = "SELECT password
                FROM users
                WHERE id = ?
                AND role = 'resident'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $password_result = $stmt->get_result();
        $password_user = $password_result->fetch_assoc();

        if (
            !$password_user
            /* Explanation: Checks the submitted password against the stored password hash without storing or comparing plain-text passwords. */
            || !password_verify(
                $current_password,
                $password_user["password"]
            )
        ) {

            $error = "Current password is incorrect.";

        } else {

            /* Explanation: Creates a secure password hash for storage instead of saving the original password. */
            $new_password_hash = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            $sql = "UPDATE users
                    SET password = ?
                    WHERE id = ?
                    AND role = 'resident'";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "si",
                $new_password_hash,
                $user_id
            );

            if ($stmt->execute()) {

                $message = "Password changed successfully.";

            } else {

                $error = "Unable to change your password.";

            }
        }
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

    <title>My Account | LaundryWise</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <style>
        :root { --lw-navy:#193664; --lw-blue:#2864ed; --lw-muted:#667995; --lw-border:#dce5f5; }
        * { box-sizing:border-box; }
        body {
            margin:0; min-height:100vh; color:#17233c;
            font-family:Inter,"Segoe UI",Arial,sans-serif;
            background:radial-gradient(ellipse at 8% 8%,rgba(211,196,255,.68),transparent 34%),
                       radial-gradient(ellipse at 95% 22%,rgba(190,220,255,.7),transparent 32%),
                       linear-gradient(135deg,#f5f0ff 0%,#eef4ff 55%,#f8f0ff 100%);
        }
        .lw-page { width:min(1080px,calc(100% - 36px)); margin:26px auto 48px; }
        .lw-nav { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:15px 22px; margin-bottom:24px; background:rgba(255,255,255,.9); border:1px solid #fff; border-radius:22px; box-shadow:0 8px 28px rgba(53,76,135,.08); }
        .lw-brand { color:var(--lw-navy); font-size:20px; font-weight:800; text-decoration:none; letter-spacing:-.4px; }
        .lw-nav-links { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
        .lw-nav-links a { color:#50617e; text-decoration:none; font-size:14px; font-weight:650; padding:10px 13px; border-radius:12px; }
        .lw-nav-links a:hover,.lw-nav-links a.active { color:var(--lw-blue); background:#eef3ff; }
        .lw-hero { padding:clamp(22px,4vw,38px); border:1px solid rgba(255,255,255,.95); border-radius:28px; background:rgba(255,255,255,.8); box-shadow:0 18px 48px rgba(63,82,145,.08); }
        .lw-eyebrow { color:#526dff; font-size:12px; font-weight:800; letter-spacing:1.6px; text-transform:uppercase; }
        .lw-hero-top { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:25px; }
        h1 { margin:8px 0; color:var(--lw-navy); font-size:clamp(30px,4vw,42px); line-height:1.12; letter-spacing:-1px; }
        .lw-subtitle { margin:0; color:var(--lw-muted); line-height:1.6; }
        .settings-section { margin:18px 0; padding:clamp(20px,3vw,28px); border:1px solid var(--lw-border); border-radius:21px; background:#fff; box-shadow:0 8px 22px rgba(53,76,135,.045); }
        .settings-section h2 { margin:0 0 20px; color:var(--lw-navy); font-size:21px; letter-spacing:-.3px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; margin-bottom:8px; color:#30435f; font-weight:700; font-size:14px; }
        .form-group input[type="text"],.form-group input[type="email"],.form-group input[type="password"] { display:block; width:100%; max-width:620px; padding:13px 14px; border:1px solid #cfdbef; border-radius:12px; background:#f9fbff; color:#17233c; font:inherit; outline:none; transition:border-color .2s,box-shadow .2s; }
        .form-group input:focus { border-color:#6c8ff5; box-shadow:0 0 0 4px rgba(40,100,237,.1); }
        .readonly-field { width:100%; max-width:620px; padding:13px 14px; border:1px solid #e0e7f2; border-radius:12px; background:#f5f7fb; color:#52627d; }
        .settings-section small { display:block; margin-top:7px; color:#7b8aa3; line-height:1.5; }
        .notification-option { margin:16px 0 20px; padding:15px; border:1px solid #e0e8f7; border-radius:14px; background:#f8faff; }
        .notification-option label { display:flex; align-items:center; gap:9px; color:#30435f; font-weight:700; }
        .notification-option input { width:17px; height:17px; accent-color:var(--lw-blue); }
        .notification-option p { margin:8px 0 0 26px; color:var(--lw-muted); font-size:13px; line-height:1.5; }
        button { border:0; border-radius:12px; padding:12px 18px; color:#fff; background:linear-gradient(135deg,#3676ff,#6447ed); box-shadow:0 7px 16px rgba(49,99,231,.18); font:inherit; font-weight:750; cursor:pointer; }
        button:hover { filter:brightness(1.04); transform:translateY(-1px); }
        .success-message,.error-message { padding:14px 16px; margin:0 0 18px; border-radius:13px; line-height:1.5; }
        .success-message { color:#116b49; background:#e8fbf0; border:1px solid #b8eed0; }
        .error-message { color:#a52a3a; background:#fff0f1; border:1px solid #ffd0d5; }
        .policy-box { margin:10px 0; padding:16px; border:1px solid #e0e7f2; border-radius:14px; background:#fbfcff; }
        .policy-box summary { cursor:pointer; color:#2d4261; font-weight:750; }
        .policy-box p { color:var(--lw-muted); line-height:1.65; }
        @media(max-width:680px) { .lw-page{width:calc(100% - 20px);margin:10px auto 28px}.lw-nav{align-items:flex-start;flex-direction:column;padding:15px;border-radius:18px}.lw-nav-links{width:100%;gap:3px}.lw-nav-links a{padding:9px 10px;font-size:13px}.lw-hero{padding:20px 13px;border-radius:22px}.lw-hero-top{flex-direction:column}.settings-section{padding:19px 15px} }
    </style>

<link rel="stylesheet" href="css/laundrywise-nav.css">
</head>

<body>

<div class="lw-page">
    <nav class="dashboard-nav lw-prototype-nav" aria-label="Main navigation">
<a class="lw-brand" href="dashboard.php" aria-label="LaundryWise home"><span class="lw-brand-icon" aria-hidden="true">▣</span><span class="lw-brand-name">LaundryWise</span></a>
<div class="lw-prototype-nav-links">
<a href="dashboard.php">Dashboard</a><a href="my_bookings.php">My Bookings</a><a href="booking_help.php">Booking Help</a><a href="policies.php">Policies &amp; Rules</a><a href="settings.php">My Account</a><a class="lw-nav-logout" href="logout.php">Logout</a>
</div></nav>

    <main class="lw-hero">
        <div class="lw-hero-top">
            <div>
                <div class="lw-eyebrow">Personalise your account</div>
                <h1>My Account</h1>
                <p class="lw-subtitle">View and update your account details, change your password, and manage email notification preferences.</p>
            </div>
        </div>


    <?php if ($message !== ""): ?>

        <div class="success-message">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error-message">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- Account Information -->

    <div class="settings-section">

        <h2>Account Information</h2>

        <div class="form-group">

            <label>
                Resident ID
            </label>

            <div class="readonly-field">

                <?php
                echo htmlspecialchars($user["resident_id"]);
                ?>

            </div>

            <small>
                Your Resident ID is issued by the administrator
                and cannot be changed.
            </small>

        </div>


        <div class="form-group">

            <label>
                Full Name
            </label>

            <div class="readonly-field">

                <?php
                echo htmlspecialchars($user["name"]);
                ?>

            </div>

        </div>

    </div>


    <!-- Profile Settings -->

    <div class="settings-section">

        <h2>Profile Settings</h2>

        <form method="POST" action="settings.php">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php
                        echo htmlspecialchars($user["username"] ?? "");
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php
                        echo htmlspecialchars($user["email"]);
                    ?>"
                    required
                >

            </div>


            <div class="notification-option">

                <label>

                    <input
                        type="checkbox"
                        name="email_notifications_enabled"
                        value="1"
                        <?php
                        if ((int)$user["email_notifications_enabled"] === 1) {
                            echo "checked";
                        }
                        ?>
                    >

                    Enable email notifications

                </label>

                <p>
                    Receive LaundryWise email notifications,
                    such as booking reminders and relevant system
                    notifications.
                </p>

            </div>


            <button type="submit">
                Save Settings
            </button>

        </form>

    </div>


    <!-- Change Password -->

    <div class="settings-section">

        <h2>Change Password</h2>

        <form method="POST" action="settings.php">

            <input
                type="hidden"
                name="change_password"
                value="1"
            >

            <div class="form-group">

                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                >

            </div>


            <div class="form-group">

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    minlength="6"
                    required
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="6"
                    required
                >

            </div>


            <button type="submit">
                Change Password
            </button>

        </form>

    </div>


    