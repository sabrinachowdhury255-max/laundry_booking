
<?php

/* LaundryWise file overview: Authenticates a resident/admin using submitted credentials and establishes the session used to protect private pages. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

require_once "config/database.php";

$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $resident_id = trim($_POST["resident_id"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($resident_id === "" || $password === "") {

        $error = "Please enter your resident ID and password.";

    } else {

        // Find the user by resident ID
        $sql = "SELECT *
                FROM users
                WHERE resident_id = ?";

        $stmt = $conn->prepare($sql);
        /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
        $stmt->bind_param("s", $resident_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        /* Explanation: Checks the submitted password against the stored password hash without storing or comparing plain-text passwords. */
        if ($user && password_verify($password, $user["password"])) {

            // Regenerate session ID after successful login
            session_regenerate_id(true);

            // Store user information in the session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["resident_id"] = $user["resident_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            // Send the user to the correct dashboard
            if ($user["role"] === "admin") {

                /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
                header("Location: admin_dashboard.php");

            } else {

                header("Location: dashboard.php");

            }

            exit();

        } else {

            $error = "Invalid resident ID or password.";

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

    <title>Login - LaundryWise</title>

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
                radial-gradient(
                    circle at top left,
                    #E8E0FF 0,
                    transparent 42%
                ),
                radial-gradient(
                    circle at bottom right,
                    #EDE8FF 0,
                    transparent 38%
                ),
                #F8F7FC;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .login-container {
            display: block;
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
            padding: 38px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(53, 37, 111, 0.10);
        }

        .brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 62px;
            height: 62px;
            margin-bottom: 22px;
            border-radius: 18px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 29px;
        }

        .brand-name {
            margin: 0 0 9px;
            color: var(--deep-purple);
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .login-title {
            margin: 0 0 10px;
            color: var(--text);
            font-size: 23px;
        }

        .login-description {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .error {
            margin-bottom: 22px;
            padding: 13px 15px;
            border: 1px solid #F2D0D0;
            border-radius: 10px;
            background: #FCECEC;
            color: #A33C45;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 21px;
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
            min-height: 47px;
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
            box-shadow: 0 0 0 3px rgba(138, 120, 199, 0.14);
        }

        .login-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            margin-top: 8px;
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

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 18px rgba(81, 65, 139, 0.23);
        }

        .register-text {
            margin: 25px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
            text-align: center;
        }

        .register-text a {
            color: var(--purple);
            font-weight: 700;
            text-decoration: none;
        }

        .register-text a:hover {
            color: var(--deep-purple);
            text-decoration: underline;
        }

        .login-footer {
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 11px;
            text-align: center;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .login-container {
                padding: 27px 22px;
                border-radius: 18px;
            }

            .brand-name {
                font-size: 26px;
            }

            .login-title {
                font-size: 21px;
            }
        }
    </style>

</head>

<body>

    <main class="container login-container">

        <div class="brand-icon" aria-hidden="true">
            ♧
        </div>

        <h1 class="brand-name">LaundryWise</h1>

        <h2 class="login-title">Welcome back</h2>

        <p class="login-description">
            Sign in to manage your laundry bookings and check machine availability.
        </p>

        <?php if ($error !== ""): ?>

            <div class="error" role="alert">
                <?php
                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">

                <label for="resident_id">
                    Resident ID
                </label>

                <input
                    type="text"
                    id="resident_id"
                    name="resident_id"
                    placeholder="Enter your Resident ID"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button type="submit" class="login-button">
                Sign In
            </button>

        </form>

        <p class="register-text">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <footer class="login-footer">
            LaundryWise · Smarter shared laundry management
        </footer>

    </main>

</body>
</html>
