<?php

session_start();

require_once "config/database.php";

$error = "";
$success = "";

// Handle registration form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $resident_id = trim($_POST["resident_id"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Check required fields
    if (
        $resident_id === "" ||
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill in all fields.";

    // Check email format
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    // Check password confirmation
    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        // Check whether the resident ID already exists
        $sql = "SELECT id
                FROM users
                WHERE resident_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $resident_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "This resident ID is already registered.";

        } else {

            // Check whether the email already exists
            $sql = "SELECT id
                    FROM users
                    WHERE email = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "This email address is already registered.";

            } else {

                // Hash the password before storing it
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // New registrations are residents
                $role = "resident";

                $sql = "INSERT INTO users
                        (
                            resident_id,
                            name,
                            email,
                            password,
                            role
                        )
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "sssss",
                    $resident_id,
                    $name,
                    $email,
                    $hashed_password,
                    $role
                );

                if ($stmt->execute()) {

                    $success = "Registration successful. You can now log in.";

                } else {

                    $error = "Registration failed. Please try again.";

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

    <title>Register - Laundry Booking System</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="container register-container">

    <h1>Laundry Booking System</h1>

    <h2>Resident Registration</h2>

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
            <a class="button-link" href="login.php">
                Go to Login
            </a>
        </p>

    <?php else: ?>

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

                <label for="name">
                    Full Name:
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
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

            <button type="submit">
                Register
            </button>

        </form>

        <p>
            Already have an account?
            <a href="login.php">Login</a>
        </p>

    <?php endif; ?>

</div>

</body>

</html>