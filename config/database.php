
<?php
/**
 * LaundryWise database connection.
 * Supports XAMPP locally and Aiven on Vercel.
 */

// XAMPP defaults; Vercel environment variables override these.
$host = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = getenv("DB_NAME") ?: "laundry_booking";
$port = (int) (getenv("DB_PORT") ?: 3306);

$ssl = getenv("DB_SSL") === "true";

$conn = mysqli_init();

if ($ssl) {
    mysqli_ssl_set($conn, null, null, null, null, null);
}

$conn->real_connect(
    $host,
    $username,
    $password,
    $database,
    $port,
    null,
    $ssl ? MYSQLI_CLIENT_SSL : 0
);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

?>
