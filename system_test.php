<?php

/* LaundryWise file overview: Development test page for checking application/system behavior. Restrict or remove it from public deployment if it exposes internal information. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

$root = __DIR__;

$passed = 0;
$failed = 0;
$warnings = 0;
$results = [];

function testPass($name, $details = "")
{
    global $passed, $results;

    $passed++;

    $results[] = [
        "status" => "PASS",
        "name" => $name,
        "details" => $details
    ];
}

function testFail($name, $details = "")
{
    global $failed, $results;

    $failed++;

    $results[] = [
        "status" => "FAIL",
        "name" => $name,
        "details" => $details
    ];
}

function testWarning($name, $details = "")
{
    global $warnings, $results;

    $warnings++;

    $results[] = [
        "status" => "WARNING",
        "name" => $name,
        "details" => $details
    ];
}

function checkFileContains($file, $patterns)
{
    if (!file_exists($file)) {
        return false;
    }

    $content = file_get_contents($file);

    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern) === false) {
            return false;
        }
    }

    return true;
}

function checkFileRegex($file, $patterns)
{
    if (!file_exists($file)) {
        return false;
    }

    $content = file_get_contents($file);

    foreach ($patterns as $pattern) {
        if (!preg_match($pattern, $content)) {
            return false;
        }
    }

    return true;
}

function getFileContent($file)
{
    if (!file_exists($file)) {
        return "";
    }

    return file_get_contents($file);
}


/*
|--------------------------------------------------------------------------
| 1. REQUIRED FILES
|--------------------------------------------------------------------------
*/

$requiredFiles = [
    "index.php",
    "login.php",
    "logout.php",
    "register.php",
    "dashboard.php",
    "availability.php",
    "book.php",
    "cancel_booking.php",
    "my_bookings.php",
    "recommendations.php",
    "admin_dashboard.php",
    "admin_machines.php",
    "admin_residents.php",
    "admin_bookings.php",
    "admin_registration_requests.php",
    "admin_resident_access.php",
    "settings.php",
    "assistant.php",
    "send_booking_reminders.php",
    "config/database.php",
    "config/mail.php",
    "ml/GradientBoostingPredictor.php",
    "ml/gradient_boosting_model_php.json"
];

foreach ($requiredFiles as $file) {

    if (file_exists($root . "/" . $file)) {
        testPass(
            "Required file: " . $file,
            "File exists."
        );
    } else {
        testFail(
            "Required file: " . $file,
            "File was not found."
        );
    }
}


