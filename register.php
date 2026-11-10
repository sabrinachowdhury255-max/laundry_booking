<?php

/* LaundryWise file overview: Collects registration details and validates them before creating a registration request or resident account, depending on the implemented workflow. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";

$error = "";
$success = "";

$full_name_value = "";
$email_value = "";
$username_value = "";
$notifications_value = 0;

// --------------------------------------------------
// Handle registration request
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $email_notifications_enabled =
        isset($_POST["email_notifications_enabled"]) ? 1 : 0;

    $full_name_value = $full_name;
    $email_value = $email;
    $username_value = $username;
    $notifications_value = $email_notifications_enabled;

    // --------------------------------------------------
    // Validate required fields
    // --------------------------------------------------

    if (
        $full_name === "" ||
        $email === "" ||
        $username === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill in all required fields.";

    // --------------------------------------------------
    // Validate email
    // --------------------------------------------------

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    // --------------------------------------------------
    // Validate username
    // --------------------------------------------------

    } elseif (strlen($username) < 2) {

        $error = "Username must contain at least 2 characters.";

    } elseif (strlen($username) > 100) {

        $error = "Username must not exceed 100 characters.";

    // --------------------------------------------------
    // Validate password
    // --------------------------------------------------

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        // --------------------------------------------------
        // Check whether email is already registered
        // --------------------------------------------------

        $sql = "SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error =
                "An account with this email address already exists.";

        } else {

            // --------------------------------------------------
            // Check whether username is already registered
            // --------------------------------------------------

            $sql = "SELECT id
                    FROM users
                    WHERE username = ?
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error =
                    "This username is already in use.";

            } else {

                // --------------------------------------------------
                // Check existing pending request
                // --------------------------------------------------

                $sql = "SELECT id
                        FROM resident_requests
                        WHERE email = ?
                        AND status = 'pending'
                        LIMIT 1";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {

                    $error =
                        "A registration request for this email " .
                        "is already waiting for administrator approval.";

                } else {

                    // --------------------------------------------------
                    // Check pending username
                    // --------------------------------------------------

                    $sql = "SELECT id
                            FROM resident_requests
                            WHERE username = ?
                            AND status = 'pending'
                            LIMIT 1";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $username);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {

                        $error =
                            "This username is already being used " .
                            "in a pending registration request.";

                    } else {

                        // --------------------------------------------------
                        // Hash password
                        // --------------------------------------------------

                        /* Explanation: Creates a secure password hash for storage instead of saving the original password. */
                        $password_hash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        // --------------------------------------------------
                        // Create pending registration request
                        // --------------------------------------------------

                        $sql = "INSERT INTO resident_requests
                                (
                                    full_name,
                                    email,
                                    username,
                                    password_hash,
                                    email_notifications_enabled,
                                    status
                                )
                                VALUES (?, ?, ?, ?, ?, 'pending')";

                        $stmt = $conn->prepare($sql);

                        $stmt->bind_param(
                            "ssssi",
                            $full_name,
                            $email,
                            $username,
                            $password_hash,
                            $email_notifications_enabled
                        );

                        if ($stmt->execute()) {

                            $success =
                                "Your registration request has been " .
                                "submitted successfully. " .
                                "Please wait for administrator approval.";

                            $full_name_value = "";
                            $email_value = "";
                            $username_value = "";
                            $notifications_value = 0;

                        } else {

                            $error =
                                "Unable to submit your registration request. " .
                                "Please try again.";
                        }
                    }
                }
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

    <title>Register - LaundryWise</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

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
    }

    * {
        box-sizing: border-box;
    }

    body {
        min-height: 100vh;
        margin: 0;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(circle at top left, #E8E0FF 0, transparent 42%),
            radial-gradient(circle at bottom right, #EDE8FF 0, transparent 38%),
            #F8F7FC;
        color: var(--text);
        font-family: Arial, Helvetica, sans-serif;
    }

    .register-container {
        display: block;
        width: 100%;
        max-width: 520px;
        margin: 25px auto;
        padding: 36px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 22px;
        box-shadow: 0 18px 50px rgba(53, 37, 111, 0.10);
    }

    .register-container h1 {
        margin: 0 0 10px;
        color: var(--deep-purple);
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -0.8px;
    }

    .register-container h2 {
        margin: 0 0 12px;
        color: var(--text);
        font-size: 23px;
    }

    .register-container > p {
        color: var(--muted);
        font-size: 14px;
        line-height: 1.7;
    }

    .register-container form {
        width: 100%;
        margin-top: 25px;
    }

    .register-container .form-group {
        margin-bottom: 19px;
    }

    .register-container .form-group label {
        display: block;
        margin-bottom: 9px;
        color: var(--text);
        font-size: 13px;
        font-weight: 700;
    }

    .register-container .form-group input:not([type="checkbox"]) {
        display: block;
        width: 100%;
        min-height: 46px;
        padding: 12px 14px;
        border: 1px solid #DCD5ED;
        border-radius: 10px;
        outline: none;
        background: var(--white);
        color: var(--text);
        font-family: inherit;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .register-container .form-group input:focus {
        border-color: #8A78C7;
        box-shadow: 0 0 0 3px rgba(138, 120, 199, 0.14);
    }

    .register-container button[type="submit"] {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 48px;
        margin-top: 22px;
        padding: 13px 18px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #51418B, #7561B5);
        color: var(--white);
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .register-container button[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 18px rgba(81, 65, 139, 0.23);
    }

    .register-container .error {
        margin: 18px 0;
        padding: 13px 15px;
        border: 1px solid #F2D0D0;
        border-radius: 10px;
        background: #FCECEC;
        color: #A33C45;
        font-size: 13px;
        line-height: 1.6;
    }

    .register-container .success {
        margin: 18px 0;
        padding: 14px 15px;
        border: 1px solid #D0EBDD;
        border-radius: 10px;
        background: #EAF7EF;
        color: #267447;
        font-size: 14px;
        line-height: 1.7;
    }

    .register-container .button-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border-radius: 10px;
        background: linear-gradient(135deg, #51418B, #7561B5);
        color: white;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }

    .register-container a:not(.button-link) {
        color: var(--purple);
        font-weight: 700;
        text-decoration: none;
    }

    .register-container a:hover {
        text-decoration: underline;
    }

    /* Email notification option */
    .register-container form > div:has(input[type="checkbox"]) {
        margin: 20px 0;
        padding: 16px !important;
        border: 1px solid var(--border) !important;
        border-radius: 12px !important;
        background: #F8F6FD !important;
    }

    .register-container input[type="checkbox"] {
        width: 17px;
        height: 17px;
        margin: 2px 0 0;
        flex-shrink: 0;
        accent-color: var(--purple);
        cursor: pointer;
    }

    .register-container label:has(input[type="checkbox"]) {
        color: var(--text);
        font-size: 13px;
        line-height: 1.6;
    }

    .register-container label:has(input[type="checkbox"]) strong {
        color: var(--deep-purple);
    }

    @media (max-width: 550px) {
        body {
            padding: 12px;
        }

        .register-container {
            margin: 10px auto;
            padding: 25px 20px;
            border-radius: 18px;
        }

        .register-container h1 {
            font-size: 26px;
        }

        .register-container h2 {
            font-size: 21px;
        }
    }
</style>

</head>

<body>

<div class="container register-container">

    <h1>LaundryWise</h1>

    <h2>Resident Registration</h2>

    <p>
        Submit your registration details for administrator approval.
    </p>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <div class="success">

            <?php echo htmlspecialchars($success); ?>

        </div>

        <p>
            Your registration request is now waiting for
            administrator approval.
        </p>

        <p>
            You will receive your Resident ID by email after
            your request has been approved.
        </p>

        <p>

            <a
                class="button-link"
                href="login.php"
            >
                Go to Login
            </a>

        </p>

    <?php else: ?>

        <form method="POST" action="">

            <div class="form-group">

                <label for="full_name">
                    Full Name:
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?php echo htmlspecialchars($full_name_value); ?>"
                    maxlength="100"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email:
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email_value); ?>"
                    maxlength="150"
                    required
                >

            </div>


            <div class="form-group">

                <label for="username">
                    Username:
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars($username_value); ?>"
                    maxlength="100"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password:
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password:
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >

            </div>


            <div
                style="
                    margin: 20px 0;
                    padding: 15px;
                    border: 1px solid #dbeafe;
                    border-radius: 10px;
                    background: #f8fbff;
                "
            >

                <label
                    style="
                        display: flex;
                        align-items: flex-start;
                        gap: 10px;
                        cursor: pointer;
                    "
                >

                    <input
                        type="checkbox"
                        name="email_notifications_enabled"
                        value="1"
                        <?php
                        echo $notifications_value === 1
                            ? "checked"
                            : "";
                        ?>
                    >

                    <span>

                        <strong>
                            Enable email notifications
                        </strong>

                        <br>

                        Receive LaundryWise email notifications
                        and booking reminders.

                    </span>

                </label>

            </div>


            <button type="submit">
                Submit Registration
            </button>

        </form>


        <p>

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </p>

    <?php endif; ?>

</div>

</body>

</html>