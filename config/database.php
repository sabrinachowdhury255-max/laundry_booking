
<?php

$host = getenv("DB_HOST");
$username = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$database = getenv("DB_NAME");
$port = (int) (getenv("DB_PORT") ?: 3306);

$ssl = getenv("DB_SSL") === "true";

if (!$host || !$username || !$password || !$database) {
    error_log("LaundryWise: Missing database environment variable.");
    die("Database configuration is incomplete.");
}

$conn = mysqli_init();

if ($ssl) {
    mysqli_ssl_set($conn, null, null, null, null, null);
}

try {
    $conn->real_connect(
        $host,
        $username,
        $password,
        $database,
        $port,
        null,
        $ssl ? MYSQLI_CLIENT_SSL : 0
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log("LaundryWise DB error: " . $e->getMessage());
    die("Unable to connect to the database. Please try again later.");
}

?>