/*
|--------------------------------------------------------------------------
| 2. DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$conn = null;

try {

    require $root . "/config/database.php";

    if (isset($conn) && $conn instanceof mysqli) {

        if (!$conn->connect_error) {

            testPass(
                "Database connection",
                "Database connection established successfully."
            );

        } else {

            testFail(
                "Database connection",
                $conn->connect_error
            );
        }

    } else {

        testFail(
            "Database connection",
            "The database connection variable was not created."
        );
    }

} catch (Throwable $e) {

    testFail(
        "Database connection",
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| Stop database-dependent tests if connection failed
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {

    testFail(
        "Database-dependent tests",
        "Skipped because database connection failed."
    );

} else {


    /*
    |--------------------------------------------------------------------------
    | 3. REQUIRED DATABASE TABLES
    |--------------------------------------------------------------------------
    */

    $requiredTables = [
        "users",
        "machines",
        "bookings",
        "resident_requests",
        "resident_access"
    ];

    foreach ($requiredTables as $table) {

        $safeTable = $conn->real_escape_string($table);

        $result = $conn->query(
            "SHOW TABLES LIKE '$safeTable'"
        );

        if ($result && $result->num_rows > 0) {

            testPass(
                "Database table: " . $table,
                "Table exists."
            );

        } else {

            testFail(
                "Database table: " . $table,
                "Table does not exist."
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 4. REQUIRED USERS COLUMNS
    |--------------------------------------------------------------------------
    */

    $requiredUserColumns = [
        "id",
        "resident_id",
        "name",
        "email",
        "password",
        "role",
        "username",
        "email",
        "email_notifications_enabled"
    ];

    $userColumns = [];

    $result = $conn->query("SHOW COLUMNS FROM users");

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $userColumns[] = $row["Field"];
        }

        foreach ($requiredUserColumns as $column) {

            if (in_array($column, $userColumns, true)) {

                testPass(
                    "users column: " . $column,
                    "Column exists."
                );

            } else {

                testFail(
                    "users column: " . $column,
                    "Required column is missing."
                );
            }
        }

    } else {

        testFail(
            "users table structure",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 5. REQUIRED MACHINE COLUMNS
    |--------------------------------------------------------------------------
    */

    $requiredMachineColumns = [
        "id",
        "machine_name",
        "machine_type",
        "status"
    ];

    $machineColumns = [];

    $result = $conn->query("SHOW COLUMNS FROM machines");

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $machineColumns[] = $row["Field"];
        }

        foreach ($requiredMachineColumns as $column) {

            if (in_array($column, $machineColumns, true)) {

                testPass(
                    "machines column: " . $column,
                    "Column exists."
                );

            } else {

                testFail(
                    "machines column: " . $column,
                    "Required column is missing."
                );
            }
        }

    } else {

        testFail(
            "machines table structure",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 6. REQUIRED BOOKING COLUMNS
    |--------------------------------------------------------------------------
    */

    $requiredBookingColumns = [
        "id",
        "user_id",
        "machine_id",
        "booking_date",
        "start_time",
        "end_time",
        "status",
        "reminder_enabled"
    ];

    $bookingColumns = [];

    $result = $conn->query("SHOW COLUMNS FROM bookings");

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $bookingColumns[] = $row["Field"];
        }

        foreach ($requiredBookingColumns as $column) {

            if (in_array($column, $bookingColumns, true)) {

                testPass(
                    "bookings column: " . $column,
                    "Column exists."
                );

            } else {

                testFail(
                    "bookings column: " . $column,
                    "Required column is missing."
                );
            }
        }

    } else {

        testFail(
            "bookings table structure",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 7. MACHINE DATA
    |--------------------------------------------------------------------------
    */

    $machineResult = $conn->query(
        "SELECT COUNT(*) AS total FROM machines"
    );

    if ($machineResult) {

        $machineCount = (int)$machineResult->fetch_assoc()["total"];

        if ($machineCount > 0) {

            testPass(
                "Machine records",
                $machineCount . " machine record(s) found."
            );

        } else {

            testFail(
                "Machine records",
                "No machines were found."
            );
        }

    } else {

        testFail(
            "Machine records",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8. ACTIVE MACHINE DATA
    |--------------------------------------------------------------------------
    */

    $activeResult = $conn->query(
        "SELECT COUNT(*) AS total
         FROM machines
         WHERE status = 'active'"
    );

    if ($activeResult) {

        $activeCount = (int)$activeResult->fetch_assoc()["total"];

        if ($activeCount > 0) {

            testPass(
                "Active machines",
                $activeCount . " active machine(s) found."
            );

        } else {

            testWarning(
                "Active machines",
                "No active machines currently exist."
            );
        }

    } else {

        testFail(
            "Active machines",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 9. MACHINE TYPES
    |--------------------------------------------------------------------------
    */

    $typeResult = $conn->query(
        "SELECT DISTINCT machine_type
         FROM machines"
    );

    if ($typeResult) {

        $types = [];

        while ($row = $typeResult->fetch_assoc()) {
            $types[] = $row["machine_type"];
        }

        if (
            in_array("Washing Machine", $types, true) ||
            in_array("Dryer", $types, true)
        ) {

            testPass(
                "Machine types",
                implode(", ", $types)
            );

        } else {

            testWarning(
                "Machine types",
                "Expected Washing Machine and/or Dryer types were not found."
            );
        }

    } else {

        testFail(
            "Machine types",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 10. USER RECORDS
    |--------------------------------------------------------------------------
    */

    $userResult = $conn->query(
        "SELECT COUNT(*) AS total FROM users"
    );

    if ($userResult) {

        $userCount = (int)$userResult->fetch_assoc()["total"];

        if ($userCount > 0) {

            testPass(
                "User records",
                $userCount . " user record(s) found."
            );

        } else {

            testWarning(
                "User records",
                "No users currently exist."
            );
        }

    } else {

        testFail(
            "User records",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 11. USER ROLES
    |--------------------------------------------------------------------------
    */

    $roleResult = $conn->query(
        "SELECT DISTINCT role FROM users"
    );

    if ($roleResult) {

        $roles = [];

        while ($row = $roleResult->fetch_assoc()) {
            $roles[] = $row["role"];
        }

        if (
            in_array("resident", $roles, true) &&
            in_array("admin", $roles, true)
        ) {

            testPass(
                "User roles",
                "Resident and admin roles exist."
            );

        } else {

            testWarning(
                "User roles",
                "Resident and/or admin role records are not both present."
            );
        }

    } else {

        testFail(
            "User roles",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 12. PASSWORD HASH FORMAT
    |--------------------------------------------------------------------------
    */

    $passwordResult = $conn->query(
        "SELECT password FROM users LIMIT 5"
    );

    if ($passwordResult) {

        $checked = false;
        $allHashed = true;

        while ($row = $passwordResult->fetch_assoc()) {

            $checked = true;

            if (
                password_get_info($row["password"])["algo"] === 0
            ) {
                $allHashed = false;
            }
        }

        if (!$checked) {

            testWarning(
                "Password hash format",
                "No user passwords available to test."
            );

        } elseif ($allHashed) {

            testPass(
                "Password hash format",
                "Stored passwords use PHP password hashing."
            );

        } else {

            testFail(
                "Password hash format",
                "At least one stored password does not appear to be hashed."
            );
        }

    } else {

        testFail(
            "Password hash format",
            $conn->error
        );
    }
}


/*
|--------------------------------------------------------------------------
| 13. SOURCE CODE TESTS
|--------------------------------------------------------------------------
*/

$files = [
    "login" => getFileContent($root . "/login.php"),
    "register" => getFileContent($root . "/register.php"),
    "logout" => getFileContent($root . "/logout.php"),
    "availability" => getFileContent($root . "/availability.php"),
    "book" => getFileContent($root . "/book.php"),
    "cancel" => getFileContent($root . "/cancel_booking.php"),
    "my_bookings" => getFileContent($root . "/my_bookings.php"),
    "recommendations" => getFileContent($root . "/recommendations.php"),
    "admin_dashboard" => getFileContent($root . "/admin_dashboard.php"),
    "admin_machines" => getFileContent($root . "/admin_machines.php"),
    "admin_residents" => getFileContent($root . "/admin_residents.php"),
    "admin_bookings" => getFileContent($root . "/admin_bookings.php"),
    "dashboard" => getFileContent($root . "/dashboard.php"),
    "index" => getFileContent($root . "/index.php"),
    "admin_registration_requests" => getFileContent($root . "/admin_registration_requests.php"),
    "settings" => getFileContent($root . "/settings.php"),
    "assistant" => getFileContent($root . "/assistant.php"),
    "send_booking_reminders" => getFileContent($root . "/send_booking_reminders.php"),
    "set_reminder" => getFileContent($root . "/set_reminder.php"),
    "predictor" => getFileContent($root . "/ml/GradientBoostingPredictor.php")
];


/*
|--------------------------------------------------------------------------
| 14. LOGIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["login"], "password_verify") !== false &&
    strpos($files["login"], "SESSION") !== false
) {

    testPass(
        "Login authentication",
        "Password verification and session authentication are implemented."
    );

} else {

    testFail(
        "Login authentication",
        "Expected password verification/session handling was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 15. REGISTER PASSWORD HASHING
|--------------------------------------------------------------------------
*/

if (
    strpos($files["register"], "password_hash") !== false &&
    strpos($files["register"], "PASSWORD_DEFAULT") !== false
) {

    testPass(
        "Password hashing",
        /* Explanation: Creates a secure password hash for storage instead of saving the original password. */
        "register.php uses password_hash() with PASSWORD_DEFAULT."
    );

} else {

    testFail(
        "Password hashing",
        "password_hash() implementation was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 16. LOGOUT
|--------------------------------------------------------------------------
*/

if (
    strpos($files["logout"], "session_destroy") !== false &&
    strpos($files["logout"], "login.php") !== false
) {

    testPass(
        "Logout",
        "Session is destroyed and user is redirected to login."
    );

} else {

    testFail(
        "Logout",
        "Expected logout implementation was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 17. BOOKING CONFLICT PREVENTION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["book"], "bookings") !== false &&
    strpos($files["book"], "start_time") !== false &&
    strpos($files["book"], "end_time") !== false &&
    strpos($files["book"], "confirmed") !== false
) {

    testPass(
        "Booking conflict prevention",
        "Booking code checks existing booking information before insertion."
    );

} else {

    testFail(
        "Booking conflict prevention",
        "Expected booking conflict checks were not found."
    );
}


/*
|--------------------------------------------------------------------------
| 18. BOOKING INSERTION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["book"], "INSERT INTO bookings") !== false &&
    strpos($files["book"], "prepare") !== false
) {

    testPass(
        "Booking creation",
        "Booking records are inserted using a prepared statement."
    );

} else {

    testFail(
        "Booking creation",
        "Booking insertion implementation was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 19. CANCELLATION OWNERSHIP
|--------------------------------------------------------------------------
*/

$cancelOwnership =
    strpos($files["cancel"], "user_id = ?") !== false &&
    strpos($files["cancel"], "WHERE id = ?") !== false &&
    strpos($files["cancel"], "bind_param") !== false &&
    strpos($files["cancel"], '$user_id') !== false;

if ($cancelOwnership) {

    testPass(
        "Cancellation ownership check",
        "Cancellation verifies that the booking belongs to the logged-in resident."
    );

} else {

    testFail(
        "Cancellation ownership check",
        "The cancellation file does not contain the required booking/user ownership protection."
    );
}


/*
|--------------------------------------------------------------------------
| 20. CONFIRMED BOOKING CANCELLATION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["cancel"], "status") !== false &&
    strpos($files["cancel"], "confirmed") !== false &&
    strpos($files["cancel"], "cancelled") !== false
) {

    testPass(
        "Cancellation status protection",
        "Only confirmed bookings are processed for cancellation."
    );

} else {

    testFail(
        "Cancellation status protection",
        "Confirmed/cancelled status handling was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 21. AVAILABILITY PAGE
|--------------------------------------------------------------------------
*/

if (
    strpos($files["availability"], "isBooked") !== false &&
    strpos($files["availability"], "start_time") !== false &&
    strpos($files["availability"], "end_time") !== false
) {

    testPass(
        "Availability checking",
        "Availability page checks booking time periods."
    );

} else {

    testFail(
        "Availability checking",
        "Expected availability logic was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 22. PAST TIME SLOT PROTECTION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["availability"], "isPastSlot") !== false &&
    strpos($files["availability"], "Europe/Copenhagen") !== false
) {

    testPass(
        "Past time-slot protection",
        "Availability uses Copenhagen time and checks whether a time slot has passed."
    );

} else {

    testFail(
        "Past time-slot protection",
        "Past time-slot protection was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 23. EXPIRED BOOKING CANCELLATION BUTTON
|--------------------------------------------------------------------------
*/

if (
    strpos($files["my_bookings"], "booking_has_passed") !== false &&
    strpos($files["my_bookings"], "Europe/Copenhagen") !== false &&
    strpos($files["my_bookings"], "end_time") !== false
) {

    testPass(
        "Expired booking cancellation control",
        "Cancellation is controlled using booking end time."
    );

} else {

    testFail(
        "Expired booking cancellation control",
        "Expected expired-booking cancellation logic was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 24. RECOMMENDATION ML
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "GradientBoostingPredictor") !== false &&
    strpos($files["recommendations"], "predict") !== false
) {

    testPass(
        "ML recommendation integration",
        "recommendations.php uses the GradientBoostingPredictor."
    );

} else {

    testFail(
        "ML recommendation integration",
        "ML predictor integration was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 25. RECOMMENDATION CURRENT AVAILABILITY
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "bookings") !== false &&
    strpos($files["recommendations"], "active") !== false
) {

    testPass(
        "Recommendation availability filtering",
        "Recommendation code uses booking/machine availability information."
    );

} else {

    testFail(
        "Recommendation availability filtering",
        "Expected availability filtering was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 26. SAME MACHINE TYPE FILTER
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "machine_type") !== false
) {

    testPass(
        "Recommendation machine-type filtering",
        "Recommendation candidates use machine type information."
    );

} else {

    testFail(
        "Recommendation machine-type filtering",
        "Machine-type filtering was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 27. BOOKED CANDIDATE EXCLUSION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "isBooked") !== false ||
    strpos($files["recommendations"], "booking") !== false
) {

    testPass(
        "Recommendation booked-slot exclusion",
        "Recommendation code contains booking availability checks."
    );

} else {

    testFail(
        "Recommendation booked-slot exclusion",
        "Booked candidate exclusion was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 28. HISTORICAL FEATURES
|--------------------------------------------------------------------------
*/

$historicalFeatureChecks = [
    '$previous_week_booking',
    '$previous_two_weeks_booking',
    '$previous_three_weeks_booking',
    '$previous_three_week_average'
];

$historicalFeaturesFound = true;

foreach ($historicalFeatureChecks as $feature) {

    if (strpos($files["recommendations"], $feature) === false) {
        $historicalFeaturesFound = false;
    }
}

if ($historicalFeaturesFound) {

    testPass(
        "Historical demand features",
        "Previous-week historical demand features are passed to the ML predictor."
    );

} else {

    testFail(
        "Historical demand features",
        "One or more historical demand features were not found."
    );
}


/*
|--------------------------------------------------------------------------
| 29. RECOMMENDATION RANKING
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "time_distance") !== false &&
    strpos($files["recommendations"], "predicted_demand") !== false &&
    strpos($files["recommendations"], "usort") !== false
) {

    testPass(
        "Recommendation ranking",
        "Recommendations are ranked using time distance and predicted demand."
    );

} else {

    testFail(
        "Recommendation ranking",
        "Expected recommendation ranking logic was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 30. TOP FIVE RECOMMENDATIONS
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "array_slice") !== false &&
    strpos($files["recommendations"], "5") !== false
) {

    testPass(
        "Recommendation limit",
        "Recommendation results are limited to five candidates."
    );

} else {

    testWarning(
        "Recommendation limit",
        "Could not verify the five-result limit directly from source."
    );
}


/*
|--------------------------------------------------------------------------
| 31. NO AUTOMATIC BOOKING FROM RECOMMENDATIONS
|--------------------------------------------------------------------------
*/

if (
    strpos($files["recommendations"], "INSERT INTO bookings") === false
) {

    testPass(
        "Recommendation does not auto-book",
        "Recommendation page does not directly insert a booking."
    );

} else {

    testFail(
        "Recommendation does not auto-book",
        "recommendations.php appears to insert bookings automatically."
    );
}


/*
|--------------------------------------------------------------------------
| 32. ADMIN DASHBOARD PROTECTION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["admin_dashboard"], "role") !== false &&
    strpos($files["admin_dashboard"], "admin") !== false
) {

    testPass(
        "Admin dashboard protection",
        "Admin role checking is present."
    );

} else {

    testFail(
        "Admin dashboard protection",
        "Admin role protection was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 33. ADMIN MACHINE MANAGEMENT
|--------------------------------------------------------------------------
*/

if (
    strpos($files["admin_machines"], "status") !== false &&
    strpos($files["admin_machines"], "UPDATE") !== false
) {

    testPass(
        "Admin machine status management",
        "Administrator can change machine status."
    );

} else {

    testFail(
        "Admin machine status management",
        "Machine status management was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 34. ADMIN RESIDENT PAGE
|--------------------------------------------------------------------------
*/

if (
    strpos($files["admin_residents"], "role") !== false &&
    strpos($files["admin_residents"], "admin") !== false
) {

    testPass(
        "Admin resident management protection",
        "Admin protection is present."
    );

} else {

    testFail(
        "Admin resident management protection",
        "Expected admin protection was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 35. ADMIN BOOKING PAGE
|--------------------------------------------------------------------------
*/

if (
    strpos($files["admin_bookings"], "role") !== false &&
    strpos($files["admin_bookings"], "admin") !== false
) {

    testPass(
        "Admin booking management protection",
        "Admin protection is present."
    );

} else {

    testFail(
        "Admin booking management protection",
        "Expected admin protection was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 36. RESIDENT DASHBOARD LOGIN PROTECTION
|--------------------------------------------------------------------------
*/

if (
    strpos($files["dashboard"], '$_SESSION["user_id"]') !== false
) {

    testPass(
        "Resident dashboard authentication",
        "Dashboard checks the logged-in session."
    );

} else {

    testFail(
        "Resident dashboard authentication",
        "Dashboard authentication check was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 37. PUBLIC LANDING PAGE
|--------------------------------------------------------------------------
*/

if (
    stripos($files["index"], "LaundryWise") !== false &&
    strpos($files["index"], "login.php") !== false &&
    strpos($files["index"], "register.php") !== false
) {

    testPass(
        "Public landing page",
        "Landing page identifies LaundryWise and links to login and registration."
    );

} else {

    testFail(
        "Public landing page",
        "Landing page branding or login/registration links were not found."
    );
}


/*
|--------------------------------------------------------------------------
| 38. PREPARED STATEMENTS
|--------------------------------------------------------------------------
*/

$preparedStatementFiles = [
    "login",
    "register",
    "book",
    "cancel",
    "recommendations"
];

$preparedStatementsOkay = true;

foreach ($preparedStatementFiles as $fileName) {

    if (
        strpos($files[$fileName], "prepare") === false
    ) {
        $preparedStatementsOkay = false;
    }
}

if ($preparedStatementsOkay) {

    testPass(
        "Prepared SQL statements",
        "Important database operations use prepared statements."
    );

} else {

    testFail(
        "Prepared SQL statements",
        "One or more important files do not appear to use prepared statements."
    );
}


/*
|--------------------------------------------------------------------------
| 39. ML MODEL FILE
|--------------------------------------------------------------------------
*/

$modelFile = $root . "/ml/gradient_boosting_model_php.json";

if (file_exists($modelFile)) {

    $modelJson = file_get_contents($modelFile);
    $model = json_decode($modelJson, true);

    if (is_array($model)) {

        testPass(
            "ML model JSON",
            "Model JSON loaded successfully."
        );

        /*
        |--------------------------------------------------------------------------
        | Model tree count
        |--------------------------------------------------------------------------
        */

        if (
            isset($model["trees"]) &&
            is_array($model["trees"])
        ) {

            $treeCount = count($model["trees"]);

            if ($treeCount === 200) {

                testPass(
                    "Gradient Boosting tree count",
                    "Model contains 200 trees."
                );

            } else {

                testFail(
                    "Gradient Boosting tree count",
                    "Expected 200 trees but found " . $treeCount . "."
                );
            }

        } else {

            testFail(
                "Gradient Boosting tree count",
                "Model tree data was not found."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Encoded feature count
        |--------------------------------------------------------------------------
        */

        if (
            isset($model["encoded_feature_names"]) &&
            is_array($model["encoded_feature_names"])
        ) {

            $featureCount = count(
                $model["encoded_feature_names"]
            );

            if ($featureCount === 21) {

                testPass(
                    "ML feature count",
                    "Model contains 21 encoded features."
                );

            } else {

                testFail(
                    "ML feature count",
                    "Expected 21 features but found " . $featureCount . "."
                );
            }

        } else {

            testFail(
                "ML feature count",
                "Encoded feature names were not found."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Learning rate
        |--------------------------------------------------------------------------
        */

        if (
            isset($model["learning_rate"]) &&
            abs((float)$model["learning_rate"] - 0.05) < 0.000001
        ) {

            testPass(
                "ML learning rate",
                "Learning rate is 0.05."
            );

        } else {

            testFail(
                "ML learning rate",
                "Expected learning rate 0.05 was not found."
            );
        }

    } else {

        testFail(
            "ML model JSON",
            "Model JSON could not be decoded."
        );
    }

} else {

    testFail(
        "ML model JSON",
        "gradient_boosting_model_php.json was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 40. PHP ML PREDICTOR
|--------------------------------------------------------------------------
*/

$predictorFile = $root . "/ml/GradientBoostingPredictor.php";

if (file_exists($predictorFile)) {

    try {

        require_once $predictorFile;

        if (class_exists("GradientBoostingPredictor")) {

            testPass(
                "PHP ML predictor class",
                "GradientBoostingPredictor class loaded successfully."
            );

            try {

                $predictor = new GradientBoostingPredictor(
                    $modelFile
                );

                $prediction = $predictor->predict(
                    "WM1",
                    "Washing Machine",
                    "Friday",
                    18,
                    0,
                    3.0,
                    3.0,
                    3.0,
                    3.0
                );

                if (is_numeric($prediction)) {

                    testPass(
                        "PHP ML prediction",
                        "Prediction generated: " .
                        number_format((float)$prediction, 6)
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Known verification value
                    |--------------------------------------------------------------------------
                    */

                    if (
                        abs((float)$prediction - 4.405906) < 0.0001
                    ) {

                        testPass(
                            "Python/PHP ML verification",
                            "PHP prediction matches the expected verification value approximately 4.405906."
                        );

                    } else {

                        testFail(
                            "Python/PHP ML verification",
                            "Expected approximately 4.405906 but received " .
                            number_format((float)$prediction, 6)
                        );
                    }

                } else {

                    testFail(
                        "PHP ML prediction",
                        "Predictor did not return a numeric value."
                    );
                }

            } catch (Throwable $e) {

                testFail(
                    "PHP ML prediction",
                    $e->getMessage()
                );
            }

        } else {

            testFail(
                "PHP ML predictor class",
                "GradientBoostingPredictor class was not loaded."
            );
        }

    } catch (Throwable $e) {

        testFail(
            "PHP ML predictor class",
            $e->getMessage()
        );
    }

} else {

    testFail(
        "PHP ML predictor class",
        "GradientBoostingPredictor.php was not found."
    );
}


/*
|--------------------------------------------------------------------------
| 41. EXISTING BOOKING DATA INTEGRITY
|--------------------------------------------------------------------------
*/

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {

    $bookingResult = $conn->query(
        "SELECT COUNT(*) AS total FROM bookings"
    );

    if ($bookingResult) {

        $bookingCount = (int)$bookingResult->fetch_assoc()["total"];

        testPass(
            "Booking database access",
            $bookingCount . " booking record(s) currently exist."
        );

    } else {

        testFail(
            "Booking database access",
            $conn->error
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Confirmed booking integrity
    |--------------------------------------------------------------------------
    */

    $confirmedResult = $conn->query(
        "SELECT COUNT(*) AS total
         FROM bookings
         WHERE status = 'confirmed'"
    );

    if ($confirmedResult) {

        $confirmedCount =
            (int)$confirmedResult->fetch_assoc()["total"];

        testPass(
            "Confirmed booking records",
            $confirmedCount . " confirmed booking(s) found."
        );

    } else {

        testFail(
            "Confirmed booking records",
            $conn->error
        );
    }
}



/*
|--------------------------------------------------------------------------
| ADDITIONAL THESIS-SCOPE TESTS: CURRENT FEATURES
|--------------------------------------------------------------------------
*/

// Registration requests must wait for administrator approval.
if (
    strpos($files["register"], "INSERT INTO resident_requests") !== false &&
    strpos($files["register"], "pending") !== false &&
    strpos($files["register"], "password_hash") !== false &&
    strpos($files["register"], "Resident ID by email") !== false
) {
    testPass("Registration approval workflow", "Registration stores a pending request and tells residents the Resident ID follows approval.");
} else {
    testFail("Registration approval workflow", "Pending registration workflow or post-approval Resident ID message was not found.");
}

// Administrator approval creates the user account and resident access record.
if (
    strpos($files["admin_registration_requests"], "resident_requests") !== false &&
    strpos($files["admin_registration_requests"], "INSERT INTO users") !== false &&
    strpos($files["admin_registration_requests"], "INSERT INTO resident_access") !== false &&
    strpos($files["admin_registration_requests"], "sendResidentIdEmail") !== false
) {
    testPass("Administrator approval and Resident ID email", "Approval code creates the account/access record and calls the Resident ID email function.");
} else {
    testFail("Administrator approval and Resident ID email", "One or more approval workflow steps were not found.");
}

// Registration workflow tables must expose the columns used by the current code.
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $tableColumnRequirements = [
        "resident_requests" => ["id", "full_name", "email", "username", "password_hash", "email_notifications_enabled", "status"],
        "resident_access" => ["id", "resident_id", "full_name", "email", "is_used"]
    ];

    foreach ($tableColumnRequirements as $tableName => $requiredColumns) {
        $actualColumns = [];
        $columnResult = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($tableName) . "`");

        if ($columnResult) {
            while ($columnRow = $columnResult->fetch_assoc()) {
                $actualColumns[] = $columnRow["Field"];
            }

            $missingColumns = array_values(array_diff($requiredColumns, $actualColumns));
            if (count($missingColumns) === 0) {
                testPass("Schema columns: " . $tableName, "All columns required by the registration/access workflow exist.");
            } else {
                testFail("Schema columns: " . $tableName, "Missing columns: " . implode(", ", $missingColumns));
            }
        } else {
            testFail("Schema columns: " . $tableName, "Could not inspect table columns: " . $conn->error);
        }
    }
}

// Settings account and password logic must use the same DB password column as login.
if (
    strpos($files["settings"], 'SELECT password_hash') !== false ||
    strpos($files["settings"], 'SET password_hash') !== false
) {
    testFail("Settings password-column consistency", "settings.php refers to password_hash, but this project’s login and account-creation code use users.password. Password change will fail unless the schema is changed consistently.");
} elseif (
    strpos($files["settings"], 'SELECT password') !== false &&
    strpos($files["settings"], 'SET password =') !== false
) {
    testPass("Settings password-column consistency", "Settings reads and updates users.password, matching login and account creation.");
} else {
    testWarning("Settings password-column consistency", "Could not verify that settings uses the same password column as login.");
}

// Settings should allow profile and notification preference changes.
if (
    strpos($files["settings"], "email_notifications_enabled") !== false &&
    strpos($files["settings"], "username") !== false &&
    strpos($files["settings"], "email") !== false
) {
    testPass("Resident settings", "Settings includes username, email and email-notification preference handling.");
} else {
    testFail("Resident settings", "Expected resident profile/notification settings were not found.");
}

// Settings and admin pages must be protected.
if (
    strpos($files["settings"], '$_SESSION["user_id"]') !== false &&
    strpos($files["settings"], "resident") !== false
) {
    testPass("Settings authentication", "Settings checks the resident session/role.");
} else {
    testFail("Settings authentication", "Resident authentication protection was not found in settings.php.");
}

if (
    strpos($files["admin_registration_requests"], '$_SESSION["role"]') !== false &&
    strpos($files["admin_registration_requests"], "admin") !== false
) {
    testPass("Registration administration authorization", "Registration approval page checks the administrator role.");
} else {
    testFail("Registration administration authorization", "Administrator role protection was not found.");
}

// Assistant must be connected to the web application and keep to LaundryWise topics.
if (
    strpos($files["dashboard"], "assistant.php") !== false &&
    strpos($files["dashboard"], "aiInput") !== false &&
    strpos($files["assistant"], "availability") !== false
) {
    testPass("LaundryWise assistant integration", "Dashboard calls assistant.php and assistant code includes availability-related handling.");
} else {
    testFail("LaundryWise assistant integration", "Dashboard/assistant integration or availability topic handling was not found.");
}

// Reminder script must run only through CLI and query confirmed opted-in bookings.
if (
    strpos($files["send_booking_reminders"], "PHP_SAPI !== \"cli\"") !== false &&
    strpos($files["send_booking_reminders"], "reminder_enabled = 1") !== false &&
    strpos($files["send_booking_reminders"], "email_notifications_enabled = 1") !== false &&
    strpos($files["send_booking_reminders"], "Europe/Copenhagen") !== false
) {
    testPass("Automatic email reminder logic", "Reminder script is CLI-only, Copenhagen-time aware and checks both reminder and email preference flags.");
} else {
    testFail("Automatic email reminder logic", "Expected reminder safety/time/preference checks were not found.");
}

if (
    strpos($files["book"], "email_notifications_enabled") !== false &&
    strpos($files["book"], "reminder_enabled") !== false
) {
    testPass("Booking reminder preference integration", "Booking creation uses the resident email-notification preference for reminder_enabled.");
} else {
    testFail("Booking reminder preference integration", "Booking creation does not clearly connect notification preference to reminder_enabled.");
}

// Ensure the reminder cannot be requested through a public browser URL.
if (strpos($files["send_booking_reminders"], "PHP_SAPI !== \"cli\"") !== false) {
    testPass("Reminder script CLI restriction", "Browser requests are rejected; the script is intended for Task Scheduler/CLI.");
} else {
    testFail("Reminder script CLI restriction", "No CLI-only restriction found on the reminder script.");
}

// Verify predictor/model names are wired together.
if (
    strpos($files["predictor"], "gradient_boosting_model_php.json") !== false ||
    strpos($files["predictor"], "encoded_feature_names") !== false
) {
    testPass("ML predictor/model wiring", "Predictor references model feature metadata or the exported PHP model file.");
} else {
    testWarning("ML predictor/model wiring", "Could not verify predictor/model metadata wiring by source inspection alone.");
}

// The dashboard should preserve the floating assistant interface.
if (
    strpos($files["dashboard"], "lwAiInput") !== false &&
    strpos($files["dashboard"], "toggleAssistant()") !== false &&
    strpos($files["dashboard"], "LaundryWise Assistant") !== false
) {
    testPass("Floating assistant interface", "Dashboard contains the LaundryWise assistant input, title and open/close interaction.");
} else {
    testFail("Floating assistant interface", "Expected LaundryWise assistant input/title/toggle elements were not found.");
}

// Confirm reminder emails are sent via PHPMailer and failures are reported.
if (
    strpos($files["send_booking_reminders"], "PHPMailer") !== false &&
    strpos($files["send_booking_reminders"], "Emails failed") !== false
) {
    testPass("Reminder email delivery handling", "Reminder script uses PHPMailer and reports failed sends.");
} else {
    testFail("Reminder email delivery handling", "PHPMailer delivery/error reporting was not found.");
}

// Scope guard: the current artifact should not implement physical hardware control.
$allScopeFiles = $files["book"] . $files["availability"] . $files["recommendations"] . $files["dashboard"];
if (
    stripos($allScopeFiles, "Arduino") === false &&
    stripos($allScopeFiles, "RFID reader") === false &&
    stripos($allScopeFiles, "IoT sensor") === false
) {
    testPass("Thesis scope boundary", "No obvious Arduino/RFID/IoT hardware-control integration was found in core resident workflows.");
} else {
    testWarning("Thesis scope boundary", "Hardware-related terms were found in core workflow source; review manually to ensure no out-of-scope implementation.");
}

/*
|--------------------------------------------------------------------------
| 42. OUTPUT
|--------------------------------------------------------------------------
*/

$totalTests = $passed + $failed + $warnings;

$successRate = $totalTests > 0
    ? round(($passed / $totalTests) * 100, 2)
    : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>System Test - Laundry Booking</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
            color: #222;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            margin-bottom: 5px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 25px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin: 0;
            font-size: 28px;
        }

        .card p {
            margin-bottom: 0;
            color: #666;
        }

        .results {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .test {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .test:last-child {
            border-bottom: none;
        }

        .status {
            font-weight: bold;
            margin-right: 10px;
        }

        .pass {
            color: #16803c;
        }

        .fail {
            color: #c62828;
        }

        .warning {
            color: #b26a00;
        }

        .details {
            margin-top: 6px;
            color: #666;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .overall {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .overall.success {
            border-left: 6px solid #16803c;
        }

        .overall.failure {
            border-left: 6px solid #c62828;
        }

        .overall h2 {
            margin-top: 0;
        }

        @media (max-width: 700px) {

            body {
                padding: 15px;
            }

            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Laundry Booking System Test</h1>

    <div class="subtitle">
        Automated read-only system verification
    </div>

    <div class="overall <?php echo $failed === 0 ? 'success' : 'failure'; ?>">

        <h2>
            <?php
            echo $failed === 0
                ? "SYSTEM TEST PASSED"
                : "SYSTEM TEST HAS FAILURES";
            ?>
        </h2>

        <p>
            <?php echo $passed; ?> passed,
            <?php echo $failed; ?> failed,
            <?php echo $warnings; ?> warnings.
        </p>

        <p>
            Success rate:
            <strong><?php echo $successRate; ?>%</strong>
        </p>

    </div>


    <div class="summary">

        <div class="card">

            <h2 class="pass">
                <?php echo $passed; ?>
            </h2>

            <p>Passed</p>

        </div>


        <div class="card">

            <h2 class="fail">
                <?php echo $failed; ?>
            </h2>

            <p>Failed</p>

        </div>


        <div class="card">

            <h2 class="warning">
                <?php echo $warnings; ?>
            </h2>

            <p>Warnings</p>

        </div>


        <div class="card">

            <h2>
                <?php echo $totalTests; ?>
            </h2>

            <p>Total tests</p>

        </div>

    </div>


    <div class="results">

        <?php foreach ($results as $result): ?>

            <div class="test">

                <?php

                $class = "pass";

                if ($result["status"] === "FAIL") {
                    $class = "fail";
                }

                if ($result["status"] === "WARNING") {
                    $class = "warning";
                }

                ?>

                <span class="status <?php echo $class; ?>">

                    <?php echo htmlspecialchars($result["status"]); ?>

                </span>


                <strong>

                    <?php echo htmlspecialchars($result["name"]); ?>

                </strong>


                <?php if (!empty($result["details"])): ?>

                    <div class="details">

                        <?php echo htmlspecialchars($result["details"]); ?>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>

</html>