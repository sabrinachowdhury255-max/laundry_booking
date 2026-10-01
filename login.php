<?php

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
        $stmt->bind_param("s", $resident_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user["password"])) {

            // Store user information in the session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["resident_id"] = $user["resident_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            // Send the user to the correct dashboard
            if ($user["role"] === "admin") {

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

    <title>Login - Laundry Booking System</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container login-container">

    <h1>Laundry Booking System</h1>

    <h2>Login</h2>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="">

        <div class="form-group">

            <label for="resident_id">
                Resident ID:
            </label>

            <input
                type="text"
                id="resident_id"
                name="resident_id"
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

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

</body>

</html>